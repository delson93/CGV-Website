<?php
/**
 * Charles Gala Venue - Production MySQL REST API (`api/mysql_api.php`)
 * Connects both the public storefront and the Admin CMS directly to MySQL (`charles_gala_db`).
 */
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $_GET['action'] ?? ($input['action'] ?? 'bootstrap');

try {
    if ($action === 'bootstrap') {
        $settings = getSiteSettings();
        // Mask raw gemini_api_key if not authenticated
        if (!isAdminAuthenticated()) {
            unset($settings['gemini_api_key']);
        }
        echo json_encode([
            'ok'        => true,
            'engine'    => 'MySQL PDO (charles_gala_db)',
            'auth'      => isAdminAuthenticated(),
            'settings'  => $settings,
            'about'     => getAboutSection(),
            'offerings' => getOfferings(!isAdminAuthenticated()),
            'gallery'   => getGalleryItems()
        ]);
        exit;
    }

    if ($action === 'login' && $method === 'POST') {
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :u OR email = :e LIMIT 1");
        $stmt->execute([':u' => $username, ':e' => $username]);
        $admin = $stmt->fetch();

        if ($admin && !empty($admin['locked_until']) && strtotime($admin['locked_until']) > time()) {
            echo json_encode(['ok' => false, 'error' => 'Account temporarily locked due to failed login attempts. Please try again in 15 minutes.']);
            exit;
        }

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $token = bin2hex(random_bytes(32));
            $pdo->prepare("UPDATE admins SET failed_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = :id")
                ->execute([':id' => $admin['id']]);
            $pdo->prepare("INSERT INTO admin_sessions (session_token, admin_id, ip_address, user_agent, expires_at) VALUES (:t, :aid, :ip, :ua, DATE_ADD(NOW(), INTERVAL 7 DAY))")
                ->execute([
                    ':t'   => $token,
                    ':aid' => $admin['id'],
                    ':ip'  => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    ':ua'  => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Browser', 0, 250)
                ]);
            $_SESSION['cgv_admin_token'] = $token;
            $_SESSION['cgv_admin_id'] = (int)$admin['id'];
            $_SESSION['cgv_admin_user'] = $admin['username'];

            echo json_encode([
                'ok'       => true,
                'token'    => $token,
                'username' => $admin['username'],
                'role'     => $admin['role']
            ]);
            exit;
        } else {
            if ($admin) {
                $attempts = (int)$admin['failed_attempts'] + 1;
                $lockSql = $attempts >= 5 ? ", locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE)" : "";
                $pdo->prepare("UPDATE admins SET failed_attempts = :a {$lockSql} WHERE id = :id")
                    ->execute([':a' => $attempts, ':id' => $admin['id']]);
            }
            echo json_encode(['ok' => false, 'error' => 'Invalid MySQL admin username or password.']);
            exit;
        }
    }

    if ($action === 'logout') {
        if (!empty($_SESSION['cgv_admin_token'])) {
            $pdo->prepare("DELETE FROM admin_sessions WHERE session_token = :t")
                ->execute([':t' => $_SESSION['cgv_admin_token']]);
        }
        session_destroy();
        echo json_encode(['ok' => true]);
        exit;
    }

    // All mutating actions below require valid MySQL Admin Authentication
    if (!isAdminAuthenticated()) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Unauthorized: Valid MySQL Admin Session Required.']);
        exit;
    }

    if ($action === 'change_password' && $method === 'POST') {
        $currentPassword = $input['current_password'] ?? '';
        $newUsername     = trim($input['new_username'] ?? 'admin');
        $newPassword     = $input['new_password'] ?? '';

        if (strlen($newPassword) < 8) {
            echo json_encode(['ok' => false, 'error' => 'New password must be at least 8 characters long.']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => (int)$_SESSION['cgv_admin_id']]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($currentPassword, $admin['password_hash'])) {
            echo json_encode(['ok' => false, 'error' => 'Current password verification failed.']);
            exit;
        }
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE admins SET username = :u, password_hash = :h WHERE id = :id")
            ->execute([':u' => $newUsername, ':h' => $newHash, ':id' => (int)$admin['id']]);
        $_SESSION['cgv_admin_user'] = $newUsername;
        echo json_encode(['ok' => true, 'message' => 'Admin credentials updated in MySQL `admins` table.']);
        exit;
    }

    if ($action === 'save_settings' && $method === 'POST') {
        $allowedKeys = [
            'site_name', 'phone_primary', 'whatsapp_number', 'whatsapp_default_msg',
            'email', 'address', 'plus_code', 'instagram_url', 'youtube_url', 'facebook_url',
            'seo_meta_title', 'seo_meta_description', 'seo_keywords', 'seo_score', 'gemini_api_key'
        ];
        foreach ($allowedKeys as $k) {
            if (isset($input[$k])) {
                updateSiteSetting($k, is_array($input[$k]) ? implode(', ', $input[$k]) : trim((string)$input[$k]));
            }
        }
        echo json_encode(['ok' => true, 'message' => 'Settings persisted to MySQL `site_settings` table.']);
        exit;
    }

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
