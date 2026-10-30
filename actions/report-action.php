<?php
/**
 * actions/report-action.php
 * ─────────────────────────────────────────────────────────────
 * Handles all report mutations: create, edit, delete.
 * The "action" POST field determines which operation to run.
 *
 * Upload security:
 *  - MIME type checked with finfo (server-side, not just extension)
 *  - Extension whitelist enforced
 *  - File size capped at 5 MB
 *  - Random hex filename generated (no user-supplied names)
 *  - Uploads folder has .htaccess preventing PHP execution
 */
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/matching/matcher.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../report-list.php');
}

verify_csrf();

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create': handle_create($pdo); break;
    case 'edit':   handle_edit($pdo);   break;
    case 'delete': handle_delete($pdo); break;
    default:
        set_flash('error', 'Invalid action.');
        redirect('../dashboard.php');
}

// ─────────────────────────────────────────────────────────────
function handle_create(PDO $pdo): void
{
    $data   = validate_report_input($_POST);
    $errors = $data['errors'];

    if (!empty($errors)) {
        set_flash('error', implode(' ', $errors));
        redirect('../report-create.php');
    }

    // Handle optional image upload
    $imagePath = handle_upload();
    if ($imagePath === false) {
        // handle_upload() already set a flash error
        redirect('../report-create.php');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO reports (user_id, type, title, category, description, location, incident_date, image_path)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        current_user_id(),
        $data['type'],
        $data['title'],
        $data['category'],
        $data['description'],
        $data['location'],
        $data['incident_date'],
        $imagePath,
    ]);

    $newId = (int) $pdo->lastInsertId();

    // Run the matching algorithm for the new report
    run_matching_for_report($pdo, $newId);

    set_flash('success', 'Your report has been submitted successfully!');
    redirect('../report-view.php?id=' . $newId);
}

// ─────────────────────────────────────────────────────────────
function handle_edit(PDO $pdo): void
{
    $reportId = (int) ($_POST['report_id'] ?? 0);
    if ($reportId <= 0) {
        redirect('../dashboard.php');
    }

    // Fetch existing report — ensure ownership
    $stmt = $pdo->prepare('SELECT * FROM reports WHERE id = ? AND is_deleted = 0');
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();

    if (!$report) {
        set_flash('error', 'Report not found.');
        redirect('../dashboard.php');
    }

    // Only owner or admin may edit
    if ((int)$report['user_id'] !== current_user_id() && !is_admin()) {
        set_flash('error', 'You do not have permission to edit this report.');
        redirect('../dashboard.php');
    }

    $data   = validate_report_input($_POST);
    $errors = $data['errors'];

    if (!empty($errors)) {
        set_flash('error', implode(' ', $errors));
        redirect('../report-edit.php?id=' . $reportId);
    }

    // Handle optional new image
    $imagePath = $report['image_path']; // keep existing by default
    $newImage  = handle_upload();
    if ($newImage === false) {
        redirect('../report-edit.php?id=' . $reportId);
    }
    if ($newImage !== null) {
        // Delete old image if it exists
        if ($imagePath && file_exists(dirname(__DIR__) . '/uploads/' . $imagePath)) {
            unlink(dirname(__DIR__) . '/uploads/' . $imagePath);
        }
        $imagePath = $newImage;
    }

    $stmt = $pdo->prepare(
        'UPDATE reports
         SET type=?, title=?, category=?, description=?, location=?, incident_date=?, image_path=?
         WHERE id=?'
    );
    $stmt->execute([
        $data['type'],
        $data['title'],
        $data['category'],
        $data['description'],
        $data['location'],
        $data['incident_date'],
        $imagePath,
        $reportId,
    ]);

    set_flash('success', 'Report updated successfully.');
    redirect('../report-view.php?id=' . $reportId);
}

// ─────────────────────────────────────────────────────────────
function handle_delete(PDO $pdo): void
{
    $reportId = (int) ($_POST['report_id'] ?? 0);
    if ($reportId <= 0) {
        redirect('../dashboard.php');
    }

    $stmt = $pdo->prepare('SELECT * FROM reports WHERE id = ? AND is_deleted = 0');
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();

    if (!$report) {
        set_flash('error', 'Report not found.');
        redirect('../dashboard.php');
    }

    if ((int)$report['user_id'] !== current_user_id() && !is_admin()) {
        set_flash('error', 'You cannot delete this report.');
        redirect('../dashboard.php');
    }

    // Soft-delete (preserve for audit, admin review)
    $stmt = $pdo->prepare('UPDATE reports SET is_deleted = 1 WHERE id = ?');
    $stmt->execute([$reportId]);

    set_flash('success', 'Report deleted successfully.');
    redirect('../dashboard.php');
}

// ─────────────────────────────────────────────────────────────
// Validate common report form fields, return cleaned data + errors
// ─────────────────────────────────────────────────────────────
function validate_report_input(array $post): array
{
    $errors = [];
    $allowed_types      = ['lost', 'found'];
    $allowed_categories = category_list();

    $type         = trim($post['type']          ?? '');
    $title        = trim($post['title']         ?? '');
    $category     = trim($post['category']      ?? '');
    $description  = trim($post['description']   ?? '');
    $location     = trim($post['location']      ?? '');
    $incident_date = trim($post['incident_date'] ?? '');

    if (!in_array($type, $allowed_types, true)) {
        $errors[] = 'Please select a valid report type (Lost or Found).';
    }
    if (strlen($title) < 3 || strlen($title) > 200) {
        $errors[] = 'Title must be between 3 and 200 characters.';
    }
    if (!in_array($category, $allowed_categories, true)) {
        $errors[] = 'Please select a valid category.';
    }
    if (empty($incident_date) || !strtotime($incident_date)) {
        $errors[] = 'Please provide a valid incident date.';
    } elseif (strtotime($incident_date) > time() + 86400) {
        $errors[] = 'Incident date cannot be in the future.';
    }

    return [
        'errors'        => $errors,
        'type'          => $type,
        'title'         => $title,
        'category'      => $category,
        'description'   => $description,
        'location'      => $location,
        'incident_date' => $incident_date,
    ];
}

// ─────────────────────────────────────────────────────────────
// Handle optional image upload.
// Returns: filename string (saved), null (no file), false (error)
// ─────────────────────────────────────────────────────────────
function handle_upload(): string|null|false
{
    if (empty($_FILES['image']['name'])) {
        return null; // no file uploaded, that's fine
    }

    $file      = $_FILES['image'];
    $maxBytes  = 5 * 1024 * 1024; // 5 MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        set_flash('error', 'File upload error. Please try again.');
        return false;
    }

    if ($file['size'] > $maxBytes) {
        set_flash('error', 'Image must be smaller than 5 MB.');
        return false;
    }

    // Check MIME type using finfo (not Content-Type header, which can be spoofed)
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, allowed_image_mimes(), true)) {
        set_flash('error', 'Only JPEG, PNG, WebP, and GIF images are allowed.');
        return false;
    }

    // Check extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, allowed_image_extensions(), true)) {
        set_flash('error', 'Invalid file extension.');
        return false;
    }

    // Generate safe filename
    $safeName  = sanitize_filename($file['name']);
    $uploadDir = dirname(__DIR__) . '/uploads/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $safeName)) {
        set_flash('error', 'Failed to save the uploaded file.');
        return false;
    }

    return $safeName;
}
