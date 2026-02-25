<?php
/**
 * إدارة مقاطع المؤدي (إضافة/تعديل/حذف)
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
$audioId = (int)($_GET['id'] ?? 0);
$success = '';
$error   = '';

// ====================== حذف مقطع ======================
if ($action === 'delete' && $audioId) {
    checkCsrf();
    // التأكد من ملكية المقطع
    $chk = $db->prepare("SELECT audio_file, cover_image FROM audios WHERE id = ? AND performer_id = ?");
    $chk->execute([$audioId, $perfId]);
    $row = $chk->fetch();
    if ($row) {
        if ($row['audio_file']) deleteFile('audios/' . $row['audio_file']);
        if ($row['cover_image']) deleteFile('albums/' . $row['cover_image']);
        $db->prepare("DELETE FROM audios WHERE id = ?")->execute([$audioId]);
        setFlash('success', 'تم حذف المقطع بنجاح.');
    }
    redirect(BASE_URL . '/performer-panel.php');
}

// ====================== إضافة / تعديل ======================
$editAudio = null;
if (in_array($action, ['add', 'edit'])) {
    if ($action === 'edit' && $audioId) {
        $stmt = $db->prepare("SELECT * FROM audios WHERE id = ? AND performer_id = ?");
        $stmt->execute([$audioId, $perfId]);
        $editAudio = $stmt->fetch();
        if (!$editAudio) { setFlash('error', 'المقطع غير موجود أو لا تملك صلاحية تعديله.'); redirect(BASE_URL . '/performer-panel.php'); }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_audio'])) {
        checkCsrf();

        $title          = clean($_POST['title'] ?? '');
        $description    = clean($_POST['description'] ?? '');
        $lyrics         = clean($_POST['lyrics'] ?? '');
        $category_id    = (int)($_POST['category_id'] ?? 0);
        $album_id       = (int)($_POST['album_id'] ?? 0) ?: null;
        $duration       = clean($_POST['duration'] ?? '');
        $status         = 'published'; // المؤدي مقاطعه تُنشر مباشرة أو يمكن وضعها مسودة
        $allow_download = isset($_POST['allow_download']) ? 1 : 0;
        $slug           = generateSlug($title);

        if (!$title || !$category_id) {
            $error = 'يرجى ملء جميع الحقول المطلوبة.';
        } else {
            // رفع الملف الصوتي
            $audioFile = $editAudio['audio_file'] ?? '';
            if (!empty($_FILES['audio_file']['name'])) {
                $uploadResult = uploadFile($_FILES['audio_file'], 'audios', ['audio/mpeg','audio/mp3','audio/wav','audio/ogg'], 200 * 1024 * 1024);
                if ($uploadResult['success']) {
                    if ($audioFile) deleteFile('audios/' . $audioFile);
                    $audioFile = $uploadResult['filename'];
                } else { $error = $uploadResult['error']; }
            }

            // رفع الغلاف
            $coverImage = $editAudio['cover_image'] ?? '';
            if (!empty($_FILES['cover_image']['name'])) {
                $imgResult = uploadFile($_FILES['cover_image'], 'albums', ['image/jpeg','image/png','image/webp'], 5 * 1024 * 1024);
                if ($imgResult['success']) {
                    if ($coverImage) deleteFile('albums/' . $coverImage);
                    $coverImage = $imgResult['filename'];
                } else { $error = $imgResult['error']; }
            }

            if (!$error) {
                if ($action === 'add') {
                    if (!$audioFile) { $error = 'يرجى رفع ملف صوتي.'; }
                    else {
                        $db->prepare("INSERT INTO audios (title, slug, description, lyrics, audio_file, cover_image, duration, category_id, performer_id, album_id, status, allow_download) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
                           ->execute([$title, $slug, $description, $lyrics, $audioFile, $coverImage, $duration, $category_id, $perfId, $album_id, $status, $allow_download]);
                        setFlash('success', 'تم إضافة المقطع بنجاح.');
                        redirect(BASE_URL . '/performer-panel.php');
                    }
                } else {
                    $db->prepare("UPDATE audios SET title=?, slug=?, description=?, lyrics=?, audio_file=?, cover_image=?, duration=?, category_id=?, album_id=?, allow_download=? WHERE id=? AND performer_id=?")
                       ->execute([$title, $slug, $description, $lyrics, $audioFile, $coverImage, $duration, $category_id, $album_id, $allow_download, $audioId, $perfId]);
                    setFlash('success', 'تم تحديث المقطع بنجاح.');
                    redirect(BASE_URL . '/performer-panel.php');
                }
            }
        }
    }
}

$categories = getCategories();
$albums     = getAlbums(500, 0, $perfId); // ألبومات هذا المؤدي فقط
$pageTitle  = ($action === 'add' ? 'إضافة مقطع' : 'تعديل المقطع');
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top:30px; margin-bottom:50px; max-width:900px;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title-sm"><?= $action === 'add' ? '<i class="fas fa-plus"></i> إضافة مقطع جديد' : '<i class="fas fa-edit"></i> تعديل المقطع' ?></h2>
            <a href="<?= BASE_URL ?>/performer-panel.php" class="btn btn-ghost btn-sm">إلغاء والرجوع</a>
        </div>
        <div class="card-body">
            <?php if ($error): ?><div class="alert alert-error"><?= clean($error) ?></div><?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <?= csrfField() ?>
                <div class="form-group">
                    <label class="form-label" for="audio_title">عنوان المقطع <span class="req">*</span></label>
                    <input type="text" id="audio_title" name="title" class="form-control" required value="<?= clean($editAudio['title'] ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="audio_cat">القسم <span class="req">*</span></label>
                        <select id="audio_cat" name="category_id" class="form-control" required>
                            <option value="">-- اختر قسمًا --</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($editAudio['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= clean($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="audio_album">الألبوم (اختياري)</label>
                        <select id="audio_album" name="album_id" class="form-control">
                            <option value="">-- بدون ألبوم --</option>
                            <?php foreach ($albums as $al): ?>
                                <option value="<?= $al['id'] ?>" <?= ($editAudio['album_id'] ?? '') == $al['id'] ? 'selected' : '' ?>><?= clean($al['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="audio_file_input">الملف الصوتي <?= $action === 'add' ? '<span class="req">*</span>' : '(اتركه فارغاً للإبقاء على القديم)' ?></label>
                        <input type="file" id="audio_file_input" name="audio_file" class="form-control" accept="audio/*">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="audio_duration">مدة المقطع (mm:ss)</label>
                        <input type="text" id="audio_duration" name="duration" class="form-control" value="<?= clean($editAudio['duration'] ?? '') ?>" placeholder="05:30">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="audio_cover">صورة غلاف المقطع (اختياري)</label>
                    <input type="file" id="audio_cover" name="cover_image" class="form-control" accept="image/*">
                </div>

                <div class="form-group">
                    <label class="form-label" for="audio_desc">الوصف</label>
                    <textarea id="audio_desc" name="description" class="form-control" rows="3"><?= clean($editAudio['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="audio_lyrics">الكلمات / النص</label>
                    <textarea id="audio_lyrics" name="lyrics" class="form-control" rows="5"><?= clean($editAudio['lyrics'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="allow_download" value="1" <?= ($editAudio['allow_download'] ?? 1) ? 'checked' : '' ?> style="width:18px; height:18px;">
                        السماح بالتنزيل للجمهور
                    </label>
                </div>

                <div style="margin-top:20px;">
                    <button type="submit" name="save_audio" class="btn btn-gold btn-lg">
                        <i class="fas fa-save"></i> <?= $action === 'add' ? 'نشر المحتوى' : 'تحديث البيانات' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
