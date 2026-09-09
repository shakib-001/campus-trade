<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../config/csrf.php';
require_once '../app/models/Category.php';
require_once '../app/models/Product.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$errors = [];
$categories = Category::all();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $title = trim($_POST['title']);
    $category_id = $_POST['category_id'];
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $item_condition = $_POST['item_condition'];
    $listing_type = $_POST['listing_type'];
    $imageName = null;

    // Basic validation
    if (empty($title) || empty($category_id) || empty($price)) {
        $errors[] = "Title, category, and price are required.";
    }
    if (strlen($title) > 150) {
        $errors[] = "Title must be under 150 characters.";
    }
    if (!is_numeric($price) || $price < 0) {
        $errors[] = "Please enter a valid price.";
    }

    // Handle image upload (optional)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $fileType = $_FILES['image']['type'];
        $fileSize = $_FILES['image']['size'];

        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "Only JPG, PNG, or WEBP images are allowed.";
        } elseif ($fileSize > 5 * 1024 * 1024) { // 5MB limit
            $errors[] = "Image must be under 5MB.";
        } else {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imageName = uniqid('item_', true) . '.' . $ext;
            $uploadPath = '../assets/uploads/' . $imageName;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $errors[] = "Failed to upload image. Please try again.";
                $imageName = null;
            }
        }
    }

    if (empty($errors)) {
        Product::create(
            $_SESSION['user_id'], $category_id, $title, $description,
            $price, $item_condition, $listing_type, $imageName
        );

        $_SESSION['success'] = "Item posted successfully!";
        header("Location: my_listings.php");
        exit;
    }
}

$pageTitle = "Post an Item";
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h3 class="card-title mb-3">Post an Item</h3>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="post_item.php" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" maxlength="150" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (৳)</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Condition</label>
                            <select name="item_condition" class="form-select" required>
                                <option value="used">Used</option>
                                <option value="new">New</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Listing Type</label>
                        <select name="listing_type" class="form-select" required>
                            <option value="sell">For Sale</option>
                            <option value="rent">For Rent</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Item Image (optional)</label>
                        <input type="file" name="image" id="imageInput" class="form-control" accept="image/*">
                        <img id="imagePreview" class="mt-2 rounded d-none" style="max-height:180px;">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Post Item</button>
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
    } else {
        preview.classList.add('d-none');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
