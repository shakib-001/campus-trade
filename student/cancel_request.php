<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../app/controllers/RequestController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if ($id) {
    // Only the buyer who made the pending request can cancel it
    $result = RequestController::cancel($id, $_SESSION['user_id']);
    $_SESSION['success'] = $result['flash'];
}

header("Location: my_requests.php");
exit;
?>
