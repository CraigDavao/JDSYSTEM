<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

/* ------------------------------------------------------------
   HELPER FUNCTION: Get default color ID for a product
------------------------------------------------------------ */
function getDefaultColorId($product_id, $conn) {
    $sql = "SELECT id FROM product_colors WHERE product_id = ? AND is_default = 1 LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    }
    
    $sql = "SELECT id FROM product_colors WHERE product_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    }
    
    return null;
}

/* ------------------------------------------------------------
   HELPER: Sale condition and price calculation - FIXED
------------------------------------------------------------ */
function isOnSale($product) {
    // Check if we have an actual sale price that's different from regular price
    if (!empty($product['actual_sale_price']) && $product['actual_sale_price'] > 0 && $product['actual_sale_price'] < $product['price']) {
        return true;
    }
    // Fallback: if only sale_price (percentage) is available
    else if (!empty($product['sale_price']) && $product['sale_price'] > 0 && $product['sale_price'] < 100) {
        return true;
    }
    return false;
}

function getDisplayPrice($product) {
    // Check if we have an actual sale price that's different from regular price
    if (!empty($product['actual_sale_price']) && $product['actual_sale_price'] > 0 && $product['actual_sale_price'] < $product['price']) {
        return $product['actual_sale_price'];
    }
    // Fallback: if only sale_price (percentage) is available, calculate it
    else if (!empty($product['sale_price']) && $product['sale_price'] > 0 && $product['sale_price'] < 100) {
        $discountAmount = $product['price'] * ($product['sale_price'] / 100);
        return $product['price'] - $discountAmount;
    }
    // No sale
    return $product['price'];
}

$query = $_GET['query'] ?? '';
$perPage = 24;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

if (!empty($query)) {
    $search_term = "%$query%";
    
    // Fetch products with color_id
    $sql = "
        SELECT 
            p.*,
            pi.image,
            pi.image_format,
            pc.id as color_id
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id
        LEFT JOIN product_colors pc ON p.id = pc.product_id AND pc.is_default = 1
        WHERE p.name LIKE ? AND p.is_active = 1
        GROUP BY p.id
        ORDER BY p.created_at DESC
        LIMIT ?, ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $search_term, $offset, $perPage);
    $stmt->execute();
    $products = $stmt->get_result();

    // Process products to ensure color_id is available
    $processed_products = [];
    if ($products->num_rows > 0) {
        while ($product = $products->fetch_assoc()) {
            if (empty($product['color_id'])) {
                $product['color_id'] = getDefaultColorId($product['id'], $conn);
            }
            $processed_products[] = $product;
        }
    }

    // Count total products
    $countSql = "SELECT COUNT(*) AS c FROM products WHERE name LIKE ? AND is_active = 1";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param("s", $search_term);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $count = $countResult->fetch_assoc()['c'] ?? 0;
    $totalPages = max(1, ceil($count / $perPage));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= SITE_URL; ?>css/search.css?v=<?= time(); ?>">
    <title>Search Results for "<?= htmlspecialchars($query) ?>"</title>
</head>
<body>

<div class="search-header">
    <h1>Search Results for "<?= htmlspecialchars($query) ?>"</h1>
    <?php if (isset($processed_products)): ?>
        <p class="results-count"><?= $count ?> results found</p>
    <?php endif; ?>
</div>

<div class="product-grid">
    <?php if (!empty($processed_products)): ?>
        <?php foreach ($processed_products as $product): 
            $img = !empty($product['image']) 
                ? 'data:' . ($product['image_format'] ?? 'image/jpeg') . ';base64,' . base64_encode($product['image'])
                : SITE_URL . 'uploads/sample1.jpg';
            
            // ✅ FIXED: Use color_id for the product link instead of product id
            $link_id = !empty($product['color_id']) ? $product['color_id'] : $product['id'];
            
            // ✅ FIXED: Price calculation
            $hasSale = isOnSale($product);
            $displayPrice = getDisplayPrice($product);
        ?>
            <a href="<?= SITE_URL ?>pages/product.php?id=<?= $link_id ?>" class="product-card">
                <div class="product-image-container">
                    <img src="<?= $img ?>" 
                         alt="<?= htmlspecialchars($product['name']); ?>"
                         class="product-thumb"
                         onerror="this.src='<?= SITE_URL ?>uploads/sample1.jpg'">
                    <?php if ($hasSale): ?>
                        <div class="sale-badge">Sale</div>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <h3 class="product-name"><?= htmlspecialchars($product['name']); ?></h3>
                    <div class="product-price">
                        <?php if ($hasSale): ?>
                            <span class="sale-price">₱<?= number_format($displayPrice, 2); ?></span>
                            <span class="original-price">₱<?= number_format($product['price'], 2); ?></span>
                        <?php else: ?>
                            <span class="current-price">₱<?= number_format($displayPrice, 2); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="no-results">No products found for "<?= htmlspecialchars($query) ?>".</p>
    <?php endif; ?>
</div>

<?php if (isset($totalPages) && $totalPages > 1): ?>
    <div class="pager">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i === $page): ?>
                <span class="current"><?= $i ?></span>
            <?php else: ?>
                <a href="?query=<?= urlencode($query) ?>&page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>