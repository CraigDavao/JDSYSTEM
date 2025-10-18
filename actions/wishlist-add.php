<?php
// ✅ Always return JSON
header('Content-Type: application/json');

// ✅ Start session if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Include database connection
require_once __DIR__ . '/../connection/connection.php';

// ✅ Check login first
if (empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'not_logged_in']);
    exit;
}


$user_id = (int) $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

// ✅ Validate product_id
if ($product_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product ID.']);
    exit;
}

// ✅ Check if product exists in the database (optional but good practice)
$check_product = $conn->prepare("SELECT id FROM products WHERE id = ? LIMIT 1");
$check_product->bind_param("i", $product_id);
$check_product->execute();
$product_result = $check_product->get_result();

if ($product_result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
    exit;
}

// ✅ Check if already in wishlist
$check = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
$check->bind_param("ii", $user_id, $product_id);
$check->execute();
$exists = $check->get_result();

if ($exists->num_rows > 0) {
    echo json_encode(['status' => 'exists', 'message' => 'Already in wishlist']);
    exit;
}

// ✅ Insert new wishlist entry
$stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id, added_at) VALUES (?, ?, NOW())");
$stmt->bind_param("ii", $user_id, $product_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Added to wishlist']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add to wishlist']);
}

$stmt->close();
$conn->close();
?>
