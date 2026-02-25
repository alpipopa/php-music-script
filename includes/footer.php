<?php
/**
 * ذيل الموقع - Footer
 * Musican - منصة الصوتيات الاحترافية
 */

if (!defined('MUSICAN_APP')) die('Access Denied');

$footerText      = getSetting('footer_text', 'جميع الحقوق محفوظة © موسيكان');
$contactEmail    = getSetting('contact_email', '');
$contactPhone    = getSetting('contact_phone', '');
$facebookUrl     = getSetting('facebook_url', '');
$twitterUrl      = getSetting('twitter_url', '');
$youtubeUrl      = getSetting('youtube_url', '');
$instagramUrl    = getSetting('instagram_url', '');
$siteName        = getSetting('site_name', 'موسيكان');
$footerCategories = getCategories();
?>

<!-- ===== الفوتر ===== -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">

            <!-- عن الموقع -->
            <div class="footer-brand">
                <div class="logo-text">🎵 <?= clean($siteName) ?></div>
                <p class="footer-desc">منصة الصوتيات العربية الاحترافية. استمع، نزّل، وشارك أفضل التلاوات والأناشيد والمقاطع الصوتية الإسلامية.</p>
                <div class="social-links">
                    <?php if ($facebookUrl): ?>
                        <a href="<?= clean($facebookUrl) ?>" class="social-link" target="_blank" title="فيسبوك"><i class="fab fa-facebook-f"></i></a>
                    <?php endif; ?>
                    <?php if ($twitterUrl): ?>
                        <a href="<?= clean($twitterUrl) ?>" class="social-link" target="_blank" title="تويتر"><i class="fab fa-twitter"></i></a>
                    <?php endif; ?>
                    <?php if ($youtubeUrl): ?>
                        <a href="<?= clean($youtubeUrl) ?>" class="social-link" target="_blank" title="يوتيوب"><i class="fab fa-youtube"></i></a>
                    <?php endif; ?>
                    <?php if ($instagramUrl): ?>
                        <a href="<?= clean($instagramUrl) ?>" class="social-link" target="_blank" title="إنستقرام"><i class="fab fa-instagram"></i></a>
                    <?php endif; ?>
                    <?php if ($contactEmail): ?>
                        <a href="mailto:<?= clean($contactEmail) ?>" class="social-link" title="البريد الإلكتروني"><i class="fas fa-envelope"></i></a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- الأقسام السريعة -->
            <div>
                <h3 class="footer-title">الأقسام</h3>
                <ul class="footer-links">
                    <?php foreach (array_slice($footerCategories, 0, 6) as $cat): ?>
                        <li>
                            <a href="<?= BASE_URL ?>/category.php?slug=<?= urlencode($cat['slug']) ?>">
                                <i class="fas <?= $cat['icon'] ?? 'fa-music' ?>"></i>
                                <?= clean($cat['name']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- روابط سريعة -->
            <div>
                <h3 class="footer-title">روابط سريعة</h3>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/"><i class="fas fa-home"></i> الرئيسية</a></li>
                    <li><a href="<?= BASE_URL ?>/player.php"><i class="fas fa-headphones"></i> الصوتيات</a></li>
                    <li><a href="<?= BASE_URL ?>/performer.php"><i class="fas fa-microphone"></i> المؤدون</a></li>
                    <li><a href="<?= BASE_URL ?>/album.php"><i class="fas fa-compact-disc"></i> الألبومات</a></li>
                    <li><a href="<?= BASE_URL ?>/request_audio.php"><i class="fas fa-plus-circle"></i> اطلب مقطعًا</a></li>
                    <li><a href="<?= BASE_URL ?>/contact.php"><i class="fas fa-envelope"></i> تواصل معنا</a></li>
                </ul>
            </div>

            <!-- معلومات التواصل -->
            <div>
                <h3 class="footer-title">تواصل معنا</h3>
                <ul class="footer-links">
                    <?php if ($contactEmail): ?>
                        <li><a href="mailto:<?= clean($contactEmail) ?>"><i class="fas fa-envelope"></i> <?= clean($contactEmail) ?></a></li>
                    <?php endif; ?>
                    <?php if ($contactPhone): ?>
                        <li><a href="tel:<?= clean($contactPhone) ?>"><i class="fas fa-phone"></i> <?= clean($contactPhone) ?></a></li>
                    <?php endif; ?>
                    <li><a href="<?= BASE_URL ?>/contact.php"><i class="fas fa-paper-plane"></i> أرسل رسالة</a></li>
                    <?php if (getSetting('allow_register', '1') === '1'): ?>
                        <li><a href="<?= BASE_URL ?>/register.php"><i class="fas fa-user-plus"></i> إنشاء حساب</a></li>
                    <?php endif; ?>
                </ul>
            </div>

        </div>

        <!-- حقوق النشر -->
        <div class="footer-bottom">
            <p><?= clean($footerText) ?></p>
        </div>
    </div>
</footer>
<!-- نهاية الفوتر -->

<!-- المشغل العائم -->
<?php require_once ROOT_PATH . '/includes/floating_player.php'; ?>

<!-- مكتبة Font Awesome (احتياطية) -->
<!-- سكربتات جافاسكريبت -->
<script src="<?= BASE_URL ?>/assets/js/player.js"></script>

<?php if (!empty($extraJs)): ?>
<script><?= $extraJs ?></script>
<?php endif; ?>

</body>
</html>
