<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

$perPage = 24;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

/* Query for Newborn only */
$sql  = "SELECT id, name, price, image, category, created_at
         FROM products
         WHERE is_active = 1 AND category = 'newborn'
         ORDER BY created_at DESC
         LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $offset, $perPage);
$stmt->execute();
$products = $stmt->get_result();

/* Count for pagination */
$countSql = "SELECT COUNT(*) AS c FROM products WHERE is_active=1 AND category='newborn'";
$count = $conn->query($countSql)->fetch_assoc()['c'] ?? 0;
$totalPages = max(1, ceil($count / $perPage));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/new.css?v=<?= time(); ?>">
  <title>New Newborn</title>
</head>
<body>

  <div class="new-header">
    <h1 class="new-title">New Newborn</h1>
  </div>

  <div class="product-grid">
    <?php if ($products->num_rows): ?>
      <?php while ($p = $products->fetch_assoc()): ?>
        <a class="product-card" href="<?= SITE_URL ?>pages/product.php?id=<?= (int)$p['id'] ?>">
          <img class="product-thumb" src="<?= SITE_URL ?>uploads/<?= htmlspecialchars($p['image'] ?: 'sample1.jpg') ?>"
               alt="<?= htmlspecialchars($p['name']) ?>">
          <div class="product-info">
            <h3 class="product-name"><?= htmlspecialchars($p['name']) ?></h3>
            <p class="product-price">₱<?= number_format((float)$p['price'], 2) ?></p>
          </div>
        </a>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="grid-column:1/-1;opacity:.7;">No products found.</p>
    <?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="pager">
      <?php for ($i=1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?>
          <span class="current"><?= $i ?></span>
        <?php else: ?>
          <a href="<?= SITE_URL ?>pages/newborn.php?page=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

</body>
</html>
