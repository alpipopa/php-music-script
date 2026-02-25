<?php
/**
 * API - نقطة نهاية للتقييم
 */
define('MUSICAN_APP', true);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSession();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً']);
    exit;
}

$input   = json_decode(file_get_contents('php://input'), true);
$audioId = (int)($input['audio_id'] ?? 0);
$rating  = (int)($input['rating'] ?? 0);
$userId  = getCurrentUser()['id'];

if (!$audioId || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير صحيحة']);
    exit;
}

toggleRating($audioId, $userId, $rating);
echo json_encode(['success' => true, 'message' => 'تم التقييم']);
