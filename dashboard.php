<?php
ob_start();
require_once 'config.php';
require_once 'connection/connection.php';
require_once __DIR__ . '/includes/header.php';

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

// User data
$stmt = $conn->prepare("SELECT id, fullname, number, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

// Handle AJAX request for setting default address
if (isset($_POST['ajax_set_default_address'])) {
    $address_id = $_POST['address_id'];
    $address_type = $_POST['address_type'];
    
    // First, set all addresses of this type to not default
    $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ? AND type = ?");
    $stmt->bind_param("is", $user_id, $address_type);
    $stmt->execute();
    
    // Then set the selected address as default
    $stmt = $conn->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $address_id, $user_id);
    
    if ($stmt->execute()) {
        // Get updated addresses
        $stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $address_result = $stmt->get_result();
        $updated_addresses = [];
        while ($row = $address_result->fetch_assoc()) {
            $updated_addresses[] = $row;
        }
        
        // Find new default addresses
        $default_shipping = null;
        $default_billing = null;
        foreach ($updated_addresses as $address) {
            if ($address['is_default'] && $address['type'] == 'shipping') {
                $default_shipping = $address;
            }
            if ($address['is_default'] && $address['type'] == 'billing') {
                $default_billing = $address;
            }
        }
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Default address updated successfully!',
            'addresses' => $updated_addresses,
            'default_shipping' => $default_shipping,
            'default_billing' => $default_billing
        ]);
        exit();
    } else {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Error updating default address.'
        ]);
        exit();
    }
}

// Addresses
$stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$address_result = $stmt->get_result();
while ($row = $address_result->fetch_assoc()) {
    $addresses[] = $row;
}

// Orders with product names
$stmt = $conn->prepare("
    SELECT o.*, 
           GROUP_CONCAT(oi.product_name SEPARATOR ', ') as product_names, 
           COUNT(oi.id) as item_count 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.user_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$order_result = $stmt->get_result();
while ($row = $order_result->fetch_assoc()) {
    $orders[] = $row;
}

// Wishlist with BLOB images
$stmt = $conn->prepare("
    SELECT 
        w.*, 
        p.name, 
        p.price, 
        p.sale_price,
        CONCAT('data:image/', 
               COALESCE(pi.image_format, 'jpeg'),
               ';base64,',
               TO_BASE64(COALESCE(pi.image, ''))) AS product_image
    FROM wishlist w 
    LEFT JOIN products p ON w.product_id = p.id 
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.id = (
        SELECT MIN(pi2.id) 
        FROM product_images pi2 
        WHERE pi2.product_id = p.id
    )
    WHERE w.user_id = ? 
    ORDER BY w.added_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$wishlist_result = $stmt->get_result();
$wishlist_count = $wishlist_result->num_rows;
while ($row = $wishlist_result->fetch_assoc()) {
    $wishlist_items[] = $row;
}

$default_shipping = null;
$default_billing = null;
foreach ($addresses as $address) {
    if ($address['is_default'] && $address['type'] == 'shipping') {
        $default_shipping = $address;
    }
    if ($address['is_default'] && $address['type'] == 'billing') {
        $default_billing = $address;
    }
}

// Handle reorder action
if (isset($_POST['reorder'])) {
    $order_id = $_POST['order_id'];
    
    // Get order items
    $stmt = $conn->prepare("SELECT product_id, quantity, size, color FROM order_items WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $reorder_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $added_to_cart = 0;
    foreach ($reorder_items as $item) {
        // Check if product exists and is active
        $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
        $stmt->bind_param("i", $item['product_id']);
        $stmt->execute();
        $product_exists = $stmt->get_result()->fetch_assoc();
        
        if ($product_exists) {
            // Add to cart
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, size, color) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiss", $user_id, $item['product_id'], $item['quantity'], $item['size'], $item['color']);
            if ($stmt->execute()) {
                $added_to_cart++;
            }
        }
    }
    
    if ($added_to_cart > 0) {
        $_SESSION['success_message'] = "{$added_to_cart} items added to cart from your order!";
    } else {
        $_SESSION['error_message'] = "No items could be added to cart. Products may no longer be available.";
    }
    
    ob_end_clean();
    header("Location: " . SITE_URL . "dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $fullname = trim($_POST['fullname']);
        $number = trim($_POST['number']);
        $stmt = $conn->prepare("UPDATE users SET fullname = ?, number = ? WHERE id = ?");
        $stmt->bind_param("ssi", $fullname, $number, $user_id);
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $fullname;
            $_SESSION['success_message'] = "Profile updated successfully!";
            ob_end_clean();
            header("Location: " . SITE_URL . "dashboard.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error updating profile.";
        }
    }
    
    if (isset($_POST['add_address'])) {
        $type = $_POST['type'];
        $street = trim($_POST['street']);
        $city = trim($_POST['city']);
        $state = trim($_POST['state']);
        $zip_code = trim($_POST['zip_code']);
        $country = 'Philippines';
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        if ($is_default) {
            $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ? AND type = ?");
            $stmt->bind_param("is", $user_id, $type);
            $stmt->execute();
        }
        $stmt = $conn->prepare("INSERT INTO addresses (user_id, type, street, city, state, zip_code, country, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssi", $user_id, $type, $street, $city, $state, $zip_code, $country, $is_default);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Address added successfully!";
            ob_end_clean();
            header("Location: " . SITE_URL . "dashboard.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error adding address.";
        }
    }
    
    if (isset($_POST['security_verify'])) {
        $password = $_POST['password'];
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['security_verified'] = true;
            $_SESSION['security_verified_time'] = time();
            ob_clean();
            echo json_encode(['success' => true]);
            exit();
        } else {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Incorrect password']);
            exit();
        }
    }
}

$security_verified = false;
if (isset($_SESSION['security_verified']) && isset($_SESSION['security_verified_time'])) {
    if (time() - $_SESSION['security_verified_time'] < 900) {
        $security_verified = true;
    } else {
        unset($_SESSION['security_verified']);
        unset($_SESSION['security_verified_time']);
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
        <!-- Overview Section -->
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
                      <strong><?php echo $_SESSION['user_name']; ?></strong><br>
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
                      <strong><?php echo $_SESSION['user_name']; ?></strong><br>
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

        <!-- Addresses Section -->
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
                     data-address-type="<?php echo $address['type']; ?>">
                  <div class="address-header">
                    <h4>
                      <?php echo ucfirst($address['type']); ?> Address 
                      <?php if ($address['is_default']): ?>
                        <span class="default-tag"><i class="fas fa-star"></i> Default</span>
                      <?php endif; ?>
                    </h4>
                    <div class="address-actions">
                      <button class="action-icon" onclick="editAddress(<?php echo $address['id']; ?>)">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="action-icon delete-icon" onclick="removeAddress(<?php echo $address['id']; ?>)">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </div>
                  <div class="address-info">
                    <p><?php echo htmlspecialchars($address['street']); ?></p>
                    <p><?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['zip_code']); ?></p>
                    <p>Philippines</p>
                  </div>
                  <?php if (!$address['is_default']): ?>
                    <button class="secondary-button set-default-btn" 
                            onclick="setDefaultAddress(<?php echo $address['id']; ?>, '<?php echo $address['type']; ?>')">
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
                <button class="primary-button" onclick="openAddressModal()">
                  <i class="fas fa-plus"></i> Add Address
                </button>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Wishlist Section -->
        <div class="section" id="wishlist">
          <div class="section-title">
            <h2>My Wishlist</h2>
            <span class="count-badge"><?php echo $wishlist_count; ?> items</span>
          </div>

          <?php if ($wishlist_count > 0): ?>
            <div class="wishlist-grid">
              <?php foreach ($wishlist_items as $item): ?>
                <div class="wishlist-item" data-wishlist-id="<?php echo $item['id']; ?>" data-product-id="<?php echo $item['product_id']; ?>">
                  <div class="item-image">
                    <?php if (!empty($item['product_image']) && strpos($item['product_image'], 'base64') !== false): ?>
                      <img src="<?php echo htmlspecialchars($item['product_image']); ?>" 
                           alt="<?php echo htmlspecialchars($item['name']); ?>"
                           onerror="this.src='<?php echo SITE_URL; ?>uploads/sample1.jpg'">
                    <?php else: ?>
                      <div class="no-image">No Image</div>
                    <?php endif; ?>
                    <button class="remove-item" onclick="deleteWishlistItem(<?php echo $item['id']; ?>)">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                  <div class="item-details">
                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                    <div class="item-price">
                      <?php if ($item['sale_price'] > 0): ?>
                        <span class="current">₱<?php echo number_format($item['sale_price'], 2); ?></span>
                        <span class="original">₱<?php echo number_format($item['price'], 2); ?></span>
                      <?php else: ?>
                        <span class="current">₱<?php echo number_format($item['price'], 2); ?></span>
                      <?php endif; ?>
                    </div>
                    <button class="primary-button cart-button" onclick="addToCart(<?php echo $item['product_id']; ?>)">
                      <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                  </div>
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

        <!-- Orders Section -->
        <div class="section" id="orders">
          <div class="section-title">
            <h2>Order History</h2>
            <span class="count-badge"><?php echo count($orders); ?> orders</span>
          </div>

          <?php if (count($orders) > 0): ?>
            <div class="orders-list">
              <?php foreach ($orders as $order): ?>
                <div class="order-panel">
                  <div class="order-header">
                    <div class="order-meta">
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
                    </div>
                    <div class="order-status">
                      <span class="status status-<?php echo strtolower($order['status']); ?>">
                        <?php echo ucfirst($order['status']); ?>
                      </span>
                    </div>
                  </div>
                  <div class="order-footer">
                    <div class="order-total">
                      <strong>Total: ₱<?php echo number_format($order['total_amount'], 2); ?></strong>
                    </div>
                    <div class="order-buttons">
                      <button class="secondary-button" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                        <i class="fas fa-eye"></i> View Details
                      </button>
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
      </div>
    </main>
  </div>

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

  <!-- Add Address Modal -->
  <div id="addressModal" class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Add New Address</h3>
        <button class="close-button" onclick="closeAddressModal()">&times;</button>
      </div>
      <form method="POST" class="modal-form">
        <input type="hidden" name="add_address" value="1">
        <div class="form-group">
          <label for="type">Address Type</label>
          <select id="type" name="type" required>
            <option value="shipping">Shipping Address</option>
            <option value="billing">Billing Address</option>
          </select>
        </div>
        <div class="form-group">
          <label for="street">Street Address</label>
          <input type="text" id="street" name="street" placeholder="House #, Street, Barangay" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="city">City/Municipality</label>
            <input type="text" id="city" name="city" placeholder="e.g., Manila, Quezon City" required>
          </div>
          <div class="form-group">
            <label for="state">Province/State</label>
            <input type="text" id="state" name="state" placeholder="e.g., Metro Manila, Laguna" required>
          </div>
        </div>
        <div class="form-group">
          <label for="zip_code">ZIP Code</label>
          <input type="text" id="zip_code" name="zip_code" placeholder="e.g., 1000" required>
        </div>
        <div class="form-group">
          <label for="country">Country</label>
          <input type="text" id="country" value="Philippines" disabled>
          <small>Currently serving Philippines only</small>
        </div>
        <div class="form-check">
          <input type="checkbox" id="is_default" name="is_default">
          <label for="is_default">Set as default address</label>
        </div>
        <div class="form-buttons">
          <button type="button" class="secondary-button" onclick="closeAddressModal()">Cancel</button>
          <button type="submit" class="primary-button">Save Address</button>
        </div>
      </form>
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

  <script src="<?php echo SITE_URL; ?>js/profile.js"></script>
  <script>
    // Order details functionality
    function viewOrderDetails(orderId) {
        fetch('<?php echo SITE_URL; ?>includes/order-details.php?order_id=' + orderId)
            .then(response => response.text())
            .then(html => {
                document.getElementById('orderDetailsContent').innerHTML = html;
                document.getElementById('orderDetailsModal').style.display = 'flex';
                document.body.style.overflow = 'hidden';
            })
            .catch(error => {
                console.error('Error loading order details:', error);
                document.getElementById('orderDetailsContent').innerHTML = '<div class="error-message">Error loading order details.</div>';
            });
    }

    function closeOrderDetailsModal() {
        document.getElementById('orderDetailsModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Close modal when clicking outside
    document.getElementById('orderDetailsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeOrderDetailsModal();
        }
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

        // Show loading state
        const buttons = document.querySelectorAll('.set-default-btn');
        buttons.forEach(btn => btn.disabled = true);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update addresses display
                updateAddressesDisplay(data.addresses);
                // Update overview display
                updateOverviewDisplay(data.default_shipping, data.default_billing);
                // Show success message
                showMessage(data.message, 'success');
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error setting default address:', error);
            showMessage('Error setting default address. Please try again.', 'error');
        })
        .finally(() => {
            // Re-enable buttons
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
        
        // Update shipping address
        if (defaultShipping) {
            shippingDisplay.innerHTML = `
                <div class="address-display">
                    <strong><?php echo $_SESSION['user_name']; ?></strong><br>
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
        
        // Update billing address
        if (defaultBilling) {
            billingDisplay.innerHTML = `
                <div class="address-display">
                    <strong><?php echo $_SESSION['user_name']; ?></strong><br>
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
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.message');
        existingMessages.forEach(msg => msg.remove());
        
        // Create new message
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        messageDiv.textContent = message;
        
        // Insert after page header
        const pageHeader = document.querySelector('.page-header');
        pageHeader.parentNode.insertBefore(messageDiv, pageHeader.nextSibling);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);
    }

    initSecurity(<?php echo $security_verified ? 'true' : 'false'; ?>);
  </script>
</body>
</html>