<?php
/**
 * notifications.php
 * Shows all notifications for the logged-in user and marks them as read.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

require_login();

$pageTitle = 'Notifications';

// Mark all as read when this page is opened
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")
    ->execute([current_user_id()]);

// Fetch all notifications newest-first
$stmt = $pdo->prepare(
    'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50'
);
$stmt->execute([current_user_id()]);
$notifications = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-container" style="max-width:760px">
    <div class="page-header">
        <h1 class="page-title">🔔 Notifications</h1>
        <p class="page-subtitle">Recent updates about your reports, matches, and claims.</p>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <div class="empty-icon">🔔</div>
            <h3>No notifications yet</h3>
            <p>You'll be notified when matches are found or your claims are reviewed.</p>
        </div>
    <?php else: ?>
        <div class="notifications-list">
            <?php foreach ($notifications as $n): ?>
            <div class="notif-item <?= $n['is_read'] ? '' : 'notif-unread' ?>">
                <span class="notif-dot"></span>
                <div class="notif-content">
                    <p class="notif-message"><?= h($n['message']) ?></p>
                    <span class="notif-time"><?= time_ago($n['created_at']) ?></span>
                </div>
                <?php if ($n['link']): ?>
                <a href="<?= h($n['link']) ?>" class="btn btn-sm btn-outline notif-action">
                    View →
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
