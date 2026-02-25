<?php
/**
 * الصفحة الرئيسية
 * Musican - منصة الصوتيات الاحترافية
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/templates_loader.php';

startSession();

$pageTitle = 'الرئيسية';
$activePage = 'home';

// الحصول على البيانات
$latestAudios    = getAudios(['limit' => 8, 'order' => 'latest']);
$popularAudios   = getAudios(['limit' => 8, 'order' => 'popular']);
$featuredAudios  = getAudios(['limit' => 4, 'featured' => true, 'order' => 'popular']);
$latestPerformers = getPerformers(8);
$latestAlbums    = getAlbums(6);
$categories      = getCategories();

// إحصائيات
$stats = [
    'audios'     => countAudios([]),
    'performers' => countPerformers(),
    'albums'     => countAlbums(),
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- ===== القسم الترحيبي ===== -->
<section class="hero-section">
    <div class="container">
        <h1 class="hero-title">🎵 استمع بلا حدود</h1>
        <p class="hero-subtitle">اكتشف أفضل التلاوات القرآنية، الأناشيد، والمقاطع الصوتية الإسلامية</p>

        <!-- البحث -->
        <div class="hero-search">
            <input type="text" id="hero-search-input" placeholder="ابحث عن مقطع صوتي، مؤدي، ألبوم..." autocomplete="off">
            <button class="hero-search-btn" onclick="window.location='/player.php?search='+document.getElementById('hero-search-input').value">
                <i class="fas fa-search"></i> بحث
            </button>
            <div class="search-results" id="hero-search-results"></div>
        </div>

        <!-- الإحصائيات -->
        <div class="hero-stats">
            <div class="hero-stat">
                <span class="num"><?= formatNumber($stats['audios']) ?>+</span>
                <span class="label">مقطع صوتي</span>
            </div>
            <div class="hero-stat">
                <span class="num"><?= formatNumber($stats['performers']) ?>+</span>
                <span class="label">مؤدي</span>
            </div>
            <div class="hero-stat">
                <span class="num"><?= formatNumber($stats['albums']) ?>+</span>
                <span class="label">ألبوم</span>
            </div>
            <div class="hero-stat">
                <span class="num">مجاناً</span>
                <span class="label">بالكامل</span>
            </div>
        </div>
    </div>
</section>

<!-- ===== الأقسام ===== -->
<section class="section" style="padding-top:0;">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">الأقسام</h2>
            <a href="<?= BASE_URL ?>/category.php" class="section-link">عرض الكل <i class="fas fa-chevron-left"></i></a>
        </div>
        <div class="grid grid-6">
            <?php foreach ($categories as $cat): ?>
                <a href="<?= BASE_URL ?>/category.php?slug=<?= urlencode($cat['slug']) ?>" class="audio-card" style="text-align:center; padding:20px 10px;">
                    <div style="font-size:2.5rem; margin-bottom:12px; color:var(--gold);">
                        <i class="fas <?= clean($cat['icon'] ?? 'fa-music') ?>"></i>
                    </div>
                    <div class="audio-card-title"><?= clean($cat['name']) ?></div>
                    <div class="audio-card-meta" style="justify-content:center;">
                        <span><?= formatNumber($cat['audios_count']) ?> مقطع</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!empty($featuredAudios)): ?>
<!-- ===== المميزة ===== -->
<section class="section" style="background: rgba(212,175,55,0.03); border-top:1px solid var(--border); border-bottom:1px solid var(--border);">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">✨ مميزة</h2>
        </div>
        <div class="grid grid-4">
            <?php foreach ($featuredAudios as $audio): ?>
                <?php include __DIR__ . '/includes/partials/audio_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===== أحدث المقاطع ===== -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">🆕 أحدث المقاطع</h2>
            <a href="<?= BASE_URL ?>/player.php" class="section-link">عرض الكل <i class="fas fa-chevron-left"></i></a>
        </div>
        <?php if (empty($latestAudios)): ?>
            <div class="empty-state">
                <div class="icon">🎵</div>
                <h3>لا توجد مقاطع صوتية بعد</h3>
                <p>ابدأ بإضافة مقاطع صوتية من لوحة التحكم</p>
            </div>
        <?php else: ?>
            <div class="grid grid-4">
                <?php foreach ($latestAudios as $audio): ?>
                    <div class="audio-card" onclick="playAudioCard(<?= $audio['id'] ?>, '<?= addslashes($audio['title']) ?>', '<?= addslashes($audio['performer_name']) ?>', '<?= addslashes(getImageUrl('albums', $audio['cover_image'] ?? '')) ?>', '<?= addslashes(getAudioUrl($audio['audio_file'])) ?>', '<?= addslashes($audio['duration'] ?? '') ?>')">
                        <div class="audio-card-img">
                            <img src="<?= getImageUrl('albums', $audio['cover_image'] ?? '') ?>" alt="<?= clean($audio['title']) ?>" loading="lazy">
                            <div class="audio-card-play">
                                <div class="play-btn-circle"><i class="fas fa-play"></i></div>
                            </div>
                            <?php if ($audio['is_featured']): ?>
                                <span class="audio-card-badge">مميز</span>
                            <?php endif; ?>
                        </div>
                        <div class="audio-card-body">
                            <div class="audio-card-title" title="<?= clean($audio['title']) ?>"><?= clean($audio['title']) ?></div>
                            <div class="audio-card-performer"><a href="<?= BASE_URL ?>/performer.php?slug=<?= urlencode($audio['performer_slug']) ?>" onclick="event.stopPropagation()"><?= clean($audio['performer_name']) ?></a></div>
                            <div class="audio-card-meta">
                                <span><i class="fas fa-headphones"></i> <?= formatNumber($audio['listens']) ?></span>
                                <span><i class="fas fa-download"></i> <?= formatNumber($audio['downloads']) ?></span>
                                <?php if ($audio['rating_avg'] > 0): ?>
                                    <span><i class="fas fa-star"></i> <?= number_format($audio['rating_avg'], 1) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===== المؤدون ===== -->
<?php if (!empty($latestPerformers)): ?>
<section class="section" style="background: rgba(15,52,96,0.1); border-top:1px solid var(--border); border-bottom:1px solid var(--border);">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">🎤 المؤدون</h2>
            <a href="<?= BASE_URL ?>/performer.php" class="section-link">عرض الكل <i class="fas fa-chevron-left"></i></a>
        </div>
        <div class="grid grid-4">
            <?php foreach (array_slice($latestPerformers, 0, 4) as $performer): ?>
                <a href="<?= BASE_URL ?>/performer.php?slug=<?= urlencode($performer['slug']) ?>" class="performer-card">
                    <img class="performer-avatar" src="<?= getImageUrl('performers', $performer['image'] ?? '') ?>" alt="<?= clean($performer['name']) ?>" loading="lazy">
                    <?php if ($performer['is_verified']): ?>
                        <div class="performer-verified">✓ موثّق</div>
                    <?php endif; ?>
                    <div class="performer-name"><?= clean($performer['name']) ?></div>
                    <div class="performer-meta">
                        <span><i class="fas fa-music"></i> <?= formatNumber($performer['audios_count']) ?> مقطع</span>
                        &nbsp;·&nbsp;
                        <span><i class="fas fa-users"></i> <?= formatNumber($performer['followers_count']) ?> متابع</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===== الأكثر استماعًا ===== -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">🔥 الأكثر استماعًا</h2>
            <a href="<?= BASE_URL ?>/player.php?order=popular" class="section-link">عرض الكل <i class="fas fa-chevron-left"></i></a>
        </div>

        <div class="grid grid-2" style="gap:12px;">
            <?php foreach (array_slice($popularAudios, 0, 6) as $i => $audio): ?>
                <div class="audio-card-h" onclick="playAudioCard(<?= $audio['id'] ?>, '<?= addslashes($audio['title']) ?>', '<?= addslashes($audio['performer_name']) ?>', '<?= addslashes(getImageUrl('albums', $audio['cover_image'] ?? '')) ?>', '<?= addslashes(getAudioUrl($audio['audio_file'])) ?>', '<?= addslashes($audio['duration'] ?? '') ?>')">
                    <span style="color:var(--gold);font-weight:900;font-size:1.1rem;min-width:24px;text-align:center;"><?= str_pad($i+1,2,'0',STR_PAD_LEFT) ?></span>
                    <img class="thumb" src="<?= getImageUrl('albums', $audio['cover_image'] ?? '') ?>" alt="" loading="lazy">
                    <div class="info">
                        <div class="title"><?= clean($audio['title']) ?></div>
                        <div class="meta"><?= clean($audio['performer_name']) ?> · <?= clean($audio['category_name']) ?></div>
                    </div>
                    <div class="actions">
                        <span style="font-size:0.75rem;color:var(--text-muted);"><i class="fas fa-headphones"></i> <?= formatNumber($audio['listens']) ?></span>
                        <button class="play-btn-circle" style="width:36px;height:36px;font-size:0.8rem;" onclick="event.stopPropagation();playAudioCard(<?= $audio['id'] ?>, '<?= addslashes($audio['title']) ?>', '<?= addslashes($audio['performer_name']) ?>', '<?= addslashes(getImageUrl('albums', $audio['cover_image'] ?? '')) ?>', '<?= addslashes(getAudioUrl($audio['audio_file'])) ?>', '<?= addslashes($audio['duration'] ?? '') ?>')"><i class="fas fa-play"></i></button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== الألبومات ===== -->
<?php if (!empty($latestAlbums)): ?>
<section class="section" style="background: rgba(212,175,55,0.03); border-top:1px solid var(--border);">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">💿 أحدث الألبومات</h2>
            <a href="<?= BASE_URL ?>/album.php" class="section-link">عرض الكل <i class="fas fa-chevron-left"></i></a>
        </div>
        <div class="grid grid-4">
            <?php foreach (array_slice($latestAlbums, 0, 4) as $album): ?>
                <a href="<?= BASE_URL ?>/album.php?slug=<?= urlencode($album['slug']) ?>" class="performer-card">
                    <img class="performer-avatar" src="<?= getImageUrl('albums', $album['cover_image'] ?? '') ?>" alt="<?= clean($album['title']) ?>" loading="lazy">
                    <div class="performer-name"><?= clean($album['title']) ?></div>
                    <div class="performer-meta">
                        <span><i class="fas fa-user-music"></i> <?= clean($album['performer_name']) ?></span>
                        &nbsp;·&nbsp;
                        <span><i class="fas fa-music"></i> <?= $album['audios_count'] ?> مقطع</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- آخر قسم CTA -->
<section class="section" style="text-align:center;">
    <div class="container">
        <h2 style="font-size:2rem;font-weight:900;margin-bottom:16px;">هل لديك طلب صوتي؟</h2>
        <p style="color:var(--text-muted);margin-bottom:24px;">أرسل طلبك وسنحاول إضافته في أقرب وقت</p>
        <a href="<?= BASE_URL ?>/request_audio.php" class="btn btn-gold btn-lg">
            <i class="fas fa-plus-circle"></i> اطلب مقطعًا صوتيًا
        </a>
    </div>
</section>

<script>
// دالة تشغيل بطاقة الصوت
function playAudioCard(id, title, performer, cover, url, duration) {
    if (!url) { alert('الملف الصوتي غير متاح'); return; }
    MusicanPlayer.addToPlaylist({
        id: id,
        title: title,
        performer: performer,
        cover: cover || '<?= DEFAULT_COVER_URL ?>',
        url: url,
        duration: duration
    }, true);
    // زيادة عداد الاستماع
    fetch('<?= BASE_URL ?>/api/listen.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({id: id})
    }).catch(() => {});
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
