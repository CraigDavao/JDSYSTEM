<?php
require_once __DIR__ . '/../../connection/connection.php';
require_once __DIR__ . '/../../includes/header.php';

// Fixed filters: Kid → Boys → Tops
$categoryGroup = 'kid';
$gender        = 'boys';
$subcategory   = 'tops';

// Pagination setup
$perPage = 24;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Query products
$sql = "SELECT id, name, price, image, created_at
        FROM products
        WHERE category_group=? AND gender=? AND subcategory=?
          AND (is_active IS NULL OR is_active=1)
        ORDER BY created_at DESC
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssii", $categoryGroup, $gender, $subcategory, $offset, $perPage);
$stmt->execute();
$result = $stmt->get_result();

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
  <title>Kid — Boys Tops</title>
  <link rel="stylesheet" href="<?= SITE_URL ?>css/new.css?v=<?= time() ?>">
</head>
<body>
  <div class="new-header">
    <h1 class="new-title">Boys Tops</h1>
  </div>

  <div class="product-grid">
    <?php if ($result->num_rows): while ($p = $result->fetch_assoc()): ?>
      <a class="product-card" href="<?= SITE_URL ?>pages/product.php?id=<?= (int)$p['id'] ?>">
        <img class="product-thumb" src="<?= SITE_URL ?>uploads/<?= htmlspecialchars($p['image'] ?: 'sample1.jpg') ?>"
             alt="<?= htmlspecialchars($p['name']) ?>">
        <div class="product-info">
          <h3 class="product-name"><?= htmlspecialchars($p['name']) ?></h3>
          <p class="product-price">₱<?= number_format((float)$p['price'],2) ?></p>
        </div>
      </a>
    <?php endwhile; else: ?>
      <p style="grid-column:1/-1; opacity:.7;">No boys tops found.</p>
    <?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="pager">
      <?php for ($i=1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?>
          <span class="current"><?= $i ?></span>
        <?php else: ?>
          <a href="<?= SITE_URL ?>pages/topsboy.php?page=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</body>
</html>
