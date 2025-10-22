<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    echo "<p style='text-align:center;margin-top:100px;'>Invalid order reference.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Fetch order details
$sql = "SELECT order_number, fullname, total_amount, payment_method, created_at 
        FROM orders 
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    echo "<p style='text-align:center;margin-top:100px;'>Order not found.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>css/order-success.css?v=<?= time(); ?>">
</head>
<body>
    <div class="order-success-container">
        <div class="success-card">
            <div class="icon">✅</div>
            <h1>Thank You, <?= htmlspecialchars($order['fullname']); ?>!</h1>
            <p>Your order has been placed successfully.</p>

            <div class="order-details">
                <p><strong>Order Number:</strong> <?= htmlspecialchars($order['order_number']); ?></p>
                <p><strong>Total:</strong> ₱<?= number_format($order['total_amount'], 2); ?></p>
                <p><strong>Payment Method:</strong> <?= strtoupper($order['payment_method']); ?></p>
                <p><strong>Date:</strong> <?= date('F j, Y, g:i A', strtotime($order['created_at'])); ?></p>
            </div>

            <a href="<?= SITE_URL; ?>" class="btn">Continue Shopping</a>
        </div>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
