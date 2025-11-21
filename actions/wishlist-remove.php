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
    // Get the wishlist ID from POST data
    $wishlist_id = isset($_POST['wishlist_id']) ? (int)$_POST['wishlist_id'] : 0;
    
    if ($wishlist_id > 0) {
        try {
            // First, verify the wishlist item belongs to the current user
            $verify_sql = "SELECT id FROM wishlist WHERE id = ? AND user_id = ?";
            $verify_stmt = $conn->prepare($verify_sql);
            $verify_stmt->bind_param("ii", $wishlist_id, $user_id);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            
            if ($verify_result->num_rows === 0) {
                echo "not_found";
                exit;
            }
            
            // Delete the wishlist item
            $delete_sql = "DELETE FROM wishlist WHERE id = ? AND user_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("ii", $wishlist_id, $user_id);
            
            if ($delete_stmt->execute()) {
                echo "success";
            } else {
                echo "database_error";
            }
            
            $delete_stmt->close();
            $verify_stmt->close();
        } catch (Exception $e) {
            echo "exception";
        }
    } else {
        echo "invalid_id";
    }
} else {
    echo "invalid_method";
}
?>