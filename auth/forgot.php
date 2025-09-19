<?php
require_once '../includes/header.php'; // this gives $conn and session

require_once '../vendor/autoload.php'; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        echo "⚠️ Please enter your email.";
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $stmt = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?");
        $stmt->bind_param("ssi", $token, $expires, $user['id']);
        $stmt->execute();

        $resetLink = SITE_URL . "pages/reset_password.php?token=" . $token;

        $mail = new PHPMailer(true);
        try {
            $mail->setFrom('your_email@example.com', 'JDSystem');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "Password Reset Request";
            $mail->Body = "Click this link to reset your password:<br><a href='$resetLink'>$resetLink</a>";

            $mail->send();
            echo "✅ Password reset link has been sent to your email.";
        } catch (Exception $e) {
            echo "⚠️ Could not send email. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "⚠️ No account found with that email.";
    }
}
?>

<h2>Forgot Password</h2>
<form method="POST">
    <input type="email" name="email" placeholder="Enter your email" required>
    <button type="submit">Send Reset Link</button>
</form>
