<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

// Set headers FIRST to prevent any output issues
header('Content-Type: application/json');

// Start output buffering
if (ob_get_level()) ob_end_clean();
ob_start();

$response = ['success' => false, 'message' => 'Unknown error'];

try {
    // Check if user is logged in - EXACTLY LIKE CART-ADD.PHP
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('not_logged_in');
    }

    $user_id = $_SESSION['user_id'];
    $color_id = isset($_POST['color_id']) ? (int)$_POST['color_id'] : 0;
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $size = $_POST['size'] ?? 'M';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;

    error_log("🚀 Buy Now - Received color_id: $color_id, product_id: $product_id, user_id: $user_id");

    // Validate input
    if ($color_id <= 0 || $product_id <= 0) {
        error_log("❌ Invalid color_id: $color_id or product_id: $product_id");
        throw new Exception('Invalid product data');
    }

    // 🟣 UPDATED: Fetch product details via COLOR ID (same as cart-add.php)
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
    
    if (!$stmt) {
        throw new Exception('Database prepare failed');
    }
    
    $stmt->bind_param("i", $color_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        error_log("❌ Color ID $color_id not found in database");
        throw new Exception('Product not found');
    }

    error_log("✅ Found product: " . $product['name'] . " for color_id: $color_id");

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
        'color_id' => $color_id,
        'color_name' => $product['color_name'],
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
        'color_quantity' => $product['color_quantity']
    ];

    // 🟣 Clear any existing cart checkout items
    unset($_SESSION['checkout_items']);

    error_log("✅ Buy Now successful for user $user_id, redirecting to checkout");
    
    $response = [
        'success' => true, 
        'message' => 'Product ready for checkout',
        'redirect_url' => SITE_URL . "pages/checkout.php"
    ];

} catch (Exception $e) {
    $error_msg = $e->getMessage();
    
    // Handle not_logged_in exactly like cart-add.php
    if ($error_msg === 'not_logged_in') {
        $response = [
            'success' => false, 
            'message' => 'not_logged_in',
            'requires_login' => true
        ];
    } else {
        $response = [
            'success' => false, 
            'message' => $error_msg
        ];
    }
    
    error_log("❌ Buy Now Error: " . $error_msg);
}

// Clear output buffer and send JSON
ob_clean();
echo json_encode($response);
ob_end_flush();
?>