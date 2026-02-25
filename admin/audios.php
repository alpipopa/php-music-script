<?php
/**
 * إدارة الصوتيات - Admin
 * Musican
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
startSession();
requireAdmin();

$action  = clean($_GET['action'] ?? '');
$audioId = (int)($_GET['id'] ?? 0);
$db      = getDB();
$success = '';
$error   = '';

// ====================== حذف مقطع ======================
if ($action === 'delete' && $audioId) {
    checkCsrf();
    $audio = $db->prepare("SELECT audio_file, cover_image FROM audios WHERE id = ?");
    $audio->execute([$audioId]);
    $row = $audio->fetch();
    if ($row) {
        if ($row['audio_file']) deleteFile('audios/' . $row['audio_file']);
        if ($row['cover_image']) deleteFile('albums/' . $row['cover_image']);
        $db->prepare("DELETE FROM audios WHERE id = ?")->execute([$audioId]);
        setFlash('success', 'تم حذف المقطع بنجاح.');
    }
    redirect(BASE_URL . '/admin/audios.php');
}

// ====================== تبديل الحالة ======================
if ($action === 'toggle_status' && $audioId) {
    $db->prepare("UPDATE audios SET status = IF(status='published','draft','published') WHERE id = ?")->execute([$audioId]);
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// ====================== إضافة / تعديل ======================
$editAudio = null;
if (in_array($action, ['add', 'edit'])) {
    if ($action === 'edit' && $audioId) {
        $stmt = $db->prepare("SELECT a.*, p.name AS performer_name, p.slug AS performer_slug, al.title AS album_title FROM audios a LEFT JOIN performers p ON a.performer_id = p.id LEFT JOIN albums al ON a.album_id = al.id WHERE a.id = ?");
        $stmt->execute([$audioId]);
        $editAudio = $stmt->fetch();
        if (!$editAudio) { setFlash('error', 'المقطع غير موجود.'); redirect(BASE_URL . '/admin/audios.php'); }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_audio'])) {
        checkCsrf();

        $title         = clean($_POST['title'] ?? '');
        $description   = clean($_POST['description'] ?? '');
        $lyrics        = clean($_POST['lyrics'] ?? '');
        $category_id   = (int)($_POST['category_id'] ?? 0);
        $performer_id  = (int)($_POST['performer_id'] ?? 0);
        $album_id      = (int)($_POST['album_id'] ?? 0) ?: null;
        $duration      = clean($_POST['duration'] ?? '');
        $status        = in_array($_POST['status'] ?? '', ['published','draft']) ? $_POST['status'] : 'draft';
        $is_featured   = isset($_POST['is_featured']) ? 1 : 0;
        $allow_download = isset($_POST['allow_download']) ? 1 : 0;
        $slug          = generateSlug($title);

        if (!$title || !$category_id || !$performer_id) {
            $error = 'يرجى ملء جميع الحقول المطلوبة.';
        } else {
            // رفع الملف الصوتي
            $audioFile = $editAudio['audio_file'] ?? '';
            if (!empty($_FILES['audio_file']['name'])) {
                $uploadResult = uploadFile($_FILES['audio_file'], 'audios', ['audio/mpeg','audio/mp3','audio/wav','audio/ogg','audio/mp4'], 200 * 1024 * 1024);
                if ($uploadResult['success']) {
                    if ($audioFile) deleteFile('audios/' . $audioFile);
                    $audioFile = $uploadResult['filename'];
                } else {
                    $error = $uploadResult['error'];
                }
            }

            // رفع الغلاف
            $coverImage = $editAudio['cover_image'] ?? '';
            if (!empty($_FILES['cover_image']['name'])) {
                $imgResult = uploadFile($_FILES['cover_image'], 'albums', ['image/jpeg','image/png','image/webp','image/gif'], 5 * 1024 * 1024);
                if ($imgResult['success']) {
                    if ($coverImage) deleteFile('albums/' . $coverImage);
                    $coverImage = $imgResult['filename'];
                } else {
                    $error = $imgResult['error'];
                }
            }

            if (!$error) {
                if ($action === 'add') {
                    if (!$audioFile) { $error = 'يرجى رفع ملف صوتي.'; }
                    else {
                        $db->prepare("INSERT INTO audios (title, slug, description, lyrics, audio_file, cover_image, duration, category_id, performer_id, album_id, status, is_featured, allow_download) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
                           ->execute([$title, $slug, $description, $lyrics, $audioFile, $coverImage, $duration, $category_id, $performer_id, $album_id, $status, $is_featured, $allow_download]);
                        setFlash('success', 'تم إضافة المقطع بنجاح.');
                        redirect(BASE_URL . '/admin/audios.php');
                    }
                } else {
                    $db->prepare("UPDATE audios SET title=?, slug=?, description=?, lyrics=?, audio_file=?, cover_image=?, duration=?, category_id=?, performer_id=?, album_id=?, status=?, is_featured=?, allow_download=? WHERE id=?")
                       ->execute([$title, $slug, $description, $lyrics, $audioFile, $coverImage, $duration, $category_id, $performer_id, $album_id, $status, $is_featured, $allow_download, $audioId]);
                    setFlash('success', 'تم تحديث المقطع بنجاح.');
                    redirect(BASE_URL . '/admin/audios.php');
                }
            }
        }
    }
}

// ====================== قائمة الصوتيات ======================
$search  = clean($_GET['search'] ?? '');
$catId   = (int)($_GET['category'] ?? 0);
$perPage = 20;
$page    = max(1, (int)($_GET['page'] ?? 1));
$opts    = ['search' => $search, 'category_id' => $catId, 'limit' => $perPage, 'offset' => ($page-1)*$perPage, 'order' => $_GET['order'] ?? 'latest'];
$audios  = getAudios($opts);
$total   = countAudios($opts);
$pagination = getPagination($total, $perPage, $page, BASE_URL . '/admin/audios.php?' . http_build_query(['search'=>$search,'category'=>$catId]));
$categories = getCategories();
$performers = getPerformers(500);
$albums     = getAlbums(500);
$pageTitle  = $action === 'add' ? 'إضافة مقطع جديد' : ($action === 'edit' ? 'تعديل المقطع' : 'إدارة الصوتيات');

require_once __DIR__ . '/includes/admin_header.php';
?>

<?php if (in_array($action, ['add', 'edit'])): ?>
<!-- ===== نموذج الإضافة/التعديل ===== -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title-sm"><?= $action === 'add' ? '<i class="fas fa-plus"></i> إضافة مقطع جديد' : '<i class="fas fa-edit"></i> تعديل المقطع' ?></h2>
        <a href="<?= BASE_URL ?>/admin/audios.php" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-right"></i> رجوع</a>
    </div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-error"><?= clean($error) ?></div><?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <?= csrfField() ?>
            <div class="form-row">
                <div class="form-group" style="grid-column:span 2;">
                    <label class="form-label">عنوان المقطع <span class="req">*</span></label>
                    <input type="text" name="title" class="form-control" required value="<?= clean($editAudio['title'] ?? $_POST['title'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">القسم <span class="req">*</span></label>
                    <select name="category_id" class="form-control" required>
                        <option value="">-- اختر قسمًا --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (($editAudio['category_id'] ?? $_POST['category_id'] ?? '') == $c['id']) ? 'selected' : '' ?>><?= clean($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">المؤدي <span class="req">*</span></label>
                    <select name="performer_id" class="form-control" required>
                        <option value="">-- اختر مؤديًا --</option>
                        <?php foreach ($performers as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= (($editAudio['performer_id'] ?? $_POST['performer_id'] ?? '') == $p['id']) ? 'selected' : '' ?>><?= clean($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">الألبوم (اختياري)</label>
                    <select name="album_id" class="form-control">
                        <option value="">-- بدون ألبوم --</option>
                        <?php foreach ($albums as $al): ?>
                            <option value="<?= $al['id'] ?>" <?= (($editAudio['album_id'] ?? $_POST['album_id'] ?? '') == $al['id']) ? 'selected' : '' ?>><?= clean($al['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">الملف الصوتي <?= $action === 'add' ? '<span class="req">*</span>' : '(اتركه فارغًا للإبقاء على القديم)' ?></label>
                    <input type="file" name="audio_file" class="form-control" accept="audio/*" <?= $action === 'add' ? 'required' : '' ?>>
                    <?php if (!empty($editAudio['audio_file'])): ?>
                        <div style="margin-top:8px;color:var(--text-muted);font-size:0.85rem;"><i class="fas fa-check-circle" style="color:#27ae60;"></i> الملف الحالي: <?= clean($editAudio['audio_file']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">صورة الغلاف</label>
                    <input type="file" name="cover_image" class="form-control" accept="image/*" data-preview="cover-preview">
                    <?php if (!empty($editAudio['cover_image'])): ?>
                        <img id="cover-preview" src="<?= getImageUrl('albums', $editAudio['cover_image']) ?>" alt="" style="width:80px;height:80px;object-fit:cover;border-radius:8px;margin-top:8px;">
                    <?php else: ?>
                        <img id="cover-preview" src="" alt="" style="display:none;width:80px;height:80px;object-fit:cover;border-radius:8px;margin-top:8px;">
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">مدة المقطع (مثال: 05:30)</label>
                    <input type="text" name="duration" class="form-control" value="<?= clean($editAudio['duration'] ?? $_POST['duration'] ?? '') ?>" placeholder="mm:ss">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">الوصف</label>
                <textarea name="description" class="form-control" rows="3"><?= clean($editAudio['description'] ?? $_POST['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">الكلمات / النص</label>
                <textarea name="lyrics" class="form-control" rows="5"><?= clean($editAudio['lyrics'] ?? $_POST['lyrics'] ?? '') ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-control">
                        <option value="published" <?= (($editAudio['status'] ?? 'published') === 'published') ? 'selected' : '' ?>>منشور</option>
                        <option value="draft" <?= (($editAudio['status'] ?? '') === 'draft') ? 'selected' : '' ?>>مسودة</option>
                    </select>
                </div>
                <div class="form-group" style="display:flex;align-items:flex-end;gap:20px;">
                    <label class="form-check-label" style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="is_featured" value="1" <?= ($editAudio['is_featured'] ?? 0) ? 'checked' : '' ?> style="width:18px;height:18px;">
                        مقطع مميز
                    </label>
                    <label class="form-check-label" style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="allow_download" value="1" <?= ($editAudio['allow_download'] ?? 1) ? 'checked' : '' ?> style="width:18px;height:18px;">
                        السماح بالتنزيل
                    </label>
                </div>
            </div>
            <div style="display:flex;gap:12px;">
                <button type="submit" name="save_audio" class="btn btn-gold btn-lg">
                    <i class="fas fa-save"></i> <?= $action === 'add' ? 'حفظ المقطع' : 'تحديث المقطع' ?>
                </button>
                <a href="<?= BASE_URL ?>/admin/audios.php" class="btn btn-ghost btn-lg">إلغاء</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ===== قائمة الصوتيات ===== -->
<div class="card">
    <div class="card-header">
        <div style="display:flex;align-items:center;gap:12px;">
            <span style="color:var(--text-muted);font-size:0.9rem;"><?= number_format($total) ?> مقطع</span>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <form method="get" style="display:flex;gap:8px;">
                <input type="text" name="search" value="<?= clean($search) ?>" placeholder="بحث..." class="form-control" style="width:180px;">
                <select name="category" class="filter-select">
                    <option value="0">كل الأقسام</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $catId == $c['id'] ? 'selected' : '' ?>><?= clean($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-ghost btn-sm"><i class="fas fa-search"></i></button>
            </form>
            <a href="<?= BASE_URL ?>/admin/audios.php?action=add" class="btn btn-gold btn-sm"><i class="fas fa-plus"></i> إضافة مقطع</a>
        </div>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($audios)): ?>
            <div class="empty-state"><div class="icon">🎵</div><h3>لا توجد مقاطع</h3><a href="?action=add" class="btn btn-gold" style="margin-top:12px;">إضافة مقطع</a></div>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all"></th>
                        <th>المقطع</th>
                        <th>المؤدي</th>
                        <th>القسم</th>
                        <th>استماع</th>
                        <th>تنزيل</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audios as $audio): ?>
                        <tr>
                            <td><input type="checkbox" class="row-checkbox" value="<?= $audio['id'] ?>"></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <img src="<?= getImageUrl('albums', $audio['cover_image'] ?? '') ?>" alt="" style="width:42px;height:42px;border-radius:8px;object-fit:cover;">
                                    <div>
                                        <a href="<?= BASE_URL ?>/audio.php?slug=<?= urlencode($audio['slug']) ?>" target="_blank" style="font-weight:600;font-size:0.85rem;color:var(--gold);"><?= clean($audio['title']) ?></a>
                                        <?php if ($audio['is_featured']): ?><span class="badge badge-gold" style="font-size:0.65rem;margin-right:4px;">مميز</span><?php endif; ?>
                                        <div style="color:var(--text-muted);font-size:0.75rem;"><?= $audio['duration'] ?: '—' ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= clean($audio['performer_name']) ?></td>
                            <td><?= clean($audio['category_name'] ?? '—') ?></td>
                            <td><?= formatNumber($audio['listens']) ?></td>
                            <td><?= formatNumber($audio['downloads']) ?></td>
                            <td>
                                <span class="badge <?= $audio['status'] === 'published' ? 'badge-success' : 'badge-warning' ?>">
                                    <?= $audio['status'] === 'published' ? 'منشور' : 'مسودة' ?>
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="?action=edit&id=<?= $audio['id'] ?>" class="btn-icon-sm" title="تعديل"><i class="fas fa-edit"></i></a>
                                    <a href="<?= BASE_URL ?>/audio.php?slug=<?= urlencode($audio['slug']) ?>" target="_blank" class="btn-icon-sm" title="معاينة"><i class="fas fa-eye"></i></a>
                                    <a href="?action=delete&id=<?= $audio['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn-icon-sm danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذا المقطع؟')"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?= renderPagination($pagination) ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
