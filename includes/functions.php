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
