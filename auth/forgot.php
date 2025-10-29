<?php
require_once '../includes/header.php';
require_once '../vendor/autoload.php'; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set header for JSON response
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        echo json_encode(['status' => 'error', 'message' => '⚠️ Please enter your email.']);
        exit;
    }

    // Check if email exists in database
    $stmt = $conn->prepare("SELECT id, fullname, email FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Store token in database
        $stmt = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?");
        $stmt->bind_param("ssi", $token, $expires, $user['id']);
        
        if ($stmt->execute()) {
            // Create reset link - points to your main site with token
            $resetLink = SITE_URL . "?token=" . $token;

            $mail = new PHPMailer(true);
            try {
                // SMTP Configuration - UPDATE THESE WITH YOUR GMAIL
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = "davaojonathancraig28@gmail.com"; 
                $mail->Password = "twzt gwhe opao snzz"; // your app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->Timeout = 30; // 30 seconds timeout

                // Email content
                $mail->setFrom('noreply@jollydolly.com', 'Jolly Dolly');
                $mail->addAddress($user['email'], $user['fullname'] ?? 'User');
                $mail->isHTML(true);
                $mail->Subject = "Password Reset Request - Jolly Dolly";
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <h2 style='color: #333;'>Password Reset Request</h2>
                        <p>Hello <strong>" . ($user['fullname'] ?? 'User') . "</strong>,</p>
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
                    </div>
                ";
                
                $mail->AltBody = "Password Reset Request\n\nHello " . ($user['fullname'] ?? 'User') . ",\n\nYou requested a password reset. Click this link: $resetLink\n\nThis link expires in 1 hour.\n\nIf you didn't request this, please ignore this email.";

                if ($mail->send()) {
                    error_log("Password reset email sent to: " . $user['email']);
                    echo json_encode([
                        'status' => 'success', 
                        'message' => '✅ Password reset link has been sent to your email! Check your inbox.'
                    ]);
                } else {
                    error_log("Mailer Error: " . $mail->ErrorInfo);
                    echo json_encode([
                        'status' => 'error', 
                        'message' => '⚠️ Could not send email. Please try again.'
                    ]);
                }
            } catch (Exception $e) {
                error_log("Mailer Exception: " . $e->getMessage());
                echo json_encode([
                    'status' => 'error', 
                    'message' => '⚠️ Email service temporarily unavailable. Please try again later.'
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'error', 
                'message' => '⚠️ Error generating reset token. Please try again.'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => '⚠️ No account found with that email address.'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => '⚠️ Invalid request method.'
    ]);
}
?>