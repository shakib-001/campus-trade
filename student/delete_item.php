<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../app/models/Product.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if ($id) {
    // Verify ownership before deleting
    $product = Product::findById($id);

    if ($product && $product['seller_id'] == $_SESSION['user_id']) {
        if ($product['image'] && file_exists('../assets/uploads/' . $product['image'])) {
            unlink('../assets/uploads/' . $product['image']);
        }
        Product::delete($id);
        $_SESSION['success'] = "Item deleted.";
    }
}

header("Location: my_listings.php");
exit;
?>
