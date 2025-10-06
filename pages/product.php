<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('<p>Invalid product ID.</p>');
}

$sql = "SELECT id, name, price, sale_price, image, category, gender, subcategory, created_at 
        FROM products 
        WHERE id = ? AND (is_active IS NULL OR is_active = 1)
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die('<p>Product not found.</p>');
}
?>

<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($product['name']) ?> | Jolly Dolly</title>
  <link rel="stylesheet" href="<?= SITE_URL ?>css/new.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= SITE_URL ?>css/product.css?v=<?= time() ?>">
</head>
<body>

<div class="product-page">
  <div class="product-image">
    <img src="<?= SITE_URL ?>uploads/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
  </div>

  <div class="product-info">
    <h1><?= htmlspecialchars($product['name']) ?></h1>

    <?php if (!empty($product['sale_price'])): ?>
      <p class="price">
        <span class="sale">₱<?= number_format($product['sale_price'], 2) ?></span>
        <span class="old">₱<?= number_format($product['price'], 2) ?></span>
      </p>
    <?php else: ?>
      <p class="price">₱<?= number_format($product['price'], 2) ?></p>
    <?php endif; ?>

    <p>Category: <?= ucfirst($product['category']) ?></p>
    <p>Gender: <?= ucfirst($product['gender']) ?></p>
    <p>Subcategory: <?= ucfirst(str_replace('-', ' ', $product['subcategory'])) ?></p>

    <div class="action-buttons">
    <?php if (isset($_SESSION['user_id'])): ?>
        <button class="add-to-cart" data-id="<?= $product['id'] ?>">Add to Cart</button>
        <button class="wishlist-btn">♡ Add to Wishlist</button>
    <?php else: ?>
        <button class="add-to-cart require-login">Add to Cart</button>
        <button class="wishlist-btn require-login">♡ Add to Wishlist</button>
    <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script src="<?= SITE_URL ?>js/product.js"></script>
</body>
</html>