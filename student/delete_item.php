<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if ($id) {
    // Verify ownership before deleting
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ? AND seller_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $product = $stmt->fetch();

    if ($product) {
        if ($product['image'] && file_exists('../assets/uploads/' . $product['image'])) {
            unlink('../assets/uploads/' . $product['image']);
        }
        $del = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
        $del->execute([$id]);
        $_SESSION['success'] = "Item deleted.";
    }
}

header("Location: my_listings.php");
exit;
?>
