<?php
/**
 * api/wishlist_toggle.php
 * POST product_id, csrf_token -> JSON { success, status: 'added'|'removed' }
 */
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../config/csrf.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

csrf_verify_json();

$product_id = $_POST['product_id'] ?? null;
if (!$product_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing product_id']);
    exit;
}

$check = $pdo->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
$check->execute([$_SESSION['user_id'], $product_id]);
$existing = $check->fetch();

if ($existing) {
    $pdo->prepare("DELETE FROM wishlist WHERE wishlist_id = ?")->execute([$existing['wishlist_id']]);
    echo json_encode(['success' => true, 'status' => 'removed']);
} else {
    $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)")->execute([$_SESSION['user_id'], $product_id]);
    echo json_encode(['success' => true, 'status' => 'added']);
}
?>
