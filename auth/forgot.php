<?php
require_once '../connection/connection.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$email = $_POST['email'] ?? '';

if (empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Email is required']);
    exit;
}

try {
    // Check if email exists
    $check_stmt = $conn->prepare("SELECT id, fullname FROM users WHERE email = ? AND is_verified = 1");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'No account found with this email']);
        exit;
    }

    // Generate reset code (6 digits)
    $reset_code = rand(100000, 999999);
    $reset_expires = date('Y-m-d H:i:s', strtotime('+30 minutes')); // Code expires in 30 minutes

    // Store reset code in database
    $update_stmt = $conn->prepare("UPDATE users SET reset_code = ?, reset_expires = ? WHERE email = ?");
    $update_stmt->bind_param("sss", $reset_code, $reset_expires, $email);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to store reset code');
    }

    // Send email with reset code
    $mail = new PHPMailer(true);
    
    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;
    $mail->Username = "davaojonathancraig28@gmail.com";
    $mail->Password = "nyqj lovn cxfh nbup";
    $mail->SMTPSecure = "tls";
    $mail->Port = 587;

    $mail->setFrom("davaojonathancraig28@gmail.com", "JDSystem");
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = "Password Reset Code";
    $mail->Body = "
        <h3>Password Reset Request</h3>
        <p>Hello <b>{$user['fullname']}</b>,</p>
        <p>Your password reset code is: <b style='font-size: 24px; color: #333;'>{$reset_code}</b></p>
        <p>This code will expire in 30 minutes.</p>
        <p>If you didn't request this reset, please ignore this email.</p>
        <br>
        <p>Best regards,<br>JDSystem Team</p>
    ";

    if ($mail->send()) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Reset code sent to your email',
            'email' => $email // Include email for the next step
        ]);
    } else {
        throw new Exception('Failed to send email');
    }

} catch (Exception $e) {
    error_log("Forgot password error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to process request. Please try again.']);
}
?>