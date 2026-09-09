<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../config/csrf.php'; ?>
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — Campus Trade' : 'Campus Trade — Student Marketplace' ?></title>
    <meta name="description" content="Buy, sell, and rent secondhand items with fellow students on Campus Trade.">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📌</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/campus-trade/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid px-3">
        <a class="navbar-brand" href="/campus-trade/index.php">📌 Campus Trade</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-light" href="#" role="button" data-bs-toggle="dropdown">Marketplace</a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/campus-trade/student/browse_items.php">Browse Items</a></li>
                            <li><a class="dropdown-item" href="/campus-trade/student/post_item.php">Post an Item</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-light" href="#" role="button" data-bs-toggle="dropdown">My Activity</a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/campus-trade/student/my_listings.php">My Listings</a></li>
                            <li><a class="dropdown-item" href="/campus-trade/student/my_requests.php">My Requests</a></li>
                            <li><a class="dropdown-item" href="/campus-trade/student/incoming_requests.php">Incoming Requests</a></li>
                            <li><a class="dropdown-item" href="/campus-trade/student/wishlist.php">Wishlist</a></li>
                            <li><a class="dropdown-item" href="/campus-trade/student/messages.php">Messages</a></li>
                        </ul>
                    </li>
                <?php elseif (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link text-light" href="/campus-trade/admin/manage_users.php">Manage Users</a></li>
                    <li class="nav-item"><a class="nav-link text-light" href="/campus-trade/admin/manage_categories.php">Manage Categories</a></li>
                    <li class="nav-item"><a class="nav-link text-light" href="/campus-trade/admin/manage_reports.php">Reports</a></li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center mt-3 mt-lg-0 ms-lg-3">
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student'): ?>
                    <?php
                        require_once __DIR__ . '/../app/models/User.php';
                        $navUser = User::findById($_SESSION['user_id']);
                        $navAvatarPic = $navUser['profile_pic'] ?? null;
                    ?>
                    <div class="dropdown">
                        <a class="d-flex align-items-center text-light dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <?php if ($navAvatarPic): ?>
                                <img src="/campus-trade/assets/uploads/<?= htmlspecialchars($navAvatarPic) ?>" class="avatar me-2">
                            <?php else: ?>
                                <span class="avatar-placeholder me-2" style="width:32px;height:32px;font-size:1rem;"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></span>
                            <?php endif; ?>
                            <?= htmlspecialchars($_SESSION['name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/campus-trade/student/profile.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="/campus-trade/student/change_password.php">Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/campus-trade/auth/logout.php">Logout</a></li>
                        </ul>
                    </div>
                <?php elseif (isset($_SESSION['user_id'])): ?>
                    <span class="text-light me-3">Hi, <?= htmlspecialchars($_SESSION['name']) ?></span>
                    <a href="/campus-trade/auth/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                <?php else: ?>
                    <a href="/campus-trade/auth/login.php" class="btn btn-outline-light btn-sm me-2">Login</a>
                    <a href="/campus-trade/auth/register.php" class="btn btn-accent btn-sm">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<div class="container mt-4">
