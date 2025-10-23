<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/order_errors.log');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE || !$input) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload']);
    exit;
}

try {
    $conn->begin_transaction();

    $shippingInfo = $input['shippingInfo'] ?? [];
    $items = $input['items'] ?? [];

    if (empty($items)) {
        throw new Exception('No items provided for this order.');
    }

    // Generate unique order number
    $order_number = 'ORD' . date('YmdHis') . mt_rand(100, 999);
    $total_quantity = array_sum(array_map(fn($i) => intval($i['quantity']), $items));
    $notes = $shippingInfo['notes'] ?? '';

    // Insert main order
    $stmt = $conn->prepare("
        INSERT INTO orders (
            user_id, order_number, subtotal, shipping_fee, total_amount, 
            payment_method, fullname, email, phone, address, city, province, 
            zipcode, notes, status, quantity
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
    ");

    $stmt->bind_param(
        "isdddsssssssssi",
        $user_id,
        $order_number,
        $input['subtotal'],
        $input['shipping'],
        $input['total'],
        $input['paymentMethod'],
        $shippingInfo['fullname'],
        $shippingInfo['email'],
        $shippingInfo['phone'],
        $shippingInfo['address'],
        $shippingInfo['city'],
        $shippingInfo['province'],
        $shippingInfo['zipcode'],
        $notes,
        $total_quantity
    );

    if (!$stmt->execute()) throw new Exception('Failed to create order: ' . $stmt->error);
    $order_id = $conn->insert_id;
    $stmt->close();

    // Insert order items
    $stmt = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, quantity, size, price, subtotal)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($items as $index => $item) {
        if (empty($item['product_id']) || $item['product_id'] <= 0) {
            throw new Exception("Invalid product_id at index $index");
        }

        $stmt->bind_param(
            "iisisdd",
            $order_id,
            $item['product_id'],
            $item['name'],
            $item['quantity'],
            $item['size'],
            $item['price'],
            $item['subtotal']
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to insert order item: ' . $stmt->error);
        }
    }
    $stmt->close();

    // Cleanup sessions
    $is_buy_now = !empty($items[0]['is_buy_now']);
    if ($is_buy_now) {
        unset($_SESSION['buy_now_product']);
    } elseif (!empty($_SESSION['checkout_items'])) {
        $cart_ids = implode(',', array_map('intval', $_SESSION['checkout_items']));
        $conn->query("DELETE FROM cart WHERE id IN ($cart_ids) AND user_id = $user_id");
        unset($_SESSION['checkout_items']);
    }

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'order_id' => $order_id,
        'order_number' => $order_number,
        'message' => 'Order placed successfully'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Order Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to place order: ' . $e->getMessage()
    ]);
}
?>
