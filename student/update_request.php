<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../app/models/ProductRequest.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($id && in_array($action, ['accept', 'reject', 'complete'])) {
    // Verify this request belongs to a product owned by the logged-in seller
    $req = ProductRequest::belongsToSeller($id, $_SESSION['user_id']);

    if ($req) {
        if ($action === 'accept' && $req['status'] === 'pending') {
            ProductRequest::accept($id, $req['product_id']);
            $_SESSION['success'] = "Request accepted.";
        } elseif ($action === 'reject' && $req['status'] === 'pending') {
            ProductRequest::reject($id);
            $_SESSION['success'] = "Request rejected.";
        } elseif ($action === 'complete' && $req['status'] === 'accepted') {
            ProductRequest::complete($id, $req['product_id']);
            $_SESSION['success'] = "Transaction completed! Item marked as sold.";
        }
    }
}

header("Location: incoming_requests.php");
exit;
?>
