<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$stmt = $pdo->prepare(
    "SELECT requests.*, products.title, products.price, products.status AS product_status, users.name AS buyer_name
     FROM requests
     JOIN products ON requests.product_id = products.product_id
     JOIN users ON requests.buyer_id = users.user_id
     WHERE products.seller_id = ?
     ORDER BY requests.requested_at DESC"
);
$stmt->execute([$_SESSION['user_id']]);
$requests = $stmt->fetchAll();

$pageTitle = "Incoming Requests";
require_once '../includes/header.php';
?>

<h3 class="mb-3">Incoming Requests</h3>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (empty($requests)): ?>
    <div class="empty-state">
        <span class="empty-icon">📭</span>
        No one has requested your items yet.
    </div>
<?php else: ?>
    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th>Item</th>
                <th>Buyer</th>
                <th>Meetup</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $req): ?>
                <tr>
                    <td><?= htmlspecialchars($req['title']) ?> (৳<?= number_format($req['price'], 2) ?>)</td>
                    <td><?= htmlspecialchars($req['buyer_name']) ?></td>
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
                            <a href="update_request.php?id=<?= $req['request_id'] ?>&action=accept" class="btn btn-sm btn-outline-success">Accept</a>
                            <a href="update_request.php?id=<?= $req['request_id'] ?>&action=reject" class="btn btn-sm btn-outline-danger">Reject</a>
                        <?php elseif ($req['status'] === 'accepted'): ?>
                            <a href="update_request.php?id=<?= $req['request_id'] ?>&action=complete" class="btn btn-sm btn-outline-primary"
                               onclick="return confirm('Mark this transaction as completed? The item will be marked as sold.')">Mark Completed</a>
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
