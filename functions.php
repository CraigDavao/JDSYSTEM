<?php
require_once __DIR__ . '/connection/connection.php';

function getCategories() {
    global $conn;
    $sql = "SELECT * FROM categories ORDER BY name ASC";
    $result = $conn->query($sql);

    $categories = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    return $categories;
}

/**
 * Convert color names to hex codes
 */
function getColorCode($colorName) {
    $colors = [
        'red' => '#ff0000',
        'blue' => '#0000ff',
        'green' => '#008000',
        'black' => '#000000',
        'white' => '#ffffff',
        'yellow' => '#ffff00',
        'pink' => '#ffc0cb',
        'purple' => '#800080',
        'orange' => '#ffa500',
        'gray' => '#808080',
        'grey' => '#808080',
        'brown' => '#a52a2a',
        'navy' => '#000080',
        'maroon' => '#800000',
        'teal' => '#008080',
        'olive' => '#808000',
        'silver' => '#c0c0c0',
        'gold' => '#ffd700',
        'beige' => '#f5f5dc',
        'burgundy' => '#800020',
        'charcoal' => '#36454f',
        'cream' => '#fffdd0',
        'khaki' => '#f0e68c'
    ];
    
    $colorName = strtolower(trim($colorName));
    return $colors[$colorName] ?? '#cccccc';
}

/**
 * Get product colors with images - FIXED VERSION
 * Based on your table structure: product_colors has colors, product_images has images linked to product_id
 */
function getProductColorsWithImages($product_id, $conn) {
    // First, get all colors for this product
    $sql = "SELECT pc.* FROM product_colors pc WHERE pc.product_id = ? ORDER BY pc.is_default DESC, pc.created_at ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $colors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Now, for each color, try to find a matching image
    foreach ($colors as &$color) {
        // Try to find an image for this specific color
        $image_sql = "SELECT pi.image, pi.image_format FROM product_images pi 
                     WHERE pi.product_id = ? AND pi.color_name = ? 
                     LIMIT 1";
        $image_stmt = $conn->prepare($image_sql);
        $image_stmt->bind_param("is", $product_id, $color['color_name']);
        $image_stmt->execute();
        $image_result = $image_stmt->get_result();
        $image_data = $image_result->fetch_assoc();
        
        if ($image_data) {
            $color['image'] = $image_data['image'];
            $color['image_format'] = $image_data['image_format'];
        } else {
            // If no specific color image, try to get any image for this product
            $fallback_sql = "SELECT pi.image, pi.image_format FROM product_images pi 
                           WHERE pi.product_id = ? LIMIT 1";
            $fallback_stmt = $conn->prepare($fallback_sql);
            $fallback_stmt->bind_param("i", $product_id);
            $fallback_stmt->execute();
            $fallback_result = $fallback_stmt->get_result();
            $fallback_data = $fallback_result->fetch_assoc();
            
            if ($fallback_data) {
                $color['image'] = $fallback_data['image'];
                $color['image_format'] = $fallback_data['image_format'];
            } else {
                $color['image'] = null;
                $color['image_format'] = null;
            }
        }
    }
    
    return $colors;
}

/**
 * Get default product color - FIXED VERSION
 */
function getDefaultProductColor($product_id, $conn) {
    // First get the default color
    $sql = "SELECT pc.* FROM product_colors pc WHERE pc.product_id = ? AND pc.is_default = 1 LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $color = $stmt->get_result()->fetch_assoc();
    
    if (!$color) {
        // If no default, get the first color
        $sql = "SELECT pc.* FROM product_colors pc WHERE pc.product_id = ? ORDER BY created_at ASC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $color = $stmt->get_result()->fetch_assoc();
    }
    
    if ($color) {
        // Try to find an image for this color
        $image_sql = "SELECT pi.image, pi.image_format FROM product_images pi 
                     WHERE pi.product_id = ? AND pi.color_name = ? 
                     LIMIT 1";
        $image_stmt = $conn->prepare($image_sql);
        $image_stmt->bind_param("is", $product_id, $color['color_name']);
        $image_stmt->execute();
        $image_result = $image_stmt->get_result();
        $image_data = $image_result->fetch_assoc();
        
        if ($image_data) {
            $color['image'] = $image_data['image'];
            $color['image_format'] = $image_data['image_format'];
        } else {
            // Fallback to any product image
            $fallback_sql = "SELECT pi.image, pi.image_format FROM product_images pi 
                           WHERE pi.product_id = ? LIMIT 1";
            $fallback_stmt = $conn->prepare($fallback_sql);
            $fallback_stmt->bind_param("i", $product_id);
            $fallback_stmt->execute();
            $fallback_result = $fallback_stmt->get_result();
            $fallback_data = $fallback_result->fetch_assoc();
            
            if ($fallback_data) {
                $color['image'] = $fallback_data['image'];
                $color['image_format'] = $fallback_data['image_format'];
            }
        }
    }
    
    return $color;
}
?>