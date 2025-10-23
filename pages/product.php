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

// ✅ Fetch product details with image from product_images table
$sql = "SELECT p.id, p.name, p.price, p.sale_price, p.actual_sale_price, 
               p.description, p.created_at,
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

// Use actual_sale_price if available, otherwise use sale_price
$displaySalePrice = !empty($product['actual_sale_price']) ? $product['actual_sale_price'] : $product['sale_price'];
$hasSale = !empty($product['sale_price']) && $product['sale_price'] > 0 && $product['sale_price'] < $product['price'];
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
      <!-- ✅ Image from blob data -->
      <img src="<?= $imageSrc ?>" 
           alt="<?= htmlspecialchars($product['name']) ?>"
           onerror="this.src='<?= SITE_URL; ?>uploads/sample1.jpg'">
    </div>

    <div class="product-info">
      <h1><?= htmlspecialchars($product['name']) ?></h1>

      <?php if ($hasSale): ?>
        <p class="price">
          <span class="sale">₱<?= number_format($displaySalePrice, 2) ?></span>
          <span class="old">₱<?= number_format($product['price'], 2) ?></span>
          <?php 
            // Calculate and display discount percentage
            $discountPercent = round((($product['price'] - $displaySalePrice) / $product['price']) * 100);
            if ($discountPercent > 0): 
          ?>
            <span class="discount-percent">-<?= $discountPercent ?>%</span>
          <?php endif; ?>
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

<!-- Buy Now Button -->
<div class="action-button">
    <form id="buy-now-form" action="<?= SITE_URL ?>actions/buy_now.php" method="POST" style="display:inline;">
        <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
        <input type="hidden" name="quantity" value="1" id="buy-now-quantity">
        <input type="hidden" name="size" value="M" id="selected-size">
        <button type="submit" class="checkout-btn" id="buy-now-btn">Buy Now</button>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const buyNowForm = document.getElementById('buy-now-form');
    const buyNowBtn = document.getElementById('buy-now-btn');
    const selectedSizeInput = document.getElementById('selected-size');

    // Update size if user selects different size
    const sizeButtons = document.querySelectorAll('.size-option');
    sizeButtons.forEach(button => {
        button.addEventListener('click', function() {
            selectedSizeInput.value = this.getAttribute('data-size');
        });
    });

    if (buyNowForm && buyNowBtn) {
        buyNowForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Disable button to prevent double click
            buyNowBtn.disabled = true;
            buyNowBtn.innerHTML = 'Processing...';

            // Submit the form
            this.submit();
        });
    }
});
</script>

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
      if (buyNowQuantity) buyNowQuantity.value = quantityInput.value;
    });

    plusBtn.addEventListener('click', () => {
      let val = parseInt(quantityInput.value);
      quantityInput.value = val + 1;
      if (buyNowQuantity) buyNowQuantity.value = quantityInput.value;
    });

    quantityInput.addEventListener('change', () => {
      if (buyNowQuantity) buyNowQuantity.value = quantityInput.value;
    });
  </script>

  <script src="../js/product.js?v=<?= time() ?>"></script>
  <script src="../js/checkout.js?v=<?= time() ?>"></script>
</body>
</html>