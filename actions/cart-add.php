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
    $size = $_POST['size'] ?? 'M';

    error_log("🛒 Cart Add - Received color_id: $color_id, user_id: $user_id, size: $size, quantity: $quantity");

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

    error_log("✅ Found product: " . $product['name'] . " for color_id: $color_id");

    // 🟣 Calculate CORRECT price (actual_sale_price > sale_price > price)
    $displayPrice = !empty($product['actual_sale_price']) ? $product['actual_sale_price'] : 
                   (!empty($product['sale_price']) ? $product['sale_price'] : $product['price']);

    // 🟣 CRITICAL FIX: Ensure price is properly formatted and validated
    $displayPrice = floatval($displayPrice);
    
    if ($displayPrice <= 0) {
        error_log("❌ Invalid price calculated: $displayPrice - Using product price instead");
        $displayPrice = floatval($product['price']);
    }
    
    error_log("💰 Final price to store: " . $displayPrice);

    // Check if product already in cart (with same color_id and size)
    $checkCart = $conn->prepare("
        SELECT id, quantity 
        FROM cart 
        WHERE user_id = ? AND color_id = ? AND size = ?
    ");
    
    if (!$checkCart) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $checkCart->bind_param("iis", $user_id, $color_id, $size);
    
    if (!$checkCart->execute()) {
        throw new Exception('Database execute failed: ' . $checkCart->error);
    }
    
    $cart_result = $checkCart->get_result();
    
    if ($cart_result->num_rows > 0) {
        // Update existing item
        $row = $cart_result->fetch_assoc();
        $newQuantity = $row['quantity'] + $quantity;
        
        $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        
        if (!$update) {
            throw new Exception('Database prepare failed: ' . $conn->error);
        }
        
        $update->bind_param("ii", $newQuantity, $row['id']);
        
        if ($update->execute()) {
            $response = ['status' => 'success', 'message' => 'Product quantity updated in cart'];
            error_log("✅ Cart updated - Color ID: $color_id, Size: $size, New Quantity: $newQuantity");
        } else {
            throw new Exception('Failed to update cart: ' . $update->error);
        }
        $update->close();
    } else {
        // Insert new item with color information
        $insert_stmt = $conn->prepare("
            INSERT INTO cart (user_id, product_id, color_id, color_name, quantity, size, price, added_at) 
            VALUES (?, ?, ?, NULL, ?, ?, ?, NOW())
        ");
        
        if (!$insert_stmt) {
            throw new Exception('Database prepare failed: ' . $conn->error);
        }
        
        // 🟣 FOCUS ON COLOR_ID: Store color_id and product_id from the color selection
        $insert_stmt->bind_param("iiisid", 
            $user_id, 
            $product['product_id'], 
            $color_id, 
            $quantity, 
            $size,
            $displayPrice
        );
        
        if ($insert_stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Product added to cart!'];
            error_log("✅ Cart item added - Color ID: $color_id, Product: " . $product['name'] . ", Size: $size, Quantity: $quantity, Price: $displayPrice");
        } else {
            throw new Exception('Failed to add to cart: ' . $insert_stmt->error);
        }
        $insert_stmt->close();
    }
    
    $checkCart->close();

} catch (Exception $e) {
    $error_msg = $e->getMessage();
    $response = ['status' => 'error', 'message' => $error_msg];
    error_log("❌ Cart Add Error: " . $error_msg);
}


// Clear output buffer and send JSON
ob_clean();
echo json_encode($response);
ob_end_flush();
?>