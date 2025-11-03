<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'not_logged_in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch cart items with product information
    $sql = "
        SELECT 
            c.id as cart_id,
            c.product_id,
            c.color_id,
            c.color_name as color,
            c.quantity,
            c.size,
            c.price,
            c.added_at,
            p.name,
            pi.image,
            pi.image_format,
            (c.price * c.quantity) as subtotal
        FROM cart c
        LEFT JOIN products p ON c.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.color_name = c.color_name
        WHERE c.user_id = ?
        ORDER BY c.added_at DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cart = [];
    while ($row = $result->fetch_assoc()) {
        // Handle image data
        if (!empty($row['image'])) {
            $row['image'] = 'data:' . ($row['image_format'] ?? 'image/jpeg') . ';base64,' . base64_encode($row['image']);
        }
        $cart[] = $row;
    }
    
    echo json_encode([
        'status' => 'success',
        'cart' => $cart
    ]);
    
} catch (Exception $e) {
    error_log("Cart Fetch Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch cart'
    ]);
}
?>