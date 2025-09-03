<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

$segment = $_GET['segment'] ?? 'all';
$allowed  = ['all','boys','girls','baby-boys','baby-girls','newborn'];
if (!in_array($segment, $allowed)) $segment = 'all';

$map = [
  'boys'       => 'kids-boys',
  'girls'      => 'kids-girls',
  'baby-boys'  => 'baby-boys',
  'baby-girls' => 'baby-girls',
  'newborn'    => 'newborn',
];

$perPage = 24;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

/* Build query */
if ($segment === 'all') {
  $sql  = "SELECT id, name, price, image, category, created_at
           FROM products
           WHERE is_active = 1
           ORDER BY created_at DESC
           LIMIT ?, ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $offset, $perPage);
} else {
  $cat  = $map[$segment];
  $sql  = "SELECT id, name, price, image, category, created_at
           FROM products
           WHERE is_active = 1 AND category = ?
           ORDER BY created_at DESC
           LIMIT ?, ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("sii", $cat, $offset, $perPage);
}

$stmt->execute();
$products = $stmt->get_result();

/* Count for simple pagination (optional) */
if ($segment === 'all') {
  $countSql = "SELECT COUNT(*) AS c FROM products WHERE is_active=1";
  $count = $conn->query($countSql)->fetch_assoc()['c'] ?? 0;
} else {
  $countStmt = $conn->prepare("SELECT COUNT(*) AS c FROM products WHERE is_active=1 AND category=?");
  $countStmt->bind_param("s", $cat);
  $countStmt->execute();
  $count = $countStmt->get_result()->fetch_assoc()['c'] ?? 0;
}
$totalPages = max(1, ceil($count / $perPage));

/* Helper for active tab */
function tabActive($seg, $current){ return $seg === $current ? 'class="tab active"' : 'class="tab"'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/new.css?v=<?= time(); ?>">
  <title>New Arrivals</title>
</head>
<body>

  <div class="new-header">
  <h1 class="new-title">New<?= $segment !== 'all' ? ' — ' . ucwords(str_replace('-', ' ', $segment)) : '' ?></h1>

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
        <a href="<?= SITE_URL ?>pages/new.php?segment=<?= urlencode($segment) ?>&page=<?= $i ?>"><?= $i ?></a>
      <?php endif; ?>
    <?php endfor; ?>
  </div>
<?php endif; ?>


</body>
</html>
