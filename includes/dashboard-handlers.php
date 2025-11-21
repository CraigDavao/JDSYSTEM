<?php
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

// Handle address removal
if (isset($_POST['remove_address'])) {
    $address_id = $_POST['address_id'];
    
    $stmt = $conn->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $address_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Address removed successfully!'
        ]);
        exit();
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error removing address.'
        ]);
        exit();
    }
}

// For updating address - FIXED VERSION
if (isset($_POST['update_address'])) {
    $address_id = $_POST['address_id'];
    $fullname = $_POST['fullname'];
    $type = $_POST['type'];
    $street = $_POST['street'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $zip_code = $_POST['zip_code'];
    $country = $_POST['country'];
    $is_default = isset($_POST['is_default']) ? (int)$_POST['is_default'] : 0;
    
    // DEBUG: Check what values we're receiving
    error_log("=== UPDATE ADDRESS DEBUG ===");
    error_log("is_default: " . $is_default . ", type: " . $type);
    
    // ONLY set as default if checkbox is checked (is_default == 1)
    if ($is_default == 1) {
        // Remove default from all addresses of this type (except the one we're updating)
        $reset_sql = "UPDATE addresses SET is_default = 0 WHERE user_id = ? AND type = ? AND id != ?";
        $reset_stmt = $conn->prepare($reset_sql);
        $reset_stmt->bind_param("isi", $user_id, $type, $address_id);
        $reset_stmt->execute();
        $reset_stmt->close();
        
        // This address will be updated with is_default = 1
        $final_is_default = 1;
        error_log("Setting address as DEFAULT - removed defaults from others");
    } else {
        // Checkbox is NOT checked - this address should NOT be default
        $final_is_default = 0;
        error_log("Setting address as REGULAR - no changes to other addresses");
    }
    
    // Update the address with the correct is_default value
    $sql = "UPDATE addresses SET fullname = ?, type = ?, street = ?, city = ?, state = ?, zip_code = ?, country = ?, is_default = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssiii", $fullname, $type, $street, $city, $state, $zip_code, $country, $final_is_default, $address_id, $user_id);
    
    if ($stmt->execute()) {
        error_log("SUCCESS: Updated address with is_default = " . $final_is_default);
        echo json_encode(['success' => true, 'message' => 'Address updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update address']);
    }
    $stmt->close();
    exit();
}

// Handle wishlist removal
if (isset($_POST['remove_wishlist'])) {
    $wishlist_id = $_POST['wishlist_id'];
    
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $wishlist_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Item removed from wishlist successfully!'
        ]);
        exit();
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error removing item from wishlist.'
        ]);
        exit();
    }
}

// For adding new address - FIXED VERSION
if (isset($_POST['add_address'])) {
    $fullname = $_POST['fullname'];
    $type = $_POST['type'];
    $street = $_POST['street'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $zip_code = $_POST['zip_code'];
    $country = $_POST['country'];
    $is_default = isset($_POST['is_default']) ? (int)$_POST['is_default'] : 0;
    
    // DEBUG: Log what we received
    error_log("=== ADD ADDRESS DEBUG ===");
    error_log("Checkbox value received: " . $is_default);
    error_log("Checkbox checked: " . ($is_default == 1 ? 'YES' : 'NO'));
    error_log("Address Type: " . $type);
    
    // ONLY set as default if checkbox is explicitly checked
    if ($is_default == 1) {
        // Remove default from all addresses of this type
        $reset_sql = "UPDATE addresses SET is_default = 0 WHERE user_id = ? AND type = ?";
        $reset_stmt = $conn->prepare($reset_sql);
        $reset_stmt->bind_param("is", $user_id, $type);
        $reset_stmt->execute();
        $reset_stmt->close();
        error_log("REMOVED defaults from other addresses because checkbox was checked");
    } else {
        error_log("Checkbox NOT checked - adding as REGULAR address");
    }
    
    // Insert with the exact is_default value (0 if unchecked, 1 if checked)
    $sql = "INSERT INTO addresses (user_id, fullname, type, street, city, state, zip_code, country, is_default) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssssi", $user_id, $fullname, $type, $street, $city, $state, $zip_code, $country, $is_default);
    
    if ($stmt->execute()) {
        error_log("SUCCESS: Added address with is_default = " . $is_default);
        echo json_encode(['success' => true, 'message' => 'Address added successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add address']);
    }
    $stmt->close();
    exit();
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

// Get wishlist with proper image handling - FIXED VERSION
$stmt = $conn->prepare("
    SELECT 
        w.id,
        w.product_id,
        w.color_id,
        p.name,
        p.price,
        p.sale_price,
        pc.color_name,
        COALESCE(
            (SELECT CONCAT('data:image/', 
                          COALESCE(pi.image_format, 'jpeg'), 
                          ';base64,', 
                          TO_BASE64(pi.image))
             FROM product_images pi
             WHERE pi.product_id = p.id
               AND (w.color_id IS NULL OR pi.color_name = pc.color_name)
             ORDER BY pi.sort_order ASC, pi.id ASC
             LIMIT 1),
            (SELECT CONCAT('data:image/', 
                          COALESCE(pi2.image_format, 'jpeg'), 
                          ';base64,', 
                          TO_BASE64(pi2.image))
             FROM product_images pi2
             WHERE pi2.product_id = p.id
             ORDER BY pi2.sort_order ASC, pi2.id ASC
             LIMIT 1),
            NULL
        ) AS product_image
    FROM wishlist w 
    LEFT JOIN products p ON w.product_id = p.id 
    LEFT JOIN product_colors pc ON w.color_id = pc.id
    WHERE w.user_id = ? 
    ORDER BY w.added_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$wishlist_result = $stmt->get_result();
$wishlist_count = $wishlist_result->num_rows;
$wishlist_items = [];
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

// Handle reorder action - FIXED VERSION WITH ORIGINAL ORDER PRICE
if (isset($_POST['reorder'])) {
    $order_id = $_POST['order_id'];
    
    // Get order items - FIXED: Use the correct columns from your table including price
    $stmt = $conn->prepare("SELECT product_id, quantity, size, color_id, color_name, price FROM order_items WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $reorder_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $added_to_cart = 0;
    $errors = [];
    
    foreach ($reorder_items as $item) {
        // Check if product exists and is active
        $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
        $stmt->bind_param("i", $item['product_id']);
        $stmt->execute();
        $product_exists = $stmt->get_result()->fetch_assoc();
        
        if ($product_exists) {
            // FIXED: Use the ORIGINAL ORDER PRICE (the price they actually paid)
            $current_price = $item['price'];
            error_log("Reordering product {$item['product_id']} with original order price: {$current_price}");
            
            try {
                // First try: Insert with all fields including price
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, size, color_id, color_name, price) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $color_id = $item['color_id'] ?? null;
                $color_name = $item['color_name'] ?? '';
                $stmt->bind_param("iiisisd", $user_id, $item['product_id'], $item['quantity'], $item['size'], $color_id, $color_name, $current_price);
                if ($stmt->execute()) {
                    $added_to_cart++;
                    error_log("SUCCESS: Added product {$item['product_id']} to cart with original price: {$current_price}");
                }
            } catch (mysqli_sql_exception $e) {
                // If that fails, try without color fields but with price
                try {
                    $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, size, price) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iiisd", $user_id, $item['product_id'], $item['quantity'], $item['size'], $current_price);
                    if ($stmt->execute()) {
                        $added_to_cart++;
                        error_log("SUCCESS: Added product {$item['product_id']} to cart with original price (no color): {$current_price}");
                    }
                } catch (mysqli_sql_exception $e2) {
                    // If that also fails, try with only basic fields including price
                    try {
                        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("iiid", $user_id, $item['product_id'], $item['quantity'], $current_price);
                        if ($stmt->execute()) {
                            $added_to_cart++;
                            error_log("SUCCESS: Added product {$item['product_id']} to cart with original price (basic): {$current_price}");
                        }
                    } catch (mysqli_sql_exception $e3) {
                        // Final fallback: without price
                        try {
                            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                            $stmt->bind_param("iii", $user_id, $item['product_id'], $item['quantity']);
                            if ($stmt->execute()) {
                                $added_to_cart++;
                                error_log("WARNING: Added product {$item['product_id']} to cart WITHOUT price");
                            }
                        } catch (mysqli_sql_exception $e4) {
                            $errors[] = "Failed to add product ID {$item['product_id']} to cart: " . $e4->getMessage();
                            error_log("ERROR: Failed to add product {$item['product_id']} to cart: " . $e4->getMessage());
                        }
                    }
                }
            }
        } else {
            error_log("Product {$item['product_id']} not found or not active");
        }
    }
    
    if ($added_to_cart > 0) {
        $_SESSION['success_message'] = "{$added_to_cart} items added to cart from your order!";
        if (!empty($errors)) {
            $_SESSION['success_message'] .= " Some items could not be added.";
        }
    } else {
        $_SESSION['error_message'] = "No items could be added to cart. Products may no longer be available.";
        if (!empty($errors)) {
            error_log("Reorder errors: " . implode(", ", $errors));
        }
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

// Handle address refresh request
if (isset($_GET['refresh_addresses'])) {
    // Get updated addresses
    $stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $address_result = $stmt->get_result();
    $addresses = [];
    while ($row = $address_result->fetch_assoc()) {
        $addresses[] = $row;
    }
    
    // Find default addresses
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
    
    // Output only the addresses section HTML
    ?>
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
                <button class="primary-button" onclick="openAddressModal()">
                    <i class="fas fa-plus"></i> Add Address
                </button>
            </div>
        <?php endif; ?>
    </div>
    
    
    <!-- Also output the overview displays -->
    <div id="defaultShippingDisplay">
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
    
    <div id="defaultBillingDisplay">
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
    <?php
    exit();
}

?>