<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo "<p style='text-align:center;margin-top:4rem;'>Please <a href='" . SITE_URL . "auth/login.php'>log in</a> to view your wishlist.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Get wishlist items with all price columns
$sql = "
SELECT 
    w.id AS wishlist_id,
    w.product_id,
    w.color_id,
    COALESCE(pc.color_name, '') AS color_name,
    p.name,
    p.price,
    p.sale_price,
    p.actual_sale_price,
    COALESCE(
        (SELECT pi.image
         FROM product_images pi
         WHERE pi.product_id = p.id
           AND pi.color_name = pc.color_name
         ORDER BY pi.sort_order ASC, pi.id ASC
         LIMIT 1),
        (SELECT pi2.image
         FROM product_images pi2
         WHERE pi2.product_id = p.id
         ORDER BY pi2.sort_order ASC, pi2.id ASC
         LIMIT 1)
    ) AS image,
    COALESCE(
        (SELECT pi.image_format
         FROM product_images pi
         WHERE pi.product_id = p.id
           AND pi.color_name = pc.color_name
         ORDER BY pi.sort_order ASC, pi.id ASC
         LIMIT 1),
        (SELECT pi2.image_format
         FROM product_images pi2
         WHERE pi2.product_id = p.id
         ORDER BY pi2.sort_order ASC, pi2.id ASC
         LIMIT 1)
    ) AS image_format
FROM wishlist w
JOIN products p ON w.product_id = p.id
LEFT JOIN product_colors pc ON w.color_id = pc.id
WHERE w.user_id = ?
ORDER BY w.added_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Get wishlist count
$count_sql = "SELECT COUNT(*) as total_count FROM wishlist WHERE user_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_count = $count_result->fetch_assoc()['total_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= SITE_URL; ?>css/wishlist.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <title>My Wishlist | Jolly Dolly</title>
</head>
<body>

    <!-- Custom Confirmation Modal -->
  <div class="custom-modal" id="confirmationModal">
    <div class="custom-modal-content">
      <h3 id="modalTitle">Remove Item</h3>
      <p id="modalMessage">Are you sure you want to remove this item from your wishlist?</p>
      <div class="custom-modal-buttons">
        <button class="custom-modal-btn custom-modal-cancel" id="modalCancel">Cancel</button>
        <button class="custom-modal-btn custom-modal-confirm" id="modalConfirm">Remove</button>
      </div>
    </div>
  </div>

  <div class="wishlist-dashboard">
    <h2>My Wishlist</h2>
    
    <div class="wishlist-layout">
      <!-- Left Column - Wishlist Items -->
      <div class="wishlist-items-column">
        <!-- Wishlist Actions -->
        <div class="wishlist-actions">
          <label class="wishlist-select-all-label">
            <input type="checkbox" id="wishlist-select-all"> Select All
          </label>
          <button class="btn-clear-wishlist" id="clear-wishlist">Clear Wishlist</button>
        </div>
        
        <!-- Wishlist Header -->
        <div class="wishlist-header">
          <div></div>
          <div>Image</div>
          <div>Product</div>
          <div>Actions</div>
          <div>Price</div>
          <div>Remove</div>
        </div>
        
        <!-- Wishlist Items -->
        <div id="wishlist-items">
          <?php if ($result->num_rows > 0): ?>
            <?php while ($item = $result->fetch_assoc()): ?>
              <?php
              // Price calculation logic
              $regularPrice = floatval($item['price']);
              $salePrice = floatval($item['sale_price']);
              $actualSalePrice = floatval($item['actual_sale_price']);
              
              $finalPrice = $regularPrice;
              $isOnSale = false;
              $discountPercentage = 0;
              
              // Check if item is on sale
              if ($salePrice > 0 && $actualSalePrice > 0) {
                  $finalPrice = $actualSalePrice;
                  $isOnSale = true;
                  $discountPercentage = round((($regularPrice - $actualSalePrice) / $regularPrice) * 100);
              }
              
              // Handle blob image conversion
              if (!empty($item['image'])) {
                  $mimeType = !empty($item['image_format']) ? $item['image_format'] : 'image/jpeg';
                  $imageSrc = 'data:' . $mimeType . ';base64,' . base64_encode($item['image']);
              } else {
                  $imageSrc = SITE_URL . 'uploads/sample1.jpg';
              }
              ?>
              
              <div class="wishlist-item" data-wishlist-id="<?= $item['wishlist_id'] ?>">
                <input type="checkbox" class="wishlist-select-item" data-wishlist-id="<?= $item['wishlist_id'] ?>">
                
                <img src="<?= $imageSrc; ?>" alt="<?= htmlspecialchars($item['name']); ?>" 
                     onerror="this.src='<?= SITE_URL; ?>uploads/sample1.jpg'">
                
                <div class="wishlist-product-info">
                  <h3 class="wishlist-product-name"><?= htmlspecialchars($item['name']); ?></h3>
                  <div class="wishlist-product-details">
                    <?php if (!empty($item['color_name'])): ?>
                      <span class="wishlist-variant-color"><?= htmlspecialchars($item['color_name']); ?></span>
                    <?php endif; ?>
                    
                    <!-- Price Display -->
                    <div class="wishlist-price-display">
                      <?php if ($isOnSale): ?>
                        <span class="original-price">₱<?= number_format($regularPrice, 2); ?></span>
                        <span class="sale-price">₱<?= number_format($actualSalePrice, 2); ?></span>
                        <span class="discount-badge"><?= $discountPercentage ?>% OFF</span>
                      <?php else: ?>
                        <!-- Show nothing here for non-sale items to avoid duplication -->
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                
                <div class="wishlist-action-buttons">
                  <button class="btn-move-to-cart" 
                          data-wishlist-id="<?= $item['wishlist_id']; ?>"
                          data-product-id="<?= $item['product_id']; ?>"
                          data-color-id="<?= $item['color_id'] ?? ''; ?>"
                          data-price="<?= $finalPrice; ?>">
                    Add to Cart
                  </button>
                </div>
                
                <!-- Price Column -->
                <div class="wishlist-product-price">
                  <?php if ($isOnSale): ?>
                    <span class="final-price">₱<?= number_format($finalPrice, 2); ?></span>
                  <?php else: ?>
                    <span class="final-price">₱<?= number_format($regularPrice, 2); ?></span>
                  <?php endif; ?>
                </div>
                
                <button class="btn-remove-wishlist" data-id="<?= $item['wishlist_id']; ?>" title="Remove from wishlist">
                  Remove
                </button>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="wishlist-empty">
              <h3>Your wishlist is empty</h3>
              <p>Save items you love for later!</p>
              <a href="<?= SITE_URL; ?>pages/new.php" class="btn-continue-shopping">Continue Shopping</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
      
      <!-- Right Column - Wishlist Summary -->
      <div class="wishlist-summary-column">
        <h3>Wishlist Summary</h3>
        
        <!-- Move All to Cart -->
        <div class="wishlist-move-all">
          <h4>Quick Actions</h4>
          <div class="wishlist-move-actions">
            <button class="btn-move-all-cart" id="move-all-to-cart">
              Move All to Cart
            </button>
          </div>
        </div>
        
        <!-- Wishlist Stats -->
        <div class="wishlist-stats-section">
          <h4>Wishlist Stats</h4>
          <div class="wishlist-stats-breakdown">
            <div class="wishlist-stat-row">
              <span class="wishlist-stat-label">Total Items:</span>
              <span class="wishlist-stat-value"><?= $total_count ?></span>
            </div>
            <div class="wishlist-stat-row">
              <span class="wishlist-stat-label">Selected Items:</span>
              <span class="wishlist-stat-value" id="selected-count">0</span>
            </div>
          </div>
        </div>
        
        <!-- Price Summary -->
        <div class="wishlist-stats-section">
          <h4>Price Summary</h4>
          <div class="wishlist-stats-breakdown">
            <div class="wishlist-stat-row">
              <span class="wishlist-stat-label">Total Value:</span>
              <span class="wishlist-stat-value" id="total-value">₱0.00</span>
            </div>
            <div class="wishlist-stat-row">
              <span class="wishlist-stat-label">You Save:</span>
              <span class="wishlist-stat-value" id="total-savings" style="color: #28a745;">₱0.00</span>
            </div>
          </div>
        </div>
        
        <!-- Continue Shopping -->
        <div class="wishlist-continue-shopping">
          <p>Continue discovering more products!</p>
          <a href="<?= SITE_URL; ?>pages/new.php" class="btn-continue-shopping">
            Continue Shopping
          </a>
        </div>
      </div>
    </div>
  </div>

  <?php require_once __DIR__ . '/../includes/footer.php'; ?>

  <script>
    // Define SITE_URL for JavaScript
    const SITE_URL = '<?= SITE_URL; ?>';
  </script>
  <script src="<?= SITE_URL; ?>js/wishlist.js?v=<?= time(); ?>"></script>
</body>
</html>