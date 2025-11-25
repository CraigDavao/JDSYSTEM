<?php
require_once '../connection/connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$email = $_POST['email'] ?? '';
$code = $_POST['code'] ?? '';

if (empty($email) || empty($code)) {
    echo json_encode(['status' => 'error', 'message' => 'Email and code are required']);
    exit;
}

try {
    // Check if code is valid and not expired
    $check_stmt = $conn->prepare("SELECT id, reset_code, reset_expires FROM users WHERE email = ? AND reset_code = ?");
    $check_stmt->bind_param("ss", $email, $code);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid reset code']);
        exit;
    }

    // Check if code is expired
    if (strtotime($user['reset_expires']) < time()) {
        echo json_encode(['status' => 'error', 'message' => 'Reset code has expired']);
        exit;
    }

    // Generate a secure token for password reset
    $reset_token = bin2hex(random_bytes(32));
    $token_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Store token in database
    $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
    $update_stmt->bind_param("sss", $reset_token, $token_expires, $email);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Code verified successfully',
            'token' => $reset_token
        ]);
    } else {
        throw new Exception('Failed to generate reset token');
    }

} catch (Exception $e) {
    error_log("Verify reset code error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Verification failed. Please try again.']);
}
?>