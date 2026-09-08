<?php
/**
 * app/Database.php
 *
 * Singleton PDO connection. This is the foundation of the MVC refactor —
 * every Model uses this instead of each file opening its own connection.
 *
 * config/db.php (used throughout the existing pages) now just delegates
 * here, so nothing in the rest of the app breaks while we migrate
 * page-by-page to using Models.
 */

class Database {
    private static $pdo = null;

    public static function connect() {
        if (self::$pdo === null) {
            $host = "localhost";
            $dbname = "campus_trade";
            $username = "root";
            $password = ""; // XAMPP default is empty

            try {
                self::$pdo = new PDO(
                    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}
?>
