<?php
/**
 * api/get_messages.php
 * GET product_id, with, after_id -> JSON { success, messages: [...] }
 * Returns only messages newer than after_id, so the chat page can poll
 * for new messages without re-fetching the whole conversation each time.
 */
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../app/models/Message.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$product_id = $_GET['product_id'] ?? null;
$with = $_GET['with'] ?? null;
$after = $_GET['after_id'] ?? 0;

if (!$product_id || !$with) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$messages = Message::findConversation($product_id, $_SESSION['user_id'], $with, $after);
echo json_encode(['success' => true, 'messages' => $messages]);
?>
