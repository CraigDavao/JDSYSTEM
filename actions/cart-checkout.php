<?php
session_start();
require_once '../connection/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_ids = explode(',', $_POST['cart_ids']);
    $_SESSION['checkout_items'] = array_map('intval', $cart_ids);
    echo json_encode(['status' => 'success']);
}
?>