<?php
/**
 * Charles Gala Venue - Production MySQL Installer & Connection Configurator (`install.php`)
 */
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['db_host'] ?? '127.0.0.1');
    $port = trim($_POST['db_port'] ?? '3306');
    $name = trim($_POST['db_name'] ?? 'charles_gala_db');
    $user = trim($_POST['db_user'] ?? 'root');
    $pass = $_POST['db_pass'] ?? '';
    $adminUser = trim($_POST['admin_user'] ?? 'admin');
    $adminPass = $_POST['admin_pass'] ?? '';

    try {
        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $safeDb = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeDb}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$safeDb}`");

        $sql = file_get_contents(__DIR__ . '/database.sql');
        $pdo->exec($sql);

        if (!empty($adminPass) && strlen($adminPass) >= 8) {
            $hash = password_hash($adminPass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE admins SET username = :u, password_hash = :h WHERE id = 1");
            $stmt->execute([':u' => $adminUser, ':h' => $hash]);
        }

        $configPhp = "<?php\n"
            . "define('DB_HOST', " . var_export($host, true) . ");\n"
            . "define('DB_PORT', " . var_export($port, true) . ");\n"
            . "define('DB_NAME', " . var_export($safeDb, true) . ");\n"
            . "define('DB_USER', " . var_export($user, true) . ");\n"
            . "define('DB_PASS', " . var_export($pass, true) . ");\n";
        file_put_contents(__DIR__ . '/config/db_credentials.php', $configPhp);
        $msg = "MySQL Database (`{$safeDb}`) and all 7 tables provisioned! You can now log in at admin/login.php.";
    } catch (Throwable $e) {
        $err = "MySQL Connection Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MySQL Production Setup — Charles Gala Venue</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="admin-login-body">
    <div class="login-box" style="max-width:520px;">
        <h2>🗄️ MySQL Production Installer</h2>
        <p style="font-size:0.85rem;color:#64748b;margin-bottom:1rem;">Connect and initialize your MySQL database (`charles_gala_db`).</p>
        <?php if ($msg): ?><div class="alert-success"><?= htmlspecialchars($msg) ?> <br><a href="admin/login.php"><strong>Go to Admin Login →</strong></a></div><?php endif; ?>
        <?php if ($err): ?><div class="alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
        <form method="POST">
            <label>MySQL Host</label>
            <input type="text" name="db_host" value="127.0.0.1" required>
            <label>MySQL Port</label>
            <input type="text" name="db_port" value="3306" required>
            <label>Database Name</label>
            <input type="text" name="db_name" value="charles_gala_db" required>
            <label>MySQL Username</label>
            <input type="text" name="db_user" value="root" required>
            <label>MySQL Password</label>
            <input type="password" name="db_pass" placeholder="Enter MySQL password">
            <hr style="margin:1rem 0;border:0;border-top:1px solid #e2e8f0;">
            <label>Set Production Admin Username</label>
            <input type="text" name="admin_user" value="admin" required>
            <label>Set Production Admin Password (min 8 chars)</label>
            <input type="password" name="admin_pass" placeholder="Leave blank to keep CharlesGala@2026">
            <button type="submit" class="btn btn-gold" style="width:100%;margin-top:1rem;">Provision MySQL Tables & Save Config</button>
        </form>
    </div>
</body>
</html>
