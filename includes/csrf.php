<?php
/**
 * includes/csrf.php
 * ─────────────────────────────────────────────────────────────
 * CSRF (Cross-Site Request Forgery) protection.
 *
 * How it works:
 *  1. On every page load, a random token is stored in the session.
 *  2. Every state-changing HTML form includes this token as a
 *     hidden field (via csrf_field()).
 *  3. On form submission, verify_csrf() compares the submitted
 *     token against the session token using hash_equals() to
 *     prevent timing attacks.
 *
 * If the tokens don't match, the request is rejected with HTTP 403.
 */

// ── Generate (or retrieve) the CSRF token ─────────────────────
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        // random_bytes(32) gives a 256-bit cryptographically secure token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// ── Return a hidden <input> with the token ────────────────────
function csrf_field(): string {
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

// ── Verify the submitted CSRF token ──────────────────────────
function verify_csrf(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    $stored    = $_SESSION['csrf_token'] ?? '';

    // hash_equals prevents timing-based attacks
    if (!hash_equals($stored, $submitted)) {
        http_response_code(403);
        die('<h2 style="font-family:sans-serif">Security error: invalid CSRF token.</h2>
             <p style="font-family:sans-serif"><a href="javascript:history.back()">Go back</a> and try again.</p>');
    }
}
