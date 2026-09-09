<?php
require_once __DIR__ . '/../Database.php';

/**
 * app/models/ProductRequest.php
 * Wraps the `requests` table — buy/rent requests between buyer and seller.
 * (Named ProductRequest, not Request, to avoid confusion with an HTTP request.)
 */
class ProductRequest {
    private static function db() {
        return Database::connect();
    }

    public static function findById($requestId) {
        $stmt = self::db()->prepare("SELECT * FROM requests WHERE request_id = ?");
        $stmt->execute([$requestId]);
        return $stmt->fetch();
    }

    public static function create($productId, $buyerId, $meetupLocation, $meetupTime) {
        $stmt = self::db()->prepare(
            "INSERT INTO requests (product_id, buyer_id, meetup_location, meetup_time, status)
             VALUES (?, ?, ?, ?, 'pending')"
        );
        $stmt->execute([$productId, $buyerId, $meetupLocation, $meetupTime]);
        return self::db()->lastInsertId();
    }

    public static function hasPendingRequest($productId, $buyerId) {
        $stmt = self::db()->prepare(
            "SELECT request_id FROM requests WHERE product_id = ? AND buyer_id = ? AND status = 'pending'"
        );
        $stmt->execute([$productId, $buyerId]);
        return (bool) $stmt->fetch();
    }

    /** Requests sent BY this user (as buyer). */
    public static function findByBuyer($buyerId) {
        $stmt = self::db()->prepare(
            "SELECT requests.*, products.title, products.price, users.name AS seller_name
             FROM requests
             JOIN products ON requests.product_id = products.product_id
             JOIN users ON products.seller_id = users.user_id
             WHERE requests.buyer_id = ?
             ORDER BY requests.requested_at DESC"
        );
        $stmt->execute([$buyerId]);
        return $stmt->fetchAll();
    }

    /** Requests received BY this user (as seller), across all their products. */
    public static function findBySeller($sellerId) {
        $stmt = self::db()->prepare(
            "SELECT requests.*, products.title, products.price, products.status AS product_status, users.name AS buyer_name
             FROM requests
             JOIN products ON requests.product_id = products.product_id
             JOIN users ON requests.buyer_id = users.user_id
             WHERE products.seller_id = ?
             ORDER BY requests.requested_at DESC"
        );
        $stmt->execute([$sellerId]);
        return $stmt->fetchAll();
    }

    public static function accept($requestId, $productId) {
        self::db()->prepare("UPDATE requests SET status = 'accepted' WHERE request_id = ?")->execute([$requestId]);
        // Auto-reject other pending requests for the same product
        self::db()->prepare(
            "UPDATE requests SET status = 'rejected' WHERE product_id = ? AND request_id != ? AND status = 'pending'"
        )->execute([$productId, $requestId]);
    }

    public static function reject($requestId) {
        self::db()->prepare("UPDATE requests SET status = 'rejected' WHERE request_id = ?")->execute([$requestId]);
    }

    public static function cancel($requestId, $buyerId) {
        $stmt = self::db()->prepare(
            "UPDATE requests SET status = 'rejected' WHERE request_id = ? AND buyer_id = ? AND status = 'pending'"
        );
        return $stmt->execute([$requestId, $buyerId]);
    }

    public static function complete($requestId, $productId) {
        self::db()->prepare("UPDATE requests SET status = 'completed' WHERE request_id = ?")->execute([$requestId]);
        self::db()->prepare("UPDATE products SET status = 'sold' WHERE product_id = ?")->execute([$productId]);
    }

    /** Confirms this request's product belongs to the given seller — used before accept/reject/complete. */
    public static function belongsToSeller($requestId, $sellerId) {
        $stmt = self::db()->prepare(
            "SELECT requests.*, products.seller_id, products.product_id
             FROM requests JOIN products ON requests.product_id = products.product_id
             WHERE requests.request_id = ?"
        );
        $stmt->execute([$requestId]);
        $row = $stmt->fetch();
        return ($row && $row['seller_id'] == $sellerId) ? $row : false;
    }

    /** Fetches a request (with product title + seller_id) only if its status is 'completed'. Used for review eligibility. */
    public static function findCompleted($requestId) {
        $stmt = self::db()->prepare(
            "SELECT requests.*, products.title AS product_title, products.seller_id
             FROM requests JOIN products ON requests.product_id = products.product_id
             WHERE requests.request_id = ? AND requests.status = 'completed'"
        );
        $stmt->execute([$requestId]);
        return $stmt->fetch();
    }
}
?>
