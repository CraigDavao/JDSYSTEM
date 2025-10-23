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

// Simple error reporting
error_reporting(0); // Turn off for production

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wishlist_id'])) {
    $wishlist_id = intval($_POST['wishlist_id']);
    $user_id = $_SESSION['user_id'];
    
    try {
        // Verify wishlist item belongs to user
        $stmt = $conn->prepare("SELECT id FROM wishlist WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $wishlist_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Wishlist item not found']);
            exit();
        }
        $stmt->close();
        
        // Delete the wishlist item
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $wishlist_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Item removed from wishlist successfully']);
        } else {
            throw new Exception('Database delete failed');
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

// Ensure no extra output
exit();
?>