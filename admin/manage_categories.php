<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../config/csrf.php';
require_once '../app/models/Category.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$errors = [];

// Add new category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    csrf_verify();
    $name = trim($_POST['category_name']);
    if (empty($name)) {
        $errors[] = "Category name can't be empty.";
    } else {
        Category::create($name);
        $_SESSION['success'] = "Category added.";
        header("Location: manage_categories.php");
        exit;
    }
}

$categories = Category::allWithItemCounts();

$pageTitle = "Manage Categories";
require_once '../includes/header.php';
?>

<h3 class="mb-3">Manage Categories</h3>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="POST" action="manage_categories.php" class="row g-2 mb-4">
    <?= csrf_field() ?>
    <div class="col-md-6">
        <input type="text" name="category_name" class="form-control" maxlength="100" placeholder="New category name" required>
    </div>
    <div class="col-md-3">
        <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
    </div>
</form>

<table class="table table-bordered align-middle">
    <thead>
        <tr>
            <th>Category</th>
            <th>Items in it</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?= htmlspecialchars($cat['category_name']) ?></td>
                <td><?= $cat['item_count'] ?></td>
                <td>
                    <?php if ($cat['item_count'] == 0): ?>
                        <a href="delete_category.php?id=<?= $cat['category_id'] ?>" class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Delete this category?')">Delete</a>
                    <?php else: ?>
                        <span class="text-muted small">In use — can't delete</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once '../includes/footer.php'; ?>
