<?php
session_start();
require_once '../connection/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$checkout_items = $_SESSION['checkout_items'] ?? [];

if (empty($checkout_items)) {
    echo json_encode(['status' => 'error', 'message' => 'No items to checkout']);
    exit;
}

$items = [];
$subtotal = 0;

// Case 1: If session contains numeric cart IDs (from cart checkout)
if (is_numeric($checkout_items[0])) {
    $cart_ids = implode(',', array_map('intval', $checkout_items));
    $sql = "SELECT c.id as cart_id, p.id, p.name, p.price, p.image, c.quantity, c.size,
                   (p.price * c.quantity) as subtotal
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.id IN ($cart_ids) AND c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
        $subtotal += $row['subtotal'];
    }
}

// Case 2: If session contains direct product data (from Buy Now)
else {
    foreach ($checkout_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
        $items[] = [
            'id' => $item['product_id'],
            'cart_id' => null,
            'name' => $item['name'],
            'price' => $item['price'],
            'image' => $item['image'],
            'quantity' => $item['quantity'],
            'size' => $item['size'] ?? 'N/A',
            'subtotal' => $item['price'] * $item['quantity']
        ];
    }
}

$shipping = $subtotal > 500 ? 0 : 50;
$total = $subtotal + $shipping;

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'items' => $items,
    'totals' => [
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total' => $total
    ]
]);
?>
