<?php
// actions/api-test.php
header('Content-Type: application/json');
session_start();

echo json_encode([
    'status' => 'success',
    'message' => 'API is working!',
    'session_active' => isset($_SESSION['user_id']),
    'timestamp' => date('Y-m-d H:i:s')
]);
?>