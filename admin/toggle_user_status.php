<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../app/models/User.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

// Admin can't block themselves
if ($id && $id != $_SESSION['user_id'] && in_array($action, ['block', 'unblock'])) {
    $newStatus = $action === 'block' ? 'blocked' : 'active';
    User::setStatus($id, $newStatus);
    $_SESSION['success'] = "User " . ($action === 'block' ? 'blocked' : 'unblocked') . " successfully.";
}

header("Location: manage_users.php");
exit;
?>
