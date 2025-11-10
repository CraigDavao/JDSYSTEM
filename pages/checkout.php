<?php
// checkout.php
ob_start();
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

/* ------------------------------------------------------------
   HELPER FUNCTION: Get product image by color_id - ULTRA SIMPLIFIED
------------------------------------------------------------ */
function getProductImageByColorId($color_id, $conn) {
    if (!$color_id || $color_id <= 0) {
        return SITE_URL . 'uploads/sample1.jpg';
    }
    
    // ULTRA SIMPLIFIED: Just get ANY image for this product via color_id
    $sql = "
        SELECT pi.image, pi.image_format 
        FROM product_images pi 
        INNER JOIN product_colors pc ON pi.product_id = pc.product_id 
        WHERE pc.id = ? 
        AND pi.image IS NOT NULL 
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("❌ FAILED to prepare image query for color_id: $color_id");
        return SITE_URL . 'uploads/sample1.jpg';
    }
    
    $stmt->bind_param("i", $color_id);
    
    if (!$stmt->execute()) {
        error_log("❌ FAILED to execute image query for color_id: $color_id");
        $stmt->close();
        return SITE_URL . 'uploads/sample1.jpg';
    }
    
    $result = $stmt->get_result();
    
    if (!$result) {
        error_log("❌ FAILED to get result for color_id: $color_id");
        $stmt->close();
        return SITE_URL . 'uploads/sample1.jpg';
    }
    
    $imageData = $result->fetch_assoc();
    $stmt->close();
    
    if (!empty($imageData['image'])) {
        $mimeType = !empty($imageData['image_format']) ? $imageData['image_format'] : 'image/jpeg';
        $imageBase64 = base64_encode($imageData['image']);
        $imageUrl = 'data:' . $mimeType . ';base64,' . $imageBase64;
        
        error_log("✅ SUCCESS: Found image for color_id: $color_id, Type: $mimeType, Size: " . strlen($imageBase64));
        return $imageUrl;
    }
    
    error_log("❌ NO IMAGE: No image found for color_id: $color_id, using fallback");
    return SITE_URL . 'uploads/sample1.jpg';
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: " . SITE_URL . "auth/login.php");
    exit;
}

// Initialize user data with safe defaults
$fullname = '';
$email = '';
$phone = '';

// Get user details
$user_id = $_SESSION['user_id'];
try {
    $stmt = $conn->prepare("SELECT fullname, email, number FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_result = $stmt->get_result();
        $user = $user_result->fetch_assoc();

        if ($user) {
            $fullname = htmlspecialchars($user['fullname'] ?? '');
            $email = htmlspecialchars($user['email'] ?? '');
            $phone = htmlspecialchars($user['number'] ?? '');
        }
        $stmt->close();
    }
} catch (Exception $e) {
    // Silently continue with default values
}

// 🟣 CHECK FOR CHECKOUT ITEMS - ULTRA RELIABLE VERSION
$checkout_items = [];
$totals = ['subtotal' => 0, 'shipping' => 0, 'total' => 0];
$is_buy_now = false;

error_log("=== CHECKOUT START == User: $user_id ===");

// Check for buy now product FIRST (takes priority)
if (isset($_SESSION['buy_now_product']) && !empty($_SESSION['buy_now_product'])) {
    $buyNowProduct = $_SESSION['buy_now_product'];
    
    error_log("🛒 BUY NOW DETECTED: " . print_r($buyNowProduct, true));
    
    // 🟣 VALIDATE BUY NOW PRODUCT DATA
    if (isset($buyNowProduct['color_id']) && $buyNowProduct['color_id'] > 0) {
        // 🟣 ENSURE ALL REQUIRED FIELDS ARE PRESENT
        if (!isset($buyNowProduct['price']) || $buyNowProduct['price'] <= 0) {
            // Fetch the correct price from database via color_id
            $priceStmt = $conn->prepare("
                SELECT p.price, p.sale_price, p.actual_sale_price 
                FROM products p 
                INNER JOIN product_colors pc ON p.id = pc.product_id 
                WHERE pc.id = ?
            ");
            $priceStmt->bind_param("i", $buyNowProduct['color_id']);
            $priceStmt->execute();
            $priceResult = $priceStmt->get_result()->fetch_assoc();
            
            if ($priceResult) {
                $regularPrice = (float)$priceResult['price'];
                $salePrice = (float)$priceResult['sale_price'];
                $actualSale = (float)$priceResult['actual_sale_price'];
                
                // Use the same price logic as product page
                $buyNowProduct['price'] = $regularPrice;
                if ($actualSale > 0 && $actualSale < $regularPrice) {
                    $buyNowProduct['price'] = $actualSale;
                } elseif ($salePrice > 0 && $salePrice < $regularPrice) {
                    $buyNowProduct['price'] = $salePrice;
                }
            } else {
                // Fallback price if product not found
                $buyNowProduct['price'] = 820.00;
            }
            $priceStmt->close();
        }
        
        // 🟣 ENSURE QUANTITY IS VALID
        if (!isset($buyNowProduct['quantity']) || $buyNowProduct['quantity'] <= 0) {
            $buyNowProduct['quantity'] = 1;
        }
        
        // 🟣 ENSURE SIZE IS SET
        if (!isset($buyNowProduct['size']) || empty($buyNowProduct['size'])) {
            $buyNowProduct['size'] = 'M';
        }
        
        // 🟣 ENSURE COLOR NAME IS SET - FETCH FROM DATABASE
        if (!isset($buyNowProduct['color_name']) || empty($buyNowProduct['color_name'])) {
            $colorStmt = $conn->prepare("SELECT color_name FROM product_colors WHERE id = ?");
            $colorStmt->bind_param("i", $buyNowProduct['color_id']);
            $colorStmt->execute();
            $colorResult = $colorStmt->get_result()->fetch_assoc();
            $buyNowProduct['color_name'] = $colorResult['color_name'] ?? 'Default Color';
            $colorStmt->close();
        }
        
        // 🟣 ENSURE PRODUCT NAME IS SET - FETCH FROM DATABASE
        if (!isset($buyNowProduct['name']) || empty($buyNowProduct['name'])) {
            $nameStmt = $conn->prepare("
                SELECT p.name 
                FROM products p 
                INNER JOIN product_colors pc ON p.id = pc.product_id 
                WHERE pc.id = ?
            ");
            $nameStmt->bind_param("i", $buyNowProduct['color_id']);
            $nameStmt->execute();
            $nameResult = $nameStmt->get_result()->fetch_assoc();
            $buyNowProduct['name'] = $nameResult['name'] ?? 'Product';
            $nameStmt->close();
        }
        
        // 🟣 CRITICAL FIX: ALWAYS FETCH FRESH IMAGE FOR BUY NOW
        $buyNowProduct['image'] = getProductImageByColorId($buyNowProduct['color_id'], $conn);
        error_log("🖼️ BUY NOW IMAGE: Color ID {$buyNowProduct['color_id']} -> " . 
                 (strpos($buyNowProduct['image'], 'data:') === 0 ? 'Base64 Image' : 'Fallback Image'));
        
        // 🟣 CALCULATE SUBTOTAL
        $buyNowProduct['subtotal'] = $buyNowProduct['price'] * $buyNowProduct['quantity'];
        
        $checkout_items = [$buyNowProduct];
        $is_buy_now = true;
        
        // Calculate totals
        $subtotal = $buyNowProduct['subtotal'];
        $shipping = $subtotal > 500 ? 0 : 50;
        $total = $subtotal + $shipping;
        
        $totals['subtotal'] = $subtotal;
        $totals['shipping'] = $shipping;
        $totals['total'] = $total;
        
        error_log("✅ Buy Now Checkout - Product: {$buyNowProduct['name']}, Color: {$buyNowProduct['color_name']}, Size: {$buyNowProduct['size']}, Price: {$buyNowProduct['price']}, Qty: {$buyNowProduct['quantity']}");
    }
} 
// Check for cart checkout items (regular cart checkout)
else if (isset($_SESSION['checkout_items']) && !empty($_SESSION['checkout_items'])) {
    $placeholders = str_repeat('?,', count($_SESSION['checkout_items']) - 1) . '?';
    
    error_log("🛒 CART CHECKOUT DETECTED: " . count($_SESSION['checkout_items']) . " items");
    
    // 🟣 SIMPLIFIED QUERY: Only get basic cart data
    $sql = "
        SELECT 
            cart.id AS cart_id,
            cart.product_id,
            cart.color_id,
            cart.color_name,
            cart.quantity,
            cart.size,
            cart.price as cart_price,
            products.name,
            products.price as product_price,
            products.sale_price,
            products.actual_sale_price
        FROM cart 
        INNER JOIN products ON cart.product_id = products.id 
        WHERE cart.id IN ($placeholders) AND cart.user_id = ?
        ORDER BY cart.id
    ";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // Bind parameters dynamically
        $types = str_repeat('i', count($_SESSION['checkout_items'])) . 'i';
        $params = array_merge($_SESSION['checkout_items'], [$user_id]);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $subtotal = 0;
        $item_count = 0;

        while ($row = $result->fetch_assoc()) {
            $item_count++;
            if (!isset($row['product_id']) || $row['product_id'] <= 0) {
                error_log("❌ INVALID CART ITEM: No product_id");
                continue;
            }

            // 🟣 CRITICAL FIX: ALWAYS FETCH FRESH IMAGE FOR EACH CART ITEM
            $image_data = getProductImageByColorId($row['color_id'], $conn);
            error_log("🖼️ CART ITEM #$item_count: Color ID {$row['color_id']} -> " . 
                     (strpos($image_data, 'data:') === 0 ? 'Base64 Image' : 'Fallback Image'));
            
            // 🟣 Use the price from cart (already calculated during add to cart)
            $displayPrice = (float)$row['cart_price'];
            
            // 🟣 Safety check: if displayPrice is 0, calculate from product prices
            if ($displayPrice <= 0) {
                $regularPrice = (float)$row['product_price'];
                $salePrice = (float)$row['sale_price'];
                $actualSale = (float)$row['actual_sale_price'];

                $displayPrice = $regularPrice;
                if ($actualSale > 0 && $actualSale < $regularPrice) {
                    $displayPrice = $actualSale;
                } elseif ($salePrice > 0 && $salePrice < $regularPrice) {
                    $displayPrice = $salePrice;
                }
            }

            // 🟣 FIX: Ensure price is never 0
            if ($displayPrice <= 0) {
                error_log("❌ ERROR: Product {$row['product_id']} has price 0. Using fallback.");
                $displayPrice = 820.00; // Fallback price
            }

            $itemSubtotal = $displayPrice * $row['quantity'];
            $subtotal += $itemSubtotal;
            
            $checkout_items[] = [
                'cart_id' => $row['cart_id'],
                'product_id' => intval($row['product_id']),
                'color_id' => intval($row['color_id']),
                'color_name' => $row['color_name'] ?? 'Default Color',
                'name' => $row['name'],
                'price' => floatval($displayPrice),
                'quantity' => intval($row['quantity']),
                'size' => $row['size'] ?? 'M',
                'image' => $image_data,
                'subtotal' => $itemSubtotal,
                'is_buy_now' => false
            ];
            
            error_log("🛒 Cart Checkout Item - ID: {$row['product_id']}, Color: {$row['color_name']}, Size: {$row['size']}, Price: $displayPrice, Qty: {$row['quantity']}, Image: " . (!empty($image_data) ? 'Found' : 'Not found'));
        }
        
        $shipping = $subtotal > 500 ? 0 : 50;
        $total = $subtotal + $shipping;
        
        $totals['subtotal'] = $subtotal;
        $totals['shipping'] = $shipping;
        $totals['total'] = $total;
        
        error_log("📊 CART TOTALS: Subtotal: $subtotal, Shipping: $shipping, Total: $total");
        
        $stmt->close();
    } else {
        error_log("❌ DATABASE ERROR: " . $conn->error);
        die("Database error: " . $conn->error);
    }
}

// If no items, redirect to cart
if (empty($checkout_items)) {
    error_log("❌ NO CHECKOUT ITEMS: Redirecting to cart");
    $_SESSION['error'] = 'No items to checkout. Please add items to your cart first.';
    header("Location: " . SITE_URL . "pages/cart.php");
    exit;
}

error_log("✅ CHECKOUT READY: " . count($checkout_items) . " items, Total: ₱" . $totals['total']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Jolly Dolly</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/checkout.css?v=<?= time(); ?>">
    <style>
        .buy-now-notice {
            background: #e3f2fd;
            border: 2px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
            font-weight: bold;
            color: #1976d2;
            font-size: 16px;
        }
        .color-badge {
            display: inline-block;
            background: #f0f0f0;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 5px;
        }
        .size-badge {
            display: inline-block;
            background: #e8f5e8;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 5px;
        }
        
        /* Improved image styling */
        .checkout-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
            background-color: #f8f8f8;
        }
        
        /* Image error handling */
        .checkout-item img[src*='sample1.jpg'] {
            border: 2px dashed #ccc;
        }
    </style>
</head>
<body>

<div class="checkout-dashboard">
    <h2>Checkout</h2>
    
    <!-- Buy Now Notice -->
    <?php if ($is_buy_now): ?>
    <div id="buy-now-notice" class="buy-now-notice">
        ⚡ You are purchasing this item directly (Buy Now)
    </div>
    <?php endif; ?>
    
    <div class="checkout-container">
        <!-- Left Column: Shipping & Payment -->
        <div class="checkout-form">
            <!-- Shipping Information -->
            <div class="checkout-section">
                <h3>🚚 Shipping Information</h3>
                <form id="shipping-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fullname">Full Name *</label>
                            <input type="text" id="fullname" name="fullname" value="<?= $fullname ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" value="<?= $email ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" value="<?= $phone ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="address">Address *</label>
                            <input type="text" id="address" name="address" placeholder="Street Address" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City *</label>
                            <input type="text" id="city" name="city" placeholder="City" required>
                        </div>
                        <div class="form-group">
                            <label for="province">Province *</label>
                            <input type="text" id="province" name="province" placeholder="Province" required>
                        </div>
                        <div class="form-group">
                            <label for="zipcode">ZIP Code *</label>
                            <input type="text" id="zipcode" name="zipcode" placeholder="ZIP Code" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Delivery Notes (Optional)</label>
                        <textarea id="notes" name="notes" placeholder="Any special delivery instructions..."></textarea>
                    </div>
                </form>
            </div>

            <!-- Payment Method -->
            <div class="checkout-section">
                <h3>💳 Payment Method</h3>
                <div class="payment-methods">
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="cod" checked>
                        <span class="payment-label">
                            <strong>Cash on Delivery (COD)</strong>
                            <small>Pay when you receive your order</small>
                        </span>
                    </label>
                    
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="gcash">
                        <span class="payment-label">
                            <strong>GCash</strong>
                            <small>Pay using GCash mobile app</small>
                        </span>
                    </label>
                </div>
            </div>

            <!-- Place Order Button -->
            <div class="checkout-actions">
                <button type="button" id="place-order-btn" class="btn-place-order" 
                        data-items='<?= json_encode($checkout_items) ?>'
                        data-totals='<?= json_encode($totals) ?>'
                        data-is-buy-now='<?= $is_buy_now ? "1" : "0" ?>'>
                    Place Order - ₱<?= number_format($totals['total'], 2) ?>
                </button>
                <a href="<?php echo SITE_URL; ?>pages/cart.php" class="btn-back-cart">← Back to Cart</a>
            </div>
        </div>

        <!-- Right Column: Order Summary -->
        <div class="order-summary">
            <h3>Order Summary</h3>
            <div id="checkout-items">
                <?php if (!empty($checkout_items)): ?>
                    <?php foreach ($checkout_items as $index => $item): ?>
                        <div class="checkout-item" data-item-index="<?= $index ?>">
                            <img src="<?= $item['image'] ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                 data-color-id="<?= $item['color_id'] ?>"
                                 onerror="this.src='<?= SITE_URL ?>uploads/sample1.jpg'; console.log('Image failed for color <?= $item['color_id'] ?>')">
                            <div class="item-info">
                                <h4><?= htmlspecialchars($item['name']) ?></h4>
                                <p>
                                    <span class="color-badge">🎨 <?= htmlspecialchars($item['color_name'] ?? 'Default Color') ?></span> | 
                                    <span class="size-badge">📏 <?= htmlspecialchars($item['size'] ?? 'M') ?></span> | 
                                    Qty: <?= $item['quantity'] ?>
                                </p>
                                <p class="item-price">
                                    ₱<?= number_format($item['price'], 2) ?> × <?= $item['quantity'] ?> = 
                                    <strong>₱<?= number_format($item['price'] * $item['quantity'], 2) ?></strong>
                                </p>
                                <?php if ($is_buy_now): ?>
                                    <p style="color: #2196f3; font-size: 12px; margin-top: 5px;">⚡ Buy Now Item</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-items">No items in checkout</div>
                <?php endif; ?>
            </div>
            <div id="order-totals">
                <div class="totals-breakdown">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>₱<?= number_format($totals['subtotal'], 2) ?></span>
                    </div>
                    <div class="total-row">
                        <span>Shipping:</span>
                        <span><?= $totals['shipping'] === 0 ? 'FREE' : '₱' . number_format($totals['shipping'], 2) ?></span>
                    </div>
                    <div class="total-row grand-total">
                        <span>Total:</span>
                        <span>₱<?= number_format($totals['total'], 2) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const SITE_URL = "<?= SITE_URL ?>";
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  console.log('Checkout page loaded successfully');
  
  // Enhanced image error handling
  document.querySelectorAll('.checkout-item img').forEach(img => {
    img.addEventListener('error', function() {
      console.log('Image error detected for color ID:', this.dataset.colorId);
      this.src = SITE_URL + 'uploads/sample1.jpg';
    });
    
    img.addEventListener('load', function() {
      console.log('Image loaded successfully for color ID:', this.dataset.colorId);
    });
  });

  // Debug: Check all images
  console.log('=== CHECKOUT IMAGES DEBUG ===');
  document.querySelectorAll('.checkout-item img').forEach((img, index) => {
    console.log(`Image ${index + 1}:`, {
      src: img.src.substring(0, 100) + '...',
      colorId: img.dataset.colorId,
      alt: img.alt
    });
  });
});
</script>

<script>
// Keep your existing session storage code
document.addEventListener('DOMContentLoaded', () => {
  const productId = <?= json_encode($product_id) ?>;

  // Restore previous selections (if exist)
  const savedData = JSON.parse(sessionStorage.getItem(`product_${productId}`) || "{}");

  if (savedData.colorId) {
    const colorBtn = document.querySelector(`[data-color-id="${savedData.colorId}"]`);
    if (colorBtn) colorBtn.click();
  }

  if (savedData.size) {
    const sizeBtn = document.querySelector(`[data-size="${savedData.size}"]`);
    if (sizeBtn) sizeBtn.click();
  }

  if (savedData.quantity) {
    const qtyInput = document.querySelector('#quantity');
    if (qtyInput) qtyInput.value = savedData.quantity;
  }

  // Save selections whenever user changes them
  document.querySelectorAll('[data-color-id]').forEach(btn => {
    btn.addEventListener('click', () => {
      saveSelection('colorId', btn.dataset.colorId);
    });
  });

  document.querySelectorAll('[data-size]').forEach(btn => {
    btn.addEventListener('click', () => {
      saveSelection('size', btn.dataset.size);
    });
  });

  const qtyInput = document.querySelector('#quantity');
  if (qtyInput) {
    qtyInput.addEventListener('input', () => {
      saveSelection('quantity', qtyInput.value);
    });
  }

  function saveSelection(key, value) {
    const current = JSON.parse(sessionStorage.getItem(`product_${productId}`) || "{}");
    current[key] = value;
    sessionStorage.setItem(`product_${productId}`, JSON.stringify(current));
  }

  sessionStorage.removeItem(`product_${productId}`);
});
</script>

<script src="<?php echo SITE_URL; ?>js/checkout.js?v=<?= time(); ?>"></script>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
ob_end_flush();
?>