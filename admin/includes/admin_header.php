<?php
/**
 * ترويسة لوحة التحكم
 * Musican Admin
 */

if (!defined('MUSICAN_APP')) die('Access Denied');

$adminSiteName  = getSetting('site_name', 'موسيكان');
$adminUser      = getCurrentUser();
$unreadMessages = getDB()->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();
$pendingRequests = getDB()->query("SELECT COUNT(*) FROM audio_requests WHERE status = 'pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle ?? 'لوحة التحكم') ?> | مدير - <?= clean($adminSiteName) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin-extra.css">
</head>
<body>

<!-- الشريط الجانبي -->
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-logo">
        <a href="<?= BASE_URL ?>/admin/dashboard.php">🎵 <?= clean($adminSiteName) ?></a>
        <button class="sidebar-close" id="sidebar-close"><i class="fas fa-times"></i></button>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-section-title">الرئيسية</div>
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="sidebar-item <?= (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i> لوحة التحكم
        </a>

        <div class="sidebar-section-title">المحتوى</div>
        <a href="<?= BASE_URL ?>/admin/audios.php" class="sidebar-item <?= str_contains($_SERVER['PHP_SELF'], 'audios') ? 'active' : '' ?>">
            <i class="fas fa-headphones"></i> الصوتيات
        </a>
        <a href="<?= BASE_URL ?>/admin/performers.php" class="sidebar-item <?= str_contains($_SERVER['PHP_SELF'], 'performers') ? 'active' : '' ?>">
            <i class="fas fa-microphone"></i> المؤدون
        </a>
        <a href="<?= BASE_URL ?>/admin/albums.php" class="sidebar-item <?= str_contains($_SERVER['PHP_SELF'], 'albums') ? 'active' : '' ?>">
            <i class="fas fa-compact-disc"></i> الألبومات
        </a>
        <a href="<?= BASE_URL ?>/admin/categories.php" class="sidebar-item <?= str_contains($_SERVER['PHP_SELF'], 'categories') ? 'active' : '' ?>">
            <i class="fas fa-list"></i> الأقسام
        </a>

        <div class="sidebar-section-title">المستخدمون</div>
        <a href="<?= BASE_URL ?>/admin/users.php" class="sidebar-item <?= str_contains($_SERVER['PHP_SELF'], 'users') ? 'active' : '' ?>">
            <i class="fas fa-users"></i> المستخدمون
        </a>
        <a href="<?= BASE_URL ?>/admin/comments.php" class="sidebar-item <?= str_contains($_SERVER['PHP_SELF'], 'comments') ? 'active' : '' ?>">
            <i class="fas fa-comments"></i> التعليقات
        </a>

        <div class="sidebar-section-title">النظام</div>
        <a href="<?= BASE_URL ?>/admin/messages.php" class="sidebar-item <?= str_contains($_SERVER['PHP_SELF'], 'messages') ? 'active' : '' ?>">
            <i class="fas fa-envelope"></i> الرسائل
            <?php if ($unreadMessages > 0): ?><span class="sidebar-badge"><?= $unreadMessages ?></span><?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/admin/requests.php" class="sidebar-item <?= str_contains($_SERVER['PHP_SELF'], 'requests') ? 'active' : '' ?>">
            <i class="fas fa-plus-circle"></i> طلبات المقاطع
            <?php if ($pendingRequests > 0): ?><span class="sidebar-badge"><?= $pendingRequests ?></span><?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/admin/settings.php" class="sidebar-item <?= str_contains($_SERVER['PHP_SELF'], 'settings') ? 'active' : '' ?>">
            <i class="fas fa-cog"></i> الإعدادات
        </a>

        <div class="sidebar-footer">
            <a href="<?= BASE_URL ?>/" target="_blank" class="sidebar-item">
                <i class="fas fa-external-link-alt"></i> معاينة الموقع
            </a>
            <a href="<?= BASE_URL ?>/logout.php" class="sidebar-item" style="color:#e74c3c;">
                <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
            </a>
        </div>
    </nav>
</aside>

<!-- الهيدر العلوي -->
<div class="admin-main">
    <header class="admin-header">
        <div class="admin-header-start">
            <button class="btn-icon" id="sidebar-toggle"><i class="fas fa-bars"></i></button>
            <div class="admin-breadcrumb">
                <a href="<?= BASE_URL ?>/admin/dashboard.php">الرئيسية</a>
                <?php if (isset($breadcrumb) && $breadcrumb): ?>
                    <?php foreach ($breadcrumb as $bc): ?>
                        <span class="sep">/</span>
                        <?php if (isset($bc['url'])): ?>
                            <a href="<?= $bc['url'] ?>"><?= clean($bc['label']) ?></a>
                        <?php else: ?>
                            <span class="current"><?= clean($bc['label']) ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php elseif (isset($pageTitle)): ?>
                    <span class="sep">/</span><span class="current"><?= clean($pageTitle) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="admin-header-end">
            <span style="color:var(--admin-muted);font-size:0.85rem;">
                <?php $adminDisplayName = !empty($adminUser['full_name']) ? $adminUser['full_name'] : $adminUser['username']; ?>
                أهلاً، <strong style="color:var(--gold);"><?= clean($adminDisplayName) ?></strong>
            </span>
            <a href="<?= BASE_URL ?>/admin/settings.php" class="btn-icon" title="الإعدادات"><i class="fas fa-cog"></i></a>
            <a href="<?= BASE_URL ?>/logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </header>

    <!-- شريط الفلاش -->
    <?= showFlash() ?>

    <!-- المحتوى الرئيسي -->
    <main class="admin-content">
        <div class="admin-page-title" style="margin-bottom:24px;">
            <h1><?= clean($pageTitle ?? 'لوحة التحكم') ?></h1>
        </div>
