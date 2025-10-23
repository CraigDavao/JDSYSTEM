<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$response = [
    'status' => 'success',
    'items' => [],
    'totals' => [
        'subtotal' => 0,
        'shipping' => 0,
        'total' => 0
    ]
];

// 🟣 Check for buy now product first
if (isset($_SESSION['buy_now_product'])) {
    $buyNowProduct = $_SESSION['buy_now_product'];
    
    if (!isset($buyNowProduct['product_id']) || $buyNowProduct['product_id'] <= 0) {
        $response['status'] = 'error';
        $response['message'] = 'Invalid product in buy now session';
    } else {
        $response['items'] = [$buyNowProduct];
        
        // Calculate totals
        $subtotal = $buyNowProduct['price'] * $buyNowProduct['quantity'];
        $shipping = $subtotal > 500 ? 0 : 50;
        $total = $subtotal + $shipping;
        
        $response['totals']['subtotal'] = $subtotal;
        $response['totals']['shipping'] = $shipping;
        $response['totals']['total'] = $total;
    }
    
} 
// 🟣 Otherwise check for cart items
else if (isset($_SESSION['checkout_items']) && !empty($_SESSION['checkout_items'])) {
    $cart_ids = implode(',', array_map('intval', $_SESSION['checkout_items']));
    
    // Updated query to match your products table structure
    $stmt = $conn->prepare("
        SELECT 
            cart.id AS cart_id,
            cart.product_id,
            products.id, 
            products.name,
            products.price,
            products.sale_price,
            products.actual_sale_price,
            products.image,
            cart.quantity,
            cart.size
        FROM cart 
        INNER JOIN products ON cart.product_id = products.id 
        WHERE cart.id IN ($cart_ids) AND cart.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    $subtotal = 0;

    while ($row = $result->fetch_assoc()) {
        if (!isset($row['product_id']) || $row['product_id'] <= 0) {
            continue;
        }

        // Handle image
        $image_data = $row['image'] ? $row['image'] : 'sample1.jpg';
        
        // Calculate price
        $displayPrice = !empty($row['actual_sale_price']) ? $row['actual_sale_price'] : 
                       (!empty($row['sale_price']) && $row['sale_price'] > 0 ? $row['sale_price'] : $row['price']);
        
        $itemSubtotal = $displayPrice * $row['quantity'];
        $subtotal += $itemSubtotal;
        
        $items[] = [
            'cart_id' => $row['cart_id'],
            'product_id' => intval($row['product_id']),
            'name' => $row['name'],
            'price' => floatval($displayPrice),
            'quantity' => intval($row['quantity']),
            'size' => $row['size'],
            'image' => $image_data,
            'subtotal' => $itemSubtotal,
            'is_buy_now' => false
        ];
    }
    
    $shipping = $subtotal > 500 ? 0 : 50;
    $total = $subtotal + $shipping;
    
    $response['items'] = $items;
    $response['totals']['subtotal'] = $subtotal;
    $response['totals']['shipping'] = $shipping;
    $response['totals']['total'] = $total;
} 
else {
    $response['status'] = 'error';
    $response['message'] = 'No items to checkout';
}

echo json_encode($response);
?>