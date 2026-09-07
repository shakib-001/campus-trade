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
?>
