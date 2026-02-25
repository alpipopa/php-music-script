<?php
/**
 * صفحة اتصل بنا
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
    $name    = clean($_POST['name'] ?? '');
    $email   = clean($_POST['email'] ?? '');
    $subject = clean($_POST['subject'] ?? '');
    $message = clean($_POST['message'] ?? '');

    if (!$name || !$email || !$message) {
        $error = 'يرجى ملء جميع الحقول المطلوبة.';
    } elseif (!validateEmail($email)) {
        $error = 'البريد الإلكتروني غير صالح.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $subject, $message])) {
            $success = 'تم إرسال رسالتك بنجاح. سنرد عليك في أقرب وقت.';
        } else {
            $error = 'حدث خطأ أثناء إرسال الرسالة. حاول لاحقاً.';
        }
    }
}

$pageTitle = 'اتصل بنا';
require_once __DIR__ . '/includes/header.php';
?>

<div style="background:var(--card-bg); border-bottom:1px solid var(--border); padding:50px 0;">
    <div class="container" style="text-align:center;">
        <h1 style="font-size:2.5rem; font-weight:900; margin-bottom:10px;">اتصل بنا</h1>
        <p style="color:var(--text-muted);">يسعدنا سماع رأيك أو استفسارك في أي وقت</p>
    </div>
</div>

<div class="container" style="margin-top:50px; margin-bottom:100px;">
    <div style="display:grid; grid-template-columns: 1fr 350px; gap:40px;">
        
        <!-- نموذج الاتصال -->
        <div class="card">
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

            <form method="post">
                <?= csrfField() ?>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="contact_name">الاسم الكامل <span class="req">*</span></label>
                        <input type="text" id="contact_name" name="name" class="form-control" required placeholder="مثال: أحمد محمد">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contact_email_input">البريد الإلكتروني <span class="req">*</span></label>
                        <input type="email" id="contact_email_input" name="email" class="form-control" required placeholder="email@example.com">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="contact_subject">الموضوع</label>
                    <input type="text" id="contact_subject" name="subject" class="form-control" placeholder="بخصوص ماذا تريد التواصل؟">
                </div>
                <div class="form-group">
                    <label class="form-label" for="contact_message">الرسالة <span class="req">*</span></label>
                    <textarea id="contact_message" name="message" class="form-control" rows="6" required placeholder="اكتب رسالتك هنا..."></textarea>
                </div>
                <button type="submit" class="btn btn-gold btn-lg" style="width:200px;">
                    <i class="fas fa-paper-plane"></i> إرسال الرسالة
                </button>
            </form>
        </div>

        <!-- معلومات التواصل -->
        <div style="display:flex; flex-direction:column; gap:20px;">
            <div class="card" style="padding:25px;">
                <h3 style="margin-bottom:20px; font-size:1.2rem; border-bottom:1px solid var(--border); padding-bottom:10px;">بيانات التواصل</h3>
                <div style="display:flex; flex-direction:column; gap:20px;">
                    <div style="display:flex; gap:15px; align-items:flex-start;">
                        <div style="color:var(--gold); font-size:1.2rem;"><i class="fas fa-envelope"></i></div>
                        <div>
                            <div style="font-weight:700;">البريد الإلكتروني</div>
                            <div style="color:var(--text-muted);"><?= clean(getSetting('contact_email', 'info@musican.com')) ?></div>
                        </div>
                    </div>
                    <?php if (getSetting('contact_phone')): ?>
                    <div style="display:flex; gap:15px; align-items:flex-start;">
                        <div style="color:var(--gold); font-size:1.2rem;"><i class="fas fa-phone"></i></div>
                        <div>
                            <div style="font-weight:700;">رقم الهاتف</div>
                            <div style="color:var(--text-muted);"><?= clean(getSetting('contact_phone')) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (getSetting('contact_address')): ?>
                    <div style="display:flex; gap:15px; align-items:flex-start;">
                        <div style="color:var(--gold); font-size:1.2rem;"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <div style="font-weight:700;">العنوان</div>
                            <div style="color:var(--text-muted);"><?= clean(getSetting('contact_address')) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- السوشيال ميديا -->
            <div class="card" style="padding:25px;">
                <h3 style="margin-bottom:20px; font-size:1.2rem; border-bottom:1px solid var(--border); padding-bottom:10px;">تابعنا على</h3>
                <div style="display:flex; gap:15px;">
                    <?php if ($fb = getSetting('facebook_url')): ?>
                        <a href="<?= $fb ?>" class="btn-icon" style="background:#1877f2; color:white;"><i class="fab fa-facebook-f"></i></a>
                    <?php endif; ?>
                    <?php if ($tw = getSetting('twitter_url')): ?>
                        <a href="<?= $tw ?>" class="btn-icon" style="background:#1da1f2; color:white;"><i class="fab fa-twitter"></i></a>
                    <?php endif; ?>
                    <?php if ($yt = getSetting('youtube_url')): ?>
                        <a href="<?= $yt ?>" class="btn-icon" style="background:#ff0000; color:white;"><i class="fab fa-youtube"></i></a>
                    <?php endif; ?>
                    <?php if ($ig = getSetting('instagram_url')): ?>
                        <a href="<?= $ig ?>" class="btn-icon" style="background:#e1306c; color:white;"><i class="fab fa-instagram"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
