<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'not_logged_in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_id = intval($_POST['cart_id']);
$quantity = intval($_POST['quantity']);
$size = $_POST['size'] ?? 'M';

$update = $conn->prepare("UPDATE cart SET quantity = ?, size = ? WHERE id = ? AND user_id = ?");
$update->bind_param("isii", $quantity, $size, $cart_id, $user_id);
$update->execute();

echo json_encode(['status' => 'success']);
