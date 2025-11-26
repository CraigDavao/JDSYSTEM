<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "auth-db1319.hstgr.io";
$user = "u251504662_jollydolly";
$pass = "8>yP5P^3Xki>";
$db = "u251504662_jollydolly";
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

echo "✅ Connected successfully!";
?>
