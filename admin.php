<?php
/**
 * admin.php
 * ─────────────────────────────────────────────────────────────
 * Administrator-only control panel.
 * Tabs: Overview | Reports | Claims | Users
 *
 * Security: require_admin() checks both session and role.
 * All DB queries use prepared statements.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

require_admin();

$pageTitle = 'Admin Panel';
$tab       = in_array($_GET['tab'] ?? '', ['overview','reports','claims','users'])
             ? $_GET['tab']
             : 'overview';

// ── Global stats ──────────────────────────────────────────────
$stats = [];
$stats['total_users']    = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$stats['total_reports']  = (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE is_deleted=0")->fetchColumn();
$stats['open_reports']   = (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE is_deleted=0 AND status='open'")->fetchColumn();
$stats['total_claims']   = (int) $pdo->query('SELECT COUNT(*) FROM claims')->fetchColumn();
$stats['pending_claims'] = (int) $pdo->query("SELECT COUNT(*) FROM claims WHERE status='pending'")->fetchColumn();
$stats['resolved']       = (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status='resolved'")->fetchColumn();
$stats['lost_reports']   = (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE type='lost' AND is_deleted=0")->fetchColumn();
$stats['found_reports']  = (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE type='found' AND is_deleted=0")->fetchColumn();

// ── Tab: Claims ───────────────────────────────────────────────
$pendingClaims = [];
if ($tab === 'claims') {
    $stmt = $pdo->query(
        "SELECT c.*, r.title AS report_title, r.type AS report_type, r.location,
                u.name AS claimant_name, u.email AS claimant_email
         FROM claims c
         JOIN reports r ON r.id = c.report_id
         JOIN users u ON u.id = c.claimant_id
         WHERE c.status = 'pending'
         ORDER BY c.created_at ASC"
    );
    $pendingClaims = $stmt->fetchAll();
}

// ── Tab: Reports ──────────────────────────────────────────────
$allReports = [];
if ($tab === 'reports') {
    $stmt = $pdo->query(
        "SELECT r.*, u.name AS owner_name FROM reports r
         JOIN users u ON r.user_id = u.id
         WHERE r.is_deleted = 0
         ORDER BY r.created_at DESC LIMIT 100"
    );
    $allReports = $stmt->fetchAll();
}

// ── Tab: Users ────────────────────────────────────────────────
$allUsers = [];
if ($tab === 'users') {
    $allUsers = $pdo->query(
        "SELECT u.*, 
                (SELECT COUNT(*) FROM reports WHERE user_id=u.id AND is_deleted=0) AS report_count,
                (SELECT COUNT(*) FROM claims WHERE claimant_id=u.id) AS claim_count
         FROM users u ORDER BY u.created_at DESC"
    )->fetchAll();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-container">
    <div class="page-header">
        <h1 class="page-title">⚙️ Admin Dashboard</h1>
        <p class="page-subtitle">Manage reports, claims, and users.</p>
    </div>

    <!-- Tab navigation -->
    <nav class="tab-nav" aria-label="Admin sections">
        <?php foreach (['overview'=>'📊 Overview','reports'=>'📋 Reports','claims'=>'📩 Claims','users'=>'👥 Users'] as $t => $label): ?>
            <a href="admin.php?tab=<?= $t ?>"
               class="tab-btn <?= $tab===$t ? 'active':'' ?>">
                <?= $label ?>
                <?php if ($t==='claims' && $stats['pending_claims'] > 0): ?>
                    <span class="badge-count"><?= $stats['pending_claims'] ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- ══ OVERVIEW ══════════════════════════════════════════ -->
    <?php if ($tab === 'overview'): ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-body">
                <div class="stat-value"><?= $stats['total_users'] ?></div>
                <div class="stat-label">Registered Users</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-body">
                <div class="stat-value"><?= $stats['total_reports'] ?></div>
                <div class="stat-label">Total Reports</div>
            </div>
        </div>
        <div class="stat-card stat-card-warn">
            <div class="stat-icon">😟</div>
            <div class="stat-body">
                <div class="stat-value"><?= $stats['lost_reports'] ?></div>
                <div class="stat-label">Lost Reports</div>
            </div>
        </div>
        <div class="stat-card stat-card-success">
            <div class="stat-icon">🎉</div>
            <div class="stat-body">
                <div class="stat-value"><?= $stats['found_reports'] ?></div>
                <div class="stat-label">Found Reports</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📩</div>
            <div class="stat-body">
                <div class="stat-value"><?= $stats['total_claims'] ?></div>
                <div class="stat-label">Total Claims</div>
            </div>
        </div>
        <div class="stat-card stat-card-warn">
            <div class="stat-icon">⏳</div>
            <div class="stat-body">
                <div class="stat-value"><?= $stats['pending_claims'] ?></div>
                <div class="stat-label">Pending Claims</div>
            </div>
        </div>
        <div class="stat-card stat-card-success">
            <div class="stat-icon">✅</div>
            <div class="stat-body">
                <div class="stat-value"><?= $stats['resolved'] ?></div>
                <div class="stat-label">Items Returned</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🔓</div>
            <div class="stat-body">
                <div class="stat-value"><?= $stats['open_reports'] ?></div>
                <div class="stat-label">Open Reports</div>
            </div>
        </div>
    </div>

    <?php if ($stats['pending_claims'] > 0): ?>
    <div class="alert alert-warning" style="margin-top:1.5rem">
        ⚠️ <strong><?= $stats['pending_claims'] ?></strong> claim(s) are awaiting your review.
        <a href="admin.php?tab=claims">Review now →</a>
    </div>
    <?php endif; ?>

    <!-- Quick actions -->
    <div class="admin-quick-actions">
        <a href="admin.php?tab=claims" class="quick-action-btn">📩 Review Claims</a>
        <a href="admin.php?tab=reports" class="quick-action-btn">📋 Manage Reports</a>
        <a href="admin.php?tab=users" class="quick-action-btn">👥 Manage Users</a>
        <a href="report-create.php" class="quick-action-btn">+ New Report</a>
    </div>

    <?php endif; // end overview ?>

    <!-- ══ CLAIMS ════════════════════════════════════════════ -->
    <?php if ($tab === 'claims'): ?>
    <h2 class="section-title">Pending Claims (<?= count($pendingClaims) ?>)</h2>

    <?php if (empty($pendingClaims)): ?>
        <div class="empty-state">
            <div class="empty-icon">✅</div>
            <h3>All caught up!</h3>
            <p>No pending claims to review.</p>
        </div>
    <?php else: ?>
        <?php foreach ($pendingClaims as $cl): ?>
        <div class="admin-claim-card">
            <div class="admin-claim-header">
                <div>
                    <h3>Claim for: <a href="report-view.php?id=<?= $cl['report_id'] ?>">
                        <?= h($cl['report_title']) ?></a>
                    </h3>
                    <span class="text-muted">
                        <?= format_type_badge($cl['report_type']) ?>
                        📍 <?= h($cl['location'] ?: 'N/A') ?>
                        &nbsp;|&nbsp;
                        Submitted <?= time_ago($cl['created_at']) ?>
                        by <strong><?= h($cl['claimant_name']) ?></strong>
                        (<?= h($cl['claimant_email']) ?>)
                    </span>
                </div>
                <span class="badge badge-warning">Pending</span>
            </div>

            <!-- Verification detail (private) -->
            <div class="claim-verification-box">
                <h4>🔒 Ownership Proof (Private — not shown to other users)</h4>
                <p><?= nl2br(h($cl['verification_detail'])) ?></p>
            </div>

            <!-- Approve / Reject forms -->
            <div class="admin-claim-actions">
                <form method="POST" action="actions/claim-action.php" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="claim_id" value="<?= $cl['id'] ?>">
                    <input type="text" name="review_note" class="form-control form-control-sm"
                           placeholder="Optional note to claimant…" style="width:240px; display:inline-block">
                    <button type="submit" class="btn btn-success btn-sm"
                            onclick="return confirm('Approve this claim? This will mark the item as resolved and reject other claims.')">
                        ✅ Approve
                    </button>
                </form>

                <form method="POST" action="actions/claim-action.php" style="display:inline; margin-left:.5rem">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="claim_id" value="<?= $cl['id'] ?>">
                    <input type="text" name="review_note" class="form-control form-control-sm"
                           placeholder="Reason for rejection…" style="width:240px; display:inline-block">
                    <button type="submit" class="btn btn-danger btn-sm"
                            onclick="return confirm('Reject this claim?')">
                        ❌ Reject
                    </button>
                </form>
            </div>

            <!-- Claim history for this claim -->
            <?php
            $histStmt = $pdo->prepare('SELECT ch.*, u.name AS actor_name FROM claim_history ch JOIN users u ON u.id = ch.actor_id WHERE ch.claim_id = ? ORDER BY ch.created_at');
            $histStmt->execute([$cl['id']]);
            $history = $histStmt->fetchAll();
            if ($history): ?>
            <details class="claim-history-details">
                <summary>📜 View History (<?= count($history) ?> event<?= count($history)>1?'s':'' ?>)</summary>
                <?php foreach ($history as $hrow): ?>
                <div class="history-row">
                    <span class="history-status"><?= ucfirst(h($hrow['new_status'] ?? 'pending')) ?></span>
                    <span>by <?= h($hrow['actor_name']) ?></span>
                    <span><?= date('d M Y H:i', strtotime($hrow['created_at'])) ?></span>
                    <?php if ($hrow['note']): ?>
                        <span class="history-note"><?= h($hrow['note']) ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </details>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php endif; // end claims ?>

    <!-- ══ REPORTS ═══════════════════════════════════════════ -->
    <?php if ($tab === 'reports'): ?>
    <h2 class="section-title">All Reports (<?= count($allReports) ?>)</h2>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th><th>Title</th><th>Type</th><th>Category</th>
                    <th>Status</th><th>Owner</th><th>Date</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allReports as $r): ?>
            <tr>
                <td>#<?= $r['id'] ?></td>
                <td><a href="report-view.php?id=<?= $r['id'] ?>"><?= h(mb_strimwidth($r['title'],0,50,'…')) ?></a></td>
                <td><?= format_type_badge($r['type']) ?></td>
                <td><?= h($r['category']) ?></td>
                <td><?= format_status_badge($r['status']) ?></td>
                <td><?= h($r['owner_name']) ?></td>
                <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                <td>
                    <a href="report-view.php?id=<?= $r['id'] ?>" class="btn btn-xs btn-outline">View</a>
                    <form method="POST" action="actions/admin-action.php" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete_report">
                        <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                        <button class="btn btn-xs btn-danger"
                                onclick="return confirm('Delete report #<?= $r['id'] ?>?')">Del</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; // end reports ?>

    <!-- ══ USERS ══════════════════════════════════════════════ -->
    <?php if ($tab === 'users'): ?>
    <h2 class="section-title">Registered Users (<?= count($allUsers) ?>)</h2>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th><th>Name</th><th>Email</th><th>Role</th>
                    <th>Reports</th><th>Claims</th><th>Joined</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allUsers as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= h($u['name']) ?></td>
                <td><?= h($u['email']) ?></td>
                <td>
                    <?php if ($u['role'] === 'admin'): ?>
                        <span class="badge badge-danger">Admin</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">User</span>
                    <?php endif; ?>
                </td>
                <td><?= $u['report_count'] ?></td>
                <td><?= $u['claim_count'] ?></td>
                <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <?php if ((int)$u['id'] !== current_user_id()): ?>
                        <form method="POST" action="actions/admin-action.php" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="toggle_role">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button class="btn btn-xs btn-outline"
                                    onclick="return confirm('Toggle role for <?= h($u['name']) ?>?')">
                                <?= $u['role']==='admin' ? '⬇️ Demote' : '⬆️ Promote' ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; // end users ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
