<?php
/**
 * صفحة إعادة تعيين كلمة المرور
 * Musican
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
startSession();

if (isLoggedIn()) redirect(BASE_URL . '/');

$pageTitle = 'إعادة تعيين كلمة المرور';
$token     = clean($_GET['token'] ?? '');
$error     = '';
$success   = '';

// التحقق من صحة الرمز
$tokenUser = validateResetToken($token);
if (!$tokenUser && empty($success)) {
    $error = 'رابط الاستعادة غير صالح أو انتهت صلاحيته. <a href="' . BASE_URL . '/forgot_password.php">طلب رابط جديد</a>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenUser) {
    checkCsrf();
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        $error = 'كلمتا المرور غير متطابقتين.';
    } else {
        $result = resetPassword($token, $password);
        if ($result['success']) {
            $success = $result['message'];
            $tokenUser = null; // إخفاء النموذج
        } else {
            $error = $result['message'];
        }
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
        .pwd-strength { height: 4px; border-radius: 2px; margin-top: 6px; transition: all .3s; }
        .pwd-strength.weak   { background: #e74c3c; width: 33%; }
        .pwd-strength.medium { background: #f39c12; width: 66%; }
        .pwd-strength.strong { background: #27ae60; width: 100%; }
        .pwd-requirements { font-size: 0.78rem; color: var(--text-muted); margin-top: 6px; }
        .pwd-req { display: flex; align-items: center; gap: 6px; margin-top: 3px; }
        .pwd-req.ok   { color: #27ae60; }
        .pwd-req.fail { color: var(--text-muted); }
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
            <div style="width:64px;height:64px;background:rgba(212,175,55,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.8rem;">🔐</div>
            <h1 class="auth-title">إعادة تعيين كلمة المرور</h1>
            <?php if ($tokenUser): ?>
                <p class="auth-subtitle">أهلاً <strong><?= clean($tokenUser['username']) ?></strong>، أدخل كلمة مرور جديدة</p>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= clean($success) ?></div>
            <div style="text-align:center;margin-top:20px;">
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-gold btn-lg">
                    <i class="fas fa-sign-in-alt"></i> تسجيل الدخول الآن
                </a>
            </div>
        <?php elseif ($tokenUser): ?>
            <form method="post" action="" id="reset-form">
                <?= csrfField() ?>
                <input type="hidden" name="token" value="<?= clean($token) ?>">

                <div class="form-group">
                    <label class="form-label" for="password">كلمة المرور الجديدة</label>
                    <div style="position:relative;">
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="8 أحرف على الأقل" required minlength="8"
                               style="padding-right:44px;padding-left:44px;"
                               oninput="checkStrength(this.value)">
                        <i class="fas fa-lock" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none;"></i>
                        <button type="button" onclick="togglePwd('password','eye1')"
                                style="position:absolute;left:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;padding:4px;">
                            <i class="fas fa-eye" id="eye1"></i>
                        </button>
                    </div>
                    <div class="pwd-strength" id="strength-bar" style="display:none;"></div>
                    <div class="pwd-requirements" id="pwd-reqs" style="display:none;">
                        <div class="pwd-req" id="req-len"><i class="fas fa-circle-xmark"></i> 8 أحرف على الأقل</div>
                        <div class="pwd-req" id="req-num"><i class="fas fa-circle-xmark"></i> يحتوي على رقم</div>
                        <div class="pwd-req" id="req-upper"><i class="fas fa-circle-xmark"></i> حرف كبير (اختياري)</div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">تأكيد كلمة المرور</label>
                    <div style="position:relative;">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                               placeholder="أعد كتابة كلمة المرور" required
                               style="padding-right:44px;padding-left:44px;"
                               oninput="checkMatch()">
                        <i class="fas fa-lock" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none;"></i>
                        <button type="button" onclick="togglePwd('confirm_password','eye2')"
                                style="position:absolute;left:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;padding:4px;">
                            <i class="fas fa-eye" id="eye2"></i>
                        </button>
                    </div>
                    <div id="match-msg" style="font-size:0.78rem;margin-top:4px;display:none;"></div>
                </div>

                <button type="submit" class="btn btn-gold btn-block btn-lg" id="submit-btn" style="margin-bottom:16px;">
                    <i class="fas fa-shield-alt"></i> حفظ كلمة المرور الجديدة
                </button>
            </form>
        <?php endif; ?>

        <div style="text-align:center;margin-top:16px;">
            <a href="<?= BASE_URL ?>/login.php" style="color:var(--text-muted);font-size:0.85rem;">
                <i class="fas fa-arrow-right"></i> العودة لتسجيل الدخول
            </a>
        </div>
    </div>
</div>

<script>
function togglePwd(inputId, eyeId) {
    const inp = document.getElementById(inputId);
    const eye = document.getElementById(eyeId);
    inp.type  = inp.type === 'password' ? 'text' : 'password';
    eye.className = inp.type === 'text' ? 'fas fa-eye-slash' : 'fas fa-eye';
}

function checkStrength(val) {
    const bar  = document.getElementById('strength-bar');
    const reqs = document.getElementById('pwd-reqs');
    if (!val) { bar.style.display = 'none'; reqs.style.display = 'none'; return; }
    bar.style.display  = 'block';
    reqs.style.display = 'block';

    const len   = val.length >= 8;
    const num   = /\d/.test(val);
    const upper = /[A-Z]/.test(val);

    setReq('req-len',   len);
    setReq('req-num',   num);
    setReq('req-upper', upper);

    const score = [len, num, upper].filter(Boolean).length;
    bar.className = 'pwd-strength ' + (score === 1 ? 'weak' : score === 2 ? 'medium' : 'strong');
}

function setReq(id, ok) {
    const el = document.getElementById(id);
    el.className = 'pwd-req ' + (ok ? 'ok' : 'fail');
    el.querySelector('i').className = ok ? 'fas fa-circle-check' : 'fas fa-circle-xmark';
}

function checkMatch() {
    const p1  = document.getElementById('password').value;
    const p2  = document.getElementById('confirm_password').value;
    const msg = document.getElementById('match-msg');
    if (!p2) { msg.style.display = 'none'; return; }
    msg.style.display = 'block';
    if (p1 === p2) {
        msg.innerHTML = '<span style="color:#27ae60;"><i class="fas fa-check"></i> كلمتا المرور متطابقتان</span>';
    } else {
        msg.innerHTML = '<span style="color:#e74c3c;"><i class="fas fa-times"></i> كلمتا المرور غير متطابقتين</span>';
    }
}

document.getElementById('reset-form')?.addEventListener('submit', function(e) {
    const p1 = document.getElementById('password').value;
    const p2 = document.getElementById('confirm_password').value;
    if (p1 !== p2) { e.preventDefault(); alert('كلمتا المرور غير متطابقتين!'); return; }
    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جارٍ الحفظ...';
});
</script>
</body>
</html>
