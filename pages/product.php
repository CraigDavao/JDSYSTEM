<?php
ob_start();
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../functions.php'; // ✅ ADD THIS LINE

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

// ✅ GET PRODUCT COLORS WITH IMAGES
$colors = getProductColorsWithImages($id, $conn);

// ✅ Get default color for main image
$default_color = getDefaultProductColor($id, $conn);
if (!$default_color && !empty($colors)) {
    $default_color = $colors[0];
}

// 🟣 Handle blob image conversion - USE COLOR IMAGE IF AVAILABLE
if ($default_color && !empty($default_color['image'])) {
    $mimeType = $default_color['image_format'] ?? 'image/jpeg';
    $imageSrc = 'data:' . $mimeType . ';base64,' . base64_encode($default_color['image']);
} elseif (!empty($product['image'])) {
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

// Debug output
error_log("Product {$product['id']} - Price: {$product['price']}, Sale Price: {$product['sale_price']}, Actual Sale: {$product['actual_sale_price']}, Display: {$displayPrice}, Has Sale: " . ($hasSale ? 'Yes' : 'No'));
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($product['name']) ?> | Jolly Dolly</title>
  <link rel="stylesheet" href="<?= SITE_URL; ?>css/new.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= SITE_URL; ?>css/product.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= SITE_URL; ?>css/color-selector.css?v=<?= time() ?>"> <!-- ✅ ADD COLOR CSS -->
</head>
<body>

  <div class="product-page">
   <div class="product-image">
  <!-- ✅ Image from blob data -->
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
        <!-- ✅ FIXED: Show regular price when not on sale -->
        <p class="price">₱<?= number_format($displayPrice, 2) ?></p>
      <?php endif; ?>

      <!-- ✅ COLOR SELECTOR -->
      <?php if (!empty($colors)): ?>
        <?php 
          $product_id = $product['id'];
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
        <!-- ✅ SIMPLIFIED: Add to Cart button with ONLY product_id -->
        <button class="add-to-cart" data-id="<?= $product['id'] ?>">
          Add to Cart
        </button>
        <button class="wishlist-btn" data-id="<?= $product['id'] ?>">♡ Add to Wishlist</button>
      </div>

      <!-- ✅ Buy Now Button -->
      <div class="action-button">
        <form id="buy-now-form" action="<?= SITE_URL ?>actions/buy_now.php" method="POST">
          <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
          <input type="hidden" name="quantity" value="1" id="buy-now-quantity">
          <input type="hidden" name="size" value="M" id="selected-size">
          <input type="hidden" name="price" value="<?= $displayPrice; ?>" id="product-price">
          <input type="hidden" name="color_id" id="form-color-id" value="<?= $default_color['id'] ?? '' ?>">
          <button type="submit" class="checkout-btn" id="buy-now-btn">Buy Now</button>
        </form>
      </div>
    </div>
  </div>

  <?php require_once __DIR__ . '/../includes/footer.php'; ?>

  <!-- ✅ ADD COLOR SELECTOR JS -->
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

    // ✅ Update form color ID when color changes (for Buy Now only)
    document.addEventListener('colorChanged', function(e) {
      const formColorInput = document.getElementById('form-color-id');
      if (formColorInput) {
        formColorInput.value = e.detail.colorId;
        console.log('Color changed to:', e.detail.colorName, 'ID:', e.detail.colorId);
      }
    });
  </script>

  <!-- Add this RIGHT BEFORE the closing </body> tag -->
<script>
// 🛒 DEBUG: Temporary Add to Cart functionality
document.querySelectorAll(".add-to-cart").forEach((btn) => {
    btn.addEventListener("click", async function(e) {
        e.preventDefault();
        console.log("🛒 Add to Cart CLICKED - DEBUG MODE");
        
        const productId = this.dataset.id;
        console.log("📦 Product ID:", productId);
        
        if (!productId) {
            alert("❌ No product ID found!");
            return;
        }

        // Test different URL formats
        const url1 = "<?= SITE_URL ?>actions/cart-add.php";
        const url2 = "/JDSystem/actions/cart-add.php";
        const url3 = "actions/cart-add.php";
        
        console.log("🌐 Testing URLs:", { url1, url2, url3 });

        try {
            console.log("🔄 Attempting fetch to:", url1);
            
            const formData = new URLSearchParams();
            formData.append("product_id", productId);
            
            const response = await fetch(url1, {
                method: "POST",
                headers: { 
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: formData,
                credentials: "include"
            });

            console.log("✅ Fetch completed. Status:", response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const resultText = await response.text();
            console.log("📄 Raw response:", resultText);
            
            try {
                const result = JSON.parse(resultText);
                console.log("📊 Parsed JSON:", result);
                
                if (result.status === "success") {
                    alert("✅ Product added to cart!");
                } else if (result.status === "not_logged_in") {
                    alert("🔐 Please log in first!");
                } else {
                    alert("❌ " + (result.message || "Unknown error"));
                }
            } catch (parseError) {
                console.error("❌ JSON Parse Error:", parseError);
                alert("Server returned invalid JSON. Check console.");
            }

        } catch (error) {
            console.error("❌ NETWORK ERROR:", {
                name: error.name,
                message: error.message,
                stack: error.stack
            });
            
            // Test if the file exists with a simple GET request
            try {
                console.log("🔍 Testing if cart-add.php exists...");
                const testResponse = await fetch("<?= SITE_URL ?>actions/cart-add.php");
                console.log("📡 File test response status:", testResponse.status);
            } catch (testError) {
                console.error("❌ File doesn't exist or can't be accessed:", testError);
            }
            
            alert("❌ Network error: " + error.message + "\nCheck browser console for details.");
        }
    });
});

console.log("🔧 Debug script loaded - Add to Cart should work now");
</script>

  <script src="<?= SITE_URL; ?>js/product.js?v=<?= time() ?>"></script>



</body>
</html>
<?php ob_end_flush(); ?>