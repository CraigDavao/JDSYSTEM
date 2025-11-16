<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if (!$order_id) {
    echo "<p style='text-align:center;margin-top:100px;'>Invalid order reference.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Fetch order details
$stmt = $conn->prepare("SELECT order_number, fullname, total_amount, payment_method, created_at FROM orders WHERE id=?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo "<p style='text-align:center;margin-top:100px;'>Order not found.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$order_number = htmlspecialchars($order['order_number']);
$fullname = htmlspecialchars($order['fullname']);
$total_amount = number_format($order['total_amount'], 2);
$payment_method = strtoupper($order['payment_method']);
$date = date('F j, Y, g:i A', strtotime($order['created_at']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation</title>
    <!-- Automatically redirect after 3 seconds -->
    <meta http-equiv="refresh" content="3;url=<?= SITE_URL ?>pages/order-success.php?order=<?= $order_number ?>">
    <style>
        .order-confirmation {
            text-align: center;
            padding: 50px;
        }
        .order-confirmation h2 {
            color: #333;
        }
        .order-confirmation p {
            font-size: 1.1em;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="order-confirmation">
        <h2>🎉 Order Placed Successfully!</h2>
        <p>Thank you, <strong><?= $fullname ?></strong>!</p>
        <p>Your order number is: <strong><?= $order_number ?></strong></p>
        <p>Total: ₱<?= $total_amount ?> | Payment: <?= $payment_method ?></p>
        <p>Redirecting to order success page...</p>
    </div>
</body>
</html>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
