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
                    <p><strong><?php echo htmlspecialchars($address['fullname'] ?? $_SESSION['user_name']); ?></strong></p>
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
                <?php
                // Debug: Check what's in product_image
                // error_log("Product Image for {$item['name']}: " . substr($item['product_image'] ?? 'NULL', 0, 50));
                
                // Handle image display - FIXED VERSION
                if (!empty($item['product_image']) && strpos($item['product_image'], 'base64') !== false) {
                    $imageSrc = $item['product_image'];
                } else {
                    // Fallback to default image
                    $imageSrc = SITE_URL . 'uploads/sample1.jpg';
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
                                <?php if ($item['sale_price'] > 0 && $item['sale_price'] < $item['price']): ?>
                                    <span class="current">₱<?php echo number_format($item['sale_price'], 2); ?></span>
                                    <span class="original">₱<?php echo number_format($item['price'], 2); ?></span>
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
        
        <div class="modal-body">
            <!-- Simple form that will submit traditionally -->
            <form method="POST" id="addressForm">
                <input type="hidden" name="add_address" value="1">
                
                <h4 style="margin-bottom: 25px; color: #2c3e50; font-size: 1.3em; font-weight: 600;">Add New Shipping Address</h4>
                
                <!-- Person's Name -->
                <div class="form-group">
                    <label for="fullname">Recipient's Full Name *</label>
                    <input type="text" id="fullname" name="fullname" placeholder="Enter recipient's full name" required>
                </div>

                <!-- Address Type -->
                <div class="form-group">
                    <label for="type">Address Type *</label>
                    <select id="type" name="type" required>
                        <option value="shipping">Shipping Address</option>
                        <option value="billing">Billing Address</option>
                    </select>
                </div>

                <!-- Address Information -->
                <div class="form-group">
                    <label for="street">Street Address *</label>
                    <input type="text" id="street" name="street" placeholder="House number, street, barangay" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City/Municipality *</label>
                        <input type="text" id="city" name="city" placeholder="Enter your city or municipality" required>
                    </div>
                    <div class="form-group">
                        <label for="state">Province *</label>
                        <input type="text" id="state" name="state" placeholder="Enter your province" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="zip_code">ZIP Code *</label>
                    <input type="text" id="zip_code" name="zip_code" placeholder="Enter ZIP code" required>
                </div>

                <!-- Default Address Option -->
                <label class="form-check">
                    <input type="checkbox" id="is_default" name="is_default" value="1">
                    Set as default address
                </label>
                
                <!-- Form Buttons -->
                <div class="form-buttons">
                    <button type="submit" class="primary-button">Save Address</button>
                    <button type="button" class="secondary-button" onclick="closeAddressModal()">Cancel</button>
                </div>
            </form>
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
        
        // Update shipping address
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
        
        // Update billing address
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

    // Initialize address modal when page loads
    document.addEventListener('DOMContentLoaded', function() {
        initAddressModal();
    });

    initSecurity(<?php echo $security_verified ? 'true' : 'false'; ?>);
  </script>
</body>
</html>