<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../app/models/Product.php';
require_once '../app/models/Wishlist.php';
require_once '../app/models/User.php';
require_once '../app/models/Review.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: browse_items.php");
    exit;
}

$product = Product::findById($id);

if (!$product) {
    $pageTitle = "Item Not Found";
    require_once '../includes/header.php';
    echo "<p>Item not found.</p>";
    require_once '../includes/footer.php';
    exit;
}

$isOwner = ($product['seller_id'] == $_SESSION['user_id']);

$inWishlist = false;
if (!$isOwner) {
    $inWishlist = in_array($id, Wishlist::productIdsForUser($_SESSION['user_id']));
}

$pageTitle = $product['title'];
require_once '../includes/header.php';
?>

<div class="row mt-3">
    <div class="col-12">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
    </div>
</div>
<div class="row mt-3">
    <div class="col-md-6">
        <?php if ($product['image']): ?>
            <img src="../assets/uploads/<?= htmlspecialchars($product['image']) ?>" class="img-fluid rounded shadow-sm" alt="<?= htmlspecialchars($product['title']) ?>">
        <?php else: ?>
            <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height:300px;">
                <span class="text-muted">No Image</span>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-md-6">
        <h2><?= htmlspecialchars($product['title']) ?></h2>
        <p>
            <span class="badge bg-secondary"><?= htmlspecialchars($product['category_name']) ?></span>
            <span class="badge bg-info text-dark"><?= ucfirst($product['item_condition']) ?></span>
            <span class="badge bg-warning text-dark"><?= $product['listing_type'] === 'rent' ? 'For Rent' : 'For Sale' ?></span>
            <span class="badge bg-<?= $product['status'] === 'available' ? 'success' : 'danger' ?>"><?= ucfirst($product['status']) ?></span>
        </p>
        <h4 class="text-primary">৳<?= number_format($product['price'], 2) ?></h4>
        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
        <p class="text-muted">
            Posted by: <a href="view_profile.php?user_id=<?= $product['seller_id'] ?>"><?= htmlspecialchars($product['seller_name']) ?></a>
            <?php $sr = User::getRating($product['seller_id']); ?>
            <?php if ($sr['count'] > 0): ?>
                <?= Review::renderStars($sr['avg']) ?> <span class="small">(<?= $sr['avg'] ?>, <?= $sr['count'] ?> review<?= $sr['count'] > 1 ? 's' : '' ?>)</span>
            <?php else: ?>
                <span class="small">(no reviews yet)</span>
            <?php endif; ?>
        </p>

        <?php if ($isOwner): ?>
            <div class="mt-3">
                <a href="edit_item.php?id=<?= $product['product_id'] ?>" class="btn btn-outline-secondary">Edit</a>
                <a href="my_listings.php" class="btn btn-outline-dark">Back to My Listings</a>
            </div>
        <?php else: ?>
            <div class="mt-3">
                <?php if ($product['status'] === 'available'): ?>
                    <a href="send_request.php?product_id=<?= $product['product_id'] ?>" class="btn btn-primary">Send Request</a>
                <?php else: ?>
                    <button class="btn btn-secondary" disabled>Not Available (<?= ucfirst($product['status']) ?>)</button>
                <?php endif; ?>
                <button type="button" id="wishlistBtn" class="btn btn-sm <?= $inWishlist ? 'btn-danger' : 'btn-outline-danger' ?>"
                        data-product-id="<?= $product['product_id'] ?>" data-in-wishlist="<?= $inWishlist ? '1' : '0' ?>">
                    <?= $inWishlist ? '♥ In Wishlist' : '♡ Add to Wishlist' ?>
                </button>
                <a href="chat.php?product_id=<?= $product['product_id'] ?>&with=<?= $product['seller_id'] ?>" class="btn btn-outline-primary btn-sm ms-2">Message Seller</a>
                <a href="report_item.php?product_id=<?= $product['product_id'] ?>" class="btn btn-outline-danger btn-sm ms-2">Report</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!$isOwner): ?>
<script>
document.getElementById('wishlistBtn').addEventListener('click', function () {
    const btn = this;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const body = new URLSearchParams({ product_id: btn.dataset.productId, csrf_token: csrfToken });

    btn.disabled = true;
    fetch('../api/wishlist_toggle.php', { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert(data.error || 'Something went wrong.'); return; }
            const inWishlist = data.status === 'added';
            btn.dataset.inWishlist = inWishlist ? '1' : '0';
            btn.textContent = inWishlist ? '♥ In Wishlist' : '♡ Add to Wishlist';
            btn.classList.toggle('btn-danger', inWishlist);
            btn.classList.toggle('btn-outline-danger', !inWishlist);
        })
        .catch(() => alert('Network error — please try again.'))
        .finally(() => { btn.disabled = false; });
});
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
