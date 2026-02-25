<?php
/**
 * صفحة الأقسام
 * Musican
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/templates_loader.php';
startSession();

$activePage = 'categories';
$slug = clean($_GET['slug'] ?? '');

// ===== قسم فردي =====
if ($slug) {
    $category = getCategoryBySlug($slug);
    if (!$category) { header('HTTP/1.0 404 Not Found'); echo '<h1>404</h1>'; exit; }
    
    $pageTitle = $category['name'];
    $perPage   = (int)getSetting('audios_per_page', '12');
    $currentPage = max(1, (int)($_GET['page'] ?? 1));
    $order = in_array($_GET['order'] ?? '', ['latest','popular','top_rated']) ? $_GET['order'] : 'latest';
    $options = ['category_id' => $category['id'], 'limit' => $perPage, 'offset' => ($currentPage-1)*$perPage, 'order' => $order];
    $audios = getAudios($options);
    $total  = countAudios($options);
    $pagination = getPagination($total, $perPage, $currentPage, BASE_URL . '/category.php?slug=' . urlencode($slug) . '&order=' . $order);

    require_once __DIR__ . '/includes/header.php';
    ?>
    <div style="background:var(--card-bg);border-bottom:1px solid var(--border);padding:40px 0;">
        <div class="container">
            <div class="breadcrumb">
                <a href="<?= BASE_URL ?>/">الرئيسية</a><span class="sep">/</span>
                <a href="<?= BASE_URL ?>/category.php">الأقسام</a><span class="sep">/</span>
                <span class="current"><?= clean($category['name']) ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:20px;margin-top:16px;">
                <div style="width:70px;height:70px;background:rgba(212,175,55,0.15);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:2rem;color:var(--gold);">
                    <i class="fas <?= clean($category['icon'] ?? 'fa-music') ?>"></i>
                </div>
                <div>
                    <h1 style="font-size:2rem;font-weight:900;"><?= clean($category['name']) ?></h1>
                    <?php if ($category['description']): ?>
                        <p style="color:var(--text-muted);"><?= clean($category['description']) ?></p>
                    <?php endif; ?>
                    <span style="color:var(--text-muted);font-size:0.85rem;"><?= number_format($total) ?> مقطع</span>
                </div>
            </div>
        </div>
    </div>
    <div class="section">
        <div class="container">
            <div class="filters-bar" style="margin-bottom:24px;">
                <div class="filter-tabs">
                    <a href="?slug=<?= $slug ?>&order=latest" class="filter-tab <?= $order==='latest'?'active':'' ?>">الأحدث</a>
                    <a href="?slug=<?= $slug ?>&order=popular" class="filter-tab <?= $order==='popular'?'active':'' ?>">الأكثر استماعًا</a>
                    <a href="?slug=<?= $slug ?>&order=top_rated" class="filter-tab <?= $order==='top_rated'?'active':'' ?>">الأعلى تقييمًا</a>
                </div>
            </div>
            <?php if (empty($audios)): ?>
                <div class="empty-state"><div class="icon">🎵</div><h3>لا توجد مقاطع في هذا القسم</h3></div>
            <?php else: ?>
                <div class="grid grid-4">
                    <?php foreach ($audios as $audio): ?>
                        <div class="audio-card" onclick="location.href='<?= BASE_URL ?>/audio.php?slug=<?= urlencode($audio['slug']) ?>'">
                            <div class="audio-card-img">
                                <img src="<?= getImageUrl('albums', $audio['cover_image'] ?? '') ?>" alt="<?= clean($audio['title']) ?>" loading="lazy">
                                <div class="audio-card-play">
                                    <div class="play-btn-circle" onclick="event.stopPropagation();playCard(<?= $audio['id'] ?>, '<?= addslashes($audio['title']) ?>', '<?= addslashes($audio['performer_name']) ?>', '<?= addslashes(getImageUrl('albums', $audio['cover_image'] ?? '')) ?>', '<?= addslashes(getAudioUrl($audio['audio_file'])) ?>', '<?= addslashes($audio['duration'] ?? '') ?>')"><i class="fas fa-play"></i></div>
                                </div>
                            </div>
                            <div class="audio-card-body">
                                <div class="audio-card-title"><?= clean($audio['title']) ?></div>
                                <div class="audio-card-performer"><a href="<?= BASE_URL ?>/performer.php?slug=<?= urlencode($audio['performer_slug']) ?>" onclick="event.stopPropagation()"><?= clean($audio['performer_name']) ?></a></div>
                                <div class="audio-card-meta">
                                    <span><i class="fas fa-headphones"></i> <?= formatNumber($audio['listens']) ?></span>
                                    <?php if ((float)$audio['rating_avg'] > 0): ?><span><i class="fas fa-star" style="color:var(--gold)"></i> <?= number_format($audio['rating_avg'],1) ?></span><?php endif; ?>
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
    function playCard(id, title, performer, cover, url, duration) {
        MusicanPlayer.addToPlaylist({ id, title, performer, cover: cover||'<?= DEFAULT_COVER_URL ?>', url, duration }, true);
    }
    </script>
    <?php require_once __DIR__ . '/includes/footer.php'; exit; ?>
<?php } ?>

<?php
// ===== قائمة الأقسام =====
$pageTitle  = 'الأقسام';
$categories = getCategories();
require_once __DIR__ . '/includes/header.php';
?>

<div style="background:var(--card-bg);border-bottom:1px solid var(--border);padding:30px 0;">
    <div class="container">
        <h1 style="font-size:1.8rem;font-weight:900;">📂 الأقسام</h1>
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>/">الرئيسية</a><span class="sep">/</span><span class="current">الأقسام</span>
        </div>
    </div>
</div>

<div class="section">
    <div class="container">
        <div class="grid grid-3">
            <?php foreach ($categories as $cat): ?>
                <a href="<?= BASE_URL ?>/category.php?slug=<?= urlencode($cat['slug']) ?>" class="card" style="padding:30px;text-align:center;text-decoration:none;transition:var(--transition);" onmouseover="this.style.borderColor='var(--gold)'" onmouseout="this.style.borderColor='var(--border)'">
                    <div style="font-size:3rem;color:var(--gold);margin-bottom:16px;"><i class="fas <?= clean($cat['icon'] ?? 'fa-music') ?>"></i></div>
                    <h2 style="font-size:1.2rem;font-weight:700;margin-bottom:8px;"><?= clean($cat['name']) ?></h2>
                    <?php if ($cat['description']): ?>
                        <p style="color:var(--text-muted);font-size:0.85rem;margin-bottom:12px;"><?= clean($cat['description']) ?></p>
                    <?php endif; ?>
                    <span class="badge badge-gold"><?= $cat['audios_count'] ?> مقطع</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
