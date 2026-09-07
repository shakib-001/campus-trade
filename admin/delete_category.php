<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if ($id) {
    // Only delete if no products use this category (extra safety check besides the UI check)
    $check = $pdo->prepare("SELECT COUNT(*) AS cnt FROM products WHERE category_id = ?");
    $check->execute([$id]);
    $count = $check->fetch()['cnt'];

    if ($count == 0) {
        $pdo->prepare("DELETE FROM categories WHERE category_id = ?")->execute([$id]);
        $_SESSION['success'] = "Category deleted.";
    } else {
        $_SESSION['success'] = "Can't delete — this category still has items in it.";
    }
}

header("Location: manage_categories.php");
exit;
?>
