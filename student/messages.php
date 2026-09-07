<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$myId = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT messages.*, products.title AS product_title,
            sender.name AS sender_name, receiver.name AS receiver_name
     FROM messages
     JOIN products ON messages.product_id = products.product_id
     JOIN users AS sender ON messages.sender_id = sender.user_id
     JOIN users AS receiver ON messages.receiver_id = receiver.user_id
     WHERE messages.sender_id = ? OR messages.receiver_id = ?
     ORDER BY messages.sent_at DESC"
);
$stmt->execute([$myId, $myId]);
$allMessages = $stmt->fetchAll();

// Group into unique conversations (product + other user), keeping only the latest message per group
$conversations = [];
foreach ($allMessages as $msg) {
    $isMine = ($msg['sender_id'] == $myId);
    $otherId = $isMine ? $msg['receiver_id'] : $msg['sender_id'];
    $otherName = $isMine ? $msg['receiver_name'] : $msg['sender_name'];
    $key = $msg['product_id'] . '_' . $otherId;

    if (!isset($conversations[$key])) {
        $conversations[$key] = [
            'product_id' => $msg['product_id'],
            'product_title' => $msg['product_title'],
            'other_id' => $otherId,
            'other_name' => $otherName,
            'last_message' => $msg['message_text'],
            'sent_at' => $msg['sent_at'],
        ];
    }
}

$pageTitle = "Messages";
require_once '../includes/header.php';
?>

<h3 class="mb-3">Messages</h3>

<?php if (empty($conversations)): ?>
    <div class="empty-state">
        <span class="empty-icon">💬</span>
        No conversations yet. Message a seller from any item's page to get started.
    </div>
<?php else: ?>
    <div class="list-group">
        <?php foreach ($conversations as $conv): ?>
            <a href="chat.php?product_id=<?= $conv['product_id'] ?>&with=<?= $conv['other_id'] ?>"
               class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between">
                    <strong><?= htmlspecialchars($conv['other_name']) ?></strong>
                    <small class="text-muted"><?= date('d M, h:i A', strtotime($conv['sent_at'])) ?></small>
                </div>
                <div class="small text-muted">Re: <?= htmlspecialchars($conv['product_title']) ?></div>
                <div><?= htmlspecialchars(mb_strimwidth($conv['last_message'], 0, 80, '...')) ?></div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
