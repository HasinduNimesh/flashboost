<?php
/**
 * Database connection file
 * 
 * Establishes a PDO connection to the MySQL database
 */

// Database configuration
$db_host = 'fake_host';     // Database host (example: 'localhost')
$db_name = 'fake_db';       // Database name
$db_user = 'fake_user';     // Database username
$db_pass = 'fake_pass';     // Database password
$db_char = 'utf8mb4';       // Character set

// Connection options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,    // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // Return associative arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                     // Use real prepared statements
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $db_char"       // Set character set
];

// Connect to database
try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=$db_char",
        $db_user,
        $db_pass,
        $options
    );
} catch (PDOException $e) {
    // In production, you might want to log the error instead of displaying it
    die("Database connection failed: " . $e->getMessage());
}

/**
 * Helper function for quick debugging
 */
function debug($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    exit;
}
?>