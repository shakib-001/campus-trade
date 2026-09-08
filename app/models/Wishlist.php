<?php
require_once __DIR__ . '/../Database.php';

/**
 * app/models/Wishlist.php
 */
class Wishlist {
    private static function db() {
        return Database::connect();
    }

    /** Adds if not already saved, removes if it is. Returns 'added' or 'removed'. */
    public static function toggle($userId, $productId) {
        $check = self::db()->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $check->execute([$userId, $productId]);
        $existing = $check->fetch();

        if ($existing) {
            self::db()->prepare("DELETE FROM wishlist WHERE wishlist_id = ?")->execute([$existing['wishlist_id']]);
            return 'removed';
        }
        self::db()->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)")->execute([$userId, $productId]);
        return 'added';
    }

    public static function productIdsForUser($userId) {
        $stmt = self::db()->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function findForUser($userId) {
        $stmt = self::db()->prepare(
            "SELECT products.*, categories.category_name, users.name AS seller_name
             FROM wishlist
             JOIN products ON wishlist.product_id = products.product_id
             JOIN categories ON products.category_id = categories.category_id
             JOIN users ON products.seller_id = users.user_id
             WHERE wishlist.user_id = ?
             ORDER BY wishlist.wishlist_id DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
?>
