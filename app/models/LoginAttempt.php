<?php
require_once __DIR__ . '/../Database.php';

/**
 * app/models/LoginAttempt.php
 * Tracks failed logins for rate-limiting brute-force attempts.
 */
class LoginAttempt {
    private static function db() {
        return Database::connect();
    }

    public static function recentCount($email, $minutes = 15) {
        $stmt = self::db()->prepare(
            "SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > (NOW() - INTERVAL {$minutes} MINUTE)"
        );
        $stmt->execute([$email]);
        return (int) $stmt->fetchColumn();
    }

    public static function log($email, $ip) {
        self::db()->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)")->execute([$email, $ip]);
    }

    public static function clear($email) {
        self::db()->prepare("DELETE FROM login_attempts WHERE email = ?")->execute([$email]);
    }
}
?>
