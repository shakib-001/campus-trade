<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../app/models/Product.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($id && $action === 'sold' && Product::isOwnedBy($id, $_SESSION['user_id'])) {
    Product::setStatus($id, 'sold');
    $_SESSION['success'] = "Item marked as sold.";
}

header("Location: my_listings.php");
exit;
?>
