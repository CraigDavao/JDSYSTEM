<?php
session_start();
require_once '../connection/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = $_SESSION['user_id'];
    
    $conn->begin_transaction();

    try {
        // Generate order number
        $order_number = 'JD' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Calculate totals
        $subtotal = $input['subtotal'];
        $shipping_fee = $input['shipping'];
        $total_amount = $input['total'];
        $payment_method = $input['paymentMethod'];

        // Extract shipping info
        $ship = $input['shippingInfo'];
        $fullname = $ship['fullname'];
        $email = $ship['email'];
        $phone = $ship['phone'];
        $address = $ship['address'];
        $city = $ship['city'];
        $province = $ship['province'];
        $zipcode = $ship['zipcode'];
        $notes = $ship['notes'];

        // Insert order
        $stmt = $conn->prepare("
            INSERT INTO orders 
            (user_id, order_number, subtotal, shipping_fee, total_amount, payment_method, fullname, email, phone, address, city, province, zipcode, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "isdddsssssssss",
            $user_id,
            $order_number,
            $subtotal,
            $shipping_fee,
            $total_amount,
            $payment_method,
            $fullname,
            $email,
            $phone,
            $address,
            $city,
            $province,
            $zipcode,
            $notes
        );
        $stmt->execute();
        $order_id = $conn->insert_id;

        // Insert order items
        $stmt = $conn->prepare("
            INSERT INTO order_items (order_id, product_id, product_name, price, quantity, size, subtotal) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($input['items'] as $item) {
            $stmt->bind_param("iisdisd", $order_id, $item['id'], $item['name'], $item['price'], $item['quantity'], $item['size'], $item['subtotal']);
            $stmt->execute();
        }

        // ✅ Remove items from cart
        $cart_ids = array_column($input['items'], 'cart_id');
        if (!empty($cart_ids)) {
            $placeholders = str_repeat('?,', count($cart_ids) - 1) . '?';
            $sql = "DELETE FROM cart WHERE id IN ($placeholders) AND user_id = ?";
            $stmt = $conn->prepare($sql);
            
            $types = str_repeat('i', count($cart_ids)) . 'i';
            $params = array_merge($cart_ids, [$user_id]);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
        }

        $conn->commit();

        echo json_encode([
            'status' => 'success',
            'order_id' => $order_id,
            'order_number' => $order_number,
            'message' => 'Order placed successfully!'
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to place order: ' . $e->getMessage()
        ]);
    }
}
?>
