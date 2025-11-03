<?php
// wishlist-clear.php
header('Content-Type: text/plain');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../connection/connection.php';

if (empty($_SESSION['user_id'])) {
    echo "error";
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Clear all wishlist items for this user
$clear_stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ?");
$clear_stmt->bind_param("i", $user_id);

if ($clear_stmt->execute()) {
    echo "success";
} else {
    echo "error";
}

$clear_stmt->close();
$conn->close();
?>