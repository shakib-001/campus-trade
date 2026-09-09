<?php
/**
 * includes/session_init.php
 *
 * Centralized, hardened session start — included by every page instead of
 * calling session_start() directly, so the whole app gets the same secure
 * cookie settings from one place.
 */

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

session_set_cookie_params([
    'lifetime' => 0,       // expires when the browser closes
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isHttps,  // cookie only sent over HTTPS once deployed with SSL; false on local HTTP so login still works on XAMPP
    'httponly' => true,      // JavaScript can't read the session cookie — mitigates session theft via XSS
    'samesite' => 'Lax',     // cookie isn't sent on most cross-site requests — mitigates basic CSRF
]);

session_start();

// Session idle timeout — log the user out after 30 minutes of inactivity
$timeoutSeconds = 1800;
if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])
    && (time() - $_SESSION['last_activity']) > $timeoutSeconds) {
    session_unset();
    session_destroy();
    session_start(); // start a fresh session so the rest of this file can still run
}
$_SESSION['last_activity'] = time();

// "Remember Me" auto-login — if there's no active session but a valid
// remember-me cookie exists, log the user back in transparently.
if (!isset($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../app/models/RememberToken.php';

    [$selector, $validator] = array_pad(explode(':', $_COOKIE['remember_token'], 2), 2, null);

    if ($selector && $validator) {
        $row = RememberToken::findValidBySelector($selector);

        if ($row && hash_equals($row['hashed_validator'], hash('sha256', $validator)) && $row['status'] !== 'blocked') {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['last_activity'] = time();

            // Rotate the validator on each auto-login — limits damage if a cookie is ever stolen
            $newValidator = bin2hex(random_bytes(32));
            RememberToken::rotate($row['token_id'], hash('sha256', $newValidator));
            setcookie('remember_token', $selector . ':' . $newValidator, [
                'expires' => strtotime('+30 days'),
                'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => $isHttps,
            ]);
        } else {
            // Invalid/expired/stale cookie — clear it so we don't keep trying
            setcookie('remember_token', '', ['expires' => time() - 3600, 'path' => '/']);
        }
    }
}
?>
