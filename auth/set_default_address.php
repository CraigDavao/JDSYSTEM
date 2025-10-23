<?php
require_once '../config.php';
require_once '../connection/connection.php';

// Add CORS headers and proper error handling
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');

// Start session after headers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to users

// Log errors to file
ini_set('log_errors', 1);
ini_set('error_log', '../php_errors.log');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authorized - please log in again']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if required parameters exist
    if (!isset($_POST['address_id']) || !isset($_POST['type'])) {
        error_log("Missing parameters: address_id=" . ($_POST['address_id'] ?? 'null') . ", type=" . ($_POST['type'] ?? 'null'));
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }
    
    $address_id = intval($_POST['address_id']);
    $type = trim($_POST['type']);
    $user_id = $_SESSION['user_id'];
    
    // Validate address type
    if (!in_array($type, ['shipping', 'billing'])) {
        error_log("Invalid address type: " . $type);
        echo json_encode(['success' => false, 'message' => 'Invalid address type']);
        exit();
    }
    
    // Verify the address belongs to the user
    $stmt = $conn->prepare("SELECT id, type FROM addresses WHERE id = ? AND user_id = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Database preparation failed']);
        exit();
    }
    
    $stmt->bind_param("ii", $address_id, $user_id);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Database query failed']);
        exit();
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("Address not found: address_id=" . $address_id . ", user_id=" . $user_id);
        echo json_encode(['success' => false, 'message' => 'Address not found']);
        exit();
    }
    
    $address = $result->fetch_assoc();
    $stmt->close();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Remove default from all addresses of this type for the user
        $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ? AND type = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement 1: ' . $conn->error);
        }
        $stmt->bind_param("is", $user_id, $type);
        if (!$stmt->execute()) {
            throw new Exception('Failed to update existing defaults: ' . $stmt->error);
        }
        $stmt->close();
        
        // Set the selected address as default
        $stmt = $conn->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement 2: ' . $conn->error);
        }
        $stmt->bind_param("ii", $address_id, $user_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to set new default: ' . $stmt->error);
        }
        $stmt->close();
        
        $conn->commit();
        
        error_log("Successfully set address " . $address_id . " as default " . $type . " for user " . $user_id);
        echo json_encode(['success' => true, 'message' => 'Default ' . $type . ' address updated successfully']);
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Set default address error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

?>