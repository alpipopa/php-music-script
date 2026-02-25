<?php
/**
 * API - البحث السريع (JSON)
 */
define('MUSICAN_APP', true);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSession();

header('Content-Type: application/json');

$q = clean($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode(['audios' => [], 'performers' => []]); exit; }

$db = getDB();

// مقاطع
$stmt = $db->prepare("SELECT a.id, a.title, a.slug, a.cover_image, p.name AS performer FROM audios a LEFT JOIN performers p ON a.performer_id = p.id WHERE a.status = 'published' AND (a.title LIKE ? OR p.name LIKE ?) LIMIT 5");
$like = '%' . $q . '%';
$stmt->execute([$like, $like]);
$audios = $stmt->fetchAll();

// مؤدون
$stmt2 = $db->prepare("SELECT id, name, slug, image FROM performers WHERE name LIKE ? LIMIT 3");
$stmt2->execute([$like]);
$performers = $stmt2->fetchAll();

echo json_encode([
    'audios' => array_map(fn($a) => [
        'id'        => $a['id'],
        'title'     => $a['title'],
        'slug'      => $a['slug'],
        'cover'     => getImageUrl('albums', $a['cover_image'] ?? ''),
        'performer' => $a['performer'],
    ], $audios),
    'performers' => array_map(fn($p) => [
        'id'    => $p['id'],
        'name'  => $p['name'],
        'slug'  => $p['slug'],
        'image' => getImageUrl('performers', $p['image'] ?? ''),
    ], $performers),
]);
