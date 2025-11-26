<?php
// connection.php - Improved version
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['path' => '/', 'samesite' => 'Lax']);
    session_start();
}

require_once __DIR__ . '/../config.php';

// Add connection timeout and error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    // Set UTF-8 for emojis
    $conn->set_charset("utf8mb4");
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    error_log("✅ Connected to Hostinger DB successfully");
    
} catch (Exception $e) {
    error_log("❌ DB CONNECTION ERROR: " . $e->getMessage());
    // Don't display detailed errors to users in production
    die("Database connection error. Please try again later.");
}
?>