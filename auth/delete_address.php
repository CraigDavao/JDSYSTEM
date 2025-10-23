<?php
require_once '../config.php';
require_once '../connection/connection.php';

// Start session and set headers FIRST
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear any previous output
if (ob_get_length()) ob_clean();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['address_id'])) {
    $address_id = intval($_POST['address_id']);
    $user_id = $_SESSION['user_id'];
    
    try {
        // Verify address belongs to user
        $stmt = $conn->prepare("SELECT id FROM addresses WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $address_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Address not found']);
            exit();
        }
        $stmt->close();
        
        // Delete the address
        $stmt = $conn->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $address_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Address deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete address']);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

exit();
?>