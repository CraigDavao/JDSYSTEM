<?php
require_once '../connection/connection.php';

// Set JSON header for AJAX responses
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    // Debug logging
    error_log("=== PASSWORD RESET ATTEMPT ===");
    error_log("Token received: " . $token);
    error_log("Password length: " . strlen($newPassword));

    if (empty($token) || empty($newPassword)) {
        error_log("ERROR: Empty token or password");
        echo json_encode(['status' => 'error', 'message' => '⚠️ Token or password missing.']);
        exit;
    }

    // Check if token exists and is valid
    $check_stmt = $conn->prepare("SELECT id, email, reset_token, reset_expires FROM users WHERE reset_token = ?");
    $check_stmt->bind_param("s", $token);
    
    if (!$check_stmt->execute()) {
        error_log("DATABASE ERROR: " . $check_stmt->error);
        echo json_encode(['status' => 'error', 'message' => '⚠️ Database error.']);
        exit;
    }
    
    $result = $check_stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        error_log("ERROR: No user found with token: " . $token);
        echo json_encode(['status' => 'error', 'message' => '⚠️ Invalid reset token.']);
        exit;
    }

    error_log("User found - ID: " . $user['id'] . ", Email: " . $user['email']);
    error_log("Token expires: " . $user['reset_expires']);

    // Check if token is expired
    if (strtotime($user['reset_expires']) < time()) {
        error_log("ERROR: Token expired");
        echo json_encode(['status' => 'error', 'message' => '⚠️ Reset token has expired.']);
        exit;
    }

    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    error_log("Password hashed: " . substr($hashedPassword, 0, 20) . "...");

    // Update the password and clear reset token
    $update_stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
    $update_stmt->bind_param("si", $hashedPassword, $user['id']);
    
    if ($update_stmt->execute()) {
        $affected_rows = $update_stmt->affected_rows;
        error_log("UPDATE SUCCESS: Affected rows: " . $affected_rows);
        
        if ($affected_rows > 0) {
            error_log("PASSWORD RESET SUCCESSFUL for user: " . $user['email']);
            echo json_encode(['status' => 'success', 'message' => '✅ Password successfully reset! You can now login with your new password.']);
        } else {
            error_log("WARNING: No rows affected - password might not have changed");
            echo json_encode(['status' => 'error', 'message' => '⚠️ Password was not updated. Please try again.']);
        }
    } else {
        error_log("UPDATE ERROR: " . $update_stmt->error);
        echo json_encode(['status' => 'error', 'message' => '⚠️ Database update failed: ' . $update_stmt->error]);
    }
    
    $update_stmt->close();
    $check_stmt->close();
    
} else {
    echo json_encode(['status' => 'error', 'message' => '⚠️ Invalid request method.']);
}
?>