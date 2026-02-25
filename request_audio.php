<?php
/**
 * صفحة طلب مقطع صوتي
 * Musican
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/templates_loader.php';
startSession();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $title     = clean($_POST['title'] ?? '');
    $performer = clean($_POST['performer'] ?? '');
    $email     = clean($_POST['email'] ?? '');
    $notes     = clean($_POST['notes'] ?? '');

    if (!$title || !$performer) {
        $error = 'يرجى إدخال عنوان المقطع واسم المؤدي.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO audio_requests (audio_title, performer_name, user_email, notes) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$title, $performer, $email, $notes])) {
            $success = 'تم استلام طلبك بنجاح. سنعمل على توفيره في أقرب وقت.';
        } else {
            $error = 'حدث خطأ. حاول لاحقاً.';
        }
    }
}

$pageTitle = 'اطلب مقطعًا صوتيًا';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top:70px; margin-bottom:100px; max-width:700px;">
    <div class="card" style="padding:40px;">
        <div style="text-align:center; margin-bottom:30px;">
            <div style="width:80px; height:80px; background:rgba(212,175,55,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; margin: 0 auto 20px; font-size:2.5rem; color:var(--gold);">
                <i class="fas fa-plus-circle"></i>
            </div>
            <h1 style="font-size:2rem; font-weight:900;">اطلب مقطعًا صوتيًا</h1>
            <p style="color:var(--text-muted);">هل هناك مقطع صوتي غير موجود في الموقع؟ اطلبه الآن وسنوفره لك.</p>
        </div>

        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

        <form method="post">
            <?= csrfField() ?>
            <div class="form-group">
                <label class="form-label" for="req_title">اسم المقطع الصوتي <span class="req">*</span></label>
                <input type="text" id="req_title" name="title" class="form-control" required placeholder="مثال: سورة الرحمن">
            </div>
            <div class="form-group">
                <label class="form-label" for="req_performer">اسم المؤدي <span class="req">*</span></label>
                <input type="text" id="req_performer" name="performer" class="form-control" required placeholder="مثال: مشاري العفاسي">
            </div>
            <div class="form-group">
                <label class="form-label" for="req_email">بريدك الإلكتروني (اختياري لتنبيهك عند التوفر)</label>
                <input type="email" id="req_email" name="email" class="form-control" placeholder="name@example.com">
            </div>
            <div class="form-group">
                <label class="form-label" for="req_notes">ملاحظات إضافية</label>
                <textarea id="req_notes" name="notes" class="form-control" rows="3" placeholder="أي تفاصيل أخرى بخصوص الطلب..."></textarea>
            </div>
            <button type="submit" class="btn btn-gold btn-lg" style="width:100%;">
                إرسال الطلب
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
