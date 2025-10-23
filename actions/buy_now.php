<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not logged in
    header("Location: " . SITE_URL . "auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $size = $_POST['size'] ?? 'M'; // Get size from form

    if ($product_id <= 0 || $quantity <= 0) {
        $_SESSION['error'] = 'Invalid product or quantity.';
        header("Location: " . SITE_URL . "pages/cart.php");
        exit;
    }

    // 🟣 Fetch product details to store complete information
    $stmt = $conn->prepare("
        SELECT p.id, p.name, p.price, p.sale_price, p.actual_sale_price,
               pi.image, pi.image_format
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        $_SESSION['error'] = 'Product not found.';
        header("Location: " . SITE_URL . "pages/cart.php");
        exit;
    }

    // Handle image
    if (!empty($product['image'])) {
        $mimeType = !empty($product['image_format']) ? $product['image_format'] : 'image/jpeg';
        $image_data = 'data:' . $mimeType . ';base64,' . base64_encode($product['image']);
    } else {
        $image_data = 'sample1.jpg';
    }

    // Calculate price
    $displayPrice = !empty($product['actual_sale_price']) ? $product['actual_sale_price'] : 
                   (!empty($product['sale_price']) && $product['sale_price'] > 0 ? $product['sale_price'] : $product['price']);

    // 🟣 Set buy now product in session for checkout
    $_SESSION['buy_now_product'] = [
        'product_id' => $product_id,
        'name' => $product['name'],
        'price' => floatval($displayPrice),
        'quantity' => $quantity,
        'size' => $size,
        'image' => $image_data,
        'subtotal' => floatval($displayPrice) * $quantity,
        'is_buy_now' => true
    ];

    // 🟣 Clear any existing cart checkout items
    unset($_SESSION['checkout_items']);

    // Redirect to checkout page
    header("Location: " . SITE_URL . "pages/checkout.php");
    exit;
}
?>