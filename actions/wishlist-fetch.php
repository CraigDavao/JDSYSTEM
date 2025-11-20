<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../connection/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo "<p>Please log in to view your wishlist.</p>";
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "
    SELECT 
        w.wishlist_id,
        p.id, 
        p.name, 
        p.price,
        p.sale_price,
        p.actual_sale_price,
        p.image,
        p.image_format,
        w.color_id,
        c.color_name,
        c.color_code
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    LEFT JOIN colors c ON w.color_id = c.color_id
    WHERE w.user_id = ?
    ORDER BY w.added_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "
    <div class='wishlist-empty'>
        <h3>❤️ Your wishlist is empty</h3>
        <p>Browse our products and add items you love!</p>
        <a href='" . SITE_URL . "pages/new.php' class='btn-continue-shopping'>Continue Shopping</a>
    </div>
    ";
    exit;
}

while ($row = $result->fetch_assoc()) {
    // 🎯 PRICE CALCULATION LOGIC BASED ON YOUR DATABASE COLUMNS
    $regularPrice = floatval($row['price']);
    $salePrice = floatval($row['sale_price']);
    $actualSalePrice = floatval($row['actual_sale_price']);
    
    $priceDisplay = '';
    $saleBadge = '';
    $finalPrice = $regularPrice;
    
    // Check if item is on sale
    if ($salePrice > 0 && $actualSalePrice > 0) {
        $finalPrice = $actualSalePrice;
        $discountPercentage = round((($regularPrice - $actualSalePrice) / $regularPrice) * 100);
        
        $priceDisplay = "
            <div class='price-display'>
                <span class='original-price'>₱" . number_format($regularPrice, 2) . "</span>
                <span class='sale-price'>₱" . number_format($actualSalePrice, 2) . "</span>
                <span class='sale-percentage'>" . $discountPercentage . "% OFF</span>
            </div>
        ";
        $saleBadge = "<div class='sale-badge'>-" . $discountPercentage . "%</div>";
    } else {
        $priceDisplay = "
            <div class='price-display'>
                <span class='regular-price'>₱" . number_format($regularPrice, 2) . "</span>
            </div>
        ";
    }
    
    // 🖼️ IMAGE HANDLING
    $imageSrc = '../uploads/sample1.jpg'; // Default fallback
    if (!empty($row['image'])) {
        // If it's a data URL or base64 image
        if (strpos($row['image'], 'data:') === 0) {
            $imageSrc = $row['image'];
        } 
        // If it's stored as base64 in database
        else if (!empty($row['image_format']) && strlen($row['image']) > 100) {
            $imageSrc = 'data:' . $row['image_format'] . ';base64,' . $row['image'];
        }
        // Regular file name
        else {
            $imageSrc = '../uploads/' . $row['image'];
        }
    }
    
    // 🎨 COLOR DISPLAY
    $colorDisplay = '';
    if (!empty($row['color_name'])) {
        $colorDisplay = "
            <p class='item-color'>
                Color: <span style='text-transform: capitalize;'>" . htmlspecialchars($row['color_name']) . "</span>
            </p>
        ";
    }
    
    echo "
    <div class='wishlist-item' data-wishlist-id='{$row['wishlist_id']}'>
        {$saleBadge}
        <button class='remove-wishlist' data-id='{$row['wishlist_id']}' title='Remove from wishlist'>
            ×
        </button>
        <div class='wishlist-image'>
            <img src='{$imageSrc}' alt='" . htmlspecialchars($row['name']) . "' 
                 onerror=\"this.onerror=null; this.src='" . SITE_URL . "uploads/sample1.jpg'\">
        </div>
        <div class='wishlist-details'>
            <h3 class='item-name'>" . htmlspecialchars($row['name']) . "</h3>
            {$colorDisplay}
            {$priceDisplay}
            <div class='wishlist-actions'>
                <button class='btn-add-to-cart' 
                        data-product-id='{$row['id']}' 
                        data-color-id='" . ($row['color_id'] ?? '') . "' 
                        data-price='" . number_format($finalPrice, 2, '.', '') . "'>
                    Add to Cart
                </button>
                <button class='btn-buy-now' 
                        data-product-id='{$row['id']}' 
                        data-color-id='" . ($row['color_id'] ?? '') . "' 
                        data-price='" . number_format($finalPrice, 2, '.', '') . "'>
                    Buy Now
                </button>
            </div>
        </div>
    </div>
    ";
}

$stmt->close();
?>