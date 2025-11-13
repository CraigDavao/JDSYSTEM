<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

header('Content-Type: text/plain');

if (!isset($_SESSION['user_id'])) {
    echo "not_logged_in";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "invalid_method";
    exit;
}

$user_id = $_SESSION['user_id'];
$wishlist_id = $_POST['wishlist_id'] ?? '';

if (empty($wishlist_id)) {
    echo "invalid_id";
    exit;
}

try {
    // Get wishlist item details
    $sql = "SELECT w.product_id, w.color_id FROM wishlist w WHERE w.id = ? AND w.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $wishlist_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "not_found";
        exit;
    }
    
    $item = $result->fetch_assoc();
    $product_id = $item['product_id'];
    $color_id = $item['color_id'];
    
    // Check if item already exists in cart
    $cart_sql = "SELECT id FROM cart WHERE user_id = ? AND product_id = ? AND color_id = ?";
    $cart_stmt = $conn->prepare($cart_sql);
    $cart_stmt->bind_param("iii", $user_id, $product_id, $color_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    
    if ($cart_result->num_rows > 0) {
        // Item already in cart, just remove from wishlist
        $delete_sql = "DELETE FROM wishlist WHERE id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $wishlist_id, $user_id);
        
        if ($delete_stmt->execute()) {
            echo "success";
        } else {
            echo "database_error";
        }
        exit;
    }
    
    // Add to cart
    $insert_sql = "INSERT INTO cart (user_id, product_id, color_id, quantity, size, added_at) VALUES (?, ?, ?, 1, 'M', NOW())";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iii", $user_id, $product_id, $color_id);
    
    if ($insert_stmt->execute()) {
        // Remove from wishlist after successful add to cart
        $delete_sql = "DELETE FROM wishlist WHERE id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $wishlist_id, $user_id);
        
        if ($delete_stmt->execute()) {
            echo "success";
        } else {
            echo "database_error";
        }
    } else {
        echo "database_error";
    }
    
} catch (Exception $e) {
    echo "database_error";
}
?>