<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../app/models/User.php';
require_once '../app/models/Review.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_GET['user_id'] ?? null;
if (!$user_id) {
    header("Location: browse_items.php");
    exit;
}

$user = User::findById($user_id);

if (!$user) {
    header("Location: browse_items.php");
    exit;
}

$reviews = Review::findForUser($user_id);
$rating = User::getRating($user_id);

$pageTitle = $user['name'];
require_once '../includes/header.php';
?>

<div class="card shadow-sm mt-3">
    <div class="card-body d-flex align-items-center gap-3">
        <?php if ($user['profile_pic']): ?>
            <img src="../assets/uploads/<?= htmlspecialchars($user['profile_pic']) ?>" class="avatar-lg" alt="<?= htmlspecialchars($user['name']) ?>'s profile picture">
        <?php else: ?>
            <div class="avatar-placeholder"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
        <?php endif; ?>
        <div>
            <h3 class="mb-1"><?= htmlspecialchars($user['name']) ?></h3>
            <?php if ($rating['count'] > 0): ?>
                <div><?= Review::renderStars($rating['avg']) ?> <span class="text-muted"><?= $rating['avg'] ?> / 5 (<?= $rating['count'] ?> review<?= $rating['count'] > 1 ? 's' : '' ?>)</span></div>
            <?php else: ?>
                <p class="text-muted mb-0">No reviews yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<h5 class="mt-4">Reviews</h5>
<?php if (empty($reviews)): ?>
    <div class="empty-state">
        <span class="empty-icon">⭐</span>
        This user hasn't received any reviews yet.
    </div>
<?php else: ?>
    <?php foreach ($reviews as $r): ?>
        <div class="card mb-2">
            <div class="card-body">
                <strong><?= htmlspecialchars($r['reviewer_name']) ?></strong>
                — <?= Review::renderStars($r['rating']) ?>
                <p class="mb-0 mt-1"><?= htmlspecialchars($r['comment']) ?></p>
                <small class="text-muted"><?= date('d M Y', strtotime($r['created_at'])) ?></small>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
