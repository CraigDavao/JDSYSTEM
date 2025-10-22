<?php
    // Start output buffering at the very beginning
    ob_start();

    require_once __DIR__ . '/../connection/connection.php';
    require_once __DIR__ . '/../includes/header.php';

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        // Clear any output that might have been sent
        ob_end_clean();
        header("Location: " . SITE_URL . "auth/login.php");
        exit;
    }

    // Get checkout items from session
    $checkout_items = isset($_SESSION['checkout_items']) ? $_SESSION['checkout_items'] : [];

    if (empty($checkout_items)) {
        ob_end_clean();
        header("Location: " . SITE_URL . "pages/cart.php");
        exit;
    }

    // Get user details with proper error handling
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT fullname, email, number FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user = $user_result->fetch_assoc();

    // Initialize user data with safe defaults
    $fullname = '';
    $email = '';
    $phone = '';

    if ($user) {
        $fullname = htmlspecialchars($user['fullname'] ?? '');
        $email = htmlspecialchars($user['email'] ?? '');
        $phone = htmlspecialchars($user['number'] ?? '');
    }

    // Now we can safely output content
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
                    
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="card">
                        <span class="payment-label">
                            <strong>Credit/Debit Card</strong>
                            <small>Pay with your card securely</small>
                        </span>
                    </label>
                </div>
            </div>

            <!-- Place Order Button -->
            <div class="checkout-actions">
                <button type="button" id="place-order-btn" class="btn-place-order">
                    Place Order
                </button>
                <a href="<?php echo SITE_URL; ?>pages/cart.php" class="btn-back-cart">← Back to Cart</a>
            </div>
        </div>

        <!-- Right Column: Order Summary -->
        <div class="order-summary">
            <h3>Order Summary</h3>
            <div id="checkout-items">
                <!-- Items will be loaded via JavaScript -->
            </div>
            <div id="order-totals">
                <!-- Totals will be calculated via JavaScript -->
            </div>
        </div>
    </div>
</div>

<script src="<?php echo SITE_URL; ?>js/checkout.js?v=<?= time(); ?>"></script>

<script>
const SITE_URL = "<?php echo SITE_URL; ?>";
</script>

<?php 
    require_once __DIR__ . '/../includes/footer.php'; 

    ob_end_flush();
?>