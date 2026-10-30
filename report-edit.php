<?php
/**
 * report-edit.php
 * Edit an existing report (owner or admin only).
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

require_login();

$reportId = (int) ($_GET['id'] ?? 0);
if ($reportId <= 0) redirect('dashboard.php');

$stmt = $pdo->prepare('SELECT * FROM reports WHERE id = ? AND is_deleted = 0');
$stmt->execute([$reportId]);
$report = $stmt->fetch();

if (!$report) {
    set_flash('error', 'Report not found.');
    redirect('dashboard.php');
}

// Authorization: only the owner or an admin can edit
if ((int)$report['user_id'] !== current_user_id() && !is_admin()) {
    set_flash('error', 'You do not have permission to edit this report.');
    redirect('dashboard.php');
}

$pageTitle  = 'Edit Report';
$categories = category_list();
require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-container">
    <div class="page-header">
        <h1 class="page-title">✏️ Edit Report</h1>
        <p class="page-subtitle">Update the details for: <strong><?= h($report['title']) ?></strong></p>
    </div>

    <div class="form-card">
        <form id="reportForm" action="actions/report-action.php" method="POST"
              enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="report_id" value="<?= $reportId ?>">

            <!-- Report Type -->
            <div class="form-group">
                <label class="form-label">Report Type <span class="required">*</span></label>
                <div class="type-toggle">
                    <label class="type-option <?= $report['type']==='lost' ? 'active':'' ?>">
                        <input type="radio" name="type" value="lost"
                               <?= $report['type']==='lost' ? 'checked':'' ?> required>
                        <span class="type-icon">😟</span>
                        <span class="type-text">I Lost Something</span>
                    </label>
                    <label class="type-option <?= $report['type']==='found' ? 'active':'' ?>">
                        <input type="radio" name="type" value="found"
                               <?= $report['type']==='found' ? 'checked':'' ?>>
                        <span class="type-icon">🎉</span>
                        <span class="type-text">I Found Something</span>
                    </label>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group form-group-2">
                    <label for="title" class="form-label">Item Name / Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" class="form-control"
                           value="<?= h($report['title']) ?>" required minlength="3" maxlength="200">
                </div>

                <div class="form-group">
                    <label for="category" class="form-label">Category <span class="required">*</span></label>
                    <select id="category" name="category" class="form-control" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= h($cat) ?>" <?= $report['category']===$cat ? 'selected':'' ?>>
                                <?= h($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" id="location" name="location" class="form-control"
                           value="<?= h($report['location'] ?? '') ?>" maxlength="200">
                </div>

                <div class="form-group">
                    <label for="incident_date" class="form-label">Date of Incident <span class="required">*</span></label>
                    <input type="date" id="incident_date" name="incident_date" class="form-control"
                           value="<?= h($report['incident_date']) ?>" required max="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-control form-textarea"
                          rows="4" maxlength="2000"><?= h($report['description'] ?? '') ?></textarea>
                <span class="form-hint char-counter" data-target="description">
                    <?= strlen($report['description'] ?? '') ?> / 2000 characters
                </span>
            </div>

            <!-- Current image -->
            <?php if ($report['image_path']): ?>
            <div class="form-group">
                <label class="form-label">Current Image</label>
                <div class="current-image-wrap">
                    <img src="/ReClaim/uploads/<?= h($report['image_path']) ?>"
                         alt="Current image" class="current-image">
                    <span class="form-hint">Upload a new image below to replace it.</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- New image upload -->
            <div class="form-group">
                <label for="image" class="form-label">
                    <?= $report['image_path'] ? 'Replace Image' : 'Upload Image' ?> (optional)
                </label>
                <div class="upload-area" id="uploadArea">
                    <input type="file" id="image" name="image" class="upload-input"
                           accept=".jpg,.jpeg,.png,.webp,.gif">
                    <div class="upload-placeholder" id="uploadPlaceholder">
                        <span class="upload-icon">📷</span>
                        <span>Click to upload or drag & drop</span>
                        <span class="upload-hint">JPEG, PNG, WebP, GIF — max 5 MB</span>
                    </div>
                    <img src="" alt="Preview" class="upload-preview" id="imagePreview">
                </div>
            </div>

            <div class="form-actions">
                <a href="report-view.php?id=<?= $reportId ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
