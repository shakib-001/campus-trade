<?php
require_once 'includes/session_init.php';
require_once __DIR__ . '/config/db.php';
$pageTitle = "Page Not Found";
require_once __DIR__ . '/includes/header.php';
?>

<div class="text-center py-5">
    <div style="font-size: 4rem;">🧭</div>
    <h1 class="mt-2">404 — Page Not Found</h1>
    <p class="text-muted">The page you're looking for doesn't exist or may have been moved.</p>
    <a href="/campus-trade/index.php" class="btn btn-accent mt-2">Back to Home</a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
