<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

$order_number = $_GET['order'] ?? null;
if(!$order_number){
    echo "<p style='text-align:center;margin-top:100px;'>Invalid order reference.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$stmt = $conn->prepare("SELECT order_number, fullname FROM orders WHERE order_number=?");
$stmt->bind_param("s",$order_number);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$order){
    echo "<p style='text-align:center;margin-top:100px;'>Order not found.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<div style="text-align:center;padding:50px;">
    <h1>🎉 Order Placed Successfully!</h1>
    <p>Thank you, <strong><?= htmlspecialchars($order['fullname']); ?></strong>!</p>
    <p>Your order number is: <strong><?= htmlspecialchars($order['order_number']); ?></strong></p>
    <a href="<?= SITE_URL ?>" style="margin-top:20px;display:inline-block;padding:10px 20px;background:black;color:white;border-radius:5px;">Back to Home</a>
</div>
