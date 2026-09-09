<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/LoginAttempt.php';
require_once __DIR__ . '/../models/RememberToken.php';
require_once __DIR__ . '/../../includes/functions.php';

/**
 * app/controllers/AuthController.php
 *
 * Holds the actual business logic for registration and login — validation,
 * calling the right Models, and deciding what should happen next. The page
 * files (auth/register.php, auth/login.php) stay as thin wrappers: they
 * collect $_POST data, call these methods, and render the view (HTML) with
 * whatever result comes back. This keeps URLs and page structure unchanged
 * while still giving the app a real Controller layer.
 *
 * Every method returns an array shaped like:
 *   ['success' => true,  'redirect' => '...', 'flash' => '...']
 *   ['success' => false, 'errors' => ['...', '...']]
 */
class AuthController {

    public static function register($data) {
        $name = trim($data['name'] ?? '');
        $student_id = trim($data['student_id'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $password = $data['password'] ?? '';
        $confirm_password = $data['confirm_password'] ?? '';

        $errors = [];

        if (empty($name) || empty($email) || empty($password)) {
            $errors[] = "Name, email, and password are required.";
        }
        if (strlen($name) > 100 || strlen($email) > 100 || strlen($student_id) > 20 || strlen($phone) > 20) {
            $errors[] = "One of the fields is too long. Please shorten it.";
        }
        if (!empty($phone) && !is_valid_bd_phone($phone)) {
            $errors[] = "Phone number must be exactly 11 digits and start with 01 (e.g. 01712345678).";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        }
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }

        if (empty($errors) && User::emailExists($email)) {
            $errors[] = "An account with this email already exists.";
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        User::create($name, $student_id, $email, $phone, $hashedPassword);

        return ['success' => true, 'redirect' => 'login.php', 'flash' => 'Registration successful! Please log in.'];
    }

    /**
     * Handles the full login flow: rate limiting, credential check, session
     * setup, and (optionally) creating a "Remember Me" token/cookie.
     * $isHttps is passed in so this stays a plain PHP class with no direct
     * dependency on superglobals beyond what's given to it.
     */
    public static function login($email, $password, $ip, $rememberMe, $isHttps) {
        $email = trim($email);

        if (empty($email) || empty($password)) {
            return ['success' => false, 'errors' => ["Please enter both email and password."]];
        }

        if (LoginAttempt::recentCount($email, 15) >= 5) {
            return ['success' => false, 'errors' => [
                "Too many failed login attempts. Please try again in 15 minutes, or use Forgot Password."
            ]];
        }

        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            LoginAttempt::log($email, $ip);
            return ['success' => false, 'errors' => ["Invalid email or password."]];
        }

        if ($user['status'] === 'blocked') {
            return ['success' => false, 'errors' => ["Your account has been blocked. Contact admin."]];
        }

        LoginAttempt::clear($email);

        $result = [
            'success' => true,
            'user' => $user,
            'redirect' => $user['role'] === 'admin' ? '../admin/dashboard.php' : '../student/dashboard.php',
        ];

        if ($rememberMe) {
            $selector = bin2hex(random_bytes(12));
            $validator = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

            RememberToken::create($user['user_id'], $selector, hash('sha256', $validator), $expires);

            $result['remember_cookie'] = [
                'value' => $selector . ':' . $validator,
                'options' => [
                    'expires' => strtotime('+30 days'),
                    'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => $isHttps,
                ],
            ];
        }

        return $result;
    }
}
?>
