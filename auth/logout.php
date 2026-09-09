<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../app/models/RememberToken.php';

// Also revoke the "Remember Me" token, if one exists, so logging out is final
if (!empty($_COOKIE['remember_token'])) {
    [$selector] = explode(':', $_COOKIE['remember_token'], 2);
    if ($selector) {
        RememberToken::deleteBySelector($selector);
    }
    setcookie('remember_token', '', ['expires' => time() - 3600, 'path' => '/']);
}

session_unset();
session_destroy();
header("Location: login.php");
exit;
?>
