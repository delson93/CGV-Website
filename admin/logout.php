<?php
require_once __DIR__ . '/../includes/functions.php';
$pdo = getDB();
if (!empty($_SESSION['cgv_admin_token'])) {
    $pdo->prepare("DELETE FROM admin_sessions WHERE session_token = :t")
        ->execute([':t' => $_SESSION['cgv_admin_token']]);
}
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
