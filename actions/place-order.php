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

try {
    $conn->begin_transaction();

    // Get form data
    $address_id = isset($_POST['address_id']) ? intval($_POST['address_id']) : 0;
    $payment_method = $_POST['payment_method'] ?? '';
    $courier = $_POST['courier'] ?? '';
    $delivery_schedule = $_POST['delivery_schedule'] ?? '';
    $items = json_decode($_POST['items'] ?? '[]', true);
    $totals = json_decode($_POST['totals'] ?? '{}', true);
    $is_buy_now = (isset($_POST['is_buy_now']) && $_POST['is_buy_now'] === 'true');

    // Basic validation
    if (!$address_id || !$payment_method || !$courier || !$delivery_schedule) {
        throw new Exception('All shipping and payment information is required.');
    }

    if (empty($items) || !is_array($items)) {
        throw new Exception('No items provided for this order.');
    }

    // Validate totals structure
    if (!isset($totals['subtotal'], $totals['shipping'], $totals['total'])) {
        throw new Exception('Totals are missing or malformed.');
    }

    // Handle GCash receipt upload (if applicable)
    $gcash_receipt_path = null;
    if ($payment_method === 'gcash' && isset($_FILES['gcash_receipt'])) {
        $receipt = $_FILES['gcash_receipt'];

        if ($receipt['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('GCash receipt upload failed.');
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        if (!in_array($receipt['type'], $allowed_types)) {
            throw new Exception('Invalid file type. Please upload JPG, PNG, GIF or PDF files only.');
        }

        if ($receipt['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum size is 5MB.');
        }

        $upload_dir = __DIR__ . '/../uploads/receipts/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_extension = pathinfo($receipt['name'], PATHINFO_EXTENSION);
        $filename = 'receipt_' . $user_id . '_' . time() . '.' . $file_extension;
        $gcash_receipt_path = 'uploads/receipts/' . $filename;

        if (!move_uploaded_file($receipt['tmp_name'], __DIR__ . '/../' . $gcash_receipt_path)) {
            throw new Exception('Failed to save GCash receipt.');
        }
    }

    // Get address details
    $address_stmt = $conn->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
    $address_stmt->bind_param("ii", $address_id, $user_id);
    $address_stmt->execute();
    $address_result = $address_stmt->get_result();
    $address = $address_result->fetch_assoc();
    $address_stmt->close();

    if (!$address) {
        throw new Exception('Invalid shipping address.');
    }

    // Generate order number and totals
    $order_number = 'ORD' . date('YmdHis') . mt_rand(100, 999);
    $total_quantity = array_sum(array_map(fn($i) => intval($i['quantity'] ?? 0), $items));

    // Prepare order insert
    // NOTE: columns must exactly match your orders table. We use placeholders for all fields except id.
    $status = 'pending';
    $notes = $_POST['notes'] ?? ($totals['notes'] ?? '');

    $order_insert = $conn->prepare("
        INSERT INTO orders (
            user_id, order_number, subtotal, shipping_fee, total_amount,
            payment_method, gcash_receipt_path, courier, delivery_schedule,
            fullname, email, phone, address, city, province, zipcode,
            address_id, country, notes, status, quantity
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$order_insert) {
        throw new Exception('Prepare failed for orders insert: ' . $conn->error);
    }

    // Build bind types and values in correct order:
    // i  user_id
    // s  order_number
    // d  subtotal
    // d  shipping_fee
    // d  total_amount
    // s  payment_method
    // s  gcash_receipt_path (nullable string)
    // s  courier
    // s  delivery_schedule
    // s  fullname
    // s  email
    // s  phone
    // s  address (street)
    // s  city
    // s  province (state)
    // s  zipcode (zip_code)
    // i  address_id
    // s  country
    // s  notes
    // s  status
    // i  quantity

    // Ensure receipt path is null or string
    if ($gcash_receipt_path === null) $gcash_receipt_path = null; // keep null

    $bind_types = "isdddsssssssssssisssi"; // 21 types (see analysis for mapping)
    // Explanation: i s d d d s s s s s s s s s s s i s s s i
    // To be safe, ensure all address fields exist
    $fullname = $address['fullname'] ?? ($address['name'] ?? '');
    $email = $address['email'] ?? '';
    $phone = $address['phone'] ?? '';
    $street = $address['street'] ?? ($address['address'] ?? '');
    $city = $address['city'] ?? '';
    $province = $address['state'] ?? $address['province'] ?? '';
    $zip_code = $address['zip_code'] ?? $address['zipcode'] ?? $address['zip'] ?? '';
    $country = $address['country'] ?? '';

    // Bind params - note: mysqli will insert NULL when variable === null for 's' type
    $order_insert->bind_param(
        $bind_types,
        $user_id,
        $order_number,
        $totals['subtotal'],
        $totals['shipping'],
        $totals['total'],
        $payment_method,
        $gcash_receipt_path,
        $courier,
        $delivery_schedule,
        $fullname,
        $email,
        $phone,
        $street,
        $city,
        $province,
        $zip_code,
        $address_id,
        $country,
        $notes,
        $status,
        $total_quantity
    );

    if (!$order_insert->execute()) {
        throw new Exception('Failed to create order: ' . $order_insert->error);
    }
    $order_id = $conn->insert_id;
    $order_insert->close();

    // Insert order items
    $item_insert = $conn->prepare("
        INSERT INTO order_items (
            user_id, order_id, product_id, product_name, price, quantity, 
            size, color_id, color_name, subtotal
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$item_insert) {
        throw new Exception('Prepare failed for order_items insert: ' . $conn->error);
    }

    // Correct bind types for order_items:
    // i user_id
    // i order_id
    // i product_id
    // s product_name
    // d price
    // i quantity
    // s size
    // i color_id
    // s color_name
    // d subtotal
    $item_types = "iiisdisisd";

    foreach ($items as $item) {
        // sanitize and default item fields
        $product_id = intval($item['product_id'] ?? 0);
        $product_name = $item['name'] ?? ($item['product_name'] ?? '');
        $price = isset($item['price']) ? floatval($item['price']) : 0.00;
        $quantity = isset($item['quantity']) ? intval($item['quantity']) : 0;
        $size = $item['size'] ?? '';
        $color_id = isset($item['color_id']) ? intval($item['color_id']) : null;
        $color_name = $item['color_name'] ?? null;
        $item_subtotal = isset($item['subtotal']) ? floatval($item['subtotal']) : ($price * $quantity);

        // Normalize nullable strings to null for bind (mysqli treats null variable as SQL NULL)
        if ($color_id === 0) $color_id = null;
        if ($color_name === '') $color_name = null;

        // Bind and execute
        $item_insert->bind_param(
            $item_types,
            $user_id,
            $order_id,
            $product_id,
            $product_name,
            $price,
            $quantity,
            $size,
            $color_id,
            $color_name,
            $item_subtotal
        );

        if (!$item_insert->execute()) {
            throw new Exception('Failed to add order item: ' . $item_insert->error);
        }

        // Update product amount in product_colors (only if color id present)
        if (!empty($color_id)) {
            $updateStock = $conn->prepare("
                UPDATE product_colors
                SET quantity = GREATEST(quantity - ?, 0)
                WHERE id = ?
            ");
            if (!$updateStock) {
                throw new Exception('Prepare failed for update stock: ' . $conn->error);
            }
            $updateStock->bind_param("ii", $quantity, $color_id);
            if (!$updateStock->execute()) {
                throw new Exception('Failed to update product stock: ' . $updateStock->error);
            }
            $updateStock->close();
        } else {
            // Optionally update product quantity on product table if you track stock there.
            // (Left intentionally unchanged per your request not to touch unrelated code.)
        }
    }
    $item_insert->close();

    // Cleanup sessions
    if ($is_buy_now) {
        unset($_SESSION['buy_now_product']);
    } elseif (!empty($_SESSION['checkout_items']) && is_array($_SESSION['checkout_items'])) {
        $cart_ids = implode(',', array_map('intval', $_SESSION['checkout_items']));
        if ($cart_ids !== '') {
            $conn->query("DELETE FROM cart WHERE id IN ($cart_ids) AND user_id = " . intval($user_id));
        }
        unset($_SESSION['checkout_items']);
    }

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'order_id' => $order_id,
        'order_number' => $order_number,
        'message' => 'Order placed successfully'
    ]);
    exit;

} catch (Exception $e) {
    $conn->rollback();
    error_log("Order Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to place order: ' . $e->getMessage()
    ]);
    exit;
}
?>
