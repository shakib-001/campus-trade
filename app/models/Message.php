<?php
require_once __DIR__ . '/../Database.php';

/**
 * app/models/Message.php
 */
class Message {
    private static function db() {
        return Database::connect();
    }

    public static function send($senderId, $receiverId, $productId, $text) {
        $stmt = self::db()->prepare(
            "INSERT INTO messages (sender_id, receiver_id, product_id, message_text) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$senderId, $receiverId, $productId, $text]);
        $id = self::db()->lastInsertId();
        return self::findById($id);
    }

    public static function findById($messageId) {
        $stmt = self::db()->prepare("SELECT * FROM messages WHERE message_id = ?");
        $stmt->execute([$messageId]);
        return $stmt->fetch();
    }

    /** Full message thread between two users about a specific product. */
    public static function findConversation($productId, $userA, $userB, $afterId = 0) {
        $stmt = self::db()->prepare(
            "SELECT * FROM messages
             WHERE product_id = ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
             AND message_id > ?
             ORDER BY sent_at ASC"
        );
        $stmt->execute([$productId, $userA, $userB, $userB, $userA, $afterId]);
        return $stmt->fetchAll();
    }

    /** All messages involving this user, newest first — used to build the inbox list. */
    public static function findAllForUser($userId) {
        $stmt = self::db()->prepare(
            "SELECT messages.*, products.title AS product_title,
                    sender.name AS sender_name, receiver.name AS receiver_name
             FROM messages
             JOIN products ON messages.product_id = products.product_id
             JOIN users AS sender ON messages.sender_id = sender.user_id
             JOIN users AS receiver ON messages.receiver_id = receiver.user_id
             WHERE messages.sender_id = ? OR messages.receiver_id = ?
             ORDER BY messages.sent_at DESC"
        );
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll();
    }
}
?>
