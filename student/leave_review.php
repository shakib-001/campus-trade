<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../config/csrf.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$request_id = $_GET['request_id'] ?? $_POST['request_id'] ?? null;
if (!$request_id) {
    header("Location: my_requests.php");
    exit;
}

// Fetch the request and confirm it's completed and the current user was part of it
$stmt = $pdo->prepare(
    "SELECT requests.*, products.title AS product_title, products.seller_id
     FROM requests JOIN products ON requests.product_id = products.product_id
     WHERE requests.request_id = ? AND requests.status = 'completed'"
);
$stmt->execute([$request_id]);
$req = $stmt->fetch();

if (!$req || ($req['buyer_id'] != $_SESSION['user_id'] && $req['seller_id'] != $_SESSION['user_id'])) {
    header("Location: my_requests.php");
    exit;
}

// Figure out who we're reviewing: the other party in this transaction
$isBuyer = ($req['buyer_id'] == $_SESSION['user_id']);
$reviewedUserId = $isBuyer ? $req['seller_id'] : $req['buyer_id'];

// Prevent duplicate review for this request
$check = $pdo->prepare("SELECT review_id FROM reviews WHERE reviewer_id = ? AND request_id = ?");
$check->execute([$_SESSION['user_id'], $request_id]);
if ($check->fetch()) {
    $_SESSION['success'] = "You've already reviewed this transaction.";
    header("Location: " . ($isBuyer ? 'my_requests.php' : 'incoming_requests.php'));
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $rating = $_POST['rating'] ?? null;
    $comment = trim($_POST['comment'] ?? '');

    if (!$rating || $rating < 1 || $rating > 5) {
        $errors[] = "Please select a rating from 1 to 5.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO reviews (reviewer_id, reviewed_user_id, request_id, rating, comment) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$_SESSION['user_id'], $reviewedUserId, $request_id, $rating, $comment]);

        $_SESSION['success'] = "Review submitted. Thanks!";
        header("Location: " . ($isBuyer ? 'my_requests.php' : 'incoming_requests.php'));
        exit;
    }
}

$pageTitle = "Leave a Review";
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h3 class="card-title mb-3">Leave a Review</h3>
                <p class="text-muted">For your <?= $isBuyer ? 'seller' : 'buyer' ?> on: <strong><?= htmlspecialchars($req['product_title']) ?></strong></p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="leave_review.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="request_id" value="<?= $request_id ?>">

                    <div class="mb-3">
                        <label class="form-label d-block">Rating</label>
                        <div class="star-rating">
                            <input type="radio" id="star5" name="rating" value="5"><label for="star5" title="5 stars">★</label>
                            <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 stars">★</label>
                            <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 stars">★</label>
                            <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 stars">★</label>
                            <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 star">★</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Comment (optional)</label>
                        <textarea name="comment" class="form-control" rows="3"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Submit Review</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
