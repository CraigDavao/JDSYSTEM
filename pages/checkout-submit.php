<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

// Make sure user is logged in
if (empty($_SESSION['user_id'])) {
    header("Location: " . SITE_URL . "login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch cart items for this user
$stmt = $conn->prepare("SELECT c.product_id, c.quantity, c.size, c.color_id, c.color_name, p.name AS product_name, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = [];
while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
}
$stmt->close();

if (empty($cart_items)) {
    echo "Cart is empty.";
    exit;
}

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping_fee = 50; // fixed or dynamic
$total_amount = $subtotal + $shipping_fee;

// Generate unique order number
$order_number = 'ORD' . date('YmdHis') . rand(100, 999);

// Insert into orders table
$stmt = $conn->prepare("INSERT INTO orders (user_id, order_number, subtotal, shipping_fee, total_amount, payment_method, fullname, email, phone, address, city, province, zipcode, country, status, created_at, quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), ?)");
$fullname = $_POST['fullname'] ?? $_SESSION['user_name'];
$email = $_POST['email'] ?? $_SESSION['user_email'];
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';
$city = $_POST['city'] ?? '';
$province = $_POST['province'] ?? '';
$zipcode = $_POST['zipcode'] ?? '';
$country = $_POST['country'] ?? 'Philippines';
$payment_method = $_POST['payment_method'] ?? 'cod';
$quantity_total = array_sum(array_column($cart_items, 'quantity'));

$stmt->bind_param("iidddsssssssssi", $user_id, $order_number, $subtotal, $shipping_fee, $total_amount, $payment_method, $fullname, $email, $phone, $address, $city, $province, $zipcode, $country, $quantity_total);
$stmt->execute();

// Get the inserted order_id
$order_id = $stmt->insert_id;
$stmt->close();

// Insert order items
$stmt = $conn->prepare("INSERT INTO order_items (user_id, order_id, product_id, product_name, price, quantity, size, color_id, color_name, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($cart_items as $item) {
    $item_subtotal = $item['price'] * $item['quantity'];
    $stmt->bind_param(
        "iiiisiiiss",
        $user_id,
        $order_id,
        $item['product_id'],
        $item['product_name'],
        $item['price'],
        $item['quantity'],
        $item['size'],
        $item['color_id'],
        $item['color_name'],
        $item_subtotal
    );
    $stmt->execute();
}
$stmt->close();

// Clear the cart
$stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

// Redirect to order confirmation page
header("Location: " . SITE_URL . "pages/order-confirmation.php?order_id=" . $order_id);
exit;
