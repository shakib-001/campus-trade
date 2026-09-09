<?php
require_once __DIR__ . '/../Database.php';

/**
 * app/models/RememberToken.php
 * "Remember Me" tokens — selector/validator pattern.
 */
class RememberToken {
    private static function db() {
        return Database::connect();
    }

    public static function create($userId, $selector, $hashedValidator, $expiresAt) {
        $stmt = self::db()->prepare(
            "INSERT INTO remember_tokens (user_id, selector, hashed_validator, expires_at) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $selector, $hashedValidator, $expiresAt]);
        return self::db()->lastInsertId();
    }

    /** Finds a non-expired token by selector, joined with its owning user. */
    public static function findValidBySelector($selector) {
        $stmt = self::db()->prepare(
            "SELECT remember_tokens.*, users.* FROM remember_tokens
             JOIN users ON remember_tokens.user_id = users.user_id
             WHERE selector = ? AND expires_at > NOW()"
        );
        $stmt->execute([$selector]);
        return $stmt->fetch();
    }

    public static function rotate($tokenId, $newHashedValidator) {
        self::db()->prepare("UPDATE remember_tokens SET hashed_validator = ? WHERE token_id = ?")
            ->execute([$newHashedValidator, $tokenId]);
    }

    public static function deleteBySelector($selector) {
        self::db()->prepare("DELETE FROM remember_tokens WHERE selector = ?")->execute([$selector]);
    }
}
?>
