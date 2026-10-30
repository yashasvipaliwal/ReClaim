<?php
/**
 * actions/ajax-search.php
 * ─────────────────────────────────────────────────────────────
 * Returns JSON search results for live search (AJAX).
 * Called by js/main.js when the user types in the search box.
 *
 * Output: JSON array of report objects
 * Security: PDO prepared statements, no session required for public search
 */
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/config/db.php';

header('Content-Type: application/json; charset=UTF-8');

// Accept only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$q        = trim($_GET['q']        ?? '');
$type     = trim($_GET['type']     ?? '');
$category = trim($_GET['cat']      ?? '');
$limit    = min(20, max(1, (int)($_GET['limit'] ?? 10)));

if (strlen($q) < 2 && $type === '' && $category === '') {
    echo json_encode([]);
    exit;
}

$where  = ["r.is_deleted = 0", "r.status != 'closed'"];
$params = [];

if ($q !== '') {
    $where[]  = '(r.title LIKE ? OR r.description LIKE ? OR r.location LIKE ?)';
    $like     = '%' . $q . '%';
    $params   = array_merge($params, [$like, $like, $like]);
}
if (in_array($type, ['lost','found'], true)) {
    $where[]  = 'r.type = ?';
    $params[] = $type;
}
if ($category !== '') {
    $where[]  = 'r.category = ?';
    $params[] = $category;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);
$stmt = $pdo->prepare(
    "SELECT r.id, r.title, r.type, r.category, r.location, r.incident_date,
            r.status, r.image_path
     FROM reports r $whereSQL
     ORDER BY r.created_at DESC LIMIT $limit"
);
$stmt->execute($params);
$results = $stmt->fetchAll();

// Build safe output array (never output raw DB rows to avoid data leaks)
$output = array_map(function($r) {
    return [
        'id'            => (int) $r['id'],
        'title'         => $r['title'],
        'type'          => $r['type'],
        'category'      => $r['category'],
        'location'      => $r['location'] ?? '',
        'incident_date' => $r['incident_date'],
        'status'        => $r['status'],
        'image'         => $r['image_path'] ? '/ReClaim/uploads/' . $r['image_path'] : null,
        'url'           => '/ReClaim/report-view.php?id=' . $r['id'],
    ];
}, $results);

echo json_encode($output, JSON_UNESCAPED_UNICODE);
