<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../config/csrf.php';
require_once '../app/controllers/AuthController.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $result = AuthController::login(
        $_POST['email'] ?? '', $_POST['password'] ?? '',
        $_SERVER['REMOTE_ADDR'], !empty($_POST['remember_me']), $isHttps
    );

    if ($result['success']) {
        $user = $result['user'];
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();

        if (isset($result['remember_cookie'])) {
            setcookie('remember_token', $result['remember_cookie']['value'], $result['remember_cookie']['options']);
        }

        header("Location: " . $result['redirect']);
        exit;
    }
    $errors = $result['errors'];
}

$pageTitle = "Login";
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h3 class="card-title mb-3">Login</h3>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                        <div class="text-end mt-1"><a href="forgot_password.php" class="small">Forgot Password?</a></div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="remember_me" value="1" class="form-check-input" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">Remember me for 30 days</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>

                <p class="mt-3 text-center">Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
