<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../app/models/Wishlist.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$items = Wishlist::findForUser($_SESSION['user_id']);

$pageTitle = "My Wishlist";
require_once '../includes/header.php';
?>

<h3 class="mb-3">My Wishlist</h3>

<div class="row">
    <?php if (empty($items)): ?>
        <div class="empty-state w-100">
            <span class="empty-icon">♡</span>
            Your wishlist is empty. <a href="browse_items.php">Browse items</a> and tap the heart to save them here.
        </div>
    <?php endif; ?>

    <?php foreach ($items as $product): ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <?php if ($product['image']): ?>
                    <img src="../assets/uploads/<?= htmlspecialchars($product['image']) ?>" class="card-img-top" style="height:180px; object-fit:cover;">
                <?php else: ?>
                    <div class="bg-light d-flex align-items-center justify-content-center" style="height:180px;">
                        <span class="text-muted">No Image</span>
                    </div>
                <?php endif; ?>
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($product['title']) ?></h5>
                    <p class="mb-1">
                        <span class="badge bg-secondary"><?= htmlspecialchars($product['category_name']) ?></span>
                        <span class="badge bg-<?= $product['status'] === 'available' ? 'success' : 'danger' ?>"><?= ucfirst($product['status']) ?></span>
                    </p>
                    <p class="fw-bold">৳<?= number_format($product['price'], 2) ?></p>
                    <p class="text-muted small">Seller: <?= htmlspecialchars($product['seller_name']) ?></p>
                    <a href="item_details.php?id=<?= $product['product_id'] ?>" class="btn btn-outline-primary btn-sm w-100 mb-1">View Details</a>
                    <a href="toggle_wishlist.php?product_id=<?= $product['product_id'] ?>&redirect=wishlist.php" class="btn btn-outline-danger btn-sm w-100">Remove from Wishlist</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
