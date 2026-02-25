<?php
/**
 * صفحة 404 - الصفحة غير موجودة
 */
define('MUSICAN_APP', true);
@require_once __DIR__ . '/config/db.php';
@require_once __DIR__ . '/includes/functions.php';
@require_once __DIR__ . '/includes/auth.php';
@require_once __DIR__ . '/includes/templates_loader.php';
@startSession();

header('HTTP/1.0 404 Not Found');
$pageTitle  = '404 - الصفحة غير موجودة';
$activePage = '';
require_once __DIR__ . '/includes/header.php';
?>
<div class="section" style="text-align:center;min-height:60vh;display:flex;align-items:center;justify-content:center;">
    <div class="container">
        <div style="font-size:8rem;line-height:1;margin-bottom:24px;">🎵</div>
        <h1 style="font-size:6rem;font-weight:900;color:var(--gold);margin-bottom:0;line-height:1;">404</h1>
        <h2 style="font-size:1.5rem;margin-bottom:16px;">الصفحة غير موجودة</h2>
        <p style="color:var(--text-muted);max-width:400px;margin:0 auto 32px;">ربما انتقلت الصفحة لعنوان آخر، أو تم حذفها. جرّب العودة للرئيسية.</p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <a href="<?= BASE_URL ?>/" class="btn btn-gold"><i class="fas fa-home"></i> الرئيسية</a>
            <a href="<?= BASE_URL ?>/player.php" class="btn btn-outline"><i class="fas fa-headphones"></i> الصوتيات</a>
            <a href="javascript:history.back()" class="btn btn-ghost"><i class="fas fa-arrow-right"></i> رجوع</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
