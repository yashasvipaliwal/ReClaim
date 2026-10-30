<?php
/**
 * includes/auth.php
 * ─────────────────────────────────────────────────────────────
 * Session management and authentication helpers.
 *
 * Always include this file BEFORE any HTML output.
 * It starts the PHP session (if not already started).
 *
 * Key concepts for viva:
 *  - PHP sessions store data server-side; only a session ID
 *    cookie is sent to the browser.
 *  - session_regenerate_id(true) after login prevents session
 *    fixation attacks.
 *  - Role-based access: only rows with role='admin' in the DB
 *    can access admin pages.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Secure session settings
    ini_set('session.cookie_httponly', 1);   // JS cannot read cookie
    ini_set('session.use_strict_mode', 1);   // reject unrecognized IDs
    session_start();
}

// ── Is the visitor logged in? ─────────────────────────────────
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

// ── Redirect to login if not authenticated ────────────────────
function require_login(): void {
    if (!is_logged_in()) {
        $current = urlencode($_SERVER['REQUEST_URI'] ?? '');
        header('Location: login.php?redirect=' . $current);
        exit;
    }
}

// ── Is the current user an administrator? ─────────────────────
function is_admin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// ── Redirect non-admins away from admin pages ─────────────────
function require_admin(): void {
    require_login();
    if (!is_admin()) {
        header('Location: dashboard.php?err=access_denied');
        exit;
    }
}

// ── Get current user's ID (null if not logged in) ─────────────
function current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

// ── Get current user's display name ──────────────────────────
function current_user_name(): string {
    return $_SESSION['name'] ?? 'Guest';
}

// ── Log the user in (call after verifying password) ───────────
function login_user(array $user): void {
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name']    = $user['name'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = $user['role'];
}

// ── Destroy session on logout ─────────────────────────────────
function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}
