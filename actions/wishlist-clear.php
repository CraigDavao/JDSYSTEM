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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Delete all wishlist items for the current user
        $delete_sql = "DELETE FROM wishlist WHERE user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $user_id);
        
        if ($delete_stmt->execute()) {
            echo "success";
        } else {
            echo "database_error";
        }
        
        $delete_stmt->close();
    } catch (Exception $e) {
        echo "exception";
    }
} else {
    echo "invalid_method";
}
?>