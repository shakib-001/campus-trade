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

if (!$product) {
    header("Location: browse_items.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $reason = trim($_POST['reason']);
    if (empty($reason)) {
        $errors[] = "Please describe why you're reporting this item.";
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO reports (reporter_id, product_id, reason, status) VALUES (?, ?, ?, 'pending')"
        );
        $stmt->execute([$_SESSION['user_id'], $product_id, $reason]);

        $_SESSION['success'] = "Thanks — your report has been submitted for review.";
        header("Location: item_details.php?id=" . $product_id);
        exit;
    }
}

$pageTitle = "Report Item";
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h3 class="card-title mb-3">Report Item</h3>
                <p class="text-muted">Reporting: <strong><?= htmlspecialchars($product['title']) ?></strong></p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="report_item.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control" rows="4" placeholder="e.g. Fake item, misleading price, inappropriate content..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger w-100">Submit Report</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
