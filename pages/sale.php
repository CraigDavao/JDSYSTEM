<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../backend/get-products.php';

// 🩷 Special filter for sale items
$category = 'sale items';

// 🟢 Pagination setup
$perPage = 24;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// 🟣 Fetch ALL products first, then filter for sale items
$data = getProducts([
    'limit' => 1000, // Get a large number to filter sale items
    'offset' => 0,
    'orderBy' => 'p.created_at DESC'
]);

// 🟡 Filter products to show only sale items
$saleProducts = [];
$totalSaleCount = 0;

if ($data['products']->num_rows > 0) {
    // Reset pointer to beginning
    $data['products']->data_seek(0);
    
    while ($product = $data['products']->fetch_assoc()) {
        $hasSale = !empty($product['sale_price']) && 
                   $product['sale_price'] > 0 && 
                   $product['sale_price'] < $product['price'];
        
        if ($hasSale) {
            $saleProducts[] = $product;
            $totalSaleCount++;
        }
    }
}

// 🟢 Apply pagination to sale products
$paginatedSaleProducts = array_slice($saleProducts, $offset, $perPage);
$totalPages = max(1, ceil($totalSaleCount / $perPage));
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
    <?php if (!empty($paginatedSaleProducts)): ?>
      <?php foreach ($paginatedSaleProducts as $product): ?>
        <?php
          $product_link = SITE_URL . "pages/product.php?id=" . (int)$product['id'];
          $hasSale = !empty($product['sale_price']) && $product['sale_price'] > 0 && $product['sale_price'] < $product['price'];

          // Use actual_sale_price if available, otherwise use sale_price
          $displaySalePrice = !empty($product['actual_sale_price']) ? $product['actual_sale_price'] : $product['sale_price'];

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
                <span class="sale-price">₱<?= number_format($displaySalePrice, 2); ?></span>
                <span class="original-price">₱<?= number_format($product['price'], 2); ?></span>
                <?php 
                  // Calculate and display discount percentage
                  $discountPercent = round((($product['price'] - $displaySalePrice) / $product['price']) * 100);
                  if ($discountPercent > 0): 
                ?>
                  <span class="discount-percent">-<?= $discountPercent ?>%</span>
                <?php endif; ?>
              <?php else: ?>
                <span class="current-price">₱<?= number_format($product['price'], 2); ?></span>
              <?php endif; ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="grid-column:1/-1;opacity:.7;text-align:center;padding:2rem;">
        No sale items found at the moment. Check back later for amazing deals!
      </p>
    <?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="pager">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>">Previous</a>
      <?php endif; ?>
      
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?>
          <span class="current"><?= $i ?></span>
        <?php else: ?>
          <a href="?page=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
      
      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>">Next</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>