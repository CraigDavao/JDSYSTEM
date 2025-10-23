<?php
session_start();
header('Content-Type: application/json');

// Clear buy now session
if (isset($_SESSION['buy_now_product'])) {
    unset($_SESSION['buy_now_product']);
    error_log("🛒 Cleared buy_now_product session");
}

echo json_encode(['status' => 'success', 'message' => 'Buy now session cleared']);
?>