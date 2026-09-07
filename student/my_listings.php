<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$stmt = $pdo->prepare(
    "SELECT products.*, categories.category_name
     FROM products
     JOIN categories ON products.category_id = categories.category_id
     WHERE products.seller_id = ?
     ORDER BY products.posted_at DESC"
);
$stmt->execute([$_SESSION['user_id']]);
$products = $stmt->fetchAll();

$pageTitle = "My Listings";
require_once '../includes/header.php';
?>

<h3 class="mb-3">My Listings</h3>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (empty($products)): ?>
    <div class="empty-state">
        <span class="empty-icon">📦</span>
        You haven't posted any items yet. <a href="post_item.php">Post your first item</a>.
    </div>
<?php else: ?>
    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Price</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><a href="item_details.php?id=<?= $product['product_id'] ?>"><?= htmlspecialchars($product['title']) ?></a></td>
                    <td><?= htmlspecialchars($product['category_name']) ?></td>
                    <td>৳<?= number_format($product['price'], 2) ?></td>
                    <td>
                        <span class="badge bg-<?= $product['status'] === 'available' ? 'success' : ($product['status'] === 'sold' ? 'secondary' : 'danger') ?>">
                            <?= ucfirst($product['status']) ?>
                        </span>
                    </td>
                    <td>
                        <a href="edit_item.php?id=<?= $product['product_id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                        <?php if ($product['status'] === 'available'): ?>
                            <a href="toggle_status.php?id=<?= $product['product_id'] ?>&action=sold" class="btn btn-sm btn-outline-success"
                               onclick="return confirm('Mark this item as sold?')">Mark Sold</a>
                        <?php endif; ?>
                        <a href="delete_item.php?id=<?= $product['product_id'] ?>" class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Delete this item permanently?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
