<?php
/**
 * report-list.php
 * Browseable, searchable listing of all active reports.
 * AJAX-powered live search via actions/ajax-search.php.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

$pageTitle  = 'Browse Reports';
$categories = category_list();

// Read filter params from GET (safe defaults)
$keyword  = trim($_GET['q']        ?? '');
$type     = in_array($_GET['type'] ?? '', ['lost', 'found', '']) ? ($_GET['type'] ?? '') : '';
$category = in_array($_GET['cat']  ?? '', array_merge([''], $categories)) ? ($_GET['cat'] ?? '') : '';
$location = trim($_GET['loc']      ?? '');
$dateFrom = trim($_GET['date_from']?? '');
$dateTo   = trim($_GET['date_to']  ?? '');
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

// ── Build SQL query with filters ─────────────────────────────
$where  = ['r.is_deleted = 0', "r.status != 'closed'"];
$params = [];

if ($keyword !== '') {
    $where[]  = '(r.title LIKE ? OR r.description LIKE ? OR r.location LIKE ?)';
    $like     = '%' . $keyword . '%';
    $params   = array_merge($params, [$like, $like, $like]);
}
if ($type !== '') {
    $where[]  = 'r.type = ?';
    $params[] = $type;
}
if ($category !== '') {
    $where[]  = 'r.category = ?';
    $params[] = $category;
}
if ($location !== '') {
    $where[]  = 'r.location LIKE ?';
    $params[] = '%' . $location . '%';
}
if ($dateFrom !== '') {
    $where[]  = 'r.incident_date >= ?';
    $params[] = $dateFrom;
}
if ($dateTo !== '') {
    $where[]  = 'r.incident_date <= ?';
    $params[] = $dateTo;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

// Count total for pagination
$countSQL = "SELECT COUNT(*) FROM reports r $whereSQL";
$stmt     = $pdo->prepare($countSQL);
$stmt->execute($params);
$total   = (int) $stmt->fetchColumn();
$pages   = (int) ceil($total / $perPage);

// Fetch paginated results
$dataSQL = "SELECT r.*, u.name AS owner_name
            FROM reports r JOIN users u ON r.user_id = u.id
            $whereSQL
            ORDER BY r.created_at DESC
            LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($dataSQL);
$stmt->execute($params);
$reports = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-container">
    <div class="page-header">
        <h1 class="page-title">
            <?php if ($type === 'lost'): ?>😟 Lost Items
            <?php elseif ($type === 'found'): ?>🎉 Found Items
            <?php else: ?>🗂️ Browse All Reports<?php endif; ?>
        </h1>
        <p class="page-subtitle">
            <?= $total ?> report<?= $total !== 1 ? 's' : '' ?> found
            <?= $keyword ? ' for "<strong>' . h($keyword) . '</strong>"' : '' ?>
        </p>
    </div>

    <div class="search-layout">
        <!-- ── Filters sidebar ─────────────────────────── -->
        <aside class="filters-panel" id="filtersPanel">
            <div class="filters-header">
                <h2>🔍 Filter Reports</h2>
                <a href="report-list.php" class="clear-filters">Clear all</a>
            </div>

            <form id="searchForm" method="GET" action="report-list.php">
                <div class="filter-group">
                    <label for="q" class="filter-label">Search</label>
                    <input type="text" id="q" name="q" class="form-control"
                           placeholder="Keywords…" value="<?= h($keyword) ?>" autocomplete="off">
                </div>

                <div class="filter-group">
                    <label class="filter-label">Type</label>
                    <div class="radio-group">
                        <label class="radio-option <?= $type==='' ? 'active':'' ?>">
                            <input type="radio" name="type" value="" <?= $type==='' ? 'checked':'' ?>>
                            All
                        </label>
                        <label class="radio-option <?= $type==='lost' ? 'active':'' ?>">
                            <input type="radio" name="type" value="lost" <?= $type==='lost' ? 'checked':'' ?>>
                            😟 Lost
                        </label>
                        <label class="radio-option <?= $type==='found' ? 'active':'' ?>">
                            <input type="radio" name="type" value="found" <?= $type==='found' ? 'checked':'' ?>>
                            🎉 Found
                        </label>
                    </div>
                </div>

                <div class="filter-group">
                    <label for="cat" class="filter-label">Category</label>
                    <select id="cat" name="cat" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= h($cat) ?>" <?= $category===$cat ? 'selected':'' ?>><?= h($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="loc" class="filter-label">Location</label>
                    <input type="text" id="loc" name="loc" class="form-control"
                           placeholder="e.g. Library" value="<?= h($location) ?>">
                </div>

                <div class="filter-group">
                    <label class="filter-label">Date Range</label>
                    <input type="date" name="date_from" class="form-control" value="<?= h($dateFrom) ?>"
                           placeholder="From" style="margin-bottom:.5rem">
                    <input type="date" name="date_to" class="form-control" value="<?= h($dateTo) ?>"
                           placeholder="To">
                </div>

                <button type="submit" class="btn btn-primary btn-full">Apply Filters</button>
            </form>
        </aside>

        <!-- ── Report grid ──────────────────────────────── -->
        <div class="reports-area">
            <!-- Mobile filter toggle -->
            <button class="btn btn-outline filter-toggle-btn" id="filterToggleBtn">
                🔍 Filters <?php if ($keyword||$type||$category||$location): ?><span class="badge-active">Active</span><?php endif; ?>
            </button>

            <!-- Sort + New Report -->
            <div class="reports-toolbar">
                <span class="results-count"><?= $total ?> result<?= $total !== 1 ? 's' : '' ?></span>
                <?php if (is_logged_in()): ?>
                    <a href="report-create.php" class="btn btn-primary btn-sm">+ New Report</a>
                <?php endif; ?>
            </div>

            <?php if (empty($reports)): ?>
                <!-- Empty state -->
                <div class="empty-state">
                    <div class="empty-icon">🗂️</div>
                    <h3>No reports found</h3>
                    <p>Try adjusting your filters or
                        <?php if (is_logged_in()): ?>
                            <a href="report-create.php">submit a new report</a>.
                        <?php else: ?>
                            <a href="login.php">log in to submit a report</a>.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="report-grid" id="reportGrid">
                    <?php foreach ($reports as $r): ?>
                    <article class="report-card" id="card-<?= $r['id'] ?>">
                        <?php if ($r['image_path']): ?>
                        <div class="report-card-img">
                            <img src="/ReClaim/uploads/<?= h($r['image_path']) ?>"
                                 alt="<?= h($r['title']) ?>" loading="lazy">
                        </div>
                        <?php else: ?>
                        <div class="report-card-img report-card-img-placeholder">
                            <?= $r['type']==='lost' ? '😟' : '🎉' ?>
                        </div>
                        <?php endif; ?>

                        <div class="report-card-body">
                            <div class="report-card-badges">
                                <?= format_type_badge($r['type']) ?>
                                <?= format_status_badge($r['status']) ?>
                            </div>
                            <h3 class="report-card-title">
                                <a href="report-view.php?id=<?= $r['id'] ?>"><?= h($r['title']) ?></a>
                            </h3>
                            <div class="report-card-meta">
                                <span>📂 <?= h($r['category']) ?></span>
                                <span>📍 <?= h($r['location'] ?: 'N/A') ?></span>
                                <span>📅 <?= date('d M Y', strtotime($r['incident_date'])) ?></span>
                            </div>
                            <?php if ($r['description']): ?>
                            <p class="report-card-desc">
                                <?= h(mb_strimwidth($r['description'], 0, 100, '…')) ?>
                            </p>
                            <?php endif; ?>
                        </div>

                        <div class="report-card-footer">
                            <span class="card-owner">By <?= h($r['owner_name']) ?></span>
                            <a href="report-view.php?id=<?= $r['id'] ?>"
                               class="btn btn-sm btn-outline">View Details →</a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($pages > 1): ?>
                <nav class="pagination" aria-label="Page navigation">
                    <?php for ($p = 1; $p <= $pages; $p++):
                        $q = http_build_query(array_merge($_GET, ['page' => $p]));
                    ?>
                        <a href="?<?= $q ?>"
                           class="page-btn <?= $p === $page ? 'active' : '' ?>"
                           aria-label="Page <?= $p ?>"><?= $p ?></a>
                    <?php endfor; ?>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div><!-- /.reports-area -->
    </div><!-- /.search-layout -->
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
