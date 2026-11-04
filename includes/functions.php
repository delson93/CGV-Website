<?php
require_once __DIR__ . '/../config/db.php';

/**
 * Fetch all site settings directly from MySQL `site_settings` table
 */
function getSiteSettings(): array {
    $pdo = getDB();
    $settings = [];
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

/**
 * Update or insert a setting in MySQL `site_settings` table
 */
function updateSiteSetting(string $key, string $value): bool {
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    return $stmt->execute([':k' => $key, ':v' => $value]);
}

/**
 * Fetch About Us row from MySQL `about_section` table
 */
function getAboutSection(): array {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM about_section WHERE id = 1 LIMIT 1");
    return $stmt->fetch() ?: [];
}

/**
 * Fetch Offerings from MySQL `offerings` table
 */
function getOfferings(bool $onlyActive = true): array {
    $pdo = getDB();
    $sql = "SELECT * FROM offerings " . ($onlyActive ? "WHERE is_active = 1 " : "") . "ORDER BY sort_order ASC, id ASC";
    return $pdo->query($sql)->fetchAll();
}

/**
 * Fetch Gallery items from MySQL `gallery_items` table
 */
function getGalleryItems(): array {
    $pdo = getDB();
    return $pdo->query("SELECT * FROM gallery_items ORDER BY sort_order ASC, id DESC")->fetchAll();
}

/**
 * Extract YouTube Video ID
 */
function extractYoutubeId(string $url): string {
    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|v\/|e(?:mbed)?\/|.*[?&]v=)|youtu\.be\/|youtube\.com\/shorts\/)([^"&?\/\s]{11})/i', $url, $match)) {
        return $match[1];
    }
    return trim($url);
}

/**
 * Build WhatsApp deep link URL for click-to-chat
 */
function buildWhatsAppUrl(string $whatsappNumber, string $message): string {
    $cleanPhone = preg_replace('/[^0-9]/', '', $whatsappNumber);
    if (strlen($cleanPhone) === 10) {
        $cleanPhone = '91' . $cleanPhone;
    }
    return 'https://wa.me/' . $cleanPhone . '?text=' . rawurlencode($message);
}

/**
 * Build tel: link for Call action
 */
function buildCallUrl(string $phoneNumber): string {
    $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
    if (strlen($cleanPhone) === 10) {
        $cleanPhone = '+91' . $cleanPhone;
    } elseif (strpos($cleanPhone, '+') !== 0) {
        $cleanPhone = '+' . $cleanPhone;
    }
    return 'tel:' . $cleanPhone;
}

/**
 * Handle Secure Image Upload in Admin
 */
function handleImageUpload(array $file, string $subFolder = 'offerings'): ?string {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) {
        return null;
    }
    $ext = $allowed[$mime];
    $targetDir = __DIR__ . '/../uploads/' . $subFolder . '/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $filename = $subFolder . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destPath = $targetDir . $filename;
    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        return 'uploads/' . $subFolder . '/' . $filename;
    }
    return null;
}

/**
 * Validate Admin Session against MySQL `admin_sessions` and `admins` tables
 */
function isAdminAuthenticated(): bool {
    if (empty($_SESSION['cgv_admin_token']) || empty($_SESSION['cgv_admin_id'])) {
        return false;
    }
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT s.id, a.username, a.role
        FROM admin_sessions s
        JOIN admins a ON a.id = s.admin_id
        WHERE s.session_token = :token
          AND s.admin_id = :aid
          AND s.expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([
        ':token' => $_SESSION['cgv_admin_token'],
        ':aid'   => (int)$_SESSION['cgv_admin_id']
    ]);
    return (bool)$stmt->fetch();
}

function requireAdminAuth(): void {
    if (!isAdminAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}


/** Public homepage defaults are used until the administrator saves custom values. */
function homepageDefaults(): array {
    return [
        'hero_eyebrow' => 'Celebrations, stays & swimming in Kulur, Mangalore',
        'hero_heading' => 'Some places bring you closer.',
        'hero_description' => 'Bring your people together at Charles Gala Venue. Explore our event spaces, enjoy time by the swimming pool, or plan a comfortable stay in Kulur, Mangalore.',
        'hero_image_path' => 'assets/images/resort/lawn.jpg',
        'pool_heading' => 'Your next poolside escape',
        'pool_description' => 'Make time for a refreshing swim with family and friends at Charles Gala Venue in Kulur, Mangalore. Ask our team about current pool timings, entry rates and private bookings before your visit.',
    ];
}

/** Accept local images or HTTPS media URLs, with a dependable empty-state asset. */
function publicImage(?string $path): string {
    $path = trim($path ?? '');
    $originals = ['hero.jpg'=>'lawn', 'open_ground.jpg'=>'lawn', 'mini_hall.jpg'=>'hall', 'pool.jpg'=>'pool-building'];
    if (preg_match('~^(uploads/(about|gallery|offerings)|assets/images)/[^/]+$~', $path) && isset($originals[basename($path)])) return 'assets/images/resort/' . $originals[basename($path)] . '.jpg';
    if (preg_match('~^(?:uploads/(?:about|gallery|offerings)|assets/images)/(?:hero|pool|rooms|mini_hall|open_ground|decorations)\.jpg$~D', $path)) return 'assets/images/placeholder.svg';
    if (filter_var($path, FILTER_VALIDATE_URL) && parse_url($path, PHP_URL_SCHEME) === 'https') return $path;
    if ($path !== '' && !str_contains($path, '..') && preg_match('~^(uploads|assets/images)/[a-zA-Z0-9_./-]+$~D', $path)) return $path;
    return 'assets/images/placeholder.svg';
}


/** Accept only a YouTube video URL or an exact video ID. Blank disables video. */
function backgroundYoutubeId(string $value): string {
    $value = trim($value);
    if (preg_match('/^[\w-]{11}$/D', $value)) return $value;
    $url = parse_url($value);
    if (!$url || !in_array($url['scheme'] ?? '', ['https', 'http'], true)) return '';
    $host = strtolower($url['host'] ?? '');
    $id = '';
    if ($host === 'youtu.be') $id = trim($url['path'] ?? '', '/');
    if (in_array($host, ['youtube.com','www.youtube.com','m.youtube.com','www.youtube-nocookie.com'], true)) {
        parse_str($url['query'] ?? '', $query);
        $id = is_string($query['v'] ?? null) ? $query['v'] : '';
        if (!$id && preg_match('~^/(?:embed|shorts)/([\w-]{11})/?$~D', $url['path'] ?? '', $m)) $id = $m[1];
    }
    return preg_match('/^[\w-]{11}$/D', $id) ? $id : '';
}
