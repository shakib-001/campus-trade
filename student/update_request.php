<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($id && in_array($action, ['accept', 'reject', 'complete'])) {
    // Verify this request belongs to a product owned by the logged-in seller
    $stmt = $pdo->prepare(
        "SELECT requests.*, products.seller_id, products.product_id
         FROM requests JOIN products ON requests.product_id = products.product_id
         WHERE requests.request_id = ?"
    );
    $stmt->execute([$id]);
    $req = $stmt->fetch();

    if ($req && $req['seller_id'] == $_SESSION['user_id']) {
        if ($action === 'accept' && $req['status'] === 'pending') {
            $pdo->prepare("UPDATE requests SET status = 'accepted' WHERE request_id = ?")->execute([$id]);
            // Auto-reject other pending requests for the same product
            $pdo->prepare("UPDATE requests SET status = 'rejected' WHERE product_id = ? AND request_id != ? AND status = 'pending'")
                ->execute([$req['product_id'], $id]);
            $_SESSION['success'] = "Request accepted.";
        } elseif ($action === 'reject' && $req['status'] === 'pending') {
            $pdo->prepare("UPDATE requests SET status = 'rejected' WHERE request_id = ?")->execute([$id]);
            $_SESSION['success'] = "Request rejected.";
        } elseif ($action === 'complete' && $req['status'] === 'accepted') {
            $pdo->prepare("UPDATE requests SET status = 'completed' WHERE request_id = ?")->execute([$id]);
            $pdo->prepare("UPDATE products SET status = 'sold' WHERE product_id = ?")->execute([$req['product_id']]);
            $_SESSION['success'] = "Transaction completed! Item marked as sold.";
        }
    }
}

header("Location: incoming_requests.php");
exit;
?>
