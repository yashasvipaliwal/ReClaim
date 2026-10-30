<?php
/**
 * login.php
 * Displays the login form. Submission is handled by actions/login-action.php.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';

// Already logged in — go to dashboard
if (is_logged_in()) {
    redirect('dashboard.php');
}

$pageTitle = 'Login';
$redirect  = htmlspecialchars($_GET['redirect'] ?? 'dashboard.php', ENT_QUOTES, 'UTF-8');

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <!-- Brand header -->
        <div class="auth-header">
            <div class="auth-icon">🔍</div>
            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-subtitle">Sign in to your ReClaim account</p>
        </div>

        <!-- Login form -->
        <form class="auth-form" id="loginForm" action="actions/login-action.php" method="POST" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="redirect" value="<?= $redirect ?>">

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="you@example.com" required autocomplete="email"
                       value="<?= h($_GET['email'] ?? '') ?>">
                <span class="form-error" id="emailError"></span>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="User@123" required autocomplete="current-password">
                    <button type="button" class="input-addon toggle-password" data-target="password"
                            aria-label="Toggle password visibility">👁</button>
                </div>
                <span class="form-error" id="passwordError"></span>
            </div>

            <button type="submit" class="btn btn-primary btn-full" id="loginBtn">
                Sign In
            </button>
        </form>

        <!-- Demo credentials hint -->
        <div class="auth-hint">
            <strong>Demo Credentials:</strong><br>
            Admin: <code>admin@reclaim.local</code> / <code>Admin@123</code><br>
            User: <code>aryan@example.com</code> / <code>User@123</code>
        </div>

        <div class="auth-footer">
            Don't have an account?
            <a href="register.php" class="auth-link">Create one here</a>
        </div>
    </div>

    <!-- Side panel (desktop only) -->
    <div class="auth-side">
        <div class="auth-side-content">
            <h2>Lost something?</h2>
            <p>ReClaim helps you report and recover lost items using a smart matching system.</p>
            <ul class="feature-list">
                <li>✅ Smart rule-based matching</li>
                <li>✅ Verified ownership claims</li>
                <li>✅ Real-time notifications</li>
                <li>✅ Community-driven recovery</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
