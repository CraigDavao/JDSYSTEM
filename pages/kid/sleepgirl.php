<?php
require_once __DIR__ . '/../../connection/connection.php';
require_once __DIR__ . '/../../includes/header.php';

// Fixed filters: Kid → Girls → Sleepwear
$categoryGroup = 'kid';
$gender        = 'girls';
$subcategory   = 'sleepwear';

// Pagination setup
$perPage = 24;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Query products
$sql = "SELECT id, name, price, sale_price, image, created_at
        FROM products
        WHERE category_group=? AND gender=? AND subcategory=?
          AND (is_active IS NULL OR is_active=1)
        ORDER BY created_at DESC
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssii", $categoryGroup, $gender, $subcategory, $offset, $perPage);
$stmt->execute();
$products = $stmt->get_result(); // fixed variable name

// Count total for pagination
$countSql = "SELECT COUNT(*) as c
             FROM products
             WHERE category_group=? AND gender=? AND subcategory=?
               AND (is_active IS NULL OR is_active=1)";
$countStmt = $conn->prepare($countSql);
$countStmt->bind_param("sss", $categoryGroup, $gender, $subcategory);
$countStmt->execute();
$count = $countStmt->get_result()->fetch_assoc()['c'] ?? 0;
$totalPages = max(1, ceil($count / $perPage));
?>

<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Kid — Girls Sleepwear</title>
  <link rel="stylesheet" href="<?= SITE_URL ?>css/new.css?v=<?= time() ?>">
  <style>
    .product-price { font-weight: 600; }
    .price-original { text-decoration: line-through; color: #111; margin-right: 8px; }
    .price-sale { color: red; font-weight: bold; }
  </style>
</head>
<body>
  <div class="new-header">
    <h1 class="new-title">Girls Sleepwear</h1>
  </div>

  <div class="product-grid">
    <?php if ($products->num_rows): ?>
      <?php while ($product = $products->fetch_assoc()): ?>
        <?php
          $product_link = SITE_URL . "pages/product.php?id=" . (int)$product['id'];
          include __DIR__ . '/../../includes/product-card.php';
        ?>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="grid-column:1/-1; opacity:.7;">No girls sleepwear found.</p>
    <?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="pager">
      <?php for ($i=1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?>
          <span class="current"><?= $i ?></span>
        <?php else: ?>
          <a href="<?= SITE_URL ?>pages/sleepgirl.php?page=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</body>
</html>
