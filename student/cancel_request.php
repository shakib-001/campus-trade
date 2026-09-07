<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if ($id) {
    // Only the buyer who made the pending request can cancel it
    $stmt = $pdo->prepare("UPDATE requests SET status = 'rejected' WHERE request_id = ? AND buyer_id = ? AND status = 'pending'");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $_SESSION['success'] = "Request cancelled.";
}

header("Location: my_requests.php");
exit;
?>
