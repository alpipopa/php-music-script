<?php
/**
 * إدارة الألبومات - Admin
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
startSession();
requireAdmin();

$action  = clean($_GET['action'] ?? '');
$albumId = (int)($_GET['id'] ?? 0);
$db      = getDB();
$error   = '';
$editAlbum = null;

// حذف
if ($action === 'delete' && $albumId) {
    checkCsrf();
    $row = $db->prepare("SELECT cover_image FROM albums WHERE id=?");
    $row->execute([$albumId]);
    $al = $row->fetch();
    if ($al) {
        if ($al['cover_image']) deleteFile('albums/' . $al['cover_image']);
        $db->prepare("DELETE FROM albums WHERE id=?")->execute([$albumId]);
        setFlash('success', 'تم حذف الألبوم.');
    }
    redirect(BASE_URL . '/admin/albums.php');
}

// إضافة / تعديل
if (in_array($action, ['add', 'edit'])) {
    if ($action === 'edit' && $albumId) {
        $stmt = $db->prepare("SELECT * FROM albums WHERE id=?");
        $stmt->execute([$albumId]);
        $editAlbum = $stmt->fetch();
        if (!$editAlbum) { setFlash('error', 'الألبوم غير موجود.'); redirect(BASE_URL . '/admin/albums.php'); }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_album'])) {
        checkCsrf();
        $title       = clean($_POST['title'] ?? '');
        $description = clean($_POST['description'] ?? '');
        $performer_id = (int)($_POST['performer_id'] ?? 0);
        $year        = (int)($_POST['year'] ?? date('Y'));
        $slug        = generateSlug($title);
        $coverImage  = $editAlbum['cover_image'] ?? '';

        if (!$title || !$performer_id) { $error = 'يرجى ملء الحقول المطلوبة.'; }
        else {
            if (!empty($_FILES['cover_image']['name'])) {
                $res = uploadFile($_FILES['cover_image'], 'albums', ['image/jpeg','image/png','image/webp'], 5*1024*1024);
                if ($res['success']) { if ($coverImage) deleteFile('albums/' . $coverImage); $coverImage = $res['filename']; }
                else { $error = $res['error']; }
            }
            if (!$error) {
                if ($action === 'add') {
                    $db->prepare("INSERT INTO albums (title, slug, description, performer_id, cover_image, year) VALUES (?,?,?,?,?,?)")
                       ->execute([$title, $slug, $description, $performer_id, $coverImage, $year]);
                    setFlash('success', 'تم إضافة الألبوم.');
                } else {
                    $db->prepare("UPDATE albums SET title=?, slug=?, description=?, performer_id=?, cover_image=?, year=? WHERE id=?")
                       ->execute([$title, $slug, $description, $performer_id, $coverImage, $year, $albumId]);
                    setFlash('success', 'تم تحديث الألبوم.');
                }
                redirect(BASE_URL . '/admin/albums.php');
            }
        }
    }
}

$albums     = getAlbums(200);
$performers = getPerformers(500);
$pageTitle  = $action === 'add' ? 'إضافة ألبوم' : ($action === 'edit' ? 'تعديل الألبوم' : 'إدارة الألبومات');

require_once __DIR__ . '/includes/admin_header.php';
?>

<?php if (in_array($action, ['add', 'edit'])): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title-sm"><?= $action === 'add' ? 'إضافة ألبوم جديد' : 'تعديل الألبوم' ?></h2>
        <a href="<?= BASE_URL ?>/admin/albums.php" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-right"></i> رجوع</a>
    </div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-error"><?= clean($error) ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <?= csrfField() ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">عنوان الألبوم <span class="req">*</span></label>
                    <input type="text" name="title" class="form-control" required value="<?= clean($editAlbum['title'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">المؤدي <span class="req">*</span></label>
                    <select name="performer_id" class="form-control" required>
                        <option value="">-- اختر مؤديًا --</option>
                        <?php foreach ($performers as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($editAlbum['performer_id'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= clean($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">سنة الإصدار</label>
                    <input type="number" name="year" class="form-control" value="<?= $editAlbum['year'] ?? date('Y') ?>" min="1900" max="<?= date('Y') ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">الوصف</label>
                <textarea name="description" class="form-control" rows="3"><?= clean($editAlbum['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">صورة الغلاف</label>
                <input type="file" name="cover_image" class="form-control" accept="image/*" data-preview="album-preview">
                <?php if (!empty($editAlbum['cover_image'])): ?>
                    <img id="album-preview" src="<?= getImageUrl('albums', $editAlbum['cover_image']) ?>" style="width:80px;height:80px;object-fit:cover;border-radius:8px;margin-top:8px;">
                <?php else: ?>
                    <img id="album-preview" src="" style="display:none;width:80px;height:80px;object-fit:cover;border-radius:8px;margin-top:8px;">
                <?php endif; ?>
            </div>
            <div style="display:flex;gap:12px;">
                <button type="submit" name="save_album" class="btn btn-gold btn-lg"><i class="fas fa-save"></i> حفظ</button>
                <a href="<?= BASE_URL ?>/admin/albums.php" class="btn btn-ghost btn-lg">إلغاء</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="card-header">
        <span style="color:var(--text-muted);font-size:0.9rem;"><?= count($albums) ?> ألبوم</span>
        <a href="?action=add" class="btn btn-gold btn-sm"><i class="fas fa-plus"></i> إضافة ألبوم</a>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($albums)): ?>
            <div class="empty-state"><div class="icon">💿</div><h3>لا توجد ألبومات</h3></div>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>الألبوم</th><th>المؤدي</th><th>السنة</th><th>المقاطع</th><th>إجراءات</th></tr></thead>
                <tbody>
                    <?php foreach ($albums as $al): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <img src="<?= getImageUrl('albums', $al['cover_image'] ?? '') ?>" style="width:42px;height:42px;border-radius:8px;object-fit:cover;">
                                    <a href="<?= BASE_URL ?>/album.php?slug=<?= urlencode($al['slug']) ?>" target="_blank" style="font-weight:600;color:var(--gold);"><?= clean($al['title']) ?></a>
                                </div>
                            </td>
                            <td><?= clean($al['performer_name']) ?></td>
                            <td><?= $al['year'] ?: '—' ?></td>
                            <td><?= $al['audios_count'] ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="?action=edit&id=<?= $al['id'] ?>" class="btn-icon-sm"><i class="fas fa-edit"></i></a>
                                    <a href="?action=delete&id=<?= $al['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn-icon-sm danger" onclick="return confirm('حذف هذا الألبوم؟')"><i class="fas fa-trash"></i></a>
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
