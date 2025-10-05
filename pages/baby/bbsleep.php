<?php
require_once __DIR__ . '/../../connection/connection.php';
require_once __DIR__ . '/../../includes/header.php';

// Fixed filters: Baby → Boys → Sleepwear
$categoryGroup = 'baby';
$gender        = 'boys';
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
  <title>Baby Boys — Sleepwear & Underwear</title>
  <link rel="stylesheet" href="<?= SITE_URL ?>css/new.css?v=<?= time() ?>">
</head>
<body>
  <div class="new-header">
    <h1 class="new-title">Baby Boys Sleepwear & Underwear</h1>
  </div>

  <div class="product-grid">
    <?php if ($result->num_rows): ?>
      <?php while ($p = $result->fetch_assoc()): ?>
        <?php
          $isSale = !empty($p['sale_price']) && $p['sale_price'] > 0 && $p['sale_price'] < $p['price'];
        ?>
        <a class="product-card" href="<?= SITE_URL ?>pages/product.php?id=<?= (int)$p['id'] ?>">
          <img class="product-thumb"
               src="<?= SITE_URL ?>uploads/<?= htmlspecialchars($p['image'] ?: 'sample1.jpg') ?>"
               alt="<?= htmlspecialchars($p['name']) ?>">
          <div class="product-info">
            <h3 class="product-name"><?= htmlspecialchars($p['name']) ?></h3>
            <?php if ($isSale): ?>
              <p class="product-price">
                <span class="sale-price">₱<?= number_format((float)$p['sale_price'], 2) ?></span>
                <span class="original-price">₱<?= number_format((float)$p['price'], 2) ?></span>
              </p>
            <?php else: ?>
              <p class="product-price">₱<?= number_format((float)$p['price'], 2) ?></p>
            <?php endif; ?>
          </div>
        </a>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="grid-column:1/-1; opacity:.7;">No baby boys sleepwear or underwear found.</p>
    <?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="pager">
      <?php for ($i=1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?>
          <span class="current"><?= $i ?></span>
        <?php else: ?>
          <a href="<?= SITE_URL ?>pages/baby/bbsleep.php?page=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

  
</body>
</html>
