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

// ✅ Remove duplicate colors by color_name to prevent duplicates
$unique_colors = [];
foreach ($colors as $color) {
    $color_name = $color['color_name'];
    if (!isset($unique_colors[$color_name])) {
        $unique_colors[$color_name] = $color;
    }
}
$colors = array_values($unique_colors);
?>

<div class="color-selector" data-product-id="<?= $product_id ?>">
  <label>Color:</label>
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

  console.log("🎨 Color selector initialized - Product ID:", productId);
  console.log("🎨 Found", colorOptions.length, "color options");

  // ✅ Function to update ALL product information when color changes
  async function updateProductForColor(colorId) {
    console.log("🔄 Updating product for color ID:", colorId);
    
    try {
      const response = await fetch(`<?= SITE_URL ?>actions/get-color-stock.php?color_id=${colorId}`);
      const data = await response.json();
      
      console.log("📊 Stock API response:", data);
      
      if (data.status === 'success') {
        const stockInfo = data.stock_info;
        const stockBySize = stockInfo.stock_by_size;
        const totalStock = stockInfo.current_stock;
        
        // 1. Update stock display
        updateStockDisplay(totalStock, stockBySize);
        
        // 2. Update size buttons with new stock data
        updateSizeButtons(stockBySize);
        
        // 3. Update quantity based on selected size's stock
        updateQuantityForSelectedSize(stockBySize);
        
        // 4. Dispatch event for other components
        const event = new CustomEvent('colorChanged', {
          detail: {
            colorId: colorId,
            stockInfo: stockInfo
          }
        });
        document.dispatchEvent(event);
        
        console.log("✅ Product updated successfully for color:", colorId);
      } else {
        console.error("❌ Stock API error:", data.message);
      }
    } catch (error) {
      console.error('❌ Error fetching stock:', error);
    }
  }

  // ✅ Update stock display
  function updateStockDisplay(totalStock, stockBySize) {
    const stockInfoElement = document.getElementById('stock-info');
    if (!stockInfoElement) return;
    
    console.log("📊 Updating stock display - Total:", totalStock, "By size:", stockBySize);
    
    let stockHTML = '';
    
    if (totalStock > 0) {
      if (totalStock <= 10) {
        stockHTML = `
          <div class="stock-low">
            <span class="stock-text">Only ${totalStock} left in stock!</span>
          </div>
        `;
      } else {
        stockHTML = `
          <div class="stock-available">
            <span class="stock-text">In Stock (${totalStock} available)</span>
          </div>
        `;
      }
      
      // Add size stock information
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

  // ✅ Update size buttons with new stock data
  function updateSizeButtons(stockBySize) {
    console.log("📏 Updating size buttons with stock:", stockBySize);
    
    const sizeButtons = document.querySelectorAll('.size-option');
    let hasActiveSize = false;
    
    sizeButtons.forEach(button => {
      const size = button.dataset.size;
      const sizeQty = stockBySize[size] || 0;
      const isDisabled = sizeQty === 0;
      
      console.log(`📏 Size ${size}: ${sizeQty} available, Disabled: ${isDisabled}`);
      
      // Update button data and state
      button.dataset.stock = sizeQty;
      button.disabled = isDisabled;
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
      
      // Auto-select first available size
      if (!isDisabled && !hasActiveSize) {
        button.classList.add('active');
        document.getElementById('selected-size').value = size;
        hasActiveSize = true;
        console.log(`📏 Auto-selected size: ${size}`);
      } else if (isDisabled && button.classList.contains('active')) {
        button.classList.remove('active');
      }
    });
    
    if (!hasActiveSize) {
      console.log("❌ No available sizes for this color");
      document.getElementById('selected-size').value = '';
    }
  }

  // ✅ Update quantity based on selected size's stock
  function updateQuantityForSelectedSize(stockBySize) {
    const selectedSize = document.querySelector('.size-option.active');
    if (!selectedSize) {
      console.log("❌ No active size found for quantity update");
      return;
    }
    
    const size = selectedSize.dataset.size;
    const sizeStock = stockBySize[size] || 0;
    
    console.log(`🔢 Updating quantity for size ${size}: ${sizeStock} available`);
    
    const quantityInput = document.getElementById('quantity');
    if (quantityInput) {
      quantityInput.max = sizeStock;
      
      if (sizeStock === 0) {
        quantityInput.value = 0;
        quantityInput.disabled = true;
      } else {
        const currentValue = parseInt(quantityInput.value);
        if (currentValue > sizeStock || currentValue === 0) {
          quantityInput.value = 1;
        }
        quantityInput.disabled = false;
      }
    }
    
    // Update button states
    updateButtonStates(sizeStock === 0);
  }

  // ✅ Update button states based on availability
  function updateButtonStates(isOutOfStock) {
    const minusBtn = document.getElementById('minus-btn');
    const plusBtn = document.getElementById('plus-btn');
    const addToCartBtn = document.querySelector('.add-to-cart');
    const buyNowBtn = document.getElementById('buy-now-btn');
    const wishlistBtn = document.querySelector('.wishlist-btn');
    const quantitySelector = document.querySelector('.quantity-selector');

    if (minusBtn) minusBtn.disabled = isOutOfStock;
    if (plusBtn) plusBtn.disabled = isOutOfStock;
    if (addToCartBtn) {
      addToCartBtn.disabled = isOutOfStock;
      addToCartBtn.textContent = isOutOfStock ? 'Out of Stock' : 'Add to Cart';
    }
    if (buyNowBtn) {
      buyNowBtn.disabled = isOutOfStock;
      buyNowBtn.textContent = isOutOfStock ? 'Out of Stock' : 'Buy Now';
    }
    if (wishlistBtn) wishlistBtn.disabled = isOutOfStock;
    if (quantitySelector) {
      quantitySelector.classList.toggle('out-of-stock', isOutOfStock);
    }
    
    console.log("🔄 Button states updated - Out of stock:", isOutOfStock);
  }

  // ✅ Handle color click
  colorOptions.forEach(option => {
    option.addEventListener("click", () => {
      const colorId = option.dataset.colorId;
      const colorName = option.dataset.colorName;
      const imageSrc = option.dataset.colorImage;

      console.log("🎨 Color clicked:", colorName, "ID:", colorId);

      // Update active color
      colorOptions.forEach(opt => opt.classList.remove("active"));
      option.classList.add("active");
      selectedColorInput.value = colorId;

      // Update image
      if (mainImage && imageSrc) {
        mainImage.src = imageSrc;
        console.log("🖼️ Image updated for color:", colorName);
      }

      // Update URL
      const url = new URL(window.location.href);
      url.searchParams.set("id", colorId);
      window.history.pushState({}, "", url);

      // Update ALL product information for this color
      updateProductForColor(colorId);
      
      // Update action buttons
      const addToCartBtn = document.querySelector('.add-to-cart');
      const buyNowBtn = document.getElementById('buy-now-btn');
      if (addToCartBtn) addToCartBtn.dataset.id = colorId;
      if (buyNowBtn) buyNowBtn.dataset.colorId = colorId;
    });
  });

  // ✅ Load initial product data for current color
  if (selectedColorInput.value) {
    console.log("🚀 Loading initial product data for color ID:", selectedColorInput.value);
    updateProductForColor(selectedColorInput.value);
  }
  
  console.log("✅ Color selector initialization complete");
});
</script>