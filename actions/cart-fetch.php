<?php
session_start();
require_once '../connection/connection.php';

header('Content-Type: application/json');

$response = ["status" => "error", "cart" => []];

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        SELECT 
            cart.id AS cart_id, 
            cart.product_id, 
            products.name, 
            products.price, 
            products.sale_price,
            products.actual_sale_price,
            cart.quantity, 
            cart.size, 
            (products.price * cart.quantity) AS subtotal,
            pi.image,
            pi.image_format
        FROM cart 
        INNER JOIN products ON cart.product_id = products.id 
        LEFT JOIN product_images pi ON products.id = pi.product_id 
            AND pi.id = (
                SELECT MIN(pi2.id) 
                FROM product_images pi2 
                WHERE pi2.product_id = products.id
            )
        WHERE cart.user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $cart = [];
    while ($row = $result->fetch_assoc()) {
        // 🟣 Handle blob image conversion
        if (!empty($row['image'])) {
            // Convert blob to Base64
            $mimeType = !empty($row['image_format']) ? $row['image_format'] : 'image/jpeg';
            $row['image'] = 'data:' . $mimeType . ';base64,' . base64_encode($row['image']);
        } else {
            $row['image'] = null;
        }
        
        // 🟣 Use sale price if available
        $displayPrice = !empty($row['actual_sale_price']) ? $row['actual_sale_price'] : 
                       (!empty($row['sale_price']) && $row['sale_price'] > 0 ? $row['sale_price'] : $row['price']);
        
        // 🟣 Update subtotal with correct price
        $row['price'] = $displayPrice;
        $row['subtotal'] = $displayPrice * $row['quantity'];
        
        $cart[] = $row;
    }

    $response["status"] = "success";
    $response["cart"] = $cart;
}

echo json_encode($response);
?>