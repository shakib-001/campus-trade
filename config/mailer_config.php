<?php
/**
 * config/mailer_config.php
 *
 * Fill these in with your own email account to enable real password-reset
 * emails. Gmail works well for this:
 *   1. Turn on 2-Step Verification on the Gmail account: myaccount.google.com/security
 *   2. Create an "App Password": myaccount.google.com/apppasswords
 *      (choose "Mail" as the app) — Google gives you a 16-character password.
 *   3. Paste that app password below (NOT your normal Gmail password).
 *
 * Until you fill this in, Forgot Password will still work in "dev mode" —
 * it shows the reset link directly on screen instead of emailing it.
 */

return [
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'username'   => 'your-email@gmail.com',      // TODO: replace with your Gmail address
    'password'   => 'your-16-char-app-password',  // TODO: replace with your Gmail App Password
    'from_email' => 'your-email@gmail.com',       // usually the same as username
    'from_name'  => 'Campus Trade',
];
?>
