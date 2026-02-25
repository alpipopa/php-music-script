<?php
/**
 * صفحة الملف الشخصي
 * Musican
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/templates_loader.php';
startSession();
requireLogin();

$currentUser = getCurrentUser();
if (!$currentUser) {
    setFlash('error', 'لم يتم العثور على الحساب.');
    redirect(BASE_URL . '/login.php');
}

// يمكن عرض ملف مستخدم آخر برقمه ?id=X أو username=Y
$viewUserId = (int)($_GET['id'] ?? $currentUser['id']);
$db         = getDB();

if ($viewUserId !== $currentUser['id']) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id=? AND is_active=1");
    $stmt->execute([$viewUserId]);
    $profileUser = $stmt->fetch();
    if (!$profileUser) { setFlash('error', 'المستخدم غير موجود.'); redirect(BASE_URL . '/'); }
} else {
    $profileUser = $currentUser;
}

$isOwn = ($profileUser['id'] === $currentUser['id']);

// إحصاءات المستخدم
$uid = $profileUser['id'];
$s1 = $db->prepare("SELECT COUNT(*) FROM comments WHERE user_id=?"); $s1->execute([$uid]);
$s2 = $db->prepare("SELECT COUNT(*) FROM likes WHERE user_id=?");    $s2->execute([$uid]);
$s3 = $db->prepare("SELECT COUNT(*) FROM follows WHERE follower_id=?"); $s3->execute([$uid]);
$stats = [
    'comments' => (int)$s1->fetchColumn(),
    'likes'    => (int)$s2->fetchColumn(),
    'follows'  => (int)$s3->fetchColumn(),
];

// آخر تعليقات المستخدم
$recentComments = $db->prepare("SELECT c.*, a.title as audio_title, a.slug as audio_slug FROM comments c JOIN audios a ON c.audio_id=a.id WHERE c.user_id=? AND c.status='visible' ORDER BY c.created_at DESC LIMIT 5");
$recentComments->execute([$profileUser['id']]);
$recentComments = $recentComments->fetchAll();

// المؤدون المتابَعون
$followedPerformers = $db->prepare("SELECT p.name, p.slug, p.image FROM follows f JOIN performers p ON f.performer_id=p.id WHERE f.follower_id=? LIMIT 8");
$followedPerformers->execute([$profileUser['id']]);
$followedPerformers = $followedPerformers->fetchAll();

$displayName = !empty($profileUser['full_name']) ? $profileUser['full_name'] : $profileUser['username'];
$pageTitle   = 'الملف الشخصي - ' . $displayName;
$siteName   = getSetting('site_name', 'موسيكان');

$avatarUrl = getUserAvatarUrl($profileUser);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) . ' | ' . clean($siteName) ?></title>
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <style>
        /* ===== Profile Page Styles ===== */
        .profile-hero {
            background: linear-gradient(135deg, rgba(20,20,40,.95) 0%, rgba(13,13,26,.95) 100%);
            border-bottom: 1px solid rgba(212,175,55,.2);
            padding: 48px 0 0;
            position: relative;
            overflow: hidden;
        }
        .profile-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 30% 50%, rgba(212,175,55,.07) 0%, transparent 60%);
        }
        .profile-hero-inner {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 24px;
            position: relative;
        }
        .profile-top {
            display: flex;
            align-items: flex-end;
            gap: 28px;
            padding-bottom: 28px;
        }
        .profile-avatar-wrap {
            position: relative;
            flex-shrink: 0;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid var(--gold);
            object-fit: cover;
            box-shadow: 0 0 30px rgba(212,175,55,.3);
            display: block;
        }
        .profile-verified {
            position: absolute;
            bottom: 6px;
            left: 6px;
            background: var(--gold);
            color: #0d0d1a;
            border-radius: 50%;
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            border: 2px solid #0d0d1a;
        }
        .profile-info { flex: 1; padding-bottom: 4px; }
        .profile-name {
            font-size: 1.8rem;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 4px;
        }
        .profile-role {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 12px;
            border-radius: 50px;
            font-size: 0.78rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .role-admin    { background: rgba(231,76,60,.15); color: #e74c3c; }
        .role-performer{ background: rgba(212,175,55,.15); color: var(--gold); }
        .role-user     { background: rgba(52,152,219,.15); color: #3498db; }
        .profile-bio   { color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; max-width: 600px; }
        .profile-meta  { display: flex; gap: 20px; margin-top: 10px; flex-wrap: wrap; }
        .profile-meta-item { display: flex; align-items: center; gap: 6px; font-size: 0.82rem; color: var(--text-muted); }
        .profile-actions { display: flex; gap: 10px; align-items: flex-end; flex-shrink: 0; padding-bottom: 4px; }

        .profile-stats {
            display: flex;
            border-top: 1px solid rgba(212,175,55,.12);
        }
        .profile-stat {
            flex: 1;
            text-align: center;
            padding: 18px 10px;
            border-left: 1px solid rgba(212,175,55,.12);
            transition: background .2s;
        }
        .profile-stat:last-child { border-left: none; }
        .profile-stat:hover { background: rgba(212,175,55,.04); }
        .profile-stat-num { font-size: 1.6rem; font-weight: 900; color: var(--gold); }
        .profile-stat-lbl { font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; }

        .profile-body {
            max-width: 900px;
            margin: 0 auto;
            padding: 28px 24px;
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 24px;
        }
        @media (max-width: 700px) {
            .profile-body { grid-template-columns: 1fr; }
            .profile-top { flex-direction: column; align-items: flex-start; }
            .profile-avatar { width: 90px; height: 90px; }
        }

        .section-card {
            background: rgba(20,25,50,.7);
            border: 1px solid rgba(212,175,55,.15);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .section-card-header {
            padding: 14px 18px;
            border-bottom: 1px solid rgba(212,175,55,.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .section-card-title { font-size: 0.9rem; font-weight: 700; color: var(--gold); display: flex; align-items: center; gap: 8px; }
        .section-card-body { padding: 16px 18px; }

        .comment-item {
            padding: 12px 0;
            border-bottom: 1px solid rgba(212,175,55,.07);
        }
        .comment-item:last-child { border-bottom: none; }
        .comment-audio { font-size: 0.8rem; color: var(--gold); margin-bottom: 4px; }
        .comment-text  { font-size: 0.88rem; color: var(--text-muted); line-height: 1.5; }
        .comment-time  { font-size: 0.75rem; color: rgba(255,255,255,.3); margin-top: 4px; }

        .followed-performer {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(212,175,55,.07);
            transition: all .2s;
        }
        .followed-performer:last-child { border-bottom: none; }
        .followed-performer:hover { padding-right: 6px; }
        .followed-performer img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid rgba(212,175,55,.3);
        }
        .followed-performer-name { font-size: 0.88rem; font-weight: 600; }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<!-- ===== Profile Hero ===== -->
<div class="profile-hero">
    <div class="profile-hero-inner">
        <div class="profile-top">
            <!-- الصورة الشخصية -->
            <div class="profile-avatar-wrap">
                <img src="<?= clean($avatarUrl) ?>" alt="<?= clean($profileUser['username']) ?>"
                     class="profile-avatar" id="main-avatar"
                     onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($profileUser['username']) ?>&background=d4af37&color=0d0d1a&size=200'">
                <?php if ($profileUser['role'] === 'admin'): ?>
                    <div class="profile-verified" title="مدير"><i class="fas fa-shield-alt"></i></div>
                <?php endif; ?>
            </div>

            <!-- البيانات -->
            <div class="profile-info">
                <h1 class="profile-name"><?= clean($displayName) ?></h1>
                <?php if (!empty($profileUser['full_name'])): ?>
                    <div style="color:var(--text-muted); font-size: 0.95rem; margin-top: -5px; margin-bottom: 8px;">@<?= clean($profileUser['username']) ?></div>
                <?php endif; ?>
                <span class="profile-role role-<?= $profileUser['role'] ?>">
                    <?php
                        $roleLabels = ['admin' => '🛡️ مدير', 'performer' => '🎤 مؤدي', 'user' => '👤 مستخدم'];
                        echo $roleLabels[$profileUser['role']] ?? 'مستخدم';
                    ?>
                </span>
                <?php if ($profileUser['bio']): ?>
                    <p class="profile-bio"><?= nl2br(clean($profileUser['bio'])) ?></p>
                <?php endif; ?>
                <div class="profile-meta">
                    <span class="profile-meta-item">
                        <i class="fas fa-calendar-alt" style="color:var(--gold);"></i>
                        عضو منذ <?= formatArabicDate($profileUser['created_at']) ?>
                    </span>
                    <?php if ($profileUser['website']): ?>
                        <a href="<?= clean($profileUser['website']) ?>" target="_blank" rel="nofollow" class="profile-meta-item" style="color:var(--gold);">
                            <i class="fas fa-globe"></i> <?= clean(parse_url($profileUser['website'], PHP_URL_HOST) ?: $profileUser['website']) ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($profileUser['phone'] && $isOwn): ?>
                        <span class="profile-meta-item">
                            <i class="fas fa-phone"></i> <?= clean($profileUser['phone']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- أزرار إجراءات -->
            <?php if ($isOwn): ?>
                <div class="profile-actions">
                    <a href="<?= BASE_URL ?>/edit_profile.php" class="btn btn-gold">
                        <i class="fas fa-user-edit"></i> تعديل الملف
                    </a>
                </div>
            <?php endif; ?>
        </div><!-- /profile-top -->

        <!-- إحصاءات -->
        <div class="profile-stats">
            <div class="profile-stat">
                <div class="profile-stat-num"><?= formatNumber($stats['comments']) ?></div>
                <div class="profile-stat-lbl">تعليق</div>
            </div>
            <div class="profile-stat">
                <div class="profile-stat-num"><?= formatNumber($stats['likes']) ?></div>
                <div class="profile-stat-lbl">إعجاب</div>
            </div>
            <div class="profile-stat">
                <div class="profile-stat-num"><?= formatNumber($stats['follows']) ?></div>
                <div class="profile-stat-lbl">متابَع</div>
            </div>
        </div>
    </div>
</div><!-- /profile-hero -->

<!-- ===== Profile Body ===== -->
<div class="profile-body">
    <!-- العمود الرئيسي -->
    <div>
        <!-- آخر التعليقات -->
        <div class="section-card">
            <div class="section-card-header">
                <span class="section-card-title"><i class="fas fa-comments"></i> آخر التعليقات</span>
                <span style="font-size:0.8rem;color:var(--text-muted);"><?= $stats['comments'] ?> تعليق</span>
            </div>
            <div class="section-card-body">
                <?php if (empty($recentComments)): ?>
                    <p style="color:var(--text-muted);text-align:center;padding:20px 0;">لا توجد تعليقات بعد</p>
                <?php else: ?>
                    <?php foreach ($recentComments as $c): ?>
                        <div class="comment-item">
                            <div class="comment-audio">
                                <i class="fas fa-music"></i>
                                <a href="<?= BASE_URL ?>/audio.php?slug=<?= urlencode($c['audio_slug']) ?>" style="color:var(--gold);">
                                    <?= clean($c['audio_title']) ?>
                                </a>
                            </div>
                            <div class="comment-text"><?= clean(mb_substr($c['comment'], 0, 150)) ?><?= mb_strlen($c['comment']) > 150 ? '...' : '' ?></div>
                            <div class="comment-time"><?= timeAgo($c['created_at']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- الشريط الجانبي -->
    <div>
        <!-- المؤدون المتابَعون -->
        <div class="section-card">
            <div class="section-card-header">
                <span class="section-card-title"><i class="fas fa-heart"></i> المتابَعون</span>
                <span style="font-size:0.8rem;color:var(--text-muted);"><?= $stats['follows'] ?></span>
            </div>
            <div class="section-card-body">
                <?php if (empty($followedPerformers)): ?>
                    <p style="color:var(--text-muted);text-align:center;padding:16px 0;font-size:0.85rem;">لا يتابع أحداً بعد</p>
                <?php else: ?>
                    <?php foreach ($followedPerformers as $p): ?>
                        <a href="<?= BASE_URL ?>/performer.php?slug=<?= urlencode($p['slug']) ?>" class="followed-performer">
                            <img src="<?= getImageUrl('performers', $p['image']) ?>"
                                 alt="<?= clean($p['name']) ?>"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($p['name']) ?>&background=d4af37&color=0d0d1a&size=80'">
                            <span class="followed-performer-name"><?= clean($p['name']) ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isOwn): ?>
        <!-- روابط سريعة -->
        <div class="section-card">
            <div class="section-card-header">
                <span class="section-card-title"><i class="fas fa-bolt"></i> الإعدادات</span>
            </div>
            <div class="section-card-body" style="padding:8px 0;">
                <a href="<?= BASE_URL ?>/edit_profile.php" style="display:flex;align-items:center;gap:10px;padding:11px 18px;color:var(--text-muted);transition:all .2s;" onmouseover="this.style.color='var(--gold)';this.style.paddingRight='24px';" onmouseout="this.style.color='var(--text-muted)';this.style.paddingRight='18px';">
                    <i class="fas fa-user-edit" style="width:16px;"></i> تعديل الملف الشخصي
                </a>
                <a href="<?= BASE_URL ?>/edit_profile.php?tab=password" style="display:flex;align-items:center;gap:10px;padding:11px 18px;color:var(--text-muted);transition:all .2s;" onmouseover="this.style.color='var(--gold)';this.style.paddingRight='24px';" onmouseout="this.style.color='var(--text-muted)';this.style.paddingRight='18px';">
                    <i class="fas fa-key" style="width:16px;"></i> تغيير كلمة المرور
                </a>
                <?php if (isAdmin()): ?>
                <a href="<?= BASE_URL ?>/admin/" style="display:flex;align-items:center;gap:10px;padding:11px 18px;color:var(--text-muted);transition:all .2s;" onmouseover="this.style.color='var(--gold)';this.style.paddingRight='24px';" onmouseout="this.style.color='var(--text-muted)';this.style.paddingRight='18px';">
                    <i class="fas fa-tachometer-alt" style="width:16px;"></i> لوحة التحكم
                </a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/logout.php" style="display:flex;align-items:center;gap:10px;padding:11px 18px;color:#e74c3c;transition:all .2s;" onmouseover="this.style.paddingRight='24px';" onmouseout="this.style.paddingRight='18px';">
                    <i class="fas fa-sign-out-alt" style="width:16px;"></i> تسجيل الخروج
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div><!-- /profile-body -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
