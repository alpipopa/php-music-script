<?php
/**
 * صفحة إنشاء حساب جديد
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
if (getSetting('allow_register', '1') !== '1') {
    die('التسجيل مغلق حالياً من قبل الإدارة.');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $username = clean($_POST['username'] ?? '');
    $fullName = clean($_POST['full_name'] ?? '');
    $email    = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = $_POST['role'] ?? 'user';

    if (!$username || !$email || !$password) {
        $error = 'يرجى ملء جميع الحقول المطلوبة.';
    } elseif ($password !== $confirm) {
        $error = 'كلمتا المرور غير متطابقتين.';
    } else {
        $result = registerUser($username, $email, $password, $role, $fullName);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'إنشاء حساب جديد';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top:100px; margin-bottom:100px; display:flex; justify-content:center;">
    <div class="card" style="width:100%; max-width:500px; padding:40px;">
        <div style="text-align:center; margin-bottom:30px;">
            <h1 style="font-size:1.8rem; font-weight:900; margin-bottom:10px;">إنشاء حساب</h1>
            <p style="color:var(--text-muted);">انضم إلينا واكتشف عالماً من الصوتيات</p>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
            <div style="text-align:center; margin-top:20px;">
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-gold">تسجيل الدخول الآن</a>
            </div>
        <?php else: ?>
            <form method="post">
                <?= csrfField() ?>
                <div class="form-group">
                    <label class="form-label" for="reg_fullname">الاسم بالكامل (للعرض)</label>
                    <input type="text" id="reg_fullname" name="full_name" class="form-control" placeholder="مثال: أحمد محمد" value="<?= clean($fullName ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="reg_username">اسم المستخدم</label>
                    <input type="text" id="reg_username" name="username" class="form-control" required placeholder="مثال: ahmed_99" value="<?= clean($username ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="reg_email">البريد الإلكتروني</label>
                    <input type="email" id="reg_email" name="email" class="form-control" required placeholder="name@example.com" value="<?= clean($email ?? '') ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="reg_password">كلمة المرور</label>
                        <input type="password" id="reg_password" name="password" class="form-control" required placeholder="••••••••">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="reg_confirm">تأكيد كلمة المرور</label>
                        <input type="password" id="reg_confirm" name="confirm_password" class="form-control" required placeholder="••••••••">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 25px;">
                    <label class="form-label">نوع الحساب</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <label class="role-option" style="cursor:pointer; display:block; position:relative;">
                            <input type="radio" name="role" value="user" checked style="position:absolute; opacity:0; pointer-events:none;">
                            <div class="role-box" style="padding:15px; border:2px solid var(--border); border-radius:12px; text-align:center; transition:var(--transition);">
                                <i class="fas fa-user" style="font-size:1.5rem; display:block; margin-bottom:8px;"></i>
                                <span style="font-weight:600; display:block;">مستمع</span>
                                <small style="display:block; color:var(--text-muted); font-size:0.75rem;">للاستماع والمتابعة</small>
                            </div>
                        </label>
                        <label class="role-option" style="cursor:pointer; display:block; position:relative;">
                            <input type="radio" name="role" value="performer" style="position:absolute; opacity:0; pointer-events:none;">
                            <div class="role-box" style="padding:15px; border:2px solid var(--border); border-radius:12px; text-align:center; transition:var(--transition);">
                                <i class="fas fa-microphone" style="font-size:1.5rem; display:block; margin-bottom:8px;"></i>
                                <span style="font-weight:600; display:block;">مؤدٍ</span>
                                <small style="display:block; color:var(--text-muted); font-size:0.75rem;">لنشر صوتياتك الخاصة</small>
                            </div>
                        </label>
                    </div>
                </div>

                <style>
                    .role-option input:checked + .role-box {
                        border-color: var(--gold) !important;
                        background: rgba(212, 175, 55, 0.05);
                        color: var(--gold);
                    }
                    .role-option:hover .role-box {
                        border-color: rgba(212, 175, 55, 0.5);
                    }
                </style>
                
                <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:20px;">بالنقر على "إنشاء حساب"، فإنك توافق على <a href="#" style="color:var(--gold);">شروط الاستخدام</a>.</p>

                <button type="submit" class="btn btn-gold btn-lg" style="width:100%;">
                    إنشاء حساب
                </button>

                <div style="text-align:center; margin-top:30px; border-top:1px solid var(--border); padding-top:20px;">
                    <p style="color:var(--text-muted);">لديك حساب بالفعل؟ <a href="<?= BASE_URL ?>/login.php" style="color:var(--gold); font-weight:700;">تسجيل الدخول</a></p>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
