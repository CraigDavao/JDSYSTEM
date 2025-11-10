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
    
    // If no default color, get the first available color
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

$perPage = 24;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$category = 'new arrivals';

// 🟢 Fetch products with their first image (BLOB) AND color_id
$sql = "
    SELECT 
        p.id,
        p.name,
        p.price,
        p.sale_price,
        p.actual_sale_price,
        p.created_at,
        pi.image,
        pi.image_format,
        pc.id as color_id
    FROM products AS p
    LEFT JOIN product_images AS pi 
        ON pi.product_id = p.id 
        AND pi.id = (
            SELECT MIN(pi2.id)
            FROM product_images AS pi2
            WHERE pi2.product_id = p.id
        )
    LEFT JOIN product_colors pc ON p.id = pc.product_id AND pc.is_default = 1
    WHERE (p.is_active IS NULL OR p.is_active = 1)
    ORDER BY p.created_at DESC
    LIMIT ?, ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $offset, $perPage);
$stmt->execute();
$products = $stmt->get_result();

// Process products to ensure color_id is available
$processed_products = [];
if ($products->num_rows > 0) {
    while ($product = $products->fetch_assoc()) {
        // If no color_id from join, get it manually
        if (empty($product['color_id'])) {
            $product['color_id'] = getDefaultColorId($product['id'], $conn);
        }
        $processed_products[] = $product;
    }
}

// 🟢 Count total products for pagination
$countSql = "SELECT COUNT(*) AS c FROM products WHERE (is_active IS NULL OR is_active = 1)";
$countStmt = $conn->prepare($countSql);
$countStmt->execute();
$countResult = $countStmt->get_result();
$count = $countResult->fetch_assoc()['c'] ?? 0;
$totalPages = max(1, ceil($count / $perPage));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= SITE_URL; ?>css/new.css?v=<?= time(); ?>">
  <title><?= ucwords($category) ?></title>
</head>
<body>

<div class="new-header">
  <h1 class="new-title"><?= ucwords($category) ?></h1>
</div>

<div class="product-grid">
  <?php if (!empty($processed_products)): ?>
    <?php foreach ($processed_products as $product): ?>
      <?php
        // ✅ FIXED: Use color_id for the product link instead of product id
        $link_id = !empty($product['color_id']) ? $product['color_id'] : $product['id'];
        $product_link = SITE_URL . "pages/product.php?id=" . $link_id;
        
        // ✅ FIXED: Price calculation
        $hasSale = isOnSale($product);
        $displayPrice = getDisplayPrice($product);

        // 🩵 Convert BLOB image to base64
        if (!empty($product['image'])) {
            $mimeType = !empty($product['image_format']) ? $product['image_format'] : 'image/jpeg';
            $imageSrc = 'data:' . $mimeType . ';base64,' . base64_encode($product['image']);
        } else {
            $imageSrc = SITE_URL . 'uploads/sample1.jpg';
        }
      ?>
      <a href="<?= htmlspecialchars($product_link) ?>" class="product-card">
        <div class="product-image-container">
          <img src="<?= $imageSrc ?>" 
               alt="<?= htmlspecialchars($product['name']); ?>" 
               class="product-thumb"
               onerror="this.src='<?= SITE_URL; ?>uploads/sample1.jpg'">

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
    <p style="grid-column:1/-1;opacity:.7;">No new products found.</p>
  <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
  <div class="pager">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <?php if ($i === $page): ?>
        <span class="current"><?= $i ?></span>
      <?php else: ?>
        <a href="?page=<?= $i ?>"><?= $i ?></a>
      <?php endif; ?>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>