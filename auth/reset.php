<?php
require_once '../includes/header.php';

if (!isset($_GET['token'])) {
    echo "⚠️ No token provided.";
    exit;
}

$token = $_GET['token'];

$stmt = $conn->prepare("SELECT id, reset_expires FROM users WHERE reset_token=?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || strtotime($user['reset_expires']) < time()) {
    echo "⚠️ Invalid or expired token.";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newPass = $_POST['password'] ?? '';

    if (empty($newPass)) {
        echo "⚠️ Please enter a new password.";
    } else {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users 
                                SET password=?, reset_token=NULL, reset_expires=NULL 
                                WHERE id=?");
        $stmt->bind_param("si", $hashed, $user['id']);
        $stmt->execute();

        echo "✅ Password updated! You can now <a href='login.php'>login</a>.";
        exit;
    }
}
?>
<h2>Reset Password</h2>
<form method="POST">
    <input type="password" name="password" placeholder="New Password" required>
    <button type="submit">Reset Password</button>
</form>
