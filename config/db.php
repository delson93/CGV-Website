<?php
/**
 * Charles Gala Venue - Production MySQL Database Connection & Auto-Provisioner
 * Strictly uses MySQL (PDO) with automatic database & schema creation on first run.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400 * 7,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

$customConfigFile = __DIR__ . '/db_credentials.php';
if (file_exists($customConfigFile)) {
    require_once $customConfigFile;
}

if (!defined('DB_HOST')) define('DB_HOST', getenv('CGV_DB_HOST') ?: '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', getenv('CGV_DB_PORT') ?: '3306');
if (!defined('DB_NAME')) define('DB_NAME', getenv('CGV_DB_NAME') ?: 'charles_gala_db');
if (!defined('DB_USER')) define('DB_USER', getenv('CGV_DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('CGV_DB_PASS') ?: '');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // If database does not exist yet, connect to MySQL server and provision it automatically
        $serverDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
        $pdo = new PDO($serverDsn, DB_USER, DB_PASS, $options);
        $dbNameSafe = preg_replace('/[^a-zA-Z0-9_]/', '', DB_NAME);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbNameSafe}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbNameSafe}`");
    }

    // Ensure core tables exist; if not, run database.sql automatically
    $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
    if ($stmt->rowCount() === 0) {
        $sqlFile = __DIR__ . '/../database.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $pdo->exec($sql);
        }
    }

    return $pdo;
}

function generateCsrfToken(): string {
    if (empty($_SESSION['cgv_csrf_token'])) {
        $_SESSION['cgv_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['cgv_csrf_token'];
}

function verifyCsrfToken(?string $token): bool {
    return !empty($token) && !empty($_SESSION['cgv_csrf_token']) && hash_equals($_SESSION['cgv_csrf_token'], $token);
}
