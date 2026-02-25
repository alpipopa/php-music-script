<?php
/**
 * صفحة تسجيل الخروج
 */
define('MUSICAN_APP', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
startSession();
logoutUser();
setFlash('success', 'تم تسجيل الخروج بنجاح.');
redirect(BASE_URL);
