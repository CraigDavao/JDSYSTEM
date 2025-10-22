<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

$perPage = 24;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$category = 'sale items';

// Fetch sale products
$sql = "SELECT id, name, price, sale_price, sale_start, sale_end, image, created_at
        FROM products
        WHERE is_active = 1
          AND sale_price IS NOT NULL
          AND sale_price > 0
          AND sale_price < price
          AND (sale_start IS NULL OR sale_start <= NOW())
          AND (sale_end IS NULL OR sale_end >= NOW())
        ORDER BY created_at DESC
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $offset, $perPage);
$stmt->execute();
$products = $stmt->get_result();

// Count total sale products
$countSql = "SELECT COUNT(*) AS c
             FROM products
             WHERE is_active = 1
               AND sale_price IS NOT NULL
               AND sale_price > 0
               AND sale_price < price
               AND (sale_start IS NULL OR sale_start <= NOW())
               AND (sale_end IS NULL OR sale_end >= NOW())";
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

  <div class="product-grid left-align">
    <?php if ($products->num_rows): ?>
      <?php while ($product = $products->fetch_assoc()): ?>
        <?php
          $product_link = SITE_URL . "pages/product.php?id=" . (int)$product['id'];
          include __DIR__ . '/../includes/product-card.php';
        ?>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="grid-column:1/-1;opacity:.7;">No sale items found.</p>
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
