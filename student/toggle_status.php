<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($id && $action === 'sold') {
    // Verify ownership before updating
    $stmt = $pdo->prepare("UPDATE products SET status = 'sold' WHERE product_id = ? AND seller_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $_SESSION['success'] = "Item marked as sold.";
}

header("Location: my_listings.php");
exit;
?>
