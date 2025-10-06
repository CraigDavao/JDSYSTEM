<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../connection/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error']);
    exit;
}

$cart_ids = $_POST['cart_ids'] ?? '';
$_SESSION['checkout_cart_ids'] = explode(",", $cart_ids);

echo json_encode(['status' => 'success']);