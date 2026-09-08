<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();

// Build filter query
$category_id = $_GET['category_id'] ?? '';
$keyword = trim($_GET['keyword'] ?? '');

$sql = "SELECT products.*, categories.category_name, users.name AS seller_name
        FROM products
        JOIN categories ON products.category_id = categories.category_id
        JOIN users ON products.seller_id = users.user_id
        WHERE products.status = 'available'";
$params = [];

if (!empty($category_id)) {
    $sql .= " AND products.category_id = ?";
    $params[] = $category_id;
}
if (!empty($keyword)) {
    $sql .= " AND products.title LIKE ?";
    $params[] = "%$keyword%";
}
$sql .= " ORDER BY products.posted_at DESC";

// Pagination
$perPage = 9;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Count total matching items first (same filters, no LIMIT)
$countSql = "SELECT COUNT(*) FROM products WHERE products.status = 'available'";
$countParams = [];
if (!empty($category_id)) {
    $countSql .= " AND products.category_id = ?";
    $countParams[] = $category_id;
}
if (!empty($keyword)) {
    $countSql .= " AND products.title LIKE ?";
    $countParams[] = "%$keyword%";
}
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);
$totalItems = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalItems / $perPage));

$sql .= " LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get this user's wishlisted product IDs so we can show filled/empty heart
$wishStmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
$wishStmt->execute([$_SESSION['user_id']]);
$wishlistedIds = $wishStmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = "Browse Items";
require_once '../includes/header.php';
?>

<h3 class="mb-3">Browse Items</h3>

<form method="GET" action="browse_items.php" class="row g-2 mb-4">
    <div class="col-md-4">
        <select name="category_id" class="form-select">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['category_id'] ?>" <?= ($category_id == $cat['category_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['category_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-5">
        <input type="text" name="keyword" class="form-control" placeholder="Search by title..." value="<?= htmlspecialchars($keyword) ?>">
    </div>
    <div class="col-md-3">
        <button type="submit" class="btn btn-primary w-100">Search</button>
    </div>
</form>

<div class="row">
    <?php if (empty($products)): ?>
        <div class="empty-state w-100">
            <span class="empty-icon">🔍</span>
            No items found. Try a different search or check back later!
        </div>
    <?php endif; ?>

    <?php foreach ($products as $product): ?>
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
                        <span class="badge bg-info text-dark"><?= ucfirst($product['item_condition']) ?></span>
                        <span class="badge bg-warning text-dark"><?= $product['listing_type'] === 'rent' ? 'For Rent' : 'For Sale' ?></span>
                    </p>
                    <p class="fw-bold">৳<?= number_format($product['price'], 2) ?></p>
                    <p class="text-muted small">
                        Seller: <a href="view_profile.php?user_id=<?= $product['seller_id'] ?>"><?= htmlspecialchars($product['seller_name']) ?></a>
                        <?php $sr = get_user_rating($pdo, $product['seller_id']); ?>
                        <?php if ($sr['count'] > 0): ?>
                            <?= render_stars($sr['avg']) ?> <span>(<?= $sr['count'] ?>)</span>
                        <?php endif; ?>
                    </p>
                    <a href="item_details.php?id=<?= $product['product_id'] ?>" class="btn btn-outline-primary btn-sm w-100 mb-1">View Details</a>
                    <?php $inWishlist = in_array($product['product_id'], $wishlistedIds); ?>
                    <button type="button" class="btn btn-sm w-100 wishlist-btn <?= $inWishlist ? 'btn-danger' : 'btn-outline-danger' ?>"
                            data-product-id="<?= $product['product_id'] ?>" data-in-wishlist="<?= $inWishlist ? '1' : '0' ?>">
                        <?= $inWishlist ? '♥ In Wishlist' : '♡ Add to Wishlist' ?>
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($totalPages > 1): ?>
    <nav aria-label="Browse items pagination">
        <ul class="pagination justify-content-center">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                    <a class="page-link" href="browse_items.php?page=<?= $p ?>&category_id=<?= urlencode($category_id) ?>&keyword=<?= urlencode($keyword) ?>">
                        <?= $p ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<script>
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.wishlist-btn');
    if (!btn) return;

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

<?php require_once '../includes/footer.php'; ?>
