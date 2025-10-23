<?php
session_start();

if (isset($_SESSION['buy_now_product'])) {
    unset($_SESSION['buy_now_product']);
}

echo json_encode(['status' => 'success']);
?>