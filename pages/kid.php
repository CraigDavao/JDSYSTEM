<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

$allowedGenders = ['girls','boys','unisex'];
$allowedSub     = ['sets','tops','bottoms','sleepwear','dresses-jumpsuits'];

$gender = $_GET['gender'] ?? null;
if ($gender && !in_array($gender, $allowedGenders)) $gender = null;

$sub = $_GET['subcategory'] ?? null;
if ($sub && !in_array($sub, $allowedSub)) $sub = null;

$perPage = 24;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Build main query
if ($gender && $sub) {
    $sql = "SELECT id,name,price,sale_price,image,created_at FROM products
            WHERE category_group='kid' AND gender=? AND subcategory=? AND (is_active IS NULL OR is_active=1)
            ORDER BY created_at DESC LIMIT ?,?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $gender, $sub, $offset, $perPage);
} elseif ($gender) {
    $sql = "SELECT id,name,price,sale_price,image,created_at FROM products
            WHERE category_group='kid' AND gender=? AND (is_active IS NULL OR is_active=1)
            ORDER BY created_at DESC LIMIT ?,?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $gender, $offset, $perPage);
} elseif ($sub) {
    $sql = "SELECT id,name,price,sale_price,image,created_at FROM products
            WHERE category_group='kid' AND subcategory=? AND (is_active IS NULL OR is_active=1)
            ORDER BY created_at DESC LIMIT ?,?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $sub, $offset, $perPage);
} else {
    $sql = "SELECT id,name,price,sale_price,image,created_at FROM products
            WHERE category_group='kid' AND (is_active IS NULL OR is_active=1)
            ORDER BY created_at DESC LIMIT ?,?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $offset, $perPage);
}

$stmt->execute();
$result = $stmt->get_result();

// Count total for pagination
if ($gender && $sub) {
    $countSql = "SELECT COUNT(*) AS c FROM products WHERE category_group='kid' AND gender=? AND subcategory=? AND (is_active IS NULL OR is_active=1)";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param("ss", $gender, $sub);
} elseif ($gender) {
    $countSql = "SELECT COUNT(*) AS c FROM products WHERE category_group='kid' AND gender=? AND (is_active IS NULL OR is_active=1)";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param("s", $gender);
} elseif ($sub) {
    $countSql = "SELECT COUNT(*) AS c FROM products WHERE category_group='kid' AND subcategory=? AND (is_active IS NULL OR is_active=1)";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param("s", $sub);
} else {
    $countSql = "SELECT COUNT(*) AS c FROM products WHERE category_group='kid' AND (is_active IS NULL OR is_active=1)";
    $countStmt = $conn->prepare($countSql);
}

$countStmt->execute();
$count = $countStmt->get_result()->fetch_assoc()['c'] ?? 0;
$totalPages = max(1, ceil($count / $perPage));
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Kid<?= $gender ? ' — '.ucfirst($gender) : '' ?><?= $sub ? ' — ' . str_replace('-', ' ', ucfirst($sub)) : '' ?></title>
<link rel="stylesheet" href="<?= SITE_URL ?>css/new.css?v=<?= time() ?>">
</head>
<body>

<div class="new-header">
  <h1 class="new-title">
    Kid<?= $gender ? ' — '.ucfirst($gender) : '' ?><?= $sub ? ' — ' . str_replace('-', ' ', ucfirst($sub)) : '' ?>
  </h1>
</div>

<div class="product-grid">
  <?php if ($result->num_rows): while ($product = $result->fetch_assoc()): ?>
    <?php
      $product_link = SITE_URL . "pages/product.php?id=" . (int)$product['id'];
      include __DIR__ . '/../includes/product-card.php';
    ?>
  <?php endwhile; else: ?>
    <p style="grid-column:1/-1; opacity:.7;">No products found.</p>
  <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
  <div class="pager">
    <?php for ($i=1; $i <= $totalPages; $i++): ?>
      <?php if ($i === $page): ?>
        <span class="current"><?= $i ?></span>
      <?php else:
        $qs = '?page='.$i;
        if ($gender) $qs .= '&gender=' . urlencode($gender);
        if ($sub)    $qs .= '&subcategory=' . urlencode($sub);
      ?>
        <a href="<?= SITE_URL ?>pages/kid.php<?= $qs ?>"><?= $i ?></a>
      <?php endif; ?>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
