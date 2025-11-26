<?php
ob_start();
require_once 'config.php';
require_once 'connection/connection.php';
require_once __DIR__ . '/includes/header.php';

// Authentication and session handling
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $conn->prepare("SELECT id, fullname, email, remember_token FROM users");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if (password_verify($token, $row['remember_token'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['fullname'];
            $_SESSION['user_email'] = $row['email'];
            ob_end_clean();
            header("Location: " . SITE_URL . "dashboard.php");
            exit();
        }
    }
}

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: " . SITE_URL . "index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_data = [];
$addresses = [];
$orders = [];
$wishlist_count = 0;
$wishlist_items = [];

// Include the handlers file
require_once __DIR__ . '/includes/dashboard-handlers.php';

// Return address constant
define('RETURN_ADDRESS', 'Blk2 Lot13 Phase 1M Kasiglahan Village San Jose Rodriguez (Montalban) Rizal, 1860');

// Helper function to get product image by product ID
function getProductImage($productId, $conn) {
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['image'];
    }
    
    return null;
}

// Helper function to get product color-specific image
function getProductColorImage($productId, $colorName, $conn) {
    $stmt = $conn->prepare("SELECT image FROM product_images WHERE product_id = ? AND color_name = ? ORDER BY sort_order LIMIT 1");
    $stmt->bind_param("is", $productId, $colorName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['image'];
    }
    
    return null;
}

// Helper function to get order status history
function getOrderStatusHistory($orderId, $conn) {
    $stmt = $conn->prepare("SELECT status, notes, created_at FROM order_status_history WHERE order_id = ? ORDER BY created_at ASC");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    return $history;
}

// Helper function to get order items with complete details
function getOrderItems($orderId, $conn) {
    $stmt = $conn->prepare("
        SELECT oi.*, p.image as product_image, p.sale_price as product_sale_price
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    return $items;
}

// Helper function to check if order receipt exists
function hasOrderReceipt($orderId, $conn) {
    $stmt = $conn->prepare("SELECT id FROM order_receipts WHERE order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Helper function to check if feedback exists for order item
function hasFeedbackForOrderItem($orderId, $productId, $userId, $conn) {
    $stmt = $conn->prepare("SELECT id FROM product_feedback WHERE order_id = ? AND product_id = ? AND user_id = ?");
    $stmt->bind_param("iii", $orderId, $productId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Handle order receipt confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_receipt'])) {
        $order_id = intval($_POST['order_id']);
        
        // Check if order belongs to user and is delivered
        $stmt = $conn->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        
        if ($order && $order['status'] === 'delivered') {
            // Check if receipt already exists
            if (!hasOrderReceipt($order_id, $conn)) {
                $stmt = $conn->prepare("INSERT INTO order_receipts (order_id, user_id, receipt_confirmed) VALUES (?, ?, 1)");
                $stmt->bind_param("ii", $order_id, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Order received confirmed successfully! You can now provide feedback for your products.";
                } else {
                    $_SESSION['error_message'] = "Failed to confirm order receipt.";
                }
            } else {
                $_SESSION['error_message'] = "Order already confirmed as received.";
            }
        } else {
            $_SESSION['error_message'] = "This order cannot be confirmed as received.";
        }
        
        header("Location: " . SITE_URL . "dashboard.php");
        exit();
    }
    
   // Handle mark as shipped for returns
    if (isset($_POST['mark_return_shipped'])) {
        $return_id = intval($_POST['return_id']);
        
        // Check if return belongs to user and is approved
        $stmt = $conn->prepare("SELECT id, status FROM return_requests WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $return_id, $user_id);
        $stmt->execute();
        $return_request = $stmt->get_result()->fetch_assoc();
        
        if ($return_request && $return_request['status'] === 'approved') {
            $stmt = $conn->prepare("UPDATE return_requests SET status = 'shipped', updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $return_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Thank you for shipping the item! Seller is now waiting for your return package.";
            } else {
                $_SESSION['error_message'] = "Failed to update return status.";
            }
        } else {
            $_SESSION['error_message'] = "Return request not found or not approved.";
        }
        
        header("Location: " . SITE_URL . "dashboard.php");
        exit();
    }
    
    // Handle product feedback submission
    if (isset($_POST['submit_feedback'])) {
        $order_id = intval($_POST['order_id']);
        $product_id = intval($_POST['product_id']);
        $rating = intval($_POST['rating']);
        $comment = trim($_POST['comment']);
        
        // Check if order receipt exists and user can provide feedback
        $stmt = $conn->prepare("SELECT ore.id FROM order_receipts ore 
                               JOIN orders o ON ore.order_id = o.id 
                               WHERE ore.order_id = ? AND ore.user_id = ? AND o.status = 'delivered'");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $receipt = $stmt->get_result()->fetch_assoc();
        
        if ($receipt && !hasFeedbackForOrderItem($order_id, $product_id, $user_id, $conn)) {
            $stmt = $conn->prepare("INSERT INTO product_feedback (order_id, product_id, user_id, rating, comment, is_approved) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->bind_param("iiiis", $order_id, $product_id, $user_id, $rating, $comment);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Thank you for your feedback! Your review will be visible after approval.";
            } else {
                $_SESSION['error_message'] = "Failed to submit feedback.";
            }
        } else {
            $_SESSION['error_message'] = "Unable to submit feedback. Please make sure you have confirmed order receipt and haven't already reviewed this product.";
        }
        
        header("Location: " . SITE_URL . "dashboard.php");
        exit();
    }
    
    // Handle cancellation and return requests
    if (isset($_POST['cancel_order'])) {
        $order_id = intval($_POST['order_id']);
        $reason = trim($_POST['cancel_reason']);
        
        // Check if order belongs to user and can be cancelled
        $stmt = $conn->prepare("SELECT id, status, can_cancel FROM orders WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        
        if ($order && $order['can_cancel'] && in_array($order['status'], ['pending', 'processing'])) {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Update order status
                $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                
                // Add to status history
                $stmt = $conn->prepare("INSERT INTO order_status_history (order_id, status, notes) VALUES (?, 'cancelled', ?)");
                $stmt->bind_param("is", $order_id, $reason);
                $stmt->execute();
                
                // Insert cancellation record
                $stmt = $conn->prepare("INSERT INTO order_cancellations (order_id, user_id, reason, status) VALUES (?, ?, ?, 'approved')");
                $stmt->bind_param("iis", $order_id, $user_id, $reason);
                $stmt->execute();
                
                $conn->commit();
                $_SESSION['success_message'] = "Order cancelled successfully!";
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error_message'] = "Failed to cancel order: " . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = "This order cannot be cancelled.";
        }
        
        header("Location: " . SITE_URL . "dashboard.php");
        exit();
    }
    
    if (isset($_POST['return_request'])) {
        $order_id = intval($_POST['order_id']);
        $reason = trim($_POST['return_reason']);
        $return_type = $_POST['return_type'];
        $item_condition = trim($_POST['item_condition']);
        $description = trim($_POST['return_description']);
        
        // Check if order belongs to user and can be returned
        $stmt = $conn->prepare("SELECT id, total_amount, status, can_return FROM orders WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        
        if ($order && $order['can_return'] && $order['status'] === 'delivered') {
            $stmt = $conn->prepare("INSERT INTO return_requests (order_id, user_id, reason, return_type, item_condition, description, refund_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("iissssd", $order_id, $user_id, $reason, $return_type, $item_condition, $description, $order['total_amount']);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Return request submitted successfully! We will review your request within 24-48 hours.";
            } else {
                $_SESSION['error_message'] = "Failed to submit return request.";
            }
        } else {
            $_SESSION['error_message'] = "This order cannot be returned.";
        }
        
        header("Location: " . SITE_URL . "dashboard.php");
        exit();
    }
    
    // Handle reorder
    if (isset($_POST['reorder'])) {
        $order_id = intval($_POST['order_id']);
        
        // Get order items
        $items = getOrderItems($order_id, $conn);
        
        if (!empty($items)) {
            foreach ($items as $item) {
                // Add to cart (you'll need to implement your cart addition logic)
                // This is a simplified example
                $cart_item = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'color_name' => $item['color_name'],
                    'size_name' => $item['size_name']
                ];
                
                // You'll need to implement your cart session logic here
                $_SESSION['cart'][] = $cart_item;
            }
            
            $_SESSION['success_message'] = "Items added to cart successfully!";
            header("Location: " . SITE_URL . "pages/cart.php");
            exit();
        } else {
            $_SESSION['error_message'] = "No items found to reorder.";
            header("Location: " . SITE_URL . "dashboard.php");
            exit();
        }
    }
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Account | Jolly Dolly</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/profile.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/details.css?v=<?= time(); ?>">
  <style>
    /* Return Instructions Styles */
    .return-instructions {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin: 15px 0;
        border-left: 4px solid #007bff;
    }

    .return-instructions h4 {
        color: #007bff;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .instructions-content ol,
    .instructions-content ul {
        margin: 10px 0;
        padding-left: 20px;
    }

    .instructions-content li {
        margin-bottom: 8px;
        line-height: 1.5;
    }

    .return-address {
        background: white;
        padding: 15px;
        border-radius: 6px;
        margin: 15px 0;
        border: 1px solid #e0e0e0;
        font-family: monospace;
        line-height: 1.6;
    }

    .shipping-notes {
        background: #fff3cd;
        padding: 15px;
        border-radius: 6px;
        margin: 15px 0;
        border: 1px solid #ffeaa7;
    }

    .mark-shipped-form {
        margin-top: 20px;
        text-align: center;
    }

    .shipped-btn {
        background: #28a745;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 16px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: background 0.3s;
    }

    .shipped-btn:hover {
        background: #218838;
    }

    .return-status-update {
        background: #d4edda;
        color: #155724;
        padding: 15px;
        border-radius: 6px;
        margin: 15px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Update existing return status colors */
    .request-status.status-approved {
        color: #28a745;
        font-weight: bold;
    }

    .request-status.status-shipped {
        color: #17a2b8;
        font-weight: bold;
    }

    .request-status.status-item_received {
        color: #6c757d;
        font-weight: bold;
    }

    .request-status.status-refund_processed {
        color: #28a745;
        font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="account-wrapper">
    <nav class="account-sidebar">
      <div class="user-info">
        <h3>Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h3>
        <p>Manage your account</p>
      </div>
      <ul class="sidebar-menu">
        <li class="menu-item active" data-section="overview">
          <i class="fas fa-user-circle"></i>
          <span>Account Overview</span>
        </li>
        <li class="menu-item" data-section="addresses">
          <i class="fas fa-map-marker-alt"></i>
          <span>Address Book</span>
          <?php if (count($addresses) > 0): ?>
            <span class="item-count"><?php echo count($addresses); ?></span>
          <?php endif; ?>
        </li>
        <li class="menu-item" data-section="wishlist">
          <i class="fas fa-heart"></i>
          <span>Wishlist</span>
          <?php if ($wishlist_count > 0): ?>
            <span class="item-count"><?php echo $wishlist_count; ?></span>
          <?php endif; ?>
        </li>
        <li class="menu-item" data-section="orders">
          <i class="fas fa-shopping-bag"></i>
          <span>Order History</span>
          <?php if (count($orders) > 0): ?>
            <span class="item-count"><?php echo count($orders); ?></span>
          <?php endif; ?>
        </li>
        <!-- NEW: My Reviews Menu Item -->
        <li class="menu-item" data-section="feedback">
          <i class="fas fa-star"></i>
          <span>My Reviews</span>
          <?php 
          // Count pending feedback opportunities
          $pending_feedback = 0;
          foreach ($orders as $order) {
              if ($order['status'] === 'delivered' && hasOrderReceipt($order['id'], $conn)) {
                  // Count items without feedback
                  $items = getOrderItems($order['id'], $conn);
                  foreach ($items as $item) {
                      if (!hasFeedbackForOrderItem($order['id'], $item['product_id'], $user_id, $conn)) {
                          $pending_feedback++;
                      }
                  }
              }
          }
          if ($pending_feedback > 0): ?>
            <span class="item-count"><?php echo $pending_feedback; ?></span>
          <?php endif; ?>
        </li>
        <li class="menu-item logout-link">
          <a href="<?php echo SITE_URL; ?>auth/logout.php">
            <i class="fas fa-sign-out-alt"></i>
            <span>Sign Out</span>
          </a>
        </li>
      </ul>
    </nav>

    <main class="account-main">
      <?php if (isset($_SESSION['success_message'])): ?>
        <div class="message success">
          <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
      <?php endif; ?>
      <?php if (isset($_SESSION['error_message'])): ?>
        <div class="message error">
          <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
      <?php endif; ?>

      <div class="page-header">
        <h1>Account Dashboard</h1>
        <p class="welcome-text">
          Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! 
          <span class="logout-hint">
            Not you? <a href="<?php echo SITE_URL; ?>auth/logout.php" class="signout-link">Sign out</a>
          </span>
        </p>
      </div>

      <div class="content-sections">
        <!-- Overview Section - YOUR EXISTING CODE -->
        <div class="section active" id="overview">
          <?php if (count($orders) === 0): ?>
          <div class="promo-card">
            <div class="promo-icon">
              <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="promo-content">
              <h3>Ready for your first order?</h3>
              <p>Discover our latest collections and start shopping today.</p>
              <a href="<?php echo SITE_URL; ?>pages/new.php" class="promo-button">Start Shopping</a>
            </div>
          </div>
          <?php endif; ?>

          <div class="info-section">
            <div class="section-title">
              <h2>Account Information</h2>
              <button class="edit-button" onclick="openEditModal()">
                <i class="fas fa-edit"></i> Edit Profile
              </button>
            </div>
            <div class="info-panel" id="accountInfoPanel">
              <div class="info-line">
                <div class="label">Full Name</div>
                <div class="value"><?php echo htmlspecialchars($user_data['fullname']); ?></div>
              </div>
              <div class="info-line">
                <div class="label">Email Address</div>
                <div class="value"><?php echo htmlspecialchars($user_data['email']); ?></div>
              </div>
              <div class="info-line">
                <div class="label">Phone Number</div>
                <div class="value"><?php echo $user_data['number'] ? htmlspecialchars($user_data['number']) : '<span class="not-set">Not set</span>'; ?></div>
              </div>
              <div class="info-line">
                <div class="label">Default Shipping Address</div>
                <div class="value" id="defaultShippingDisplay">
                  <?php if ($default_shipping): ?>
                    <div class="address-display">
                      <strong><?php echo htmlspecialchars($default_shipping['fullname'] ?? $_SESSION['user_name']); ?></strong><br>
                      <?php echo htmlspecialchars($default_shipping['street']); ?><br>
                      <?php echo htmlspecialchars($default_shipping['city'] . ', ' . $default_shipping['state'] . ' ' . $default_shipping['zip_code']); ?><br>
                      Philippines
                      <a href="#" onclick="showSection('addresses'); return false;" class="change-address-link">Change</a>
                    </div>
                  <?php else: ?>
                    <span class="not-set">Not set</span>
                    <a href="#" onclick="showSection('addresses'); return false;" class="set-address-link">Set shipping address</a>
                  <?php endif; ?>
                </div>
              </div>
              <div class="info-line">
                <div class="label">Default Billing Address</div>
                <div class="value" id="defaultBillingDisplay">
                  <?php if ($default_billing): ?>
                    <div class="address-display">
                      <strong><?php echo htmlspecialchars($default_billing['fullname'] ?? $_SESSION['user_name']); ?></strong><br>
                      <?php echo htmlspecialchars($default_billing['street']); ?><br>
                      <?php echo htmlspecialchars($default_billing['city'] . ', ' . $default_billing['state'] . ' ' . $default_billing['zip_code']); ?><br>
                      Philippines
                      <a href="#" onclick="showSection('addresses'); return false;" class="change-address-link">Change</a>
                    </div>
                  <?php else: ?>
                    <span class="not-set">Not set</span>
                    <a href="#" onclick="showSection('addresses'); return false;" class="set-address-link">Set billing address</a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <div class="quick-links">
            <h2>Quick Actions</h2>
            <div class="links-grid">
              <a href="<?php echo SITE_URL; ?>pages/new.php" class="link-card">
                <i class="fas fa-bag-shopping"></i>
                <span>Continue Shopping</span>
              </a>
              <a href="#" class="link-card" onclick="showSection('wishlist')">
                <i class="fas fa-heart"></i>
                <span>View Wishlist</span>
              </a>
              <a href="#" class="link-card" onclick="showSection('addresses')">
                <i class="fas fa-map-marker-alt"></i>
                <span>Manage Addresses</span>
              </a>
              <a href="#" class="link-card" onclick="openEditModal()">
                <i class="fas fa-user-edit"></i>
                <span>Edit Profile</span>
              </a>
            </div>
          </div>
        </div>

        <!-- Addresses Section - YOUR EXISTING CODE -->
        <div class="section" id="addresses">
          <div class="section-title">
            <h2>Address Book</h2>
            <button class="primary-button" onclick="openAddressModal()">
              <i class="fas fa-plus"></i> Add New Address
            </button>
          </div>

          <div class="address-grid" id="addressesContainer">
            <?php if (count($addresses) > 0): ?>
              <?php foreach ($addresses as $address): ?>
                <div class="address-panel <?php echo $address['is_default'] ? 'default-address' : ''; ?>" 
                     data-address-id="<?php echo $address['id']; ?>"
                     data-address-type="<?php echo $address['type']; ?>"
                     onclick="editAddress(<?php echo $address['id']; ?>)">
                  <div class="address-header">
                    <h4>
                      <?php echo ucfirst($address['type']); ?> Address 
                      <?php if ($address['is_default']): ?>
                        <span class="default-tag"><i class="fas fa-star"></i> Default</span>
                      <?php endif; ?>
                    </h4>
                    <div class="address-actions">
                      <button class="action-icon" onclick="event.stopPropagation(); editAddress(<?php echo $address['id']; ?>)">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="action-icon delete-icon" onclick="event.stopPropagation(); removeAddress(<?php echo $address['id']; ?>)">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </div>
                  <div class="address-info">
                    <p><strong><?php echo htmlspecialchars($address['fullname'] ?? $_SESSION['user_name']); ?></strong></p>
                    <p><?php echo htmlspecialchars($address['street']); ?></p>
                    <p><?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['zip_code']); ?></p>
                    <p>Philippines</p>
                  </div>
                  <?php if (!$address['is_default']): ?>
                    <button class="secondary-button set-default-btn" 
                            onclick="event.stopPropagation(); setDefaultAddress(<?php echo $address['id']; ?>, '<?php echo $address['type']; ?>')">
                      <i class="fas fa-star"></i> Set as Default
                    </button>
                  <?php else: ?>
                    <div class="default-indicator">
                      <i class="fas fa-check-circle"></i> Default Address
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="empty-state">
                <i class="fas fa-map-marker-alt"></i>
                <h3>No addresses saved</h3>
                <p>Add your first address to make checkout easier.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Wishlist Section - YOUR EXISTING CODE -->
        <div class="section" id="wishlist">
          <div class="section-title">
            <h2>My Wishlist</h2>
            <span class="count-badge"><?php echo $wishlist_count; ?> items</span>
          </div>

          <?php if ($wishlist_count > 0): ?>
            <div class="wishlist-grid">
              <?php foreach ($wishlist_items as $item): ?>
                <?php
                // Handle image display
                if (!empty($item['product_image']) && strpos($item['product_image'], 'base64') !== false) {
                  $imageSrc = $item['product_image'];
                } else {
                  $imageSrc = SITE_URL . 'uploads/sample1.jpg';
                }
                
                // Calculate actual sale price
                $actual_sale_price = 0;
                if ($item['sale_price'] > 0) {
                  $discount_amount = ($item['price'] * $item['sale_price']) / 100;
                  $actual_sale_price = $item['price'] - $discount_amount;
                }
                
                // Create product link
                $product_link = SITE_URL . 'pages/product.php?id=' . ($item['color_id'] ?? $item['product_id']);
                ?>
                
                <div class="wishlist-item" data-wishlist-id="<?php echo $item['id']; ?>" data-product-id="<?php echo $item['product_id']; ?>">
                  <a href="<?php echo $product_link; ?>" class="wishlist-item-link">
                    <div class="item-image">
                      <img src="<?php echo $imageSrc; ?>" 
                           alt="<?php echo htmlspecialchars($item['name']); ?>"
                           onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>uploads/sample1.jpg'">
                      <button class="remove-item" onclick="event.preventDefault(); event.stopPropagation(); deleteWishlistItem(<?php echo $item['id']; ?>)">
                        <i class="fas fa-times"></i>
                      </button>
                    </div>
                    <div class="item-details">
                      <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                      <?php if (!empty($item['color_name'])): ?>
                        <div class="item-color">
                          <span class="color-badge">Color: <?php echo htmlspecialchars($item['color_name']); ?></span>
                        </div>
                      <?php endif; ?>
                      <div class="item-price">
                        <?php if ($item['sale_price'] > 0): ?>
                          <span class="current">₱<?php echo number_format($actual_sale_price, 2); ?></span>
                          <span class="original">₱<?php echo number_format($item['price'], 2); ?></span>
                          <span class="discount-percentage">-<?php echo number_format($item['sale_price'], 0); ?>%</span>
                        <?php else: ?>
                          <span class="current">₱<?php echo number_format($item['price'], 2); ?></span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="empty-state">
              <i class="fas fa-heart"></i>
              <h3>Your wishlist is empty</h3>
              <p>Start adding items you love to your wishlist.</p>
              <a href="<?php echo SITE_URL; ?>pages/new.php" class="primary-button">
                <i class="fas fa-shopping-bag"></i> Start Shopping
              </a>
            </div>
          <?php endif; ?>
        </div>

        <div class="homepage-order-history">
          <!-- Orders Section - WITH COMPLETE MUTUAL EXCLUSION LOGIC -->
          <div class="section" id="orders">
            <div class="section-title">
              <h2>Order History</h2>
              <span class="count-badge"><?php echo count($orders); ?> orders</span>
            </div>

            <?php if (count($orders) > 0): ?>
              <div class="orders-list">
                <?php foreach ($orders as $order): 
                  // Check if order has cancellation or return requests
                  $cancellation_stmt = $conn->prepare("SELECT status FROM order_cancellations WHERE order_id = ?");
                  $cancellation_stmt->bind_param("i", $order['id']);
                  $cancellation_stmt->execute();
                  $cancellation = $cancellation_stmt->get_result()->fetch_assoc();
                  
                  $return_stmt = $conn->prepare("SELECT id, status, return_type, refund_amount FROM return_requests WHERE order_id = ?");
                  $return_stmt->bind_param("i", $order['id']);
                  $return_stmt->execute();
                  $return_request = $return_stmt->get_result()->fetch_assoc();

                  // Check if order has receipt
                  $has_receipt = hasOrderReceipt($order['id'], $conn);
                  
                  // Get order items with product details
                  $items_stmt = $conn->prepare("
                      SELECT oi.product_id, oi.product_name, oi.color_name, oi.quantity, oi.price,
                              p.image as product_image, p.name as product_name,
                              pc.color_name as product_color
                      FROM order_items oi 
                      LEFT JOIN products p ON oi.product_id = p.id 
                      LEFT JOIN product_colors pc ON oi.product_id = pc.product_id AND oi.color_name = pc.color_name
                      WHERE oi.order_id = ?
                  ");
                  $items_stmt->bind_param("i", $order['id']);
                  $items_stmt->execute();
                  $order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                ?>
                  <div class="order-panel">
                    <div class="order-header">
                      <div class="order-meta">
                        <div class="order-product-images">
                          <?php
                          if (is_array($order_items)) {
                            foreach ($order_items as $item) {
                              $productImage = null;
                              
                              // Try to get the specific color image first
                              if (isset($item['color_name']) && !empty($item['color_name'])) {
                                $productImage = getProductColorImage($item['product_id'], $item['color_name'], $conn);
                              }
                              
                              // If no color-specific image found, get default product image
                              if (!$productImage) {
                                $productImage = getProductImage($item['product_id'], $conn);
                              }
                              
                              echo '<div class="order-product-image">';
                              if ($productImage) {
                                echo '<img src="data:image/jpeg;base64,' . base64_encode($productImage) . '" 
                                        alt="' . htmlspecialchars($item['product_name'] ?? 'Product') . '" 
                                        title="' . htmlspecialchars($item['product_name'] ?? 'Product') . 
                                        (isset($item['color_name']) ? ' - ' . htmlspecialchars($item['color_name']) : '') . '">';
                              } else {
                                echo '<div class="no-image">No Image</div>';
                              }
                              echo '</div>';
                            }
                          }
                          ?>
                        </div>
                        
                        <div class="order-text-info">
                          <h4>Order #<?php echo $order['order_number']; ?></h4>
                          <span class="order-date">
                            <?php 
                            if (isset($order['created_at'])) {
                              echo date('F j, Y g:i A', strtotime($order['created_at']));
                            } else {
                              echo 'Date not available';
                            }
                            ?>
                          </span>
                          <span class="order-items"><?php echo $order['item_count']; ?> item(s)</span>
                          
                          <?php if ($has_receipt): ?>
                          <div class="receipt-confirmed">
                            <i class="fas fa-check-circle"></i> Order Received
                          </div>
                          <?php endif; ?>
                          
                          <?php if ($cancellation): ?>
                          <div class="request-status status-<?php echo $cancellation['status']; ?>">
                            Cancellation: <?php echo ucfirst($cancellation['status']); ?>
                          </div>
                          <?php endif; ?>
                          
                          <?php if ($return_request): ?>
                          <div class="request-status status-<?php echo $return_request['status']; ?>">
                            Return: <?php echo ucfirst(str_replace('_', ' ', $return_request['status'])); ?>
                          </div>
                          <?php endif; ?>
                        </div>
                      </div>
                      <div class="order-status">
                        <span class="status status-<?php echo strtolower($order['status']); ?>">
                          <?php echo ucfirst($order['status']); ?>
                        </span>
                      </div>
                    </div>
                    
                   <?php if ($return_request): ?>
                      <div class="return-info">
                          <h4>Return & Refund Information</h4>
                          <div class="return-details">
                              <div class="return-detail-item">
                                  <strong>Type:</strong> <?php echo ucfirst($return_request['return_type']); ?>
                              </div>
                              <div class="return-detail-item">
                                  <strong>Refund Amount:</strong> ₱<?php echo number_format($return_request['refund_amount'], 2); ?>
                              </div>
                              <div class="return-detail-item">
                                  <strong>Status:</strong> 
                                  <span class="request-status status-<?php echo $return_request['status']; ?>">
                                      <?php echo ucfirst(str_replace('_', ' ', $return_request['status'])); ?>
                                  </span>
                              </div>
                          </div>
                          
                          <!-- NEW: Return Shipping Instructions -->
                          <?php if ($return_request['status'] === 'approved'): ?>
                          <div class="return-instructions">
                              <h4><i class="fas fa-shipping-fast"></i> Return Shipping Instructions</h4>
                              <div class="instructions-content">
                                  <p><strong>Please follow these steps to complete your return:</strong></p>
                                  <ol>
                                      <li>Package your item securely in its original packaging if possible</li>
                                      <li>Include all original tags and accessories</li>
                                      <li>Ship to our return address:</li>
                                  </ol>
                                  
                                  <div class="return-address">
                                      <strong>Return Address:</strong><br>
                                      <?php echo RETURN_ADDRESS; ?>
                                  </div>
                                  
                                  <div class="shipping-notes">
                                      <p><strong>Important Notes:</strong></p>
                                      <ul>
                                          <li>Keep your tracking number for reference</li>
                                          <li>Return shipping costs are the customer's responsibility</li>
                                          <li>Refund will be processed after we receive and inspect the item</li>
                                      </ul>
                                  </div>
                                  
                                  <form method="POST" class="mark-shipped-form">
                                      <input type="hidden" name="mark_return_shipped" value="1">
                                      <input type="hidden" name="return_id" value="<?php echo $return_request['id']; ?>">
                                      <button type="submit" class="shipped-btn">
                                          <i class="fas fa-check-circle"></i> I've Shipped the Item
                                      </button>
                                  </form>
                              </div>
                          </div>
                          <?php elseif ($return_request['status'] === 'shipped'): ?>
                          <div class="return-status-update">
                              <i class="fas fa-truck"></i>
                              <span><strong>Seller waiting for your return item.</strong> We'll notify you when we receive and inspect your package.</span>
                          </div>
                          <?php endif; ?>
                          <!-- END OF NEW SECTION -->
                          
                          <?php if ($return_request['status'] === 'approved'): ?>
                              <div class="refund-processing">
                                  <p><strong>Refund Processing:</strong> Once we receive the returned item, we will inspect it and notify you. If approved, your refund will be automatically processed to your original payment method. Please allow 5-10 business days for the refund to show in your account.</p>
                              </div>
                          <?php endif; ?>
                      </div>
                      <?php endif; ?>
                    
                    <!-- UPDATED: Order Received and Feedback Section WITH COMPLETE MUTUAL EXCLUSION -->
                    <?php if ($order['status'] === 'delivered'): ?>
                    <div class="feedback-section">
                      <h4><i class="fas fa-star"></i> Order Actions</h4>
                      
                      <?php if (!$has_receipt && !$return_request): ?>
                        <p>What would you like to do with this order?</p>
                        <div class="order-action-buttons">
                          <!-- Option 1: Order Received -->
                          <div class="action-option">
                            <form method="POST" style="display: inline;">
                              <input type="hidden" name="confirm_receipt" value="1">
                              <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                              <button type="submit" class="receipt-btn">
                                <i class="fas fa-check-circle"></i> Order Received
                              </button>
                            </form>
                            <p class="action-description">I'm satisfied with my order and want to keep the items</p>
                          </div>
                          
                          <!-- Option 2: Return/Refund -->
                          <div class="action-option">
                            <button class="return-btn" onclick="openReturnModal(<?php echo $order['id']; ?>)">
                              <i class="fas fa-undo"></i> Return/Refund
                            </button>
                            <p class="action-description">I want to return some or all items</p>
                          </div>
                        </div>
                        
                      <?php elseif ($has_receipt && !$return_request): ?>
                        <!-- Customer confirmed Order Received - CAN PROVIDE FEEDBACK -->
                        <div class="received-confirmed">
                          <i class="fas fa-check-circle"></i> 
                          <span>Order Received Confirmed - Thank you for your purchase!</span>
                        </div>
                        <p>You can now provide feedback for your products:</p>
                        <div class="order-items-feedback">
                          <?php foreach ($order_items as $item): 
                            $has_feedback = hasFeedbackForOrderItem($order['id'], $item['product_id'], $user_id, $conn);
                          ?>
                            <div class="feedback-item" style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                              <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                              <?php if (!empty($item['color_name'])): ?>
                                <span> - Color: <?php echo htmlspecialchars($item['color_name']); ?></span>
                              <?php endif; ?>
                              
                              <?php if ($has_feedback): ?>
                                <span style="color: green; margin-left: 10px;">
                                  <i class="fas fa-check"></i> Feedback Provided
                                </span>
                              <?php else: ?>
                                <button class="feedback-btn" onclick="openFeedbackModal(<?php echo $order['id']; ?>, <?php echo $item['product_id']; ?>, '<?php echo addslashes($item['product_name']); ?>')">
                                  <i class="fas fa-star"></i> Provide Feedback
                                </button>
                              <?php endif; ?>
                            </div>
                          <?php endforeach; ?>
                        </div>
                        
                      <?php elseif ($return_request): ?>
                        <!-- Customer requested Return/Refund - CANNOT PROVIDE FEEDBACK -->
                        <div class="return-pending">
                          <i class="fas fa-undo"></i> 
                          <span>Return Request Submitted</span>
                        </div>
                        <p style="color: #666; margin-top: 10px;">
                          <i class="fas fa-info-circle"></i> 
                          Feedback is not available for orders with return requests.
                        </p>
                        
                        <!-- Show items but without feedback options -->
                        <div class="order-items-feedback" style="margin-top: 15px;">
                          <?php foreach ($order_items as $item): ?>
                            <div class="feedback-item" style="margin: 10px 0; padding: 10px; border: 1px solid #eee; border-radius: 4px; background: #f9f9f9;">
                              <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                              <?php if (!empty($item['color_name'])): ?>
                                <span> - Color: <?php echo htmlspecialchars($item['color_name']); ?></span>
                              <?php endif; ?>
                              <span style="color: #999; margin-left: 10px;">
                                <i class="fas fa-ban"></i> Feedback not available
                              </span>
                            </div>
                          <?php endforeach; ?>
                        </div>
                        
                      <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="order-footer">
                      <div class="order-total">
                        <strong>Total: ₱<?php echo number_format($order['total_amount'], 2); ?></strong>
                      </div>
                      <div class="order-buttons">
                        <button class="secondary-button" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                          <i class="fas fa-eye"></i> View Details
                        </button>
                        
                        <?php if (!$cancellation && $order['can_cancel'] && in_array($order['status'], ['pending', 'processing'])): ?>
                          <button class="cancel-btn" onclick="openCancelModal(<?php echo $order['id']; ?>)">
                            <i class="fas fa-times"></i> Cancel Order
                          </button>
                        <?php endif; ?>
                        
                        <form method="POST" style="display: inline;">
                          <input type="hidden" name="reorder" value="1">
                          <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                          <button type="submit" class="primary-button" 
                                  <?php echo $order['status'] === 'cancelled' ? 'disabled' : ''; ?>>
                            <i class="fas fa-redo"></i> Reorder
                          </button>
                        </form>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <h3>No orders yet</h3>
                <p>Start shopping to see your orders here.</p>
                <a href="<?php echo SITE_URL; ?>pages/new.php" class="primary-button">
                  <i class="fas fa-shopping-cart"></i> Start Shopping
                </a>
              </div>
            <?php endif; ?>
          </div>

          <!-- My Reviews Section - KEPT INTACT -->
          <div class="section" id="feedback">
            <div class="section-title">
              <h2><i class="fas fa-star"></i> My Reviews</h2>
            </div>

            <?php
            // Get user's feedback
            $feedback_stmt = $conn->prepare("
              SELECT pf.*, p.name as product_name, o.order_number 
              FROM product_feedback pf
              JOIN products p ON pf.product_id = p.id
              JOIN orders o ON pf.order_id = o.id
              WHERE pf.user_id = ?
              ORDER BY pf.created_at DESC
            ");
            $feedback_stmt->bind_param("i", $user_id);
            $feedback_stmt->execute();
            $user_feedback = $feedback_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            ?>

            <?php if (count($user_feedback) > 0): ?>
              <div class="feedback-history">
                <?php foreach ($user_feedback as $feedback): ?>
                  <div class="feedback-item" style="margin: 15px 0; padding: 15px; border: 1px solid #e0e0e0; border-radius: 8px;">
                    <div class="feedback-header">
                      <h4><?php echo htmlspecialchars($feedback['product_name']); ?></h4>
                      <p>Order #<?php echo htmlspecialchars($feedback['order_number']); ?></p>
                    </div>
                    <div class="rating-display">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?php echo $i <= $feedback['rating'] ? 'active' : ''; ?>" style="color: <?php echo $i <= $feedback['rating'] ? '#ffc107' : '#ddd'; ?>"></i>
                      <?php endfor; ?>
                      <span>(<?php echo $feedback['rating']; ?>/5)</span>
                    </div>
                    <?php if (!empty($feedback['comment'])): ?>
                      <div class="feedback-comment">
                        <p><?php echo nl2br(htmlspecialchars($feedback['comment'])); ?></p>
                      </div>
                    <?php endif; ?>
                    <div class="feedback-meta">
                      <small>Submitted on: <?php echo date('F j, Y g:i A', strtotime($feedback['created_at'])); ?></small>
                      <span class="status-badge <?php echo $feedback['is_approved'] ? 'approved' : 'pending'; ?>">
                        <?php echo $feedback['is_approved'] ? 'Approved' : 'Pending Approval'; ?>
                      </span>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="empty-state">
                <i class="fas fa-star"></i>
                <h3>No reviews yet</h3>
                <p>You haven't submitted any reviews yet. Review products from your delivered orders to help other customers.</p>
                <button class="primary-button" onclick="showSection('orders')">
                  <i class="fas fa-shopping-bag"></i> View Orders
                </button>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- NEW: Feedback Modal -->
  <div id="feedbackModal" class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Provide Feedback</h3>
        <button class="close-button" onclick="closeFeedbackModal()">&times;</button>
      </div>
      <form method="POST" class="modal-form">
        <input type="hidden" name="submit_feedback" value="1">
        <input type="hidden" id="feedback_order_id" name="order_id">
        <input type="hidden" id="feedback_product_id" name="product_id">
        
        <div class="form-group">
          <label>Product: <span id="feedback_product_name"></span></label>
        </div>
        
        <div class="form-group">
          <label>Rating *</label>
          <div class="star-rating">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <span class="star" data-rating="<?php echo $i; ?>" onclick="setRating(<?php echo $i; ?>)">
                <i class="far fa-star"></i>
              </span>
            <?php endfor; ?>
          </div>
          <input type="hidden" id="rating" name="rating" required>
        </div>
        
        <div class="form-group">
          <label for="comment">Comment (Optional)</label>
          <textarea id="comment" name="comment" rows="4" placeholder="Share your experience with this product..."></textarea>
        </div>
        
        <div class="form-buttons">
          <button type="button" class="secondary-button" onclick="closeFeedbackModal()">Cancel</button>
          <button type="submit" class="primary-button">Submit Feedback</button>
        </div>
      </form>
    </div>
  </div>

  <!-- YOUR EXISTING MODALS - KEEP ALL OF THESE -->
  <!-- Order Details Modal -->
  <div id="orderDetailsModal" class="modal-overlay">
    <div class="modal-box large-modal">
      <div class="modal-header">
        <h3>Order Details</h3>
        <button class="close-button" onclick="closeOrderDetailsModal()">&times;</button>
      </div>
      <div class="modal-body" id="orderDetailsContent">
        <!-- Content will be loaded via AJAX -->
      </div>
    </div>
  </div>

  <!-- Cancel Order Modal -->
  <div id="cancelModal" class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Cancel Order</h3>
        <button class="close-button" onclick="closeCancelModal()">&times;</button>
      </div>
      <form method="POST" class="modal-form">
        <input type="hidden" name="cancel_order" value="1">
        <input type="hidden" id="cancel_order_id" name="order_id">
        
        <div class="form-group">
          <label for="cancel_reason">Reason for Cancellation *</label>
          <select id="cancel_reason" name="cancel_reason" required>
            <option value="">Select a reason</option>
            <option value="Changed my mind">Changed my mind</option>
            <option value="Found better price">Found better price</option>
            <option value="Ordered by mistake">Ordered by mistake</option>
            <option value="Shipping takes too long">Shipping takes too long</option>
            <option value="Other">Other reason</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="cancel_notes">Additional Notes (Optional)</label>
          <textarea id="cancel_notes" name="cancel_notes" rows="3" placeholder="Any additional information..."></textarea>
        </div>
        
        <div class="form-buttons">
          <button type="button" class="secondary-button" onclick="closeCancelModal()">Cancel</button>
          <button type="submit" class="cancel-btn">Submit Cancellation</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Return/Refund Modal -->
  <div id="returnModal" class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Return / Refund Request</h3>
        <button class="close-button" onclick="closeReturnModal()">&times;</button>
      </div>
      <form method="POST" class="modal-form">
        <input type="hidden" name="return_request" value="1">
        <input type="hidden" id="return_order_id" name="order_id">
        
        <div class="form-group">
          <label for="return_type">Request Type *</label>
          <select id="return_type" name="return_type" required>
            <option value="refund">Refund</option>
            <option value="exchange">Exchange</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="return_reason">Reason for Return *</label>
          <select id="return_reason" name="return_reason" required>
            <option value="">Select a reason</option>
            <option value="Item not as described">Item not as described</option>
            <option value="Wrong size">Wrong size</option>
            <option value="Wrong item received">Wrong item received</option>
            <option value="Damaged item">Damaged item</option>
            <option value="Changed my mind">Changed my mind</option>
            <option value="Other">Other reason</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="item_condition">Item Condition *</label>
          <select id="item_condition" name="item_condition" required>
            <option value="">Select condition</option>
            <option value="Unused with tags">Unused with tags</option>
            <option value="Unused without tags">Unused without tags</option>
            <option value="Used but good condition">Used but good condition</option>
            <option value="Damaged">Damaged</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="return_description">Detailed Description *</label>
          <textarea id="return_description" name="return_description" rows="4" placeholder="Please provide detailed information about why you're returning this item..." required></textarea>
        </div>
        
        <div class="return-policy">
          <h4>Return Policy Information</h4>
          <div class="policy-details">
            <p><strong>Return Shipping:</strong> Customer is responsible for return shipping costs unless the item was faulty.</p>
            <p><strong>Damaged Items:</strong> Please contact us immediately with photos of the damaged product.</p>
            <p><strong>Refund Processing:</strong> Once we receive the returned item, we will inspect it and notify you. If approved, your refund will be automatically processed to your original payment method. Please allow 5-10 business days for the refund to show in your account.</p>
          </div>
        </div>
        
        <div class="form-buttons">
          <button type="button" class="secondary-button" onclick="closeReturnModal()">Cancel</button>
          <button type="submit" class="return-btn">Submit Return Request</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Image Modal for Feedback Images -->
  <div id="imageModal" class="modal-overlay">
    <div class="modal-box image-modal">
      <div class="modal-header">
        <button class="close-button" onclick="closeImageModal()">&times;</button>
      </div>
      <div class="modal-body">
        <img id="modalImage" src="" alt="Enlarged view">
      </div>
    </div>
  </div>

  <!-- Edit Profile Modal -->
  <div id="editModal" class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Edit Profile</h3>
        <button class="close-button" onclick="closeEditModal()">&times;</button>
      </div>
      <form method="POST" class="modal-form">
        <input type="hidden" name="update_profile" value="1">
        <div class="form-group">
          <label for="fullname">Full Name</label>
          <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user_data['fullname']); ?>" required>
        </div>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" disabled>
          <small>Email cannot be changed</small>
        </div>
        <div class="form-group">
          <label for="number">Phone Number</label>
          <input type="tel" id="number" name="number" value="<?php echo htmlspecialchars($user_data['number'] ?? ''); ?>">
        </div>
        <div class="form-buttons">
          <button type="button" class="secondary-button" onclick="closeEditModal()">Cancel</button>
          <button type="submit" class="primary-button">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Add/Edit Address Modal -->
  <div id="addressModal" class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Add New Address</h3>
        <button class="close-button" onclick="closeAddressModal()">&times;</button>
      </div>
      
      <div class="modal-body">
        <div id="addAddressForm">
          <h4>Add New Shipping Address</h4>
          
          <div class="form-group">
            <label for="newFullname">Recipient's Full Name *</label>
            <input type="text" id="newFullname" placeholder="Enter recipient's full name" required>
          </div>

          <div class="form-group">
            <label for="newType">Address Type *</label>
            <select id="newType" required>
              <option value="shipping">Shipping Address</option>
              <option value="billing">Billing Address</option>
            </select>
          </div>

          <div class="form-group">
            <label for="newStreet">Street Address *</label>
            <input type="text" id="newStreet" placeholder="House number, street, barangay" required>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="newCity">City/Municipality *</label>
              <input type="text" id="newCity" placeholder="Enter your city or municipality" required>
            </div>
            <div class="form-group">
              <label for="newState">Province *</label>
              <input type="text" id="newState" placeholder="Enter your province" required>
            </div>
          </div>
          
          <div class="form-group">
            <label for="newZip">ZIP Code *</label>
            <input type="text" id="newZip" placeholder="Enter ZIP code" required>
          </div>
          
          <div class="form-group">
            <label for="newCountry">Country *</label>
            <input type="text" id="newCountry" value="Philippines" readonly>
          </div>

          <label class="form-check">
            <input type="checkbox" id="setAsDefault">
            Set as default address
          </label>
          
          <div class="form-buttons">
            <button id="saveAddressBtn" class="primary-button" onclick="saveOrUpdateAddress()">Save Address</button>
            <button type="button" class="secondary-button" onclick="closeAddressModal()">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Security Verification Modal -->
  <div id="securityModal" class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header">
        <h3><i class="fas fa-shield-alt"></i> Security Verification</h3>
        <button class="close-button" onclick="closeSecurityModal()">&times;</button>
      </div>
      <div class="modal-form">
        <div class="security-alert">
          <i class="fas fa-lock"></i>
          <p>For your security, please verify your password to continue with this action.</p>
        </div>
        <div class="form-group">
          <label for="security_password">Password</label>
          <input type="password" id="security_password" name="security_password" required>
        </div>
        <div class="form-buttons">
          <button type="button" class="secondary-button" onclick="closeSecurityModal()">Cancel</button>
          <button type="button" class="primary-button" onclick="verifyPassword()">Verify</button>
        </div>
      </div>
    </div>
  </div>

  <script src="<?php echo SITE_URL; ?>js/dashboard.js"></script>
  <script>
    // NEW: Feedback Modal Functions
    function openFeedbackModal(orderId, productId, productName) {
      document.getElementById('feedback_order_id').value = orderId;
      document.getElementById('feedback_product_id').value = productId;
      document.getElementById('feedback_product_name').textContent = productName;
      document.getElementById('feedbackModal').style.display = 'flex';
      document.body.style.overflow = 'hidden';
      resetRating();
    }

    function closeFeedbackModal() {
      document.getElementById('feedbackModal').style.display = 'none';
      document.body.style.overflow = 'auto';
      resetRating();
    }

    function setRating(rating) {
      document.getElementById('rating').value = rating;
      
      // Update star display
      const stars = document.querySelectorAll('.star-rating .star');
      stars.forEach((star, index) => {
        const starIcon = star.querySelector('i');
        if (index < rating) {
          starIcon.className = 'fas fa-star';
          star.style.color = '#ffc107';
        } else {
          starIcon.className = 'far fa-star';
          star.style.color = '#ddd';
        }
      });
    }

    function resetRating() {
      document.getElementById('rating').value = '';
      const stars = document.querySelectorAll('.star-rating .star');
      stars.forEach(star => {
        const starIcon = star.querySelector('i');
        starIcon.className = 'far fa-star';
        star.style.color = '#ddd';
      });
    }

    // Close feedback modal when clicking outside
    document.getElementById('feedbackModal')?.addEventListener('click', function(e) {
      if (e.target === this) closeFeedbackModal();
    });

    // YOUR EXISTING JAVASCRIPT FUNCTIONS - KEEP ALL OF THESE
    // Cancel Order Modal Functions
    function openCancelModal(orderId) {
      document.getElementById('cancel_order_id').value = orderId;
      document.getElementById('cancelModal').style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }

    function closeCancelModal() {
      document.getElementById('cancelModal').style.display = 'none';
      document.body.style.overflow = 'auto';
      document.getElementById('cancel_reason').value = '';
      document.getElementById('cancel_notes').value = '';
    }

    // Return/Refund Modal Functions
    function openReturnModal(orderId) {
      document.getElementById('return_order_id').value = orderId;
      document.getElementById('returnModal').style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }

    function closeReturnModal() {
      document.getElementById('returnModal').style.display = 'none';
      document.body.style.overflow = 'auto';
      document.getElementById('return_type').value = 'refund';
      document.getElementById('return_reason').value = '';
      document.getElementById('item_condition').value = '';
      document.getElementById('return_description').value = '';
    }

    // Order details functionality
    function viewOrderDetails(orderId) {
      fetch('<?php echo SITE_URL; ?>pages/order-details.php?order_id=' + orderId)
        .then(response => response.text())
        .then(html => {
          document.getElementById('orderDetailsContent').innerHTML = html;
          document.getElementById('orderDetailsModal').style.display = 'flex';
          document.body.style.overflow = 'hidden';
        })
        .catch(error => {
          document.getElementById('orderDetailsContent').innerHTML = '<div class="error-message">Error loading order details.</div>';
        });
    }

    function closeOrderDetailsModal() {
      document.getElementById('orderDetailsModal').style.display = 'none';
      document.body.style.overflow = 'auto';
    }

    // Close modals when clicking outside
    document.getElementById('cancelModal')?.addEventListener('click', function(e) {
      if (e.target === this) closeCancelModal();
    });
    
    document.getElementById('returnModal')?.addEventListener('click', function(e) {
      if (e.target === this) closeReturnModal();
    });

    document.getElementById('orderDetailsModal')?.addEventListener('click', function(e) {
      if (e.target === this) closeOrderDetailsModal();
    });

    // Set default address with AJAX
    function setDefaultAddress(addressId, addressType) {
      if (!confirm(`Set this as your default ${addressType} address?`)) {
        return;
      }

      const formData = new FormData();
      formData.append('ajax_set_default_address', '1');
      formData.append('address_id', addressId);
      formData.append('address_type', addressType);

      const buttons = document.querySelectorAll('.set-default-btn');
      buttons.forEach(btn => btn.disabled = true);

      fetch(window.location.href, {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          updateAddressesDisplay(data.addresses);
          updateOverviewDisplay(data.default_shipping, data.default_billing);
          showMessage(data.message, 'success');
        } else {
          showMessage(data.message, 'error');
        }
      })
      .catch(error => {
        showMessage('Error setting default address. Please try again.', 'error');
      })
      .finally(() => {
        buttons.forEach(btn => btn.disabled = false);
      });
    }

    // Update addresses display
    function updateAddressesDisplay(addresses) {
      const addressesContainer = document.getElementById('addressesContainer');
      
      if (addresses.length === 0) {
        addressesContainer.innerHTML = `
          <div class="empty-state">
            <i class="fas fa-map-marker-alt"></i>
            <h3>No addresses saved</h3>
            <p>Add your first address to make checkout easier.</p>
            <button class="primary-button" onclick="openAddressModal()">
              <i class="fas fa-plus"></i> Add Address
            </button>
          </div>
        `;
        return;
      }

      let addressesHTML = '';
      addresses.forEach(address => {
        addressesHTML += `
          <div class="address-panel ${address.is_default ? 'default-address' : ''}" 
               data-address-id="${address.id}"
               data-address-type="${address.type}">
            <div class="address-header">
              <h4>
                ${address.type.charAt(0).toUpperCase() + address.type.slice(1)} Address 
                ${address.is_default ? '<span class="default-tag"><i class="fas fa-star"></i> Default</span>' : ''}
              </h4>
              <div class="address-actions">
                <button class="action-icon" onclick="editAddress(${address.id})">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="action-icon delete-icon" onclick="removeAddress(${address.id})">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
            <div class="address-info">
              <p><strong>${escapeHtml(address.fullname || 'User')}</strong></p>
              <p>${escapeHtml(address.street)}</p>
              <p>${escapeHtml(address.city + ', ' + address.state + ' ' + address.zip_code)}</p>
              <p>Philippines</p>
            </div>
            ${!address.is_default ? 
              `<button class="secondary-button set-default-btn" 
                      onclick="setDefaultAddress(${address.id}, '${address.type}')">
                <i class="fas fa-star"></i> Set as Default
              </button>` : 
              `<div class="default-indicator">
                <i class="fas fa-check-circle"></i> Default Address
              </div>`
            }
          </div>
        `;
      });
      
      addressesContainer.innerHTML = addressesHTML;
    }

    // Update overview display
    function updateOverviewDisplay(defaultShipping, defaultBilling) {
      const shippingDisplay = document.getElementById('defaultShippingDisplay');
      const billingDisplay = document.getElementById('defaultBillingDisplay');
      
      if (defaultShipping) {
        shippingDisplay.innerHTML = `
          <div class="address-display">
            <strong>${escapeHtml(defaultShipping.fullname || '<?php echo $_SESSION['user_name']; ?>')}</strong><br>
            ${escapeHtml(defaultShipping.street)}<br>
            ${escapeHtml(defaultShipping.city + ', ' + defaultShipping.state + ' ' + defaultShipping.zip_code)}<br>
            Philippines
            <a href="#" onclick="showSection('addresses'); return false;" class="change-address-link">Change</a>
          </div>
        `;
      } else {
        shippingDisplay.innerHTML = `
          <span class="not-set">Not set</span>
          <a href="#" onclick="showSection('addresses'); return false;" class="set-address-link">Set shipping address</a>
        `;
      }
      
      if (defaultBilling) {
        billingDisplay.innerHTML = `
          <div class="address-display">
            <strong>${escapeHtml(defaultBilling.fullname || '<?php echo $_SESSION['user_name']; ?>')}</strong><br>
            ${escapeHtml(defaultBilling.street)}<br>
            ${escapeHtml(defaultBilling.city + ', ' + defaultBilling.state + ' ' + defaultBilling.zip_code)}<br>
            Philippines
            <a href="#" onclick="showSection('addresses'); return false;" class="change-address-link">Change</a>
          </div>
        `;
      } else {
        billingDisplay.innerHTML = `
          <span class="not-set">Not set</span>
          <a href="#" onclick="showSection('addresses'); return false;" class="set-address-link">Set billing address</a>
        `;
      }
    }

    // Utility function to escape HTML
    function escapeHtml(unsafe) {
      return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }

    // Show message function
    function showMessage(message, type) {
      const existingMessages = document.querySelectorAll('.message');
      existingMessages.forEach(msg => msg.remove());
      
      const messageDiv = document.createElement('div');
      messageDiv.className = `message ${type}`;
      messageDiv.textContent = message;
      
      const pageHeader = document.querySelector('.page-header');
      pageHeader.parentNode.insertBefore(messageDiv, pageHeader.nextSibling);
      
      setTimeout(() => {
        if (messageDiv.parentNode) {
          messageDiv.remove();
        }
      }, 5000);
    }

    // Initialize address modal when page loads
    document.addEventListener('DOMContentLoaded', function() {
      initAddressModal();
    });

    // Section navigation
    function showSection(sectionName) {
      // Hide all sections
      document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
      });
      
      // Remove active class from all menu items
      document.querySelectorAll('.menu-item').forEach(item => {
        item.classList.remove('active');
      });
      
      // Show selected section
      document.getElementById(sectionName).classList.add('active');
      
      // Activate corresponding menu item
      document.querySelector(`[data-section="${sectionName}"]`).classList.add('active');
    }

    // Initialize section navigation
    document.querySelectorAll('.menu-item').forEach(item => {
      if (!item.classList.contains('logout-link')) {
        item.addEventListener('click', function() {
          const sectionName = this.getAttribute('data-section');
          showSection(sectionName);
        });
      }
    });

    // Modal functions
    function openEditModal() {
      document.getElementById('editModal').style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
      document.getElementById('editModal').style.display = 'none';
      document.body.style.overflow = 'auto';
    }

    function openAddressModal() {
      document.getElementById('addressModal').style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }

    function closeAddressModal() {
      document.getElementById('addressModal').style.display = 'none';
      document.body.style.overflow = 'auto';
    }

    // Close modals when clicking outside
    document.getElementById('editModal')?.addEventListener('click', function(e) {
      if (e.target === this) closeEditModal();
    });

    document.getElementById('addressModal')?.addEventListener('click', function(e) {
      if (e.target === this) closeAddressModal();
    });

    // Escape key to close modals
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeEditModal();
        closeAddressModal();
        closeCancelModal();
        closeReturnModal();
        closeOrderDetailsModal();
        closeFeedbackModal();
      }
    });
  </script>
</body>
</html>