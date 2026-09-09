<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../app/models/Report.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$reports = Report::all();

$pageTitle = "Reported Items";
require_once '../includes/header.php';
?>

<h3 class="mb-3">Reported Items</h3>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (empty($reports)): ?>
    <p>No reports so far. 👍</p>
<?php else: ?>
    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th>Item</th>
                <th>Seller</th>
                <th>Reported By</th>
                <th>Reason</th>
                <th>Report Status</th>
                <th>Item Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reports as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['product_title']) ?></td>
                    <td><?= htmlspecialchars($r['seller_name']) ?></td>
                    <td><?= htmlspecialchars($r['reporter_name']) ?></td>
                    <td><?= htmlspecialchars($r['reason']) ?></td>
                    <td><span class="badge bg-<?= $r['status'] === 'pending' ? 'warning text-dark' : 'secondary' ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td><span class="badge bg-<?= $r['product_status'] === 'removed' ? 'danger' : 'success' ?>"><?= ucfirst($r['product_status']) ?></span></td>
                    <td>
                        <?php if ($r['status'] === 'pending'): ?>
                            <?php if ($r['product_status'] !== 'removed'): ?>
                                <a href="handle_report.php?id=<?= $r['report_id'] ?>&action=remove" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Remove this listing from the site?')">Remove Listing</a>
                            <?php endif; ?>
                            <a href="handle_report.php?id=<?= $r['report_id'] ?>&action=dismiss" class="btn btn-sm btn-outline-secondary">Dismiss</a>
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
