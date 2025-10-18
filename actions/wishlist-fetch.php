<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../connection/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo "<p>Please log in to view your wishlist.</p>";
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "
    SELECT p.id, p.name, p.price, p.image
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    WHERE w.user_id = ?
    ORDER BY w.added_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p class='empty'>Your wishlist is empty.</p>";
    exit;
}

while ($row = $result->fetch_assoc()) {
    echo "
    <div class='wishlist-item'>
        <img src='../uploads/{$row['image']}' alt='{$row['name']}'>
        <div class='info'>
            <h3>{$row['name']}</h3>
            <p>₱" . number_format($row['price'], 2) . "</p>
            <button class='remove-wishlist' data-id='{$row['id']}'>Remove</button>
        </div>
    </div>
    ";
}
