<?php
/**
 * api/wishlist_toggle.php
 * POST product_id, csrf_token -> JSON { success, status: 'added'|'removed' }
 */
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../config/csrf.php';
require_once '../app/models/Wishlist.php';
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

$status = Wishlist::toggle($_SESSION['user_id'], $product_id);
echo json_encode(['success' => true, 'status' => $status]);
?>
