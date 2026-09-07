<?php
// config/csrf.php — lightweight CSRF protection helpers.
// Include this after session_start(). Call csrf_field() inside every <form method="POST">,
// and call csrf_verify() at the top of every block that handles a POST submission.

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify() {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Your form session expired or is invalid. Please go back and try submitting again.");
    }
}
?>
