<?php
ob_start();
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

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

// 🟣 CHECK FOR CHECKOUT ITEMS - FIXED VERSION
$checkout_items = [];
$totals = ['subtotal' => 0, 'shipping' => 0, 'total' => 0];
$is_buy_now = false;

// Check for buy now product
if (isset($_SESSION['buy_now_product'])) {
    $buyNowProduct = $_SESSION['buy_now_product'];
    
    if (isset($buyNowProduct['product_id']) && $buyNowProduct['product_id'] > 0) {
        $checkout_items = [$buyNowProduct];
        $is_buy_now = true;
        
        // Calculate totals
        $subtotal = $buyNowProduct['price'] * $buyNowProduct['quantity'];
        $shipping = $subtotal > 500 ? 0 : 50;
        $total = $subtotal + $shipping;
        
        $totals['subtotal'] = $subtotal;
        $totals['shipping'] = $shipping;
        $totals['total'] = $total;
    }
} 
// Check for cart checkout items
else if (isset($_SESSION['checkout_items']) && !empty($_SESSION['checkout_items'])) {
    $cart_ids = implode(',', array_map('intval', $_SESSION['checkout_items']));
    
    $stmt = $conn->prepare("
        SELECT 
            cart.id AS cart_id,
            cart.product_id,
            products.id, 
            products.name,
            products.price,
            products.sale_price,
            products.actual_sale_price,
            products.description,
            cart.quantity,
            cart.size,
            product_images.image,
            product_images.image_format
        FROM cart 
        INNER JOIN products ON cart.product_id = products.id 
        LEFT JOIN product_images ON products.id = product_images.product_id
        WHERE cart.id IN ($cart_ids) AND cart.user_id = ?
        GROUP BY cart.id
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $subtotal = 0;

    while ($row = $result->fetch_assoc()) {
        if (!isset($row['product_id']) || $row['product_id'] <= 0) {
            continue;
        }

        // Handle blob image
        if (!empty($row['image'])) {
            $mimeType = !empty($row['image_format']) ? $row['image_format'] : 'image/jpeg';
            $image_data = 'data:' . $mimeType . ';base64,' . base64_encode($row['image']);
        } else {
            $image_data = SITE_URL . 'uploads/sample1.jpg';
        }
        
        // Calculate correct price
      // 🟣 Normalize all price fields as float
        $actualSale = isset($row['actual_sale_price']) ? (float)$row['actual_sale_price'] : 0;
        $salePrice  = isset($row['sale_price']) ? (float)$row['sale_price'] : 0;
        $regularPrice = isset($row['price']) ? (float)$row['price'] : 0;

        // 🟣 Determine which price to use
        if ($actualSale > 0) {
            $displayPrice = $actualSale;
        } elseif ($salePrice > 0) {
            $displayPrice = $salePrice;
        } else {
            $displayPrice = $regularPrice;
        }


        
        $itemSubtotal = $displayPrice * $row['quantity'];
        $subtotal += $itemSubtotal;
        
        $checkout_items[] = [
            'cart_id' => $row['cart_id'],
            'product_id' => intval($row['product_id']),
            'name' => $row['name'],
            'price' => floatval($displayPrice),
            'quantity' => intval($row['quantity']),
            'size' => $row['size'],
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
                        data-is-buy-now='<?= $is_buy_now ?>'>
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
                    <?php foreach ($checkout_items as $item): ?>
                        <div class="checkout-item">
                            <img src="<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['name']) ?>" 
                                 onerror="this.src='<?= SITE_URL ?>uploads/sample1.jpg'">
                            <div class="item-info">
                                <h4><?= htmlspecialchars($item['name']) ?></h4>
                                <p>Size: <?= $item['size'] ?> | Qty: <?= $item['quantity'] ?></p>
                                <p class="item-price">₱<?= number_format($item['price'], 2) ?> × <?= $item['quantity'] ?> = ₱<?= number_format($item['subtotal'], 2) ?></p>
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
const SITE_URL = "<?php echo SITE_URL; ?>";
const CHECKOUT_ITEMS = <?= json_encode($checkout_items) ?>;
const ORDER_TOTALS = <?= json_encode($totals) ?>;
const IS_BUY_NOW = <?= $is_buy_now ? 'true' : 'false'; ?>;
</script>

<script src="<?php echo SITE_URL; ?>js/checkout.js?v=<?= time(); ?>"></script>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
ob_end_flush();
?>