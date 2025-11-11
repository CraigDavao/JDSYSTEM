<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'not_logged_in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch cart items with product information - FIXED QUERY
    $sql = "
        SELECT 
            c.id as cart_id,
            c.product_id,
            c.color_id,
            c.color_name,
            c.quantity,
            c.size,
            c.price,
            c.added_at,
            p.name,
            p.description,
            pc.color_name as actual_color_name,
            COALESCE(
                (SELECT CONCAT('data:image/', 
                              COALESCE(pi.image_format, 'jpeg'), 
                              ';base64,', 
                              TO_BASE64(pi.image))
                 FROM product_images pi
                 WHERE pi.product_id = p.id
                   AND pi.color_name = c.color_name
                 ORDER BY pi.sort_order ASC, pi.id ASC
                 LIMIT 1),
                (SELECT CONCAT('data:image/', 
                              COALESCE(pi2.image_format, 'jpeg'), 
                              ';base64,', 
                              TO_BASE64(pi2.image))
                 FROM product_images pi2
                 WHERE pi2.product_id = p.id
                   AND pi2.color_name IS NULL
                 ORDER BY pi2.sort_order ASC, pi2.id ASC
                 LIMIT 1),
                (SELECT CONCAT('data:image/', 
                              COALESCE(pi3.image_format, 'jpeg'), 
                              ';base64,', 
                              TO_BASE64(pi3.image))
                 FROM product_images pi3
                 WHERE pi3.product_id = p.id
                 ORDER BY pi3.sort_order ASC, pi3.id ASC
                 LIMIT 1),
                NULL
            ) AS image,
            COALESCE(
                (SELECT pi.image_format
                 FROM product_images pi
                 WHERE pi.product_id = p.id
                   AND pi.color_name = c.color_name
                 ORDER BY pi.sort_order ASC, pi.id ASC
                 LIMIT 1),
                (SELECT pi2.image_format
                 FROM product_images pi2
                 WHERE pi2.product_id = p.id
                   AND pi2.color_name IS NULL
                 ORDER BY pi2.sort_order ASC, pi2.id ASC
                 LIMIT 1),
                (SELECT pi3.image_format
                 FROM product_images pi3
                 WHERE pi3.product_id = p.id
                 ORDER BY pi3.sort_order ASC, pi3.id ASC
                 LIMIT 1),
                'jpeg'
            ) AS image_format,
            (c.price * c.quantity) as subtotal
        FROM cart c
        LEFT JOIN products p ON c.product_id = p.id
        LEFT JOIN product_colors pc ON c.color_id = pc.id
        WHERE c.user_id = ?
        ORDER BY c.added_at DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cart = [];
    while ($row = $result->fetch_assoc()) {
        // Use actual color name from product_colors table if available
        if (!empty($row['actual_color_name'])) {
            $row['color_name'] = $row['actual_color_name'];
        }
        
        // Handle image data - NO NEED TO CONVERT, MySQL TO_BASE64 already did it
        // The image field now contains the complete data URL
        if (empty($row['image'])) {
            $row['image'] = null;
        }
        
        $cart[] = $row;
    }
    
    echo json_encode([
        'status' => 'success',
        'cart' => $cart
    ]);
    
} catch (Exception $e) {
    error_log("Cart Fetch Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch cart: ' . $e->getMessage()
    ]);
}
?>