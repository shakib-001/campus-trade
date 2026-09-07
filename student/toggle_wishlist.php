<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$product_id = $_GET['product_id'] ?? null;
$redirect = $_GET['redirect'] ?? 'browse_items.php';

if ($product_id) {
    $check = $pdo->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $check->execute([$_SESSION['user_id'], $product_id]);
    $existing = $check->fetch();

    if ($existing) {
        $pdo->prepare("DELETE FROM wishlist WHERE wishlist_id = ?")->execute([$existing['wishlist_id']]);
    } else {
        $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)")->execute([$_SESSION['user_id'], $product_id]);
    }
}

header("Location: " . $redirect);
exit;
?>
