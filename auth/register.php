<?php
require_once '../connection/connection.php';
require_once '../vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = $_POST['fullname'] ?? '';
    $number   = $_POST['number'] ?? '';
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate required fields
    if (empty($fullname) || empty($number) || empty($email) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "⚠️ All fields are required."]);
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $code = rand(100000, 999999);

    // Check if email already exists AND is verified
    $check = $conn->prepare("SELECT id FROM users WHERE email=? AND is_verified=1");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    
    if ($check->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "⚠️ Email already registered."]);
        exit;
    }

    // Check if email exists but unverified - UPDATE instead of INSERT
    $check_unverified = $conn->prepare("SELECT id FROM users WHERE email=? AND is_verified=0");
    $check_unverified->bind_param("s", $email);
    $check_unverified->execute();
    $check_unverified->store_result();
    
    if ($check_unverified->num_rows > 0) {
        // Update existing unverified user
        $stmt = $conn->prepare("UPDATE users SET fullname=?, number=?, password=?, verification_code=? WHERE email=? AND is_verified=0");
        $stmt->bind_param("sssss", $fullname, $number, $hashedPassword, $code, $email);
    } else {
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (fullname, number, email, password, verification_code, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("sssss", $fullname, $number, $email, $hashedPassword, $code);
    }

    if ($stmt->execute()) {
        // Send email with code
        $mail = new PHPMailer(true);
        try {
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
            $mail->Subject = "Verify Your Account";
            $mail->Body = "Hello <b>$fullname</b>,<br><br>Your verification code is <b>$code</b>.<br><br>Thanks for registering!";

            $mail->send();

            echo json_encode([
                "status" => "success",
                "email" => $email,
                "message" => "✅ Registration successful. Check your email for the verification code."
            ]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "Mailer Error: {$mail->ErrorInfo}"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Registration failed."]);
    }
}