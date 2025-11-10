<?php
// test-addresses.php
require_once __DIR__ . '/../connection/connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die('Not logged in');
}

$user_id = $_SESSION['user_id'];

echo "<h2>Addresses for user $user_id</h2>";

$stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Street</th><th>City</th><th>State</th><th>ZIP</th><th>Country</th><th>Is Default</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['street']}</td>";
    echo "<td>{$row['city']}</td>";
    echo "<td>{$row['state']}</td>";
    echo "<td>{$row['zip_code']}</td>";
    echo "<td>{$row['country']}</td>";
    echo "<td>{$row['is_default']}</td>";
    echo "</tr>";
}

echo "</table>";
$stmt->close();
?>