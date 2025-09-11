<?php
require_once '../connection/connection.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, fullname, email, password, is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($row['is_verified'] == 0) {
            echo "Please verify your email before logging in.";
            exit;
        }

        if (password_verify($password, $row['password'])) {
            // Set session
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['fullname'];
            $_SESSION['user_email'] = $row['email'];

            // Generate remember token
            $token = bin2hex(random_bytes(32));
            $hashedToken = password_hash($token, PASSWORD_DEFAULT);

            // Save hashed token in DB
            $stmt = $conn->prepare("UPDATE users SET remember_token=? WHERE id=?");
            $stmt->bind_param("si", $hashedToken, $row['id']);
            $stmt->execute();

            // Set cookie (valid for 30 days)
            setcookie("remember_token", $token, time() + (86400 * 30), "/", "", false, true);

            header("Location: ../dashboard.php");
        exit;

        } else {
            echo "Invalid password.";
        }
    } else {
        echo "No account found.";
    }
}

?>
