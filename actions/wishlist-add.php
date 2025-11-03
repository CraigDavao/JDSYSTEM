<?php
// wishlist-add.php - FIXED VERSION WITH PROPER COLOR HANDLING
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../connection/connection.php';

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'not_logged_in']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

// Properly handle optional color
$color_id = isset($_POST['color_id']) && $_POST['color_id'] !== '' ? (int) $_POST['color_id'] : null;
$color_name = isset($_POST['color_name']) && $_POST['color_name'] !== '' ? trim($_POST['color_name']) : null;

// Validate product ID
if ($product_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product ID.']);
    exit;
}

// Check if product exists and is active
$check_product = $conn->prepare("
    SELECT id, name FROM products
    WHERE id = ? AND (is_active IS NULL OR is_active = 1)
    LIMIT 1
");
$check_product->bind_param("i", $product_id);
$check_product->execute();
$product_result = $check_product->get_result();

if ($product_result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Product not found or inactive.']);
    exit;
}

$product_name = $product_result->fetch_assoc()['name'];
$check_product->close();

// Check if product with same color already exists in wishlist
if ($color_id !== null) {
    $check_existing = $conn->prepare("
        SELECT id FROM wishlist
        WHERE user_id = ? AND product_id = ? AND color_id = ?
    ");
    $check_existing->bind_param("iii", $user_id, $product_id, $color_id);
} else {
    $check_existing = $conn->prepare("
        SELECT id FROM wishlist
        WHERE user_id = ? AND product_id = ? AND color_id IS NULL
    ");
    $check_existing->bind_param("ii", $user_id, $product_id);
}

$check_existing->execute();
$exists = $check_existing->get_result();
$check_existing->close();

if ($exists->num_rows > 0) {
    echo json_encode([
        'status' => 'exists',
        'message' => 'This product with the same color is already in your wishlist.',
        'product_name' => $product_name
    ]);
    exit;
}

// Insert into wishlist with color info
$insert = $conn->prepare("
    INSERT INTO wishlist (user_id, product_id, color_id, color_name, added_at)
    VALUES (?, ?, ?, ?, NOW())
");
$insert->bind_param("iiis", $user_id, $product_id, $color_id, $color_name);

if ($insert->execute()) {
    // Get updated wishlist count
    $count_stmt = $conn->prepare("SELECT COUNT(*) AS wishlist_count FROM wishlist WHERE user_id = ?");
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $wishlist_count = $count_stmt->get_result()->fetch_assoc()['wishlist_count'];
    $count_stmt->close();

    echo json_encode([
        'status' => 'success',
        'message' => 'Product added to wishlist.',
        'product_name' => $product_name,
        'wishlist_count' => $wishlist_count
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to add product to wishlist.',
        'error' => $conn->error
    ]);
}

$insert->close();
$conn->close();
?>
