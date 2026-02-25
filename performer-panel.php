<?php
/**
 * لوحة تحكم المؤدي
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

// جلب بيانات المؤدي المرتبطة بهذا المستخدم
$stmt = $db->prepare("SELECT * FROM performers WHERE user_id = ?");
$stmt->execute([$currentUser['id']]);
$performer = $stmt->fetch();

if (!$performer) {
    // إصلاح تلقائي: إذا كان المستخدم "مؤدي" ولكن ينقصه سجل في جدول performers
    $performerName = !empty($currentUser['full_name']) ? $currentUser['full_name'] : $currentUser['username'];
    $slug = generateSlug($performerName, 'performers');
    $ins = $db->prepare("INSERT INTO performers (user_id, name, slug) VALUES (?, ?, ?)");
    $ins->execute([$currentUser['id'], $performerName, $slug]);
    
    // جلب البيانات مجدداً بعد الإنشاء
    $stmt->execute([$currentUser['id']]);
    $performer = $stmt->fetch();
}

if (!$performer) {
    setFlash('error', 'حدث خطأ أثناء محاولة تهيئة ملف المؤدي الخاص بك.');
    redirect(BASE_URL . '/profile.php');
}

// إذا كان اسم المؤدي هو نفسه اسم المستخدم ولدى المستخدم اسم كامل، نستخدم الاسم الكامل كاسم عرض فني
if ($performer['name'] === $currentUser['username'] && !empty($currentUser['full_name'])) {
    $performer['name'] = $currentUser['full_name'];
}

// معالجة تحديث البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    checkCsrf();
    $name = clean($_POST['name'] ?? '');
    $bio  = clean($_POST['bio'] ?? '');
    $error = '';

    if (empty($name)) {
        $error = 'اسم المؤدي مطلوب.';
    } else {
        $imgFile = $performer['image'];
        if (!empty($_FILES['image']['name'])) {
            $imgResult = uploadFile($_FILES['image'], 'performers', ['image/jpeg','image/png','image/webp','image/gif'], 5*1024*1024);
            if ($imgResult['success']) {
                if ($imgFile) deleteFile('performers/' . $imgFile);
                $imgFile = $imgResult['filename'];
            } else { $error = $imgResult['error']; }
        }

        if (!$error) {
            $slug = generateSlug($name, 'performers', $performer['id']);
            $upd = $db->prepare("UPDATE performers SET name=?, bio=?, image=?, slug=? WHERE id=?");
            $upd->execute([$name, $bio, $imgFile, $slug, $performer['id']]);
            setFlash('success', 'تم تحديث بيانات ملفك بنجاح.');
            redirect(BASE_URL . '/performer-panel.php?tab=settings');
        } else {
            setFlash('error', $error);
        }
    }
}

// إحصائيات المؤدي
$stats = $db->prepare("SELECT SUM(listens) as total_listens, SUM(downloads) as total_downloads, COUNT(*) as audios_count FROM audios WHERE performer_id = ?");
$stats->execute([$performer['id']]);
$perfStats = $stats->fetch();

// جلب المقاطع الخاصة به
$audios = getAudios(['performer_id' => $performer['id'], 'limit' => 50, 'order' => 'latest']);

// جلب الألبومات الخاصة به
$albumsList = getAlbums(50, 0, $performer['id']);

$activeTab = $_GET['tab'] ?? 'audios';

$pageTitle = 'لوحتي الخاصة - ' . $performer['name'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top:30px; margin-bottom:50px;">
    
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; gap:20px; flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:15px;">
            <img src="<?= getImageUrl('performers', $performer['image']) ?>" alt="" style="width:70px; height:70px; border-radius:50%; object-fit:cover; border:3px solid var(--gold);">
            <div>
                <h1 style="font-size:1.8rem; font-weight:900;"><?= clean($performer['name']) ?></h1>
                <p style="color:var(--text-muted);">أهلاً بك <?= clean($currentUser['full_name'] ?: $currentUser['username']) ?> <i class="fas fa-hand"> </i> هذه لوحتك الفنية الخاصة.</p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="<?= BASE_URL ?>/performer-albums.php?action=add" class="btn btn-outline">
                <i class="fas fa-folder-plus"></i> إنشاء ألبوم
            </a>
            <a href="<?= BASE_URL ?>/performer-audios.php?action=add" class="btn btn-gold">
                <i class="fas fa-plus-circle"></i> إضافة مقطع
            </a>
        </div>
    </div>

    <!-- كروت الإحصائيات -->
    <div class="grid grid-3" style="gap:20px; margin-bottom:40px;">
        <div class="card" style="text-align:center; padding:30px;">
            <div style="font-size:2.5rem; color:var(--gold); margin-bottom:10px;"><i class="fas fa-music"></i></div>
            <div style="font-size:2rem; font-weight:900;"><?= formatNumber($perfStats['audios_count'] ?? 0) ?></div>
            <div style="color:var(--text-muted);">إجمالي المقاطع</div>
        </div>
        <div class="card" style="text-align:center; padding:30px;">
            <div style="font-size:2.5rem; color:#3498db; margin-bottom:10px;"><i class="fas fa-headphones"></i></div>
            <div style="font-size:2rem; font-weight:900;"><?= formatNumber($perfStats['total_listens'] ?? 0) ?></div>
            <div style="color:var(--text-muted);">إجمالي الاستماعات</div>
        </div>
        <div class="card" style="text-align:center; padding:30px;">
            <div style="font-size:2.5rem; color:#2ecc71; margin-bottom:10px;"><i class="fas fa-download"></i></div>
            <div style="font-size:2rem; font-weight:900;"><?= formatNumber($perfStats['total_downloads'] ?? 0) ?></div>
            <div style="color:var(--text-muted);">إجمالي التنزيلات</div>
        </div>
    </div>

    <!-- التبويبات -->
    <div class="tabs" style="margin-bottom:20px; border-bottom:1px solid var(--border); display:flex; gap:30px;">
        <a href="?tab=audios" style="padding:10px 0; text-decoration:none; color:<?= $activeTab == 'audios' ? 'var(--gold)' : 'var(--text-muted)' ?>; border-bottom: 2px solid <?= $activeTab == 'audios' ? 'var(--gold)' : 'transparent' ?>; font-weight:<?= $activeTab == 'audios' ? '700' : '400' ?>;">
            <i class="fas fa-music"></i> مقاطعي
        </a>
        <a href="?tab=albums" style="padding:10px 0; text-decoration:none; color:<?= $activeTab == 'albums' ? 'var(--gold)' : 'var(--text-muted)' ?>; border-bottom: 2px solid <?= $activeTab == 'albums' ? 'var(--gold)' : 'transparent' ?>; font-weight:<?= $activeTab == 'albums' ? '700' : '400' ?>;">
            <i class="fas fa-compact-disc"></i> ألبوماتي
        </a>
        <a href="?tab=settings" style="padding:10px 0; text-decoration:none; color:<?= $activeTab == 'settings' ? 'var(--gold)' : 'var(--text-muted)' ?>; border-bottom: 2px solid <?= $activeTab == 'settings' ? 'var(--gold)' : 'transparent' ?>; font-weight:<?= $activeTab == 'settings' ? '700' : '400' ?>;">
            <i class="fas fa-cog"></i> إعدادات الملف
        </a>
    </div>

    <?php if ($activeTab == 'audios'): ?>
        <!-- قائمة المقاطع -->
        <div class="card">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h2 class="card-title-sm">كل المقاطع</h2>
                <span style="font-size:0.85rem; color:var(--text-muted);"><?= count($audios) ?> مقطع</span>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($audios)): ?>
                    <div style="padding:50px; text-align:center;">
                        <i class="fas fa-music" style="font-size:4rem; opacity:0.1; margin-bottom:20px;"></i>
                        <h3>لم تقم بنشر أي مقاطع بعد</h3>
                        <a href="<?= BASE_URL ?>/performer-audios.php?action=add" class="btn btn-gold" style="margin-top:15px;">إضافة مقطعك الأول</a>
                    </div>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>المقطع</th>
                                <th>الألبوم</th>
                                <th>الاستماع</th>
                                <th>التنزيل</th>
                                <th>الحالة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($audios as $audio): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:12px;">
                                            <img src="<?= getImageUrl('albums', $audio['cover_image'] ?? '') ?>" alt="" style="width:40px; height:40px; border-radius:8px; object-fit:cover;">
                                            <div>
                                                <div style="font-weight:700;"><?= clean($audio['title']) ?></div>
                                                <div style="font-size:0.75rem; color:var(--text-muted);"><?= $audio['duration'] ?: '--:--' ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= $audio['album_title'] ? clean($audio['album_title']) : '<span style="color:var(--text-muted);">بلا ألبوم</span>' ?></td>
                                    <td><?= formatNumber($audio['listens']) ?></td>
                                    <td><?= formatNumber($audio['downloads']) ?></td>
                                    <td>
                                        <span class="badge <?= $audio['status'] === 'published' ? 'badge-success' : 'badge-warning' ?>">
                                            <?= $audio['status'] === 'published' ? 'منشور' : 'مسودة' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="<?= BASE_URL ?>/performer-audios.php?action=edit&id=<?= $audio['id'] ?>" class="btn-icon-sm" title="تعديل"><i class="fas fa-edit"></i></a>
                                            <a href="<?= BASE_URL ?>/audio.php?slug=<?= urlencode($audio['slug']) ?>" target="_blank" class="btn-icon-sm" title="معاينة"><i class="fas fa-eye"></i></a>
                                            <a href="<?= BASE_URL ?>/performer-audios.php?action=delete&id=<?= $audio['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn-icon-sm danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذا المقطع؟')"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($activeTab == 'albums'): ?>
        <!-- قائمة الألبومات -->
        <div class="card">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h2 class="card-title-sm">كل الألبومات</h2>
                <span style="font-size:0.85rem; color:var(--text-muted);"><?= count($albumsList) ?> ألبوم</span>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($albumsList)): ?>
                    <div style="padding:50px; text-align:center;">
                        <i class="fas fa-compact-disc" style="font-size:4rem; opacity:0.1; margin-bottom:20px;"></i>
                        <h3>لا توجد ألبومات بعد</h3>
                        <a href="<?= BASE_URL ?>/performer-albums.php?action=add" class="btn btn-gold" style="margin-top:15px;">إنشاء ألبومك الأول</a>
                    </div>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>الألبوم</th>
                                <th>السنة</th>
                                <th>المقاطع</th>
                                <th>تاريخ الإنشاء</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($albumsList as $album): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:12px;">
                                            <img src="<?= getImageUrl('albums', $album['cover_image'] ?? '') ?>" alt="" style="width:50px; height:50px; border-radius:8px; object-fit:cover;">
                                            <div style="font-weight:700;"><?= clean($album['title']) ?></div>
                                        </div>
                                    </td>
                                    <td><?= $album['year'] ?></td>
                                    <td><?= $album['audios_count'] ?> مقطع</td>
                                    <td><?= date('Y-m-d', strtotime($album['created_at'])) ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="<?= BASE_URL ?>/performer-albums.php?action=edit&id=<?= $album['id'] ?>" class="btn-icon-sm" title="تعديل"><i class="fas fa-edit"></i></a>
                                            <a href="<?= BASE_URL ?>/album.php?slug=<?= urlencode($album['slug']) ?>" target="_blank" class="btn-icon-sm" title="معاينة"><i class="fas fa-eye"></i></a>
                                            <a href="<?= BASE_URL ?>/performer-albums.php?action=delete&id=<?= $album['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn-icon-sm danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذا الألبوم؟ سيتم فك ارتباط المقاطع به.')"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($activeTab == 'settings'): ?>
        <!-- إعدادات ملف المؤدي -->
        <div class="card" style="max-width:800px; margin:0 auto;">
            <div class="card-header">
                <h2 class="card-title-sm">تعديل الملف العام للمؤدي</h2>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label class="form-label">الاسم الفني (يظهر للجمهور)</label>
                        <input type="text" name="name" class="form-control" required value="<?= clean($performer['name']) ?>">
                        <small style="color:var(--text-muted);">هذا الاسم هو ما سيظهر للجمهور في صفحات المقاطع والألبومات.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">الصورة الشخصية للمؤدي</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small style="color:var(--text-muted);">JPG, PNG - يفضل أن تكون مربعة وبجودة عالية.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">النبذة التعريفية</label>
                        <textarea name="bio" class="form-control" rows="5" placeholder="اكتب نبذة عن مسيرتك الفنية..."><?= clean($performer['bio']) ?></textarea>
                    </div>

                    <div style="margin-top:20px;">
                        <button type="submit" class="btn btn-gold btn-lg">حفظ التغييرات</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

</div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
