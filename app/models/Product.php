<?php
require_once __DIR__ . '/../Database.php';

/**
 * app/models/Product.php
 * Wraps all `products` table queries — listings, browsing, filtering.
 */
class Product {
    private static function db() {
        return Database::connect();
    }

    public static function findById($productId) {
        $stmt = self::db()->prepare(
            "SELECT products.*, categories.category_name, users.name AS seller_name
             FROM products
             JOIN categories ON products.category_id = categories.category_id
             JOIN users ON products.seller_id = users.user_id
             WHERE products.product_id = ?"
        );
        $stmt->execute([$productId]);
        return $stmt->fetch();
    }

    /** Available listings, optionally filtered by category/keyword, sorted, paginated. */
    public static function findAvailable($categoryId = '', $keyword = '', $perPage = 9, $page = 1, $sort = 'newest') {
        $sql = "SELECT products.*, categories.category_name, users.name AS seller_name
                FROM products
                JOIN categories ON products.category_id = categories.category_id
                JOIN users ON products.seller_id = users.user_id
                WHERE products.status = 'available'";
        $params = [];

        if (!empty($categoryId)) {
            $sql .= " AND products.category_id = ?";
            $params[] = $categoryId;
        }
        if (!empty($keyword)) {
            $sql .= " AND products.title LIKE ?";
            $params[] = "%$keyword%";
        }

        if ($sort === 'price_low') {
            $orderBy = "products.price ASC";
        } elseif ($sort === 'price_high') {
            $orderBy = "products.price DESC";
        } else {
            $orderBy = "products.posted_at DESC"; // 'newest' (default)
        }
        $sql .= " ORDER BY $orderBy";

        $offset = (max(1, $page) - 1) * $perPage;
        $sql .= " LIMIT $perPage OFFSET $offset";

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countAvailable($categoryId = '', $keyword = '') {
        $sql = "SELECT COUNT(*) FROM products WHERE status = 'available'";
        $params = [];
        if (!empty($categoryId)) { $sql .= " AND category_id = ?"; $params[] = $categoryId; }
        if (!empty($keyword)) { $sql .= " AND title LIKE ?"; $params[] = "%$keyword%"; }
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function findBySeller($sellerId) {
        $stmt = self::db()->prepare(
            "SELECT products.*, categories.category_name FROM products
             JOIN categories ON products.category_id = categories.category_id
             WHERE products.seller_id = ? ORDER BY products.posted_at DESC"
        );
        $stmt->execute([$sellerId]);
        return $stmt->fetchAll();
    }

    public static function create($sellerId, $categoryId, $title, $description, $price, $condition, $listingType, $image) {
        $stmt = self::db()->prepare(
            "INSERT INTO products (seller_id, category_id, title, description, price, item_condition, listing_type, image)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$sellerId, $categoryId, $title, $description, $price, $condition, $listingType, $image]);
        return self::db()->lastInsertId();
    }

    public static function update($productId, $title, $categoryId, $description, $price, $condition, $listingType, $image) {
        $stmt = self::db()->prepare(
            "UPDATE products SET title=?, category_id=?, description=?, price=?, item_condition=?, listing_type=?, image=?
             WHERE product_id=?"
        );
        return $stmt->execute([$title, $categoryId, $description, $price, $condition, $listingType, $image, $productId]);
    }

    public static function delete($productId) {
        $stmt = self::db()->prepare("DELETE FROM products WHERE product_id = ?");
        return $stmt->execute([$productId]);
    }

    public static function setStatus($productId, $status) {
        $stmt = self::db()->prepare("UPDATE products SET status = ? WHERE product_id = ?");
        return $stmt->execute([$status, $productId]);
    }

    public static function isOwnedBy($productId, $userId) {
        $stmt = self::db()->prepare("SELECT 1 FROM products WHERE product_id = ? AND seller_id = ?");
        $stmt->execute([$productId, $userId]);
        return (bool) $stmt->fetch();
    }

    public static function countAll() {
        return (int) self::db()->query("SELECT COUNT(*) FROM products")->fetchColumn();
    }
}
?>
