<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../config/csrf.php';
require_once '../app/models/User.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    $user = User::findById($_SESSION['user_id']);

    if (!password_verify($current, $user['password'])) {
        $errors[] = "Current password is incorrect.";
    }
    if (strlen($new) < 6) {
        $errors[] = "New password must be at least 6 characters.";
    }
    if ($new !== $confirm) {
        $errors[] = "New passwords do not match.";
    }

    if (empty($errors)) {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        User::updatePassword($_SESSION['user_id'], $hashed);

        $_SESSION['success'] = "Password changed successfully.";
        header("Location: profile.php");
        exit;
    }
}

$pageTitle = "Change Password";
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm mt-2">
            <div class="card-body">
                <h3 class="card-title mb-3">Change Password</h3>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="change_password.php">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Update Password</button>
                    <a href="profile.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
