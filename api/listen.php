<?php
/**
 * API - عداد الاستماع
 */
define('MUSICAN_APP', true);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSession();

header('Content-Type: application/json');

$input   = json_decode(file_get_contents('php://input'), true);
$audioId = (int)($input['id'] ?? 0);

if ($audioId) {
    // زيادة عداد الاستماع
    getDB()->prepare("UPDATE audios SET listens = listens + 1 WHERE id = ?")->execute([$audioId]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
