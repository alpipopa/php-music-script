<?php
/**
 * صفحة الألبومات والألبوم الفردي
 * Musican
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/templates_loader.php';
startSession();

$activePage = 'albums';
$slug = clean($_GET['slug'] ?? '');

// ===== ألبوم فردي =====
if ($slug) {
    $album = getAlbumBySlug($slug);
    if (!$album) { header('HTTP/1.0 404 Not Found'); readfile(__DIR__ . '/404.php'); exit; }

    $pageTitle = $album['title'];
    $audios = getAudios(['album_id' => $album['id'], 'limit' => 100]);

    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="performer-hero">
        <div class="container">
            <div class="breadcrumb">
                <a href="<?= BASE_URL ?>/">الرئيسية</a><span class="sep">/</span>
                <a href="<?= BASE_URL ?>/album.php">الألبومات</a><span class="sep">/</span>
                <span class="current"><?= clean($album['title']) ?></span>
            </div>
            <div class="audio-hero-inner" style="align-items:flex-start;">
                <div class="audio-cover" style="width:200px;height:200px;flex-shrink:0;">
                    <img src="<?= getImageUrl('albums', $album['cover_image'] ?? '') ?>" alt="<?= clean($album['title']) ?>">
                </div>
                <div class="audio-info">
                    <span class="audio-category">ألبوم</span>
                    <h1 class="audio-title"><?= clean($album['title']) ?></h1>
                    <p class="audio-performer-name">بواسطة: <a href="<?= BASE_URL ?>/performer.php?slug=<?= urlencode($album['performer_slug']) ?>"><?= clean($album['performer_name']) ?></a></p>
                    <?php if ($album['description']): ?>
                        <p style="color:var(--text-muted);margin-bottom:20px;"><?= clean($album['description']) ?></p>
                    <?php endif; ?>
                    <div class="audio-stats">
                        <div class="audio-stat"><span class="num"><?= count($audios) ?></span> <span>مقطع</span></div>
                        <?php if ($album['year']): ?><div class="audio-stat"><span class="num"><?= $album['year'] ?></span></div><?php endif; ?>
                    </div>
                    <?php if (!empty($audios) && getSetting('allow_download','1')==='1'): ?>
                        <a href="<?= BASE_URL ?>/download.php?album=<?= $album['id'] ?>" class="btn btn-gold">
                            <i class="fas fa-download"></i> تنزيل الألبوم كاملاً
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($audios)): ?>
                        <button class="btn btn-outline" onclick="playAlbum()">
                            <i class="fas fa-play"></i> تشغيل الكل
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="container">
            <?php if (empty($audios)): ?>
                <div class="empty-state"><div class="icon">🎵</div><h3>لا توجد مقاطع في هذا الألبوم</h3></div>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <?php foreach ($audios as $i => $audio): ?>
                        <div class="audio-card-h" onclick="playAudioCard(<?= $audio['id'] ?>, '<?= addslashes($audio['title']) ?>', '<?= addslashes($audio['performer_name']) ?>', '<?= addslashes(getImageUrl('albums', $audio['cover_image'] ?? '')) ?>', '<?= addslashes(getAudioUrl($audio['audio_file'])) ?>', '<?= addslashes($audio['duration'] ?? '') ?>')">
                            <span style="color:var(--gold);font-weight:900;min-width:30px;text-align:center;"><?= str_pad($i+1,2,'0',STR_PAD_LEFT) ?></span>
                            <img class="thumb" src="<?= getImageUrl('albums', $audio['cover_image'] ?? '') ?>" alt="" loading="lazy">
                            <div class="info">
                                <div class="title"><?= clean($audio['title']) ?></div>
                                <div class="meta"><?= $audio['duration'] ? $audio['duration'] . ' · ' : '' ?><?= formatNumber($audio['listens']) ?> استماع</div>
                            </div>
                            <div class="actions">
                                <?php if (getSetting('allow_download','1')==='1' && $audio['allow_download']): ?>
                                    <a href="<?= BASE_URL ?>/download.php?audio=<?= $audio['id'] ?>" onclick="event.stopPropagation()" class="btn-icon-sm" title="تنزيل"><i class="fas fa-download"></i></a>
                                <?php endif; ?>
                                <button class="play-btn-circle" style="width:36px;height:36px;font-size:0.8rem;"><i class="fas fa-play"></i></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    const albumTracks = <?= json_encode(array_map(function($a) {
        return [
            'id' => $a['id'], 'title' => $a['title'], 'performer' => $a['performer_name'],
            'cover' => getImageUrl('albums', $a['cover_image'] ?? ''),
            'url' => getAudioUrl($a['audio_file']), 'duration' => $a['duration'] ?? ''
        ];
    }, $audios)) ?>;

    function playAlbum() {
        MusicanPlayer.clearPlaylist();
        MusicanPlayer.addMultipleToPlaylist(albumTracks, true);
    }

    function playAudioCard(id, title, performer, cover, url, duration) {
        if (!url) return;
        MusicanPlayer.addToPlaylist({ id, title, performer, cover: cover || '<?= DEFAULT_COVER_URL ?>', url, duration }, true);
    }
    </script>

    <?php require_once __DIR__ . '/includes/footer.php'; exit; ?>
<?php } ?>

<?php
// ===== قائمة الألبومات =====
$pageTitle = 'الألبومات';
$perPage   = 12;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$performerId = (int)($_GET['performer'] ?? 0);
$total     = countAlbums();
$albums    = getAlbums($perPage, ($currentPage-1)*$perPage, $performerId);
$pagination = getPagination($total, $perPage, $currentPage, BASE_URL . '/album.php');

require_once __DIR__ . '/includes/header.php';
?>

<div style="background:var(--card-bg);border-bottom:1px solid var(--border);padding:30px 0;">
    <div class="container">
        <h1 style="font-size:1.8rem;font-weight:900;">💿 الألبومات</h1>
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>/">الرئيسية</a><span class="sep">/</span><span class="current">الألبومات</span>
        </div>
    </div>
</div>

<div class="section">
    <div class="container">
        <?php if (empty($albums)): ?>
            <div class="empty-state"><div class="icon">💿</div><h3>لا توجد ألبومات بعد</h3></div>
        <?php else: ?>
            <div class="grid grid-4">
                <?php foreach ($albums as $album): ?>
                    <a href="<?= BASE_URL ?>/album.php?slug=<?= urlencode($album['slug']) ?>" class="album-card">
                        <div class="album-cover">
                            <img src="<?= getImageUrl('albums', $album['cover_image'] ?? '') ?>" alt="<?= clean($album['title']) ?>" loading="lazy">
                        </div>
                        <div class="album-card-body">
                            <div class="album-title"><?= clean($album['title']) ?></div>
                            <div class="album-performer">
                                <a href="<?= BASE_URL ?>/performer.php?slug=<?= urlencode($album['performer_slug']) ?>" onclick="event.stopPropagation()"><?= clean($album['performer_name']) ?></a>
                                · <?= $album['audios_count'] ?> مقطع
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <?= renderPagination($pagination) ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
