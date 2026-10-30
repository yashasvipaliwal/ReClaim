<?php
/**
 * includes/functions.php
 * ─────────────────────────────────────────────────────────────
 * Shared utility functions used across the application.
 * Include this after starting the session and after db.php.
 *
 * Functions provided:
 *  - create_notification()   — insert a notification row
 *  - get_unread_count()      — badge count for navbar
 *  - set_flash()             — store a one-time message
 *  - get_flash()             — retrieve and clear the message
 *  - redirect()              — safe header redirect + exit
 *  - h()                     — short alias for htmlspecialchars
 *  - category_list()         — centralised list of categories
 *  - format_status_badge()   — HTML badge for report/claim status
 *  - sanitize_filename()     — generate a safe upload filename
 *  - time_ago()              — human-friendly relative time
 */

// ── HTML-escape (shorthand) ───────────────────────────────────
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// ── Safe redirect ─────────────────────────────────────────────
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// ── Flash messages (one-time session messages) ────────────────
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ── Create a notification for a user ─────────────────────────
function create_notification(PDO $pdo, int $userId, string $message, string $link = ''): void {
    $stmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)'
    );
    $stmt->execute([$userId, $message, $link]);
}

// ── Get unread notification count for navbar badge ────────────
function get_unread_count(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0'
    );
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

// ── Centralised item categories ───────────────────────────────
function category_list(): array {
    return [
        'Electronics',
        'Accessories',
        'Clothing',
        'Stationery',
        'Documents',
        'Keys',
        'Bags & Luggage',
        'Jewellery',
        'Sports Equipment',
        'Books',
        'Pets',
        'Other',
    ];
}

// ── Status badge HTML ─────────────────────────────────────────
function format_status_badge(string $status): string {
    $map = [
        'open'      => ['class' => 'badge-success',  'label' => 'Open'],
        'claimed'   => ['class' => 'badge-warning',  'label' => 'Claimed'],
        'resolved'  => ['class' => 'badge-info',     'label' => 'Resolved'],
        'closed'    => ['class' => 'badge-secondary','label' => 'Closed'],
        'pending'   => ['class' => 'badge-warning',  'label' => 'Pending'],
        'approved'  => ['class' => 'badge-success',  'label' => 'Approved'],
        'rejected'  => ['class' => 'badge-danger',   'label' => 'Rejected'],
        'confirmed' => ['class' => 'badge-info',     'label' => 'Confirmed'],
        'dismissed' => ['class' => 'badge-secondary','label' => 'Dismissed'],
    ];
    $entry = $map[$status] ?? ['class' => 'badge-secondary', 'label' => ucfirst($status)];
    return '<span class="badge ' . $entry['class'] . '">' . $entry['label'] . '</span>';
}

// ── Report type badge ─────────────────────────────────────────
function format_type_badge(string $type): string {
    if ($type === 'lost') {
        return '<span class="badge badge-danger">Lost</span>';
    }
    return '<span class="badge badge-success">Found</span>';
}

// ── Generate a safe filename for uploads ──────────────────────
// Returns a random hex string + sanitised extension.
function sanitize_filename(string $originalName): string {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return bin2hex(random_bytes(16)) . '.' . $ext;
}

// ── Allowed image MIME types ──────────────────────────────────
function allowed_image_mimes(): array {
    return ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
}

// ── Allowed image extensions (extra guard) ─────────────────────
function allowed_image_extensions(): array {
    return ['jpg', 'jpeg', 'png', 'webp', 'gif'];
}

// ── Human-readable relative time ─────────────────────────────
function time_ago(string $datetime): string {
    $now  = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' min ago';
    return 'Just now';
}
