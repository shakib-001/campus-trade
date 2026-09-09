<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../app/models/Wishlist.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$product_id = $_GET['product_id'] ?? null;
$redirect = $_GET['redirect'] ?? 'browse_items.php';

if ($product_id) {
    Wishlist::toggle($_SESSION['user_id'], $product_id);
}

header("Location: " . $redirect);
exit;
?>
