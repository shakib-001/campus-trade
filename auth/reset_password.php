<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../config/csrf.php';

$token = $_GET['token'] ?? $_POST['token'] ?? null;
$errors = [];

if (!$token) {
    header("Location: forgot_password.php");
    exit;
}

// Validate the token exists and hasn't expired
$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $pageTitle = "Reset Password";
    require_once '../includes/header.php';
    echo '<div class="alert alert-danger">This reset link is invalid or has expired. <a href="forgot_password.php">Request a new one</a>.</div>';
    require_once '../includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $newPassword = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (strlen($newPassword) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    if ($newPassword !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE user_id = ?")
            ->execute([$hashed, $user['user_id']]);

        $_SESSION['success'] = "Password reset successful! Please log in with your new password.";
        header("Location: login.php");
        exit;
    }
}

$pageTitle = "Reset Password";
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h3 class="card-title mb-3">Set a New Password</h3>
                <p class="text-muted">For account: <?= htmlspecialchars($user['email']) ?></p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="reset_password.php?token=<?= htmlspecialchars($token) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
