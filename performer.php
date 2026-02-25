<?php
/**
 * صفحة المؤدين والمؤدي الفردي
 * Musican
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/templates_loader.php';
startSession();

$activePage = 'performers';
$slug = clean($_GET['slug'] ?? '');

// ===== صفحة مؤدي فردي =====
if ($slug) {
    $performer = getPerformerBySlug($slug);
    if (!$performer) { header('HTTP/1.0 404 Not Found'); require __DIR__ . '/404.php'; exit; }

    $pageTitle  = $performer['name'];
    $perPage    = 12;
    $currentPage = max(1, (int)($_GET['page'] ?? 1));
    $options    = ['performer_id' => $performer['id'], 'limit' => $perPage, 'offset' => ($currentPage-1)*$perPage, 'order' => $_GET['order'] ?? 'latest'];
    $audios     = getAudios($options);
    $total      = countAudios($options);
    $albums     = getAlbums(20, 0, $performer['id']);
    $pagination = getPagination($total, $perPage, $currentPage, BASE_URL . '/performer.php?slug=' . urlencode($slug));
    $currentUser = getCurrentUser();
    $following  = ($currentUser && $performer) ? isFollowing($currentUser['id'], $performer['id']) : false;

    require_once __DIR__ . '/includes/header.php';
    ?>

    <!-- رأس المؤدي -->
    <div class="performer-hero">
        <div class="container">
            <div class="breadcrumb">
                <a href="<?= BASE_URL ?>/">الرئيسية</a>
                <span class="sep">/</span>
                <a href="<?= BASE_URL ?>/performer.php">المؤدون</a>
                <span class="sep">/</span>
                <span class="current"><?= clean($performer['name']) ?></span>
            </div>
            <div class="performer-hero-inner">
                <img class="performer-hero-img" src="<?= getImageUrl('performers', $performer['image'] ?? '') ?>" alt="<?= clean($performer['name']) ?>">
                <div class="performer-hero-info">
                    <h1 class="performer-hero-name">
                        <?= clean($performer['name']) ?>
                        <?php if ($performer['is_verified']): ?><span style="color:var(--gold);font-size:1.2rem;" title="موثّق">✓</span><?php endif; ?>
                    </h1>
                    <?php if ($performer['bio']): ?>
                        <p class="performer-hero-bio"><?= nl2br(clean($performer['bio'])) ?></p>
                    <?php endif; ?>
                    <div class="performer-stats-row">
                        <div class="performer-stat">
                            <span class="num"><?= formatNumber($total) ?></span>
                            <span class="label">مقطع</span>
                        </div>
                        <div class="performer-stat">
                            <span class="num"><?= formatNumber(count($albums)) ?></span>
                            <span class="label">ألبوم</span>
                        </div>
                        <div class="performer-stat">
                            <span class="num"><?= formatNumber($performer['followers_count']) ?></span>
                            <span class="label">متابع</span>
                        </div>
                    </div>
                    <div style="display:flex;gap:12px;flex-wrap:wrap;">
                        <?php if ($currentUser && $currentUser['id'] !== ($performer['user_id'] ?? -1)): ?>
                            <button class="btn btn-gold follow-btn <?= $following ? 'following' : '' ?>" data-id="<?= $performer['id'] ?>" id="follow-btn-<?= $performer['id'] ?>">
                                <?= $following ? '✓ متابَع' : '+ متابعة' ?>
                            </button>
                            <?php if (!empty($performer['user_id'])): ?>
                                <a href="<?= BASE_URL ?>/messages.php?id=<?= $performer['user_id'] ?>" class="btn btn-outline">
                                    <i class="fas fa-envelope"></i> راسلني
                                </a>
                            <?php endif; ?>
                        <?php elseif (!$currentUser): ?>
                            <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline">+ متابعة</a>
                        <?php endif; ?>
                        <?php if (!empty($albums)): ?>
                            <a href="<?= BASE_URL ?>/album.php?performer=<?= $performer['id'] ?>" class="btn btn-ghost btn-sm">
                                <i class="fas fa-compact-disc"></i> الألبومات
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- المقاطع -->
    <div class="section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">مقاطع <?= clean($performer['name']) ?></h2>
                <form method="get">
                    <input type="hidden" name="slug" value="<?= clean($slug) ?>">
                    <select name="order" class="filter-select" onchange="this.form.submit()">
                        <option value="latest" <?= ($_GET['order']??'latest')==='latest'?'selected':'' ?>>الأحدث</option>
                        <option value="popular" <?= ($_GET['order']??'')==='popular'?'selected':'' ?>>الأكثر استماعًا</option>
                        <option value="top_rated" <?= ($_GET['order']??'')==='top_rated'?'selected':'' ?>>الأعلى تقييمًا</option>
                    </select>
                </form>
            </div>

            <?php if (empty($audios)): ?>
                <div class="empty-state"><div class="icon">🎵</div><h3>لا توجد مقاطع بعد</h3></div>
            <?php else: ?>
                <div class="grid grid-4">
                    <?php foreach ($audios as $audio): ?>
                        <div class="audio-card" onclick="playAudioCard(<?= $audio['id'] ?>, '<?= addslashes($audio['title']) ?>', '<?= addslashes($audio['performer_name']) ?>', '<?= addslashes(getImageUrl('albums', $audio['cover_image'] ?? '')) ?>', '<?= addslashes(getAudioUrl($audio['audio_file'])) ?>', '<?= addslashes($audio['duration'] ?? '') ?>')">
                            <div class="audio-card-img">
                                <img src="<?= getImageUrl('albums', $audio['cover_image'] ?? '') ?>" alt="<?= clean($audio['title']) ?>" loading="lazy">
                                <div class="audio-card-play"><div class="play-btn-circle"><i class="fas fa-play"></i></div></div>
                            </div>
                            <div class="audio-card-body">
                                <div class="audio-card-title"><?= clean($audio['title']) ?></div>
                                <div class="audio-card-performer"><?= clean($audio['category_name']) ?></div>
                                <div class="audio-card-meta">
                                    <span><i class="fas fa-headphones"></i> <?= formatNumber($audio['listens']) ?></span>
                                    <?php if ((float)$audio['rating_avg'] > 0): ?>
                                        <span><i class="fas fa-star" style="color:var(--gold)"></i> <?= number_format($audio['rating_avg'],1) ?></span>
                                    <?php endif; ?>
                                    <a href="<?= BASE_URL ?>/audio.php?slug=<?= urlencode($audio['slug']) ?>" onclick="event.stopPropagation()" style="color:var(--text-muted);margin-right:auto;font-size:0.75rem;"><i class="fas fa-info-circle"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?= renderPagination($pagination) ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function playAudioCard(id, title, performer, cover, url, duration) {
        if (!url) return;
        MusicanPlayer.addToPlaylist({ id, title, performer, cover: cover || '<?= DEFAULT_COVER_URL ?>', url, duration }, true);
    }
    </script>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <?php exit; ?>
<?php } ?>

<?php
// ===== صفحة قائمة المؤدين =====
$pageTitle = 'المؤدون';
$perPage   = 20;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$total     = countPerformers();
$performers = getPerformers($perPage, ($currentPage-1)*$perPage);
$pagination = getPagination($total, $perPage, $currentPage, BASE_URL . '/performer.php');

require_once __DIR__ . '/includes/header.php';
?>

<div style="background:var(--card-bg);border-bottom:1px solid var(--border);padding:30px 0;">
    <div class="container">
        <h1 style="font-size:1.8rem;font-weight:900;margin-bottom:4px;">🎤 المؤدون</h1>
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>/">الرئيسية</a>
            <span class="sep">/</span>
            <span class="current">المؤدون</span>
        </div>
    </div>
</div>

<div class="section">
    <div class="container">
        <?php if (empty($performers)): ?>
            <div class="empty-state"><div class="icon">🎤</div><h3>لا يوجد مؤدون بعد</h3></div>
        <?php else: ?>
            <div class="grid grid-4">
                <?php foreach ($performers as $performer): ?>
                    <a href="<?= BASE_URL ?>/performer.php?slug=<?= urlencode($performer['slug']) ?>" class="performer-card">
                        <img class="performer-avatar" src="<?= getImageUrl('performers', $performer['image'] ?? '') ?>" alt="<?= clean($performer['name']) ?>" loading="lazy">
                        <?php if ($performer['is_verified']): ?>
                            <div class="performer-verified">✓ موثّق</div>
                        <?php endif; ?>
                        <div class="performer-name"><?= clean($performer['name']) ?></div>
                        <div class="performer-meta">
                            <span><i class="fas fa-music"></i> <?= $performer['audios_count'] ?> مقطع</span>
                            &nbsp;·&nbsp;
                            <span><i class="fas fa-users"></i> <?= formatNumber($performer['followers_count']) ?> متابع</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <?= renderPagination($pagination) ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
