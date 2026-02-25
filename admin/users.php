<?php
/**
 * إدارة المستخدمين - Admin
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
startSession();
requireAdmin();

$action = clean($_GET['action'] ?? '');
$userId = (int)($_GET['id'] ?? 0);
$db     = getDB();
$error  = '';
$editUser = null;

// حظر / إلغاء الحظر
if ($action === 'toggle_active' && $userId) {
    $db->prepare("UPDATE users SET is_active = IF(is_active=1,0,1) WHERE id=?")->execute([$userId]);
    setFlash('success', 'تم تحديث حالة المستخدم.');
    redirect(BASE_URL . '/admin/users.php');
}

// حذف
if ($action === 'delete' && $userId) {
    checkCsrf();
    if ($userId === getCurrentUserId()) { setFlash('error', 'لا يمكنك حذف حسابك الخاص.'); redirect(BASE_URL . '/admin/users.php'); }
    $db->prepare("DELETE FROM users WHERE id=?")->execute([$userId]);
    setFlash('success', 'تم حذف المستخدم.');
    redirect(BASE_URL . '/admin/users.php');
}

// تغيير الدور
if ($action === 'change_role' && $userId) {
    checkCsrf();
    $newRole = in_array($_POST['role'] ?? '', ['user','performer','admin']) ? $_POST['role'] : 'user';
    $db->prepare("UPDATE users SET role=? WHERE id=?")->execute([$newRole, $userId]);

    // إذا تم تحويله لمؤدي، نتأكد من وجود سجل له في جدول performers
    if ($newRole === 'performer') {
        $check = $db->prepare("SELECT id FROM performers WHERE user_id = ?");
        $check->execute([$userId]);
        if (!$check->fetch()) {
            $u = $db->prepare("SELECT username FROM users WHERE id = ?");
            $u->execute([$userId]);
            $userData = $u->fetch();
            $slug = generateSlug($userData['username'], 'performers');
            $db->prepare("INSERT INTO performers (user_id, name, slug) VALUES (?, ?, ?)")
               ->execute([$userId, $userData['username'], $slug]);
        }
    }

    setFlash('success', 'تم تحديث دور المستخدم.');
    redirect(BASE_URL . '/admin/users.php');
}

$search  = clean($_GET['search'] ?? '');
$role    = clean($_GET['role'] ?? '');
$where   = [];
$params  = [];
$where[] = '1=1';
if ($search) { $where[] = '(username LIKE ? OR email LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($role)   { $where[] = 'role=?'; $params[] = $role; }

$sql   = "SELECT * FROM users WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC LIMIT 50";
$stmt  = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'إدارة المستخدمين';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="card">
    <div class="card-header">
        <div style="display:flex;gap:10px;">
            <form method="get" style="display:flex;gap:8px;">
                <input type="text" name="search" value="<?= clean($search) ?>" placeholder="بحث بالاسم أو الإيميل..." class="form-control" style="width:200px;">
                <select name="role" class="filter-select">
                    <option value="">كل الأدوار</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>مديرون</option>
                    <option value="performer" <?= $role === 'performer' ? 'selected' : '' ?>>مؤدون</option>
                    <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>مستخدمون</option>
                </select>
                <button type="submit" class="btn btn-ghost btn-sm"><i class="fas fa-search"></i></button>
            </form>
        </div>
        <span style="color:var(--text-muted);font-size:0.9rem;"><?= count($users) ?> مستخدم</span>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($users)): ?>
            <div class="empty-state"><div class="icon">👥</div><h3>لا يوجد مستخدمون</h3></div>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>المستخدم</th><th>الاسم الكامل</th><th>الدور</th><th>الحالة</th><th>تاريخ التسجيل</th><th>إجراءات</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <img src="<?= getUserAvatarUrl($u) ?>" alt="" style="width:32px;height:32px;object-fit:cover;border-radius:50%;">
                                    <span style="font-weight:600;"><?= clean($u['username']) ?></span>
                                </div>
                            </td>
                            <td><?= clean($u['full_name'] ?? '—') ?></td>
                            <td>
                                <form method="post" action="?action=change_role&id=<?= $u['id'] ?>" style="display:inline;">
                                    <?= csrfField() ?>
                                    <select name="role" onchange="this.form.submit()" class="filter-select" style="padding:2px 5px; font-size:0.8rem;">
                                        <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>مستخدم</option>
                                        <option value="performer" <?= $u['role'] === 'performer' ? 'selected' : '' ?>>مؤدي</option>
                                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>مدير</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <a href="?action=toggle_active&id=<?= $u['id'] ?>" class="badge <?= $u['is_active'] ? 'badge-success' : 'badge-danger' ?>" style="text-decoration:none;">
                                    <?= $u['is_active'] ? 'نشط' : 'محظور' ?>
                                </a>
                            </td>
                            <td style="font-size:0.85rem; color:var(--text-muted);"><?= date('Y-m-d', strtotime($u['created_at'])) ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?= BASE_URL ?>/profile.php?id=<?= $u['id'] ?>" target="_blank" class="btn-icon-sm" title="عرض الملف"><i class="fas fa-eye"></i></a>
                                    <?php if ($u['id'] !== getCurrentUserId()): ?>
                                        <a href="?action=delete&id=<?= $u['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn-icon-sm danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم نهائياً؟')"><i class="fas fa-trash"></i></a>
                                    <?php endif; ?>
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
