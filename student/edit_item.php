<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../config/csrf.php';
require_once '../app/models/Product.php';
require_once '../app/models/Category.php';
require_once '../app/controllers/ProductController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? $_POST['product_id'] ?? null;
if (!$id) {
    header("Location: my_listings.php");
    exit;
}

// Fetch item and verify ownership
$product = Product::findById($id);

if (!$product || $product['seller_id'] != $_SESSION['user_id']) {
    header("Location: my_listings.php");
    exit;
}

$categories = Category::all();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $result = ProductController::update($id, $product, $_POST, $_FILES['image'] ?? null, '../assets/uploads');

    if ($result['success']) {
        $_SESSION['success'] = $result['flash'];
        header("Location: " . $result['redirect']);
        exit;
    }
    $errors = $result['errors'];
}

$pageTitle = "Edit Item";
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h3 class="card-title mb-3">Edit Item</h3>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="edit_item.php?id=<?= $product['product_id'] ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" maxlength="150" value="<?= htmlspecialchars($product['title']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" <?= ($cat['category_id'] == $product['category_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (৳)</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="<?= $product['price'] ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Condition</label>
                            <select name="item_condition" class="form-select">
                                <option value="used" <?= $product['item_condition'] === 'used' ? 'selected' : '' ?>>Used</option>
                                <option value="new" <?= $product['item_condition'] === 'new' ? 'selected' : '' ?>>New</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Listing Type</label>
                        <select name="listing_type" class="form-select">
                            <option value="sell" <?= $product['listing_type'] === 'sell' ? 'selected' : '' ?>>For Sale</option>
                            <option value="rent" <?= $product['listing_type'] === 'rent' ? 'selected' : '' ?>>For Rent</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Replace Image (optional)</label>
                        <input type="file" name="image" id="imageInput" class="form-control" accept="image/*">
                        <?php if ($product['image']): ?>
                            <img id="imagePreview" class="mt-2 rounded" src="../assets/uploads/<?= htmlspecialchars($product['image']) ?>" style="max-height:180px;">
                        <?php else: ?>
                            <img id="imagePreview" class="mt-2 rounded d-none" style="max-height:180px;">
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Update Item</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('imageInput').addEventListener('change', function (e) {
    const preview = document.getElementById('imagePreview');
    const file = e.target.files[0];
    if (file) {
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('d-none');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
