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

$delete = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
$delete->bind_param("ii", $cart_id, $user_id);
$delete->execute();

echo json_encode(['status' => 'success']);
