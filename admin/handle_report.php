<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($id && in_array($action, ['remove', 'dismiss'])) {
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE report_id = ?");
    $stmt->execute([$id]);
    $report = $stmt->fetch();

    if ($report) {
        if ($action === 'remove') {
            $pdo->prepare("UPDATE products SET status = 'removed' WHERE product_id = ?")->execute([$report['product_id']]);
            $_SESSION['success'] = "Listing removed from the site.";
        } else {
            $_SESSION['success'] = "Report dismissed.";
        }
        $pdo->prepare("UPDATE reports SET status = 'reviewed' WHERE report_id = ?")->execute([$id]);
    }
}

header("Location: manage_reports.php");
exit;
?>
