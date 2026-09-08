<?php
// config/db.php
// Delegates to app/Database.php (the new MVC-style singleton connection),
// but still exposes $pdo so all existing pages keep working unchanged.
require_once __DIR__ . '/../app/Database.php';
$pdo = Database::connect();
?>
