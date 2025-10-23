<?php
session_start();
require_once '../connection/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_ids'])) {
    // Clear any previous buy now session
    unset($_SESSION['buy_now_product']);
    
    $cart_ids = explode(',', $_POST['cart_ids']);
    
    // Validate and sanitize cart IDs
    $valid_cart_ids = [];
    foreach ($cart_ids as $cart_id) {
        $cart_id = (int)trim($cart_id);
        if ($cart_id > 0) {
            $valid_cart_ids[] = $cart_id;
        }
    }
    
    if (empty($valid_cart_ids)) {
        echo json_encode(['status' => 'error', 'message' => 'No valid cart items selected']);
        exit;
    }
    
    $_SESSION['checkout_items'] = $valid_cart_ids;
    
    echo json_encode([
        'status' => 'success', 
        'count' => count($valid_cart_ids),
        'items' => $valid_cart_ids
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>