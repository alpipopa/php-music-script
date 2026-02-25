<?php
/**
 * صفحة الإشعارات
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
$activePage  = 'notifications';
$notifications = getNotifications($currentUser['id'], 50);

// تحديث الإشعارات كمقروءة
markNotificationsAsRead($currentUser['id']);

$pageTitle = 'الإشعارات';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top:30px; margin-bottom:50px; max-width:800px;">
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h2 class="card-title-sm"><i class="fas fa-bell"></i> مركز الإشعارات</h2>
            <span style="font-size:0.8rem; color:var(--text-muted);"><?= count($notifications) ?> إشعار</span>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($notifications)): ?>
                <div style="padding:50px; text-align:center; color:var(--text-muted);">
                    <i class="fas fa-bell-slash" style="font-size:3rem; opacity:0.2; margin-bottom:15px;"></i>
                    <p>لا توجد إشعارات حالياً</p>
                </div>
            <?php else: ?>
                <div class="notif-list">
                    <?php foreach ($notifications as $notif): ?>
                        <a href="<?= $notif['link'] ?: '#' ?>" class="notif-item <?= !$notif['is_read'] ? 'unread' : '' ?>" style="display:flex; gap:15px; padding:20px; text-decoration:none; border-bottom:1px solid var(--border); transition:0.2s;">
                            <div class="notif-icon" style="width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:<?= $notif['type'] == 'message' ? '#3498db' : ($notif['type'] == 'follow' ? '#2ecc71' : 'var(--gold)') ?>; color:white;">
                                <i class="fas <?= $notif['type'] == 'message' ? 'fa-envelope' : ($notif['type'] == 'follow' ? 'fa-user-plus' : 'fa-info-circle') ?>"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="color:var(--text-dark); margin-bottom:4px;"><?= clean($notif['content']) ?></div>
                                <div style="font-size:0.75rem; color:var(--text-muted);"><?= timeAgo($notif['created_at']) ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.notif-item:hover { background: rgba(0,0,0,0.02); }
.notif-item.unread { background: rgba(212,175,55,0.05); border-right: 4px solid var(--gold); }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
