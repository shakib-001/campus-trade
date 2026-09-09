<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../config/csrf.php';
require_once '../app/models/Product.php';
require_once '../app/controllers/RequestController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$product_id = $_GET['product_id'] ?? $_POST['product_id'] ?? null;
if (!$product_id) {
    header("Location: browse_items.php");
    exit;
}

$product = Product::findById($product_id);

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
    $result = RequestController::send($product_id, $_SESSION['user_id'], $_POST['meetup_location'] ?? '', $_POST['meetup_time'] ?? '');

    if ($result['success']) {
        $_SESSION['success'] = $result['flash'];
        header("Location: " . $result['redirect']);
        exit;
    }
    $errors = $result['errors'];
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
