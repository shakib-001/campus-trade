<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../config/csrf.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$product_id = $_GET['product_id'] ?? $_POST['product_id'] ?? null;
if (!$product_id) {
    header("Location: browse_items.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product || $product['status'] !== 'available') {
    header("Location: browse_items.php");
    exit;
}

// Can't request your own item
if ($product['seller_id'] == $_SESSION['user_id']) {
    header("Location: item_details.php?id=" . $product_id);
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $meetup_location = trim($_POST['meetup_location']);
    $meetup_time = $_POST['meetup_time'];

    if (empty($meetup_location) || empty($meetup_time)) {
        $errors[] = "Please provide both a meetup location and time.";
    }

    // Prevent duplicate pending requests from the same buyer for the same item
    if (empty($errors)) {
        $check = $pdo->prepare(
            "SELECT request_id FROM requests WHERE product_id = ? AND buyer_id = ? AND status = 'pending'"
        );
        $check->execute([$product_id, $_SESSION['user_id']]);
        if ($check->fetch()) {
            $errors[] = "You already have a pending request for this item.";
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO requests (product_id, buyer_id, meetup_location, meetup_time, status)
             VALUES (?, ?, ?, ?, 'pending')"
        );
        $stmt->execute([$product_id, $_SESSION['user_id'], $meetup_location, $meetup_time]);

        $_SESSION['success'] = "Request sent! The seller will review it.";
        header("Location: my_requests.php");
        exit;
    }
}

$pageTitle = "Send Request";
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h3 class="card-title mb-3">Send Request</h3>
                <p class="text-muted">For: <strong><?= htmlspecialchars($product['title']) ?></strong> — ৳<?= number_format($product['price'], 2) ?></p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="send_request.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">

                    <div class="mb-3">
                        <label class="form-label">Meetup Location</label>
                        <input type="text" name="meetup_location" class="form-control" placeholder="e.g. Library main gate" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Meetup Date & Time</label>
                        <input type="datetime-local" name="meetup_time" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Send Request</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
