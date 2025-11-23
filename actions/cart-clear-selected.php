<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';
header('Content-Type: text/plain');

if (!isset($_SESSION['user_id'])) {
    echo "not_logged_in";
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the cart IDs from POST data
    $cart_ids_json = isset($_POST['cart_ids']) ? $_POST['cart_ids'] : '[]';
    $cart_ids = json_decode($cart_ids_json, true);
    
    if (!empty($cart_ids) && is_array($cart_ids)) {
        try {
            // Convert IDs to integers and create placeholders
            $cart_ids = array_map('intval', $cart_ids);
            $placeholders = str_repeat('?,', count($cart_ids) - 1) . '?';
            
            // Delete the cart items
            $delete_sql = "DELETE FROM cart WHERE id IN ($placeholders) AND user_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            
            // Bind parameters: cart IDs + user_id
            $types = str_repeat('i', count($cart_ids)) . 'i';
            $params = array_merge($cart_ids, [$user_id]);
            $delete_stmt->bind_param($types, ...$params);
            
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
        echo "invalid_ids";
    }
} else {
    echo "invalid_method";
}
?>