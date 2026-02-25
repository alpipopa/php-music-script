<?php
/**
 * إدارة طلبات المقاطع - Admin Requests
 * Musican
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
startSession();
requireAdmin();

$db = getDB();
$action = clean($_GET['action'] ?? '');
$id = (int)($_GET['id'] ?? 0);

// حذف طلب
if ($action === 'delete' && $id) {
    checkCsrf();
    $db->prepare("DELETE FROM audio_requests WHERE id = ?")->execute([$id]);
    setFlash('success', 'تم حذف الطلب بنجاح.');
    redirect('requests.php');
}

// تغيير الحالة
if ($action === 'update_status' && $id) {
    $status = clean($_GET['status'] ?? 'pending');
    $db->prepare("UPDATE audio_requests SET status = ? WHERE id = ?")->execute([$status, $id]);
    redirect('requests.php');
}

$requests = $db->query("SELECT * FROM audio_requests ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'طلبات المقاطع';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-header-actions">
    <h1 class="admin-page-title">طلبات المقاطع الصوتية</h1>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (empty($requests)): ?>
            <div style="padding:50px; text-align:center; color:var(--text-muted);">لا توجد طلبات حالياً.</div>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>المقطع المطلوب</th>
                        <th>المؤدي</th>
                        <th>بريد الطالب</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><strong><?= clean($req['audio_title']) ?></strong></td>
                            <td><?= clean($req['performer_name']) ?></td>
                            <td><?= clean($req['user_email']) ?: '<span style="color:var(--text-muted);">غير متوفر</span>' ?></td>
                            <td>
                                <div class="dropdown" style="display:inline-block;">
                                    <button class="badge <?= $req['status'] == 'completed' ? 'badge-success' : ($req['status'] == 'pending' ? 'badge-warning' : 'badge-danger') ?>" style="cursor:pointer; border:none;">
                                        <?= $req['status'] == 'pending' ? 'قيد الانتظار' : ($req['status'] == 'completed' ? 'تم التنفيذ' : 'ملغي') ?>
                                        <i class="fas fa-chevron-down" style="font-size:0.7rem;"></i>
                                    </button>
                                    <div class="dropdown-content">
                                        <a href="?action=update_status&status=pending&id=<?= $req['id'] ?>">قيد الانتظار</a>
                                        <a href="?action=update_status&status=completed&id=<?= $req['id'] ?>">تم التنفيذ</a>
                                        <a href="?action=update_status&status=cancelled&id=<?= $req['id'] ?>">ملغي</a>
                                    </div>
                                </div>
                            </td>
                            <td><?= date('Y-m-d', strtotime($req['created_at'])) ?></td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn-icon-sm" title="عرض التفاصيل" onclick="alert('ملاحظات الطالب: <?= addslashes(clean($req['notes'] ?: 'لا توجد ملاحظات')) ?>')"><i class="fas fa-info-circle"></i></button>
                                    <a href="?action=delete&id=<?= $req['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn-icon-sm danger" title="حذف" onclick="return confirm('حذف هذا الطلب؟')"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
/* ستايل الدروب داون لتغيير الحالة */
.dropdown { position: relative; }
.dropdown-content {
    display: none;
    position: absolute;
    background: var(--secondary);
    min-width: 120px;
    box-shadow: var(--shadow);
    z-index: 100;
    border: 1px solid var(--border);
    border-radius: 8px;
    top: 100%;
    right: 0;
}
.dropdown:hover .dropdown-content { display: block; }
.dropdown-content a {
    color: var(--text);
    padding: 10px;
    text-decoration: none;
    display: block;
    font-size: 0.85rem;
}
.dropdown-content a:hover { background: var(--card-hover); color: var(--gold); }
</style>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
