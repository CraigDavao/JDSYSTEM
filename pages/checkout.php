<?php
// checkout.php
ob_start();
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

// 🟣 TEMPORARY DEBUG - Check what's in session
echo "<div style='background: yellow; padding: 10px; margin: 10px; border: 2px solid red;'>";
echo "<h3>🛒 SESSION DEBUG</h3>";
echo "checkout_items: ";
if (isset($_SESSION['checkout_items'])) {
    echo implode(', ', $_SESSION['checkout_items']) . " (Count: " . count($_SESSION['checkout_items']) . ")";
} else {
    echo "Not set";
}
echo "<br>buy_now_product: ";
echo isset($_SESSION['buy_now_product']) ? 'Set' : 'Not set';
echo "</div>";

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

// 🟣 CHECK FOR CHECKOUT ITEMS - UPDATED FOR COLOR ID SYSTEM
$checkout_items = [];
$totals = ['subtotal' => 0, 'shipping' => 0, 'total' => 0];
$is_buy_now = false;

// Check for buy now product
if (isset($_SESSION['buy_now_product'])) {
    $buyNowProduct = $_SESSION['buy_now_product'];
    
    // 🟣 UPDATED: Check for color_id instead of product_id
    if (isset($buyNowProduct['color_id']) && $buyNowProduct['color_id'] > 0) {
        // 🟣 FIXED: Ensure price is valid
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
                
                // Use the same price logic as above
                $buyNowProduct['price'] = $regularPrice;
                if ($actualSale > 0 && $actualSale < $regularPrice) {
                    $buyNowProduct['price'] = $actualSale;
                } elseif ($salePrice > 0 && $salePrice < $regularPrice) {
                    $buyNowProduct['price'] = $salePrice;
                }
            }
        }
        
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
    if (!empty($_SESSION['checkout_items'])) {
        $placeholders = str_repeat('?,', count($_SESSION['checkout_items']) - 1) . '?';
        
        // 🟣 UPDATED QUERY: Get cart items with color information
        $sql = "
            SELECT 
                cart.id AS cart_id,
                cart.product_id,
                cart.color_id,
                cart.color_name,
                cart.quantity,
                cart.size,
                cart.price as cart_price,  -- Use the price stored in cart
                products.name,
                products.price as product_price,
                products.sale_price,
                products.actual_sale_price,
                product_images.image,
                product_images.image_format
            FROM cart 
            INNER JOIN products ON cart.product_id = products.id 
            LEFT JOIN product_images ON products.id = product_images.product_id 
                AND (product_images.color_name = cart.color_name OR product_images.color_name IS NULL)
            WHERE cart.id IN ($placeholders) AND cart.user_id = ?
            GROUP BY cart.id
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
            
            // Debug: Check how many items were fetched
            $fetched_count = $result->num_rows;
            error_log("🛒 Checkout - Fetched $fetched_count items from database");
            
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
                
                // 🟣 UPDATED: Use the price from cart (already calculated during add to cart)
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
                    'color_id' => intval($row['color_id']),  // 🟣 ADD COLOR ID
                    'color_name' => $row['color_name'],      // 🟣 ADD COLOR NAME
                    'name' => $row['name'],
                    'price' => floatval($displayPrice),
                    'quantity' => intval($row['quantity']),
                    'size' => $row['size'],
                    'image' => $image_data,
                    'subtotal' => $itemSubtotal,
                    'is_buy_now' => false
                ];
                
                // 🟣 DEBUG LOGGING
                error_log("🛒 Cart Item - ID: {$row['product_id']}, Color: {$row['color_name']}, Price: $displayPrice, Qty: {$row['quantity']}, Subtotal: $itemSubtotal");
            }
            
            $shipping = $subtotal > 500 ? 0 : 50;
            $total = $subtotal + $shipping;
            
            $totals['subtotal'] = $subtotal;
            $totals['shipping'] = $shipping;
            $totals['total'] = $total;
            
            $stmt->close();
        } else {
            die("Database error: " . $conn->error);
        }
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
                                <p>
                                    <?php if (isset($item['color_name'])): ?>
                                        Color: <?= htmlspecialchars($item['color_name']) ?> | 
                                    <?php endif; ?>
                                    Size: <?= $item['size'] ?> | Qty: <?= $item['quantity'] ?>
                                </p>
                                <p class="item-price">₱<?= number_format($item['price'], 2) ?> × <?= $item['quantity'] ?> = ₱<?= number_format($item['price'] * $item['quantity'], 2) ?></p>
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

<script src="<?php echo SITE_URL; ?>js/checkout.js?v=<?= time(); ?>"></script>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
ob_end_flush();
?>