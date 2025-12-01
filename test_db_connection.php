<?php
/**
 * Database Connection Test Script
 * This script helps diagnose database connection issues
 */

// Display all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";
echo "<hr>";

// Load config
require_once 'config.php';

echo "<h2>1. Configuration Values</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><td><strong>DB_HOST</strong></td><td>" . DB_HOST . "</td></tr>";
echo "<tr><td><strong>DB_USER</strong></td><td>" . DB_USER . "</td></tr>";
echo "<tr><td><strong>DB_PASS</strong></td><td>" . str_repeat('*', strlen(DB_PASS)) . "</td></tr>";
echo "<tr><td><strong>DB_NAME</strong></td><td>" . DB_NAME . "</td></tr>";
echo "</table>";
echo "<hr>";

echo "<h2>2. PHP MySQL Extensions</h2>";
if (extension_loaded('pdo')) {
    echo "✓ <span style='color:green;'>PDO extension is loaded</span><br>";
} else {
    echo "✗ <span style='color:red;'>PDO extension is NOT loaded</span><br>";
}

if (extension_loaded('pdo_mysql')) {
    echo "✓ <span style='color:green;'>PDO MySQL driver is loaded</span><br>";
} else {
    echo "✗ <span style='color:red;'>PDO MySQL driver is NOT loaded</span><br>";
}
echo "<hr>";

echo "<h2>3. Database Connection Test</h2>";

try {
    // Try to connect
    $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $conn = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "✓ <span style='color:green;'><strong>Successfully connected to MySQL server!</strong></span><br>";
    echo "<hr>";

    echo "<h2>4. Database Existence Check</h2>";

    // Check if database exists
    $stmt = $conn->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    $dbExists = $stmt->rowCount() > 0;

    if ($dbExists) {
        echo "✓ <span style='color:green;'>Database '<strong>" . DB_NAME . "</strong>' exists!</span><br>";

        // Try to select the database
        $conn->exec("USE " . DB_NAME);
        echo "✓ <span style='color:green;'>Successfully selected database!</span><br>";
        echo "<hr>";

        echo "<h2>5. Tables Check</h2>";
        $stmt = $conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($tables) > 0) {
            echo "✓ <span style='color:green;'>Found " . count($tables) . " tables:</span><br>";
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>" . $table . "</li>";
            }
            echo "</ul>";
        } else {
            echo "⚠ <span style='color:orange;'>Database exists but has no tables yet.</span><br>";
            echo "<p><strong>Next step:</strong> Run <a href='setup_database.php'>setup_database.php</a> to create tables.</p>";
        }

    } else {
        echo "✗ <span style='color:red;'>Database '<strong>" . DB_NAME . "</strong>' does NOT exist!</span><br>";
        echo "<br><h3>How to fix:</h3>";
        echo "<p>Run this in MySQL:</p>";
        echo "<pre style='background:#f4f4f4;padding:10px;'>
mysql -u root -p

CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER '" . DB_USER . "'@'localhost' IDENTIFIED BY '" . DB_PASS . "';
GRANT ALL PRIVILEGES ON " . DB_NAME . ".* TO '" . DB_USER . "'@'localhost';
FLUSH PRIVILEGES;
EXIT;
</pre>";
    }

    echo "<hr>";
    echo "<h2>✓ Connection Test Complete</h2>";
    echo "<p style='color:green;'><strong>Your database connection is working!</strong></p>";

    if ($dbExists && count($tables) > 0) {
        echo "<p><a href='admin/login.php' style='padding:10px 20px; background:#5DADEC; color:white; text-decoration:none; border-radius:5px;'>Go to Admin Login</a></p>";
    } else {
        echo "<p><a href='setup_database.php' style='padding:10px 20px; background:#5DADEC; color:white; text-decoration:none; border-radius:5px;'>Run Database Setup</a></p>";
    }

} catch (PDOException $e) {
    echo "✗ <span style='color:red;'><strong>Connection FAILED!</strong></span><br>";
    echo "<hr>";
    echo "<h2>Error Details:</h2>";
    echo "<pre style='background:#fee;padding:10px;'>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<hr>";

    echo "<h2>Common Solutions:</h2>";
    echo "<ol>";
    echo "<li><strong>Check MySQL is running:</strong><br><code>sudo systemctl status mysql</code></li>";
    echo "<li><strong>Verify credentials are correct in config.php</strong></li>";
    echo "<li><strong>Create database and user:</strong><br><pre style='background:#f4f4f4;padding:10px;'>
mysql -u root -p

CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER '" . DB_USER . "'@'localhost' IDENTIFIED BY '" . DB_PASS . "';
GRANT ALL PRIVILEGES ON " . DB_NAME . ".* TO '" . DB_USER . "'@'localhost';
FLUSH PRIVILEGES;
</pre></li>";
    echo "<li><strong>If using different credentials, update config.php:</strong><br>DB_USER, DB_PASS, DB_NAME</li>";
    echo "</ol>";
}

echo "<hr>";
echo "<p><small>Delete this file after testing for security: <code>rm test_db_connection.php</code></small></p>";
?>
