<?php
/**
 * إدارة الإعدادات - Admin Settings
 * Musican
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
startSession();
requireAdmin();

$pageTitle = 'الإعدادات';
$success   = '';
$error     = '';

// حفظ الإعدادات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();

    $settings = [
        'site_name'        => clean($_POST['site_name'] ?? ''),
        'meta_description' => clean($_POST['meta_description'] ?? ''),
        'meta_keywords'    => clean($_POST['meta_keywords'] ?? ''),
        'footer_text'      => clean($_POST['footer_text'] ?? ''),
        'contact_email'    => clean($_POST['contact_email'] ?? ''),
        'contact_phone'    => clean($_POST['contact_phone'] ?? ''),
        'contact_address'  => clean($_POST['contact_address'] ?? ''),
        'facebook_url'     => clean($_POST['facebook_url'] ?? ''),
        'twitter_url'      => clean($_POST['twitter_url'] ?? ''),
        'youtube_url'      => clean($_POST['youtube_url'] ?? ''),
        'instagram_url'    => clean($_POST['instagram_url'] ?? ''),
        'allow_register'   => isset($_POST['allow_register']) ? '1' : '0',
        'allow_comments'   => isset($_POST['allow_comments']) ? '1' : '0',
        'allow_download'   => isset($_POST['allow_download']) ? '1' : '0',
        'active_template'  => clean($_POST['active_template'] ?? 'default'),
        'audios_per_page'  => (int)($_POST['audios_per_page'] ?? 12),
    ];

    // رفع الشعار
    if (!empty($_FILES['logo']['name'])) {
        $logoResult = uploadFile($_FILES['logo'], 'site', ['image/jpeg','image/png','image/webp','image/svg+xml'], 2*1024*1024);
        if ($logoResult['success']) {
            $settings['logo'] = $logoResult['filename'];
        } else {
            $error = $logoResult['error'];
        }
    }

    if (!$error) {
        foreach ($settings as $key => $value) {
            updateSetting($key, $value);
        }
        $success = 'تم حفظ الإعدادات بنجاح.';
    }
}

require_once __DIR__ . '/includes/admin_header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= clean($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><i class="fas fa-times-circle"></i> <?= clean($error) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <?= csrfField() ?>
    <div class="admin-tabs" style="margin-bottom:24px;">
        <button type="button" class="admin-tab active" data-tab="tab-general">عام</button>
        <button type="button" class="admin-tab" data-tab="tab-social">التواصل الاجتماعي</button>
        <button type="button" class="admin-tab" data-tab="tab-features">المزايا</button>
    </div>

    <!-- عام -->
    <div class="tab-content active" id="tab-general">
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h2 class="card-title-sm"><i class="fas fa-globe"></i> المعلومات العامة</h2></div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="site_name">اسم الموقع</label>
                        <input type="text" id="site_name" name="site_name" class="form-control" value="<?= clean(getSetting('site_name', 'موسيكان')) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="logo">شعار الموقع</label>
                        <input type="file" id="logo" name="logo" class="form-control" accept="image/*" data-preview="logo-preview">
                        <?php if (getSetting('logo')): ?>
                            <img id="logo-preview" src="<?= getImageUrl('site', getSetting('logo')) ?>" alt="" style="height:50px;margin-top:8px;">
                        <?php else: ?>
                            <img id="logo-preview" src="" style="display:none;height:50px;margin-top:8px;">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="meta_description">وصف الموقع (SEO)</label>
                    <textarea id="meta_description" name="meta_description" class="form-control" rows="2"><?= clean(getSetting('meta_description')) ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="meta_keywords">الكلمات المفتاحية (SEO)</label>
                    <input type="text" id="meta_keywords" name="meta_keywords" class="form-control" value="<?= clean(getSetting('meta_keywords')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="active_template">قالب الموقع</label>
                    <select id="active_template" name="active_template" class="form-control">
                        <?php
                        $dirs = array_filter(glob(ROOT_PATH . '/templates/*'), 'is_dir');
                        foreach ($dirs as $dir) {
                            $t_name = basename($dir);
                            $selected = (getSetting('active_template', 'default') === $t_name) ? 'selected' : '';
                            echo "<option value=\"$t_name\" $selected>" . ucfirst($t_name) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="footer_text">نص الفوتر</label>
                    <input type="text" id="footer_text" name="footer_text" class="form-control" value="<?= clean(getSetting('footer_text', 'جميع الحقوق محفوظة')) ?>">
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title-sm"><i class="fas fa-envelope"></i> معلومات التواصل</h2></div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="contact_email">البريد الإلكتروني</label>
                        <input type="email" id="contact_email" name="contact_email" class="form-control" value="<?= clean(getSetting('contact_email')) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contact_phone">رقم الهاتف</label>
                        <input type="text" id="contact_phone" name="contact_phone" class="form-control" value="<?= clean(getSetting('contact_phone')) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="contact_address">العنوان</label>
                    <input type="text" id="contact_address" name="contact_address" class="form-control" value="<?= clean(getSetting('contact_address')) ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- التواصل الاجتماعي -->
    <div class="tab-content" id="tab-social" style="display:none;">
        <div class="card">
            <div class="card-header"><h2 class="card-title-sm"><i class="fas fa-share-alt"></i> روابط التواصل الاجتماعي</h2></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label"><i class="fab fa-facebook" style="color:#1877f2;"></i> فيسبوك</label>
                    <input type="url" name="facebook_url" class="form-control" value="<?= clean(getSetting('facebook_url')) ?>" placeholder="https://facebook.com/...">
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fab fa-twitter" style="color:#1da1f2;"></i> تويتر</label>
                    <input type="url" name="twitter_url" class="form-control" value="<?= clean(getSetting('twitter_url')) ?>" placeholder="https://twitter.com/...">
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fab fa-youtube" style="color:#ff0000;"></i> يوتيوب</label>
                    <input type="url" name="youtube_url" class="form-control" value="<?= clean(getSetting('youtube_url')) ?>" placeholder="https://youtube.com/...">
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fab fa-instagram" style="color:#e1306c;"></i> إنستقرام</label>
                    <input type="url" name="instagram_url" class="form-control" value="<?= clean(getSetting('instagram_url')) ?>" placeholder="https://instagram.com/...">
                </div>
            </div>
        </div>
    </div>

    <!-- المزايا -->
    <div class="tab-content" id="tab-features" style="display:none;">
        <div class="card">
            <div class="card-header"><h2 class="card-title-sm"><i class="fas fa-toggle-on"></i> إعدادات المزايا</h2></div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:20px;">
                    <label class="form-toggle">
                        <input type="checkbox" name="allow_register" value="1" <?= getSetting('allow_register','1') === '1' ? 'checked' : '' ?>>
                        <span>السماح بالتسجيل للمستخدمين الجدد</span>
                    </label>
                    <label class="form-toggle">
                        <input type="checkbox" name="allow_comments" value="1" <?= getSetting('allow_comments','1') === '1' ? 'checked' : '' ?>>
                        <span>السماح بالتعليقات</span>
                    </label>
                    <label class="form-toggle">
                        <input type="checkbox" name="allow_download" value="1" <?= getSetting('allow_download','1') === '1' ? 'checked' : '' ?>>
                        <span>السماح بتنزيل الملفات الصوتية</span>
                    </label>
                    <div class="form-group" style="max-width:200px;">
                        <label class="form-label">عدد المقاطع في الصفحة</label>
                        <input type="number" name="audios_per_page" class="form-control" value="<?= (int)getSetting('audios_per_page', '12') ?>" min="4" max="48" step="4">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top:24px;">
        <button type="submit" class="btn btn-gold btn-lg"><i class="fas fa-save"></i> حفظ الإعدادات</button>
    </div>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
