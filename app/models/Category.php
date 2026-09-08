<?php
require_once __DIR__ . '/../Database.php';

/**
 * app/models/Category.php
 */
class Category {
    private static function db() {
        return Database::connect();
    }

    public static function all() {
        return self::db()->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();
    }

    public static function allWithItemCounts() {
        return self::db()->query(
            "SELECT categories.*, COUNT(products.product_id) AS item_count
             FROM categories
             LEFT JOIN products ON categories.category_id = products.category_id
             GROUP BY categories.category_id
             ORDER BY categories.category_name"
        )->fetchAll();
    }

    public static function create($name) {
        $stmt = self::db()->prepare("INSERT INTO categories (category_name) VALUES (?)");
        return $stmt->execute([$name]);
    }

    public static function delete($categoryId) {
        // Refuse to delete a category that still has items in it
        $check = self::db()->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $check->execute([$categoryId]);
        if ($check->fetchColumn() > 0) {
            return false;
        }
        $stmt = self::db()->prepare("DELETE FROM categories WHERE category_id = ?");
        return $stmt->execute([$categoryId]);
    }
}
?>
