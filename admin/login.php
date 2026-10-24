<?php
/**
 * Charles Gala Venue - Production MySQL Admin Login (`admin/login.php`)
 * Authenticates strictly against the MySQL `admins` table using `password_verify()`
 * and records sessions in the MySQL `admin_sessions` table.
 */
require_once __DIR__ . '/../includes/functions.php';

if (isAdminAuthenticated()) {
    header('Location: index.php');
    exit;
}

$settings = getSiteSettings();
$error = '';
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $pdo = getDB();

        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :u OR email = :e LIMIT 1");
        $stmt->execute([':u' => $username, ':e' => $username]);
        $admin = $stmt->fetch();

        if ($admin && !empty($admin['locked_until']) && strtotime($admin['locked_until']) > time()) {
            $error = 'Too many failed login attempts. Account locked for 15 minutes.';
        } elseif ($admin && password_verify($password, $admin['password_hash'])) {
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
            $_SESSION['cgv_admin_id']    = (int)$admin['id'];
            $_SESSION['cgv_admin_user']  = $admin['username'];

            header('Location: index.php');
            exit;
        } else {
            if ($admin) {
                $attempts = (int)$admin['failed_attempts'] + 1;
                $lockSql = $attempts >= 5 ? ", locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE)" : "";
                $pdo->prepare("UPDATE admins SET failed_attempts = :a {$lockSql} WHERE id = :id")
                    ->execute([':a' => $attempts, ':id' => $admin['id']]);
            }
            $error = 'Invalid admin username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?= htmlspecialchars($settings['site_name']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-login-body">
    <div class="login-box">
        <img src="../assets/images/logo.png" alt="Logo" width="72" height="72" style="margin:0 auto 12px;">
        <h2><?= htmlspecialchars($settings['site_name']) ?></h2>
        <p style="font-size:0.85rem;color:#64748b;margin-bottom:1.25rem;">MySQL Admin Backend Authentication</p>
        <?php if ($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <label for="login-username">Admin Username or Email</label>
            <input type="text" id="login-username" name="username" placeholder="Enter admin username" required autofocus>
            <label for="login-password">Password</label>
            <input type="password" id="login-password" name="password" placeholder="Enter password" required>
            <button type="submit" class="btn btn-gold" style="width:100%;margin-top:1rem;">Sign In to MySQL Admin Panel</button>
        </form>
        <p style="margin-top:1.25rem;font-size:0.85rem;"><a href="../index.php">← Return to Public Website</a></p>
    </div>
</body>
</html>
