<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'not_logged_in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
$size = $_POST['size'] ?? 'M';

if ($product_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'invalid_product']);
    exit;
}

// Check product exists
$check = $conn->prepare("SELECT id FROM products WHERE id = ? AND (is_active IS NULL OR is_active = 1)");
$check->bind_param("i", $product_id);
$check->execute();
$res = $check->get_result();
if (!$res->num_rows) {
    echo json_encode(['status' => 'error', 'message' => 'product_not_found']);
    exit;
}

// Check if product already in cart
$checkCart = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND size = ?");
$checkCart->bind_param("iis", $user_id, $product_id, $size);
$checkCart->execute();
$cartRes = $checkCart->get_result();

if ($cartRes->num_rows > 0) {
    $row = $cartRes->fetch_assoc();
    $newQuantity = $row['quantity'] + $quantity;
    $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $update->bind_param("ii", $newQuantity, $row['id']);
    $update->execute();
} else {
    $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, size, added_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiis", $user_id, $product_id, $quantity, $size);
    $stmt->execute();
}

// Return updated cart quantity
$totalQty = 0;
$cartCount = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
$cartCount->bind_param("i", $user_id);
$cartCount->execute();
$result = $cartCount->get_result()->fetch_assoc();
$totalQty = $result['total'] ?? 0;

echo json_encode(['status' => 'success', 'cartCount' => $totalQty]);