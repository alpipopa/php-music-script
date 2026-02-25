<?php
/**
 * صفحة نسيت كلمة المرور
 * Musican
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
startSession();

if (isLoggedIn()) redirect(BASE_URL . '/');

$pageTitle = 'نسيت كلمة المرور';
$error     = '';
$success   = '';
$resetLink = ''; // في بيئة التطوير سنعرض الرابط مباشرة

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $email  = clean($_POST['email'] ?? '');
    $result = requestPasswordReset($email);

    if ($result['success']) {
        if (isset($result['token'])) {
            // ===== في بيئة الإنتاج: أرسل الرابط عبر البريد الإلكتروني =====
            // mail($email, 'استعادة كلمة المرور', 'رابط الاستعادة: ' . BASE_URL . '/reset_password.php?token=' . $result['token']);
            //
            // ===== في بيئة التطوير: اعرض الرابط مباشرة =====
            $resetLink = BASE_URL . '/reset_password.php?token=' . $result['token'];
        }
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

$siteName = getSetting('site_name', 'موسيكان');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle . ' | ' . clean($siteName) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <style>
        .dev-link-box {
            background: rgba(212,175,55,0.08);
            border: 1px dashed var(--gold);
            border-radius: 10px;
            padding: 14px 16px;
            margin-top: 16px;
            font-size: 0.82rem;
            word-break: break-all;
        }
        .dev-link-box strong { color: var(--gold); display: block; margin-bottom: 6px; }
        .dev-link-box a { color: var(--gold); }
    </style>
</head>
<body>
<div class="auth-page">
    <div class="auth-card">

        <div class="auth-logo">
            <a href="<?= BASE_URL ?>/" style="font-size:2.2rem;font-weight:900;background:var(--gradient-gold);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
                🎵 <?= clean($siteName) ?>
            </a>
        </div>

        <div style="text-align:center;margin-bottom:28px;">
            <div style="width:64px;height:64px;background:rgba(212,175,55,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.8rem;">🔑</div>
            <h1 class="auth-title">نسيت كلمة المرور؟</h1>
            <p class="auth-subtitle">لا تقلق! أدخل بريدك الإلكتروني وسنرسل لك رابط الاستعادة</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= clean($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= clean($success) ?></div>

            <?php if ($resetLink): ?>
                <div class="dev-link-box">
                    <strong>🛠️ وضع التطوير - رابط الاستعادة:</strong>
                    <a href="<?= clean($resetLink) ?>">انقر هنا لإعادة تعيين كلمة المرور</a>
                    <br><small style="color:var(--text-muted);margin-top:6px;display:block;">في الإنتاج سيُرسل هذا الرابط للبريد الإلكتروني فقط.</small>
                </div>
            <?php endif; ?>

            <div style="text-align:center;margin-top:20px;">
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-gold">
                    <i class="fas fa-sign-in-alt"></i> العودة لتسجيل الدخول
                </a>
            </div>

        <?php else: ?>
            <form method="post" action="" id="forgot-form">
                <?= csrfField() ?>

                <div class="form-group">
                    <label class="form-label" for="email">البريد الإلكتروني</label>
                    <div style="position:relative;">
                        <input type="email" id="email" name="email" class="form-control"
                               placeholder="أدخل بريدك الإلكتروني المسجّل"
                               required value="<?= clean($_POST['email'] ?? '') ?>"
                               style="padding-right:44px;">
                        <i class="fas fa-envelope" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none;"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-gold btn-block btn-lg" id="submit-btn" style="margin-bottom:16px;">
                    <i class="fas fa-paper-plane"></i> إرسال رابط الاستعادة
                </button>
            </form>

            <div class="auth-switch" style="text-align:center;margin-top:12px;">
                تذكرت كلمة المرور؟ <a href="<?= BASE_URL ?>/login.php">تسجيل الدخول</a>
            </div>
        <?php endif; ?>

        <div style="text-align:center;margin-top:16px;">
            <a href="<?= BASE_URL ?>/" style="color:var(--text-muted);font-size:0.85rem;">
                <i class="fas fa-home"></i> العودة للرئيسية
            </a>
        </div>
    </div>
</div>

<script>
document.getElementById('forgot-form')?.addEventListener('submit', function() {
    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جارٍ الإرسال...';
});
</script>
</body>
</html>
