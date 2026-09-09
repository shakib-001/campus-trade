<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../config/csrf.php';
require_once '../includes/functions.php';
require_once '../app/models/User.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$user = User::findById($_SESSION['user_id']);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name = trim($_POST['name']);
    $student_id = trim($_POST['student_id']);
    $phone = trim($_POST['phone']);
    $profilePic = $user['profile_pic'];

    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (strlen($name) > 100 || strlen($student_id) > 20) {
        $errors[] = "One of the fields is too long.";
    }
    if (!empty($phone) && !is_valid_bd_phone($phone)) {
        $errors[] = "Phone number must be exactly 11 digits and start with 01 (e.g. 01712345678).";
    }

    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($_FILES['profile_pic']['type'], $allowedTypes)) {
            $errors[] = "Profile picture must be JPG, PNG, or WEBP.";
        } elseif ($_FILES['profile_pic']['size'] > 3 * 1024 * 1024) {
            $errors[] = "Profile picture must be under 3MB.";
        } else {
            if ($user['profile_pic'] && file_exists('../assets/uploads/' . $user['profile_pic'])) {
                unlink('../assets/uploads/' . $user['profile_pic']);
            }
            $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $profilePic = uniqid('avatar_', true) . '.' . $ext;
            move_uploaded_file($_FILES['profile_pic']['tmp_name'], '../assets/uploads/' . $profilePic);
        }
    }

    if (empty($errors)) {
        User::updateProfile($_SESSION['user_id'], $name, $student_id, $phone, $profilePic);

        $_SESSION['name'] = $name; // keep navbar greeting in sync
        $_SESSION['success'] = "Profile updated successfully.";
        header("Location: profile.php");
        exit;
    }
}

$pageTitle = "Edit Profile";
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm mt-2">
            <div class="card-body">
                <h3 class="card-title mb-3">Edit Profile</h3>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="edit_profile.php" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="mb-3 text-center">
                        <?php if ($user['profile_pic']): ?>
                            <img id="avatarPreview" src="../assets/uploads/<?= htmlspecialchars($user['profile_pic']) ?>" class="avatar-lg">
                        <?php else: ?>
                            <img id="avatarPreview" class="avatar-lg d-none">
                            <div id="avatarPlaceholder" class="avatar-placeholder mx-auto"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Profile Picture</label>
                        <input type="file" name="profile_pic" id="picInput" class="form-control" accept="image/*">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" maxlength="100" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Student ID</label>
                        <input type="text" name="student_id" class="form-control" maxlength="20" value="<?= htmlspecialchars($user['student_id']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" maxlength="11" inputmode="numeric"
                               pattern="01[0-9]{9}" title="11-digit number starting with 01"
                               value="<?= htmlspecialchars($user['phone']) ?>" placeholder="01XXXXXXXXX">
                        <div class="form-text">11 digits, starting with 01 (e.g. 01712345678).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                        <div class="form-text">Email can't be changed here.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Save Changes</button>
                    <a href="profile.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('picInput').addEventListener('change', function (e) {
    const preview = document.getElementById('avatarPreview');
    const placeholder = document.getElementById('avatarPlaceholder');
    const file = e.target.files[0];
    if (file) {
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('d-none');
        if (placeholder) placeholder.classList.add('d-none');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
