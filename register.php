<?php
/**
 * register.php
 * Displays the registration form.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$pageTitle = 'Create Account';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card auth-card-wide">
        <div class="auth-header">
            <div class="auth-icon">📝</div>
            <h1 class="auth-title">Create Your Account</h1>
            <p class="auth-subtitle">Join ReClaim and start reporting</p>
        </div>

        <form class="auth-form" id="registerForm" action="actions/register-action.php" method="POST" novalidate>
            <?= csrf_field() ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="name" class="form-label">Full Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control"
                           placeholder="Aryan Sharma" required autocomplete="name"
                           minlength="2" maxlength="100"
                           value="<?= h($_GET['name'] ?? '') ?>">
                    <span class="form-error" id="nameError"></span>
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label">Phone (optional)</label>
                    <input type="tel" id="phone" name="phone" class="form-control"
                           placeholder="9876543210" maxlength="15"
                           value="<?= h($_GET['phone'] ?? '') ?>">
                    <span class="form-error" id="phoneError"></span>
                </div>
            </div>

            <div class="form-group">
                <label for="reg_email" class="form-label">Email Address <span class="required">*</span></label>
                <input type="email" id="reg_email" name="email" class="form-control"
                       placeholder="you@example.com" required autocomplete="email"
                       value="<?= h($_GET['email'] ?? '') ?>">
                <span class="form-error" id="emailError"></span>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="reg_password" class="form-label">Password <span class="required">*</span></label>
                    <div class="input-group">
                        <input type="password" id="reg_password" name="password" class="form-control"
                               placeholder="Min. 8 characters" required minlength="8"
                               autocomplete="new-password">
                        <button type="button" class="input-addon toggle-password"
                                data-target="reg_password" aria-label="Toggle">👁</button>
                    </div>
                    <div class="password-strength" id="passwordStrength"></div>
                    <span class="form-error" id="reg_passwordError"></span>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password <span class="required">*</span></label>
                    <div class="input-group">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                               placeholder="Repeat password" required autocomplete="new-password">
                        <button type="button" class="input-addon toggle-password"
                                data-target="confirm_password" aria-label="Toggle">👁</button>
                    </div>
                    <span class="form-error" id="confirmPasswordError"></span>
                </div>
            </div>

            <p class="form-note">
                ⚠️ Passwords must be at least 8 characters. Roles are assigned by administrators only.
            </p>

            <button type="submit" class="btn btn-primary btn-full" id="registerBtn">
                Create Account
            </button>
        </form>

        <div class="auth-footer">
            Already have an account?
            <a href="login.php" class="auth-link">Sign in here</a>
        </div>
    </div>

    <div class="auth-side auth-side-right">
        <div class="auth-side-content">
            <h2>How ReClaim works</h2>
            <div class="steps">
                <div class="step"><span class="step-num">1</span><span>Report your lost or found item</span></div>
                <div class="step"><span class="step-num">2</span><span>Our system suggests matches</span></div>
                <div class="step"><span class="step-num">3</span><span>Submit a claim with proof</span></div>
                <div class="step"><span class="step-num">4</span><span>Get your item back!</span></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
