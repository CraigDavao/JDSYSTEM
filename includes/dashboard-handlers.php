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
?>