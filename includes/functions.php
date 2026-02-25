<?php
/**
 * الدوال المساعدة الشاملة
 * Musican - منصة الصوتيات الاحترافية
 */

if (!defined('MUSICAN_APP')) die('Access Denied');

// =====================================================
// دوال الإعدادات
// =====================================================

function getSetting(string $key, string $default = ''): string {
    static $settings = [];
    if (empty($settings)) {
        try {
            $db = getDB();
            $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            return $default;
        }
    }
    return $settings[$key] ?? $default;
}

function updateSetting(string $key, string $value): bool {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$key, $value, $value]);
}

// =====================================================
// دوال النظافة والأمان
// =====================================================

function clean(mixed $data): mixed {
    if (is_array($data)) {
        return array_map('clean', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function sanitizeSlug(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^\p{Arabic}\p{L}\p{N}\s-]/u', '', $text);
    $text = preg_replace('/[\s_]+/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');
    if (empty($text)) {
        $text = 'item-' . time();
    }
    return $text;
}

function generateSlug(string $text, string $table = '', int $excludeId = 0): string {
    $slug = sanitizeSlug($text);
    if (empty($slug)) $slug = 'item-' . time();
    if ($table) {
        $slug = makeUniqueSlug($table, $slug, $excludeId);
    }
    return $slug;
}

function deleteFile(string $relativePath): void {
    $fullPath = UPLOADS_PATH . '/' . ltrim($relativePath, '/');
    if (file_exists($fullPath)) {
        @unlink($fullPath);
    }
}

function makeUniqueSlug(string $table, string $slug, int $excludeId = 0): string {
    $db = getDB();
    $original = $slug;
    $i = 1;
    while (true) {
        $sql = "SELECT id FROM $table WHERE slug = ?";
        $params = [$slug];
        if ($excludeId > 0) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) break;
        $slug = $original . '-' . $i++;
    }
    return $slug;
}

// =====================================================
// دوال رفع الملفات
// =====================================================

function uploadFile(array $file, string $folder, array $allowedTypes, int $maxSize = 50 * 1024 * 1024): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'حدث خطأ أثناء رفع الملف.'];
    }
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'حجم الملف يتجاوز الحد المسموح به.'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'نوع الملف غير مسموح به.'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newName = uniqid('', true) . '_' . time() . '.' . $ext;
    $uploadDir = UPLOADS_PATH . '/' . $folder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $destPath = $uploadDir . $newName;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'message' => 'فشل نقل الملف.'];
    }
    return ['success' => true, 'filename' => $newName, 'path' => $destPath];
}

function deleteUploadedFile(string $folder, string $filename): void {
    if (!empty($filename)) {
        $path = UPLOAD_PATH . $folder . '/' . $filename;
        if (file_exists($path)) {
            unlink($path);
        }
    }
}

function getFileUrl(string $folder, ?string $filename, string $default = ''): string {
    if (empty($filename)) return $default;
    return UPLOAD_URL . $folder . '/' . $filename;
}

function getAudioUrl(?string $filename): string {
    return getFileUrl('audios', $filename);
}

function getImageUrl(string $folder, ?string $filename, string $default = ''): string {
    if (empty($filename)) {
        return empty($default) ? DEFAULT_COVER_URL : $default;
    }
    return UPLOAD_URL . $folder . '/' . $filename;
}

/**
 * جلب رابط الصورة الشخصية للمستخدم مع دعم Gravatar
 */
function getUserAvatarUrl(?array $user): string {
    if (!$user) return 'https://www.gravatar.com/avatar/0000?d=mp';
    if (!empty($user['avatar'])) {
        return getImageUrl('avatars', $user['avatar']);
    }
    $hash = md5(strtolower(trim($user['email'] ?? '')));
    return "https://www.gravatar.com/avatar/{$hash}?s=200&d=identicon";
}

// =====================================================
// دوال التاريخ والوقت
// =====================================================

function timeAgo(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'منذ لحظات';
    if ($diff < 3600) return 'منذ ' . floor($diff/60) . ' دقيقة';
    if ($diff < 86400) return 'منذ ' . floor($diff/3600) . ' ساعة';
    if ($diff < 2592000) return 'منذ ' . floor($diff/86400) . ' يوم';
    if ($diff < 31536000) return 'منذ ' . floor($diff/2592000) . ' شهر';
    return 'منذ ' . floor($diff/31536000) . ' سنة';
}

function formatDate(string $datetime, string $format = 'Y/m/d'): string {
    return date($format, strtotime($datetime));
}

function formatArabicDate(string $datetime): string {
    $months = ['', 'يناير', 'فبراير', 'مارس', 'إبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
    $d = date('j', strtotime($datetime));
    $m = (int)date('n', strtotime($datetime));
    $y = date('Y', strtotime($datetime));
    return $d . ' ' . $months[$m] . ' ' . $y;
}

// =====================================================
// دوال التنسيق
// =====================================================

function formatNumber(int $num): string {
    if ($num >= 1000000) return round($num / 1000000, 1) . 'م';
    if ($num >= 1000) return round($num / 1000, 1) . 'ك';
    return (string)$num;
}

function formatDuration(string $duration): string {
    return $duration;
}

function formatFileSize(int $bytes): string {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

// =====================================================
// دوال الإشعارات
// =====================================================

function sendNotification(int $userId, string $content, string $link = '', string $type = 'info'): bool {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, type, content, link) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $type, $content, $link]);
    } catch (Exception $e) {
        return false;
    }
}

function getUnreadNotificationsCount(int $userId): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function markNotificationsRead(int $userId): void {
    $db = getDB();
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
}

// =====================================================
// دوال عداد المشاهدات
// =====================================================

function incrementListens(int $audioId): void {
    $db = getDB();
    $stmt = $db->prepare("UPDATE audios SET listens = listens + 1 WHERE id = ?");
    $stmt->execute([$audioId]);
}

function incrementDownloads(int $audioId): void {
    $db = getDB();
    $stmt = $db->prepare("UPDATE audios SET downloads = downloads + 1 WHERE id = ?");
    $stmt->execute([$audioId]);
}

// =====================================================
// دوال الإحصائيات
// =====================================================

function getDashboardStats(): array {
    $db = getDB();
    return [
        'audios'           => (int)$db->query("SELECT COUNT(*) FROM audios WHERE status='published'")->fetchColumn(),
        'performers'       => (int)$db->query("SELECT COUNT(*) FROM performers")->fetchColumn(),
        'albums'           => (int)$db->query("SELECT COUNT(*) FROM albums")->fetchColumn(),
        'categories'       => (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
        'users'            => (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'total_listens'    => (int)($db->query("SELECT SUM(listens) FROM audios")->fetchColumn() ?: 0),
        'total_downloads'  => (int)($db->query("SELECT SUM(downloads) FROM audios")->fetchColumn() ?: 0),
        'total_comments'   => (int)$db->query("SELECT COUNT(*) FROM comments")->fetchColumn(),
        'pending_requests' => (int)$db->query("SELECT COUNT(*) FROM audio_requests WHERE status='pending'")->fetchColumn(),
        'unread_messages'  => (int)$db->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn(),
    ];
}

// =====================================================
// دوال التحقق
// =====================================================

function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword(string $password): bool {
    return strlen($password) >= 8;
}

// =====================================================
// دوال الإعادة
// =====================================================

function redirect(string $url): void {
    header('Location: ' . $url);
    exit();
}

function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function showFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';
    $icons = ['success' => '✅', 'error' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'];
    $icon = $icons[$flash['type']] ?? 'ℹ️';
    return '<div class="alert alert-' . $flash['type'] . '">' . $icon . ' ' . clean($flash['message']) . '</div>';
}

// =====================================================
// دوال الصوتيات
// =====================================================

function getAudios(array $options = []): array {
    $db = getDB();
    $where = ["a.status = 'published'"];
    $params = [];
    
    if (!empty($options['category_id'])) {
        $where[] = "a.category_id = ?";
        $params[] = $options['category_id'];
    }
    if (!empty($options['performer_id'])) {
        $where[] = "a.performer_id = ?";
        $params[] = $options['performer_id'];
    }
    if (!empty($options['album_id'])) {
        $where[] = "a.album_id = ?";
        $params[] = $options['album_id'];
    }
    if (!empty($options['search'])) {
        $where[] = "(a.title LIKE ? OR p.name LIKE ?)";
        $params[] = '%' . $options['search'] . '%';
        $params[] = '%' . $options['search'] . '%';
    }
    if (!empty($options['featured'])) {
        $where[] = "a.is_featured = 1";
    }
    
    $whereStr = implode(' AND ', $where);
    $orderBy = match($options['order'] ?? 'latest') {
        'popular'  => 'a.listens DESC',
        'most_downloaded' => 'a.downloads DESC',
        'top_rated' => 'a.rating_avg DESC',
        default    => 'a.created_at DESC'
    };
    
    $limit = (int)($options['limit'] ?? 12);
    $offset = (int)($options['offset'] ?? 0);
    
    $sql = "SELECT a.*, p.name as performer_name, p.slug as performer_slug, 
                   c.name as category_name, c.slug as category_slug,
                   al.title as album_title, al.slug as album_slug
            FROM audios a
            JOIN performers p ON a.performer_id = p.id
            JOIN categories c ON a.category_id = c.id
            LEFT JOIN albums al ON a.album_id = al.id
            WHERE $whereStr
            ORDER BY $orderBy
            LIMIT $limit OFFSET $offset";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getAudio(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT a.*, p.name as performer_name, p.slug as performer_slug, p.image as performer_image,
                           c.name as category_name, c.slug as category_slug,
                           al.title as album_title, al.slug as album_slug
                    FROM audios a
                    JOIN performers p ON a.performer_id = p.id
                    JOIN categories c ON a.category_id = c.id
                    LEFT JOIN albums al ON a.album_id = al.id
                    WHERE a.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getAudioBySlug(string $slug): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT a.*, p.name as performer_name, p.slug as performer_slug, p.image as performer_image, p.bio as performer_bio,
                           c.name as category_name, c.slug as category_slug,
                           al.title as album_title, al.slug as album_slug
                    FROM audios a
                    JOIN performers p ON a.performer_id = p.id
                    JOIN categories c ON a.category_id = c.id
                    LEFT JOIN albums al ON a.album_id = al.id
                    WHERE a.slug = ? AND a.status = 'published'");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function countAudios(array $options = []): int {
    $db = getDB();
    $where = ["a.status = 'published'"];
    $params = [];
    if (!empty($options['category_id'])) { $where[] = "a.category_id = ?"; $params[] = $options['category_id']; }
    if (!empty($options['performer_id'])) { $where[] = "a.performer_id = ?"; $params[] = $options['performer_id']; }
    if (!empty($options['album_id'])) { $where[] = "a.album_id = ?"; $params[] = $options['album_id']; }
    if (!empty($options['search'])) {
        $where[] = "(a.title LIKE ? OR p.name LIKE ?)";
        $params[] = '%'.$options['search'].'%';
        $params[] = '%'.$options['search'].'%';
    }
    $whereStr = implode(' AND ', $where);
    $stmt = $db->prepare("SELECT COUNT(*) FROM audios a JOIN performers p ON a.performer_id=p.id WHERE $whereStr");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

// =====================================================
// دوال المؤدين
// =====================================================

function getPerformers(int $limit = 20, int $offset = 0): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT p.*, (SELECT COUNT(*) FROM audios WHERE performer_id=p.id AND status='published') as audios_count
                          FROM performers p ORDER BY p.name ASC LIMIT ? OFFSET ?");
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

function getPerformerBySlug(string $slug): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM performers WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function getPerformer(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM performers WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function countPerformers(): int {
    return (int)getDB()->query("SELECT COUNT(*) FROM performers")->fetchColumn();
}

// =====================================================
// دوال الألبومات
// =====================================================

function getAlbums(int $limit = 12, int $offset = 0, int $performerId = 0): array {
    $db = getDB();
    $where = $performerId > 0 ? "WHERE al.performer_id = " . (int)$performerId : '';
    $stmt = $db->prepare("SELECT al.*, p.name as performer_name, p.slug as performer_slug,
                          (SELECT COUNT(*) FROM audios WHERE album_id=al.id AND status='published') as audios_count
                          FROM albums al JOIN performers p ON al.performer_id=p.id
                          $where ORDER BY al.created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

function getAlbumBySlug(string $slug): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT al.*, p.name as performer_name, p.slug as performer_slug
                          FROM albums al JOIN performers p ON al.performer_id=p.id
                          WHERE al.slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function getAlbum(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT al.*, p.name as performer_name FROM albums al JOIN performers p ON al.performer_id=p.id WHERE al.id=?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function countAlbums(): int {
    return (int)getDB()->query("SELECT COUNT(*) FROM albums")->fetchColumn();
}

// =====================================================
// دوال الأقسام
// =====================================================

function getCategories(): array {
    $db = getDB();
    $stmt = $db->query("SELECT c.*, (SELECT COUNT(*) FROM audios WHERE category_id=c.id AND status='published') as audios_count
                        FROM categories c ORDER BY c.sort_order ASC, c.name ASC");
    return $stmt->fetchAll();
}

function getCategoryBySlug(string $slug): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function getCategory(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// =====================================================
// دوال التعليقات
// =====================================================

function getComments(int $audioId): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT c.*, u.username, u.avatar FROM comments c 
                          JOIN users u ON c.user_id=u.id
                          WHERE c.audio_id=? AND c.status='visible'
                          ORDER BY c.created_at DESC");
    $stmt->execute([$audioId]);
    return $stmt->fetchAll();
}

function addComment(int $audioId, int $userId, string $comment): bool {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO comments (audio_id, user_id, comment) VALUES (?, ?, ?)");
    return $stmt->execute([$audioId, $userId, $comment]);
}

// =====================================================
// دوال التقييم والإعجاب
// =====================================================

function getUserRating(int $audioId, int $userId): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT rating FROM ratings WHERE audio_id=? AND user_id=?");
    $stmt->execute([$audioId, $userId]);
    return (int)($stmt->fetchColumn() ?: 0);
}

function rateAudio(int $audioId, int $userId, int $rating): bool {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO ratings (audio_id, user_id, rating) VALUES (?,?,?) ON DUPLICATE KEY UPDATE rating=?");
    $result = $stmt->execute([$audioId, $userId, $rating, $rating]);
    if ($result) {
        $db->prepare("UPDATE audios SET rating_avg=(SELECT AVG(rating) FROM ratings WHERE audio_id=?), rating_count=(SELECT COUNT(*) FROM ratings WHERE audio_id=?) WHERE id=?")
           ->execute([$audioId, $audioId, $audioId]);
    }
    return $result;
}

function toggleRating(int $audioId, int $userId, int $rating): bool {
    return rateAudio($audioId, $userId, $rating);
}

function isLiked(int $audioId, int $userId): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM likes WHERE audio_id=? AND user_id=?");
    $stmt->execute([$audioId, $userId]);
    return (bool)$stmt->fetch();
}

function toggleLike(int $audioId, int $userId): array {
    $db = getDB();
    if (isLiked($audioId, $userId)) {
        $db->prepare("DELETE FROM likes WHERE audio_id=? AND user_id=?")->execute([$audioId, $userId]);
        $db->prepare("UPDATE audios SET likes_count=GREATEST(0, likes_count-1) WHERE id=?")->execute([$audioId]);
        $count = (int)$db->prepare("SELECT likes_count FROM audios WHERE id=?")->execute([$audioId]) ? $db->query("SELECT likes_count FROM audios WHERE id=$audioId")->fetchColumn() : 0;
        return ['liked' => false, 'count' => (int)$count];
    } else {
        $db->prepare("INSERT INTO likes (audio_id, user_id) VALUES (?,?)")->execute([$audioId, $userId]);
        $db->prepare("UPDATE audios SET likes_count=likes_count+1 WHERE id=?")->execute([$audioId]);
        $count = (int)$db->query("SELECT likes_count FROM audios WHERE id=$audioId")->fetchColumn();
        return ['liked' => true, 'count' => $count];
    }
}

// =====================================================
// دوال المتابعة
// =====================================================

function isFollowing(int $userId, int $performerId): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM follows WHERE follower_id=? AND performer_id=?");
    $stmt->execute([$userId, $performerId]);
    return (bool)$stmt->fetch();
}

function toggleFollow(int $userId, int $performerId): array {
    $db = getDB();
    if (isFollowing($userId, $performerId)) {
        $db->prepare("DELETE FROM follows WHERE follower_id=? AND performer_id=?")->execute([$userId, $performerId]);
        $db->prepare("UPDATE performers SET followers_count=GREATEST(0, followers_count-1) WHERE id=?")->execute([$performerId]);
        $count = (int)$db->query("SELECT followers_count FROM performers WHERE id=$performerId")->fetchColumn();
        return ['following' => false, 'count' => $count];
    } else {
        $db->prepare("INSERT INTO follows (follower_id, performer_id) VALUES (?,?)")->execute([$userId, $performerId]);
        $db->prepare("UPDATE performers SET followers_count=followers_count+1 WHERE id=?")->execute([$performerId]);
        $count = (int)$db->query("SELECT followers_count FROM performers WHERE id=$performerId")->fetchColumn();
        return ['following' => true, 'count' => $count];
    }
}

// =====================================================
// دوال الترقيم
// =====================================================

function getPagination(int $total, int $perPage, int $currentPage, string $baseUrl): array {
    $totalPages = max(1, (int)ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    return [
        'total'        => $total,
        'per_page'     => $perPage,
        'current_page' => $currentPage,
        'total_pages'  => $totalPages,
        'offset'       => ($currentPage - 1) * $perPage,
        'base_url'     => $baseUrl,
    ];
}

function renderPagination(array $pagination): string {
    if ($pagination['total_pages'] <= 1) return '';
    
    $html = '<div class="pagination">';
    $current = $pagination['current_page'];
    $total   = $pagination['total_pages'];
    $base    = $pagination['base_url'];
    $sep = strpos($base, '?') !== false ? '&' : '?';
    
    if ($current > 1) {
        $html .= '<a class="page-btn" href="' . $base . $sep . 'page=' . ($current - 1) . '">‹ السابق</a>';
    }
    
    $start = max(1, $current - 2);
    $end   = min($total, $current + 2);
    
    if ($start > 1) { $html .= '<a class="page-btn" href="' . $base . $sep . 'page=1">1</a>'; if ($start > 2) $html .= '<span class="page-dots">...</span>'; }
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $current ? ' active' : '';
        $html .= '<a class="page-btn' . $active . '" href="' . $base . $sep . 'page=' . $i . '">' . $i . '</a>';
    }
    if ($end < $total) { if ($end < $total - 1) $html .= '<span class="page-dots">...</span>'; $html .= '<a class="page-btn" href="' . $base . $sep . 'page=' . $total . '">' . $total . '</a>'; }
    
    if ($current < $total) {
        $html .= '<a class="page-btn" href="' . $base . $sep . 'page=' . ($current + 1) . '">التالي ›</a>';
    }
    
    $html .= '</div>';
    return $html;
}

// =====================================================
// دوال البحث
// =====================================================

function searchAll(string $q, int $limit = 5): array {
    $db = getDB();
    $q = '%' . $q . '%';
    $audios = $db->prepare("SELECT 'audio' as type, id, title as name, slug, cover_image FROM audios WHERE title LIKE ? AND status='published' LIMIT ?");
    $audios->execute([$q, $limit]);
    $performers = $db->prepare("SELECT 'performer' as type, id, name, slug, image as cover_image FROM performers WHERE name LIKE ? LIMIT ?");
    $performers->execute([$q, $limit]);
    return array_merge($audios->fetchAll(), $performers->fetchAll());
}

// =====================================================
// دوال الإشعارات
// =====================================================

function addNotification(int $userId, string $type, string $content, string $link = null): bool {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, type, content, link) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$userId, $type, $content, $link]);
}

function getNotifications(int $userId, int $limit = 20): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

function countUnreadNotifications(int $userId): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function markNotificationsAsRead(int $userId): bool {
    $db = getDB();
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    return $stmt->execute([$userId]);
}

// =====================================================
// دوال المراسلة
// =====================================================

function getConversations(int $userId): array {
    $db = getDB();
    // الحصول على أحدث رسالة في كل محادثة
    $stmt = $db->prepare("
        SELECT 
            u.id as contact_id, u.username, u.avatar, 
            m.message_text as last_message, m.created_at, m.is_read, m.sender_id
        FROM users u
        JOIN (
            SELECT MAX(id) as max_id, 
                   IF(sender_id = ?, receiver_id, sender_id) as contact_id
            FROM messages
            WHERE sender_id = ? OR receiver_id = ?
            GROUP BY contact_id
        ) latest ON u.id = latest.contact_id
        JOIN messages m ON m.id = latest.max_id
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$userId, $userId, $userId]);
    return $stmt->fetchAll();
}

function getChatHistory(int $userId, int $contactId, int $limit = 50): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?) 
           OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC
        LIMIT ?
    ");
    $stmt->execute([$userId, $contactId, $contactId, $userId, $limit]);
    
    // وضع الرسائل كمقروءة
    $db->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?")->execute([$contactId, $userId]);
    
    return $stmt->fetchAll();
}

function sendMessage(int $senderId, int $receiverId, string $message): bool {
    if (empty(trim($message))) return false;
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)");
    $result = $stmt->execute([$senderId, $receiverId, $message]);
    
    if ($result) {
        // إضافة إشعار للمستلم
        $sender = getUser($senderId);
        addNotification($receiverId, 'message', "لديك رسالة جديدة من " . $sender['username'], BASE_URL . "/messages.php?id=" . $senderId);
    }
    
    return $result;
}

function countUnreadMessages(int $userId): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}
