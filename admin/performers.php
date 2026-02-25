<?php
/**
 * إدارة المؤدين - Admin
 * Musican
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
startSession();
requireAdmin();

$action     = clean($_GET['action'] ?? '');
$perfId     = (int)($_GET['id'] ?? 0);
$db         = getDB();
$error      = '';
$editPerformer = null;

// حذف
if ($action === 'delete' && $perfId) {
    checkCsrf();
    $row = $db->prepare("SELECT image FROM performers WHERE id = ?");
    $row->execute([$perfId]);
    $perf = $row->fetch();
    if ($perf) {
        if ($perf['image']) deleteFile('performers/' . $perf['image']);
        $db->prepare("DELETE FROM performers WHERE id = ?")->execute([$perfId]);
        setFlash('success', 'تم حذف المؤدي بنجاح.');
    }
    redirect(BASE_URL . '/admin/performers.php');
}

// إضافة / تعديل
if (in_array($action, ['add', 'edit'])) {
    if ($action === 'edit' && $perfId) {
        $stmt = $db->prepare("SELECT * FROM performers WHERE id = ?");
        $stmt->execute([$perfId]);
        $editPerformer = $stmt->fetch();
        if (!$editPerformer) { setFlash('error', 'المؤدي غير موجود.'); redirect(BASE_URL . '/admin/performers.php'); }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_performer'])) {
        checkCsrf();
        $name       = clean($_POST['name'] ?? '');
        $fullName   = clean($_POST['full_name'] ?? '');
        $bio        = clean($_POST['bio'] ?? '');
        $is_verified = isset($_POST['is_verified']) ? 1 : 0;
        $slug       = generateSlug($name);
        $imgFile    = $editPerformer['image'] ?? '';

        if (!$name) { $error = 'يرجى إدخال اسم المؤدي.'; }
        else {
            if (!empty($_FILES['image']['name'])) {
                $imgResult = uploadFile($_FILES['image'], 'performers', ['image/jpeg','image/png','image/webp','image/gif'], 5*1024*1024);
                if ($imgResult['success']) {
                    if ($imgFile) deleteFile('performers/' . $imgFile);
                    $imgFile = $imgResult['filename'];
                } else { $error = $imgResult['error']; }
            }

            if (!$error) {
                if ($action === 'add') {
                    $db->prepare("INSERT INTO performers (name, full_name, slug, bio, image, is_verified) VALUES (?,?,?,?,?,?)")
                       ->execute([$name, $fullName, $slug, $bio, $imgFile, $is_verified]);
                    setFlash('success', 'تم إضافة المؤدي بنجاح.');
                } else {
                    $db->prepare("UPDATE performers SET name=?, full_name=?, slug=?, bio=?, image=?, is_verified=? WHERE id=?")
                       ->execute([$name, $fullName, $slug, $bio, $imgFile, $is_verified, $perfId]);
                    setFlash('success', 'تم تحديث المؤدي بنجاح.');
                }
                redirect(BASE_URL . '/admin/performers.php');
            }
        }
    }
}

$performers = getPerformers(500);
$pageTitle  = $action === 'add' ? 'إضافة مؤدي جديد' : ($action === 'edit' ? 'تعديل المؤدي' : 'إدارة المؤدين');

require_once __DIR__ . '/includes/admin_header.php';
?>

<?php if (in_array($action, ['add', 'edit'])): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title-sm"><?= $action === 'add' ? 'إضافة مؤدي جديد' : 'تعديل المؤدي' ?></h2>
        <a href="<?= BASE_URL ?>/admin/performers.php" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-right"></i> رجوع</a>
    </div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-error"><?= clean($error) ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <?= csrfField() ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">الاسم الفني/العام <span class="req">*</span></label>
                    <input type="text" name="name" class="form-control" required value="<?= clean($editPerformer['name'] ?? $_POST['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">الاسم الكامل (اختياري)</label>
                    <input type="text" name="full_name" class="form-control" value="<?= clean($editPerformer['full_name'] ?? $_POST['full_name'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">الصورة الشخصية</label>
                    <input type="file" name="image" class="form-control" accept="image/*" data-preview="perf-preview">
                    <?php if (!empty($editPerformer['image'])): ?>
                        <img id="perf-preview" src="<?= getImageUrl('performers', $editPerformer['image']) ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-top:8px;">
                    <?php else: ?>
                        <img id="perf-preview" src="" style="display:none;width:80px;height:80px;border-radius:50%;object-fit:cover;margin-top:8px;">
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">نبذة تعريفية</label>
                    <textarea name="bio" class="form-control" rows="1"><?= clean($editPerformer['bio'] ?? $_POST['bio'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="is_verified" value="1" <?= ($editPerformer['is_verified'] ?? 0) ? 'checked' : '' ?> style="width:18px;height:18px;">
                    مؤدي موثّق
                </label>
            </div>
            <div style="display:flex;gap:12px;">
                <button type="submit" name="save_performer" class="btn btn-gold btn-lg"><i class="fas fa-save"></i> حفظ</button>
                <a href="<?= BASE_URL ?>/admin/performers.php" class="btn btn-ghost btn-lg">إلغاء</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="card-header">
        <span style="color:var(--text-muted);font-size:0.9rem;"><?= count($performers) ?> مؤدي</span>
        <a href="?action=add" class="btn btn-gold btn-sm"><i class="fas fa-plus"></i> إضافة مؤدي</a>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($performers)): ?>
            <div class="empty-state"><div class="icon">🎤</div><h3>لا يوجد مؤدون</h3><a href="?action=add" class="btn btn-gold" style="margin-top:12px;">إضافة مؤدي</a></div>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>المؤدي</th><th>المقاطع</th><th>المتابعون</th><th>موثّق</th><th>إجراءات</th></tr></thead>
                <tbody>
                    <?php foreach ($performers as $p): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <img src="<?= getImageUrl('performers', $p['image'] ?? '') ?>" alt="" style="width:42px;height:42px;object-fit:cover;border-radius:50%;">
                                    <div>
                                        <a href="<?= BASE_URL ?>/performer.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" style="font-weight:600;color:var(--gold);"><?= clean($p['name']) ?></a>
                                    </div>
                                </div>
                            </td>
                            <td><?= $p['audios_count'] ?></td>
                            <td><?= formatNumber($p['followers_count']) ?></td>
                            <td><?= $p['is_verified'] ? '<span class="badge badge-success">موثّق</span>' : '—' ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="?action=edit&id=<?= $p['id'] ?>" class="btn-icon-sm" title="تعديل"><i class="fas fa-edit"></i></a>
                                    <a href="?action=delete&id=<?= $p['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn-icon-sm danger" title="حذف" onclick="return confirm('حذف هذا المؤدي؟')"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
