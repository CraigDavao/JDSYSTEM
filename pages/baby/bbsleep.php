<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../backend/get-products.php';

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

// 🩷 Fixed filters for this page
$categoryGroup = 'baby';
$gender = 'boys';

// 🟢 Optional subcategory filter
$allowedSub = ['sets', 'tops', 'bottoms', 'sleepwear', 'accessories', 'bodysuits-sleepsuits', 'essentials'];
$subcategory = $_GET['subcategory'] ?? 'sleepwear';
if ($subcategory && !in_array($subcategory, $allowedSub)) {
    $subcategory = 'sleepwear';
}

// 🟢 Pagination setup
$perPage = 24;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// 🟣 Fetch products using reusable function
$data = getProducts([
    'category_group' => $categoryGroup,
    'gender' => $gender,
    'subcategory' => $subcategory,
    'limit' => $perPage,
    'offset' => $offset,
    'orderBy' => 'p.created_at DESC'
]);

$products = $data['products'];
$count = $data['count'];
$totalPages = max(1, ceil($count / $perPage));

// Process products to ensure color_id is available
$processed_products = [];
if ($products && $products->num_rows > 0) {
    while ($product = $products->fetch_assoc()) {
        // If no color_id from the query, get it manually
        if (empty($product['color_id'])) {
            $product['color_id'] = getDefaultColorId($product['id'], $conn);
        }
        $processed_products[] = $product;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= SITE_URL; ?>css/new.css?v=<?= time(); ?>">
  <title>Baby Boys <?= ucfirst(str_replace('-', ' ', $subcategory)) ?></title>
</head>
<body>

  <div class="new-header">
    <h1 class="new-title">Baby Boys <?= ucfirst(str_replace('-', ' ', $subcategory)) ?></h1>
  </div>

  <div class="product-grid">
    <?php if (!empty($processed_products)): ?>
      <?php foreach ($processed_products as $product): ?>
        <?php
          // ✅ FIXED: Use color_id for the product link instead of product id
          $link_id = !empty($product['color_id']) ? $product['color_id'] : $product['id'];
          $product_link = SITE_URL . "pages/product.php?id=" . $link_id;
          
          // ✅ FIXED: Price calculation using helper functions
          $hasSale = isOnSale($product);
          $displayPrice = getDisplayPrice($product);

          // 🩵 Use product_image directly (already Base64 from get-products.php)
          $imageSrc = !empty($product['product_image']) 
              ? htmlspecialchars($product['product_image']) 
              : SITE_URL . 'uploads/sample1.jpg';
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
      <p style="grid-column:1/-1;opacity:.7;">No baby boys <?= htmlspecialchars($subcategory) ?> found.</p>
    <?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="pager">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?>
          <span class="current"><?= $i ?></span>
        <?php else: ?>
          <a href="<?= SITE_URL ?>pages/<?= $gender ?>.php?page=<?= $i ?>&subcategory=<?= urlencode($subcategory) ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

</body>
</html>