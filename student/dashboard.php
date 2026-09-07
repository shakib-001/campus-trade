<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
// Access control: only logged-in students can see this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}
$pageTitle = "Dashboard";
require_once '../includes/header.php';
?>

<h2>Student Dashboard</h2>
<p>Welcome, <?= htmlspecialchars($_SESSION['name']) ?>! 🎉</p>
<div class="row mt-4">
    <div class="col-md-3 mb-3"><a href="browse_items.php" class="btn btn-outline-primary w-100 py-3">Browse Items</a></div>
    <div class="col-md-3 mb-3"><a href="post_item.php" class="btn btn-outline-primary w-100 py-3">Post an Item</a></div>
    <div class="col-md-3 mb-3"><a href="my_listings.php" class="btn btn-outline-primary w-100 py-3">My Listings</a></div>
    <div class="col-md-3 mb-3"><a href="my_requests.php" class="btn btn-outline-primary w-100 py-3">My Requests</a></div>
    <div class="col-md-3 mb-3"><a href="incoming_requests.php" class="btn btn-outline-primary w-100 py-3">Incoming Requests</a></div>
</div>

<?php require_once '../includes/footer.php'; ?>
