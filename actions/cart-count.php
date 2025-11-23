<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];

// COUNT UNIQUE ITEMS (each row in cart = 1 item, regardless of quantity)
$stmt = $conn->prepare("SELECT COUNT(*) AS item_count FROM cart WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo json_encode(['count' => (int)$row['item_count']]);

$stmt->close();
$conn->close();
?>