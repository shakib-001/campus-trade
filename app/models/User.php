<?php
require_once __DIR__ . '/../Database.php';

/**
 * app/models/User.php
 * Wraps all `users` table queries used across the app.
 */
class User {
    private static function db() {
        return Database::connect();
    }

    public static function findById($userId) {
        $stmt = self::db()->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public static function findByEmail($email) {
        $stmt = self::db()->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public static function emailExists($email) {
        return self::findByEmail($email) !== false;
    }

    public static function create($name, $studentId, $email, $phone, $hashedPassword) {
        $stmt = self::db()->prepare(
            "INSERT INTO users (name, student_id, email, phone, password, role) VALUES (?, ?, ?, ?, ?, 'student')"
        );
        $stmt->execute([$name, $studentId, $email, $phone, $hashedPassword]);
        return self::db()->lastInsertId();
    }

    public static function updateProfile($userId, $name, $studentId, $phone, $profilePic) {
        $stmt = self::db()->prepare(
            "UPDATE users SET name = ?, student_id = ?, phone = ?, profile_pic = ? WHERE user_id = ?"
        );
        return $stmt->execute([$name, $studentId, $phone, $profilePic, $userId]);
    }

    public static function updatePassword($userId, $hashedPassword) {
        $stmt = self::db()->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        return $stmt->execute([$hashedPassword, $userId]);
    }

    public static function all() {
        return self::db()->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
    }

    public static function setStatus($userId, $status) {
        $stmt = self::db()->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        return $stmt->execute([$status, $userId]);
    }

    public static function countStudents() {
        return self::db()->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
    }

    /** Average rating + review count for a user, computed from the reviews table. */
    public static function getRating($userId) {
        $stmt = self::db()->prepare(
            "SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM reviews WHERE reviewed_user_id = ?"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return [
            'avg' => $row['avg_rating'] ? round($row['avg_rating'], 1) : 0,
            'count' => (int) $row['cnt'],
        ];
    }

    // --- Password reset (forgot-password flow) ---

    public static function setResetToken($userId, $token, $expiresAt) {
        $stmt = self::db()->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE user_id = ?");
        $stmt->execute([$token, $expiresAt, $userId]);
    }

    public static function findByValidResetToken($token) {
        $stmt = self::db()->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    public static function clearResetToken($userId) {
        self::db()->prepare("UPDATE users SET reset_token = NULL, reset_expires = NULL WHERE user_id = ?")
            ->execute([$userId]);
    }
}
?>
