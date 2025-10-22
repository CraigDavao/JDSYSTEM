<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

if (!isset($_SESSION['user_id'])) {
    // fallback for testing
    $_SESSION['user_id'] = 1; 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    if ($product_id <= 0 || $quantity <= 0) {
        die('Invalid product or quantity.');
    }

    // Get product info
    $sql = "SELECT name, price, sale_price FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        die('Product not found.');
    }

    $price = ($product['sale_price'] > 0) ? $product['sale_price'] : $product['price'];
    $subtotal = $price * $quantity;
    $shipping_fee = 100.00;
    $total_amount = $subtotal + $shipping_fee;
    $order_number = 'JD' . time();

    // Insert order
    $sql = "INSERT INTO orders (
                user_id, order_number, subtotal, shipping_fee, total_amount,
                payment_method, status, created_at, fullname, email, phone,
                address, city, province, zipcode, notes
            ) VALUES (
                ?, ?, ?, ?, ?, 'COD', 'Pending', NOW(), 
                'Test User', 'test@example.com', '09123456789',
                'Sample Address', 'Sample City', 'Sample Province', '1000', ''
            )";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("isddd", $user_id, $order_number, $subtotal, $shipping_fee, $total_amount);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        header("Location: ../pages/order-success.php?order=" . urlencode($order_number));
        exit;
    } else {
        echo "Error placing order: " . $stmt->error;
    }
}
?>
