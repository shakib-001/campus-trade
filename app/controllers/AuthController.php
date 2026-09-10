<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/LoginAttempt.php';
require_once __DIR__ . '/../models/RememberToken.php';
require_once __DIR__ . '/../../includes/functions.php';

/**
 * app/controllers/AuthController.php
 *
 * Business logic for registration (with email verification), login, and
 * password reset — all using 6-digit OTP codes instead of email links,
 * since that's a much more familiar, mobile-friendly flow for real users.
 *
 * The page files (auth/*.php) stay thin: they collect input, call these
 * methods, send the actual email (using SimpleMailer) when a code needs
 * emailing, and render the view based on the result returned here.
 *
 * Every method returns a result array shaped roughly like:
 *   ['success' => true,  'redirect' => '...', 'flash' => '...', ...]
 *   ['success' => false, 'errors' => ['...', '...'], ...]
 */
class AuthController {

    const CODE_EXPIRY_MINUTES = 10;
    const MAX_CODE_ATTEMPTS = 5;

    // ---------------- Registration + email verification ----------------

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
        $userId = User::create($name, $student_id, $email, $phone, $hashedPassword);

        $code = generate_otp_code();
        $expires = date('Y-m-d H:i:s', strtotime('+' . self::CODE_EXPIRY_MINUTES . ' minutes'));
        User::setVerificationCode($userId, hash('sha256', $code), $expires);

        return [
            'success' => true,
            'redirect' => 'verify_email.php',
            'email' => $email,
            'name' => $name,
            'code' => $code, // the page uses this to send the actual email
        ];
    }

    /** Verifies a submitted 6-digit code against the stored hash for this email. */
    public static function verifyEmailCode($email, $code) {
        $user = User::findByEmail($email);

        if (!$user || $user['email_verified']) {
            return ['success' => false, 'errors' => ["Invalid request."]];
        }
        if ($user['verify_attempts'] >= self::MAX_CODE_ATTEMPTS) {
            return ['success' => false, 'errors' => ["Too many incorrect attempts. Please request a new code."], 'locked' => true];
        }
        if (empty($user['verify_code_hash']) || strtotime($user['verify_expires']) < time()) {
            return ['success' => false, 'errors' => ["This code has expired. Please request a new one."], 'expired' => true];
        }
        if (!hash_equals($user['verify_code_hash'], hash('sha256', trim($code)))) {
            User::incrementVerifyAttempts($user['user_id']);
            $remaining = self::MAX_CODE_ATTEMPTS - ($user['verify_attempts'] + 1);
            return ['success' => false, 'errors' => ["Incorrect code. $remaining attempt(s) remaining."]];
        }

        User::markEmailVerified($user['user_id']);
        return ['success' => true, 'redirect' => 'login.php', 'flash' => 'Email verified! You can now log in.'];
    }

    /** Generates and returns a fresh code for an unverified account (e.g. the first one expired). */
    public static function resendVerificationCode($email) {
        $user = User::findByEmail($email);
        if (!$user || $user['email_verified']) {
            return ['success' => false, 'errors' => ["Invalid request."]];
        }

        $code = generate_otp_code();
        $expires = date('Y-m-d H:i:s', strtotime('+' . self::CODE_EXPIRY_MINUTES . ' minutes'));
        User::setVerificationCode($user['user_id'], hash('sha256', $code), $expires);

        return ['success' => true, 'email' => $email, 'name' => $user['name'], 'code' => $code];
    }

    // ---------------- Login ----------------

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

        if (!$user['email_verified']) {
            return ['success' => false, 'errors' => ["Please verify your email before logging in."], 'unverified_email' => $email];
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

    // ---------------- Forgot / reset password (also OTP-based) ----------------

    public static function requestPasswordReset($email) {
        $email = trim($email);
        $user = User::findByEmail($email);

        // Don't reveal whether the email exists — caller always shows the same message,
        // but only actually generates/emails a code when the account is real.
        if (!$user) {
            return ['success' => true, 'exists' => false];
        }

        $code = generate_otp_code();
        $expires = date('Y-m-d H:i:s', strtotime('+' . self::CODE_EXPIRY_MINUTES . ' minutes'));
        User::setResetCode($user['user_id'], hash('sha256', $code), $expires);

        return ['success' => true, 'exists' => true, 'email' => $email, 'name' => $user['name'], 'code' => $code];
    }

    public static function resetPasswordWithCode($email, $code, $newPassword, $confirm) {
        $user = User::findByEmail(trim($email));

        if (!$user || empty($user['reset_token'])) {
            return ['success' => false, 'errors' => ["Invalid or expired request. Please start over."], 'restart' => true];
        }
        if ($user['reset_attempts'] >= self::MAX_CODE_ATTEMPTS) {
            return ['success' => false, 'errors' => ["Too many incorrect attempts. Please request a new code."], 'restart' => true];
        }
        if (strtotime($user['reset_expires']) < time()) {
            return ['success' => false, 'errors' => ["This code has expired. Please request a new one."], 'restart' => true];
        }
        if (!hash_equals($user['reset_token'], hash('sha256', trim($code)))) {
            User::incrementResetAttempts($user['user_id']);
            $remaining = self::MAX_CODE_ATTEMPTS - ($user['reset_attempts'] + 1);
            return ['success' => false, 'errors' => ["Incorrect code. $remaining attempt(s) remaining."]];
        }

        $errors = [];
        if (strlen($newPassword) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        }
        if ($newPassword !== $confirm) {
            $errors[] = "Passwords do not match.";
        }
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        User::updatePassword($user['user_id'], password_hash($newPassword, PASSWORD_DEFAULT));
        User::clearResetCode($user['user_id']);

        return ['success' => true, 'redirect' => 'login.php', 'flash' => 'Password reset successful! Please log in with your new password.'];
    }
}
?>
