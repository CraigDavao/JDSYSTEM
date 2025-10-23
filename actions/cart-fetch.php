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
       // 🟣 Normalize all price fields as float to avoid string/NULL issues
        $actualSale = isset($row['actual_sale_price']) ? (float)$row['actual_sale_price'] : 0;
        $salePrice  = isset($row['sale_price']) ? (float)$row['sale_price'] : 0;
        $regularPrice = isset($row['price']) ? (float)$row['price'] : 0;

        // 🟣 Determine which price to use
        if ($actualSale > 0) {
            $displayPrice = $actualSale;
        } elseif ($salePrice > 0) {
            $displayPrice = $salePrice;
        } else {
            $displayPrice = $regularPrice;
        }

        // 🟣 Update subtotal correctly
        $row['price'] = $displayPrice;
        $row['subtotal'] = $displayPrice * (int)$row['quantity'];


        
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