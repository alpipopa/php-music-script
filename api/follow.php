<?php
/**
 * API - المتابعة
 */
define('MUSICAN_APP', true);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSession();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول']);
    exit;
}

$input       = json_decode(file_get_contents('php://input'), true);
$performerId = (int)($input['performer_id'] ?? 0);
$userId      = getCurrentUser()['id'];

if (!$performerId) {
    echo json_encode(['success' => false]);
    exit;
}

$result = toggleFollow($userId, $performerId);
echo json_encode(['success' => true, 'following' => $result['following'], 'count' => $result['count']]);
