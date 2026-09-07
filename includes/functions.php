<?php
// includes/functions.php — small reusable helpers shared across pages.

// Renders a row of filled/empty star characters for a given average rating (0–5).
function render_stars($rating, $max = 5) {
    $rounded = round($rating);
    $html = '<span class="stars-display">';
    for ($i = 1; $i <= $max; $i++) {
        $filled = $i <= $rounded;
        $html .= '<span class="' . ($filled ? 'star-filled' : 'star-empty') . '">★</span>';
    }
    $html .= '</span>';
    return $html;
}

// Returns ['avg' => float, 'count' => int] for a given user's received reviews.
function get_user_rating($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM reviews WHERE reviewed_user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return [
        'avg' => $row['avg_rating'] ? round($row['avg_rating'], 1) : 0,
        'count' => (int) $row['cnt'],
    ];
}

// Validates a Bangladeshi mobile number: exactly 11 digits, starting with 01.
function is_valid_bd_phone($phone) {
    return (bool) preg_match('/^01[0-9]{9}$/', $phone);
}
?>
