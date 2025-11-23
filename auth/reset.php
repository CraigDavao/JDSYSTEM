<?php
require_once "../includes/header.php";
require_once "../connection/connection.php";
require_once "../config.php";

$token = $_GET['token'] ?? '';
$message = "";

if (empty($token)) {
    $message = "⚠️ No token provided.";
} else {
    $stmt = $conn->prepare("SELECT id, reset_expires FROM users WHERE reset_token=? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        $message = "❌ Invalid token.";
    } elseif (strtotime($user['reset_expires']) < time()) {
        $message = "❌ Token expired.";
    }

    // Handle password update
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['password'])) {
        $newPass = trim($_POST['password']);
        if (empty($newPass)) {
            $message = "⚠️ Please enter a new password.";
        } else {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
            $update->bind_param("si", $hashed, $user['id']);
            $update->execute();
            $message = "✅ Password updated successfully!";
        }
    }
}
?>

<h2>Reset Password</h2>
<?php if (!empty($message)) echo "<p style='color:red;'>$message</p>"; ?>
<form method="POST">
    <input type="password" name="password" placeholder="New Password" required>
    <button type="submit">Reset Password</button>
</form>
