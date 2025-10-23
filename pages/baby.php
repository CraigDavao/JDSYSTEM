<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../backend/get-products.php';

// Allowed filters for Baby
$allowedGenders = ['girls','boys','newborn']; 
$allowedSub     = [
    'sets','tops','bottoms','sleepwear','accessories',
    'bodysuits-sleepsuits','essentials'
];

$gender = $_GET['gender'] ?? null;
if ($gender && !in_array($gender, $allowedGenders)) $gender = null;

$sub = $_GET['subcategory'] ?? null;
if ($sub && !in_array($sub, $allowedSub)) $sub = null;

$perPage = 24;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// 🟣 Build filters for get-products function
$filters = [
    'category_group' => 'baby',
    'limit' => $perPage,
    'offset' => $offset,
    'orderBy' => 'p.created_at DESC'
];

if ($gender) {
    $filters['gender'] = $gender;
}
if ($sub) {
    $filters['subcategory'] = $sub;
}

// 🟣 Fetch products using reusable function
$data = getProducts($filters);
$products = $data['products'];
$count = $data['count'];
$totalPages = max(1, ceil($count / $perPage));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= SITE_URL; ?>css/new.css?v=<?= time(); ?>">
  <title>Baby<?= $gender ? ' — ' . ucfirst($gender) : '' ?><?= $sub ? ' — ' . str_replace('-', ' ', ucfirst($sub)) : '' ?></title>
</head>
<body>

  <div class="new-header">
    <h1 class="new-title">
      Baby<?= $gender ? ' — ' . ucfirst($gender) : '' ?><?= $sub ? ' — ' . str_replace('-', ' ', ucfirst($sub)) : '' ?>
    </h1>
  </div>

  <div class="product-grid">
    <?php if ($products->num_rows): ?>
      <?php while ($product = $products->fetch_assoc()): ?>
        <?php
          $product_link = SITE_URL . "pages/product.php?id=" . (int)$product['id'];
          $hasSale = !empty($product['sale_price']) && $product['sale_price'] > 0 && $product['sale_price'] < $product['price'];

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
                <span class="sale-price">₱<?= number_format($product['sale_price'], 2); ?></span>
                <span class="original-price">₱<?= number_format($product['price'], 2); ?></span>
              <?php else: ?>
                <span class="current-price">₱<?= number_format($product['price'], 2); ?></span>
              <?php endif; ?>
            </div>
          </div>
        </a>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="grid-column:1/-1;opacity:.7;">No products found.</p>
    <?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="pager">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?>
          <span class="current"><?= $i ?></span>
        <?php else:
          $qs = '?page=' . $i;
          if ($gender) $qs .= '&gender=' . urlencode($gender);
          if ($sub)    $qs .= '&subcategory=' . urlencode($sub);
        ?>
          <a href="<?= SITE_URL ?>pages/baby.php<?= $qs ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

  <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>