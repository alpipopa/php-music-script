<?php
/**
 * صفحة الصوتيات - عرض ببحث وفلترة
 * Musican
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/templates_loader.php';
startSession();

$pageTitle  = 'الصوتيات';
$activePage = 'player';

// معاملات الفلترة
$search      = clean($_GET['search'] ?? '');
$categoryId  = (int)($_GET['category'] ?? 0);
$performerId = (int)($_GET['performer'] ?? 0);
$albumId     = (int)($_GET['album'] ?? 0);
$order       = in_array($_GET['order'] ?? '', ['latest','popular','most_downloaded','top_rated']) ? $_GET['order'] : 'latest';
$perPage     = (int)getSetting('audios_per_page', '12');
$currentPage = max(1, (int)($_GET['page'] ?? 1));

$options = [
    'search'      => $search,
    'category_id' => $categoryId,
    'performer_id'=> $performerId,
    'album_id'    => $albumId,
    'order'       => $order,
    'limit'       => $perPage,
    'offset'      => ($currentPage - 1) * $perPage,
];

$total      = countAudios($options);
$audios     = getAudios($options);
$pagination = getPagination($total, $perPage, $currentPage, BASE_URL . '/player.php?' . http_build_query(['search'=>$search,'category'=>$categoryId,'performer'=>$performerId,'order'=>$order]));
$categories = getCategories();
$performers = getPerformers(100);

require_once __DIR__ . '/includes/header.php';
?>

<!-- رأس الصفحة -->
<div style="background:var(--card-bg);border-bottom:1px solid var(--border);padding:30px 0;">
    <div class="container">
        <h1 style="font-size:1.8rem;font-weight:900;margin-bottom:4px;">🎵 الصوتيات</h1>
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>/">الرئيسية</a>
            <span class="sep">/</span>
            <span class="current">الصوتيات</span>
            <?php if ($search): ?><span class="sep">/</span><span class="current">نتائج: <?= clean($search) ?></span><?php endif; ?>
        </div>
    </div>
</div>

<div class="section">
    <div class="container">

        <!-- شريط الفلترة -->
        <div class="filters-bar">
            <!-- البحث -->
            <form method="get" action="<?= BASE_URL ?>/player.php" style="display:flex;gap:8px;flex:1;">
                <input type="text" name="search" value="<?= clean($search) ?>" placeholder="ابحث..." class="form-control" style="max-width:250px;">
                <select name="category" class="filter-select">
                    <option value="0">كل الأقسام</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>><?= clean($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="order" class="filter-select">
                    <option value="latest" <?= $order === 'latest' ? 'selected' : '' ?>>الأحدث</option>
                    <option value="popular" <?= $order === 'popular' ? 'selected' : '' ?>>الأكثر استماعًا</option>
                    <option value="most_downloaded" <?= $order === 'most_downloaded' ? 'selected' : '' ?>>الأكثر تنزيلًا</option>
                    <option value="top_rated" <?= $order === 'top_rated' ? 'selected' : '' ?>>الأعلى تقييمًا</option>
                </select>
                <button type="submit" class="btn btn-gold btn-sm"><i class="fas fa-search"></i> بحث</button>
                <?php if ($search || $categoryId || $order !== 'latest'): ?>
                    <a href="<?= BASE_URL ?>/player.php" class="btn btn-ghost btn-sm">إعادة ضبط</a>
                <?php endif; ?>
            </form>

            <span style="color:var(--text-muted);font-size:0.85rem;white-space:nowrap;"><?= number_format($total) ?> نتيجة</span>
        </div>

        <!-- الشبكة -->
        <?php if (empty($audios)): ?>
            <div class="empty-state">
                <div class="icon">🔍</div>
                <h3>لا توجد نتائج</h3>
                <p>جرّب كلمات بحث مختلفة أو قسمًا آخر</p>
                <a href="<?= BASE_URL ?>/player.php" class="btn btn-gold" style="margin-top:16px;">عرض الكل</a>
            </div>
        <?php else: ?>
            <div class="grid grid-4">
                <?php foreach ($audios as $audio): ?>
                    <div class="audio-card" onclick="playAudioCard(<?= $audio['id'] ?>, '<?= addslashes($audio['title']) ?>', '<?= addslashes($audio['performer_name']) ?>', '<?= addslashes(getImageUrl('albums', $audio['cover_image'] ?? '')) ?>', '<?= addslashes(getAudioUrl($audio['audio_file'])) ?>', '<?= addslashes($audio['duration'] ?? '') ?>')">
                        <div class="audio-card-img">
                            <img src="<?= getImageUrl('albums', $audio['cover_image'] ?? '') ?>" alt="<?= clean($audio['title']) ?>" loading="lazy">
                            <div class="audio-card-play">
                                <div class="play-btn-circle"><i class="fas fa-play"></i></div>
                            </div>
                        </div>
                        <div class="audio-card-body">
                            <div class="audio-card-title" title="<?= clean($audio['title']) ?>"><?= clean($audio['title']) ?></div>
                            <div class="audio-card-performer">
                                <a href="<?= BASE_URL ?>/performer.php?slug=<?= urlencode($audio['performer_slug']) ?>" onclick="event.stopPropagation()"><?= clean($audio['performer_name']) ?></a>
                                · <a href="<?= BASE_URL ?>/category.php?slug=<?= urlencode($audio['category_slug']) ?>" onclick="event.stopPropagation()"><?= clean($audio['category_name']) ?></a>
                            </div>
                            <div class="audio-card-meta">
                                <span><i class="fas fa-headphones"></i> <?= formatNumber($audio['listens']) ?></span>
                                <?php if ((float)$audio['rating_avg'] > 0): ?>
                                    <span><i class="fas fa-star" style="color:var(--gold)"></i> <?= number_format($audio['rating_avg'],1) ?></span>
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>/audio.php?slug=<?= urlencode($audio['slug']) ?>" onclick="event.stopPropagation()" style="color:var(--text-muted);margin-right:auto;font-size:0.75rem;" title="عرض التفاصيل"><i class="fas fa-info-circle"></i></a>
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
    if (!url) { alert('الملف الصوتي غير متاح'); return; }
    MusicanPlayer.addToPlaylist({ id, title, performer, cover: cover || '<?= DEFAULT_COVER_URL ?>', url, duration }, true);
    fetch('<?= BASE_URL ?>/api/listen.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) }).catch(()=>{});
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
