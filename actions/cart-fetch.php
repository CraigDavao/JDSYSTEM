<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'cart' => [], 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT c.id as cart_id, c.quantity, c.size, p.id as product_id, p.name, p.price, p.image,
           (p.price * c.quantity) as subtotal
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart = [];
$totalQuantity = 0;
while ($row = $result->fetch_assoc()) {
    $cart[] = $row;
    $totalQuantity += $row['quantity'];
}

// Debug information
echo json_encode([
    'status' => 'success', 
    'cart' => $cart,
    'unique_items_count' => count($cart),
    'total_quantity' => $totalQuantity,
    'debug' => [
        'user_id' => $user_id,
        'cart_items' => count($cart)
    ]
]);

$stmt->close();
$conn->close();
?>