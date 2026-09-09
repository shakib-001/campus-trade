<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../app/controllers/RequestController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($id && in_array($action, ['accept', 'reject', 'complete'])) {
    $result = RequestController::updateStatus($id, $_SESSION['user_id'], $action);
    if ($result['flash']) {
        $_SESSION['success'] = $result['flash'];
    }
}

header("Location: incoming_requests.php");
exit;
?>
