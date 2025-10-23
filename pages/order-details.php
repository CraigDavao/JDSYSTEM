<?php
require_once __DIR__ . '/../connection/connection.php';

if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

$order_id = $_GET['order_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, 
           a.street, a.city, a.state, a.zip_code, a.country,
           pm.method as payment_method
    FROM orders o 
    LEFT JOIN addresses a ON o.shipping_address_id = a.id 
    LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    echo '<div class="error-message">Order not found.</div>';
    exit;
}

// Get order items with product images
$stmt = $conn->prepare("
    SELECT oi.*,
           p.name as product_name,
           p.price as original_price,
           p.sale_price,
           p.actual_sale_price,
           CONCAT('data:image/', 
                  COALESCE(pi.image_format, 'jpeg'),
                  ';base64,',
                  TO_BASE64(COALESCE(pi.image, ''))) AS product_image
    FROM order_items oi 
    LEFT JOIN products p ON oi.product_id = p.id 
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.id = (
        SELECT MIN(pi2.id) 
        FROM product_images pi2 
        WHERE pi2.product_id = p.id
    )
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items_result = $stmt->get_result();
$order_items = [];
while ($row = $order_items_result->fetch_assoc()) {
    $order_items[] = $row;
}

// Get order status history
$stmt = $conn->prepare("
    SELECT status, created_at, notes 
    FROM order_status_history 
    WHERE order_id = ? 
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$status_result = $stmt->get_result();
$status_history = [];
while ($row = $status_result->fetch_assoc()) {
    $status_history[] = $row;
}
?>

<div class="order-details">
    <!-- Order Header -->
    <div class="order-detail-header">
        <div class="order-info">
            <h2>Order #<?php echo $order['order_number']; ?></h2>
            <p class="order-date">Placed on <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                <?php echo ucfirst($order['status']); ?>
            </span>
        </div>
        <div class="order-actions">
            <form method="POST" action="../dashboard.php">
                <input type="hidden" name="reorder" value="1">
                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                <button type="submit" class="primary-button" <?php echo $order['status'] === 'cancelled' ? 'disabled' : ''; ?>>
                    <i class="fas fa-redo"></i> Reorder All Items
                </button>
            </form>
        </div>
    </div>

    <!-- Shipping Address -->
    <div class="detail-section">
        <h3>Shipping Address</h3>
        <div class="address-box">
            <p><strong><?php echo $_SESSION['user_name']; ?></strong></p>
            <p><?php echo htmlspecialchars($order['street']); ?></p>
            <p><?php echo htmlspecialchars($order['city'] . ', ' . $order['state'] . ' ' . $order['zip_code']); ?></p>
            <p><?php echo htmlspecialchars($order['country']); ?></p>
        </div>
    </div>

    <!-- Order Items -->
    <div class="detail-section">
        <h3>Order Items (<?php echo count($order_items); ?>)</h3>
        <div class="order-items-list">
            <?php foreach ($order_items as $item): ?>
                <div class="order-item">
                    <div class="item-image">
                        <?php if (!empty($item['product_image']) && strpos($item['product_image'], 'base64') !== false): ?>
                            <img src="<?php echo htmlspecialchars($item['product_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                        <?php else: ?>
                            <div class="no-image">No Image</div>
                        <?php endif; ?>
                    </div>
                    <div class="item-details">
                        <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                        <div class="item-meta">
                            <span class="quantity">Quantity: <?php echo $item['quantity']; ?></span>
                            <?php if (!empty($item['size'])): ?>
                                <span class="size">Size: <?php echo $item['size']; ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['color'])): ?>
                                <span class="color">Color: <?php echo $item['color']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="item-price">
                            ₱<?php echo number_format($item['price'], 2); ?> each
                        </div>
                    </div>
                    <div class="item-total">
                        <strong>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Order Summary -->
    <div class="detail-section">
        <h3>Order Summary</h3>
        <div class="order-summary">
            <div class="summary-row">
                <span>Subtotal:</span>
                <span>₱<?php echo number_format($order['total_amount'] - $order['shipping_fee'], 2); ?></span>
            </div>
            <div class="summary-row">
                <span>Shipping Fee:</span>
                <span>₱<?php echo number_format($order['shipping_fee'], 2); ?></span>
            </div>
            <div class="summary-row total">
                <span><strong>Total:</strong></span>
                <span><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Payment Information -->
    <div class="detail-section">
        <h3>Payment Information</h3>
        <div class="payment-info">
            <p><strong>Payment Method:</strong> <?php echo $order['payment_method'] ?? 'Not specified'; ?></p>
            <p><strong>Payment Status:</strong> <span class="status status-<?php echo strtolower($order['payment_status']); ?>"><?php echo ucfirst($order['payment_status']); ?></span></p>
        </div>
    </div>

    <!-- Status History -->
    <?php if (!empty($status_history)): ?>
    <div class="detail-section">
        <h3>Order Status History</h3>
        <div class="status-timeline">
            <?php foreach ($status_history as $history): ?>
                <div class="status-item">
                    <div class="status-dot"></div>
                    <div class="status-content">
                        <span class="status"><?php echo ucfirst($history['status']); ?></span>
                        <span class="status-date"><?php echo date('M j, Y g:i A', strtotime($history['created_at'])); ?></span>
                        <?php if (!empty($history['notes'])): ?>
                            <p class="status-notes"><?php echo htmlspecialchars($history['notes']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.order-details {
    max-height: 70vh;
    overflow-y: auto;
    padding: 10px;
}

.order-detail-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.detail-section {
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f5f5f5;
}

.detail-section h3 {
    margin-bottom: 15px;
    color: #333;
    font-size: 1.2em;
}

.address-box {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 8px;
}

.order-items-list {
    space-y: 15px;
}

.order-item {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 8px;
    margin-bottom: 10px;
}

.item-image {
    width: 80px;
    height: 80px;
    margin-right: 15px;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 4px;
}

.item-details {
    flex: 1;
}

.item-details h4 {
    margin: 0 0 8px 0;
    color: #333;
}

.item-meta {
    font-size: 0.9em;
    color: #666;
    margin-bottom: 5px;
}

.item-meta span {
    margin-right: 15px;
}

.item-total {
    font-weight: bold;
    font-size: 1.1em;
}

.order-summary {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 8px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.summary-row.total {
    border-top: 1px solid #ddd;
    padding-top: 10px;
    font-size: 1.2em;
}

.status-timeline {
    position: relative;
    padding-left: 20px;
}

.status-item {
    position: relative;
    margin-bottom: 20px;
}

.status-dot {
    position: absolute;
    left: -20px;
    top: 5px;
    width: 12px;
    height: 12px;
    background: #4CAF50;
    border-radius: 50%;
}

.status-content {
    margin-left: 10px;
}

.status-date {
    font-size: 0.9em;
    color: #666;
    margin-left: 10px;
}

.status-notes {
    font-size: 0.9em;
    color: #888;
    margin-top: 5px;
}

.large-modal {
    max-width: 800px;
    width: 90%;
}

.status-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.9em;
    font-weight: bold;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-processing { background: #cce7ff; color: #004085; }
.status-shipped { background: #d1ecf1; color: #0c5460; }
.status-delivered { background: #d4edda; color: #155724; }
.status-completed { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }
</style>