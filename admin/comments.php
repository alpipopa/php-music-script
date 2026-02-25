<?php
/**
 * إدارة التعليقات - Admin
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
startSession();
requireAdmin();

$action    = clean($_GET['action'] ?? '');
$commentId = (int)($_GET['id'] ?? 0);
$db        = getDB();

// حذف
if ($action === 'delete' && $commentId) {
    checkCsrf();
    $db->prepare("DELETE FROM comments WHERE id=?")->execute([$commentId]);
    setFlash('success', 'تم حذف التعليق.');
    redirect(BASE_URL . '/admin/comments.php');
}

// إخفاء / إظهار
if ($action === 'toggle' && $commentId) {
    $db->prepare("UPDATE comments SET status=IF(status='visible','hidden','visible') WHERE id=?")->execute([$commentId]);
    redirect(BASE_URL . '/admin/comments.php');
}

$comments = $db->query("SELECT c.*, u.username, a.title as audio_title, a.slug as audio_slug FROM comments c JOIN users u ON c.user_id=u.id JOIN audios a ON c.audio_id=a.id ORDER BY c.created_at DESC LIMIT 100")->fetchAll();

$pageTitle = 'إدارة التعليقات';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title-sm"><i class="fas fa-comments"></i> التعليقات</h2>
        <span style="color:var(--text-muted);font-size:0.9rem;"><?= count($comments) ?> تعليق</span>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($comments)): ?>
            <div class="empty-state"><div class="icon">💬</div><h3>لا توجد تعليقات</h3></div>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>المستخدم</th><th>المقطع</th><th>التعليق</th><th>الحالة</th><th>التاريخ</th><th>إجراءات</th></tr></thead>
                <tbody>
                    <?php foreach ($comments as $c): ?>
                        <tr>
                            <td style="font-weight:600;"><?= clean($c['username']) ?></td>
                            <td><a href="<?= BASE_URL ?>/audio.php?slug=<?= urlencode($c['audio_slug']) ?>" target="_blank" style="color:var(--gold);"><?= clean(mb_substr($c['audio_title'], 0, 30)) ?></a></td>
                            <td style="font-size:0.85rem;max-width:250px;"><?= clean(mb_substr($c['comment'], 0, 80)) ?><?= mb_strlen($c['comment']) > 80 ? '...' : '' ?></td>
                            <td>
                                <span class="badge <?= $c['status'] === 'visible' ? 'badge-success' : 'badge-warning' ?>">
                                    <?= $c['status'] === 'visible' ? 'ظاهر' : 'مخفي' ?>
                                </span>
                            </td>
                            <td><?= timeAgo($c['created_at']) ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="?action=toggle&id=<?= $c['id'] ?>" class="btn-icon-sm" title="إخفاء/إظهار"><i class="fas fa-eye<?= $c['status'] === 'visible' ? '-slash' : '' ?>"></i></a>
                                    <a href="?action=delete&id=<?= $c['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn-icon-sm danger" onclick="return confirm('حذف هذا التعليق؟')"><i class="fas fa-trash"></i></a>
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
