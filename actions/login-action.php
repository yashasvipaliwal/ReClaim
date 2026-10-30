<?php
/**
 * actions/login-action.php
 * ─────────────────────────────────────────────────────────────
 * Handles POST from login.php.
 * Steps:
 *  1. Verify CSRF token
 *  2. Validate inputs
 *  3. Fetch user by email
 *  4. Verify password hash
 *  5. Start session and redirect
 */
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/config/db.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../login.php');
}

// 1. CSRF check
verify_csrf();

// 2. Sanitise and validate inputs
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$redirect = $_POST['redirect'] ?? 'dashboard.php';

// Basic validation
if (empty($email) || empty($password)) {
    set_flash('error', 'Please enter both email and password.');
    redirect('../login.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Invalid email address format.');
    redirect('../login.php');
}

// 3. Look up user by email using a prepared statement
$stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

// 4. Verify password (always run password_verify to prevent timing attacks)
if (!$user || !password_verify($password, $user['password_hash'])) {
    set_flash('error', 'Invalid email or password. Please try again.');
    redirect('../login.php');
}

// 5. Log in: set session variables and regenerate session ID
login_user($user);

set_flash('success', 'Welcome back, ' . $user['name'] . '!');

// Ensure the redirect target is safe (prevent open redirect)
$allowedPrefixes = ['dashboard', 'report-', 'matches', 'my-claims', 'notifications', 'admin', 'index'];
$redirectSafe = 'dashboard.php';
foreach ($allowedPrefixes as $prefix) {
    if (strpos(basename($redirect), $prefix) === 0) {
        $redirectSafe = $redirect;
        break;
    }
}

redirect('../' . ltrim($redirectSafe, '/'));
