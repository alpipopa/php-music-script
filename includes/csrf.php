<?php
/**
 * نظام CSRF للحماية من الهجمات
 */

if (!defined('MUSICAN_APP')) die('Access Denied');

function generateCsrfToken(): string {
    startSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): bool {
    startSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

function checkCsrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!validateCsrfToken($token)) {
            setFlash('error', 'طلب غير صالح. يرجى المحاولة مرة أخرى.');
            redirect($_SERVER['HTTP_REFERER'] ?? BASE_URL);
        }
    }
}
