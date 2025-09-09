<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

// Allowed filters for Baby
$allowedGenders = ['girls','boys','newborn']; 
$allowedSub = [
    // Baby Girls
    'sets','tops','bottoms','sleepwear','accessories',
    // Baby Boys
    'sets','tops','bottoms','sleepwear',
    // Newborn
    'bodysuits-sleepsuits','essentials','sets'
];

$gender = $_GET['gender'] ?? null;
if ($gender && !in_array($gender, $allowedGenders)) $gender = null;

$sub = $_GET['subcategory'] ?? null;
if ($sub && !in_array($sub, $allowedSub)) $sub = null;

$perPage = 24;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

/* build query */
if ($gender && $sub) {
    $sql = "SELECT id,name,price,image,created_at FROM products
            WHERE category_group='baby' AND gender=? AND subcategory=? 
              AND (is_active IS NULL OR is_active=1)
            ORDER BY created_at DESC LIMIT ?,?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $gender, $sub, $offset, $perPage);
}
elseif ($gender) {
    $sql = "SELECT id,name,price,image,created_at FROM products
            WHERE category_group='baby' AND gender=? 
              AND (is_active IS NULL OR is_active=1)
            ORDER BY created_at DESC LIMIT ?,?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $gender, $offset, $perPage);
}
elseif ($sub) {
    $sql = "SELECT id,name,price,image,created_at FROM products
            WHERE category_group='baby' AND subcategory=? 
              AND (is_active IS NULL OR is_active=1)
            ORDER BY created_at DESC LIMIT ?,?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $sub, $offset, $perPage);
}
else {
    $sql = "SELECT id,name,price,image,created_at FROM products
            WHERE category_group='baby' 
              AND (is_active IS NULL OR is_active=1)
            ORDER BY created_at DESC LIMIT ?,?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $offset, $perPage);
}

$stmt->execute();
$result = $stmt->get_result();

/* count total */
if ($gender && $sub) {
    $countStmt = $conn->prepare("SELECT COUNT(*) AS c FROM products WHERE category_group='baby' AND gender=? AND subcategory=? AND (is_active IS NULL OR is_active=1)");
    $countStmt->bind_param("ss",$gender,$sub);
} elseif ($gender) {
    $countStmt = $conn->prepare("SELECT COUNT(*) AS c FROM products WHERE category_group='baby' AND gender=? AND (is_active IS NULL OR is_active=1)");
    $countStmt->bind_param("s",$gender);
} elseif ($sub) {
    $countStmt = $conn->prepare("SELECT COUNT(*) AS c FROM products WHERE category_group='baby' AND subcategory=? AND (is_active IS NULL OR is_active=1)");
    $countStmt->bind_param("s",$sub);
} else {
    $countStmt = $conn->prepare("SELECT COUNT(*) AS c FROM products WHERE category_group='baby' AND (is_active IS NULL OR is_active=1)");
}

$countStmt->execute();
$count = $countStmt->get_result()->fetch_assoc()['c'] ?? 0;
$totalPages = max(1, ceil($count / $perPage));
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Baby</title>
  <link rel="stylesheet" href="<?= SITE_URL ?>css/new.css?v=<?= time() ?>">
  <script src="<?= SITE_URL ?>js/main.js?v=<?= time() ?>"></script>
</head>
<body>
  <div class="new-header">
    <h1 class="new-title">Baby<?= $gender ? ' — '.ucfirst($gender) : '' ?><?= $sub ? ' — ' . str_replace('-', ' ', ucfirst($sub)) : '' ?></h1>
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
      <p style="grid-column:1/-1; opacity:.7;">No products found.</p>
    <?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="pager">
      <?php for ($i=1;$i <= $totalPages; $i++): if ($i === $page): ?>
        <span class="current"><?= $i ?></span>
      <?php else:
        $qs = '?page='.$i;
        if ($gender) $qs .= '&gender=' . urlencode($gender);
        if ($sub)    $qs .= '&subcategory=' . urlencode($sub);
      ?>
        <a href="<?= SITE_URL ?>pages/baby.php<?= $qs ?>"><?= $i ?></a>
      <?php endif; endfor; ?>
    </div>
  <?php endif; ?>
</body>
</html>
