<?php
/**
 * actions/admin-action.php
 * ─────────────────────────────────────────────────────────────
 * Handles admin-only POST actions: report deletion, user role toggle.
 */
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/config/db.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../admin.php');
}

verify_csrf();

$action = $_POST['action'] ?? '';

switch ($action) {

    case 'delete_report':
        $reportId = (int) ($_POST['report_id'] ?? 0);
        if ($reportId > 0) {
            $pdo->prepare('UPDATE reports SET is_deleted = 1 WHERE id = ?')->execute([$reportId]);
            set_flash('success', 'Report #' . $reportId . ' deleted.');
        }
        redirect('../admin.php?tab=reports');
        break;

    case 'toggle_role':
        $userId = (int) ($_POST['user_id'] ?? 0);
        // Prevent demoting yourself
        if ($userId > 0 && $userId !== current_user_id()) {
            $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if ($user) {
                $newRole = ($user['role'] === 'admin') ? 'user' : 'admin';
                $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$newRole, $userId]);
                set_flash('success', 'User role changed to ' . $newRole . '.');
            }
        } else {
            set_flash('error', 'Cannot modify your own role.');
        }
        redirect('../admin.php?tab=users');
        break;

    default:
        set_flash('error', 'Unknown admin action.');
        redirect('../admin.php');
}
