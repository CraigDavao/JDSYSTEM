<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

if (!isset($_SESSION['user_id'])) {
    // Store intended action and redirect to login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    $_SESSION['buy_now_data'] = $_POST;
    header("Location: " . SITE_URL . "auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $color_id = intval($_POST['color_id']); // 🟣 CHANGED: Now using color_id
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $size = $_POST['size'] ?? 'M';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;

    if ($color_id <= 0 || $quantity <= 0) { // 🟣 CHANGED: Check color_id instead of product_id
        $_SESSION['error'] = 'Invalid product or quantity.';
        header("Location: " . SITE_URL);
        exit;
    }

    // 🟣 UPDATED: Fetch product details via COLOR ID with proper price calculation
    $stmt = $conn->prepare("
        SELECT 
            pc.id as color_id,
            pc.product_id,
            pc.color_name,
            pc.quantity as color_quantity,
            p.name, 
            p.price, 
            p.sale_price, 
            p.actual_sale_price,
            p.description, 
            p.category, 
            p.category_group, 
            p.gender, 
            p.subcategory,
            pi.image, 
            pi.image_format
        FROM product_colors pc
        INNER JOIN products p ON pc.product_id = p.id
        LEFT JOIN product_images pi ON pc.product_id = pi.product_id AND pi.color_name = pc.color_name
        WHERE pc.id = ? AND (p.is_active IS NULL OR p.is_active = 1)
        LIMIT 1
    ");
    $stmt->bind_param("i", $color_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        $_SESSION['error'] = 'Product color not found.';
        header("Location: " . SITE_URL);
        exit;
    }

    // 🟣 Calculate CORRECT price (actual_sale_price > sale_price > price)
    // Use the price from form if provided, otherwise calculate from database
    if ($price > 0) {
        $displayPrice = $price;
    } else {
        $displayPrice = !empty($product['actual_sale_price']) ? $product['actual_sale_price'] : 
                       (!empty($product['sale_price']) ? $product['sale_price'] : $product['price']);
    }

    // 🟣 Handle blob image properly
    if (!empty($product['image'])) {
        $mimeType = !empty($product['image_format']) ? $product['image_format'] : 'image/jpeg';
        $image_data = 'data:' . $mimeType . ';base64,' . base64_encode($product['image']);
    } else {
        $image_data = SITE_URL . 'uploads/sample1.jpg';
    }

    // 🟣 Set buy now product in session with ALL details including COLOR INFORMATION
    $_SESSION['buy_now_product'] = [
        'product_id' => $product['product_id'],
        'color_id' => $color_id, // 🟣 ADDED: Color ID
        'color_name' => $product['color_name'], // 🟣 ADDED: Color name
        'name' => $product['name'],
        'price' => floatval($displayPrice),
        'quantity' => $quantity,
        'size' => $size,
        'image' => $image_data,
        'subtotal' => floatval($displayPrice) * $quantity,
        'is_buy_now' => true,
        'original_price' => floatval($product['price']),
        'sale_price' => floatval($product['sale_price']),
        'actual_sale_price' => floatval($product['actual_sale_price']),
        'description' => $product['description'],
        'color_quantity' => $product['color_quantity'] // 🟣 ADDED: Available color quantity
    ];

    // 🟣 Clear any existing cart checkout items
    unset($_SESSION['checkout_items']);

    // Redirect to checkout page
    header("Location: " . SITE_URL . "pages/checkout.php");
    exit;
} else {
    // If not POST, redirect to home
    header("Location: " . SITE_URL);
    exit;
}
?>