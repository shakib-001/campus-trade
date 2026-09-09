<?php
require_once '../includes/session_init.php';
require_once '../config/db.php';
require_once '../app/models/Report.php';
require_once '../app/models/Product.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($id && in_array($action, ['remove', 'dismiss'])) {
    $report = Report::findById($id);

    if ($report) {
        if ($action === 'remove') {
            Product::setStatus($report['product_id'], 'removed');
            $_SESSION['success'] = "Listing removed from the site.";
        } else {
            $_SESSION['success'] = "Report dismissed.";
        }
        Report::markReviewed($id);
    }
}

header("Location: manage_reports.php");
exit;
?>
