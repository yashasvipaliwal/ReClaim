<?php
/**
 * matching/matcher.php
 * ─────────────────────────────────────────────────────────────
 * Rule-based matching algorithm for ReClaim.
 *
 * Algorithm overview (for viva):
 *   The system compares LOST reports against FOUND reports using
 *   four weighted criteria. Each criterion contributes a portion
 *   of the total 100-point score:
 *
 *   ┌─────────────────────────────┬──────────┐
 *   │ Criterion                   │ Max pts  │
 *   ├─────────────────────────────┼──────────┤
 *   │ 1. Same category            │   30     │
 *   │ 2. Similar title/item name  │   30     │
 *   │ 3. Matching location        │   20     │
 *   │ 4. Close incident dates     │   20     │
 *   └─────────────────────────────┴──────────┘
 *   Total: 100 points max
 *
 * If score ≥ MATCH_THRESHOLD, the pair is saved to the matches
 * table as a suggestion. This is NOT proof of ownership — it is
 * a starting point for manual verification.
 *
 * No AI or ML is used. This is deterministic and explainable.
 */

define('MATCH_THRESHOLD', 60); // Configurable; change here to adjust sensitivity

// ─────────────────────────────────────────────────────────────
// Main scoring function
// Returns: ['score' => int(0-100), 'matched_fields' => string[]]
// ─────────────────────────────────────────────────────────────
function calculate_match_score(array $lost, array $found): array
{
    $score          = 0;
    $matched_fields = [];

    // ── 1. Category match (30 points) ─────────────────────────
    if (!empty($lost['category']) && !empty($found['category'])) {
        if (strtolower(trim($lost['category'])) === strtolower(trim($found['category']))) {
            $score += 30;
            $matched_fields[] = 'category';
        }
    }

    // ── 2. Title / item-name similarity (up to 30 points) ─────
    // PHP's similar_text() returns a percentage similarity.
    // We scale 0–100% → 0–30 points.
    $titleScore = _title_similarity_score(
        $lost['title']  ?? '',
        $found['title'] ?? ''
    );
    $score += $titleScore;
    if ($titleScore >= 12) { // at least 40% similar titles
        $matched_fields[] = 'title';
    }

    // ── 3. Location match (up to 20 points) ───────────────────
    $locScore = _location_score(
        $lost['location']  ?? '',
        $found['location'] ?? ''
    );
    $score += $locScore;
    if ($locScore > 0) {
        $matched_fields[] = 'location';
    }

    // ── 4. Date proximity (up to 20 points) ───────────────────
    $dateScore = _date_proximity_score(
        $lost['incident_date']  ?? '',
        $found['incident_date'] ?? ''
    );
    $score += $dateScore;
    if ($dateScore > 0) {
        $matched_fields[] = 'date';
    }

    return [
        'score'          => min(100, max(0, $score)),
        'matched_fields' => $matched_fields,
    ];
}

// ─────────────────────────────────────────────────────────────
// Private helper: title similarity → 0–30 points
// ─────────────────────────────────────────────────────────────
function _title_similarity_score(string $a, string $b): int
{
    if (empty($a) || empty($b)) {
        return 0;
    }
    $a = strtolower(trim($a));
    $b = strtolower(trim($b));

    // similar_text writes the percentage to $pct by reference
    similar_text($a, $b, $pct);

    // Also check if key words overlap (brand names, colours, etc.)
    $aWords = array_filter(explode(' ', preg_replace('/[^a-z0-9 ]/', ' ', $a)));
    $bWords = array_filter(explode(' ', preg_replace('/[^a-z0-9 ]/', ' ', $b)));
    $stopWords  = ['a', 'an', 'the', 'my', 'i', 'its', 'it', 'lost', 'found'];
    $aKeywords  = array_diff($aWords, $stopWords);
    $bKeywords  = array_diff($bWords, $stopWords);
    $commonWords = array_intersect($aKeywords, $bKeywords);
    // Boost if there are matching keywords
    $wordBoost  = count($commonWords) >= 2 ? 10 : (count($commonWords) === 1 ? 5 : 0);

    $similarityPoints = (int) round(($pct / 100) * 20); // 0-20 from similar_text
    return min(30, $similarityPoints + $wordBoost);
}

// ─────────────────────────────────────────────────────────────
// Private helper: location match → 0, 10, 15, or 20 points
// ─────────────────────────────────────────────────────────────
function _location_score(string $a, string $b): int
{
    if (empty($a) || empty($b)) {
        return 0;
    }

    // Normalise: lowercase, collapse whitespace
    $a = strtolower(preg_replace('/\s+/', ' ', trim($a)));
    $b = strtolower(preg_replace('/\s+/', ' ', trim($b)));

    if ($a === $b) {
        return 20; // exact match
    }

    // Substring containment (one is part of the other)
    if (str_contains($a, $b) || str_contains($b, $a)) {
        return 15;
    }

    // Shared meaningful words
    $stopWords = ['the', 'a', 'an', 'in', 'at', 'on', 'of', 'and', 'or', 'near', 'by', 'beside', 'next'];
    $aWords    = array_diff(explode(' ', $a), $stopWords);
    $bWords    = array_diff(explode(' ', $b), $stopWords);
    $common    = array_intersect(array_filter($aWords), array_filter($bWords));

    if (count($common) >= 2) {
        return 15;
    }
    if (count($common) === 1) {
        return 10;
    }

    return 0;
}

// ─────────────────────────────────────────────────────────────
// Private helper: date proximity → 0, 5, 10, 15, or 20 points
// ─────────────────────────────────────────────────────────────
function _date_proximity_score(string $lostDate, string $foundDate): int
{
    if (empty($lostDate) || empty($foundDate)) {
        return 0;
    }

    try {
        $d1   = new DateTime($lostDate);
        $d2   = new DateTime($foundDate);
        $diff = abs((int) $d1->diff($d2)->days);

        // Found item should be AFTER the lost date (up to 30 days later is realistic)
        // We also score backward matches slightly (someone reports finding after losing)
        if ($diff === 0)        return 20;
        if ($diff <= 3)         return 15;
        if ($diff <= 7)         return 10;
        if ($diff <= 14)        return 5;
        return 0;
    } catch (Exception $e) {
        return 0;
    }
}

// ─────────────────────────────────────────────────────────────
// Run matching for a newly created report against all existing
// opposite-type reports. Inserts new rows into `matches` table.
// Called automatically after a report is saved.
// ─────────────────────────────────────────────────────────────
function run_matching_for_report(PDO $pdo, int $newReportId): void
{
    // Fetch the new report
    $stmt = $pdo->prepare(
        'SELECT * FROM reports WHERE id = ? AND is_deleted = 0 AND status = "open"'
    );
    $stmt->execute([$newReportId]);
    $newReport = $stmt->fetch();

    if (!$newReport) {
        return; // Report not found or not eligible
    }

    // Get all open reports of the OPPOSITE type
    $oppositeType = ($newReport['type'] === 'lost') ? 'found' : 'lost';
    $stmt = $pdo->prepare(
        'SELECT * FROM reports WHERE type = ? AND is_deleted = 0 AND status = "open" AND id != ?'
    );
    $stmt->execute([$oppositeType, $newReportId]);
    $candidates = $stmt->fetchAll();

    // Prepare insert statement (INSERT IGNORE prevents duplicate pairs)
    $insertStmt = $pdo->prepare(
        'INSERT IGNORE INTO matches (lost_id, found_id, score, matched_fields)
         VALUES (?, ?, ?, ?)'
    );

    foreach ($candidates as $candidate) {
        // Decide which is lost and which is found
        if ($newReport['type'] === 'lost') {
            $lost  = $newReport;
            $found = $candidate;
        } else {
            $lost  = $candidate;
            $found = $newReport;
        }

        $result = calculate_match_score($lost, $found);

        // Only save pairs above the threshold
        if ($result['score'] >= MATCH_THRESHOLD) {
            $insertStmt->execute([
                $lost['id'],
                $found['id'],
                $result['score'],
                json_encode($result['matched_fields']),
            ]);

            // Notify both report owners
            _notify_match($pdo, $lost, $found, $result['score']);
        }
    }
}

// ─────────────────────────────────────────────────────────────
// Private helper: send notifications to both report owners
// when a match is found.
// ─────────────────────────────────────────────────────────────
function _notify_match(PDO $pdo, array $lost, array $found, int $score): void
{
    require_once __DIR__ . '/../includes/functions.php';

    // Notify lost-item owner
    create_notification(
        $pdo,
        (int) $lost['user_id'],
        "A potential match (score: {$score}/100) was found for your lost report: \"{$lost['title']}\".",
        "matches.php?report_id={$lost['id']}"
    );

    // Notify found-item reporter (they may know who owns it)
    if ((int) $found['user_id'] !== (int) $lost['user_id']) {
        create_notification(
            $pdo,
            (int) $found['user_id'],
            "Your found-item report \"{$found['title']}\" matches a lost-item report (score: {$score}/100).",
            "matches.php?report_id={$found['id']}"
        );
    }
}
