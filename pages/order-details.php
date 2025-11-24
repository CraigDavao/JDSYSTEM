<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

$order_id = $_GET['order_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Helper function to get product image by product ID
function getProductImage($productId, $conn) {
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['image'];
    }
    
    return null;
}

// Helper function to get product color-specific image
function getProductColorImage($productId, $colorName, $conn) {
    $stmt = $conn->prepare("SELECT image FROM product_images WHERE product_id = ? AND color_name = ? ORDER BY sort_order LIMIT 1");
    $stmt->bind_param("is", $productId, $colorName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['image'];
    }
    
    return null;
}

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

// Fetch order items with improved image handling
$stmt = $conn->prepare("
    SELECT oi.*, p.name AS product_name, p.price AS original_price,
           oi.color_name, oi.size
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$item_result = $stmt->get_result();
$order_items = [];
while ($row = $item_result->fetch_assoc()) {
    // Get the appropriate product image
    $productImage = null;
    
    // Try to get the specific color image first
    if (isset($row['color_name']) && !empty($row['color_name'])) {
        $productImage = getProductColorImage($row['product_id'], $row['color_name'], $conn);
    }
    
    // If no color-specific image found, get default product image
    if (!$productImage) {
        $productImage = getProductImage($row['product_id'], $conn);
    }
    
    // Convert image to base64 if exists
    if ($productImage) {
        $row['product_image'] = 'data:image/jpeg;base64,' . base64_encode($productImage);
    } else {
        $row['product_image'] = null;
    }
    
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

// NEW: Check for cancellation and return requests
$cancellation_request = null;
$return_request = null;

// Check cancellation request
$stmt = $conn->prepare("SELECT * FROM order_cancellations WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$cancellation_result = $stmt->get_result();
if ($cancellation_result->num_rows > 0) {
    $cancellation_request = $cancellation_result->fetch_assoc();
}
$stmt->close();

// Check return request
$stmt = $conn->prepare("SELECT * FROM return_requests WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$return_result = $stmt->get_result();
if ($return_result->num_rows > 0) {
    $return_request = $return_result->fetch_assoc();
}
$stmt->close();

// DETERMINE AVAILABLE ACTIONS BASED ON YOUR LOGIC:
// - Can cancel only if status is 'pending' (admin hasn't accepted yet)
// - Cannot cancel if status is 'processing', 'shipped', 'delivered', etc. (admin has accepted)
// - Can return only if status is 'delivered' and no return request exists
$can_cancel = !$cancellation_request && $order['status'] === 'pending';
$can_return = !$return_request && $order['status'] === 'delivered';
?>

<div class="order-details-content">
    <div class="order-details-single-column">
        <div class="order-detail-header">
            <div class="order-info">
                <h2>Order #<?= htmlspecialchars($order['order_number']); ?></h2>
                <p class="order-date">Placed on <?= date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                <div class="status-container">
                    <span class="status-badge status-<?= strtolower($order['status']); ?>"><?= ucfirst($order['status']); ?></span>
                    
                    <?php if ($cancellation_request): ?>
                        <span class="request-status status-<?= $cancellation_request['status']; ?>">
                            Cancellation: <?= ucfirst($cancellation_request['status']); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($return_request): ?>
                        <span class="request-status status-<?= $return_request['status']; ?>">
                            Return: <?= ucfirst(str_replace('_', ' ', $return_request['status'])); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="order-actions">
                <?php if ($can_cancel): ?>
                    <button class="cancel-btn" onclick="openCancelModal(<?= $order_id; ?>)">
                        <i class="fas fa-times"></i> Cancel Order
                    </button>
                <?php endif; ?>
                
                <?php if ($can_return): ?>
                    <button class="return-btn" onclick="openReturnModal(<?= $order_id; ?>)">
                        <i class="fas fa-undo"></i> Return/Refund
                    </button>
                <?php endif; ?>
                
                <button class="primary-button" onclick="reorderItems(<?= $order_id; ?>)">
                    <i class="fas fa-shopping-cart"></i> Reorder All
                </button>
            </div>
        </div>

        <?php if ($return_request): ?>
        <div class="detail-section return-info-section">
            <h3>Return & Refund Information</h3>
            <div class="return-info-box">
                <div class="return-details-grid">
                    <div class="return-detail">
                        <strong>Request Type:</strong> <?= ucfirst($return_request['return_type']); ?>
                    </div>
                    <div class="return-detail">
                        <strong>Refund Amount:</strong> ₱<?= number_format($return_request['refund_amount'], 2); ?>
                    </div>
                    <div class="return-detail">
                        <strong>Status:</strong> 
                        <span class="request-status status-<?= $return_request['status']; ?>">
                            <?= ucfirst(str_replace('_', ' ', $return_request['status'])); ?>
                        </span>
                    </div>
                    <div class="return-detail">
                        <strong>Reason:</strong> <?= htmlspecialchars($return_request['reason']); ?>
                    </div>
                    <div class="return-detail">
                        <strong>Item Condition:</strong> <?= htmlspecialchars($return_request['item_condition']); ?>
                    </div>
                    <div class="return-detail">
                        <strong>Submitted:</strong> <?= date('F j, Y g:i A', strtotime($return_request['created_at'])); ?>
                    </div>
                </div>
                
                <?php if ($return_request['status'] === 'approved'): ?>
                <div class="refund-processing-info">
                    <h4>Refund Processing</h4>
                    <p>Once we receive the returned item, we will inspect it and notify you. If approved, your refund will be automatically processed to your original payment method. Please allow 5-10 business days for the refund to show in your account.</p>
                    
                    <?php if (!empty($return_request['tracking_number'])): ?>
                    <p><strong>Return Tracking Number:</strong> <?= $return_request['tracking_number']; ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($cancellation_request): ?>
        <div class="detail-section cancellation-info-section">
            <h3>Cancellation Request</h3>
            <div class="cancellation-info-box">
                <div class="cancellation-details">
                    <p><strong>Status:</strong> 
                        <span class="request-status status-<?= $cancellation_request['status']; ?>">
                            <?= ucfirst($cancellation_request['status']); ?>
                        </span>
                    </p>
                    <p><strong>Reason:</strong> <?= htmlspecialchars($cancellation_request['reason']); ?></p>
                    <p><strong>Submitted:</strong> <?= date('F j, Y g:i A', strtotime($cancellation_request['created_at'])); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

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
                                <img src="<?= htmlspecialchars($item['product_image']); ?>" 
                                     alt="<?= htmlspecialchars($item['product_name']); ?>"
                                     title="<?= htmlspecialchars($item['product_name']); ?><?= !empty($item['color_name']) ? ' - ' . htmlspecialchars($item['color_name']) : ''; ?>">
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>
                        </div>
                        <div class="item-details">
                            <h4><?= htmlspecialchars($item['product_name']); ?></h4>
                            <div class="item-meta">
                                <span>Qty: <?= $item['quantity']; ?></span>
                                <?php if (!empty($item['size']) && $item['size'] !== ''): ?>
                                    <span>Size: <?= htmlspecialchars($item['size']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($item['color_name']) && $item['color_name'] !== ''): ?>
                                    <span>Color: <?= htmlspecialchars($item['color_name']); ?></span>
                                <?php endif; ?>
                            </div>
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
</div>

<!-- Cancel Order Modal -->
<div id="cancelModal" class="modal-overlay" style="display: none;">
    <div class="modal-box large-modal">
        <div class="modal-header">
            <h3>Cancel Order</h3>
            <button class="close-button" onclick="closeCancelModal()">&times;</button>
        </div>
        <form id="cancelForm" method="POST" class="modal-form">
            <input type="hidden" name="cancel_order" value="1">
            <input type="hidden" id="cancel_order_id" name="order_id">
            
            <div class="form-group">
                <label for="cancel_reason">Reason for Cancellation *</label>
                <select id="cancel_reason" name="cancel_reason" required>
                    <option value="">Select a reason</option>
                    <option value="Changed my mind">Changed my mind</option>
                    <option value="Found better price">Found better price</option>
                    <option value="Ordered by mistake">Ordered by mistake</option>
                    <option value="Shipping takes too long">Shipping takes too long</option>
                    <option value="Other">Other reason</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="cancel_notes">Additional Notes (Optional)</label>
                <textarea id="cancel_notes" name="cancel_notes" rows="3" placeholder="Any additional information..."></textarea>
            </div>
            
            <div class="form-buttons">
                <button type="button" class="secondary-button" onclick="closeCancelModal()">Cancel</button>
                <button type="submit" class="cancel-btn">Submit Cancellation</button>
            </div>
        </form>
    </div>
</div>

<!-- Return/Refund Modal -->
<div id="returnModal" class="modal-overlay" style="display: none;">
    <div class="modal-box large-modal">
        <div class="modal-header">
            <h3>Return / Refund Request</h3>
            <button class="close-button" onclick="closeReturnModal()">&times;</button>
        </div>
        <form id="returnForm" method="POST" class="modal-form">
            <input type="hidden" name="return_request" value="1">
            <input type="hidden" id="return_order_id" name="order_id">
            
            <div class="form-group">
                <label for="return_type">Request Type *</label>
                <select id="return_type" name="return_type" required>
                    <option value="refund">Refund</option>
                    <option value="exchange">Exchange</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="return_reason">Reason for Return *</label>
                <select id="return_reason" name="return_reason" required>
                    <option value="">Select a reason</option>
                    <option value="Item not as described">Item not as described</option>
                    <option value="Wrong size">Wrong size</option>
                    <option value="Wrong item received">Wrong item received</option>
                    <option value="Damaged item">Damaged item</option>
                    <option value="Changed my mind">Changed my mind</option>
                    <option value="Other">Other reason</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="item_condition">Item Condition *</label>
                <select id="item_condition" name="item_condition" required>
                    <option value="">Select condition</option>
                    <option value="Unused with tags">Unused with tags</option>
                    <option value="Unused without tags">Unused without tags</option>
                    <option value="Used but good condition">Used but good condition</option>
                    <option value="Damaged">Damaged</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="return_description">Detailed Description *</label>
                <textarea id="return_description" name="return_description" rows="4" placeholder="Please provide detailed information about why you're returning this item..." required></textarea>
            </div>
            
            <div class="return-policy">
                <h4>Return Policy Information</h4>
                <div class="policy-details">
                    <p><strong>Return Shipping:</strong> Customer is responsible for return shipping costs unless the item was faulty.</p>
                    <p><strong>Damaged Items:</strong> Please contact us immediately with photos of the damaged product.</p>
                    <p><strong>Refund Processing:</strong> Once we receive the returned item, we will inspect it and notify you. If approved, your refund will be automatically processed to your original payment method. Please allow 5-10 business days for the refund to show in your account.</p>
                </div>
            </div>
            
            <div class="form-buttons">
                <button type="button" class="secondary-button" onclick="closeReturnModal()">Cancel</button>
                <button type="submit" class="return-btn">Submit Return Request</button>
            </div>
        </form>
    </div>
</div>

<style>
.order-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #eee;
    gap: 15px;
}

.item-image {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #e0e0e0;
    flex-shrink: 0;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-image .no-image {
    width: 100%;
    height: 100%;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #666;
}

.item-details {
    flex: 1;
}

.item-details h4 {
    margin: 0 0 8px 0;
    font-size: 16px;
    color: #333;
}

.item-meta {
    display: flex;
    gap: 15px;
    margin-bottom: 8px;
    font-size: 14px;
    color: #666;
}

.item-price {
    font-size: 14px;
    color: #666;
}

.item-total {
    font-size: 16px;
    color: #333;
    font-weight: bold;
    min-width: 100px;
    text-align: right;
}

/* Responsive design */
@media (max-width: 768px) {
    .order-item {
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
    }
    
    .item-image {
        align-self: center;
    }
    
    .item-total {
        align-self: flex-end;
        text-align: right;
        margin-top: 10px;
    }
}
</style>

<script>
function openCancelModal(orderId) {
    document.getElementById('cancel_order_id').value = orderId;
    document.getElementById('cancelModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeCancelModal() {
    document.getElementById('cancelModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('cancel_reason').value = '';
    document.getElementById('cancel_notes').value = '';
}

function openReturnModal(orderId) {
    document.getElementById('return_order_id').value = orderId;
    document.getElementById('returnModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeReturnModal() {
    document.getElementById('returnModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('return_type').value = 'refund';
    document.getElementById('return_reason').value = '';
    document.getElementById('item_condition').value = '';
    document.getElementById('return_description').value = '';
}

function reorderItems(orderId) {
    if (confirm('Add all items from this order to your cart?')) {
        // Create a form and submit it instead of using fetch
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../includes/dashboard-handlers.php';
        
        const reorderInput = document.createElement('input');
        reorderInput.type = 'hidden';
        reorderInput.name = 'reorder';
        reorderInput.value = '1';
        
        const orderIdInput = document.createElement('input');
        orderIdInput.type = 'hidden';
        orderIdInput.name = 'order_id';
        orderIdInput.value = orderId;
        
        form.appendChild(reorderInput);
        form.appendChild(orderIdInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Handle form submissions with AJAX
document.getElementById('cancelForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('../includes/dashboard-handlers.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeCancelModal();
            location.reload();
        } else {
            alert(data.message || 'Error submitting cancellation request');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error submitting cancellation request');
    });
});

document.getElementById('returnForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('../includes/dashboard-handlers.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeReturnModal();
            location.reload();
        } else {
            alert(data.message || 'Error submitting return request');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error submitting return request');
    });
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
});

document.querySelectorAll('.modal-box').forEach(modal => {
    modal.addEventListener('click', function(e) {
        e.stopPropagation();
    });
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCancelModal();
        closeReturnModal();
    }
});
</script>