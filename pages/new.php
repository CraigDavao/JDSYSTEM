<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

$perPage = 24;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$category = 'new arrivals';

// 🟢 Fetch products with their first image (BLOB)
$sql = "
    SELECT 
        p.id,
        p.name,
        p.price,
        p.sale_price,
        p.actual_sale_price,
        p.created_at,
        pi.image,
        pi.image_format
    FROM products AS p
    LEFT JOIN product_images AS pi 
        ON pi.product_id = p.id 
        AND pi.id = (
            SELECT MIN(pi2.id)
            FROM product_images AS pi2
            WHERE pi2.product_id = p.id
        )
    WHERE (p.is_active IS NULL OR p.is_active = 1)
    ORDER BY p.created_at DESC
    LIMIT ?, ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $offset, $perPage);
$stmt->execute();
$products = $stmt->get_result();

// 🟢 Count total products for pagination
$countSql = "SELECT COUNT(*) AS c FROM products WHERE (is_active IS NULL OR is_active = 1)";
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

<div class="product-grid">
  <?php if ($products->num_rows): ?>
    <?php while ($product = $products->fetch_assoc()): ?>
      <?php
        $product_link = SITE_URL . "pages/product.php?id=" . (int)$product['id'];
        $hasSale = !empty($product['sale_price']) && $product['sale_price'] > 0 && $product['sale_price'] < $product['price'];

        // 🩵 Convert BLOB image to base64
        if (!empty($product['image'])) {
            $mimeType = !empty($product['image_format']) ? $product['image_format'] : 'image/jpeg';
            $imageSrc = 'data:' . $mimeType . ';base64,' . base64_encode($product['image']);
        } else {
            $imageSrc = SITE_URL . 'uploads/sample1.jpg';
        }
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
    <p style="grid-column:1/-1;opacity:.7;">No new products found.</p>
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
