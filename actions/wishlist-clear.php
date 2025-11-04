<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

header('Content-Type: text/plain');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "not_logged_in";
    exit;
}

$user_id = $_SESSION['user_id'];

error_log("Wishlist clear request received - User ID: $user_id");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Delete all wishlist items for this user
        $delete_sql = "DELETE FROM wishlist WHERE user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $user_id);
        
        if ($delete_stmt->execute()) {
            error_log("Successfully cleared wishlist for user ID: $user_id");
            echo "success";
        } else {
            error_log("Database error: " . $delete_stmt->error);
            echo "database_error";
        }
        
        $delete_stmt->close();
    } catch (Exception $e) {
        error_log("Exception: " . $e->getMessage());
        echo "exception";
    }
} else {
    error_log("Invalid request method");
    echo "invalid_method";
}
?>