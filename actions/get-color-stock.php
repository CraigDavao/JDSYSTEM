<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

header('Content-Type: application/json');

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$color_id = isset($_GET['color_id']) ? (int)$_GET['color_id'] : 0;

error_log("🔄 get-color-stock.php called with color_id: " . $color_id);

if ($color_id <= 0) {
    error_log("❌ Invalid color ID: " . $color_id);
    echo json_encode(['status' => 'error', 'message' => 'Invalid color ID']);
    exit;
}

try {
    // Get color stock information
    $color_sql = "SELECT 
                    pc.id as color_id,
                    pc.product_id,
                    pc.color_name,
                    pc.quantity as color_quantity
                FROM product_colors pc
                WHERE pc.id = ?";
    
    $color_stmt = $conn->prepare($color_sql);
    if (!$color_stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $color_stmt->bind_param("i", $color_id);
    
    if (!$color_stmt->execute()) {
        throw new Exception('Execute failed: ' . $color_stmt->error);
    }
    
    $color_result = $color_stmt->get_result();
    $color_data = $color_result->fetch_assoc();
    
    if (!$color_data) {
        throw new Exception('Color not found for ID: ' . $color_id);
    }
    
    $current_stock = $color_data['color_quantity'] ?? 0;
    error_log("✅ Found color: " . $color_data['color_name'] . " with stock: " . $current_stock);
    
    // Get stock by size
    $stock_by_size = [];
    $sizes = ['S', 'M', 'L', 'XL'];
    
    // Check if product_sizes table exists
    $check_table_sql = "SHOW TABLES LIKE 'product_sizes'";
    $table_exists = $conn->query($check_table_sql)->num_rows > 0;
    
    if ($table_exists) {
        error_log("📊 product_sizes table exists, fetching size-specific stock");
        $size_stock_sql = "SELECT size, quantity FROM product_sizes WHERE color_id = ?";
        $size_stmt = $conn->prepare($size_stock_sql);
        if ($size_stmt) {
            $size_stmt->bind_param("i", $color_id);
            $size_stmt->execute();
            $size_stock_result = $size_stmt->get_result();
            while ($row = $size_stock_result->fetch_assoc()) {
                $stock_by_size[$row['size']] = $row['quantity'];
                error_log("📏 Size " . $row['size'] . ": " . $row['quantity'] . " available");
            }
            $size_stmt->close();
        }
    } else {
        error_log("ℹ️ product_sizes table not found, using color quantity for all sizes");
    }
    
    // If no size-specific data, use color quantity for all sizes
    if (empty($stock_by_size)) {
        foreach ($sizes as $size) {
            $stock_by_size[$size] = $current_stock;
        }
        error_log("🔄 Using uniform stock for all sizes: " . $current_stock);
    }
    
    $response = [
        'status' => 'success',
        'stock_info' => [
            'current_stock' => $current_stock,
            'stock_by_size' => $stock_by_size,
            'sizes' => $sizes
        ]
    ];
    
    error_log("✅ Sending successful response: " . json_encode($response));
    echo json_encode($response);
    
} catch (Exception $e) {
    $error_message = 'Database error: ' . $e->getMessage();
    error_log("❌ " . $error_message);
    echo json_encode([
        'status' => 'error',
        'message' => $error_message
    ]);
}

$color_stmt->close();
?>