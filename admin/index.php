<?php
/**
 * مدخل لوحة التحكم
 */
define('MUSICAN_APP', true);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSession();
requireAdmin();
header('Location: ' . BASE_URL . '/admin/dashboard.php');
exit;
