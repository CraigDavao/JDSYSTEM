<?php
ob_start();
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../functions.php';

// ✅ Get ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  die('<p>Invalid ID.</p>');
}

// DEBUG: Check what's on line 121
echo "<!-- Debug: Current line count: " . __LINE__ . " -->";

// First, check if this is a COLOR ID
$is_color_id = false;
$color_id = $id;
$product_id = 0;

// Try to fetch as color ID first - WITH PRICE FROM PRODUCTS TABLE
$color_sql = "SELECT 
                pc.id as color_id,
                pc.product_id,
                pc.color_name,
                pc.quantity as color_quantity,
                pc.is_default,
                p.name, 
                p.price,
                p.sale_price,
                p.actual_sale_price,
                p.description, 
                p.created_at, 
                p.category, 
                p.category_group,
                p.gender, 
                p.subcategory, 
                p.sale_start, 
                p.sale_end,
                pi.image, 
                pi.image_format
            FROM product_colors pc
            INNER JOIN products p ON pc.product_id = p.id
            LEFT JOIN product_images pi ON pc.product_id = pi.product_id AND pi.color_name = pc.color_name
            WHERE pc.id = ? AND (p.is_active IS NULL OR p.is_active = 1)
            LIMIT 1";

echo "<!-- Debug: SQL Query: " . htmlspecialchars($color_sql) . " -->";

// Initialize product state from session
if (!isset($_SESSION['product_state'])) {
    $_SESSION['product_state'] = [];
}

// Get current product state from session
$current_size = $_SESSION['product_state'][$product_id]['size'] ?? 'M';
$current_quantity = $_SESSION['product_state'][$product_id]['quantity'] ?? 1;

// If color_id is in session, use it (unless URL has different color_id)
if (isset($_SESSION['product_state'][$product_id]['color_id']) && !isset($_GET['color_id'])) {
    $color_id = $_SESSION['product_state'][$product_id]['color_id'];
}

$color_stmt = $conn->prepare($color_sql);
if (!$color_stmt) {
    die('Prepare failed: (' . $conn->errno . ') ' . $conn->error);
}

$color_stmt->bind_param("i", $color_id);
$color_stmt->execute();
$product = $color_stmt->get_result()->fetch_assoc();

// ... rest of your code

if ($product) {
    // This is a COLOR ID
    $is_color_id = true;
    $product_id = $product['product_id'];
} else {
    // If not found as color ID, try as PRODUCT ID - WITH PRICE FROM PRODUCTS TABLE
    $product_sql = "SELECT p.id as product_id, p.name, p.price, p.sale_price, p.actual_sale_price, 
                           p.description, p.created_at, p.category, p.category_group,
                           p.gender, p.subcategory, p.sale_start, p.sale_end,
                           pi.image, pi.image_format
                    FROM products p
                    LEFT JOIN product_images pi ON p.id = pi.product_id
                    WHERE p.id = ? AND (p.is_active IS NULL OR p.is_active = 1)
                    LIMIT 1";
    $product_stmt = $conn->prepare($product_sql);
    $product_stmt->bind_param("i", $id);
    $product_stmt->execute();
    $product = $product_stmt->get_result()->fetch_assoc();
    
    if (!$product) {
        die('<p>Product not found.</p>');
    }
    
    $product_id = $product['product_id'] = $id;
    
    // Get default color for this product
    $default_color = getDefaultProductColor($product_id, $conn);
    if ($default_color) {
        $color_id = $default_color['id'];
        $is_color_id = true;
        
        // Update URL to use color ID instead of product ID
        echo "<script>
            if (window.history.replaceState) {
                var newUrl = window.location.protocol + '//' + window.location.host + window.location.pathname + '?id=' + $color_id;
                window.history.replaceState({}, '', newUrl);
            }
        </script>";
    }
}

// ✅ GET PRODUCT COLORS WITH IMAGES
$colors = getProductColorsWithImages($product_id, $conn);

// ✅ Get current color
$current_color = null;
foreach ($colors as $color) {
    if ($color['id'] == $color_id) {
        $current_color = $color;
        break;
    }
}

if (!$current_color && !empty($colors)) {
    $current_color = $colors[0];
    $color_id = $current_color['id'];
}

// 🟣 Handle blob image conversion - USE COLOR IMAGE IF AVAILABLE
if ($current_color && !empty($current_color['image'])) {
    $mimeType = $current_color['image_format'] ?? 'image/jpeg';
    $imageSrc = 'data:' . $mimeType . ';base64,' . base64_encode($current_color['image']);
} elseif (!empty($product['image'])) {
    $mimeType = !empty($product['image_format']) ? $product['image_format'] : 'image/jpeg';
    $imageSrc = 'data:' . $mimeType . ';base64,' . base64_encode($product['image']);
} else {
    $imageSrc = SITE_URL . 'uploads/sample1.jpg';
}

// ✅ FIXED: Price calculation using data from products table
$hasSale = false;
$displayPrice = $product['price']; // Default to regular price

// Check if product is on sale
if (!empty($product['sale_price']) && $product['sale_price'] > 0 && $product['sale_price'] < $product['price']) {
    $hasSale = true;
    // Use actual_sale_price if available, otherwise use sale_price
    $displayPrice = !empty($product['actual_sale_price']) ? $product['actual_sale_price'] : $product['sale_price'];
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($product['name']) ?> | Jolly Dolly</title>
  <link rel="stylesheet" href="<?= SITE_URL; ?>css/new.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= SITE_URL; ?>css/product.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= SITE_URL; ?>css/color-selector.css?v=<?= time() ?>">
</head>
<body>

  <div class="product-page">
   <div class="product-image">
    <img src="<?= $imageSrc ?>" 
         alt="<?= htmlspecialchars($product['name']) ?>"
         class="main-product-image"
         data-fallback="<?= SITE_URL ?>uploads/sample1.jpg"
         onerror="this.src='<?= SITE_URL ?>uploads/sample1.jpg'">
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
        <p class="price">₱<?= number_format($displayPrice, 2) ?></p>
      <?php endif; ?>

      <!-- ✅ COLOR SELECTOR -->
      <?php if (!empty($colors)): ?>
        <?php 
          $current_color_id = $current_color['id'] ?? $color_id;
          include __DIR__ . '/../includes/color-selector.php'; 
        ?>
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
        <!-- ✅ Add to Cart button with ONLY COLOR ID -->
        <button class="add-to-cart" data-id="<?= $current_color_id ?>">
          Add to Cart
        </button>
        <button class="wishlist-btn" data-id="<?= $product_id ?>">♡ Add to Wishlist</button>
      </div>

      <!-- ✅ Buy Now Button - Make sure this exists in your product.php -->
      <button class="checkout-btn" id="buy-now-btn" 
        data-color-id="<?= $current_color_id ?>" 
        data-product-id="<?= $product_id ?>"
        data-price="<?= $displayPrice ?>">
          Buy Now
      </button>
      </div>
    </div>
  </div>

  <?php require_once __DIR__ . '/../includes/footer.php'; ?>

  <script src="<?= SITE_URL; ?>js/color-selector.js?v=<?= time() ?>"></script>

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

    // ✅ Update URL when color changes
    document.addEventListener('colorChanged', function(e) {
      const newUrl = `<?= SITE_URL ?>pages/product.php?id=${e.detail.colorId}`;
      window.history.replaceState({}, '', newUrl);
      console.log('URL updated to:', newUrl);
    });

    
  </script>

  <script>
document.addEventListener("DOMContentLoaded", () => {
  const productId = document.querySelector(".color-selector")?.dataset.productId;
  const selectedColorId = document.getElementById("selected-color-id");

  const markCartAction = () => {
    if (productId && selectedColorId.value) {
      sessionStorage.setItem("selected_color_" + productId, selectedColorId.value);
      sessionStorage.setItem("from_cart_actions", "true");
    }
  };

  document.querySelectorAll(".add-to-cart, .add-to-wishlist, .buy-now").forEach(btn => {
    btn.addEventListener("click", markCartAction);
  });
});
</script>


  <script src="<?= SITE_URL; ?>js/product.js?v=<?= time() ?>"></script>
</body>
</html>
<?php ob_end_flush(); ?>