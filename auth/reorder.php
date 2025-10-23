<?php
require_once '../config.php';
require_once '../connection/connection.php';
session_start();

// Set JSON header
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    $user_id = $_SESSION['user_id'];
    
    // Verify the order belongs to the user
    $stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    // Get order items
    $stmt = $conn->prepare("SELECT product_id, product_name, price, quantity FROM order_items WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items_result = $stmt->get_result();
    
    // Here you would add items to cart
    // This is a placeholder - integrate with your cart system
    $added_items = [];
    while ($item = $items_result->fetch_assoc()) {
        $added_items[] = $item['product_name'];
        // Add to cart logic would go here
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Added ' . count($added_items) . ' items to cart'
    ]);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>