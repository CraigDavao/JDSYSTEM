<?php
/**
 * Color Selector Component - FIXED VERSION
 * ✅ Updates size buttons when color changes
 * ✅ No page refresh
 * ✅ No session persistence - always uses URL parameter
 */

if (!isset($product_id) || !isset($colors)) {
    return;
}

// ✅ Get ID from URL (product color id) - ONLY source of truth
$current_color_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// ✅ Default to first or default color if no URL parameter
if (!$current_color_id && !empty($colors)) {
    foreach ($colors as $color) {
        if (!empty($color['is_default'])) {
            $current_color_id = $color['id'];
            break;
        }
    }
    if (!$current_color_id) $current_color_id = $colors[0]['id'];
}

// ✅ Find current color
$current_color = null;
foreach ($colors as $color) {
    if ($color['id'] == $current_color_id) {
        $current_color = $color;
        break;
    }
}
if (!$current_color && !empty($colors)) {
    $current_color = $colors[0];
    $current_color_id = $current_color['id'];
}
?>

<div class="color-selector" data-product-id="<?= $product_id ?>">
  <div class="color-options">
    <?php foreach ($colors as $color): 
        $image_data = '';
        if (!empty($color['image'])) {
            $mimeType = $color['image_format'] ?? 'image/jpeg';
            $image_data = 'data:' . $mimeType . ';base64,' . base64_encode($color['image']);
        }
    ?>
      <div class="color-option <?= ($color['id'] == $current_color_id) ? 'active' : '' ?>" 
           data-color-id="<?= $color['id'] ?>"
           data-color-name="<?= htmlspecialchars($color['color_name']) ?>"
           data-color-image="<?= htmlspecialchars($image_data) ?>"
           title="<?= htmlspecialchars($color['color_name']) ?>">
        <span class="color-text"><?= htmlspecialchars($color['color_name']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <input type="hidden" name="selected_color_id" id="selected-color-id" value="<?= $current_color_id ?>">
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const productId = document.querySelector(".color-selector")?.dataset.productId;
  const colorOptions = document.querySelectorAll(".color-option");
  const selectedColorInput = document.getElementById("selected-color-id");
  const mainImage = document.querySelector(".main-product-image");

  // ✅ Function to update size buttons based on stock
  function updateSizeButtons(stockBySize) {
    const sizeButtons = document.querySelectorAll('.size-option');
    
    sizeButtons.forEach(button => {
      const size = button.dataset.size;
      const sizeQty = stockBySize[size] || 0;
      const isDisabled = sizeQty === 0;
      
      // Update button disabled state
      button.disabled = isDisabled;
      
      // Update button classes
      button.classList.toggle('disabled', isDisabled);
      
      // Update out-of-stock indicator
      let outOfStockSpan = button.querySelector('.size-out-of-stock');
      if (isDisabled && !outOfStockSpan) {
        outOfStockSpan = document.createElement('span');
        outOfStockSpan.className = 'size-out-of-stock';
        outOfStockSpan.textContent = '(X)';
        button.appendChild(outOfStockSpan);
      } else if (!isDisabled && outOfStockSpan) {
        outOfStockSpan.remove();
      }
      
      // If current active size is now disabled, remove active class
      if (button.classList.contains('active') && isDisabled) {
        button.classList.remove('active');
      }
    });
    
    // Auto-select first available size if no active size
    const activeSize = document.querySelector('.size-option.active');
    if (!activeSize) {
      const firstAvailable = document.querySelector('.size-option:not(.disabled)');
      if (firstAvailable) {
        firstAvailable.classList.add('active');
        document.getElementById('selected-size').value = firstAvailable.dataset.size;
      }
    }
  }

  // ✅ Function to update quantity and buttons based on stock
  function updateQuantityAndButtons(currentStock) {
    const quantityInput = document.getElementById('quantity');
    const minusBtn = document.getElementById('minus-btn');
    const plusBtn = document.getElementById('plus-btn');
    const addToCartBtn = document.querySelector('.add-to-cart');
    const buyNowBtn = document.getElementById('buy-now-btn');
    const wishlistBtn = document.querySelector('.wishlist-btn');
    const quantitySelector = document.querySelector('.quantity-selector');
    
    const isOutOfStock = currentStock === 0;
    const maxQuantity = Math.min(10, currentStock);
    
    // Update quantity input
    if (quantityInput) {
      quantityInput.disabled = isOutOfStock;
      quantityInput.max = maxQuantity;
    }
    
    // Update quantity buttons
    if (minusBtn) minusBtn.disabled = isOutOfStock;
    if (plusBtn) plusBtn.disabled = isOutOfStock;
    
    // Update Add to Cart button
    if (addToCartBtn) {
      addToCartBtn.disabled = isOutOfStock;
      addToCartBtn.textContent = isOutOfStock ? 'Out of Stock' : 'Add to Cart';
    }
    
    // Update Buy Now button
    if (buyNowBtn) {
      buyNowBtn.disabled = isOutOfStock;
      buyNowBtn.textContent = isOutOfStock ? 'Out of Stock' : 'Buy Now';
    }
    
    // Update Wishlist button
    if (wishlistBtn) {
      wishlistBtn.disabled = isOutOfStock;
    }
    
    // Update quantity selector styling
    if (quantitySelector) {
      if (isOutOfStock) {
        quantitySelector.classList.add('out-of-stock');
      } else {
        quantitySelector.classList.remove('out-of-stock');
      }
    }
  }

  // ✅ Simple function to update stock information
  async function updateStockInfo(colorId) {
    try {
      const response = await fetch(`<?= SITE_URL ?>actions/get-color-stock.php?color_id=${colorId}`);
      const data = await response.json();
      
      if (data.status === 'success') {
        // Update stock display
        updateStockDisplay(data.stock_info);
        // Update size buttons - THIS IS THE KEY FIX
        updateSizeButtons(data.stock_info.stock_by_size);
        // Update quantity and buttons
        updateQuantityAndButtons(data.stock_info.current_stock);
      }
    } catch (error) {
      console.error('Error fetching stock:', error);
    }
  }

  // ✅ ONLY updates the stock display section
  function updateStockDisplay(stockInfo) {
    const stockInfoElement = document.querySelector('.stock-info');
    if (!stockInfoElement) return;
    
    const currentStock = stockInfo.current_stock;
    const stockBySize = stockInfo.stock_by_size;
    
    let stockHTML = '';
    
    if (currentStock > 0) {
      if (currentStock <= 10) {
        stockHTML = `
          <div class="stock-low">
            <span class="stock-text">Only ${currentStock} left in stock!</span>
          </div>
        `;
      } else {
        stockHTML = `
          <div class="stock-available">
            <span class="stock-text">In Stock (${currentStock} available)</span>
          </div>
        `;
      }
      
      // Add size stock information (read-only display)
      stockHTML += `
        <div class="size-stock-info">
          <h4>Available by Size:</h4>
          <div class="size-stock-grid">
      `;
      
      ['S', 'M', 'L', 'XL'].forEach(size => {
        const sizeQty = stockBySize[size] || 0;
        stockHTML += `
          <div class="size-stock-item">
            <span class="size-label">Size ${size}:</span>
            <span class="size-quantity ${sizeQty == 0 ? 'out-of-stock' : 'in-stock'}">
              ${sizeQty > 0 ? sizeQty + ' available' : 'Out of stock'}
            </span>
          </div>
        `;
      });
      
      stockHTML += `
          </div>
        </div>
      `;
    } else {
      stockHTML = `
        <div class="stock-out">
          <span class="stock-icon">❌</span>
          <span class="stock-text">Out of Stock</span>
        </div>
      `;
    }
    
    stockInfoElement.innerHTML = stockHTML;
  }

  // ✅ Handle color click - updates image, stock, AND size buttons
  colorOptions.forEach(option => {
    option.addEventListener("click", () => {
      const colorId = option.dataset.colorId;
      const imageSrc = option.dataset.colorImage;

      // Update active color
      colorOptions.forEach(opt => opt.classList.remove("active"));
      option.classList.add("active");
      selectedColorInput.value = colorId;

      // Update image only
      if (mainImage && imageSrc) {
        mainImage.src = imageSrc;
      }

      // ✅ REMOVED: Session storage saving
      // sessionStorage.setItem("selected_color_" + productId, colorId);

      // Update URL (existing functionality)
      const url = new URL(window.location.href);
      url.searchParams.set("id", colorId);
      window.history.pushState({}, "", url);

      // Update stock information AND size buttons
      updateStockInfo(colorId);
    });
  });

  // ✅ Load initial stock for current color
  if (selectedColorInput.value) {
    updateStockInfo(selectedColorInput.value);
  }
});
</script>