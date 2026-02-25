<?php
/**
 * صفحة تعديل الملف الشخصي
 * Musican
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/templates_loader.php';
startSession();
requireLogin();

$currentUser = getCurrentUser();
if (!$currentUser) redirect(BASE_URL . '/login.php');

$userId     = $currentUser['id'];
$activeTab  = in_array($_GET['tab'] ?? '', ['profile', 'password', 'avatar']) ? ($_GET['tab']) : 'profile';
$success    = '';
$error      = '';

/* ===== معالجة الفورم ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $formType = $_POST['form_type'] ?? '';

    // تعديل البيانات الأساسية
    if ($formType === 'profile') {
        $activeTab = 'profile';
        $result = updateUserProfile($userId, $_POST, $_FILES['avatar'] ?? []);
        if ($result['success']) {
            $success = $result['message'];
            // إعادة تحميل بيانات المستخدم من الجلسة
            $currentUser = getCurrentUser();
        } else {
            $error = $result['message'];
        }
    }

    // تغيير كلمة المرور
    if ($formType === 'password') {
        $activeTab = 'password';
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if ($new !== $confirm) {
            $error = 'كلمتا المرور الجديدتان غير متطابقتين.';
        } else {
            $result = changePassword($userId, $current, $new);
            $success = $result['success'] ? $result['message'] : '';
            $error   = $result['success'] ? '' : $result['message'];
        }
    }

    // حذف الصورة الشخصية
    if ($formType === 'remove_avatar') {
        $activeTab = 'profile';
        $db = getDB();
        $stmt = $db->prepare("SELECT avatar FROM users WHERE id=?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if ($row && $row['avatar']) {
            deleteFile('avatars/' . $row['avatar']);
            $db->prepare("UPDATE users SET avatar=NULL WHERE id=?")->execute([$userId]);
            $_SESSION['avatar'] = null;
            $success = 'تم حذف الصورة الشخصية.';
            $currentUser = getCurrentUser();
        }
    }
}

// صورة المستخدم الحالية
$avatarUrl = '';
if (!empty($currentUser['avatar'])) {
    $avatarUrl = getImageUrl('avatars', $currentUser['avatar']);
} else {
    $hash = md5(strtolower(trim($currentUser['email'] ?? '')));
    $avatarUrl = "https://www.gravatar.com/avatar/{$hash}?s=200&d=identicon";
}

$pageTitle = 'تعديل الملف الشخصي';
$siteName  = getSetting('site_name', 'موسيكان');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) . ' | ' . clean($siteName) ?></title>
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <style>
        .edit-page { max-width: 800px; margin: 40px auto; padding: 0 20px 60px; }
        .edit-header { display: flex; align-items: center; gap: 20px; margin-bottom: 32px; }
        .edit-avatar-sm {
            width: 72px; height: 72px; border-radius: 50%;
            border: 3px solid var(--gold); object-fit: cover; flex-shrink: 0;
        }
        .edit-tabs {
            display: flex; gap: 4px;
            border-bottom: 2px solid rgba(212,175,55,.2);
            margin-bottom: 28px;
        }
        .edit-tab {
            padding: 10px 20px; background: none; border: none;
            color: var(--text-muted); font-size: 0.9rem; font-weight: 600;
            cursor: pointer; transition: all .2s; font-family: inherit;
            border-bottom: 2px solid transparent; margin-bottom: -2px;
            display: flex; align-items: center; gap: 7px;
        }
        .edit-tab:hover { color: var(--text); }
        .edit-tab.active { color: var(--gold); border-bottom-color: var(--gold); }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }
        .form-card {
            background: rgba(20,25,50,.7);
            border: 1px solid rgba(212,175,55,.15);
            border-radius: 14px; padding: 28px;
        }
        .avatar-upload-area {
            display: flex; align-items: center; gap: 24px;
            padding: 20px; background: rgba(0,0,0,.2);
            border-radius: 12px; margin-bottom: 24px;
            border: 1px dashed rgba(212,175,55,.3);
        }
        .avatar-preview {
            width: 100px; height: 100px; border-radius: 50%;
            object-fit: cover; border: 3px solid var(--gold);
            box-shadow: 0 0 20px rgba(212,175,55,.2); flex-shrink: 0;
        }
        .avatar-upload-info h4 { font-size: 0.95rem; margin-bottom: 6px; }
        .avatar-upload-info p { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 12px; }
        .strength-bar {
            height: 4px; border-radius: 2px; margin-top: 6px;
            transition: all .3s; width: 0;
        }
        .strength-bar.weak   { background: #e74c3c; width: 33%; }
        .strength-bar.medium { background: #f39c12; width: 66%; }
        .strength-bar.strong { background: #27ae60; width: 100%; }
        .req-item { font-size: 0.78rem; display: flex; align-items:center; gap:6px; margin-top:3px; }
        .req-item.ok   { color: #27ae60; }
        .req-item.fail { color: var(--text-muted); }
        .form-label { display: block; margin-bottom: 8px; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); }
        .form-control {
            width: 100%; padding: 11px 14px;
            background: rgba(0,0,0,.3); border: 1px solid rgba(212,175,55,.2);
            border-radius: 8px; color: var(--text); font-family: inherit;
            font-size: 0.9rem; transition: all .2s;
        }
        .form-control:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px rgba(212,175,55,.1); }
        .form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 600px) { .form-row-2 { grid-template-columns: 1fr; } .avatar-upload-area { flex-direction: column; text-align: center; } }
        .form-group { margin-bottom: 20px; }
        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; display: flex; align-items: flex-start; gap: 10px; border-right: 3px solid; }
        .alert-success { background: rgba(39,174,96,.1); border-color: #27ae60; color: #2ecc71; }
        .alert-error   { background: rgba(231,76,60,.1); border-color: #e74c3c; color: #e74c3c; }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="edit-page">

    <!-- Header -->
    <div class="edit-header">
        <?php $displayName = !empty($currentUser['full_name']) ? $currentUser['full_name'] : $currentUser['username']; ?>
        <img src="<?= clean($avatarUrl) ?>" alt="<?= clean($displayName) ?>"
             class="edit-avatar-sm" id="header-avatar"
             onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($displayName) ?>&background=d4af37&color=0d0d1a&size=200'">
        <div>
            <h1 style="font-size:1.4rem;font-weight:900;margin-bottom:4px;"><?= clean($displayName) ?></h1>
            <a href="<?= BASE_URL ?>/profile.php" style="font-size:0.85rem;color:var(--gold);">
                <i class="fas fa-arrow-right"></i> عرض الملف الشخصي
            </a>
        </div>
    </div>

    <!-- Tabs -->
    <div class="edit-tabs">
        <button class="edit-tab <?= $activeTab === 'profile' ? 'active' : '' ?>"
                onclick="switchTab('profile')">
            <i class="fas fa-user"></i> البيانات الأساسية
        </button>
        <button class="edit-tab <?= $activeTab === 'password' ? 'active' : '' ?>"
                onclick="switchTab('password')">
            <i class="fas fa-key"></i> كلمة المرور
        </button>
    </div>

    <!-- ===== تبويب البيانات الأساسية ===== -->
    <div class="tab-panel <?= $activeTab === 'profile' ? 'active' : '' ?>" id="tab-profile">

        <?php if ($success && $activeTab === 'profile'): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= clean($success) ?></div>
        <?php endif; ?>
        <?php if ($error && $activeTab === 'profile'): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= clean($error) ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="post" enctype="multipart/form-data" id="profile-form">
                <?= csrfField() ?>
                <input type="hidden" name="form_type" value="profile">

                <!-- صورة المستخدم -->
                <div class="avatar-upload-area">
                    <img src="<?= clean($avatarUrl) ?>" class="avatar-preview" id="avatar-preview"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($currentUser['username']) ?>&background=d4af37&color=0d0d1a&size=200'">
                    <div class="avatar-upload-info">
                        <h4><i class="fas fa-camera" style="color:var(--gold);"></i> الصورة الشخصية</h4>
                        <p>JPG, PNG, WebP - بحد أقصى 3 ميغابايت<br>الأبعاد الموصى بها: 200×200 بكسل</p>
                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                            <label for="avatar" class="btn btn-gold" style="cursor:pointer;font-size:0.85rem;padding:8px 16px;">
                                <i class="fas fa-upload"></i> رفع صورة
                            </label>
                            <input type="file" id="avatar" name="avatar" accept="image/*" style="display:none;" onchange="previewAvatar(this)">
                            <?php if (!empty($currentUser['avatar'])): ?>
                                <button type="button" class="btn" onclick="if(confirm('حذف الصورة الشخصية؟')) document.getElementById('remove-avatar-form').submit();" style="background:rgba(231,76,60,.1);color:#e74c3c;border:1px solid rgba(231,76,60,.3);font-size:0.85rem;padding:8px 16px;">
                                    <i class="fas fa-trash"></i> حذف الصورة
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- البيانات -->
                <div class="form-group">
                    <label class="form-label" for="full_name">الاسم بالكامل (للعرض)</label>
                    <input type="text" id="full_name" name="full_name" class="form-control"
                           maxlength="100" placeholder="مثال: محمد أحمد"
                           value="<?= clean($currentUser['full_name'] ?? '') ?>">
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label" for="username">اسم المستخدم <span style="color:#e74c3c;">*</span></label>
                        <input type="text" id="username" name="username" class="form-control"
                               required minlength="3" maxlength="50"
                               value="<?= clean($currentUser['username']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">البريد الإلكتروني <span style="color:#e74c3c;">*</span></label>
                        <input type="email" id="email" name="email" class="form-control"
                               required value="<?= clean($currentUser['email']) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="bio">نبذة شخصية</label>
                    <textarea id="bio" name="bio" class="form-control" rows="3"
                              placeholder="اكتب نبذة قصيرة عنك..." maxlength="500"><?= clean($currentUser['bio'] ?? '') ?></textarea>
                    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;" id="bio-count">0 / 500 حرف</div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label" for="phone">رقم الهاتف</label>
                        <div style="position:relative;">
                            <input type="tel" id="phone" name="phone" class="form-control"
                                   placeholder="+966 5xx xxx xxxx"
                                   value="<?= clean($currentUser['phone'] ?? '') ?>" style="padding-right:44px;">
                            <i class="fas fa-phone" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none;"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="website">الموقع الإلكتروني</label>
                        <div style="position:relative;">
                            <input type="url" id="website" name="website" class="form-control"
                                   placeholder="https://yoursite.com"
                                   value="<?= clean($currentUser['website'] ?? '') ?>" style="padding-right:44px;">
                            <i class="fas fa-globe" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none;"></i>
                        </div>
                    </div>
                </div>

                <div style="display:flex;gap:12px;margin-top:8px;">
                    <button type="submit" class="btn btn-gold btn-lg">
                        <i class="fas fa-save"></i> حفظ التغييرات
                    </button>
                    <a href="<?= BASE_URL ?>/profile.php" class="btn btn-lg" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:var(--text-muted);">
                        إلغاء
                    </a>
                </div>
            </form>

            <form id="remove-avatar-form" method="post" style="display:none;">
                <?= csrfField() ?>
                <input type="hidden" name="form_type" value="remove_avatar">
            </form>
        </div>
    </div>

    <!-- ===== تبويب كلمة المرور ===== -->
    <div class="tab-panel <?= $activeTab === 'password' ? 'active' : '' ?>" id="tab-password">

        <?php if ($success && $activeTab === 'password'): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= clean($success) ?></div>
        <?php endif; ?>
        <?php if ($error && $activeTab === 'password'): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= clean($error) ?></div>
        <?php endif; ?>

        <div class="form-card">
            <h3 style="font-size:1rem;margin-bottom:20px;color:var(--gold);display:flex;align-items:center;gap:8px;"><i class="fas fa-shield-alt"></i> تغيير كلمة المرور</h3>
            <form method="post" id="pwd-form">
                <?= csrfField() ?>
                <input type="hidden" name="form_type" value="password">

                <div class="form-group">
                    <label class="form-label" for="current_password">كلمة المرور الحالية</label>
                    <div style="position:relative;">
                        <input type="password" id="current_password" name="current_password" class="form-control"
                               required placeholder="••••••••" style="padding-right:44px;padding-left:44px;">
                        <i class="fas fa-lock" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none;"></i>
                        <button type="button" onclick="togglePwd('current_password','eye-c')"
                                style="position:absolute;left:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;padding:4px;">
                            <i class="fas fa-eye" id="eye-c"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_password">كلمة المرور الجديدة</label>
                    <div style="position:relative;">
                        <input type="password" id="new_password" name="new_password" class="form-control"
                               required minlength="8" placeholder="8 أحرف على الأقل"
                               style="padding-right:44px;padding-left:44px;"
                               oninput="checkStrength(this.value)">
                        <i class="fas fa-lock" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none;"></i>
                        <button type="button" onclick="togglePwd('new_password','eye-n')"
                                style="position:absolute;left:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;padding:4px;">
                            <i class="fas fa-eye" id="eye-n"></i>
                        </button>
                    </div>
                    <div class="strength-bar" id="str-bar" style="display:none;"></div>
                    <div id="str-reqs" style="display:none;margin-top:8px;">
                        <div class="req-item fail" id="req-len"><i class="fas fa-times-circle"></i> 8 أحرف على الأقل</div>
                        <div class="req-item fail" id="req-num"><i class="fas fa-times-circle"></i> يحتوي على رقم</div>
                        <div class="req-item fail" id="req-spec"><i class="fas fa-times-circle"></i> حرف كبير أو رمز خاص</div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">تأكيد كلمة المرور الجديدة</label>
                    <div style="position:relative;">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                               required placeholder="أعد كتابة كلمة المرور الجديدة"
                               style="padding-right:44px;padding-left:44px;"
                               oninput="checkMatch()">
                        <i class="fas fa-lock" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none;"></i>
                        <button type="button" onclick="togglePwd('confirm_password','eye-confirm')"
                                style="position:absolute;left:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;padding:4px;">
                            <i class="fas fa-eye" id="eye-confirm"></i>
                        </button>
                    </div>
                    <div id="match-msg" style="font-size:0.78rem;margin-top:4px;display:none;"></div>
                </div>

                <button type="submit" class="btn btn-gold btn-lg">
                    <i class="fas fa-shield-alt"></i> تغيير كلمة المرور
                </button>
            </form>
        </div>
    </div>

</div><!-- /edit-page -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
/* ===== Tabs ===== */
function switchTab(name) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.edit-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    event.currentTarget.classList.add('active');
    history.replaceState(null, '', '?tab=' + name);
}

/* ===== Avatar Preview ===== */
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('avatar-preview').src = e.target.result;
            document.getElementById('header-avatar') && (document.getElementById('header-avatar').src = e.target.result);
        };
        reader.readAsDataURL(input.files[0]);
    }
}

/* ===== Bio counter ===== */
const bioEl = document.getElementById('bio');
const bioCount = document.getElementById('bio-count');
if (bioEl) {
    const update = () => bioCount.textContent = bioEl.value.length + ' / 500 حرف';
    bioEl.addEventListener('input', update);
    update();
}

/* ===== Password toggle ===== */
function togglePwd(id, eyeId) {
    const inp = document.getElementById(id);
    const eye = document.getElementById(eyeId);
    inp.type  = inp.type === 'password' ? 'text' : 'password';
    eye.className = inp.type === 'text' ? 'fas fa-eye-slash' : 'fas fa-eye';
}

/* ===== Password Strength ===== */
function checkStrength(val) {
    const bar  = document.getElementById('str-bar');
    const reqs = document.getElementById('str-reqs');
    if (!val) { bar.style.display = 'none'; reqs.style.display = 'none'; return; }
    bar.style.display = 'block'; reqs.style.display = 'block';
    const len  = val.length >= 8;
    const num  = /\d/.test(val);
    const spec = /[A-Z!@#$%^&*]/.test(val);
    setReq('req-len', len); setReq('req-num', num); setReq('req-spec', spec);
    const score = [len, num, spec].filter(Boolean).length;
    bar.className = 'strength-bar ' + (score <= 1 ? 'weak' : score === 2 ? 'medium' : 'strong');
}

function setReq(id, ok) {
    const el = document.getElementById(id);
    el.className = 'req-item ' + (ok ? 'ok' : 'fail');
    el.querySelector('i').className = ok ? 'fas fa-check-circle' : 'fas fa-times-circle';
}

function checkMatch() {
    const p1  = document.getElementById('new_password').value;
    const p2  = document.getElementById('confirm_password').value;
    const msg = document.getElementById('match-msg');
    if (!p2) { msg.style.display = 'none'; return; }
    msg.style.display = 'block';
    msg.innerHTML = p1 === p2
        ? '<span style="color:#27ae60;"><i class="fas fa-check"></i> كلمتا المرور متطابقتان</span>'
        : '<span style="color:#e74c3c;"><i class="fas fa-times"></i> كلمتا المرور غير متطابقتين</span>';
}

document.getElementById('pwd-form')?.addEventListener('submit', function(e) {
    const p1 = document.getElementById('new_password').value;
    const p2 = document.getElementById('confirm_password').value;
    if (p1 !== p2) { e.preventDefault(); alert('كلمتا المرور الجديدتان غير متطابقتين!'); }
});
</script>
</body>
</html>
