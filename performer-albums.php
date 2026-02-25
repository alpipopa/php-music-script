<?php
/**
 * إدارة ألبومات المؤدي (إضافة/تعديل/حذف)
 * Musican
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/templates_loader.php';
startSession();
requirePerformer();

$currentUser = getCurrentUser();
$db = getDB();

$stmt = $db->prepare("SELECT * FROM performers WHERE user_id = ?");
$stmt->execute([$currentUser['id']]);
$performer = $stmt->fetch();

if (!$performer) {
    // إصلاح تلقائي لحالة نقص البيانات
    $performerName = !empty($currentUser['full_name']) ? $currentUser['full_name'] : $currentUser['username'];
    $slug = generateSlug($performerName, 'performers');
    $db->prepare("INSERT INTO performers (user_id, name, slug) VALUES (?, ?, ?)")
       ->execute([$currentUser['id'], $performerName, $slug]);
    
    $stmt->execute([$currentUser['id']]);
    $performer = $stmt->fetch();
}

if (!$performer) {
    setFlash('error', 'حدث خطأ في الوصول لبيانات المؤدي.');
    redirect(BASE_URL . '/');
}

$perfId  = $performer['id'];
$action  = clean($_GET['action'] ?? '');
$albumId = (int)($_GET['id'] ?? 0);
$success = '';
$error   = '';

// ====================== حذف ألبوم ======================
if ($action === 'delete' && $albumId) {
    checkCsrf();
    // التأكد من ملكية الألبوم
    $chk = $db->prepare("SELECT cover_image FROM albums WHERE id = ? AND performer_id = ?");
    $chk->execute([$albumId, $perfId]);
    $row = $chk->fetch();
    if ($row) {
        if ($row['cover_image']) deleteFile('albums/' . $row['cover_image']);
        // فك ارتباط الصوتيات بهذا الألبوم (لا نحذف الصوتيات، فقط نجعل ألبومها NULL)
        $db->prepare("UPDATE audios SET album_id = NULL WHERE album_id = ?")->execute([$albumId]);
        $db->prepare("DELETE FROM albums WHERE id = ?")->execute([$albumId]);
        setFlash('success', 'تم حذف الألبوم بنجاح.');
    }
    redirect(BASE_URL . '/performer-panel.php?tab=albums');
}

// ====================== إضافة / تعديل ======================
$editAlbum = null;
if (in_array($action, ['add', 'edit'])) {
    if ($action === 'edit' && $albumId) {
        $stmt = $db->prepare("SELECT * FROM albums WHERE id = ? AND performer_id = ?");
        $stmt->execute([$albumId, $perfId]);
        $editAlbum = $stmt->fetch();
        if (!$editAlbum) { 
            setFlash('error', 'الألبوم غير موجود أو لا تملك صلاحية تعديله.'); 
            redirect(BASE_URL . '/performer-panel.php?tab=albums'); 
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_album'])) {
        checkCsrf();

        $title       = clean($_POST['title'] ?? '');
        $description = clean($_POST['description'] ?? '');
        $year        = (int)($_POST['year'] ?? date('Y'));
        $slug        = generateSlug($title);

        if (!$title) {
            $error = 'يرجى إدخال عنوان الألبوم.';
        } else {
            // رفع الغلاف
            $coverImage = $editAlbum['cover_image'] ?? '';
            if (!empty($_FILES['cover_image']['name'])) {
                $imgResult = uploadFile($_FILES['cover_image'], 'albums', ['image/jpeg','image/png','image/webp'], 5 * 1024 * 1024);
                if ($imgResult['success']) {
                    if ($coverImage) deleteFile('albums/' . $coverImage);
                    $coverImage = $imgResult['filename'];
                } else { $error = $imgResult['error']; }
            }

            if (!$error) {
                if ($action === 'add') {
                    $db->prepare("INSERT INTO albums (title, slug, description, performer_id, year, cover_image) VALUES (?,?,?,?,?,?)")
                       ->execute([$title, $slug, $description, $perfId, $year, $coverImage]);
                    setFlash('success', 'تم إضافة الألبوم بنجاح.');
                } else {
                    $db->prepare("UPDATE albums SET title=?, slug=?, description=?, year=?, cover_image=? WHERE id=? AND performer_id=?")
                       ->execute([$title, $slug, $description, $year, $coverImage, $albumId, $perfId]);
                    setFlash('success', 'تم تحديث الألبوم بنجاح.');
                }
                redirect(BASE_URL . '/performer-panel.php?tab=albums');
            }
        }
    }
}

$pageTitle = ($action === 'add' ? 'إضافة ألبوم جديد' : 'تعديل الألبوم');
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top:30px; margin-bottom:50px; max-width:800px;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title-sm"><?= $action === 'add' ? '<i class="fas fa-plus"></i> إضافة ألبوم جديد' : '<i class="fas fa-edit"></i> تعديل الألبوم' ?></h2>
            <a href="<?= BASE_URL ?>/performer-panel.php?tab=albums" class="btn btn-ghost btn-sm">إلغاء والرجوع</a>
        </div>
        <div class="card-body">
            <?php if ($error): ?><div class="alert alert-error"><?= clean($error) ?></div><?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <?= csrfField() ?>
                <div class="form-group">
                    <label class="form-label">عنوان الألبوم <span class="req">*</span></label>
                    <input type="text" name="title" class="form-control" required value="<?= clean($editAlbum['title'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">سنة الإصدار</label>
                    <input type="number" name="year" class="form-control" value="<?= clean($editAlbum['year'] ?? date('Y')) ?>" min="1900" max="<?= date('Y')+1 ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">صورة الغلاف</label>
                    <input type="file" name="cover_image" class="form-control" accept="image/*">
                    <?php if (!empty($editAlbum['cover_image'])): ?>
                        <img src="<?= getImageUrl('albums', $editAlbum['cover_image']) ?>" alt="" style="width:100px; height:100px; margin-top:10px; border-radius:8px; object-fit:cover;">
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">الوصف / عن الألبوم</label>
                    <textarea name="description" class="form-control" rows="4"><?= clean($editAlbum['description'] ?? '') ?></textarea>
                </div>

                <div style="margin-top:20px;">
                    <button type="submit" name="save_album" class="btn btn-gold btn-lg">
                        <i class="fas fa-save"></i> <?= $action === 'add' ? 'إنشاء الألبوم' : 'حفظ التعديلات' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
