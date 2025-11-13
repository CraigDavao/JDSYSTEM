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

// Debug logging
error_log("Wishlist remove request received - User ID: $user_id");
error_log("POST data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the wishlist ID from POST data
    $wishlist_id = isset($_POST['wishlist_id']) ? (int)$_POST['wishlist_id'] : 0;
    
    error_log("Attempting to remove wishlist ID: $wishlist_id");
    
    if ($wishlist_id > 0) {
        try {
            // First, verify the wishlist item belongs to the current user
            $verify_sql = "SELECT id FROM wishlist WHERE id = ? AND user_id = ?";
            $verify_stmt = $conn->prepare($verify_sql);
            $verify_stmt->bind_param("ii", $wishlist_id, $user_id);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            
            if ($verify_result->num_rows === 0) {
                error_log("Wishlist item not found or doesn't belong to user");
                echo "not_found";
                exit;
            }
            
            // Delete the wishlist item
            $delete_sql = "DELETE FROM wishlist WHERE id = ? AND user_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("ii", $wishlist_id, $user_id);
            
            if ($delete_stmt->execute()) {
                error_log("Successfully removed wishlist item ID: $wishlist_id");
                echo "success";
            } else {
                error_log("Database error: " . $delete_stmt->error);
                echo "database_error";
            }
            
            $delete_stmt->close();
            $verify_stmt->close();
        } catch (Exception $e) {
            error_log("Exception: " . $e->getMessage());
            echo "exception";
        }
    } else {
        error_log("Invalid wishlist ID: $wishlist_id");
        echo "invalid_id";
    }
} else {
    error_log("Invalid request method");
    echo "invalid_method";
}
?>