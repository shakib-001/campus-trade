<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../config/csrf.php';
require_once '../app/models/Product.php';
require_once '../app/models/User.php';
require_once '../app/models/Message.php';

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
$product = Product::findById($product_id);
$otherUser = User::findById($with);

if (!$product || !$otherUser) {
    header("Location: messages.php");
    exit;
}

// Send a new message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $text = trim($_POST['message_text'] ?? '');
    if (!empty($text)) {
        Message::send($myId, $with, $product_id, $text);
    }
    // Redirect (PRG pattern) to avoid re-submitting the message on refresh
    header("Location: chat.php?product_id=$product_id&with=$with");
    exit;
}

// Fetch the full thread between these two users for this product
$messages = Message::findConversation($product_id, $myId, $with);

$pageTitle = 'Chat with ' . $otherUser['name'];
require_once '../includes/header.php';
?>

<div class="card shadow-sm mt-3">
    <div class="card-header">
        <strong>Chat with <?= htmlspecialchars($otherUser['name']) ?></strong>
        — about <a href="item_details.php?id=<?= $product_id ?>"><?= htmlspecialchars($product['title']) ?></a>
    </div>
    <div class="card-body chat-scroll" id="chatThread" data-product-id="<?= $product_id ?>" data-with="<?= $with ?>" data-my-id="<?= $myId ?>">
        <?php if (empty($messages)): ?>
            <p class="text-muted" id="noMessagesNote">No messages yet. Say hello! 👋</p>
        <?php endif; ?>
        <?php foreach ($messages as $msg): ?>
            <?php $isMine = ($msg['sender_id'] == $myId); ?>
            <div class="d-flex mb-2 <?= $isMine ? 'justify-content-end' : 'justify-content-start' ?>" data-msg-id="<?= $msg['message_id'] ?>">
                <div class="p-2 rounded <?= $isMine ? 'bg-primary text-white' : 'bg-light' ?>" style="max-width: 70%;">
                    <?= nl2br(htmlspecialchars($msg['message_text'])) ?>
                    <div class="small <?= $isMine ? 'text-white-50' : 'text-muted' ?>"><?= date('d M, h:i A', strtotime($msg['sent_at'])) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="card-footer">
        <form method="POST" action="chat.php" id="chatForm" class="d-flex gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <input type="hidden" name="with" value="<?= $with ?>">
            <input type="text" name="message_text" id="messageInput" class="form-control" placeholder="Type a message..." required autofocus autocomplete="off">
            <button type="submit" class="btn btn-primary">Send</button>
        </form>
    </div>
</div>

<script>
(function () {
    const thread = document.getElementById('chatThread');
    const form = document.getElementById('chatForm');
    const input = document.getElementById('messageInput');
    const productId = thread.dataset.productId;
    const withId = thread.dataset.with;
    const myId = thread.dataset.myId;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function lastMessageId() {
        const bubbles = thread.querySelectorAll('[data-msg-id]');
        if (bubbles.length === 0) return 0;
        return bubbles[bubbles.length - 1].dataset.msgId;
    }

    function scrollToBottom() {
        thread.scrollTop = thread.scrollHeight;
    }

    function appendMessage(msg) {
        const note = document.getElementById('noMessagesNote');
        if (note) note.remove();

        const isMine = String(msg.sender_id) === String(myId);
        const wrapper = document.createElement('div');
        wrapper.className = 'd-flex mb-2 ' + (isMine ? 'justify-content-end' : 'justify-content-start');
        wrapper.dataset.msgId = msg.message_id;

        const bubble = document.createElement('div');
        bubble.className = 'p-2 rounded ' + (isMine ? 'bg-primary text-white' : 'bg-light');
        bubble.style.maxWidth = '70%';

        const textDiv = document.createElement('div');
        textDiv.textContent = msg.message_text; // textContent — safe against XSS, no HTML injection
        bubble.appendChild(textDiv);

        const timeDiv = document.createElement('div');
        timeDiv.className = 'small ' + (isMine ? 'text-white-50' : 'text-muted');
        timeDiv.textContent = new Date(msg.sent_at.replace(' ', 'T')).toLocaleString('en-US', {
            day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
        });
        bubble.appendChild(timeDiv);

        wrapper.appendChild(bubble);
        thread.appendChild(wrapper);
        scrollToBottom();
    }

    // Send a message via AJAX instead of a full page reload
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;

        const body = new URLSearchParams({
            product_id: productId, with: withId, message_text: text, csrf_token: csrfToken
        });

        fetch('../api/send_message.php', { method: 'POST', body })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    appendMessage(data.message);
                    input.value = '';
                } else {
                    alert(data.error || 'Could not send message.');
                }
            })
            .catch(() => alert('Network error — message not sent.'));
    });

    // Poll for new messages every few seconds
    setInterval(function () {
        const afterId = lastMessageId();
        fetch(`../api/get_messages.php?product_id=${productId}&with=${withId}&after_id=${afterId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.messages.length > 0) {
                    data.messages.forEach(appendMessage);
                }
            })
            .catch(() => {}); // silent — a missed poll isn't worth alarming the user over
    }, 4000);

    scrollToBottom();
})();
</script>

<?php require_once '../includes/footer.php'; ?>
