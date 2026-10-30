<?php
/**
 * actions/claim-action.php
 * ─────────────────────────────────────────────────────────────
 * Handles claim creation, approval, and rejection.
 * Approval uses a DB transaction to keep report status and
 * claim status consistent, and rejects other pending claims.
 */
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/config/db.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../dashboard.php');
}

verify_csrf();

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create': handle_claim_create($pdo); break;
    case 'approve': handle_claim_review($pdo, 'approved'); break;
    case 'reject':  handle_claim_review($pdo, 'rejected'); break;
    default:
        set_flash('error', 'Invalid action.');
        redirect('../dashboard.php');
}

// ─────────────────────────────────────────────────────────────
// Create a new claim
// ─────────────────────────────────────────────────────────────
function handle_claim_create(PDO $pdo): void
{
    $reportId = (int) ($_POST['report_id'] ?? 0);
    $detail   = trim($_POST['verification_detail'] ?? '');

    // Validation
    if ($reportId <= 0 || strlen($detail) < 20) {
        set_flash('error', 'Please provide at least 20 characters of ownership proof.');
        redirect('../claim-create.php?report_id=' . $reportId);
    }

    // Reload report with security checks
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ? AND is_deleted = 0 AND type = 'found' AND status = 'open'");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();

    if (!$report) {
        set_flash('error', 'This report is not eligible for claims.');
        redirect('../report-list.php');
    }

    // Cannot claim own report
    if ((int)$report['user_id'] === current_user_id()) {
        set_flash('error', 'You cannot claim your own report.');
        redirect('../report-view.php?id=' . $reportId);
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO claims (report_id, claimant_id, verification_detail) VALUES (?, ?, ?)'
        );
        $stmt->execute([$reportId, current_user_id(), $detail]);
        $claimId = (int) $pdo->lastInsertId();

        // Log the creation event
        $histStmt = $pdo->prepare(
            'INSERT INTO claim_history (claim_id, actor_id, old_status, new_status, note)
             VALUES (?, ?, NULL, "pending", "Claim submitted.")'
        );
        $histStmt->execute([$claimId, current_user_id()]);

        // Update report status to 'claimed'
        $pdo->prepare("UPDATE reports SET status = 'claimed' WHERE id = ? AND status = 'open'")->execute([$reportId]);

        // Notify the found-item reporter
        create_notification(
            $pdo,
            (int) $report['user_id'],
            'A new ownership claim has been submitted for your found report: "' . $report['title'] . '".',
            'admin.php?tab=claims'
        );

    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            set_flash('error', 'You have already submitted a claim for this report.');
        } else {
            error_log('[ReClaim Claim Error] ' . $e->getMessage());
            set_flash('error', 'Failed to submit claim. Please try again.');
        }
        redirect('../claim-create.php?report_id=' . $reportId);
    }

    set_flash('success', 'Your claim has been submitted! An administrator will review it shortly.');
    redirect('../my-claims.php');
}

// ─────────────────────────────────────────────────────────────
// Approve or reject a claim (admin only)
// Uses a transaction to maintain DB consistency.
// ─────────────────────────────────────────────────────────────
function handle_claim_review(PDO $pdo, string $newStatus): void
{
    require_admin();

    $claimId = (int) ($_POST['claim_id'] ?? 0);
    $note    = trim($_POST['review_note'] ?? '');

    if ($claimId <= 0) {
        redirect('../admin.php?tab=claims');
    }

    // Fetch the claim
    $stmt = $pdo->prepare('SELECT * FROM claims WHERE id = ?');
    $stmt->execute([$claimId]);
    $claim = $stmt->fetch();

    if (!$claim || $claim['status'] !== 'pending') {
        set_flash('error', 'Claim not found or already processed.');
        redirect('../admin.php?tab=claims');
    }

    // ── Begin DB transaction ──────────────────────────────────
    // This ensures all changes happen atomically (all or nothing).
    $pdo->beginTransaction();

    try {
        $oldStatus = $claim['status'];

        // 1. Update the claim status
        $pdo->prepare('UPDATE claims SET status = ?, reviewer_id = ? WHERE id = ?')
            ->execute([$newStatus, current_user_id(), $claimId]);

        // 2. Log the status change
        $pdo->prepare(
            'INSERT INTO claim_history (claim_id, actor_id, old_status, new_status, note)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$claimId, current_user_id(), $oldStatus, $newStatus, $note]);

        if ($newStatus === 'approved') {
            // 3a. Mark the report as resolved
            $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?")
                ->execute([$claim['report_id']]);

            // 3b. Reject all OTHER pending claims for this report (prevent double approval)
            $otherClaims = $pdo->prepare(
                "SELECT id FROM claims WHERE report_id = ? AND id != ? AND status = 'pending'"
            );
            $otherClaims->execute([$claim['report_id'], $claimId]);

            $rejectOthers = $pdo->prepare('UPDATE claims SET status = "rejected" WHERE id = ?');
            $histOther    = $pdo->prepare(
                'INSERT INTO claim_history (claim_id, actor_id, old_status, new_status, note)
                 VALUES (?, ?, "pending", "rejected", "Auto-rejected: another claim was approved.")'
            );

            foreach ($otherClaims->fetchAll() as $other) {
                $rejectOthers->execute([$other['id']]);
                $histOther->execute([$other['id'], current_user_id()]);

                // Notify rejected claimants
                $otherClaim = $pdo->prepare('SELECT claimant_id FROM claims WHERE id = ?');
                $otherClaim->execute([$other['id']]);
                $otherClaimData = $otherClaim->fetch();
                if ($otherClaimData) {
                    create_notification($pdo, $otherClaimData['claimant_id'],
                        'Your ownership claim has been rejected as another claim was approved.',
                        'my-claims.php');
                }
            }

            // Notify the approved claimant
            create_notification($pdo, (int) $claim['claimant_id'],
                '🎉 Your ownership claim has been APPROVED! Please collect your item.',
                'my-claims.php');

        } else {
            // 4. Rejected: revert report to open if no other claims pending
            $pendingCount = $pdo->prepare(
                "SELECT COUNT(*) FROM claims WHERE report_id = ? AND status = 'pending'"
            );
            $pendingCount->execute([$claim['report_id']]);
            if ((int)$pendingCount->fetchColumn() === 0) {
                $pdo->prepare("UPDATE reports SET status = 'open' WHERE id = ? AND status = 'claimed'")
                    ->execute([$claim['report_id']]);
            }

            // Notify claimant of rejection
            create_notification($pdo, (int) $claim['claimant_id'],
                'Your ownership claim has been reviewed and rejected.' . ($note ? ' Note: ' . $note : ''),
                'my-claims.php');
        }

        $pdo->commit();
        set_flash('success', 'Claim has been ' . $newStatus . ' successfully.');

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('[ReClaim Claim Review Error] ' . $e->getMessage());
        set_flash('error', 'An error occurred while processing the claim. Please try again.');
    }

    redirect('../admin.php?tab=claims');
}
