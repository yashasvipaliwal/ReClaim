<?php
/**
 * dashboard.php
 * User dashboard showing personal stats, recent reports, and claims.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

require_login();

$pageTitle = 'Dashboard';
$userId    = current_user_id();

// Personal stats — all use prepared statements for safety
$s1 = $pdo->prepare('SELECT COUNT(*) FROM reports WHERE user_id=? AND is_deleted=0');
$s1->execute([$userId]);
$myReports = (int) $s1->fetchColumn();

$s2 = $pdo->prepare('SELECT COUNT(*) FROM claims WHERE claimant_id=?');
$s2->execute([$userId]);
$myClaims  = (int) $s2->fetchColumn();

$s3 = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id=? AND status='resolved'");
$s3->execute([$userId]);
$myResolved = (int) $s3->fetchColumn();

$s4 = $pdo->prepare(
    "SELECT COUNT(*) FROM matches m
     JOIN reports r ON (r.id = m.lost_id OR r.id = m.found_id)
     WHERE r.user_id = ? AND m.status = 'pending'"
);
$s4->execute([$userId]);
$myMatches = (int) $s4->fetchColumn();

$unread = get_unread_count($pdo, $userId);

// Recent reports
$stmt = $pdo->prepare(
    'SELECT * FROM reports WHERE user_id = ? AND is_deleted = 0 ORDER BY created_at DESC LIMIT 5'
);
$stmt->execute([$userId]);
$recentReports = $stmt->fetchAll();

// Recent claims
$stmt2 = $pdo->prepare(
    'SELECT c.*, r.title FROM claims c JOIN reports r ON r.id=c.report_id WHERE c.claimant_id=? ORDER BY c.created_at DESC LIMIT 5'
);
$stmt2->execute([$userId]);
$recentClaims = $stmt2->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-container">
    <!-- Welcome banner -->
    <div class="dashboard-welcome">
        <div class="welcome-text">
            <h1>Welcome back, <?= h(current_user_name()) ?>! 👋</h1>
            <p>Here's a summary of your ReClaim activity.</p>
        </div>
        <div class="welcome-actions">
            <a href="report-create.php?type=lost" class="btn btn-outline">😟 Report Lost</a>
            <a href="report-create.php?type=found" class="btn btn-primary">🎉 Report Found</a>
        </div>
    </div>

    <!-- Stats row -->
    <div class="dashboard-stats">
        <a href="report-list.php?user_id=<?= $userId ?>" class="dash-stat">
            <span class="dash-stat-value"><?= $myReports ?></span>
            <span class="dash-stat-label">My Reports</span>
        </a>
        <a href="my-claims.php" class="dash-stat">
            <span class="dash-stat-value"><?= $myClaims ?></span>
            <span class="dash-stat-label">My Claims</span>
        </a>
        <a href="matches.php" class="dash-stat dash-stat-accent">
            <span class="dash-stat-value"><?= $myMatches ?></span>
            <span class="dash-stat-label">Matches Found</span>
        </a>
        <a href="notifications.php" class="dash-stat <?= $unread ? 'dash-stat-warn':'' ?>">
            <span class="dash-stat-value"><?= $unread ?></span>
            <span class="dash-stat-label">Unread Notifications</span>
        </a>
        <div class="dash-stat dash-stat-success">
            <span class="dash-stat-value"><?= $myResolved ?></span>
            <span class="dash-stat-label">Items Returned</span>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Recent reports -->
        <div class="dashboard-panel">
            <div class="panel-header">
                <h2>📋 My Recent Reports</h2>
                <a href="report-create.php" class="btn btn-sm btn-outline">+ New</a>
            </div>

            <?php if (empty($recentReports)): ?>
                <div class="panel-empty">
                    <p>You haven't submitted any reports yet.</p>
                    <a href="report-create.php" class="btn btn-primary btn-sm">Submit your first report</a>
                </div>
            <?php else: ?>
                <ul class="dashboard-list">
                    <?php foreach ($recentReports as $r): ?>
                    <li class="dashboard-list-item">
                        <div class="dli-info">
                            <a href="report-view.php?id=<?= $r['id'] ?>" class="dli-title">
                                <?= h($r['title']) ?>
                            </a>
                            <span class="dli-meta">
                                <?= format_type_badge($r['type']) ?>
                                <?= format_status_badge($r['status']) ?>
                                &nbsp; <?= time_ago($r['created_at']) ?>
                            </span>
                        </div>
                        <a href="report-view.php?id=<?= $r['id'] ?>" class="btn btn-xs btn-outline">View</a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <a href="report-list.php" class="panel-link">View all reports →</a>
            <?php endif; ?>
        </div>

        <!-- Recent claims -->
        <div class="dashboard-panel">
            <div class="panel-header">
                <h2>📩 My Recent Claims</h2>
                <a href="my-claims.php" class="btn btn-sm btn-outline">All Claims</a>
            </div>

            <?php if (empty($recentClaims)): ?>
                <div class="panel-empty">
                    <p>No claims submitted yet.</p>
                    <a href="report-list.php?type=found" class="btn btn-outline btn-sm">Browse Found Items</a>
                </div>
            <?php else: ?>
                <ul class="dashboard-list">
                    <?php foreach ($recentClaims as $c): ?>
                    <li class="dashboard-list-item">
                        <div class="dli-info">
                            <a href="report-view.php?id=<?= $c['report_id'] ?>" class="dli-title">
                                <?= h($c['title']) ?>
                            </a>
                            <span class="dli-meta">
                                <?= format_status_badge($c['status']) ?>
                                &nbsp; <?= time_ago($c['created_at']) ?>
                            </span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <a href="my-claims.php" class="panel-link">View all claims →</a>
            <?php endif; ?>
        </div>
    </div><!-- /.dashboard-grid -->

    <?php if (is_admin()): ?>
    <div class="admin-notice-card">
        <span>⚙️ Admin Panel</span>
        <p>You have admin privileges. Manage reports, claims and users.</p>
        <a href="admin.php" class="btn btn-primary">Open Admin Panel →</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
