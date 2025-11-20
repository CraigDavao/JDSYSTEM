<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

header('Content-Type: application/json');

$response = ['count' => 0];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    $sql = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $row = $result->fetch_assoc();
        $response['count'] = $row['count'];
    }
}

echo json_encode($response);
?>