<?php
/**
 * إدارة رسائل التواصل - Admin
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
startSession();
requireAdmin();

$action = clean($_GET['action'] ?? '');
$msgId  = (int)($_GET['id'] ?? 0);
$db     = getDB();

// حذف
if ($action === 'delete' && $msgId) {
    checkCsrf();
    $db->prepare("DELETE FROM contact_messages WHERE id=?")->execute([$msgId]);
    setFlash('success', 'تم حذف الرسالة.');
    redirect(BASE_URL . '/admin/messages.php');
}

// تعيين مقروء
if ($action === 'read' && $msgId) {
    $db->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?")->execute([$msgId]);
}

$messages = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 100")->fetchAll();

$view = null;
if ($msgId && in_array($action, ['view', 'read'])) {
    $stmt = $db->prepare("SELECT * FROM contact_messages WHERE id=?");
    $stmt->execute([$msgId]);
    $view = $stmt->fetch();
}

$pageTitle = 'رسائل التواصل';
require_once __DIR__ . '/includes/admin_header.php';
?>

<?php if ($view): ?>
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h2 class="card-title-sm">رسالة من: <?= clean($view['name']) ?></h2>
        <a href="<?= BASE_URL ?>/admin/messages.php" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-right"></i> رجوع</a>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
            <div><label class="form-label">الاسم</label><p><?= clean($view['name']) ?></p></div>
            <div><label class="form-label">الإيميل</label><p><a href="mailto:<?= clean($view['email']) ?>"><?= clean($view['email']) ?></a></p></div>
            <div><label class="form-label">التاريخ</label><p><?= formatArabicDate($view['created_at']) ?></p></div>
        </div>
        <?php if ($view['subject']): ?>
            <div style="margin-bottom:16px;"><label class="form-label">الموضوع</label><p><?= clean($view['subject']) ?></p></div>
        <?php endif; ?>
        <div><label class="form-label">الرسالة</label><div class="card-body" style="background:var(--admin-sidebar);border-radius:8px;margin-top:8px;"><?= nl2br(clean($view['message'])) ?></div></div>
        <div style="margin-top:20px;display:flex;gap:10px;">
            <a href="mailto:<?= clean($view['email']) ?>?subject=رد: <?= clean($view['subject']) ?>" class="btn btn-gold"><i class="fas fa-reply"></i> رد عبر الإيميل</a>
            <a href="?action=delete&id=<?= $view['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn btn-danger btn-sm" onclick="return confirm('حذف هذه الرسالة؟')"><i class="fas fa-trash"></i> حذف</a>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title-sm"><i class="fas fa-envelope"></i> الرسائل</h2>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($messages)): ?>
            <div class="empty-state"><div class="icon">📭</div><h3>لا توجد رسائل</h3></div>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>المرسل</th><th>الموضوع</th><th>التاريخ</th><th>الحالة</th><th>إجراءات</th></tr></thead>
                <tbody>
                    <?php foreach ($messages as $msg): ?>
                        <tr <?= !$msg['is_read'] ? 'style="font-weight:bold;"' : '' ?>>
                            <td>
                                <div><?= clean($msg['name']) ?></div>
                                <div style="color:var(--text-muted);font-size:0.75rem;"><?= clean($msg['email']) ?></div>
                            </td>
                            <td><?= clean($msg['subject'] ?: '—') ?></td>
                            <td><?= timeAgo($msg['created_at']) ?></td>
                            <td><span class="badge <?= $msg['is_read'] ? 'badge-info' : 'badge-warning' ?>"><?= $msg['is_read'] ? 'مقروءة' : 'جديدة' ?></span></td>
                            <td>
                                <div class="table-actions">
                                    <a href="?action=read&id=<?= $msg['id'] ?>" class="btn-icon-sm" title="عرض"><i class="fas fa-eye"></i></a>
                                    <a href="?action=delete&id=<?= $msg['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn-icon-sm danger" onclick="return confirm('حذف الرسالة؟')"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
