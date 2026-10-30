<?php
/**
 * report-create.php
 * Form to submit a new Lost or Found item report.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

require_login();

$pageTitle  = 'Submit a Report';
$categories = category_list();
// Pre-fill type from query string if provided
$preType    = in_array($_GET['type'] ?? '', ['lost', 'found']) ? $_GET['type'] : '';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-container">
    <div class="page-header">
        <h1 class="page-title">📋 Submit a Report</h1>
        <p class="page-subtitle">Fill in the details about the item you lost or found.</p>
    </div>

    <div class="form-card">
        <form id="reportForm" action="actions/report-action.php" method="POST"
              enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">

            <!-- Report Type -->
            <div class="form-group">
                <label class="form-label">Report Type <span class="required">*</span></label>
                <div class="type-toggle">
                    <label class="type-option <?= $preType === 'lost' ? 'active' : '' ?>">
                        <input type="radio" name="type" value="lost" required
                               <?= $preType === 'lost' ? 'checked' : '' ?>>
                        <span class="type-icon">😟</span>
                        <span class="type-text">I Lost Something</span>
                    </label>
                    <label class="type-option <?= $preType === 'found' ? 'active' : '' ?>">
                        <input type="radio" name="type" value="found"
                               <?= $preType === 'found' ? 'checked' : '' ?>>
                        <span class="type-icon">🎉</span>
                        <span class="type-text">I Found Something</span>
                    </label>
                </div>
                <span class="form-error" id="typeError"></span>
            </div>

            <div class="form-row">
                <!-- Item Title -->
                <div class="form-group form-group-2">
                    <label for="title" class="form-label">Item Name / Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" class="form-control"
                           placeholder="e.g. Blue Samsung Galaxy S21" required
                           minlength="3" maxlength="200">
                    <span class="form-hint">Be descriptive: include brand, color, model if known.</span>
                    <span class="form-error" id="titleError"></span>
                </div>

                <!-- Category -->
                <div class="form-group">
                    <label for="category" class="form-label">Category <span class="required">*</span></label>
                    <select id="category" name="category" class="form-control" required>
                        <option value="">— Select category —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= h($cat) ?>"><?= h($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-error" id="categoryError"></span>
                </div>
            </div>

            <div class="form-row">
                <!-- Location -->
                <div class="form-group">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" id="location" name="location" class="form-control"
                           placeholder="e.g. College Library Block B" maxlength="200">
                    <span class="form-hint">Where was the item lost/found?</span>
                </div>

                <!-- Date -->
                <div class="form-group">
                    <label for="incident_date" class="form-label">Date of Incident <span class="required">*</span></label>
                    <input type="date" id="incident_date" name="incident_date" class="form-control"
                           required max="<?= date('Y-m-d') ?>">
                    <span class="form-error" id="incident_dateError"></span>
                </div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-control form-textarea"
                          rows="4" maxlength="2000"
                          placeholder="Include identifying details: serial number, unique marks, contents, etc."></textarea>
                <span class="form-hint char-counter" data-target="description">0 / 2000 characters</span>
            </div>

            <!-- Image Upload -->
            <div class="form-group">
                <label for="image" class="form-label">Image (optional)</label>
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
                <span class="form-error" id="imageError"></span>
            </div>

            <div class="form-actions">
                <a href="dashboard.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    Submit Report
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
