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
            products.image, 
            cart.quantity, 
            cart.size, 
            (products.price * cart.quantity) AS subtotal
        FROM cart 
        INNER JOIN products ON cart.product_id = products.id 
        WHERE cart.user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $cart = [];
    while ($row = $result->fetch_assoc()) {
        $cart[] = $row;
    }

    $response["status"] = "success";
    $response["cart"] = $cart;
}

echo json_encode($response);
?>
