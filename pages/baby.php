<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

// Allowed filters for Baby
$allowedGenders = ['girls','boys','newborn']; 
$allowedSub     = [
    'sets','tops','bottoms','sleepwear','accessories',
    'sets','tops','bottoms','sleepwear',
    'bodysuits-sleepsuits','essentials','sets'
];

$gender = $_GET['gender'] ?? null;
if ($gender && !in_array($gender, $allowedGenders)) $gender = null;

$sub = $_GET['subcategory'] ?? null;
if ($sub && !in_array($sub, $allowedSub)) $sub = null;

$perPage = 24;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

/* Build main query */
$baseQuery = "SELECT id, name, price, sale_price, image, created_at
              FROM products
              WHERE category_group='baby'
                AND (is_active IS NULL OR is_active=1)";
$params = [];
$types  = '';

if ($gender) {
    $baseQuery .= " AND gender=?";
    $params[] = $gender;
    $types .= 's';
}
if ($sub) {
    $baseQuery .= " AND subcategory=?";
    $params[] = $sub;
    $types .= 's';
}

$baseQuery .= " ORDER BY created_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $perPage;
$types .= 'ii';

$stmt = $conn->prepare($baseQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

/* Count total */
$countQuery = "SELECT COUNT(*) AS c
               FROM products
               WHERE category_group='baby'
                 AND (is_active IS NULL OR is_active=1)";
$params2 = [];
$types2  = '';

if ($gender) {
    $countQuery .= " AND gender=?";
    $params2[] = $gender;
    $types2 .= 's';
}
if ($sub) {
    $countQuery .= " AND subcategory=?";
    $params2[] = $sub;
    $types2 .= 's';
}

$countStmt = $conn->prepare($countQuery);
if (!empty($params2)) $countStmt->bind_param($types2, ...$params2);
$countStmt->execute();
$count = $countStmt->get_result()->fetch_assoc()['c'] ?? 0;
$totalPages = max(1, ceil($count / $perPage));
?>

<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Baby<?= $gender ? ' — ' . ucfirst($gender) : '' ?><?= $sub ? ' — ' . str_replace('-', ' ', ucfirst($sub)) : '' ?></title>
  <link rel="stylesheet" href="<?= SITE_URL ?>css/new.css?v=<?= time() ?>">
</head>
<body>
  <div class="new-header">
    <h1 class="new-title">
      Baby<?= $gender ? ' — ' . ucfirst($gender) : '' ?><?= $sub ? ' — ' . str_replace('-', ' ', ucfirst($sub)) : '' ?>
    </h1>
  </div>

  <div class="product-grid">
    <?php if ($result->num_rows): ?>
      <?php while ($product = $result->fetch_assoc()): ?>
        <?php
          $product_link = SITE_URL . "pages/product.php?id=" . (int)$product['id'];
          include __DIR__ . '/../includes/product-card.php';
        ?>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="grid-column:1/-1; opacity:.7;">No products found.</p>
    <?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="pager">
      <?php for ($i=1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?>
          <span class="current"><?= $i ?></span>
        <?php else:
          $qs = '?page=' . $i;
          if ($gender) $qs .= '&gender=' . urlencode($gender);
          if ($sub)    $qs .= '&subcategory=' . urlencode($sub);
        ?>
          <a href="<?= SITE_URL ?>pages/baby.php<?= $qs ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

  <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
