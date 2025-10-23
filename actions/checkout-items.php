<?php
session_start();
require_once '../connection/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_ids'])) {
    // Clear any previous buy now session
    unset($_SESSION['buy_now_product']);
    
    $cart_ids = explode(',', $_POST['cart_ids']);
    
    // Validate and sanitize cart IDs
    $valid_cart_ids = [];
    foreach ($cart_ids as $cart_id) {
        $cart_id = (int)trim($cart_id);
        if ($cart_id > 0) {
            $valid_cart_ids[] = $cart_id;
        }
    }
    
    if (empty($valid_cart_ids)) {
        echo json_encode(['status' => 'error', 'message' => 'No valid cart items selected']);
        exit;
    }
    
    // Verify these cart items belong to the current user
    $user_id = $_SESSION['user_id'];
    $placeholders = str_repeat('?,', count($valid_cart_ids) - 1) . '?';
    $stmt = $conn->prepare("
        SELECT id FROM cart 
        WHERE id IN ($placeholders) AND user_id = ?
    ");
    
    $types = str_repeat('i', count($valid_cart_ids)) . 'i';
    $params = array_merge($valid_cart_ids, [$user_id]);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $verified_cart_ids = [];
    while ($row = $result->fetch_assoc()) {
        $verified_cart_ids[] = $row['id'];
    }
    
    if (empty($verified_cart_ids)) {
        echo json_encode(['status' => 'error', 'message' => 'No valid cart items found']);
        exit;
    }
    
    // Store in session
    $_SESSION['checkout_items'] = $verified_cart_ids;
    
    // Debug log
    error_log("🛒 Checkout - User $user_id selected " . count($verified_cart_ids) . " items: " . implode(',', $verified_cart_ids));
    
    echo json_encode([
        'status' => 'success', 
        'count' => count($verified_cart_ids),
        'items' => $verified_cart_ids
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>