<?php
/**
 * إدارة رسائل اتصل بنا - Admin Contacts
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

// حذف رسالة
if ($action === 'delete' && $id) {
    checkCsrf();
    $db->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$id]);
    setFlash('success', 'تم حذف الرسالة بنجاح.');
    redirect('contacts.php');
}

// تحديث حالة القراءة
if ($action === 'mark_read' && $id) {
    $db->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?")->execute([$id]);
    redirect('contacts.php');
}

$messages = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'رسائل اتصل بنا';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-header-actions">
    <h1 class="admin-page-title">رسائل اتصل بنا</h1>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (empty($messages)): ?>
            <div style="padding:50px; text-align:center; color:var(--text-muted);">لا توجد رسائل حالياً.</div>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>البريد الإلكتروني</th>
                        <th>الموضوع</th>
                        <th>التاريخ</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $msg): ?>
                        <tr style="<?= !$msg['is_read'] ? 'background:rgba(212,175,55,0.05); font-weight:bold;' : '' ?>">
                            <td><?= clean($msg['name']) ?></td>
                            <td><?= clean($msg['email']) ?></td>
                            <td><?= clean($msg['subject']) ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($msg['created_at'])) ?></td>
                            <td>
                                <?php if (!$msg['is_read']): ?>
                                    <span class="badge badge-warning">جديدة</span>
                                <?php else: ?>
                                    <span class="badge badge-success">تمت القراءة</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn-icon-sm" onclick="viewMessage(<?= htmlspecialchars(json_encode($msg)) ?>)" title="عرض"><i class="fas fa-eye"></i></button>
                                    <a href="?action=delete&id=<?= $msg['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn-icon-sm danger" title="حذف" onclick="return confirm('حذف هذه الرسالة؟')"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- مودال عرض الرسالة -->
<div id="msgModal" class="modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); z-index:2000; align-items:center; justify-content:center; padding:20px;">
    <div class="card" style="width:100%; max-width:600px;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h2 id="modalSubject" class="card-title-sm"></h2>
            <button onclick="closeModal()" style="background:none; border:none; color:var(--text); cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div class="card-body">
            <div id="modalMeta" style="margin-bottom:20px; font-size:0.9rem; color:var(--text-muted); padding-bottom:15px; border-bottom:1px solid var(--border);"></div>
            <div id="modalContent" style="white-space:pre-wrap; line-height:1.8;"></div>
        </div>
    </div>
</div>

<script>
function viewMessage(msg) {
    document.getElementById('modalSubject').innerText = msg.subject || 'بدون موضوع';
    document.getElementById('modalMeta').innerHTML = `من: ${msg.name} (${msg.email}) <br> التاريخ: ${msg.created_at}`;
    document.getElementById('modalContent').innerText = msg.message;
    document.getElementById('msgModal').style.display = 'flex';
    
    // وضع الماركر كأنه قرأ
    if (msg.is_read == 0) {
        fetch(`contacts.php?action=mark_read&id=${msg.id}`);
    }
}
function closeModal() {
    document.getElementById('msgModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
