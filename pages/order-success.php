<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

// Get order number from query string safely
$order_number = isset($_GET['order']) ? $_GET['order'] : null;

if (!$order_number) {
    echo "<p style='text-align:center;margin-top:100px;'>Invalid order reference.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Fetch order details from database
$sql = "SELECT order_number, fullname FROM orders WHERE order_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $order_number);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    echo "<p style='text-align:center;margin-top:100px;'>Order not found.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Escape for safe output
$order_number = htmlspecialchars($order['order_number']);
$fullname = htmlspecialchars($order['fullname']);
?>

<div class="order-success" style="text-align:center; padding:50px;">
  <h1>🎉 Order Placed Successfully!</h1>
  <p>Thank you, <strong><?= $fullname ?></strong>!</p>
  <p>Your order number is: <strong><?= $order_number ?></strong></p>
  <p>Thank you for shopping with Jolly Dolly!</p>
  <a href="<?= SITE_URL ?>" class="btn" style="margin-top:20px; display:inline-block; background:black; color:white; padding:10px 20px; border-radius:5px;">Back to Home</a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
