<?php
require_once '../connection/connection.php'; // already starts session

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    ob_start();

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Include restriction fields
    $stmt = $conn->prepare("SELECT id, fullname, email, password, is_verified, is_blocked, is_restricted FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // 🚫 Blocked users
        if ($row['is_blocked'] == 1) {
            echo "Your account has been blocked. Please contact support.";
            exit;
        }

        // ⚠️ Unverified users
        if ($row['is_verified'] == 0) {
            echo "Please verify your email before logging in.";
            exit;
        }

        // ✅ Verify password
        if (password_verify($password, $row['password'])) {

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['fullname'];
            $_SESSION['user_email'] = $row['email'];
            $_SESSION['is_restricted'] = $row['is_restricted']; // 🔹 store restriction status
            $_SESSION['is_blocked'] = $row['is_blocked']; // (just for completeness)

            // Generate remember token
            $token = bin2hex(random_bytes(32));
            $hashedToken = password_hash($token, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET remember_token=? WHERE id=?");
            $stmt->bind_param("si", $hashedToken, $row['id']);
            $stmt->execute();

            setcookie("remember_token", $token, time() + (86400 * 30), "/", "", false, true);

            ob_end_clean();
            header("Location: " . SITE_URL . "dashboard.php");
            exit;

        } else {
            echo "Invalid password.";
        }
    } else {
        echo "No account found.";
    }

    ob_end_flush();
}
?>