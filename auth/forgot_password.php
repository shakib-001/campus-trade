<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../config/csrf.php';
require_once '../includes/SimpleMailer.php';

$errors = [];
$resetLink = null;
$emailSent = false;
$emailError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Don't reveal whether the email exists — just show a generic message either way
        $_SESSION['success'] = "If an account with that email exists, a reset link has been generated.";
        header("Location: forgot_password.php");
        exit;
    } else {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE user_id = ?")
            ->execute([$token, $expires, $user['user_id']]);

        $resetLink = "reset_password.php?token=" . $token;

        // Build a full, absolute URL so the link works correctly inside an email
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
            . '://' . $_SERVER['HTTP_HOST']
            . rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/');
        $fullResetLink = $baseUrl . '/auth/' . $resetLink;

        $mailConfig = require '../config/mailer_config.php';
        if ($mailConfig['username'] !== 'your-email@gmail.com') {
            try {
                $mailer = new SimpleMailer(
                    $mailConfig['host'], $mailConfig['port'],
                    $mailConfig['username'], $mailConfig['password'],
                    $mailConfig['from_email'], $mailConfig['from_name']
                );
                $mailer->send(
                    $user['email'], $user['name'],
                    'Reset your Campus Trade password',
                    "<p>Hi {$user['name']},</p>
                     <p>Click the link below to reset your Campus Trade password. This link expires in 1 hour.</p>
                     <p><a href=\"{$fullResetLink}\">{$fullResetLink}</a></p>
                     <p>If you didn't request this, you can safely ignore this email.</p>"
                );
                $emailSent = true;
                $resetLink = null; // don't show the link on screen once it's actually emailed
            } catch (Exception $e) {
                $emailError = $e->getMessage();
            }
        }
    }
}

$pageTitle = "Forgot Password";
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h3 class="card-title mb-3">Forgot Password</h3>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($emailSent): ?>
                    <div class="alert alert-success">
                        <strong>Reset link sent!</strong> Check your inbox at the email you provided (and your spam folder, just in case).
                    </div>
                <?php elseif ($resetLink): ?>
                    <div class="alert alert-success">
                        <strong>Reset link generated.</strong>
                        <?php if ($emailError): ?>
                            <p class="small mb-2 mt-1 text-danger">Email could not be sent (<?= htmlspecialchars($emailError) ?>). Showing the link here instead so you're not blocked:</p>
                        <?php else: ?>
                            <p class="small mb-2 mt-1">Email sending isn't configured yet (see <code>config/mailer_config.php</code>). For now, click below directly:</p>
                        <?php endif; ?>
                        <a href="<?= $resetLink ?>" class="btn btn-primary btn-sm">Reset My Password</a>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Enter your account email — we'll generate a password reset link for you.</p>
                    <form method="POST" action="forgot_password.php">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                    </form>
                <?php endif; ?>

                <p class="mt-3 text-center"><a href="login.php">Back to Login</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
