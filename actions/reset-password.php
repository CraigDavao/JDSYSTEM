<?php
require_once '../connection/connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$token = $_POST['token'] ?? '';
$newPassword = $_POST['new_password'] ?? '';

if (empty($token) || empty($newPassword)) {
    echo json_encode(['status' => 'error', 'message' => 'Token and password are required']);
    exit;
}

if (strlen($newPassword) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters long']);
    exit;
}

try {
    // Check if token exists and is valid
    $check_stmt = $conn->prepare("SELECT id, reset_expires FROM users WHERE reset_token = ?");
    $check_stmt->bind_param("s", $token);
    
    if (!$check_stmt->execute()) {
        throw new Exception('Database query failed');
    }
    
    $result = $check_stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired reset token']);
        exit;
    }

    // Check if token is expired
    if (strtotime($user['reset_expires']) < time()) {
        echo json_encode(['status' => 'error', 'message' => 'Reset token has expired']);
        exit;
    }

    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update the password and clear reset fields
    $update_stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_code = NULL, reset_expires = NULL WHERE id = ?");
    $update_stmt->bind_param("si", $hashedPassword, $user['id']);
    
    if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Password successfully reset! You can now login with your new password.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update password']);
    }
    
} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred. Please try again.']);
}
?>