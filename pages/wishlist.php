<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo "<p style='text-align:center;margin-top:4rem;'>Please <a href='" . SITE_URL . "auth/login.php'>log in</a> to view your wishlist.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// ✅ UPDATED QUERY: Get color-specific images and ALL PRICE COLUMNS
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
  <style>
    /* Ensure even height for all wishlist items */
    .wishlist-item {
        min-height: 120px;
        align-items: center;
    }
    
    .wishlist-product-info {
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .wishlist-action-buttons {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .wishlist-product-price {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
    }
    
    /* FIX REMOVE BUTTON ALIGNMENT */
    .btn-remove-wishlist {
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        min-width: 80px;
        padding: 0.5rem 0.8rem;
        background: none;
        border: 1.5px solid #ff6b6b;
        color: #ff6b6b;
        cursor: pointer;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }

    .btn-remove-wishlist:hover {
        background: #ff6b6b;
        color: white;
        transform: translateY(-1px);
    }

    .wishlist-item > .btn-remove-wishlist {
        align-self: center;
        justify-self: center;
    }

    /* Price display styling */
    .wishlist-price-display {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 5px;
    }

    .original-price {
        text-decoration: line-through;
        color: #999;
        font-size: 0.9rem;
    }

    .sale-price {
        color: #8b5a2b;
        font-weight: 700;
        font-size: 1rem;
    }

    .discount-badge {
        color: #28a745;
        font-size: 0.8rem;
        background: #f0f8f0;
        padding: 2px 6px;
        border-radius: 4px;
    }

    .final-price {
        color: #8b5a2b;
        font-weight: 700;
        font-size: 1rem;
    }

    /* Animation styles */
    @keyframes fadeOut {
        from { opacity: 1; transform: scale(1); }
        to { opacity: 0; transform: scale(0.8); }
    }
  </style>
</head>
<body>

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
              // 🎯 PRICE CALCULATION LOGIC BASED ON YOUR DATABASE COLUMNS
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
                    
                    <!-- 🎯 UPDATED PRICE DISPLAY - Only show sale info here -->
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
                
                <!-- 🎯 UPDATED PRICE COLUMN - Show only the final price here -->
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

  <!-- Include your JavaScript file -->
  <script>
    // Define SITE_URL for JavaScript
    const SITE_URL = '<?= SITE_URL; ?>';
  </script>
  <script src="<?= SITE_URL; ?>js/wishlist.js?v=<?= time(); ?>"></script>
</body>
</html>