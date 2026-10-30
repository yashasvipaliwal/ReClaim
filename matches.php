<?php
/**
 * matches.php
 * Shows match suggestions for the logged-in user's reports.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

require_login();

$pageTitle  = 'My Matches';
$filterReport = (int)($_GET['report_id'] ?? 0);

// Fetch all the user's reports that have matches
$sql = "SELECT m.*, 
           lr.id AS lost_id, lr.title AS lost_title, lr.category AS lost_category, lr.location AS lost_location,
           lr.incident_date AS lost_date, lr.user_id AS lost_owner,
           fr.id AS found_id, fr.title AS found_title, fr.category AS found_category, fr.location AS found_location,
           fr.incident_date AS found_date, fr.user_id AS found_owner,
           fr.image_path AS found_image,
           lu.name AS lost_owner_name, fu.name AS found_owner_name
        FROM matches m
        JOIN reports lr ON lr.id = m.lost_id
        JOIN reports fr ON fr.id = m.found_id
        JOIN users lu ON lu.id = lr.user_id
        JOIN users fu ON fu.id = fr.user_id
        WHERE (lr.user_id = ? OR fr.user_id = ?) AND m.status = 'pending'";

$params = [current_user_id(), current_user_id()];

if ($filterReport > 0) {
    $sql .= ' AND (lr.id = ? OR fr.id = ?)';
    $params[] = $filterReport;
    $params[] = $filterReport;
}

$sql .= ' ORDER BY m.score DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$matches = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-container">
    <div class="page-header">
        <h1 class="page-title">🔗 Match Suggestions</h1>
        <p class="page-subtitle">
            Automatically suggested matches between lost and found reports.
            These are <em>possible</em> matches — not confirmed ownership.
        </p>
    </div>

    <!-- How it works callout -->
    <div class="info-callout">
        <h3>🤖 How Matching Works</h3>
        <div class="score-breakdown">
            <div class="score-item"><span class="score-pts">30 pts</span><span>Same category</span></div>
            <div class="score-item"><span class="score-pts">30 pts</span><span>Similar item name/title</span></div>
            <div class="score-item"><span class="score-pts">20 pts</span><span>Matching location</span></div>
            <div class="score-item"><span class="score-pts">20 pts</span><span>Close incident dates</span></div>
        </div>
        <p>Matches with a score ≥ 60 are shown here. Contact the other party through a claim submission.</p>
    </div>

    <?php if (empty($matches)): ?>
        <div class="empty-state">
            <div class="empty-icon">🔍</div>
            <h3>No matches yet</h3>
            <p>Matches are automatically found when you submit a report. Make sure your report has
               complete details (category, location, date) for better matching.</p>
            <a href="report-create.php" class="btn btn-primary">+ Create a Report</a>
        </div>
    <?php else: ?>
        <div class="matches-list">
            <?php foreach ($matches as $m): ?>
            <div class="match-detail-card">
                <!-- Score badge -->
                <div class="match-score-large <?= $m['score'] >= 80 ? 'score-high' : ($m['score'] >= 60 ? 'score-medium' : 'score-low') ?>">
                    <span class="score-number"><?= $m['score'] ?></span>
                    <span class="score-label">/ 100</span>
                </div>

                <!-- Lost side -->
                <div class="match-side match-lost">
                    <div class="match-side-label">😟 Lost Item</div>
                    <h4><a href="report-view.php?id=<?= $m['lost_id'] ?>"><?= h($m['lost_title']) ?></a></h4>
                    <div class="match-side-meta">
                        <span>📂 <?= h($m['lost_category']) ?></span>
                        <span>📍 <?= h($m['lost_location'] ?: 'N/A') ?></span>
                        <span>📅 <?= date('d M Y', strtotime($m['lost_date'])) ?></span>
                        <span>👤 <?= h($m['lost_owner_name']) ?></span>
                    </div>
                </div>

                <!-- Match divider -->
                <div class="match-divider">
                    <span class="match-arrow">⟷</span>
                    <?php
                    $fields = json_decode($m['matched_fields'] ?? '[]', true) ?: [];
                    ?>
                    <div class="matched-on">
                        <span class="matched-label">Matched on:</span>
                        <?php foreach ($fields as $f): ?>
                            <span class="match-field-tag"><?= h(ucfirst($f)) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Found side -->
                <div class="match-side match-found">
                    <div class="match-side-label">🎉 Found Item</div>
                    <?php if ($m['found_image']): ?>
                        <img src="/ReClaim/uploads/<?= h($m['found_image']) ?>" class="match-thumb-img" alt="Found item">
                    <?php endif; ?>
                    <h4><a href="report-view.php?id=<?= $m['found_id'] ?>"><?= h($m['found_title']) ?></a></h4>
                    <div class="match-side-meta">
                        <span>📂 <?= h($m['found_category']) ?></span>
                        <span>📍 <?= h($m['found_location'] ?: 'N/A') ?></span>
                        <span>📅 <?= date('d M Y', strtotime($m['found_date'])) ?></span>
                        <span>👤 <?= h($m['found_owner_name']) ?></span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="match-card-actions">
                    <?php if ($m['found_owner'] !== current_user_id()): ?>
                        <!-- Check if user has already claimed -->
                        <?php
                        $cs = $pdo->prepare('SELECT id FROM claims WHERE report_id=? AND claimant_id=?');
                        $cs->execute([$m['found_id'], current_user_id()]);
                        $hasClaim = $cs->fetch();
                        ?>
                        <?php if (!$hasClaim): ?>
                            <a href="claim-create.php?report_id=<?= $m['found_id'] ?>" class="btn btn-primary btn-sm">
                                📋 Claim This Item
                            </a>
                        <?php else: ?>
                            <span class="badge badge-info">Claim Submitted</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a href="report-view.php?id=<?= $m['found_id'] ?>" class="btn btn-outline btn-sm">
                        View Found Report →
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
