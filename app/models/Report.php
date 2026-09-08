<?php
require_once __DIR__ . '/../Database.php';

/**
 * app/models/Report.php
 */
class Report {
    private static function db() {
        return Database::connect();
    }

    public static function create($reporterId, $productId, $reason) {
        $stmt = self::db()->prepare(
            "INSERT INTO reports (reporter_id, product_id, reason, status) VALUES (?, ?, ?, 'pending')"
        );
        return $stmt->execute([$reporterId, $productId, $reason]);
    }

    public static function all() {
        return self::db()->query(
            "SELECT reports.*, products.title AS product_title, products.status AS product_status,
                    reporter.name AS reporter_name, seller.name AS seller_name
             FROM reports
             JOIN products ON reports.product_id = products.product_id
             JOIN users AS reporter ON reports.reporter_id = reporter.user_id
             JOIN users AS seller ON products.seller_id = seller.user_id
             ORDER BY reports.reported_at DESC"
        )->fetchAll();
    }

    public static function findById($reportId) {
        $stmt = self::db()->prepare("SELECT * FROM reports WHERE report_id = ?");
        $stmt->execute([$reportId]);
        return $stmt->fetch();
    }

    public static function markReviewed($reportId) {
        self::db()->prepare("UPDATE reports SET status = 'reviewed' WHERE report_id = ?")->execute([$reportId]);
    }

    public static function pendingCount() {
        return self::db()->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn();
    }
}
?>
