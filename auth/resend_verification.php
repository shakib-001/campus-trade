<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../app/controllers/AuthController.php';

$email = $_GET['email'] ?? null;
if (!$email) {
    header("Location: login.php");
    exit;
}

$result = AuthController::resendVerificationCode($email);

if ($result['success']) {
    $mailResult = send_app_email(
        $result['email'], $result['name'],
        'Your Campus Trade verification code',
        "<p>Hi {$result['name']},</p>
         <p>Your Campus Trade verification code is:</p>
         <h2 style=\"letter-spacing:4px;\">{$result['code']}</h2>
         <p>This code expires in 10 minutes.</p>"
    );
    $_SESSION['pending_email'] = $email;
    if (!$mailResult['sent']) {
        $_SESSION['dev_otp'] = $result['code'];
    }
    header("Location: verify_email.php");
    exit;
}

header("Location: login.php");
exit;
?>
