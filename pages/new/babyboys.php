<?php
require_once __DIR__ . '/../../connection/connection.php';
require_once __DIR__ . '/../../includes/header.php';

$perPage = 24;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Category for this page
$category = 'baby-boys';

$sql = "SELECT id, name, price, sale_price, image, category, created_at
        FROM products
        WHERE is_active = 1 AND category = ?
        ORDER BY created_at DESC
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $category, $offset, $perPage);
$stmt->execute();
$products = $stmt->get_result();

/* Count for pagination */
$countSql = "SELECT COUNT(*) AS c FROM products WHERE is_active = 1 AND category = '$category'";
$count = $conn->query($countSql)->fetch_assoc()['c'] ?? 0;
$totalPages = max(1, ceil($count / $perPage));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= SITE_URL; ?>css/new.css?v=<?= time(); ?>">
  <title>New <?= ucwords(str_replace('-', ' ', $category)) ?></title>
</head>
<body>

  <div class="new-header">
    <h1 class="new-title">New <?= ucwords(str_replace('-', ' ', $category)) ?></h1>
  </div>

  <div class="product-grid">
    <?php if ($products->num_rows): ?>
      <?php while ($p = $products->fetch_assoc()): ?>
        <?php
          $hasSale = isset($p['sale_price']) && $p['sale_price'] > 0 && $p['sale_price'] < $p['price'];
        ?>
        <a class="product-card" href="<?= SITE_URL ?>pages/product.php?id=<?= (int)$p['id'] ?>">
          <img class="product-thumb"
               src="<?= SITE_URL ?>uploads/<?= htmlspecialchars($p['image'] ?: 'sample1.jpg') ?>"
               alt="<?= htmlspecialchars($p['name']) ?>">
          <div class="product-info">
            <h3 class="product-name"><?= htmlspecialchars($p['name']) ?></h3>
            <?php if ($hasSale): ?>
              <p class="product-price">
                <span class="sale-price">₱<?= number_format($p['sale_price'], 2) ?></span>
                <span class="old-price">₱<?= number_format($p['price'], 2) ?></span>
              </p>
            <?php else: ?>
              <p class="product-price">₱<?= number_format($p['price'], 2) ?></p>
            <?php endif; ?>
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
        <?php else: ?>
          <a href="<?= SITE_URL ?>pages/new/<?= $category ?>.php?page=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

</body>
</html>
