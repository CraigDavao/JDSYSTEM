<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration
require_once __DIR__ . '/../config.php';

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$hasSale = !empty($product['sale_price']) && $product['sale_price'] > 0 && $product['sale_price'] < $product['price'];
?>
<a href="<?= htmlspecialchars($product_link) ?>" class="product-card">
    <div class="product-image-container">
        <img src="<?= SITE_URL; ?>uploads/<?= htmlspecialchars($product['image'] ?: 'sample1.jpg'); ?>"
             alt="<?= htmlspecialchars($product['name']); ?>"
             class="product-thumb"
             onerror="this.src='<?= SITE_URL; ?>uploads/sample1.jpg'">
        <?php if ($hasSale): ?>
            <div class="sale-badge">Sale</div>
        <?php endif; ?>
    </div>
    <div class="product-info">
        <h3 class="product-name"><?= htmlspecialchars($product['name']); ?></h3>
        <div class="product-price">
            <?php if ($hasSale): ?>
                <span class="sale-price">₱<?= number_format($product['sale_price'], 2); ?></span>
                <span class="original-price">₱<?= number_format($product['price'], 2); ?></span>
            <?php else: ?>
                <span class="current-price">₱<?= number_format($product['price'], 2); ?></span>
            <?php endif; ?>
        </div>
    </div>
</a>


