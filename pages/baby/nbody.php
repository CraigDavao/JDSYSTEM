<?php
require_once __DIR__ . '/../../connection/connection.php';
require_once __DIR__ . '/../../includes/header.php';

// Fixed filters: Baby → Newborn → Bodysuits & Sleepsuits
$categoryGroup = 'baby';
$gender        = 'newborn';
$subcategory   = 'bodysuits_sleepsuits';

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
$products = $stmt->get_result(); // Updated variable

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
  <title>Newborn — Bodysuits & Sleepsuits</title>
  <link rel="stylesheet" href="<?= SITE_URL ?>css/new.css?v=<?= time() ?>">
  <style>
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
    <h1 class="new-title">Newborn Bodysuits & Sleepsuits</h1>
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
      <p style="grid-column:1/-1; opacity:.7;">No newborn bodysuits & sleepsuits found.</p>
    <?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="pager">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?>
          <span class="current"><?= $i ?></span>
        <?php else: ?>
          <a href="<?= SITE_URL ?>pages/baby/nbody.php?page=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</body>
</html>
