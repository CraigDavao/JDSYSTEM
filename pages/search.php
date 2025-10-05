<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

$search = trim($_GET['query'] ?? '');
$products = null;

if ($search !== '') {
    $like = "%" . $search . "%";
    $sql = "SELECT id, name, price, sale_price, sale_start, sale_end, image, category, created_at
            FROM products
            WHERE is_active = 1 AND name LIKE ?
            ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $products = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Search results for "<?= htmlspecialchars($search) ?>"</title>
  <link rel="stylesheet" href="<?= SITE_URL; ?>css/search.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

  <div class="new-header">
    <h1 class="new-title">Search results for "<?= htmlspecialchars($search) ?>"</h1>
  </div>

  <div class="product-grid">
    <?php if ($products && $products->num_rows): ?>
      <?php while ($product = $products->fetch_assoc()): ?>
        <?php
          $product_link = SITE_URL . "pages/product.php?id=" . (int)$product['id'];
          include __DIR__ . '/../includes/product-card.php';
        ?>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="grid-column:1/-1;opacity:.7;">No products found matching your search.</p>
    <?php endif; ?>
  </div>

  <div style="text-align:center;margin:40px 0;">
    <a href="<?= SITE_URL ?>" class="close-btn" title="Back to Home">
      <i class="fa-solid fa-xmark"></i>
    </a>
  </div>

</body>
</html>
