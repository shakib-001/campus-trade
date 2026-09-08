<?php
/**
 * api/get_messages.php
 * GET product_id, with, after_id -> JSON { success, messages: [...] }
 * Returns only messages newer than after_id, so the chat page can poll
 * for new messages without re-fetching the whole conversation each time.
 */
require_once '../includes/session_init.php';
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$product_id = $_GET['product_id'] ?? null;
$with = $_GET['with'] ?? null;
$after = $_GET['after_id'] ?? 0;
$myId = $_SESSION['user_id'];

if (!$product_id || !$with) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT * FROM messages
     WHERE product_id = ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
     AND message_id > ?
     ORDER BY sent_at ASC"
);
$stmt->execute([$product_id, $myId, $with, $with, $myId, $after]);
$messages = $stmt->fetchAll();

echo json_encode(['success' => true, 'messages' => $messages]);
?>
