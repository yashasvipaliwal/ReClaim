<?php
/**
 * claim-create.php
 * Form to submit an ownership claim against a found report.
 *
 * Rules enforced here (and in action handler):
 *  - Only logged-in users
 *  - Cannot claim your own found report
 *  - Cannot claim a lost report (only found items can be claimed)
 *  - Cannot claim if a pending claim already exists for this report from this user
 *  - Report must be 'open'
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

require_login();

$reportId = (int) ($_GET['report_id'] ?? 0);
if ($reportId <= 0) redirect('report-list.php');

// Fetch the report
$stmt = $pdo->prepare(
    'SELECT r.*, u.name AS owner_name FROM reports r
     JOIN users u ON r.user_id = u.id
     WHERE r.id = ? AND r.is_deleted = 0'
);
$stmt->execute([$reportId]);
$report = $stmt->fetch();

if (!$report) {
    set_flash('error', 'Report not found.');
    redirect('report-list.php');
}

// Must be a found report that is still open
if ($report['type'] !== 'found' || $report['status'] !== 'open') {
    set_flash('error', 'You can only claim open "found" item reports.');
    redirect('report-view.php?id=' . $reportId);
}

// Cannot claim your own report
if ((int)$report['user_id'] === current_user_id()) {
    set_flash('error', 'You cannot claim your own found report.');
    redirect('report-view.php?id=' . $reportId);
}

// Already claimed by this user?
$stmt2 = $pdo->prepare('SELECT id, status FROM claims WHERE report_id = ? AND claimant_id = ?');
$stmt2->execute([$reportId, current_user_id()]);
$existing = $stmt2->fetch();
if ($existing) {
    set_flash('error', 'You have already submitted a claim for this report. Status: ' . $existing['status']);
    redirect('report-view.php?id=' . $reportId);
}

$pageTitle = 'Submit Ownership Claim';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-container" style="max-width:720px">
    <div class="page-header">
        <h1 class="page-title">📋 Submit Ownership Claim</h1>
        <p class="page-subtitle">Provide proof of ownership for the item below.</p>
    </div>

    <!-- Item summary card -->
    <div class="item-summary-card">
        <div class="item-summary-icon"><?= $report['image_path'] ?
            '<img src="/ReClaim/uploads/'.h($report['image_path']).'" alt="Item">' :
            '📦' ?></div>
        <div class="item-summary-info">
            <h3><?= h($report['title']) ?></h3>
            <span class="text-muted">
                📂 <?= h($report['category']) ?> &nbsp;|&nbsp;
                📍 <?= h($report['location'] ?: 'N/A') ?> &nbsp;|&nbsp;
                📅 <?= date('d M Y', strtotime($report['incident_date'])) ?>
            </span>
            <p>Found and reported by <strong><?= h($report['owner_name']) ?></strong></p>
        </div>
    </div>

    <div class="form-card">
        <div class="alert alert-info" style="margin-bottom:1.5rem">
            ⚠️ <strong>Important:</strong> Your verification details are kept private and only
            visible to administrators reviewing claims. Provide honest, specific information
            only you would know as the owner.
        </div>

        <form id="claimForm" action="actions/claim-action.php" method="POST" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="report_id" value="<?= $reportId ?>">

            <div class="form-group">
                <label for="verification_detail" class="form-label">
                    Proof of Ownership <span class="required">*</span>
                </label>
                <textarea id="verification_detail" name="verification_detail"
                          class="form-control form-textarea" rows="6" required
                          minlength="20" maxlength="2000"
                          placeholder="Describe specific details only the owner would know:
• Unique marks, scratches, or engravings
• Contents (if a bag or wallet)
• Serial number or IMEI
• Purchase details or receipts
• Any custom stickers or modifications"></textarea>
                <span class="form-hint char-counter" data-target="verification_detail">0 / 2000 characters</span>
                <span class="form-error" id="verification_detailError"></span>
            </div>

            <div class="form-actions">
                <a href="report-view.php?id=<?= $reportId ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary" id="submitClaimBtn">
                    Submit Claim
                </button>
            </div>
        </form>
    </div>

    <p class="text-muted text-center" style="margin-top:1rem; font-size:.875rem">
        Claims are reviewed by our administrators. You will be notified of the outcome.
        False claims may result in account suspension.
    </p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
