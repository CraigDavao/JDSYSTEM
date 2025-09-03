<?php
require_once '../connection/connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];
    $code  = $_POST['code'];

    $stmt = $conn->prepare("SELECT verification_code FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($dbCode);
    $stmt->fetch();
    $stmt->close();

    if ($dbCode == $code) {
        $update = $conn->prepare("UPDATE users SET is_verified=1 WHERE email=?");
        $update->bind_param("s", $email);
        $update->execute();

        echo "✅ Verification successful! You can now login.";
    } else {
        echo "❌ Invalid verification code.";
    }
}
