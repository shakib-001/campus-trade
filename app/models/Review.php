<?php
require_once __DIR__ . '/../Database.php';

/**
 * app/models/Review.php
 */
class Review {
    private static function db() {
        return Database::connect();
    }

    public static function create($reviewerId, $reviewedUserId, $requestId, $rating, $comment) {
        $stmt = self::db()->prepare(
            "INSERT INTO reviews (reviewer_id, reviewed_user_id, request_id, rating, comment) VALUES (?, ?, ?, ?, ?)"
        );
        return $stmt->execute([$reviewerId, $reviewedUserId, $requestId, $rating, $comment]);
    }

    public static function existsForRequest($reviewerId, $requestId) {
        $stmt = self::db()->prepare("SELECT review_id FROM reviews WHERE reviewer_id = ? AND request_id = ?");
        $stmt->execute([$reviewerId, $requestId]);
        return (bool) $stmt->fetch();
    }

    public static function findForUser($userId) {
        $stmt = self::db()->prepare(
            "SELECT reviews.*, reviewer.name AS reviewer_name
             FROM reviews JOIN users AS reviewer ON reviews.reviewer_id = reviewer.user_id
             WHERE reviews.reviewed_user_id = ? ORDER BY reviews.created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Renders a row of filled/empty stars for a given rating (0–5). Shared UI helper. */
    public static function renderStars($rating, $max = 5) {
        $rounded = round($rating);
        $html = '<span class="stars-display">';
        for ($i = 1; $i <= $max; $i++) {
            $filled = $i <= $rounded;
            $html .= '<span class="' . ($filled ? 'star-filled' : 'star-empty') . '">★</span>';
        }
        $html .= '</span>';
        return $html;
    }
}
?>
