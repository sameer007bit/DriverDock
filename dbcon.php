<?php
// Database configuration
$host = "localhost";   // Database host
$user = "root";        // Database username
$pass = "";            // Database password
$db   = "drivedock";   // Database name

// Enable detailed error reporting for mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Create a new connection
    $conn = new mysqli($host, $user, $pass, $db);

    // Set charset to UTF-8 for better compatibility
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    // If connection fails, log error and show generic message
    error_log("Database Connection Failed: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}
?>