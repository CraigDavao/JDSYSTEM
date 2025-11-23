<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter your email']);
    exit;
}

// Check if email exists
$stmt = $conn->prepare("SELECT id, fullname FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // For security, don't reveal if email exists
    echo json_encode(['status' => 'success', 'message' => 'If an account with that email exists, a reset link has been sent']);
    exit;
}

$user = $result->fetch_assoc();

// Generate token and expiry
$token = bin2hex(random_bytes(32));
$expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

// Update database with token
$update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
$update_stmt->bind_param("ssi", $token, $expires, $user['id']);

if (!$update_stmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Error generating reset token']);
    exit;
}

// Create reset link that points to main site with token
$resetLink = SITE_URL . "?token=" . $token;

// Send email
$mail = new PHPMailer(true);
try {
    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = "davaojonathancraig28@gmail.com";
    $mail->Password = "nyqj lovn cxfh nbup"; // Your app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->Timeout = 30;

    // Email content
    $mail->setFrom('noreply@jollydolly.com', 'Jolly Dolly');
    $mail->addAddress($email, $user['fullname']);
    $mail->isHTML(true);
    $mail->Subject = "Password Reset Request - Jolly Dolly";
    
    $mail->Body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #333;'>Password Reset Request</h2>
        <p>Hello <strong>{$user['fullname']}</strong>,</p>
        <p>You requested a password reset for your Jolly Dolly account.</p>
        <p>Click the button below to reset your password:</p>
        <div style='text-align: center; margin: 30px 0;'>
            <a href='$resetLink' style='background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>Reset Password</a>
        </div>
        <p>Or copy and paste this link in your browser:</p>
        <p style='background: #f8f9fa; padding: 10px; border-radius: 4px; word-break: break-all;'>$resetLink</p>
        <p><strong>⚠️ This link will expire in 1 hour.</strong></p>
        <p>If you didn't request this password reset, please ignore this email.</p>
        <br>
        <p>Best regards,<br>Jolly Dolly Team</p>
    </div>";

    $mail->AltBody = "Password Reset Request\n\nHello {$user['fullname']},\n\nYou requested a password reset. Click this link: $resetLink\n\nThis link expires in 1 hour.\n\nIf you didn't request this, please ignore this email.";

    if ($mail->send()) {
        error_log("Password reset email sent to: $email");
        echo json_encode(['status' => 'success', 'message' => 'Password reset link has been sent to your email! Check your inbox.']);
    } else {
        throw new Exception('Mail send failed');
    }

} catch (Exception $e) {
    error_log("Mailer Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Could not send email. Please try again later.']);
}
?>