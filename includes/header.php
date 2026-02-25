<?php
/**
 * ترويسة الموقع - Header
 * Musican - منصة الصوتيات الاحترافية
 */

if (!defined('MUSICAN_APP')) die('Access Denied');

$siteName     = getSetting('site_name', 'موسيكان');
$logoFile     = getSetting('logo', '');
$metaDesc     = getSetting('meta_description', 'منصة موسيكان للصوتيات العربية الاحترافية');
$metaKeywords = getSetting('meta_keywords', 'صوتيات، تلاوة، أناشيد');
$pageTitle    = ($pageTitle ?? '') ? ($pageTitle . ' | ' . $siteName) : $siteName;
$categories   = getCategories();
$currentUser  = getCurrentUser();
$unreadNotif  = $currentUser ? countUnreadNotifications($currentUser['id']) : 0;
$unreadMsgs   = $currentUser ? countUnreadMessages($currentUser['id']) : 0;
$csrfToken    = generateCsrfToken();
$logoUrl      = $logoFile ? getImageUrl('site', $logoFile) : '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= clean($metaDesc) ?>">
    <meta name="keywords" content="<?= clean($metaKeywords) ?>">
    <meta name="csrf" content="<?= $csrfToken ?>">
    <title><?= clean($pageTitle) ?></title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- الأنماط الرئيسية -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <?= loadTemplateStyle() ?>

    <?php if (!empty($extraCss)): ?>
    <style><?= $extraCss ?></style>
    <?php endif; ?>

    <script>
        window.MusicanConfig = {
            baseUrl: '<?= BASE_URL ?>',
            defaultCover: '<?= DEFAULT_COVER_URL ?>'
        };
    </script>
</head>
<body>

<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>

<!-- ترويسة الموقع -->
<header class="site-header" id="site-header">
    <div class="container">
        <div class="header-inner">

            <!-- الشعار -->
            <a href="<?= BASE_URL ?>/" class="site-logo">
                <?php if ($logoUrl): ?>
                    <img src="<?= $logoUrl ?>" alt="<?= clean($siteName) ?>" style="height:44px;">
                <?php else: ?>
                    <div class="logo-icon">🎵</div>
                    <span class="logo-text"><?= clean($siteName) ?></span>
                <?php endif; ?>
            </a>

            <!-- التنقل الرئيسي -->
            <nav class="site-nav" id="site-nav">
                <ul class="nav-links">
                    <li><a href="<?= BASE_URL ?>/" class="<?= (($activePage??'') === 'home') ? 'active' : '' ?>"><i class="fas fa-home"></i> الرئيسية</a></li>
                    <li><a href="<?= BASE_URL ?>/player.php" class="<?= (($activePage??'') === 'player') ? 'active' : '' ?>"><i class="fas fa-headphones"></i> الصوتيات</a></li>
                    <li><a href="<?= BASE_URL ?>/performer.php" class="<?= (($activePage??'') === 'performers') ? 'active' : '' ?>"><i class="fas fa-microphone"></i> المؤدون</a></li>
                    <li><a href="<?= BASE_URL ?>/album.php" class="<?= (($activePage??'') === 'albums') ? 'active' : '' ?>"><i class="fas fa-compact-disc"></i> الألبومات</a></li>
                    <li><a href="<?= BASE_URL ?>/category.php" class="<?= (($activePage??'') === 'categories') ? 'active' : '' ?>"><i class="fas fa-list"></i> الأقسام</a></li>
                    <li><a href="<?= BASE_URL ?>/contact.php" class="<?= (($activePage??'') === 'contact') ? 'active' : '' ?>"><i class="fas fa-envelope"></i> تواصل</a></li>
                </ul>
            </nav>

            <!-- البحث -->
            <div class="header-search">
                <input type="text" id="header-search-input" placeholder="ابحث عن صوتيات، مؤدين..." autocomplete="off">
                <i class="fas fa-search search-icon"></i>
                <div class="search-results" id="header-search-results"></div>
            </div>

            <!-- إجراءات الهيدر -->
            <div class="header-actions">
                <?php if ($currentUser): ?>
                    <!-- الرسائل -->
                    <a href="<?= BASE_URL ?>/messages.php" class="btn-icon" title="الرسائل">
                        <i class="fas fa-envelope"></i>
                        <?php if ($unreadMsgs > 0): ?>
                            <span class="badge" style="background:#e67e22;"><?= $unreadMsgs ?></span>
                        <?php endif; ?>
                    </a>

                    <!-- الإشعارات -->
                    <a href="<?= BASE_URL ?>/notifications.php" class="btn-icon" title="الإشعارات">
                        <i class="fas fa-bell"></i>
                        <?php if ($unreadNotif > 0): ?>
                            <span class="badge" id="unread-notif-count"><?= $unreadNotif ?></span>
                        <?php endif; ?>
                    </a>

                    <!-- قائمة المستخدم -->
                    <div class="user-menu">
                        <?php $displayName = !empty($currentUser['full_name']) ? $currentUser['full_name'] : $currentUser['username']; ?>
                        <div class="user-avatar-btn" id="user-menu-btn" title="<?= clean($displayName) ?>">
                            <?php
                                $__avatar = $currentUser['avatar']
                                    ? getImageUrl('avatars', $currentUser['avatar'])
                                    : 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($currentUser['email'] ?? ''))) . '?s=80&d=identicon';
                            ?>
                            <img src="<?= $__avatar ?>" alt="<?= clean($displayName) ?>"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($displayName) ?>&background=d4af37&color=0d0d1a&size=80'">
                        </div>
                        <div class="user-dropdown" id="user-dropdown">
                            <a href="#"><i class="fas fa-user"></i> <?= clean($displayName) ?></a>
                            <a href="<?= BASE_URL ?>/profile.php"><i class="fas fa-user-circle"></i> ملفي الشخصي</a>
                            <a href="<?= BASE_URL ?>/edit_profile.php"><i class="fas fa-cog"></i> إعدادات الحساب</a>
                            <?php if (isAdmin()): ?>
                                <a href="<?= BASE_URL ?>/admin/" style="background:rgba(212,175,55,0.1); color:var(--gold); font-weight:bold;"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a>
                            <?php elseif (isPerformer()): ?>
                                <a href="<?= BASE_URL ?>/performer-panel.php"><i class="fas fa-music"></i> لوحتي</a>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/logout.php" style="color:#e74c3c;"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline btn-sm">دخول</a>
                    <a href="<?= BASE_URL ?>/register.php" class="btn btn-gold btn-sm">تسجيل</a>
                <?php endif; ?>

                <!-- زر الموبايل -->
                <button class="mobile-menu-btn" id="mobile-menu-btn" aria-label="القائمة">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>

        </div>
    </div>
</header>
<!-- نهاية الهيدر -->

<!-- محتوى يُقحم هنا -->
<?= showFlash() ?>
