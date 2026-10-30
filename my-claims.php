<?php
/**
 * my-claims.php
 * Shows all claims submitted by the currently logged-in user.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

require_login();

$pageTitle = 'My Claims';

// Fetch all claims by this user with report and reviewer info
$stmt = $pdo->prepare(
    'SELECT c.*, r.title AS report_title, r.category, r.location, r.type AS report_type,
            r.image_path, r.status AS report_status,
            u.name AS reviewer_name
     FROM claims c
     JOIN reports r ON r.id = c.report_id
     LEFT JOIN users u ON u.id = c.reviewer_id
     WHERE c.claimant_id = ?
     ORDER BY c.created_at DESC'
);
$stmt->execute([current_user_id()]);
$claims = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-container">
    <div class="page-header">
        <h1 class="page-title">📋 My Claims</h1>
        <p class="page-subtitle">Track the status of all your ownership claims.</p>
    </div>

    <?php if (empty($claims)): ?>
        <div class="empty-state">
            <div class="empty-icon">📋</div>
            <h3>No claims yet</h3>
            <p>When you submit ownership claims for found items, they'll appear here.</p>
            <a href="report-list.php?type=found" class="btn btn-primary">Browse Found Items</a>
        </div>
    <?php else: ?>
        <div class="claims-list">
            <?php foreach ($claims as $c): ?>
            <div class="claim-card claim-status-<?= h($c['status']) ?>">
                <div class="claim-card-left">
                    <?php if ($c['image_path']): ?>
                        <img src="/ReClaim/uploads/<?= h($c['image_path']) ?>" alt="Item" class="claim-thumb">
                    <?php else: ?>
                        <div class="claim-thumb-placeholder">📦</div>
                    <?php endif; ?>
                </div>

                <div class="claim-card-body">
                    <div class="claim-card-header">
                        <h3>
                            <a href="report-view.php?id=<?= $c['report_id'] ?>"><?= h($c['report_title']) ?></a>
                        </h3>
                        <div class="claim-badges">
                            <?= format_status_badge($c['status']) ?>
                            <?= format_type_badge($c['report_type']) ?>
                        </div>
                    </div>

                    <div class="claim-meta">
                        <span>📂 <?= h($c['category']) ?></span>
                        <span>📍 <?= h($c['location'] ?: 'N/A') ?></span>
                        <span>🕐 Claimed <?= time_ago($c['created_at']) ?></span>
                        <?php if ($c['reviewer_name']): ?>
                            <span>👤 Reviewed by <?= h($c['reviewer_name']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Timeline -->
                    <div class="claim-timeline">
                        <?php
                        $histStmt = $pdo->prepare('SELECT * FROM claim_history WHERE claim_id = ? ORDER BY created_at ASC');
                        $histStmt->execute([$c['id']]);
                        $history = $histStmt->fetchAll();
                        foreach ($history as $h_row): ?>
                            <div class="timeline-item">
                                <span class="timeline-dot timeline-<?= h($h_row['new_status'] ?? 'pending') ?>"></span>
                                <div class="timeline-content">
                                    <span class="timeline-status"><?= ucfirst(h($h_row['new_status'] ?? '')) ?></span>
                                    <span class="timeline-date"><?= date('d M Y, H:i', strtotime($h_row['created_at'])) ?></span>
                                    <?php if ($h_row['note']): ?>
                                        <p class="timeline-note"><?= h($h_row['note']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="claim-card-actions">
                    <a href="report-view.php?id=<?= $c['report_id'] ?>" class="btn btn-sm btn-outline">
                        View Item →
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
