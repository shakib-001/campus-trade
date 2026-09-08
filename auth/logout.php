<?php
require_once '../includes/session_init.php';

// Also revoke the "Remember Me" token, if one exists, so logging out is final
if (!empty($_COOKIE['remember_token'])) {
    require_once '../config/db.php';
    [$selector] = explode(':', $_COOKIE['remember_token'], 2);
    if ($selector) {
        $pdo->prepare("DELETE FROM remember_tokens WHERE selector = ?")->execute([$selector]);
    }
    setcookie('remember_token', '', ['expires' => time() - 3600, 'path' => '/']);
}

session_unset();
session_destroy();
header("Location: login.php");
exit;
?>
