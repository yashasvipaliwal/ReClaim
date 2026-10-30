<?php
/**
 * actions/register-action.php
 * ─────────────────────────────────────────────────────────────
 * Handles POST from register.php.
 * Security:
 *  - CSRF verification
 *  - Server-side validation (never trust client-side alone)
 *  - Password hashing via password_hash() with PASSWORD_BCRYPT
 *  - Role is always forced to 'user' — admins created via SQL only
 *  - Duplicate email caught via UNIQUE constraint + PDO exception
 */
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../register.php');
}

verify_csrf();

// ── Collect and sanitise inputs ───────────────────────────────
$name            = trim($_POST['name']             ?? '');
$email           = trim(strtolower($_POST['email'] ?? ''));
$phone           = trim($_POST['phone']            ?? '');
$password        = $_POST['password']              ?? '';
$confirmPassword = $_POST['confirm_password']      ?? '';

$errors = [];

// ── Server-side validation ────────────────────────────────────
if (strlen($name) < 2 || strlen($name) > 100) {
    $errors[] = 'Name must be between 2 and 100 characters.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}
if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}
if ($password !== $confirmPassword) {
    $errors[] = 'Passwords do not match.';
}
if (!empty($phone) && !preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
    $errors[] = 'Phone number must be 7-15 digits.';
}

if (!empty($errors)) {
    set_flash('error', implode(' ', $errors));
    redirect('../register.php');
}

// ── Hash the password ─────────────────────────────────────────
// password_hash() automatically generates a salt and uses bcrypt.
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// ── Insert the new user ───────────────────────────────────────
// Note: role is hardcoded as 'user' — never from form input.
try {
    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, role, phone) VALUES (?, ?, ?, "user", ?)'
    );
    $stmt->execute([$name, $email, $hash, $phone ?: null]);
    $newUserId = (int) $pdo->lastInsertId();
} catch (PDOException $e) {
    // Duplicate email (UNIQUE constraint violation = error code 23000)
    if ($e->getCode() === '23000') {
        set_flash('error', 'An account with this email already exists. Please log in.');
        redirect('../register.php');
    }
    // Other DB error — log it, show generic message
    error_log('[ReClaim Register Error] ' . $e->getMessage());
    set_flash('error', 'Registration failed due to a server error. Please try again.');
    redirect('../register.php');
}

// ── Auto-login after registration ─────────────────────────────
$user = ['id' => $newUserId, 'name' => $name, 'email' => $email, 'role' => 'user'];
login_user($user);

set_flash('success', 'Welcome to ReClaim, ' . $name . '! Your account has been created.');
redirect('../dashboard.php');
