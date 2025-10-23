<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../connection/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo "error";
    exit;
}

$user_id = $_SESSION['user_id'];
$wishlist_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($wishlist_id <= 0) {
    echo "error";
    exit;
}

// Delete by wishlist_id (not product_id)
$stmt = $conn->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $wishlist_id, $user_id);

if ($stmt->execute()) {
    echo "success";
} else {
    echo "error";
}
?>