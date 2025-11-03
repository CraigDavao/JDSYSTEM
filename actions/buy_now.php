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
    // ✅ Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('not_logged_in');
    }

    $user_id = $_SESSION['user_id'];

    // 🛑 Check if user is blocked or restricted
    $checkUser = $conn->prepare("SELECT is_blocked, is_restricted FROM users WHERE id = ?");
    $checkUser->bind_param("i", $user_id);
    $checkUser->execute();
    $userStatus = $checkUser->get_result()->fetch_assoc();

    if ($userStatus) {
        if ($userStatus['is_blocked'] == 1) {
            throw new Exception('blocked');
        } elseif ($userStatus['is_restricted'] == 1) {
            throw new Exception('restricted');
        }
    }

    // ✅ Proceed with normal Buy Now logic
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

    // 🟣 Fetch product details via COLOR ID
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
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $color_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Database execute failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        error_log("❌ Color ID $color_id not found in database");
        throw new Exception('Product color not found');
    }

    error_log("✅ Found product: " . $product['name'] . " for color_id: $color_id, color_name: " . $product['color_name']);

    // 🟣 Calculate CORRECT price
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

    // 🟣 COMPLETELY CLEAR any previous buy now data
    unset($_SESSION['buy_now_product']);
    unset($_SESSION['checkout_items']);

    // 🟣 Set NEW buy now product in session
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

    error_log("✅ Buy Now session updated for user $user_id:");
    error_log("   - Product: " . $product['name']);
    error_log("   - Color: " . $product['color_name'] . " (ID: $color_id)");
    error_log("   - Size: $size");
    error_log("   - Quantity: $quantity");
    error_log("   - Price: $displayPrice");
    
    $response = [
        'success' => true, 
        'message' => 'Product ready for checkout',
        'redirect_url' => SITE_URL . "pages/checkout.php",
        'debug_info' => [
            'color_name' => $product['color_name'],
            'color_id' => $color_id,
            'product_name' => $product['name']
        ]
    ];

} catch (Exception $e) {
    $error_msg = $e->getMessage();
    
    if ($error_msg === 'not_logged_in') {
        $response = [
            'success' => false, 
            'message' => 'not_logged_in',
            'requires_login' => true
        ];
    } elseif ($error_msg === 'restricted') {
        $response = [
            'success' => false,
            'message' => 'Your account is restricted and cannot make purchases.'
        ];
    } elseif ($error_msg === 'blocked') {
        $response = [
            'success' => false,
            'message' => 'Your account has been blocked. Please contact support.'
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