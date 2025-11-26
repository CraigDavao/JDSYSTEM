<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['path' => '/']);
    session_start();
}

require_once __DIR__ . '/../config.php';

// Database connection (Hostinger)
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    error_log("❌ DB CONNECTION FAILED: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

// Force UTF-8 for emojis + images
$conn->set_charset("utf8mb4");

// Debug log if needed
error_log("✅ Connected to Hostinger DB successfully");
?>
