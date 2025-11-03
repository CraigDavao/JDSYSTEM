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
  <title>My Wishlist | Jolly Dolly</title>
</head>
<body>

  <div class="wishlist-container">
    <div class="wishlist-header">
      <h1 class="wishlist-title">My Wishlist</h1>
      <div class="wishlist-stats">
        <span class="item-count"><?= $total_count ?> item<?= $total_count != 1 ? 's' : '' ?></span>
        <?php if ($total_count > 0): ?>
          <button id="clear-wishlist" class="clear-all-btn">Clear All</button>
        <?php endif; ?>
      </div>
    </div>

    <div id="wishlist-items" class="wishlist-grid">
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
          
          // Create product link with color parameter
          $product_link = SITE_URL . 'pages/products.php?id=' . $item['product_id'];
          if (!empty($item['color_id'])) {
              $product_link .= '&color_id=' . $item['color_id'];
          }
          ?>
          
          <div class="wishlist-item" data-wishlist-id="<?= $item['wishlist_id'] ?>">
            <a href="<?= $product_link ?>" class="wishlist-link">
              <div class="item-image">
                <img src="<?= $imageSrc; ?>" alt="<?= htmlspecialchars($item['name']); ?>" 
                     onerror="this.src='<?= SITE_URL; ?>uploads/sample1.jpg'">
              </div>
              <div class="item-info">
                <h3 class="item-name"><?= htmlspecialchars($item['name']); ?></h3>
                <?php if (!empty($item['color_name'])): ?>
                  <div class="item-color">
                    <span class="color-badge">Color: <?= htmlspecialchars($item['color_name']); ?></span>
                  </div>
                <?php endif; ?>
              </div>
            </a>
            
            <div class="item-actions">
          <a href="#" class="remove-wishlist" data-id="<?= $item['wishlist_id']; ?>" title="Remove from wishlist">
              <i class="fa-solid fa-trash"></i>
          </a>
        </div>

          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="wishlist-empty">
          <div class="empty-icon">💝</div>
          <h3>Your wishlist is empty</h3>
          <p>Save items you love for later!</p>
          <a href="<?= SITE_URL; ?>pages/new.php" class="continue-shopping">Continue Shopping</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php require_once __DIR__ . '/../includes/footer.php'; ?>

  <script>
document.addEventListener("DOMContentLoaded", function() {
    console.log('Wishlist page loaded');


    document.addEventListener("DOMContentLoaded", function() {
    console.log('Wishlist page loaded');

    // ... your existing code for remove button, notifications, etc. ...

    // Make the entire wishlist item clickable
    document.querySelectorAll('.wishlist-item').forEach(item => {
        item.addEventListener('click', function(e) {
            // Avoid clicking the remove button
            if (!e.target.classList.contains('remove-wishlist')) {
                const link = this.querySelector('.wishlist-link');
                if (link) window.location.href = link.href;
            }
        });
    });

});


    // Remove item from wishlist
    document.addEventListener("click", async (e) => {
        if (e.target.classList.contains("remove-wishlist")) {
            const wishlistId = e.target.dataset.id;
            const itemElement = e.target.closest('.wishlist-item');
            
            if (confirm('Remove this item from your wishlist?')) {
                try {
                    const res = await fetch("<?= SITE_URL; ?>actions/wishlist-remove.php", {
                        method: "POST",
                        headers: {"Content-Type": "application/x-www-form-urlencoded"},
                        body: "id=" + wishlistId
                    });
                    
                    const result = await res.text();
                    
                    if (result.trim() === "success") {
                        itemElement.remove();
                        updateWishlistUI();
                        showNotification('Item removed from wishlist', 'success');
                    } else {
                        showNotification('Failed to remove item', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showNotification('Network error', 'error');
                }
            }
        }
    });

    // Add to Cart from wishlist
    document.addEventListener("click", async (e) => {
        if (e.target.classList.contains("add-to-cart-btn")) {
            console.log('Add to cart clicked');
            
            const productId = e.target.dataset.productId;
            const colorId = e.target.dataset.colorId;
            const colorName = e.target.dataset.colorName;
            const button = e.target;
            const originalText = button.textContent;
            
            console.log('Product ID:', productId, 'Color ID:', colorId, 'Color Name:', colorName);
            
            // Check if colorId is valid
            if (!colorId || colorId === '0' || colorId === 'null') {
                showNotification('This item has no color selected. Please visit the product page to add to cart.', 'error');
                return;
            }
            
            button.textContent = 'Adding...';
            button.disabled = true;
            
            try {
                const formData = new URLSearchParams();
                formData.append("product_id", productId);
                formData.append("color_id", colorId);
                formData.append("quantity", 1);
                formData.append("size", "M");
                
                if (colorName && colorName !== '' && colorName !== 'null') {
                    formData.append("color_name", colorName);
                }

                console.log('Sending form data:', formData.toString());

                const response = await fetch("<?= SITE_URL; ?>actions/cart-add.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: formData,
                    credentials: "same-origin",
                });

                console.log('Response status:', response.status);
                
                const result = await response.json();
                console.log('Cart add result:', result);
                
                if (result.status === "success") {
                    showNotification('Added to cart!', 'success');
                } else if (result.status === "exists") {
                    showNotification('Already in cart', 'info');
                } else if (result.message === "not_logged_in") {
                    showNotification('Please log in first', 'error');
                } else if (result.message === "invalid_color" || result.message === "color_not_found") {
                    showNotification('Invalid color selection. Please visit product page.', 'error');
                } else {
                    showNotification(result.message || 'Failed to add to cart', 'error');
                }
            } catch (error) {
                console.error("Cart Error:", error);
                showNotification('Network error: ' + error.message, 'error');
            } finally {
                button.textContent = originalText;
                button.disabled = false;
            }
        }
    });

    // Clear all wishlist items
    document.getElementById('clear-wishlist')?.addEventListener('click', async function() {
        if (confirm('Clear all items from your wishlist?')) {
            try {
                const res = await fetch("<?= SITE_URL; ?>actions/wishlist-clear.php", {
                    method: "POST",
                    headers: {"Content-Type": "application/x-www-form-urlencoded"}
                });
                
                const result = await res.text();
                
                if (result.trim() === "success") {
                    document.getElementById('wishlist-items').innerHTML = `
                        <div class="wishlist-empty">
                          <div class="empty-icon">💝</div>
                          <h3>Your wishlist is empty</h3>
                          <p>Save items you love for later!</p>
                          <a href='<?= SITE_URL; ?>pages/new.php' class='continue-shopping'>Continue Shopping</a>
                        </div>`;
                    updateWishlistUI();
                    showNotification('Wishlist cleared', 'success');
                } else {
                    showNotification('Failed to clear wishlist', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Network error', 'error');
            }
        }
    });

    // Update UI after changes
    function updateWishlistUI() {
        const items = document.querySelectorAll('.wishlist-item');
        const itemCount = items.length;
        const countElement = document.querySelector('.item-count');
        const clearBtn = document.getElementById('clear-wishlist');
        
        if (countElement) {
            countElement.textContent = itemCount + ' item' + (itemCount !== 1 ? 's' : '');
        }
        
        if (clearBtn) {
            clearBtn.style.display = itemCount > 0 ? 'block' : 'none';
        }
        
        // Update wishlist count in header
        const wishlistBadge = document.getElementById('wishlist-count');
        if (wishlistBadge) {
            wishlistBadge.textContent = itemCount;
        }
    }

    // Notification function
    function showNotification(message, type = 'info') {
        // Remove existing notifications
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
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 3000);
    }

    // Add CSS for notification animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
});
</script>
</body>
</html>