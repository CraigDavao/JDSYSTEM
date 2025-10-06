<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['checkout_cart_ids'])) {
    echo "No items selected for checkout.";
    exit;
}

$cart_ids = implode(",", array_map('intval', $_SESSION['checkout_cart_ids']));

$sql = "SELECT c.id AS cart_id, c.quantity, c.size, p.id AS product_id, p.name, p.price, p.image 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.id IN ($cart_ids)";
$result = $conn->query($sql);

$total = 0;
while ($item = $result->fetch_assoc()) {
    $subtotal = $item['price'] * $item['quantity'];
    $total += $subtotal;
    echo "<div>{$item['name']} ({$item['size']}) × {$item['quantity']} = ₱{$subtotal}</div>";
}

echo "<h3>Total: ₱{$total}</h3>";
