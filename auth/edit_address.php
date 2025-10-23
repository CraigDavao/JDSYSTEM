<?php
require_once '../config.php';
require_once '../connection/connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_address'])) {
    $address_id = intval($_POST['address_id']);
    $user_id = $_SESSION['user_id'];
    $type = $_POST['type'];
    $street = trim($_POST['street']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zip_code = trim($_POST['zip_code']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    // Verify the address belongs to the user
    $stmt = $conn->prepare("SELECT id FROM addresses WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $address_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error_message'] = "Address not found";
        header("Location: " . SITE_URL . "dashboard.php");
        exit();
    }
    
    // If setting as default, remove default from other addresses of same type
    if ($is_default) {
        $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ? AND type = ?");
        $stmt->bind_param("is", $user_id, $type);
        $stmt->execute();
    }
    
    // Update the address
    $stmt = $conn->prepare("UPDATE addresses SET type = ?, street = ?, city = ?, state = ?, zip_code = ?, is_default = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sssssiii", $type, $street, $city, $state, $zip_code, $is_default, $address_id, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Address updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating address: " . $stmt->error;
    }
    
    header("Location: " . SITE_URL . "dashboard.php");
    exit();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>