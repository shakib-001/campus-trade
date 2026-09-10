<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../config/csrf.php';
require_once '../includes/functions.php';
require_once '../app/controllers/AuthController.php';

$email = $_SESSION['pending_email'] ?? $_POST['email'] ?? null;
if (!$email) {
    header("Location: register.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (isset($_POST['resend'])) {
        $result = AuthController::resendVerificationCode($email);
        if ($result['success']) {
            $mailResult = send_app_email(
                $result['email'], $result['name'],
                'Your new Campus Trade verification code',
                "<p>Hi {$result['name']},</p>
                 <p>Your new Campus Trade verification code is:</p>
                 <h2 style=\"letter-spacing:4px;\">{$result['code']}</h2>
                 <p>This code expires in 10 minutes.</p>"
            );
            unset($_SESSION['dev_otp']);
            if (!$mailResult['sent']) {
                $_SESSION['dev_otp'] = $result['code'];
            }
            $_SESSION['success'] = "A new code has been sent.";
        }
        header("Location: verify_email.php");
        exit;
    }

    $code = trim($_POST['code'] ?? '');
    $result = AuthController::verifyEmailCode($email, $code);

    if ($result['success']) {
        unset($_SESSION['pending_email'], $_SESSION['dev_otp']);
        $_SESSION['success'] = $result['flash'];
        header("Location: " . $result['redirect']);
        exit;
    }
    $errors = $result['errors'];
}

$devOtp = $_SESSION['dev_otp'] ?? null;

$pageTitle = "Verify Your Email";
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h3 class="card-title mb-3">Verify Your Email</h3>
                <p class="text-muted">We sent a 6-digit code to <strong><?= htmlspecialchars($email) ?></strong>. Enter it below to activate your account.</p>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if ($devOtp): ?>
                    <div class="alert alert-warning small">
                        Email sending isn't configured yet (see <code>config/mailer_config.php</code>).
                        For now, your code is: <strong style="letter-spacing:2px;"><?= htmlspecialchars($devOtp) ?></strong>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="verify_email.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                    <div class="mb-3">
                        <label class="form-label">6-Digit Code</label>
                        <input type="text" name="code" class="form-control text-center" style="letter-spacing:4px; font-size:1.3rem;"
                               maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Verify & Activate</button>
                </form>

                <form method="POST" action="verify_email.php" class="mt-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                    <button type="submit" name="resend" value="1" class="btn btn-outline-secondary w-100 btn-sm">Resend Code</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
