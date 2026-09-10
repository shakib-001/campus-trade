<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../config/csrf.php';
require_once '../includes/functions.php';
require_once '../app/controllers/AuthController.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email']);
    $result = AuthController::requestPasswordReset($email);

    if ($result['exists']) {
        $mailResult = send_app_email(
            $result['email'], $result['name'],
            'Your Campus Trade password reset code',
            "<p>Hi {$result['name']},</p>
             <p>Your Campus Trade password reset code is:</p>
             <h2 style=\"letter-spacing:4px;\">{$result['code']}</h2>
             <p>This code expires in 10 minutes. If you didn't request this, you can ignore this email.</p>"
        );
        $_SESSION['reset_email'] = $email;
        if (!$mailResult['sent']) {
            $_SESSION['dev_otp'] = $result['code'];
        }
    }

    // Same message either way — don't reveal whether the email exists
    header("Location: reset_password.php");
    exit;
}

$pageTitle = "Forgot Password";
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h3 class="card-title mb-3">Forgot Password</h3>
                <p class="text-muted">Enter your account email — we'll send you a 6-digit code to reset your password.</p>

                <form method="POST" action="forgot_password.php">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Send Reset Code</button>
                </form>

                <p class="mt-3 text-center"><a href="login.php">Back to Login</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
