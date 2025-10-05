<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

// Pagination setup
$perPage = 24;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Fetch products that are on sale (sale_price > 0 and less than price)
$sql = "SELECT id, name, price, sale_price, image, created_at
        FROM products
        WHERE is_active = 1
          AND sale_price IS NOT NULL
          AND sale_price > 0
          AND sale_price < price
        ORDER BY created_at DESC
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $offset, $perPage);
$stmt->execute();
$result = $stmt->get_result();

// Count total sale products for pagination
$countSql = "SELECT COUNT(*) AS c
             FROM products
             WHERE is_active = 1
               AND sale_price IS NOT NULL
               AND sale_price > 0
               AND sale_price < price";
$count = $conn->query($countSql)->fetch_assoc()['c'] ?? 0;
$totalPages = max(1, ceil($count / $perPage));
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Sale Items</title>
<link rel="stylesheet" href="<?= SITE_URL ?>css/new.css?v=<?= time() ?>">
<style>
  /* Match style with other product pages */
  .old-price {
    text-decoration: line-through;
    color: #888;
    margin-right: 6px;
  }
  .sale-price {
    color: #e63946;
    font-weight: 600;
  }
</style>
</head>
<body>

  <div class="new-header">
    <h1 class="new-title">Sale Items</h1>
  </div>

  <div class="product-grid">
    <?php if ($result->num_rows): while ($p = $result->fetch_assoc()): ?>
      <a class="product-card" href="<?= SITE_URL ?>pages/product.php?id=<?= (int)$p['id'] ?>">
        <img class="product-thumb"
             src="<?= SITE_URL ?>uploads/<?= htmlspecialchars($p['image'] ?: 'sample1.jpg') ?>"
             alt="<?= htmlspecialchars($p['name']) ?>">
        <div class="product-info">
          <h3 class="product-name"><?= htmlspecialchars($p['name']) ?></h3>
          <p class="product-price">
            <span class="old-price">₱<?= number_format((float)$p['price'], 2) ?></span>
            <span class="sale-price">₱<?= number_format((float)$p['sale_price'], 2) ?></span>
          </p>
        </div>
      </a>
    <?php endwhile; else: ?>
      <p style="grid-column:1/-1; opacity:.7;">No sale items found.</p>
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
