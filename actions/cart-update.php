<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'not_logged_in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_id = $_POST['cart_id'] ?? 0;
$quantity = $_POST['quantity'] ?? 1;
$size = $_POST['size'] ?? 'M';

if ($cart_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'invalid_cart_id']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE cart SET quantity = ?, size = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("isii", $quantity, $size, $cart_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Cart updated']);
    } else {
        throw new Exception('Failed to update cart');
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>