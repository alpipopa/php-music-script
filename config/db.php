<?php
/**
 * إعدادات قاعدة البيانات
 * Musican - منصة الصوتيات الاحترافية
 */

// منع الوصول المباشر
if (!defined('MUSICAN_APP')) {
    die('Access Denied');
}

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '1234');
define('DB_NAME', 'music');
define('DB_CHARSET', 'utf8mb4');

// جذر المشروع
define('ROOT_PATH', dirname(__DIR__));
define('BASE_URL', getBaseUrl());

// المسارات
define('UPLOADS_PATH', ROOT_PATH . '/uploads');   // بدون شرطة مائلة في النهاية
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');   // مع شرطة مائلة - للتوافق
define('UPLOAD_URL', BASE_URL . '/uploads/');
define('ASSETS_URL', BASE_URL . '/assets/');
define('DEFAULT_COVER_URL', 'https://images.unsplash.com/photo-1458560871784-56d23406c091?q=80&w=800&auto=format&fit=crop');

// الضبط الزمني
date_default_timezone_set('Asia/Riyadh');

/**
 * الحصول على الرابط الأساسي للموقع
 */
function getBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    // نصعد لجذر المشروع
    $parts = explode('/', trim($script, '/'));
    // نجد اسم المجلد
    $base = '';
    foreach ($parts as $part) {
        if ($part === 'musican') {
            $base = '/musican';
            break;
        }
        $base .= '/' . $part;
    }
    return rtrim($protocol . '://' . $host . $base, '/');
}

/**
 * إنشاء اتصال PDO
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="direction:rtl;font-family:Arial;padding:40px;background:#1a1a2e;color:#e74c3c;text-align:center;">
                <h2>⚠️ خطأ في الاتصال بقاعدة البيانات</h2>
                <p>تأكد من تشغيل MySQL وصحة إعدادات الاتصال.</p>
                <small>' . htmlspecialchars($e->getMessage()) . '</small>
            </div>');
        }
    }
    return $pdo;
}
