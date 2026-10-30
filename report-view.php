<?php
/**
 * report-view.php
 * Displays a single report with full details, match suggestions,
 * and option to submit a claim (for found items).
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

$reportId = (int) ($_GET['id'] ?? 0);
if ($reportId <= 0) {
    redirect('report-list.php');
}

// Fetch report with owner info
$stmt = $pdo->prepare(
    'SELECT r.*, u.name AS owner_name
     FROM reports r JOIN users u ON r.user_id = u.id
     WHERE r.id = ? AND r.is_deleted = 0'
);
$stmt->execute([$reportId]);
$report = $stmt->fetch();

if (!$report) {
    set_flash('error', 'Report not found or has been removed.');
    redirect('report-list.php');
}

// Has the logged-in user already submitted a claim for this report?
$alreadyClaimed = false;
$userClaim      = null;
if (is_logged_in()) {
    $stmt2 = $pdo->prepare('SELECT * FROM claims WHERE report_id = ? AND claimant_id = ?');
    $stmt2->execute([$reportId, current_user_id()]);
    $userClaim = $stmt2->fetch();
    $alreadyClaimed = ($userClaim !== false);
}

// Count of pending/approved claims (for admin view)
$claimCount = 0;
if (is_admin()) {
    $stmt3 = $pdo->prepare("SELECT COUNT(*) FROM claims WHERE report_id = ? AND status != 'rejected'");
    $stmt3->execute([$reportId]);
    $claimCount = (int) $stmt3->fetchColumn();
}

// Fetch matches for this report (if logged in and owner or admin)
$matches = [];
$canSeeMatches = is_logged_in() && ((int)$report['user_id'] === current_user_id() || is_admin());
if ($canSeeMatches) {
    $col = ($report['type'] === 'lost') ? 'lost_id' : 'found_id';
    $otherCol = ($report['type'] === 'lost') ? 'found_id' : 'lost_id';
    $stmt4 = $pdo->prepare(
        "SELECT m.*, r.title AS match_title, r.location AS match_location,
                r.incident_date AS match_date, r.type AS match_type,
                r.category AS match_category, u.name AS match_owner
         FROM matches m
         JOIN reports r ON r.id = m.{$otherCol}
         JOIN users u ON u.id = r.user_id
         WHERE m.{$col} = ? AND m.status = 'pending' AND r.is_deleted = 0
         ORDER BY m.score DESC LIMIT 5"
    );
    $stmt4->execute([$reportId]);
    $matches = $stmt4->fetchAll();
}

$pageTitle = h($report['title']);
require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-container">
    <!-- Breadcrumb -->
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="report-list.php">Browse Reports</a> &rsaquo;
        <span><?= h($report['title']) ?></span>
    </nav>

    <div class="report-detail-grid">
        <!-- Main content -->
        <div class="report-detail-main">
            <div class="report-detail-card">
                <!-- Report header -->
                <div class="report-detail-header">
                    <div class="report-badges">
                        <?= format_type_badge($report['type']) ?>
                        <?= format_status_badge($report['status']) ?>
                        <span class="badge badge-category"><?= h($report['category']) ?></span>
                    </div>
                    <h1 class="report-detail-title"><?= h($report['title']) ?></h1>

                    <div class="report-meta">
                        <span title="Location">📍 <?= h($report['location'] ?: 'Location not specified') ?></span>
                        <span title="Date">📅 <?= date('d M Y', strtotime($report['incident_date'])) ?></span>
                        <span title="Reported by">👤 Reported by <?= h($report['owner_name']) ?></span>
                        <span title="Posted">🕐 <?= time_ago($report['created_at']) ?></span>
                    </div>
                </div>

                <!-- Image -->
                <?php if ($report['image_path']): ?>
                <div class="report-image-wrap">
                    <img src="/ReClaim/uploads/<?= h($report['image_path']) ?>"
                         alt="<?= h($report['title']) ?>" class="report-image">
                </div>
                <?php endif; ?>

                <!-- Description -->
                <div class="report-description">
                    <h2 class="section-title">Description</h2>
                    <?php if ($report['description']): ?>
                        <p><?= nl2br(h($report['description'])) ?></p>
                    <?php else: ?>
                        <p class="text-muted">No additional description provided.</p>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="report-actions">
                    <?php if (is_logged_in()): ?>
                        <?php if ((int)$report['user_id'] === current_user_id()): ?>
                            <!-- Owner actions -->
                            <a href="report-edit.php?id=<?= $reportId ?>" class="btn btn-outline">✏️ Edit</a>
                            <form method="POST" action="actions/report-action.php"
                                  onsubmit="return confirm('Delete this report? This cannot be undone.')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="report_id" value="<?= $reportId ?>">
                                <button type="submit" class="btn btn-danger">🗑️ Delete</button>
                            </form>
                        <?php elseif ($report['type'] === 'found' && $report['status'] === 'open'): ?>
                            <!-- Claim button for found items (not your own) -->
                            <?php if ($alreadyClaimed): ?>
                                <div class="alert alert-info">
                                    You have already submitted a claim for this item.
                                    Status: <?= format_status_badge($userClaim['status']) ?>
                                </div>
                            <?php else: ?>
                                <a href="claim-create.php?report_id=<?= $reportId ?>" class="btn btn-primary">
                                    📋 Submit Ownership Claim
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($report['type'] === 'found'): ?>
                            <a href="login.php?redirect=report-view.php?id=<?= $reportId ?>"
                               class="btn btn-primary">🔑 Login to Claim This Item</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div><!-- /.report-detail-card -->

            <!-- Matches section (owner / admin only) -->
            <?php if ($canSeeMatches && !empty($matches)): ?>
            <div class="section-card">
                <h2 class="section-title">🔗 Potential Matches (<?= count($matches) ?>)</h2>
                <p class="text-muted" style="margin-bottom:1rem">
                    These are automatically suggested matches. They are not proof of ownership.
                </p>
                <?php foreach ($matches as $m): ?>
                <div class="match-card">
                    <div class="match-score-ring" title="Match score">
                        <span><?= $m['score'] ?></span>
                        <small>/ 100</small>
                    </div>
                    <div class="match-info">
                        <strong><?= h($m['match_title']) ?></strong>
                        <span class="text-muted">
                            <?= format_type_badge($m['match_type']) ?>
                            📍 <?= h($m['match_location'] ?: 'N/A') ?>
                            📅 <?= date('d M Y', strtotime($m['match_date'])) ?>
                        </span>
                        <?php
                        $fields = json_decode($m['matched_fields'] ?? '[]', true) ?: [];
                        if ($fields): ?>
                        <span class="match-fields">
                            Matched on:
                            <?php foreach ($fields as $f): ?>
                                <span class="match-field-tag"><?= h(ucfirst($f)) ?></span>
                            <?php endforeach; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <a href="report-view.php?id=<?= ($report['type']==='lost') ? $m['found_id'] : $m['lost_id'] ?>"
                       class="btn btn-sm btn-outline">View →</a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div><!-- /.report-detail-main -->

        <!-- Sidebar -->
        <aside class="report-detail-sidebar">
            <div class="sidebar-card">
                <h3>Report Details</h3>
                <dl class="detail-list">
                    <dt>Report ID</dt><dd>#<?= $reportId ?></dd>
                    <dt>Type</dt><dd><?= format_type_badge($report['type']) ?></dd>
                    <dt>Category</dt><dd><?= h($report['category']) ?></dd>
                    <dt>Status</dt><dd><?= format_status_badge($report['status']) ?></dd>
                    <dt>Incident Date</dt><dd><?= date('d M Y', strtotime($report['incident_date'])) ?></dd>
                    <dt>Posted</dt><dd><?= date('d M Y', strtotime($report['created_at'])) ?></dd>
                </dl>

                <?php if (is_admin() && $claimCount > 0): ?>
                <div class="admin-sidebar-info">
                    <strong>⚠️ <?= $claimCount ?> claim(s)</strong> pending for this report.
                    <a href="admin.php?tab=claims&report_id=<?= $reportId ?>">Review in Admin</a>
                </div>
                <?php endif; ?>
            </div>

            <div class="sidebar-card">
                <h3>Share this report</h3>
                <button class="btn btn-outline btn-full" onclick="
                    navigator.clipboard.writeText(window.location.href);
                    this.textContent='✅ Link Copied!';
                    setTimeout(()=>this.textContent='📋 Copy Link',2000)
                ">📋 Copy Link</button>
            </div>
        </aside>
    </div><!-- /.report-detail-grid -->
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
