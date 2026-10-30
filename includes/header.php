<?php
/**
 * includes/header.php
 * ─────────────────────────────────────────────────────────────
 * Shared HTML <head> and navigation bar.
 *
 * Variables expected (set before including this file):
 *   $pageTitle  (string) — used in <title> tag
 *   $pdo        (PDO)    — needed for unread notification count
 */

// Calculate unread notifications count for badge
$unreadCount = 0;
if (is_logged_in() && isset($pdo)) {
    $unreadCount = get_unread_count($pdo, current_user_id());
}

// Base path - adjust if not running from htdocs root
$base = '/ReClaim';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ReClaim — Smart Lost and Found Platform. Report, search, and recover your lost items.">
    <title><?= h($pageTitle ?? 'ReClaim') ?> | ReClaim</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔍</text></svg>">
</head>
<body>

<!-- ══ Navigation Bar ══════════════════════════════════════ -->
<header class="navbar" id="navbar">
    <div class="container navbar-inner">

        <!-- Logo -->
        <a href="<?= $base ?>/index.php" class="navbar-brand">
            <span class="brand-icon">🔍</span>
            <span class="brand-name">Re<span class="brand-accent">Claim</span></span>
        </a>

        <!-- Hamburger (mobile) -->
        <button class="hamburger" id="hamburgerBtn" aria-label="Toggle menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <!-- Nav links -->
        <nav class="nav-links" id="navLinks">
            <a href="<?= $base ?>/report-list.php" class="nav-link">Browse Reports</a>
            <a href="<?= $base ?>/report-list.php?type=lost" class="nav-link">Lost Items</a>
            <a href="<?= $base ?>/report-list.php?type=found" class="nav-link">Found Items</a>

            <?php if (is_logged_in()): ?>
                <a href="<?= $base ?>/report-create.php" class="btn btn-sm btn-outline-white">+ New Report</a>

                <!-- Notifications icon -->
                <a href="<?= $base ?>/notifications.php" class="nav-link notif-link" aria-label="Notifications">
                    🔔
                    <?php if ($unreadCount > 0): ?>
                        <span class="notif-badge" id="notifBadge"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </a>

                <!-- User dropdown -->
                <div class="nav-dropdown" id="userDropdown">
                    <button class="nav-link dropdown-toggle" id="userDropdownBtn">
                        <span class="user-avatar"><?= strtoupper(substr(current_user_name(), 0, 1)) ?></span>
                        <?= h(current_user_name()) ?>
                        <span class="dropdown-arrow">▾</span>
                    </button>
                    <div class="dropdown-menu" id="userDropdownMenu">
                        <a href="<?= $base ?>/dashboard.php" class="dropdown-item">📊 Dashboard</a>
                        <a href="<?= $base ?>/my-claims.php" class="dropdown-item">📋 My Claims</a>
                        <a href="<?= $base ?>/matches.php" class="dropdown-item">🔗 My Matches</a>
                        <?php if (is_admin()): ?>
                            <div class="dropdown-divider"></div>
                            <a href="<?= $base ?>/admin.php" class="dropdown-item admin-link">⚙️ Admin Panel</a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="<?= $base ?>/logout.php" class="dropdown-item text-danger">🚪 Logout</a>
                    </div>
                </div>

            <?php else: ?>
                <a href="<?= $base ?>/login.php" class="nav-link">Login</a>
                <a href="<?= $base ?>/register.php" class="btn btn-sm btn-primary">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<!-- ══ Flash Message ════════════════════════════════════════ -->
<?php $flash = get_flash(); if ($flash): ?>
<div class="flash-message flash-<?= h($flash['type']) ?>" id="flashMessage" role="alert">
    <span><?= h($flash['message']) ?></span>
    <button class="flash-close" onclick="this.parentElement.remove()" aria-label="Close">✕</button>
</div>
<?php endif; ?>

<!-- ══ Page content begins ══════════════════════════════════ -->
<main class="main-content">
