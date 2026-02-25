<?php
/**
 * صفحة تفاصيل المقطع الصوتي
 * Musican
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/templates_loader.php';
startSession();

$slug = clean($_GET['slug'] ?? '');
if (!$slug) { header('Location: ' . BASE_URL . '/player.php'); exit; }

$audio = getAudioBySlug($slug);
if (!$audio) { header('HTTP/1.0 404 Not Found'); echo '<h1>404 - غير موجود</h1>'; exit; }

// زيادة عداد الاستماع
incrementListens($audio['id']);

$pageTitle  = $audio['title'];
$activePage = 'player';

$currentUser = getCurrentUser();
$liked       = $currentUser ? isLiked($audio['id'], $currentUser['id']) : false;
$userRating  = $currentUser ? getUserRating($audio['id'], $currentUser['id']) : 0;
$comments    = getComments($audio['id']);
$relatedAudios = getAudios(['category_id' => $audio['category_id'], 'limit' => 6]);
$relatedAudios = array_filter($relatedAudios, fn($a) => $a['id'] !== $audio['id']);

// معالجة التعليق
$commentSuccess = false;
$commentError   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    checkCsrf();
    if (!$currentUser) {
        $commentError = 'يجب تسجيل الدخول للتعليق.';
    } elseif (getSetting('allow_comments', '1') !== '1') {
        $commentError = 'التعليقات معطّلة حاليًا.';
    } else {
        $commentText = clean($_POST['comment'] ?? '');
        if (strlen($commentText) < 3) {
            $commentError = 'التعليق قصير جدًا.';
        } else {
            if (addComment($audio['id'], $currentUser['id'], $commentText)) {
                $commentSuccess = true;
                $comments = getComments($audio['id']);
            } else {
                $commentError = 'حدث خطأ أثناء إضافة التعليق.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- رأس المقطع -->
<div class="audio-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>/">الرئيسية</a><span class="sep">/</span>
            <a href="<?= BASE_URL ?>/player.php">الصوتيات</a><span class="sep">/</span>
            <a href="<?= BASE_URL ?>/category.php?slug=<?= urlencode($audio['category_slug']) ?>"><?= clean($audio['category_name']) ?></a>
            <span class="sep">/</span><span class="current"><?= clean($audio['title']) ?></span>
        </div>
        <div class="audio-hero-inner">
            <!-- الغلاف -->
            <div class="audio-cover">
                <img src="<?= getImageUrl('albums', $audio['cover_image'] ?? '') ?>" alt="<?= clean($audio['title']) ?>">
            </div>
            <!-- المعلومات -->
            <div class="audio-info">
                <span class="audio-category"><?= clean($audio['category_name']) ?></span>
                <h1 class="audio-title"><?= clean($audio['title']) ?></h1>
                <p class="audio-performer-name">المؤدي: <a href="<?= BASE_URL ?>/performer.php?slug=<?= urlencode($audio['performer_slug']) ?>"><?= clean($audio['performer_name']) ?></a></p>
                <?php if ($audio['album_title']): ?>
                    <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:12px;">الألبوم: <a href="<?= BASE_URL ?>/album.php?slug=<?= urlencode($audio['album_slug']) ?>" style="color:var(--gold);"><?= clean($audio['album_title']) ?></a></p>
                <?php endif; ?>

                <div class="audio-stats">
                    <div class="audio-stat"><i class="fas fa-headphones" style="color:var(--gold)"></i> <span class="num"><?= formatNumber($audio['listens']) ?></span> استماع</div>
                    <div class="audio-stat"><i class="fas fa-download" style="color:var(--gold)"></i> <span class="num"><?= formatNumber($audio['downloads']) ?></span> تنزيل</div>
                    <div class="audio-stat"><i class="fas fa-heart" style="color:#e74c3c"></i> <span class="num" id="likes-count"><?= formatNumber($audio['likes_count']) ?></span> إعجاب</div>
                    <?php if ((float)$audio['rating_avg'] > 0): ?>
                        <div class="audio-stat"><i class="fas fa-star" style="color:var(--gold)"></i> <?= number_format($audio['rating_avg'],1) ?> (<?= $audio['rating_count'] ?>)</div>
                    <?php endif; ?>
                    <?php if ($audio['duration']): ?>
                        <div class="audio-stat"><i class="fas fa-clock" style="color:var(--gold)"></i> <?= clean($audio['duration']) ?></div>
                    <?php endif; ?>
                    <div class="audio-stat"><?= formatArabicDate($audio['created_at']) ?></div>
                </div>

                <div class="audio-actions">
                    <!-- زر التشغيل -->
                    <button class="btn btn-gold btn-lg" onclick="playThisAudio()">
                        <i class="fas fa-play"></i> تشغيل
                    </button>

                    <!-- الإعجاب -->
                    <?php if ($currentUser): ?>
                        <button class="btn <?= $liked ? 'btn-danger' : 'btn-ghost' ?>" id="like-btn" onclick="toggleLikeAudio()">
                            <i class="fas fa-heart"></i> <?= $liked ? 'أعجبني' : 'إعجاب' ?>
                        </button>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/login.php" class="btn btn-ghost"><i class="fas fa-heart"></i> إعجاب</a>
                    <?php endif; ?>

                    <!-- التنزيل -->
                    <?php if (getSetting('allow_download','1')==='1' && $audio['allow_download']): ?>
                        <a href="<?= BASE_URL ?>/download.php?audio=<?= $audio['id'] ?>" class="btn btn-outline">
                            <i class="fas fa-download"></i> تنزيل
                        </a>
                    <?php endif; ?>

                    <!-- المشاركة -->
                    <button class="btn btn-ghost" onclick="shareAudio()"><i class="fas fa-share-alt"></i> مشاركة</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="section">
    <div class="container">
        <div class="grid grid-3" style="gap:30px;align-items:flex-start;">

            <!-- المحتوى الرئيسي -->
            <div style="grid-column:span 2;">

                <!-- الوصف -->
                <?php if ($audio['description']): ?>
                <div class="card" style="margin-bottom:24px;">
                    <div class="card-header"><h2 class="card-title-sm"><i class="fas fa-info-circle"></i> الوصف</h2></div>
                    <div class="card-body">
                        <p style="color:var(--text-muted);line-height:2;"><?= nl2br(clean($audio['description'])) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- الكلمات -->
                <?php if ($audio['lyrics']): ?>
                <div class="card" style="margin-bottom:24px;">
                    <div class="card-header"><h2 class="card-title-sm"><i class="fas fa-file-alt"></i> النص / الكلمات</h2></div>
                    <div class="card-body">
                        <pre style="color:var(--text-muted);line-height:2;white-space:pre-wrap;font-family:inherit;"><?= clean($audio['lyrics']) ?></pre>
                    </div>
                </div>
                <?php endif; ?>

                <!-- التقييم -->
                <?php if ($currentUser): ?>
                <div class="card" style="margin-bottom:24px;">
                    <div class="card-header"><h2 class="card-title-sm"><i class="fas fa-star"></i> تقييم المقطع</h2></div>
                    <div class="card-body">
                        <div class="star-rating" id="star-rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <label title="<?= $i ?> نجوم" onclick="rateAudio(<?= $i ?>)" style="color:<?= $i <= $userRating ? 'var(--gold)' : 'var(--text-muted)' ?>; cursor:pointer; font-size:1.6rem;">★</label>
                            <?php endfor; ?>
                        </div>
                        <p style="color:var(--text-muted);font-size:0.85rem;margin-top:8px;">
                            متوسط التقييم: <strong style="color:var(--gold);"><?= number_format($audio['rating_avg'],1) ?></strong> من 5 (<?= $audio['rating_count'] ?> تقييم)
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- التعليقات -->
                <?php if (getSetting('allow_comments', '1') === '1'): ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title-sm"><i class="fas fa-comments"></i> التعليقات (<?= count($comments) ?>)</h2>
                    </div>
                    <div class="card-body">
                        <!-- نموذج التعليق -->
                        <?php if ($currentUser): ?>
                            <?php if ($commentSuccess): ?>
                                <div class="alert alert-success">✅ تم إضافة تعليقك بنجاح!</div>
                            <?php endif; ?>
                            <?php if ($commentError): ?>
                                <div class="alert alert-error">❌ <?= clean($commentError) ?></div>
                            <?php endif; ?>
                            <form method="post" action="" style="margin-bottom:24px;">
                                <?= csrfField() ?>
                                <textarea name="comment" class="form-control" rows="3" placeholder="أضف تعليقك..." required style="margin-bottom:10px;"></textarea>
                                <button type="submit" name="add_comment" class="btn btn-gold btn-sm">
                                    <i class="fas fa-paper-plane"></i> إرسال التعليق
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <a href="<?= BASE_URL ?>/login.php">سجّل دخولك</a> لإضافة تعليق.
                            </div>
                        <?php endif; ?>

                        <!-- قائمة التعليقات -->
                        <?php if (empty($comments)): ?>
                            <p style="color:var(--text-muted);text-align:center;padding:20px;">لا توجد تعليقات بعد. كن أول من يعلّق!</p>
                        <?php else: ?>
                            <div style="display:flex;flex-direction:column;gap:16px;">
                                <?php foreach ($comments as $comment): ?>
                                    <div style="display:flex;gap:12px;align-items:flex-start;">
                                        <img src="<?= $comment['avatar'] ? getImageUrl('avatars', $comment['avatar']) : BASE_URL . '/assets/images/default-avatar.jpg' ?>" alt="" style="width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid var(--border);">
                                        <div style="flex:1;background:var(--input-bg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px;">
                                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                                                <strong style="color:var(--gold);"><?= clean($comment['username']) ?></strong>
                                                <span style="color:var(--text-muted);font-size:0.75rem;"><?= timeAgo($comment['created_at']) ?></span>
                                            </div>
                                            <p style="color:var(--text-dark);line-height:1.6;"><?= nl2br(clean($comment['comment'])) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- الجانب -->
            <div>
                <!-- معلومات المؤدي -->
                <div class="card" style="margin-bottom:20px;">
                    <div class="card-header"><h3 class="card-title-sm"><i class="fas fa-microphone"></i> المؤدي</h3></div>
                    <div class="card-body" style="text-align:center;">
                        <img src="<?= getImageUrl('performers', $audio['performer_image'] ?? '') ?>" alt="" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--border);margin-bottom:12px;">
                        <h3 style="font-size:1rem;font-weight:700;margin-bottom:8px;"><?= clean($audio['performer_name']) ?></h3>
                        <?php if ($audio['performer_bio']): ?>
                            <p style="color:var(--text-muted);font-size:0.85rem;margin-bottom:12px;"><?= mb_substr(clean($audio['performer_bio']), 0, 100) ?>...</p>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/performer.php?slug=<?= urlencode($audio['performer_slug']) ?>" class="btn btn-gold btn-sm btn-block">
                            عرض صفحة المؤدي
                        </a>
                    </div>
                </div>

                <!-- مقاطع مشابهة -->
                <?php if (!empty($relatedAudios)): ?>
                <div class="card">
                    <div class="card-header"><h3 class="card-title-sm"><i class="fas fa-music"></i> مقاطع مشابهة</h3></div>
                    <div class="card-body" style="padding:12px;">
                        <?php foreach (array_slice($relatedAudios, 0, 5) as $related): ?>
                            <div class="audio-card-h" onclick="playAudioCard(<?= $related['id'] ?>, '<?= addslashes($related['title']) ?>', '<?= addslashes($related['performer_name']) ?>', '<?= addslashes(getImageUrl('albums', $related['cover_image'] ?? '')) ?>', '<?= addslashes(getAudioUrl($related['audio_file'])) ?>', '<?= addslashes($related['duration'] ?? '') ?>')" style="padding:8px;border-radius:8px;margin-bottom:6px;">
                                <img class="thumb" src="<?= getImageUrl('albums', $related['cover_image'] ?? '') ?>" alt="" loading="lazy" style="width:44px;height:44px;">
                                <div class="info">
                                    <div class="title" style="font-size:0.82rem;"><?= clean($related['title']) ?></div>
                                    <div class="meta"><?= clean($related['performer_name']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script>
const audioData = {
    id: <?= $audio['id'] ?>,
    title: '<?= addslashes($audio['title']) ?>',
    performer: '<?= addslashes($audio['performer_name']) ?>',
    cover: '<?= addslashes(getImageUrl('albums', $audio['cover_image'] ?? '')) ?>',
    url: '<?= addslashes(getAudioUrl($audio['audio_file'])) ?>',
    duration: '<?= addslashes($audio['duration'] ?? '') ?>'
};

const csrfToken = document.querySelector('meta[name="csrf"]')?.content;

function playThisAudio() {
    if (!audioData.url) { alert('الملف الصوتي غير متاح'); return; }
    MusicanPlayer.addToPlaylist(audioData, true);
}

// تلقائياً أضفه للقائمة
window.addEventListener('load', () => {
    if (audioData.url) MusicanPlayer.addToPlaylist(audioData, false);
});

function playAudioCard(id, title, performer, cover, url, duration) {
    if (!url) return;
    MusicanPlayer.addToPlaylist({ id, title, performer, cover: cover || '<?= DEFAULT_COVER_URL ?>', url, duration }, true);
}

function toggleLikeAudio() {
    fetch('<?= BASE_URL ?>/api/like.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ audio_id: <?= $audio['id'] ?>, csrf: csrfToken })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const btn = document.getElementById('like-btn');
            btn.className = data.liked ? 'btn btn-danger' : 'btn btn-ghost';
            btn.innerHTML = `<i class="fas fa-heart"></i> ${data.liked ? 'أعجبني' : 'إعجاب'}`;
            document.getElementById('likes-count').textContent = data.count;
            MusicanPlayer.showToast(data.liked ? '❤️ تم الإعجاب' : 'تم إلغاء الإعجاب', 'success');
        }
    }).catch(() => {});
}

function rateAudio(rating) {
    fetch('<?= BASE_URL ?>/api/rate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ audio_id: <?= $audio['id'] ?>, rating, csrf: csrfToken })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            MusicanPlayer.showToast('⭐ تم التقييم بنجاح', 'success');
            const labels = document.querySelectorAll('#star-rating label');
            labels.forEach((l, i) => l.style.color = (5 - i) <= rating ? 'var(--gold)' : 'var(--text-muted)');
        }
    }).catch(() => {});
}

function shareAudio() {
    const url = window.location.href;
    if (navigator.share) {
        navigator.share({ title: '<?= addslashes($audio['title']) ?>', url });
    } else if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => MusicanPlayer.showToast('✅ تم نسخ الرابط', 'success'));
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
