<?php
/**
 * ذيل لوحة التحكم
 */
if (!defined('MUSICAN_APP')) die('Access Denied');
?>
    </main>
    <!-- نهاية المحتوى -->
</div>
<!-- نهاية الرئيسية -->

<div id="toast-container"></div>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<?php if (!empty($extraAdminJs)): ?>
<script><?= $extraAdminJs ?></script>
<?php endif; ?>
</body>
</html>
