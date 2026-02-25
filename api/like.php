<?php
/**
 * API - نقطة نهاية للإعجاب
 */
define('MUSICAN_APP', true);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
startSession();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً']);
    exit;
}

$input   = json_decode(file_get_contents('php://input'), true);
$audioId = (int)($input['audio_id'] ?? 0);
$userId  = getCurrentUser()['id'];

if (!$audioId) {
    echo json_encode(['success' => false, 'message' => 'معرف غير صحيح']);
    exit;
}

$result = toggleLike($audioId, $userId);
$count  = $result['count'];
echo json_encode(['success' => true, 'liked' => $result['liked'], 'count' => $count]);
