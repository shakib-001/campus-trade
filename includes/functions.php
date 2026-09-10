<?php
// includes/functions.php — small reusable helpers shared across pages.

// Validates a Bangladeshi mobile number: exactly 11 digits, starting with 01.
function is_valid_bd_phone($phone) {
    return (bool) preg_match('/^01[0-9]{9}$/', $phone);
}

// Generates a 6-digit numeric one-time code (e.g. "042917"), used for both
// email verification (registration) and password-reset codes.
function generate_otp_code() {
    return sprintf('%06d', random_int(0, 999999));
}

/**
 * Sends an email if config/mailer_config.php has been filled in; otherwise
 * reports back "not configured" so the caller can fall back to dev-mode
 * (e.g. showing the OTP code directly on screen instead of emailing it).
 * Returns ['sent' => bool, 'configured' => bool, 'error' => string|null].
 */
function send_app_email($toEmail, $toName, $subject, $bodyHtml) {
    require_once __DIR__ . '/SimpleMailer.php';
    $mailConfig = require __DIR__ . '/../config/mailer_config.php';

    if ($mailConfig['username'] === 'your-email@gmail.com') {
        return ['sent' => false, 'configured' => false, 'error' => null];
    }

    try {
        $mailer = new SimpleMailer(
            $mailConfig['host'], $mailConfig['port'],
            $mailConfig['username'], $mailConfig['password'],
            $mailConfig['from_email'], $mailConfig['from_name']
        );
        $mailer->send($toEmail, $toName, $subject, $bodyHtml);
        return ['sent' => true, 'configured' => true, 'error' => null];
    } catch (Exception $e) {
        return ['sent' => false, 'configured' => true, 'error' => $e->getMessage()];
    }
}
?>
