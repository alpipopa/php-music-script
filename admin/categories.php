<?php
/**
 * إدارة الأقسام - Admin
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
startSession();
requireAdmin();

$action = clean($_GET['action'] ?? '');
$catId  = (int)($_GET['id'] ?? 0);
$db     = getDB();
$error  = '';
$editCat = null;

// حذف
if ($action === 'delete' && $catId) {
    checkCsrf();
    $db->prepare("DELETE FROM categories WHERE id=?")->execute([$catId]);
    setFlash('success', 'تم حذف القسم.');
    redirect(BASE_URL . '/admin/categories.php');
}

// إضافة / تعديل
if (in_array($action, ['add', 'edit'])) {
    if ($action === 'edit' && $catId) {
        $stmt = $db->prepare("SELECT * FROM categories WHERE id=?");
        $stmt->execute([$catId]);
        $editCat = $stmt->fetch();
        if (!$editCat) { setFlash('error', 'القسم غير موجود.'); redirect(BASE_URL . '/admin/categories.php'); }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
        checkCsrf();
        $name       = clean($_POST['name'] ?? '');
        $icon       = clean($_POST['icon'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $slug       = generateSlug($name);

        if (!$name) { $error = 'يرجى إدخال اسم القسم.'; }
        else {
            if ($action === 'add') {
                $db->prepare("INSERT INTO categories (name, slug, icon, sort_order) VALUES (?,?,?,?)")
                   ->execute([$name, $slug, $icon, $sort_order]);
                setFlash('success', 'تم إضافة القسم.');
            } else {
                $db->prepare("UPDATE categories SET name=?, slug=?, icon=?, sort_order=? WHERE id=?")
                   ->execute([$name, $slug, $icon, $sort_order, $catId]);
                setFlash('success', 'تم تحديث القسم.');
            }
            redirect(BASE_URL . '/admin/categories.php');
        }
    }
}

$categories = getCategories();
$pageTitle  = $action === 'add' ? 'إضافة قسم' : ($action === 'edit' ? 'تعديل القسم' : 'إدارة الأقسام');

require_once __DIR__ . '/includes/admin_header.php';
?>

<?php if (in_array($action, ['add', 'edit'])): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title-sm"><?= $action === 'add' ? 'إضافة قسم جديد' : 'تعديل القسم' ?></h2>
        <a href="<?= BASE_URL ?>/admin/categories.php" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-right"></i> رجوع</a>
    </div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-error"><?= clean($error) ?></div><?php endif; ?>
        <form method="post">
            <?= csrfField() ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">اسم القسم <span class="req">*</span></label>
                    <input type="text" name="name" class="form-control" required value="<?= clean($editCat['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">أيقونة (Font Awesome class)</label>
                    <input type="text" name="icon" class="form-control" value="<?= clean($editCat['icon'] ?? '') ?>" placeholder="fas fa-music">
                    <p style="font-size:0.78rem;color:var(--text-muted);margin-top:4px;">مثل: fas fa-quran, fas fa-music, fas fa-headphones</p>
                </div>
                <div class="form-group">
                    <label class="form-label">ترتيب العرض</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= $editCat['sort_order'] ?? 0 ?>" min="0">
                </div>
            </div>
            <div style="display:flex;gap:12px;">
                <button type="submit" name="save_category" class="btn btn-gold btn-lg"><i class="fas fa-save"></i> حفظ</button>
                <a href="<?= BASE_URL ?>/admin/categories.php" class="btn btn-ghost btn-lg">إلغاء</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="card-header">
        <span style="color:var(--text-muted);font-size:0.9rem;"><?= count($categories) ?> قسم</span>
        <a href="?action=add" class="btn btn-gold btn-sm"><i class="fas fa-plus"></i> إضافة قسم</a>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($categories)): ?>
            <div class="empty-state"><div class="icon">📂</div><h3>لا توجد أقسام</h3></div>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>القسم</th><th>الأيقونة</th><th>المقاطع</th><th>الترتيب</th><th>إجراءات</th></tr></thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/category.php?slug=<?= urlencode($cat['slug']) ?>" target="_blank" style="font-weight:600;color:var(--gold);"><?= clean($cat['name']) ?></a>
                            </td>
                            <td><?= $cat['icon'] ? '<i class="' . clean($cat['icon']) . '"></i>' : '—' ?></td>
                            <td><?= $cat['audios_count'] ?></td>
                            <td><?= $cat['sort_order'] ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="?action=edit&id=<?= $cat['id'] ?>" class="btn-icon-sm"><i class="fas fa-edit"></i></a>
                                    <a href="?action=delete&id=<?= $cat['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn-icon-sm danger" onclick="return confirm('حذف هذا القسم؟')"><i class="fas fa-trash"></i></a>
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
