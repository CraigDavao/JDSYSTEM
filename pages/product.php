<?php
$sql = "INSERT INTO wishlist (user_id, product_id, added_at) VALUES (?, ?, NOW())";

ob_start();

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

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($product['name']) ?> | Jolly Dolly</title>
  <link rel="stylesheet" href="../css/new.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../css/product.css?v=<?= time() ?>">
</head>

<body>

  <div class="product-page">
    <div class="product-image">
      <!-- ✅ Image from uploads folder -->
      <img src="../uploads/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
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

      <!-- ✅ Quantity Selector -->
      <div class="quantity-selector">
        <button type="button" class="quantity-btn" id="minus-btn">−</button>
        <input type="number" id="quantity" name="quantity" value="1" min="1" class="quantity-input">
        <button type="button" class="quantity-btn" id="plus-btn">+</button>
      </div>

      <div class="action-buttons">
        <button class="add-to-cart" data-id="<?= $product['id'] ?>">Add to Cart</button>
        <button class="wishlist-btn" data-id="<?= $product['id'] ?>">♡ Add to Wishlist</button>
      </div>

      <div class="action-button">
        <form action="<?php echo SITE_URL; ?>actions/buy_now.php" method="POST" style="display:inline;">
          <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
          <input type="hidden" name="quantity" id="buy-now-quantity" value="1">
          <button type="submit" class="checkout-btn">Buy Now</button>
        </form>
      </div>
    </div>
  </div>

  <?php require_once __DIR__ . '/../includes/footer.php'; ?>

  <script>
    // ✅ Quantity logic for + / − buttons
    const minusBtn = document.getElementById('minus-btn');
    const plusBtn = document.getElementById('plus-btn');
    const quantityInput = document.getElementById('quantity');
    const buyNowQuantity = document.getElementById('buy-now-quantity');

    minusBtn.addEventListener('click', () => {
      let val = parseInt(quantityInput.value);
      if (val > 1) quantityInput.value = val - 1;
      buyNowQuantity.value = quantityInput.value;
    });

    plusBtn.addEventListener('click', () => {
      let val = parseInt(quantityInput.value);
      quantityInput.value = val + 1;
      buyNowQuantity.value = quantityInput.value;
    });

    quantityInput.addEventListener('change', () => {
      buyNowQuantity.value = quantityInput.value;
    });
  </script>

  <script src="../js/product.js?v=<?= time() ?>"></script>
  <script src="../js/checkout.js?v=<?= time() ?>"></script>
</body>
</html>
