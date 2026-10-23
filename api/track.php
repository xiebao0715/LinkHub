<?php
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$type = $input['type'] ?? '';
$id = (int) ($input['id'] ?? 0);

if ($type === 'carousel' && $id > 0) {
    Stats::recordCarouselClick($id);
    json_response(['success' => true]);
}

if ($type === 'link' && $id > 0) {
    Stats::recordLinkClick($id);
    json_response(['success' => true]);
}

json_response(['success' => false, 'message' => 'Invalid request'], 400);
