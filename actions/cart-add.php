<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

// Set headers FIRST to prevent any output issues
header('Content-Type: application/json');

// Start output buffering
if (ob_get_level()) ob_end_clean();
ob_start();

$response = ['status' => 'error', 'message' => 'Unknown error'];

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('not_logged_in');
    }

    $user_id = $_SESSION['user_id'];
    $color_id = isset($_POST['color_id']) ? (int)$_POST['color_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $size = $_POST['size'] ?? 'Small'; // Changed default to 'Small'
    $price_from_post = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    error_log("🛒 Cart Add - Received user_id: $user_id, product_id: $product_id, color_id: $color_id, size: $size, quantity: $quantity, price_from_post: $price_from_post");

    // Validate input
    if ($color_id <= 0) {
        error_log("❌ Invalid color_id: $color_id");
        throw new Exception('invalid_color');
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
    $stmt->bind_param("i", $color_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        error_log("❌ Color ID $color_id not found in database");
        throw new Exception('color_not_found');
    }

    // Use product_id from database if not provided in POST
    if ($product_id <= 0) {
        $product_id = $product['product_id'];
    }

    error_log("✅ Found product: " . $product['name'] . " for color_id: $color_id, product_id: $product_id");

    // 🟣 CRITICAL FIX: USE PRICE FROM POST IF PROVIDED, OTHERWISE CALCULATE FROM DB
    if ($price_from_post > 0) {
        // Use price sent from frontend (wishlist items)
        $displayPrice = $price_from_post;
        error_log("💰 Using price from POST: $displayPrice");
    } else {
        // Calculate price from database (product page items)
        $displayPrice = !empty($product['actual_sale_price']) ? $product['actual_sale_price'] : 
                       (!empty($product['sale_price']) ? $product['sale_price'] : $product['price']);
        
        $displayPrice = floatval($displayPrice);
        
        if ($displayPrice <= 0) {
            error_log("❌ Invalid price calculated: $displayPrice - Using product price instead");
            $displayPrice = floatval($product['price']);
        }
        error_log("💰 Using calculated price from DB: $displayPrice");
    }

    // 🟣 CRITICAL FIX: Check if product already in cart (with same product_id, color_id and size)
    $checkCart = $conn->prepare("
        SELECT id, quantity 
        FROM cart 
        WHERE user_id = ? AND product_id = ? AND color_id = ? AND size = ?
    ");
    
    if (!$checkCart) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $checkCart->bind_param("iiis", $user_id, $product_id, $color_id, $size);
    
    if (!$checkCart->execute()) {
        throw new Exception('Database execute failed: ' . $checkCart->error);
    }
    
    $cart_result = $checkCart->get_result();
    
    if ($cart_result->num_rows > 0) {
        // 🟣 FIX: ITEM ALREADY EXISTS - Don't update quantity, just return 'exists' status
        $row = $cart_result->fetch_assoc();
        
        $response = [
            'status' => 'exists', 
            'message' => 'This item is already in your cart!',
            'current_quantity' => $row['quantity']
        ];
        error_log("✅ Item already in cart - Product ID: $product_id, Color ID: $color_id, Size: $size, Current Quantity: {$row['quantity']}");
        
    } else {
        // Insert new item with color information - ALWAYS USE THE QUANTITY FROM REQUEST
        $insert_stmt = $conn->prepare("
            INSERT INTO cart (user_id, product_id, color_id, color_name, quantity, size, price, added_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if (!$insert_stmt) {
            throw new Exception('Database prepare failed: ' . $conn->error);
        }
        
        // 🟣 FIX: Include color_name instead of NULL
        $color_name = $product['color_name'] ?? NULL;
        
        $insert_stmt->bind_param("iiisisd", 
            $user_id, 
            $product_id, 
            $color_id, 
            $color_name,
            $quantity, // Use the quantity from the request (should be 1 for wishlist)
            $size,
            $displayPrice
        );
        
        if ($insert_stmt->execute()) {
            $response = [
                'status' => 'success', 
                'message' => 'Product added to cart!',
                'cart_id' => $conn->insert_id
            ];
            error_log("✅ NEW Cart item added - Product ID: $product_id, Color ID: $color_id, Size: $size, Quantity: $quantity, Price: $displayPrice");
        } else {
            throw new Exception('Failed to add to cart: ' . $insert_stmt->error);
        }
        $insert_stmt->close();
    }
    
    $checkCart->close();

} catch (Exception $e) {
    $error_msg = $e->getMessage();
    
    // Map error codes to user-friendly messages
    if ($error_msg === 'not_logged_in') {
        $response = ['status' => 'not_logged_in', 'message' => 'Please log in first.'];
    } elseif ($error_msg === 'invalid_color') {
        $response = ['status' => 'error', 'message' => 'Please select a color.'];
    } elseif ($error_msg === 'color_not_found') {
        $response = ['status' => 'error', 'message' => 'Product color not found.'];
    } else {
        $response = ['status' => 'error', 'message' => $error_msg];
    }
    
    error_log("❌ Cart Add Error: " . $error_msg);
}

// Clear output buffer and send JSON
ob_clean();
echo json_encode($response);
ob_end_flush();
?>