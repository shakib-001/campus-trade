<?php
require_once 'includes/session_init.php';
require_once 'config/db.php';
require_once 'includes/header.php';
?>

<section class="hero">
    <div class="row align-items-center">
        <div class="col-lg-6">
            <h1>Everything you need, already on campus.</h1>
            <p class="lead mt-3">
                Buy, sell, and rent secondhand books, gadgets, cycles, and more —
                straight from fellow students. No shipping, no middlemen, just a meetup and a handshake.
            </p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="mt-4">
                    <a href="auth/register.php" class="btn btn-accent btn-lg me-2">Get Started</a>
                    <a href="auth/login.php" class="btn btn-outline-primary btn-lg">Login</a>
                </div>
            <?php else: ?>
                <?php $dash = $_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'; ?>
                <a href="<?= $dash ?>" class="btn btn-accent btn-lg mt-4">Go to Dashboard</a>
            <?php endif; ?>
        </div>
        <div class="col-lg-6">
            <div class="hero-board d-none d-lg-block">
                <div class="hero-note note-1">
                    <h6>Calculus Textbook</h6>
                    <div class="price">৳300</div>
                </div>
                <div class="hero-note note-2">
                    <h6>Mountain Bike — Rent</h6>
                    <div class="price">৳50/day</div>
                </div>
                <div class="hero-note note-3">
                    <h6>Scientific Calculator</h6>
                    <div class="price">৳450</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-4">
    <h3 class="mb-4">How Campus Trade works</h3>
    <div class="row g-4">
        <div class="col-md-4 d-flex gap-3">
            <div class="step-num">1</div>
            <div>
                <h5>Post or browse</h5>
                <p class="text-muted mb-0">List something you don't need, or search for what you're looking for by category.</p>
            </div>
        </div>
        <div class="col-md-4 d-flex gap-3">
            <div class="step-num">2</div>
            <div>
                <h5>Chat & agree</h5>
                <p class="text-muted mb-0">Message the seller, send a request, and agree on a meetup time and place.</p>
            </div>
        </div>
        <div class="col-md-4 d-flex gap-3">
            <div class="step-num">3</div>
            <div>
                <h5>Meet up & rate</h5>
                <p class="text-muted mb-0">Exchange on campus, then leave a review to help other students trust the community.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
