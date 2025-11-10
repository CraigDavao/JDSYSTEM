<?php
// checkout.php
ob_start();
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

/* ------------------------------------------------------------
   HELPER FUNCTION: Get product image by color_id
------------------------------------------------------------ */
function getProductImageByColorId($color_id, $conn) {
    if (!$color_id || $color_id <= 0) {
        return SITE_URL . 'uploads/sample1.jpg';
    }
    
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

/* ============================
   Fetch user's default address
   ============================ */
$addr_street = $addr_city = $addr_state = $addr_zip = $addr_country = '';
$has_default_address = false;
$default_address_id = null;

try {
    $addrStmt = $conn->prepare("SELECT id, street, city, state, zip_code, country FROM addresses WHERE user_id = ? AND is_default = 1 LIMIT 1");
    if ($addrStmt) {
        $addrStmt->bind_param("i", $user_id);
        $addrStmt->execute();
        $addrRes = $addrStmt->get_result();
        $addrRow = $addrRes ? $addrRes->fetch_assoc() : null;
        if ($addrRow) {
            $default_address_id = $addrRow['id'];
            $addr_street = htmlspecialchars($addrRow['street'] ?? '');
            $addr_city = htmlspecialchars($addrRow['city'] ?? '');
            $addr_state = htmlspecialchars($addrRow['state'] ?? '');
            $addr_zip = htmlspecialchars($addrRow['zip_code'] ?? '');
            $addr_country = htmlspecialchars($addrRow['country'] ?? '');
            $has_default_address = true;
        }
        $addrStmt->close();
    }
} catch (Exception $e) {
    error_log("❌ Failed to fetch default address for user {$user_id}: " . $e->getMessage());
}

// CHECK FOR CHECKOUT ITEMS
$checkout_items = [];
$totals = ['subtotal' => 0, 'shipping' => 0, 'total' => 0];
$is_buy_now = false;

// Check for buy now product FIRST (takes priority)
if (isset($_SESSION['buy_now_product']) && !empty($_SESSION['buy_now_product'])) {
    $buyNowProduct = $_SESSION['buy_now_product'];
    
    if (isset($buyNowProduct['color_id']) && $buyNowProduct['color_id'] > 0) {
        if (!isset($buyNowProduct['price']) || $buyNowProduct['price'] <= 0) {
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
                
                $buyNowProduct['price'] = $regularPrice;
                if ($actualSale > 0 && $actualSale < $regularPrice) {
                    $buyNowProduct['price'] = $actualSale;
                } elseif ($salePrice > 0 && $salePrice < $regularPrice) {
                    $buyNowProduct['price'] = $salePrice;
                }
            } else {
                $buyNowProduct['price'] = 820.00;
            }
            $priceStmt->close();
        }
        
        if (!isset($buyNowProduct['quantity']) || $buyNowProduct['quantity'] <= 0) {
            $buyNowProduct['quantity'] = 1;
        }
        
        if (!isset($buyNowProduct['size']) || empty($buyNowProduct['size'])) {
            $buyNowProduct['size'] = 'M';
        }
        
        if (!isset($buyNowProduct['color_name']) || empty($buyNowProduct['color_name'])) {
            $colorStmt = $conn->prepare("SELECT color_name FROM product_colors WHERE id = ?");
            $colorStmt->bind_param("i", $buyNowProduct['color_id']);
            $colorStmt->execute();
            $colorResult = $colorStmt->get_result()->fetch_assoc();
            $buyNowProduct['color_name'] = $colorResult['color_name'] ?? 'Default Color';
            $colorStmt->close();
        }
        
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
        
        $buyNowProduct['image'] = getProductImageByColorId($buyNowProduct['color_id'], $conn);
        $buyNowProduct['subtotal'] = $buyNowProduct['price'] * $buyNowProduct['quantity'];
        
        $checkout_items = [$buyNowProduct];
        $is_buy_now = true;
        
        $subtotal = $buyNowProduct['subtotal'];
        $shipping = $subtotal > 500 ? 0 : 50;
        $total = $subtotal + $shipping;
        
        $totals['subtotal'] = $subtotal;
        $totals['shipping'] = $shipping;
        $totals['total'] = $total;
    }
} 
// Check for cart checkout items (regular cart checkout)
else if (isset($_SESSION['checkout_items']) && !empty($_SESSION['checkout_items'])) {
    $placeholders = str_repeat('?,', count($_SESSION['checkout_items']) - 1) . '?';
    
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
        $types = str_repeat('i', count($_SESSION['checkout_items'])) . 'i';
        $params = array_merge($_SESSION['checkout_items'], [$user_id]);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $subtotal = 0;

        while ($row = $result->fetch_assoc()) {
            $image_data = getProductImageByColorId($row['color_id'], $conn);
            
            $displayPrice = (float)$row['cart_price'];
            
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

            if ($displayPrice <= 0) {
                $displayPrice = 820.00;
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
        }
        
        $shipping = $subtotal > 500 ? 0 : 50;
        $total = $subtotal + $shipping;
        
        $totals['subtotal'] = $subtotal;
        $totals['shipping'] = $shipping;
        $totals['total'] = $total;
        
        $stmt->close();
    }
}

// If no items, redirect to cart
if (empty($checkout_items)) {
    $_SESSION['error'] = 'No items to checkout. Please add items to your cart first.';
    header("Location: " . SITE_URL . "pages/cart.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Jolly Dolly</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/checkout.css?v=<?= time(); ?>">
</head>
<body>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div style="text-align: center;">
        <div style="font-size: 24px; margin-bottom: 10px;">⏳</div>
        <div>Processing your order...</div>
    </div>
</div>

<div class="checkout-dashboard">
    <h2>Checkout</h2>
    
    <!-- Messages -->
    <div id="message-container"></div>
    
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

                <div class="address-display-box">
                    <?php if ($has_default_address): ?>
                        <p id="current-address">
                            <?= $addr_street ?>, <?= $addr_city ?>, <?= $addr_state ?>, <?= $addr_zip ?>, <?= $addr_country ?>
                        </p>
                        <input type="hidden" id="selected-address-id" value="<?= $default_address_id ?>">
                    <?php else: ?>
                        <p id="current-address">No default address set. Please add an address to continue.</p>
                        <input type="hidden" id="selected-address-id" value="">
                    <?php endif; ?>
                    <button type="button" id="change-address-btn" class="btn-change-address">Change / Add New Address</button>
                </div>
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
                                 onerror="this.src='<?= SITE_URL ?>uploads/sample1.jpg'">
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

<!-- Address Modal -->
<div id="address-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        
        <!-- Modal Header -->
        <div class="modal-header">
            <h3>Your Addresses</h3>
        </div>
        
        <!-- Modal Body -->
        <div class="modal-body">
            <!-- Address List -->
            <div id="address-list">
                <div class="loading-message">Loading addresses...</div>
            </div>
            
            <!-- Add Address Section -->
            <div class="add-address-section">
                <button id="add-new-address-btn" class="btn-add-address">
                    <span style="font-size: 18px; margin-right: 8px;">+</span>
                    Add New Address
                </button>
                
                <!-- Add Address Form -->
                <div id="add-address-form" style="display:none;">
                    <h4 style="margin-bottom: 25px; color: #2c3e50; font-size: 1.3em; font-weight: 600;">Add New Shipping Address</h4>
                    
                    <!-- Person's Name -->
                    <div class="form-group">
                        <label for="new-fullname">Recipient's Full Name *</label>
                        <input type="text" id="new-fullname" placeholder="Enter recipient's full name" required>
                    </div>

                    <!-- Address Information -->
                    <div class="form-group">
                        <label for="new-street">Street Address *</label>
                        <input type="text" id="new-street" placeholder="House number, street, barangay" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new-city">City/Municipality *</label>
                        <input type="text" id="new-city" placeholder="Enter your city or municipality" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new-state">Province *</label>
                        <input type="text" id="new-state" placeholder="Enter your province" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new-zip">ZIP Code *</label>
                        <input type="text" id="new-zip" placeholder="Enter ZIP code" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new-country">Country *</label>
                        <input type="text" id="new-country" value="Philippines" required>
                    </div>

                    <!-- Default Address Option -->
                    <label class="checkbox-label">
                        <input type="checkbox" id="set-as-default">
                        Set as default shipping address
                    </label>
                    
                    <!-- Form Buttons -->
                    <div class="form-buttons">
                        <button id="save-address-btn" class="btn-save-address">Save Address</button>
                        <button id="cancel-address-btn" type="button" class="btn-cancel-address">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const SITE_URL = "<?= SITE_URL ?>";
const USER_ID = <?= $user_id ?>;
</script>

<script src="<?php echo SITE_URL; ?>js/checkout.js?v=<?= time(); ?>"></script>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
ob_end_flush();
?>