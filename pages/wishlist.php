<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo "<p style='text-align:center;margin-top:4rem;'>Please <a href='" . SITE_URL . "auth/login.php'>log in</a> to view your wishlist.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// ✅ UPDATED QUERY: Get color-specific images using color_name
$sql = "
SELECT 
    w.id AS wishlist_id,
    w.product_id,
    w.color_id,
    COALESCE(pc.color_name, '') AS color_name,
    p.name,
    p.price,
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
              // Handle blob image conversion
              if (!empty($item['image'])) {
                  $mimeType = !empty($item['image_format']) ? $item['image_format'] : 'image/jpeg';
                  $imageSrc = 'data:' . $mimeType . ';base64,' . base64_encode($item['image']);
              } else {
                  $imageSrc = SITE_URL . 'uploads/sample1.jpg';
              }
              
              // ✅ FIXED: Create product link with color ID as the main parameter
              $product_link = SITE_URL . 'pages/product.php?id=' . $item['color_id'];
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
                    <span class="wishlist-product-price">₱<?= number_format($item['price'], 2); ?></span>
                  </div>
                </div>
                
                <div class="wishlist-action-buttons">
                  <button class="btn-move-to-cart" data-wishlist-id="<?= $item['wishlist_id']; ?>">
                    Add to Cart
                  </button>
                </div>
                
                <div class="wishlist-product-price">
                  ₱<?= number_format($item['price'], 2); ?>
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
document.addEventListener("DOMContentLoaded", function() {
    console.log('Wishlist page loaded');

    // Wishlist count updater
    const wishlistCount = document.querySelector(".wishlist-count");
    if (wishlistCount) {
        function updateWishlistCount() {
            fetch("<?= SITE_URL; ?>actions/wishlist-count.php")
                .then(response => {
                    if (!response.ok) throw new Error("Network response was not ok");
                    return response.json();
                })
                .then(data => {
                    wishlistCount.textContent = data.count ?? 0;
                })
                .catch(error => {
                    console.error("❌ Error fetching wishlist count:", error);
                    wishlistCount.textContent = "0";
                });
        }
        updateWishlistCount();
        setInterval(updateWishlistCount, 10000);
    }

    // Select All functionality
    document.getElementById('wishlist-select-all')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.wishlist-select-item');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateSelectedCount();
    });

    // Individual checkbox changes
    document.querySelectorAll('.wishlist-select-item').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });

    // Update selected count
    function updateSelectedCount() {
        const selectedCount = document.querySelectorAll('.wishlist-select-item:checked').length;
        document.getElementById('selected-count').textContent = selectedCount;
    }

    // Move to cart functionality
    document.querySelectorAll('.btn-move-to-cart').forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const wishlistId = this.dataset.wishlistId;
            await moveToCart(wishlistId);
        });
    });

    // Move all to cart
    document.getElementById('move-all-to-cart')?.addEventListener('click', async function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const selectedItems = getSelectedWishlistIds();
        if (selectedItems.length === 0) {
            showNotification('Please select items to move to cart', 'error');
            return;
        }

        if (confirm(`Move ${selectedItems.length} item(s) to cart?`)) {
            for (const wishlistId of selectedItems) {
                await moveToCart(wishlistId);
            }
        }
    });

    async function moveToCart(wishlistId) {
        try {
            const formData = new URLSearchParams();
            formData.append("wishlist_id", wishlistId);
            
            const res = await fetch("<?= SITE_URL; ?>actions/wishlist-move-to-cart.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: formData
            });
            
            const result = await res.text();
            
            if (result.trim() === "success") {
                showNotification('Item moved to cart successfully', 'success');
                // Optionally remove from wishlist after moving to cart
                const itemElement = document.querySelector(`[data-wishlist-id="${wishlistId}"]`);
                if (itemElement) {
                    itemElement.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(() => {
                        itemElement.remove();
                        updateWishlistUI();
                    }, 300);
                }
            } else {
                showNotification('Failed to move item to cart', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Network error: ' + error.message, 'error');
        }
    }

    function getSelectedWishlistIds() {
        const selectedItems = [];
        document.querySelectorAll('.wishlist-select-item:checked').forEach(checkbox => {
            selectedItems.push(checkbox.dataset.wishlistId);
        });
        return selectedItems;
    }

    // ✅ SINGLE Remove item from wishlist handler
    document.addEventListener("click", async (e) => {
        if (e.target.closest(".btn-remove-wishlist")) {
            e.preventDefault();
            e.stopPropagation();
            
            const removeBtn = e.target.closest(".btn-remove-wishlist");
            const wishlistId = removeBtn.dataset.id;
            const itemElement = removeBtn.closest('.wishlist-item');
            
            if (confirm('Remove this item from your wishlist?')) {
                try {
                    console.log('Removing wishlist item ID:', wishlistId);
                    
                    const formData = new URLSearchParams();
                    formData.append("wishlist_id", wishlistId);
                    
                    const res = await fetch("<?= SITE_URL; ?>actions/wishlist-remove.php", {
                        method: "POST",
                        headers: {"Content-Type": "application/x-www-form-urlencoded"},
                        body: formData
                    });
                    
                    const result = await res.text();
                    console.log('Remove response:', result);
                    
                    if (result.trim() === "success") {
                        itemElement.style.animation = 'fadeOut 0.3s ease';
                        setTimeout(() => {
                            itemElement.remove();
                            updateWishlistUI();
                        }, 300);
                        showNotification('Item removed from wishlist', 'success');
                    } else {
                        let errorMessage = 'Failed to remove item';
                        
                        switch(result.trim()) {
                            case 'not_logged_in':
                                errorMessage = 'Please log in to continue';
                                break;
                            case 'not_found':
                                errorMessage = 'Item not found in your wishlist';
                                break;
                            case 'database_error':
                                errorMessage = 'Database error occurred';
                                break;
                            case 'invalid_id':
                                errorMessage = 'Invalid item ID';
                                break;
                            case 'invalid_method':
                                errorMessage = 'Invalid request';
                                break;
                            default:
                                errorMessage = 'Failed to remove item: ' + result;
                        }
                        
                        showNotification(errorMessage, 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showNotification('Network error: ' + error.message, 'error');
                }
            }
        }
    });

    // Clear all wishlist items
    document.getElementById('clear-wishlist')?.addEventListener('click', async function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (confirm('Clear all items from your wishlist?')) {
            try {
                const res = await fetch("<?= SITE_URL; ?>actions/wishlist-clear.php", {
                    method: "POST",
                    headers: {"Content-Type": "application/x-www-form-urlencoded"}
                });
                
                const result = await res.text();
                console.log('Clear response:', result);
                
                if (result.trim() === "success") {
                    document.getElementById('wishlist-items').innerHTML = `
                        <div class="wishlist-empty">
                          <h3>Your wishlist is empty</h3>
                          <p>Save items you love for later!</p>
                          <a href='<?= SITE_URL; ?>pages/new.php' class='btn-continue-shopping'>Continue Shopping</a>
                        </div>`;
                    updateWishlistUI();
                    showNotification('Wishlist cleared', 'success');
                } else {
                    showNotification('Failed to clear wishlist: ' + result, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Network error: ' + error.message, 'error');
            }
        }
    });

    // Update UI after changes
    function updateWishlistUI() {
        const items = document.querySelectorAll('.wishlist-item');
        const itemCount = items.length;
        
        // Update wishlist count in header
        const wishlistBadge = document.querySelector('.wishlist-count');
        if (wishlistBadge) {
            wishlistBadge.textContent = itemCount;
        }
        
        // Update selected count
        updateSelectedCount();
        
        // If no items, show empty state
        if (itemCount === 0 && !document.querySelector('.wishlist-empty')) {
            document.getElementById('wishlist-items').innerHTML = `
                <div class="wishlist-empty">
                  <h3>Your wishlist is empty</h3>
                  <p>Save items you love for later!</p>
                  <a href='<?= SITE_URL; ?>pages/new.php' class='btn-continue-shopping'>Continue Shopping</a>
                </div>`;
        }
    }

    // Notification function
    function showNotification(message, type = 'info') {
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notif => notif.remove());
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 300px;
            animation: slideIn 0.3s ease;
        `;
        
        if (type === 'success') {
            notification.style.backgroundColor = '#28a745';
        } else if (type === 'error') {
            notification.style.backgroundColor = '#dc3545';
        } else if (type === 'info') {
            notification.style.backgroundColor = '#17a2b8';
        } else {
            notification.style.backgroundColor = '#6c757d';
        }
        
        notification.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer;">×</button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 3000);
    }

    // Add CSS for animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes fadeOut {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0; transform: scale(0.8); }
        }
    `;
    document.head.appendChild(style);

    // Initialize selected count
    updateSelectedCount();
});
</script>
</body>
</html>