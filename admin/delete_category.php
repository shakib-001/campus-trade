<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../app/models/Category.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if ($id) {
    if (Category::delete($id)) {
        $_SESSION['success'] = "Category deleted.";
    } else {
        $_SESSION['success'] = "Can't delete — this category still has items in it.";
    }
}

header("Location: manage_categories.php");
exit;
?>
