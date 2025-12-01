<?php
/**
 * Database Configuration
 * Update these values with your database credentials
 */

// Database Configuration - LOCAL MYSQL
define('DB_HOST', 'localhost');
define('DB_USER', 'gmpc_user');
define('DB_PASS', 'gmpc_local_pass');
define('DB_NAME', 'gmpc_cms');

// Site Configuration - LOCAL
define('SITE_URL', 'http://localhost/gmpc');
define('ADMIN_URL', 'http://localhost/gmpc/admin');
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'http://localhost/gmpc/uploads/');

// Security
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('PASSWORD_MIN_LENGTH', 8);

// Create database connection
function getDBConnection() {
    static $conn = null;

    if ($conn === null) {
        try {
            $conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please contact the administrator.");
        }
    }

    return $conn;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Africa/Accra');
?>
