<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

$order_id = $_GET['order_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Fetch order info
$stmt = $conn->prepare("
    SELECT o.*, 
           a.street, a.city, a.state, a.zip_code, a.country,
           o.payment_method
    FROM orders o 
    LEFT JOIN addresses a ON o.address_id = a.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();
$stmt->close();

if (!$order) {
    echo '<div class="error-message">Order not found.</div>';
    exit;
}

// Fetch order items
$stmt = $conn->prepare("
    SELECT oi.*, p.name AS product_name, p.price AS original_price,
           CONCAT('data:image/', COALESCE(pi.image_format, 'jpeg'), ';base64,', TO_BASE64(COALESCE(pi.image, ''))) AS product_image
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.id = (
        SELECT MIN(pi2.id) FROM product_images pi2 WHERE pi2.product_id = p.id
    )
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$item_result = $stmt->get_result();
$order_items = [];
while ($row = $item_result->fetch_assoc()) {
    $order_items[] = $row;
}
$stmt->close();

// Fetch status history
$stmt = $conn->prepare("SELECT status, created_at, notes FROM order_status_history WHERE order_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$status_result = $stmt->get_result();
$status_history = [];
while ($row = $status_result->fetch_assoc()) {
    $status_history[] = $row;
}
$stmt->close();
?>

<div class="order-details">
    <div class="order-detail-header">
        <div class="order-info">
            <h2>Order #<?= htmlspecialchars($order['order_number']); ?></h2>
            <p class="order-date">Placed on <?= date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
            <span class="status-badge status-<?= strtolower($order['status']); ?>"><?= ucfirst($order['status']); ?></span>
        </div>
    </div>

    <div class="detail-section">
        <h3>Shipping Address</h3>
        <div class="address-box">
            <p><strong><?= htmlspecialchars($_SESSION['user_name']); ?></strong></p>
            <p><?= htmlspecialchars($order['street']); ?></p>
            <p><?= htmlspecialchars($order['city'] . ', ' . $order['state'] . ' ' . $order['zip_code']); ?></p>
            <p><?= htmlspecialchars($order['country']); ?></p>
        </div>
    </div>

    <div class="detail-section">
        <h3>Order Items (<?= count($order_items); ?>)</h3>
        <div class="order-items-list">
            <?php foreach ($order_items as $item): ?>
                <div class="order-item">
                    <div class="item-image">
                        <?php if (!empty($item['product_image'])): ?>
                            <img src="<?= htmlspecialchars($item['product_image']); ?>" alt="<?= htmlspecialchars($item['product_name']); ?>">
                        <?php else: ?>
                            <div class="no-image">No Image</div>
                        <?php endif; ?>
                    </div>
                    <div class="item-details">
                        <h4><?= htmlspecialchars($item['product_name']); ?></h4>
                        <div class="item-meta">
                            <span>Qty: <?= $item['quantity']; ?></span>
                            <?php if (!empty($item['size'])): ?><span>Size: <?= htmlspecialchars($item['size']); ?></span><?php endif; ?>
                            <?php if (!empty($item['color_name'])): ?><span>Color: <?= htmlspecialchars($item['color_name']); ?></span><?php endif; ?>
                        </div>
                        <div class="item-price">₱<?= number_format($item['price'], 2); ?> each</div>
                    </div>
                    <div class="item-total"><strong>₱<?= number_format($item['price'] * $item['quantity'], 2); ?></strong></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="detail-section">
        <h3>Order Summary</h3>
        <div class="order-summary">
            <div class="summary-row"><span>Subtotal:</span><span>₱<?= number_format($order['total_amount'] - $order['shipping_fee'], 2); ?></span></div>
            <div class="summary-row"><span>Shipping Fee:</span><span>₱<?= number_format($order['shipping_fee'], 2); ?></span></div>
            <div class="summary-row total"><span><strong>Total:</strong></span><span><strong>₱<?= number_format($order['total_amount'], 2); ?></strong></span></div>
        </div>
    </div>

    <div class="detail-section">
        <h3>Payment Information</h3>
        <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']); ?></p>
        <p><strong>Payment Status:</strong> <?= ucfirst($order['payment_status'] ?? 'Not Paid'); ?></p>
    </div>

    <?php if (!empty($status_history)): ?>
    <div class="detail-section">
        <h3>Status History</h3>
        <div class="status-timeline">
            <?php foreach ($status_history as $history): ?>
                <div class="status-item">
                    <div class="status-dot"></div>
                    <div class="status-content">
                        <span class="status"><?= ucfirst($history['status']); ?></span>
                        <span class="status-date"><?= date('M j, Y g:i A', strtotime($history['created_at'])); ?></span>
                        <?php if (!empty($history['notes'])): ?><p><?= htmlspecialchars($history['notes']); ?></p><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* Styles as before: order-items, address-box, timeline, badges */
.order-details { max-height:70vh; overflow-y:auto; padding:10px; font-family: sans-serif; }
.order-detail-header { display:flex; justify-content:space-between; border-bottom:1px solid #eee; margin-bottom:20px; padding-bottom:10px; }
.detail-section { margin-bottom:25px; padding-bottom:20px; border-bottom:1px solid #f5f5f5; }
.address-box { background:#f9f9f9; padding:15px; border-radius:8px; }
.order-item { display:flex; align-items:center; background:#f9f9f9; padding:15px; border-radius:8px; margin-bottom:10px; }
.item-image img { width:80px; height:80px; object-fit:cover; border-radius:4px; }
.item-details { flex:1; }
.summary-row { display:flex; justify-content:space-between; margin-bottom:10px; }
.summary-row.total { border-top:1px solid #ddd; padding-top:10px; font-size:1.1em; }
.status-timeline { position:relative; padding-left:20px; }
.status-dot { position:absolute; left:-20px; top:5px; width:12px; height:12px; background:#4CAF50; border-radius:50%; }
.status-badge { padding:5px 12px; border-radius:20px; font-size:0.9em; font-weight:bold; }
.status-pending { background:#fff3cd; color:#856404; }
.status-processing { background:#cce7ff; color:#004085; }
.status-shipped { background:#d1ecf1; color:#0c5460; }
.status-delivered, .status-completed { background:#d4edda; color:#155724; }
.status-cancelled { background:#f8d7da; color:#721c24; }
</style>
