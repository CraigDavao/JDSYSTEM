<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;
error_log("User ID: " . $user_id);

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
error_log("Input received: " . print_r($input, true));

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();
    error_log("Transaction started");

    // 1. Create order - using your EXACT table structure
    $shippingInfo = $input['shippingInfo'];
    error_log("Shipping info: " . print_r($shippingInfo, true));
    
    // Generate order number
    $order_number = 'ORD' . date('YmdHis') . mt_rand(100, 999);
    
    // Calculate total quantity from items
    $total_quantity = 0;
    foreach ($input['items'] as $item) {
        $total_quantity += intval($item['quantity']);
    }
    
    error_log("Order details - Number: $order_number, Quantity: $total_quantity");
    error_log("Financials - Subtotal: {$input['subtotal']}, Shipping: {$input['shipping']}, Total: {$input['total']}");
    
    // Insert into orders table with your exact column names
    $stmt = $conn->prepare("
        INSERT INTO orders (
            user_id, order_number, subtotal, shipping_fee, total_amount, 
            payment_method, fullname, email, phone, address, city, province, 
            zipcode, notes, status, quantity
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
    ");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare order statement: ' . $conn->error);
    }
    
    $notes = $shippingInfo['notes'] ?? '';
    
    $stmt->bind_param("isdddsssssssssi", 
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
    
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception('Failed to execute order statement: ' . $stmt->error);
    }
    
    $order_id = $conn->insert_id;
    $stmt->close();
    
    error_log("Order created successfully. Order ID: $order_id");

    // 2. Add order items
    $stmt = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, quantity, size, price, subtotal)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        throw new Exception('Failed to prepare order items statement: ' . $conn->error);
    }

    foreach ($input['items'] as $index => $item) {
        error_log("Processing item $index: " . print_r($item, true));
        
        if (!isset($item['product_id']) || $item['product_id'] <= 0) {
            throw new Exception('Invalid product_id in order items at index ' . $index);
        }
        
        $stmt->bind_param("iisisd",
            $order_id,
            $item['product_id'],
            $item['name'],
            $item['quantity'],
            $item['size'],
            $item['price'],
            $item['subtotal']
        );
        
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception('Failed to execute order item statement: ' . $stmt->error);
        }
        
        error_log("Order item added: {$item['name']}");
    }
    $stmt->close();

    // 3. Handle session cleanup based on order type
    $is_buy_now = !empty($input['items'][0]['is_buy_now']);
    error_log("Is buy now order: " . ($is_buy_now ? 'yes' : 'no'));
    
    if ($is_buy_now) {
        // Clear buy now session
        unset($_SESSION['buy_now_product']);
        error_log("Buy now session cleared");
    } else if (isset($_SESSION['checkout_items'])) {
        // Clear cart items that were checked out
        $cart_ids = implode(',', array_map('intval', $_SESSION['checkout_items']));
        $delete_result = $conn->query("DELETE FROM cart WHERE id IN ($cart_ids) AND user_id = $user_id");
        if ($delete_result) {
            error_log("Cart items deleted: " . $conn->affected_rows . " items");
        }
        unset($_SESSION['checkout_items']);
        error_log("Checkout session cleared");
    }

    // Commit transaction
    $conn->commit();
    error_log("Transaction committed successfully");

    echo json_encode([
        'status' => 'success',
        'order_id' => $order_id,
        'order_number' => $order_number,
        'message' => 'Order placed successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("ERROR in place-order: " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error', 
        'message' => 'Failed to place order: ' . $e->getMessage()
    ]);
}
?>