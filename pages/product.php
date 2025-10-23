<?php
ob_start();
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

// ✅ Get product ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  die('<p>Invalid product ID.</p>');
}

// ✅ Fetch product details with image from product_images table
$sql = "SELECT p.id, p.name, p.price, p.sale_price, p.actual_sale_price, 
               p.description, p.created_at, p.category, p.category_group,
               p.gender, p.subcategory, p.sale_start, p.sale_end,
               pi.image, pi.image_format
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id
        WHERE p.id = ? AND (p.is_active IS NULL OR p.is_active = 1)
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
  die('<p>Product not found.</p>');
}

// 🟣 Handle blob image conversion
if (!empty($product['image'])) {
    $mimeType = !empty($product['image_format']) ? $product['image_format'] : 'image/jpeg';
    $imageSrc = 'data:' . $mimeType . ';base64,' . base64_encode($product['image']);
} else {
    $imageSrc = SITE_URL . 'uploads/sample1.jpg';
}

// ✅ FIXED: Better price calculation logic
$hasSale = false;
$displayPrice = $product['price']; // Default to regular price

// Check if product is on sale
if (!empty($product['sale_price']) && $product['sale_price'] > 0 && $product['sale_price'] < $product['price']) {
    $hasSale = true;
    // Use actual_sale_price if available, otherwise use sale_price
    $displayPrice = !empty($product['actual_sale_price']) ? $product['actual_sale_price'] : $product['sale_price'];
}

// Debug output (you can remove this after testing)
error_log("Product {$product['id']} - Price: {$product['price']}, Sale Price: {$product['sale_price']}, Actual Sale: {$product['actual_sale_price']}, Display: {$displayPrice}, Has Sale: " . ($hasSale ? 'Yes' : 'No'));
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($product['name']) ?> | Jolly Dolly</title>
  <link rel="stylesheet" href="<?= SITE_URL; ?>css/new.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= SITE_URL; ?>css/product.css?v=<?= time() ?>">
</head>
<body>

  <div class="product-page">
    <div class="product-image">
      <!-- ✅ Image from blob data -->
      <img src="<?= $imageSrc ?>" 
           alt="<?= htmlspecialchars($product['name']) ?>"
           onerror="this.src='<?= SITE_URL; ?>uploads/sample1.jpg'">
    </div>

    <div class="product-info">
      <h1><?= htmlspecialchars($product['name']) ?></h1>

      <?php if ($hasSale): ?>
        <div class="price-container">
          <p class="price">
            <span class="sale">₱<?= number_format($displayPrice, 2) ?></span>
            <span class="old">₱<?= number_format($product['price'], 2) ?></span>
          </p>
          <?php 
            $discountPercent = round((($product['price'] - $displayPrice) / $product['price']) * 100);
            if ($discountPercent > 0): 
          ?>
            <span class="discount-percent">-<?= $discountPercent ?>%</span>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <!-- ✅ FIXED: Show regular price when not on sale -->
        <p class="price">₱<?= number_format($displayPrice, 2) ?></p>
      <?php endif; ?>

      <div class="product-description">
        <h3>Description</h3>
        <p>
          <?= !empty($product['description'])
            ? nl2br(htmlspecialchars($product['description']))
            : 'No description available for this product.' ?>
        </p>
      </div>

      <!-- ✅ Size Selection -->
      <div class="size-selector">
        <label>Size:</label>
        <div class="size-options">
          <button type="button" class="size-option active" data-size="S">S</button>
          <button type="button" class="size-option" data-size="M">M</button>
          <button type="button" class="size-option" data-size="L">L</button>
          <button type="button" class="size-option" data-size="XL">XL</button>
        </div>
      </div>

      <!-- ✅ Quantity Selector -->
      <div class="quantity-selector">
        <label>Quantity:</label>
        <button type="button" class="quantity-btn" id="minus-btn">−</button>
        <input type="number" id="quantity" name="quantity" value="1" min="1" max="10" class="quantity-input">
        <button type="button" class="quantity-btn" id="plus-btn">+</button>
      </div>

      <div class="action-buttons">
        <button class="add-to-cart" data-id="<?= $product['id'] ?>">Add to Cart</button>
        <button class="wishlist-btn" data-id="<?= $product['id'] ?>">♡ Add to Wishlist</button>
      </div>

      <!-- ✅ Buy Now Button -->
      <div class="action-button">
        <form id="buy-now-form" action="<?= SITE_URL ?>actions/buy_now.php" method="POST">
          <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
          <input type="hidden" name="quantity" value="1" id="buy-now-quantity">
          <input type="hidden" name="size" value="M" id="selected-size">
          <input type="hidden" name="price" value="<?= $displayPrice; ?>" id="product-price">
          <button type="submit" class="checkout-btn" id="buy-now-btn">Buy Now</button>
        </form>
      </div>
    </div>
  </div>

  <?php require_once __DIR__ . '/../includes/footer.php'; ?>

  <script>
    // ✅ Size selection
    document.querySelectorAll('.size-option').forEach(btn => {
      btn.addEventListener('click', function() {
        document.querySelectorAll('.size-option').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('selected-size').value = this.dataset.size;
      });
    });

    // ✅ Quantity logic
    const minusBtn = document.getElementById('minus-btn');
    const plusBtn = document.getElementById('plus-btn');
    const quantityInput = document.getElementById('quantity');
    const buyNowQuantity = document.getElementById('buy-now-quantity');

    minusBtn.addEventListener('click', () => {
      let val = parseInt(quantityInput.value);
      if (val > 1) {
        quantityInput.value = val - 1;
        buyNowQuantity.value = quantityInput.value;
      }
    });

    plusBtn.addEventListener('click', () => {
      let val = parseInt(quantityInput.value);
      if (val < 10) {
        quantityInput.value = val + 1;
        buyNowQuantity.value = quantityInput.value;
      }
    });

    quantityInput.addEventListener('change', () => {
      let val = parseInt(quantityInput.value);
      if (val < 1) quantityInput.value = 1;
      if (val > 10) quantityInput.value = 10;
      buyNowQuantity.value = quantityInput.value;
    });
  </script>

  <script src="<?= SITE_URL; ?>js/product.js?v=<?= time() ?>"></script>
</body>
</html>
<?php ob_end_flush(); ?>