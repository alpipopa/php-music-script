<?php
/**
 * لوحة التحكم - Dashboard
 * Musican Admin
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
startSession();
requireAdmin();

$pageTitle = 'لوحة التحكم';

// الإحصائيات
$stats = getDashboardStats();

// أحدث المقاطع
$latestAudios = getAudios(['limit' => 5, 'order' => 'latest']);

// أحدث المستخدمين
$db = getDB();
$latestUsers = $db->query("SELECT id, username, full_name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

// أحدث رسائل التواصل
$latestMessages = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5")->fetchAll();

// أحدث طلبات المقاطع
$audioRequests = $db->query("SELECT ar.*, u.username FROM audio_requests ar LEFT JOIN users u ON ar.user_id = u.id ORDER BY ar.created_at DESC LIMIT 5")->fetchAll();

require_once __DIR__ . '/includes/admin_header.php';
?>

<!-- الإحصائيات -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-headphones-alt"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-value" data-count="<?= $stats['audios'] ?>"><?= formatNumber($stats['audios']) ?></div>
            <div class="stat-card-label">مقطع صوتي</div>
        </div>
        <a href="<?= BASE_URL ?>/admin/audios.php" class="stat-card-footer">إدارة المقاطع <i class="fas fa-arrow-left"></i></a>
    </div>
    <div class="stat-card">
        <div class="stat-icon gold"><i class="fas fa-microphone"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-value" data-count="<?= $stats['performers'] ?>"><?= formatNumber($stats['performers']) ?></div>
            <div class="stat-card-label">مؤدي</div>
        </div>
        <a href="<?= BASE_URL ?>/admin/performers.php" class="stat-card-footer">إدارة المؤدين <i class="fas fa-arrow-left"></i></a>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-users"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-value" data-count="<?= $stats['users'] ?>"><?= formatNumber($stats['users']) ?></div>
            <div class="stat-card-label">مستخدم</div>
        </div>
        <a href="<?= BASE_URL ?>/admin/users.php" class="stat-card-footer">إدارة المستخدمين <i class="fas fa-arrow-left"></i></a>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-compact-disc"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-value" data-count="<?= $stats['albums'] ?>"><?= formatNumber($stats['albums']) ?></div>
            <div class="stat-card-label">ألبوم</div>
        </div>
        <a href="<?= BASE_URL ?>/admin/albums.php" class="stat-card-footer">إدارة الألبومات <i class="fas fa-arrow-left"></i></a>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-play-circle"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-value" data-count="<?= $stats['total_listens'] ?>"><?= formatNumber($stats['total_listens']) ?></div>
            <div class="stat-card-label">إجمالي الاستماع</div>
        </div>
        <a href="<?= BASE_URL ?>/admin/audios.php?order=popular" class="stat-card-footer">الأكثر استماعًا <i class="fas fa-arrow-left"></i></a>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-download"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-value" data-count="<?= $stats['total_downloads'] ?>"><?= formatNumber($stats['total_downloads']) ?></div>
            <div class="stat-card-label">إجمالي التنزيلات</div>
        </div>
        <a href="<?= BASE_URL ?>/admin/audios.php" class="stat-card-footer">عرض التفاصيل <i class="fas fa-arrow-left"></i></a>
    </div>
</div>

<!-- الإجراءات السريعة -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-header"><h2 class="card-title-sm"><i class="fas fa-bolt"></i> إجراءات سريعة</h2></div>
    <div class="card-body">
        <div style="display:flex;flex-wrap:wrap;gap:12px;">
            <a href="<?= BASE_URL ?>/admin/audios.php?action=add" class="btn btn-gold"><i class="fas fa-plus"></i> إضافة مقطع</a>
            <a href="<?= BASE_URL ?>/admin/performers.php?action=add" class="btn btn-outline"><i class="fas fa-plus"></i> إضافة مؤدي</a>
            <a href="<?= BASE_URL ?>/admin/albums.php?action=add" class="btn btn-outline"><i class="fas fa-plus"></i> إضافة ألبوم</a>
            <a href="<?= BASE_URL ?>/admin/categories.php?action=add" class="btn btn-outline"><i class="fas fa-plus"></i> إضافة قسم</a>
            <a href="<?= BASE_URL ?>/admin/settings.php" class="btn btn-ghost"><i class="fas fa-cog"></i> الإعدادات</a>
            <a href="<?= BASE_URL ?>/" target="_blank" class="btn btn-ghost"><i class="fas fa-eye"></i> معاينة الموقع</a>
        </div>
    </div>
</div>

<!-- جداول البيانات -->
<div class="grid grid-2" style="gap:24px;align-items:flex-start;">

    <!-- آخر المقاطع -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title-sm"><i class="fas fa-music"></i> أحدث المقاطع</h2>
            <a href="<?= BASE_URL ?>/admin/audios.php" class="btn btn-ghost btn-sm">عرض الكل</a>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($latestAudios)): ?>
                <div class="empty-state" style="padding:30px;"><div class="icon">🎵</div><h3>لا توجد مقاطع</h3></div>
            <?php else: ?>
                <table class="admin-table">
                    <thead><tr><th>المقطع</th><th>الاستماع</th><th>الحالة</th></tr></thead>
                    <tbody>
                        <?php foreach ($latestAudios as $audio): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <img src="<?= getImageUrl('albums', $audio['cover_image'] ?? '') ?>" alt="" style="width:38px;height:38px;border-radius:8px;object-fit:cover;">
                                        <div>
                                            <div style="font-weight:600;font-size:0.85rem;"><?= clean($audio['title']) ?></div>
                                            <div style="color:var(--text-muted);font-size:0.75rem;"><?= clean($audio['performer_name']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= formatNumber($audio['listens']) ?></td>
                                <td>
                                    <span class="badge <?= $audio['status'] === 'published' ? 'badge-success' : 'badge-warning' ?>">
                                        <?= $audio['status'] === 'published' ? 'منشور' : 'مسودة' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- آخر المستخدمين -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title-sm"><i class="fas fa-users"></i> أحدث المستخدمين</h2>
            <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-ghost btn-sm">عرض الكل</a>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($latestUsers)): ?>
                <div class="empty-state" style="padding:30px;"><div class="icon">👥</div><h3>لا يوجد مستخدمون</h3></div>
            <?php else: ?>
                <table class="admin-table">
                    <thead><tr><th>المستخدم</th><th>الاسم الكامل</th><th>الدور</th><th>التاريخ</th></tr></thead>
                    <tbody>
                        <?php foreach ($latestUsers as $user): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600;font-size:0.85rem;"><?= clean($user['username']) ?></div>
                                    <div style="color:var(--text-muted);font-size:0.75rem;"><?= clean($user['email']) ?></div>
                                </td>
                                <td><?= clean($user['full_name'] ?? '—') ?></td>
                                <td>
                                    <span class="badge <?= $user['role'] === 'admin' ? 'badge-danger' : ($user['role'] === 'performer' ? 'badge-gold' : 'badge-info') ?>">
                                        <?= $user['role'] === 'admin' ? 'مدير' : ($user['role'] === 'performer' ? 'مؤدي' : 'مستخدم') ?>
                                    </span>
                                </td>
                                <td><?= timeAgo($user['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- طلبات المقاطع -->
    <?php if (!empty($audioRequests)): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title-sm"><i class="fas fa-plus-circle"></i> طلبات المقاطع</h2>
            <a href="<?= BASE_URL ?>/admin/requests.php" class="btn btn-ghost btn-sm">عرض الكل</a>
        </div>
        <div class="card-body" style="padding:0;">
            <table class="admin-table">
                <thead><tr><th>الطلب</th><th>المستخدم</th><th>الحالة</th></tr></thead>
                <tbody>
                    <?php foreach ($audioRequests as $req): ?>
                        <tr>
                            <td style="font-size:0.85rem;"><?= clean(mb_substr($req['request_text'], 0, 50)) ?>...</td>
                            <td><?= clean($req['username'] ?: 'زائر') ?></td>
                            <td>
                                <span class="badge <?= $req['status'] === 'done' ? 'badge-success' : ($req['status'] === 'rejected' ? 'badge-danger' : 'badge-warning') ?>">
                                    <?= $req['status'] === 'done' ? 'منجز' : ($req['status'] === 'rejected' ? 'مرفوض' : 'قيد الانتظار') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- رسائل التواصل -->
    <?php if (!empty($latestMessages)): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title-sm"><i class="fas fa-envelope"></i> أحدث الرسائل</h2>
            <a href="<?= BASE_URL ?>/admin/messages.php" class="btn btn-ghost btn-sm">عرض الكل</a>
        </div>
        <div class="card-body" style="padding:0;">
            <table class="admin-table">
                <thead><tr><th>المرسل</th><th>الموضوع</th><th>التاريخ</th></tr></thead>
                <tbody>
                    <?php foreach ($latestMessages as $msg): ?>
                        <tr>
                            <td>
                                <div style="font-weight:600;font-size:0.85rem;"><?= clean($msg['name']) ?></div>
                                <div style="color:var(--text-muted);font-size:0.75rem;"><?= clean($msg['email']) ?></div>
                            </td>
                            <td style="font-size:0.85rem;"><?= clean($msg['subject'] ?: 'بلا موضوع') ?></td>
                            <td><?= timeAgo($msg['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
