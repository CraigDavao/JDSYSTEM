<?php
if (isset($_POST['product_id']) || isset($_POST['wishlist_action'])) {
    // This is an API call, don't render the page
    exit;
}

ob_start();

// ✅ Correct paths for pages/product.php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

// ✅ Get product ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('<p>Invalid product ID.</p>');
}

// ✅ Fetch product details
$sql = "SELECT id, name, price, sale_price, image, description, created_at 
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

<?php
// Temporary debug version
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- Debug: Starting product.php -->";

// Test connection.php first
require_once __DIR__ . '/../connection/connection.php';
echo "<!-- Debug: Connection loaded -->";

// Test header.php
require_once __DIR__ . '/../includes/header.php';
echo "<!-- Debug: Header loaded -->";

// Rest of your code...
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($product['name']) ?> | Jolly Dolly</title>
  <!-- ✅ Update CSS paths too -->
  <link rel="stylesheet" href="../css/new.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../css/product.css?v=<?= time() ?>">
</head>
<body>

<div class="product-page">
  <div class="product-image">
    <!-- ✅ Update image path -->
    <img src="../uploads/<?= htmlspecialchars($product['image']) ?>" 
         alt="<?= htmlspecialchars($product['name']) ?>">
  </div>

  <div class="product-info">
    <h1><?= htmlspecialchars($product['name']) ?></h1>

    <?php if (!empty($product['sale_price']) && $product['sale_price'] > 0): ?>
      <p class="price">
        <span class="sale">₱<?= number_format($product['sale_price'], 2) ?></span>
        <span class="old">₱<?= number_format($product['price'], 2) ?></span>
      </p>
    <?php else: ?>
      <p class="price">₱<?= number_format($product['price'], 2) ?></p>
    <?php endif; ?>

    <div class="product-description">
      <h3>Description</h3>
      <p>
        <?= !empty($product['description']) 
            ? nl2br(htmlspecialchars($product['description'])) 
            : 'No description available for this product.' ?>
      </p>
    </div>

    <div class="action-buttons">
      <!-- Always show buttons, let JavaScript handle login state -->
      <button class="add-to-cart" data-id="<?= $product['id'] ?>">Add to Cart</button>
      <button class="wishlist-btn" data-id="<?= $product['id'] ?>">♡ Add to Wishlist</button>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
  const SITE_URL = "<?= SITE_URL ?>";
</script>
<script src="../js/product.js?v=<?= time() ?>"></script>

</body>
</html>