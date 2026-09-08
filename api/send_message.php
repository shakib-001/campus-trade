<?php
/**
 * api/send_message.php
 * POST product_id, with, message_text, csrf_token -> JSON { success, message: {...} }
 */
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../config/csrf.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

csrf_verify_json();

$product_id = $_POST['product_id'] ?? null;
$with = $_POST['with'] ?? null;
$text = trim($_POST['message_text'] ?? '');
$myId = $_SESSION['user_id'];

if (!$product_id || !$with || $with == $myId || $text === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid message']);
    exit;
}

$stmt = $pdo->prepare(
    "INSERT INTO messages (sender_id, receiver_id, product_id, message_text) VALUES (?, ?, ?, ?)"
);
$stmt->execute([$myId, $with, $product_id, $text]);
$messageId = $pdo->lastInsertId();

$stmt = $pdo->prepare("SELECT * FROM messages WHERE message_id = ?");
$stmt->execute([$messageId]);
$message = $stmt->fetch();

echo json_encode(['success' => true, 'message' => $message]);
?>
