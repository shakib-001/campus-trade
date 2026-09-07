<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$stmt = $pdo->prepare(
    "SELECT requests.*, products.title, products.price, users.name AS seller_name
     FROM requests
     JOIN products ON requests.product_id = products.product_id
     JOIN users ON products.seller_id = users.user_id
     WHERE requests.buyer_id = ?
     ORDER BY requests.requested_at DESC"
);
$stmt->execute([$_SESSION['user_id']]);
$requests = $stmt->fetchAll();

$pageTitle = "My Requests";
require_once '../includes/header.php';
?>

<h3 class="mb-3">My Requests</h3>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (empty($requests)): ?>
    <div class="empty-state">
        <span class="empty-icon">📨</span>
        You haven't sent any requests yet. <a href="browse_items.php">Browse items</a> to get started.
    </div>
<?php else: ?>
    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th>Item</th>
                <th>Seller</th>
                <th>Meetup</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $req): ?>
                <tr>
                    <td><?= htmlspecialchars($req['title']) ?> (৳<?= number_format($req['price'], 2) ?>)</td>
                    <td><?= htmlspecialchars($req['seller_name']) ?></td>
                    <td><?= htmlspecialchars($req['meetup_location']) ?><br><small><?= date('d M Y, h:i A', strtotime($req['meetup_time'])) ?></small></td>
                    <td>
                        <span class="badge bg-<?= [
                            'pending' => 'warning text-dark',
                            'accepted' => 'success',
                            'rejected' => 'danger',
                            'completed' => 'primary'
                        ][$req['status']] ?>">
                            <?= ucfirst($req['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($req['status'] === 'pending'): ?>
                            <a href="cancel_request.php?id=<?= $req['request_id'] ?>" class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Cancel this request?')">Cancel</a>
                        <?php elseif ($req['status'] === 'completed'): ?>
                            <a href="leave_review.php?request_id=<?= $req['request_id'] ?>" class="btn btn-sm btn-outline-warning">Leave Review</a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
