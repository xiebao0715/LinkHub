<?php
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$keyword = trim($_GET['q'] ?? '');
$scope = $_GET['scope'] ?? 'all';

if ($keyword === '') {
    json_response(['success' => true, 'data' => [], 'total' => 0]);
}

Stats::recordSearch($keyword);

$db = Database::getInstance();
$params = [];
$where = ['(l.title LIKE ? OR l.url LIKE ? OR l.description LIKE ? OR p.name LIKE ?)'];
$like = '%' . $keyword . '%';
$params = array_merge($params, [$like, $like, $like, $like]);

if ($scope !== 'all' && is_numeric($scope)) {
    $where[] = 'l.platform_id = ?';
    $params[] = (int) $scope;
}

$sql = 'SELECT l.*, p.name as platform_name, p.logo as platform_logo
        FROM links l
        JOIN platforms p ON l.platform_id = p.id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY l.created_at DESC
        LIMIT 50';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

json_response([
    'success' => true,
    'data' => $results,
    'total' => count($results),
]);
