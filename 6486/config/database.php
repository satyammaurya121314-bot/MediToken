<?php
// config/database.php
// Database connection configuration for MediToken

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'meditoken_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function get_db_connection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error safely in production, return user friendly message
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please ensure MySQL is running in XAMPP and the meditoken_db database exists.");
        }
    }

    return $pdo;
}
