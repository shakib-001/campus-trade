<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../app/models/User.php';
require_once '../app/models/Product.php';
require_once '../app/models/Report.php';

// Access control: only logged-in admins can see this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$totalUsers = User::countStudents();
$totalItems = Product::countAll();
$pendingReports = Report::pendingCount();

$pageTitle = "Admin Dashboard";
require_once '../includes/header.php';
?>

<h2>Admin Dashboard</h2>
<p>Welcome, <?= htmlspecialchars($_SESSION['name']) ?>! 🛠️</p>

<div class="row mt-4">
    <div class="col-md-4 mb-3">
        <div class="card text-center shadow-sm"><div class="card-body">
            <h5>Total Students</h5><h2><?= $totalUsers ?></h2>
        </div></div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card text-center shadow-sm"><div class="card-body">
            <h5>Total Listings</h5><h2><?= $totalItems ?></h2>
        </div></div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card text-center shadow-sm"><div class="card-body">
            <h5>Pending Reports</h5><h2><?= $pendingReports ?></h2>
        </div></div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-4 mb-3"><a href="manage_users.php" class="btn btn-outline-primary w-100 py-3">Manage Users</a></div>
    <div class="col-md-4 mb-3"><a href="manage_categories.php" class="btn btn-outline-primary w-100 py-3">Manage Categories</a></div>
    <div class="col-md-4 mb-3"><a href="manage_reports.php" class="btn btn-outline-primary w-100 py-3">Review Reports</a></div>
</div>

<?php require_once '../includes/footer.php'; ?>
