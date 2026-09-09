<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../app/models/User.php';
require_once '../app/models/Product.php';
require_once '../app/models/Review.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$user = User::findById($_SESSION['user_id']);
$rating = User::getRating($_SESSION['user_id']);
$listingCount = count(Product::findBySeller($_SESSION['user_id']));

$pageTitle = "My Profile";
require_once '../includes/header.php';
?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-auto">
                <?php if ($user['profile_pic']): ?>
                    <img src="../assets/uploads/<?= htmlspecialchars($user['profile_pic']) ?>" class="avatar-lg">
                <?php else: ?>
                    <div class="avatar-placeholder"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                <?php endif; ?>
            </div>
            <div class="col">
                <h3 class="mb-1"><?= htmlspecialchars($user['name']) ?></h3>
                <?php if ($rating['count'] > 0): ?>
                    <div><?= Review::renderStars($rating['avg']) ?> <span class="text-muted"><?= $rating['avg'] ?> (<?= $rating['count'] ?> review<?= $rating['count'] > 1 ? 's' : '' ?>)</span></div>
                <?php else: ?>
                    <div class="text-muted">No reviews yet</div>
                <?php endif; ?>
                <div class="text-muted small mt-1">Member since <?= date('M Y', strtotime($user['created_at'])) ?> · <?= $listingCount ?> item<?= $listingCount != 1 ? 's' : '' ?> posted</div>
            </div>
        </div>

        <hr>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="text-muted small">Student ID</div>
                <div><?= htmlspecialchars($user['student_id'] ?: '—') ?></div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small">Email</div>
                <div><?= htmlspecialchars($user['email']) ?></div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small">Phone</div>
                <div><?= htmlspecialchars($user['phone'] ?: '—') ?></div>
            </div>
        </div>

        <div class="mt-4">
            <a href="edit_profile.php" class="btn btn-outline-primary">Edit Profile</a>
            <a href="change_password.php" class="btn btn-outline-secondary">Change Password</a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
