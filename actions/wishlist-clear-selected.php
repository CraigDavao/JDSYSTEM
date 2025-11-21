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
    // Get the wishlist IDs from POST data
    $wishlist_ids_json = isset($_POST['wishlist_ids']) ? $_POST['wishlist_ids'] : '[]';
    $wishlist_ids = json_decode($wishlist_ids_json, true);
    
    if (!empty($wishlist_ids) && is_array($wishlist_ids)) {
        try {
            // Convert IDs to integers and create placeholders
            $wishlist_ids = array_map('intval', $wishlist_ids);
            $placeholders = str_repeat('?,', count($wishlist_ids) - 1) . '?';
            
            // First, verify the wishlist items belong to the current user
            $verify_sql = "SELECT id FROM wishlist WHERE id IN ($placeholders) AND user_id = ?";
            $verify_stmt = $conn->prepare($verify_sql);
            
            // Bind parameters: wishlist IDs + user_id
            $types = str_repeat('i', count($wishlist_ids)) . 'i';
            $params = array_merge($wishlist_ids, [$user_id]);
            $verify_stmt->bind_param($types, ...$params);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            
            $valid_ids = [];
            while ($row = $verify_result->fetch_assoc()) {
                $valid_ids[] = $row['id'];
            }
            
            if (empty($valid_ids)) {
                echo "not_found";
                exit;
            }
            
            // Delete the wishlist items
            $delete_sql = "DELETE FROM wishlist WHERE id IN ($placeholders) AND user_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            
            // Bind parameters: valid wishlist IDs + user_id
            $delete_types = str_repeat('i', count($valid_ids)) . 'i';
            $delete_params = array_merge($valid_ids, [$user_id]);
            $delete_stmt->bind_param($delete_types, ...$delete_params);
            
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
        echo "invalid_ids";
    }
} else {
    echo "invalid_method";
}
?>