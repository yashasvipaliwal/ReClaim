<?php
/**
 * logout.php
 * Destroys the session and redirects to login.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

logout_user();
set_flash('success', 'You have been logged out successfully.');
redirect('login.php');
