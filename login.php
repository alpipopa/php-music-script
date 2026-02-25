<?php
/**
 * صفحة تسجيل الدخول
 * Musican
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/templates_loader.php';
startSession();

if (isLoggedIn()) redirect(BASE_URL . '/');

$error = '';
$redirect = clean($_GET['redirect'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور.';
    } else {
        $result = loginUser($username, $password);
        if ($result['success']) {
            setFlash('success', 'أهلاً بك مجدداً، ' . $result['user']['username']);
            redirect($redirect ?: BASE_URL . '/');
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'تسجيل الدخول';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top:100px; margin-bottom:100px; display:flex; justify-content:center;">
    <div class="card" style="width:100%; max-width:450px; padding:40px;">
        <div style="text-align:center; margin-bottom:30px;">
            <h1 style="font-size:1.8rem; font-weight:900; margin-bottom:10px;">تسجيل الدخول</h1>
            <p style="color:var(--text-muted);">مرحباً بك في <?= clean(getSetting('site_name', 'موسيكان')) ?></p>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        <?php if ($msg = getFlash('success')): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

        <form method="post">
            <?= csrfField() ?>
            <div class="form-group">
                <label class="form-label" for="username">اسم المستخدم أو البريد الإلكتروني</label>
                <div style="position:relative;">
                    <i class="fas fa-user" style="position:absolute; right:15px; top:15px; color:var(--text-muted);"></i>
                    <input type="text" id="username" name="username" class="form-control" required style="padding-right:45px;" placeholder="Username or Email">
                </div>
            </div>
            <div class="form-group">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <label class="form-label" for="password">كلمة المرور</label>
                    <a href="<?= BASE_URL ?>/forgot_password.php" style="font-size:0.8rem; color:var(--gold); text-decoration:none;">نسيت كلمة المرور؟</a>
                </div>
                <div style="position:relative;">
                    <i class="fas fa-lock" style="position:absolute; right:15px; top:15px; color:var(--text-muted);"></i>
                    <input type="password" id="password" name="password" class="form-control" required style="padding-right:45px;" placeholder="••••••••">
                </div>
            </div>
            
            <button type="submit" class="btn btn-gold btn-lg" style="width:100%; margin-top:20px;">
                دخول
            </button>

            <div style="text-align:center; margin-top:30px; border-top:1px solid var(--border); padding-top:20px;">
                <p style="color:var(--text-muted);">ليس لديك حساب؟ <a href="<?= BASE_URL ?>/register.php" style="color:var(--gold); font-weight:700;">إنشاء حساب جديد</a></p>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
