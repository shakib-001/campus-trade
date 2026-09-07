<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../config/csrf.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$product_id = $_GET['product_id'] ?? $_POST['product_id'] ?? null;
$with = $_GET['with'] ?? $_POST['with'] ?? null;
$myId = $_SESSION['user_id'];

if (!$product_id || !$with || $with == $myId) {
    header("Location: messages.php");
    exit;
}

// Confirm the product and the other user both exist
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$with]);
$otherUser = $stmt->fetch();

if (!$product || !$otherUser) {
    header("Location: messages.php");
    exit;
}

// Send a new message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $text = trim($_POST['message_text'] ?? '');
    if (!empty($text)) {
        $stmt = $pdo->prepare(
            "INSERT INTO messages (sender_id, receiver_id, product_id, message_text) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$myId, $with, $product_id, $text]);
    }
    // Redirect (PRG pattern) to avoid re-submitting the message on refresh
    header("Location: chat.php?product_id=$product_id&with=$with");
    exit;
}

// Fetch the full thread between these two users for this product
$stmt = $pdo->prepare(
    "SELECT * FROM messages
     WHERE product_id = ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
     ORDER BY sent_at ASC"
);
$stmt->execute([$product_id, $myId, $with, $with, $myId]);
$messages = $stmt->fetchAll();

$pageTitle = 'Chat with ' . $otherUser['name'];
require_once '../includes/header.php';
?>

<div class="card shadow-sm mt-3">
    <div class="card-header">
        <strong>Chat with <?= htmlspecialchars($otherUser['name']) ?></strong>
        — about <a href="item_details.php?id=<?= $product_id ?>"><?= htmlspecialchars($product['title']) ?></a>
    </div>
    <div class="card-body chat-scroll">
        <?php if (empty($messages)): ?>
            <p class="text-muted">No messages yet. Say hello! 👋</p>
        <?php endif; ?>
        <?php foreach ($messages as $msg): ?>
            <?php $isMine = ($msg['sender_id'] == $myId); ?>
            <div class="d-flex mb-2 <?= $isMine ? 'justify-content-end' : 'justify-content-start' ?>">
                <div class="p-2 rounded <?= $isMine ? 'bg-primary text-white' : 'bg-light' ?>" style="max-width: 70%;">
                    <?= nl2br(htmlspecialchars($msg['message_text'])) ?>
                    <div class="small <?= $isMine ? 'text-white-50' : 'text-muted' ?>"><?= date('d M, h:i A', strtotime($msg['sent_at'])) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="card-footer">
        <form method="POST" action="chat.php" class="d-flex gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <input type="hidden" name="with" value="<?= $with ?>">
            <input type="text" name="message_text" class="form-control" placeholder="Type a message..." required autofocus>
            <button type="submit" class="btn btn-primary">Send</button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
