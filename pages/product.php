<?php
ob_start();
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../functions.php';

// ✅ Initialize product state from session
if (!isset($_SESSION['product_state'])) {
    $_SESSION['product_state'] = [];
}

// ✅ Get COLOR ID from URL (from wishlist)
$color_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($color_id <= 0) {
  die('<p>Invalid ID.</p>');
}

echo "<!-- Debug: Received Color ID from URL: $color_id -->";

// ✅ DIRECT QUERY: Get product data using COLOR ID
$sql = "SELECT 
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
            p.sale_end
        FROM product_colors pc
        INNER JOIN products p ON pc.product_id = p.id
        WHERE pc.id = ? AND (p.is_active IS NULL OR p.is_active = 1)
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Prepare failed: (' . $conn->errno . ') ' . $conn->error);
}

$stmt->bind_param("i", $color_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die('<p>Product color not found.</p>');
}

$product_id = $product['product_id'];
$current_color_name = $product['color_name'];
echo "<!-- Debug: Product ID: $product_id, Color Name: $current_color_name -->";

// ✅ GET PRODUCT COLORS WITH IMAGES
$colors = getProductColorsWithImages($product_id, $conn);
echo "<!-- Debug: Found " . count($colors) . " colors for product $product_id -->";

// ✅ Get current color from the colors array
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
    echo "<!-- Debug: Using first color as default: $color_id -->";
}

// ✅ GET STOCK INFORMATION FOR CURRENT COLOR
$current_stock = $current_color['quantity'] ?? 0;

// ✅ GET STOCK BY SIZE FOR CURRENT COLOR
$stock_by_size = [];
$sizes = ['S', 'M', 'L', 'XL'];

// Check if product_sizes table exists and has data
$check_table_sql = "SHOW TABLES LIKE 'product_sizes'";
$table_exists = $conn->query($check_table_sql)->num_rows > 0;

if ($table_exists) {
    $size_stock_sql = "SELECT size, quantity FROM product_sizes WHERE color_id = ?";
    $size_stmt = $conn->prepare($size_stock_sql);
    if ($size_stmt) {
        $size_stmt->bind_param("i", $color_id);
        $size_stmt->execute();
        $size_stock_result = $size_stmt->get_result();
        while ($row = $size_stock_result->fetch_assoc()) {
            $stock_by_size[$row['size']] = $row['quantity'];
        }
        $size_stmt->close();
    }
} else {
    foreach ($sizes as $size) {
        $stock_by_size[$size] = $current_stock;
    }
}

if (empty($stock_by_size)) {
    foreach ($sizes as $size) {
        $stock_by_size[$size] = $current_stock;
    }
}

// 🟣 FIXED: Get the CORRECT image for the specific color
$imageSrc = SITE_URL . 'uploads/sample1.jpg'; // Default fallback

// ✅ METHOD 1: Use the image from $current_color (from getProductColorsWithImages)
if ($current_color && !empty($current_color['image'])) {
    $mimeType = $current_color['image_format'] ?? 'image/jpeg';
    $imageSrc = 'data:' . $mimeType . ';base64,' . base64_encode($current_color['image']);
    echo "<!-- Debug: Using image from current_color array -->";

// ✅ METHOD 2: Direct query for the specific color image
} else {
    $image_sql = "SELECT image, image_format FROM product_images 
                  WHERE product_id = ? AND color_name = ? 
                  ORDER BY sort_order ASC, id ASC 
                  LIMIT 1";
    $image_stmt = $conn->prepare($image_sql);
    $image_stmt->bind_param("is", $product_id, $current_color_name);
    $image_stmt->execute();
    $image_result = $image_stmt->get_result();

    if ($image_result && $image_row = $image_result->fetch_assoc() && !empty($image_row['image'])) {
        $mimeType = $image_row['image_format'] ?? 'image/jpeg';
        $imageSrc = 'data:' . $mimeType . ';base64,' . base64_encode($image_row['image']);
        echo "<!-- Debug: Using direct query image for color: $current_color_name -->";
    } else {
        // ✅ METHOD 3: Get any product image as fallback
        $fallback_sql = "SELECT image, image_format FROM product_images 
                         WHERE product_id = ? 
                         ORDER BY sort_order ASC, id ASC 
                         LIMIT 1";
        $fallback_stmt = $conn->prepare($fallback_sql);
        $fallback_stmt->bind_param("i", $product_id);
        $fallback_stmt->execute();
        $fallback_result = $fallback_stmt->get_result();
        
        if ($fallback_result && $fallback_row = $fallback_result->fetch_assoc() && !empty($fallback_row['image'])) {
            $mimeType = $fallback_row['image_format'] ?? 'image/jpeg';
            $imageSrc = 'data:' . $mimeType . ';base64,' . base64_encode($fallback_row['image']);
            echo "<!-- Debug: Using fallback product image -->";
        } else {
            echo "<!-- Debug: Using default sample image -->";
        }
    }
}

// ✅ DEBUG: Show which image we're using
echo "<!-- Debug: Final Image Source - Color ID: $color_id, Color Name: $current_color_name -->";
echo "<!-- Debug: Image exists in current_color: " . (!empty($current_color['image']) ? 'YES' : 'NO') . " -->";

// ✅ FIXED: Price calculation - treat sale_price as percentage discount
$hasSale = false;
$displayPrice = $product['price'];

// Check if we have a sale price (percentage) and actual sale price (calculated amount)
if (!empty($product['sale_price']) && $product['sale_price'] > 0 && !empty($product['actual_sale_price'])) {
    $hasSale = true;
    $displayPrice = $product['actual_sale_price'];
    
    // Debug output to see what's happening
    echo "<!-- Debug: Sale Price (percentage): " . $product['sale_price'] . "% -->";
    echo "<!-- Debug: Actual Sale Price: " . $product['actual_sale_price'] . " -->";
    echo "<!-- Debug: Display Price: " . $displayPrice . " -->";
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

      <!-- ✅ STOCK INFORMATION DISPLAY -->
      <div class="stock-info">
        <?php if ($current_stock > 0): ?>
          <?php if ($current_stock <= 10): ?>
            <div class="stock-low">
              <span class="stock-text">Only <?= $current_stock ?> left in stock!</span>
            </div>
          <?php else: ?>
            <div class="stock-available">
              <span class="stock-text">In Stock (<?= $current_stock ?> available)</span>
            </div>
          <?php endif; ?>
          
          <div class="size-stock-info">
            <h4>Available by Size:</h4>
            <div class="size-stock-grid">
              <?php foreach ($sizes as $size): 
                $size_qty = $stock_by_size[$size] ?? 0;
              ?>
                <div class="size-stock-item">
                  <span class="size-label">Size <?= $size ?>:</span>
                  <span class="size-quantity <?= $size_qty == 0 ? 'out-of-stock' : 'in-stock' ?>">
                    <?= $size_qty > 0 ? $size_qty . ' available' : 'Out of stock' ?>
                  </span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php else: ?>
          <div class="stock-out">
            <span class="stock-icon">❌</span>
            <span class="stock-text">Out of Stock</span>
          </div>
        <?php endif; ?>
      </div>

      <!-- ✅ COLOR SELECTOR -->
      <?php if (!empty($colors)): ?>
        <?php 
          $current_color_id = $current_color['id'] ?? $color_id;
          echo "<!-- Debug: Passing to color-selector - Current Color ID: $current_color_id -->";
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

    <!-- ✅ Size Selection - IMPROVED WITH AUTO-RESET -->
    <div class="size-selector">
        <label>Size:</label>
        <div class="size-options">
            <?php 
            // Always reset to first available size when page loads
            $current_size = 'M'; // Default fallback
            $has_active_size = false;
            
            foreach ($sizes as $size): 
                $size_qty = $stock_by_size[$size] ?? 0;
                $is_disabled = $size_qty == 0;
                
                // Auto-select first available size
                if (!$has_active_size && !$is_disabled) {
                    $is_active = true;
                    $has_active_size = true;
                    $current_size = $size;
                } else {
                    $is_active = false;
                }
            ?>
                <button type="button" 
                        class="size-option <?= $is_active ? 'active' : '' ?> <?= $is_disabled ? 'disabled' : '' ?>" 
                        data-size="<?= $size ?>"
                        data-stock="<?= $size_qty ?>"
                        <?= $is_disabled ? 'disabled' : '' ?>>
                    <?= $size ?>
                    <?php if ($is_disabled): ?>
                        <span class="size-out-of-stock">(X)</span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
            
            <?php if (!$has_active_size): ?>
                <!-- If all sizes are out of stock, show message -->
                <div class="no-sizes-available">
                </div>
            <?php endif; ?>
        </div>
    </div>

      <!-- ✅ Quantity Selector - FIXED TO USE ACTUAL STOCK -->
<div class="quantity-selector <?= $current_stock == 0 ? 'out-of-stock' : '' ?>">
  <label>Quantity:</label>
  <button type="button" class="quantity-btn" id="minus-btn" <?= $current_stock == 0 ? 'disabled' : '' ?>>−</button>
  <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= $current_stock ?>" 
         class="quantity-input" <?= $current_stock == 0 ? 'disabled' : '' ?>>
  <button type="button" class="quantity-btn" id="plus-btn" <?= $current_stock == 0 ? 'disabled' : '' ?>>+</button>
</div>

      <div class="action-buttons">
        <!-- ✅ Add to Cart button with ONLY COLOR ID -->
        <button class="add-to-cart" data-id="<?= $color_id ?>" <?= $current_stock == 0 ? 'disabled' : '' ?>>
          <?= $current_stock == 0 ? 'Out of Stock' : 'Add to Cart' ?>
        </button>
        <button class="wishlist-btn" data-id="<?= $product_id ?>" <?= $current_stock == 0 ? 'disabled' : '' ?>>♡ Add to Wishlist</button>
      </div>

      <!-- ✅ Buy Now Button -->
      <button class="checkout-btn" id="buy-now-btn" 
        data-color-id="<?= $color_id ?>" 
        data-product-id="<?= $product_id ?>"
        data-price="<?= $displayPrice ?>"
        <?= $current_stock == 0 ? 'disabled' : '' ?>>
          <?= $current_stock == 0 ? 'Out of Stock' : 'Buy Now' ?>
      </button>
      
      <!-- Hidden field for selected color -->
      <input type="hidden" id="selected-color-id" value="<?= $color_id ?>">
      <input type="hidden" id="selected-size" value="<?= $current_size ?>">
    </div>
  </div>

  <?php require_once __DIR__ . '/../includes/footer.php'; ?>

  <script src="<?= SITE_URL; ?>js/color-selector.js?v=<?= time() ?>"></script>

  <!-- ✅ Cart action marking script -->
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

        document.querySelectorAll(".add-to-cart, .wishlist-btn, .checkout-btn").forEach(btn => {
            btn.addEventListener("click", markCartAction);
        });
    });
  </script>

  <script src="<?= SITE_URL; ?>js/product.js?v=<?= time() ?>"></script>
</body>
</html>
<?php ob_end_flush(); ?>