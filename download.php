<?php
/**
 * صفحة التنزيل
 * Musican
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
startSession();

if (getSetting('allow_download', '1') !== '1') {
    die('التنزيل غير مسموح به حاليًا.');
}

$audioId = (int)($_GET['audio'] ?? 0);
$albumId = (int)($_GET['album'] ?? 0);

if ($audioId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM audios WHERE id = ? AND status = 'published' AND allow_download = 1");
    $stmt->execute([$audioId]);
    $audio = $stmt->fetch();

    if (!$audio) die('الملف غير متاح.');

    $filePath = UPLOADS_PATH . '/audios/' . $audio['audio_file'];
    if (!file_exists($filePath)) die('الملف غير موجود على الخادم.');

    // زيادة عداد التنزيل
    $db->prepare("UPDATE audios SET downloads = downloads + 1 WHERE id = ?")->execute([$audioId]);

    $filename = sanitize_filename($audio['title']) . '.' . pathinfo($audio['audio_file'], PATHINFO_EXTENSION);
    header('Content-Type: audio/' . pathinfo($audio['audio_file'], PATHINFO_EXTENSION));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, must-revalidate');
    readfile($filePath);
    exit;
}

if ($albumId) {
    // تنزيل ألبوم كـ ZIP
    $db = getDB();
    $album = $db->prepare("SELECT * FROM albums WHERE id = ?");
    $album->execute([$albumId]);
    $albumData = $album->fetch();
    if (!$albumData) die('الألبوم غير موجود.');

    $audios = $db->prepare("SELECT * FROM audios WHERE album_id = ? AND status = 'published' AND allow_download = 1");
    $audios->execute([$albumId]);
    $tracks = $audios->fetchAll();

    if (empty($tracks)) die('لا توجد مقاطع للتنزيل.');

    if (!class_exists('ZipArchive')) die('خاصية ZIP غير مدعومة.');

    $zipFile = sys_get_temp_dir() . '/album_' . $albumId . '_' . time() . '.zip';
    $zip = new ZipArchive();
    $zip->open($zipFile, ZipArchive::CREATE);

    foreach ($tracks as $track) {
        $filePath = UPLOADS_PATH . '/audios/' . $track['audio_file'];
        if (file_exists($filePath)) {
            $trackName = sanitize_filename($track['title']) . '.' . pathinfo($track['audio_file'], PATHINFO_EXTENSION);
            $zip->addFile($filePath, $trackName);
            $db->prepare("UPDATE audios SET downloads = downloads + 1 WHERE id = ?")->execute([$track['id']]);
        }
    }
    $zip->close();

    $albumName = sanitize_filename($albumData['title']);
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $albumName . '.zip"');
    header('Content-Length: ' . filesize($zipFile));
    readfile($zipFile);
    unlink($zipFile);
    exit;
}

die('طلب غير صحيح.');

function sanitize_filename(string $name): string {
    return preg_replace('/[^a-zA-Z0-9\u0600-\u06FF._-]/u', '_', $name);
}
