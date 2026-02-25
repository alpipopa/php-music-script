<?php
/**
 * نظام المصادقة والتحقق من الصلاحيات
 * Musican
 */

if (!defined('MUSICAN_APP')) die('Access Denied');

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn(): bool {
    startSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    startSession();
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isPerformer(): bool {
    startSession();
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'performer' || $_SESSION['role'] === 'admin');
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function getCurrentUserId(): int {
    startSession();
    return (int)($_SESSION['user_id'] ?? 0);
}

function requireLogin(string $redirect = ''): void {
    if (!isLoggedIn()) {
        $back = $redirect ?: $_SERVER['REQUEST_URI'];
        setFlash('error', 'يجب تسجيل الدخول أولاً للوصول إلى هذه الصفحة.');
        redirect(BASE_URL . '/login.php?redirect=' . urlencode($back));
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        setFlash('error', 'ليس لديك صلاحية الوصول لهذه الصفحة.');
        redirect(BASE_URL . '/');
    }
}

function requirePerformer(): void {
    requireLogin();
    if (!isPerformer()) {
        setFlash('error', 'هذه الصفحة مخصصة للمؤدين فقط.');
        redirect(BASE_URL . '/');
    }
}

/* =====================================================
   تسجيل الدخول / الخروج
   ===================================================== */

function loginUser(string $username, string $password): array {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة.'];
    }

    startSession();
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['avatar']    = $user['avatar'];

    return ['success' => true, 'user' => $user];
}

function logoutUser(): void {
    startSession();
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

/* =====================================================
   التسجيل
   ===================================================== */

function registerUser(string $username, string $email, string $password, string $role = 'user', string $fullName = ''): array {
    if (strlen($username) < 3 || strlen($username) > 50) {
        return ['success' => false, 'message' => 'اسم المستخدم يجب أن يكون بين 3 و 50 حرف.'];
    }
    if (!preg_match('/^[\w\-\p{Arabic}]+$/u', $username)) {
        return ['success' => false, 'message' => 'اسم المستخدم يحتوي على رموز غير مسموح بها.'];
    }
    if (!validateEmail($email)) {
        return ['success' => false, 'message' => 'البريد الإلكتروني غير صالح.'];
    }
    if (!validatePassword($password)) {
        return ['success' => false, 'message' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.'];
    }

    // السماح فقط بدور مستخدم أو مؤدٍ عند التسجيل
    $allowedRoles = ['user', 'performer'];
    if (!in_array($role, $allowedRoles)) {
        $role = 'user';
    }

    $db   = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE username=? OR email=?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'اسم المستخدم أو البريد الإلكتروني مسجل مسبقاً.'];
    }

    $hash   = password_hash($password, PASSWORD_BCRYPT, ['cost' => 11]);
    $stmt   = $db->prepare("INSERT INTO users (username, full_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $result = $stmt->execute([$username, $fullName, $email, $hash, $role]);

    if ($result && $role === 'performer') {
        $userId = $db->lastInsertId();
        // استخدام الاسم الكامل كاسم للمؤدي إذا وجد، وإلا اسم المستخدم
        $performerName = $fullName ?: $username;
        $slug   = generateSlug($performerName, 'performers');
        $db->prepare("INSERT INTO performers (user_id, name, slug) VALUES (?, ?, ?)")
           ->execute([$userId, $performerName, $slug]);
    }

    return $result
        ? ['success' => true, 'message' => 'تم إنشاء الحساب بنجاح! يمكنك تسجيل الدخول الآن.']
        : ['success' => false, 'message' => 'حدث خطأ أثناء إنشاء الحساب.'];
}

/* =====================================================
   نسيت كلمة المرور / إعادة التعيين
   ===================================================== */

/**
 * إنشاء رمز الاستعادة وحفظه في قاعدة البيانات
 */
function requestPasswordReset(string $email): array {
    $db   = getDB();
    $stmt = $db->prepare("SELECT id, username FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // رسالة وهمية لعدم الكشف عن وجود الإيميل
        return ['success' => true, 'message' => 'إذا كان البريد مسجلاً ستصلك رسالة باستعادة كلمة المرور.'];
    }

    $token   = bin2hex(random_bytes(32));          // 64 حرف hex
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $db->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?")->execute([$token, $expires, $user['id']]);

    return [
        'success' => true,
        'token'   => $token,
        'user'    => $user,
        'message' => 'إذا كان البريد مسجلاً ستصلك رسالة باستعادة كلمة المرور.',
    ];
}

/**
 * التحقق من صحة رمز الاستعادة
 */
function validateResetToken(string $token): ?array {
    if (empty($token) || strlen($token) !== 64) return null;
    $db   = getDB();
    $stmt = $db->prepare("SELECT id, username, email FROM users WHERE reset_token=? AND reset_expires > NOW() AND is_active=1");
    $stmt->execute([$token]);
    return $stmt->fetch() ?: null;
}

/**
 * تنفيذ إعادة تعيين كلمة المرور
 */
function resetPassword(string $token, string $newPassword): array {
    $user = validateResetToken($token);
    if (!$user) {
        return ['success' => false, 'message' => 'رابط الاستعادة غير صالح أو انتهت صلاحيته.'];
    }
    if (!validatePassword($newPassword)) {
        return ['success' => false, 'message' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.'];
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 11]);
    $db   = getDB();
    $db->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?")->execute([$hash, $user['id']]);

    return ['success' => true, 'message' => 'تم تغيير كلمة المرور بنجاح. يمكنك تسجيل الدخول الآن.'];
}

/* =====================================================
   إعدادات الملف الشخصي
   ===================================================== */

/**
 * تغيير كلمة المرور للمستخدم المسجّل
 */
function changePassword(int $userId, string $currentPassword, string $newPassword): array {
    $db   = getDB();
    $stmt = $db->prepare("SELECT password FROM users WHERE id=?");
    $stmt->execute([$userId]);
    $row  = $stmt->fetch();

    if (!$row || !password_verify($currentPassword, $row['password'])) {
        return ['success' => false, 'message' => 'كلمة المرور الحالية غير صحيحة.'];
    }
    if (!validatePassword($newPassword)) {
        return ['success' => false, 'message' => 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل.'];
    }
    if ($currentPassword === $newPassword) {
        return ['success' => false, 'message' => 'يجب أن تكون كلمة المرور الجديدة مختلفة عن الحالية.'];
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 11]);
    $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $userId]);
    return ['success' => true, 'message' => 'تم تغيير كلمة المرور بنجاح.'];
}

/**
 * تحديث الملف الشخصي مع دعم رفع الصورة
 */
function updateUserProfile(int $userId, array $data, array $avatarFile = []): array {
    $db      = getDB();
    $username = trim($data['username'] ?? '');
    $fullName = trim($data['full_name'] ?? '');
    $email    = trim($data['email'] ?? '');
    $bio      = trim($data['bio'] ?? '');
    $phone    = trim($data['phone'] ?? '');
    $website  = trim($data['website'] ?? '');

    if (strlen($username) < 3 || strlen($username) > 50) {
        return ['success' => false, 'message' => 'اسم المستخدم يجب أن يكون بين 3 و 50 حرف.'];
    }

    if (!validateEmail($email)) {
        return ['success' => false, 'message' => 'البريد الإلكتروني غير صالح.'];
    }

    // التحقق من عدم التكرار مع مستخدمين آخرين
    $dup = $db->prepare("SELECT id FROM users WHERE (username=? OR email=?) AND id != ?");
    $dup->execute([$username, $email, $userId]);
    if ($dup->fetch()) {
        return ['success' => false, 'message' => 'اسم المستخدم أو البريد الإلكتروني مستخدم من حساب آخر.'];
    }

    // رفع الصورة الشخصية
    $avatarName = null;
    if (!empty($avatarFile['name'])) {
        $res = uploadFile($avatarFile, 'avatars', ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], 3 * 1024 * 1024);
        if (!$res['success']) {
            return ['success' => false, 'message' => $res['error'] ?? 'خطأ في رفع الصورة الشخصية.'];
        }
        $avatarName = $res['filename'];
        // حذف الصورة القديمة
        $old = $db->prepare("SELECT avatar FROM users WHERE id=?");
        $old->execute([$userId]);
        $oldRow = $old->fetch();
        if ($oldRow && $oldRow['avatar']) {
            deleteFile('avatars/' . $oldRow['avatar']);
        }
    }

    $sql    = "UPDATE users SET username=?, full_name=?, email=?, bio=?, phone=?, website=?" . ($avatarName ? ", avatar=?" : "") . " WHERE id=?";
    $params = [$username, $fullName, $email, $bio, $phone, $website];
    if ($avatarName) $params[] = $avatarName;
    $params[] = $userId;
    $db->prepare($sql)->execute($params);

    // تحديث بيانات الجلسة
    $_SESSION['username'] = $username;
    $_SESSION['full_name'] = $fullName;
    if ($avatarName) $_SESSION['avatar'] = $avatarName;

    return ['success' => true, 'message' => 'تم تحديث الملف الشخصي بنجاح.'];
}
