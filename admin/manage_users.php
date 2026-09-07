<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

$pageTitle = "Manage Users";
require_once '../includes/header.php';
?>

<h3 class="mb-3">Manage Users</h3>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<table class="table table-bordered align-middle">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Joined</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge bg-<?= $u['role'] === 'admin' ? 'dark' : 'secondary' ?>"><?= ucfirst($u['role']) ?></span></td>
                <td><span class="badge bg-<?= $u['status'] === 'active' ? 'success' : 'danger' ?>"><?= ucfirst($u['status']) ?></span></td>
                <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                        <?php if ($u['status'] === 'active'): ?>
                            <a href="toggle_user_status.php?id=<?= $u['user_id'] ?>&action=block" class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Block this user?')">Block</a>
                        <?php else: ?>
                            <a href="toggle_user_status.php?id=<?= $u['user_id'] ?>&action=unblock" class="btn btn-sm btn-outline-success">Unblock</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted">(you)</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once '../includes/footer.php'; ?>
