<?php
/**
 * Color Selector Component - FINAL UPDATED VERSION
 * Usage: include this file in product pages
 */

if (!isset($product_id) || !isset($colors)) {
    return;
}

// Get current color ID from URL or session
$current_color_id = $color_id ?? null;
if (!$current_color_id && !empty($colors)) {
    $current_color_id = $colors[0]['id'];
}

// Find current color
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
    <label class="color-label">
        Color: <span id="selected-color-name"><?= htmlspecialchars($current_color['color_name'] ?? 'Select Color') ?></span>
    </label>
    
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
                <span class="color-preview" style="background-color: <?= getColorCode($color['color_name']) ?>"></span>
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
  const selectedColorName = document.getElementById("selected-color-name");
  const mainImage = document.querySelector(".main-product-image");

  // Detect if user came from Add to Cart / Wishlist / Buy Now
  const fromCartActions = sessionStorage.getItem("from_cart_actions");

  // ✅ Restore color only if returning from Add to Cart / Wishlist / Buy Now
  if (fromCartActions && productId) {
    const savedColorId = sessionStorage.getItem("selected_color_" + productId);
    if (savedColorId) {
      const savedOption = document.querySelector(`.color-option[data-color-id="${savedColorId}"]`);
      if (savedOption) {
        colorOptions.forEach(opt => opt.classList.remove("active"));
        savedOption.classList.add("active");
        selectedColorInput.value = savedColorId;
        selectedColorName.textContent = savedOption.dataset.colorName;

        const imageSrc = savedOption.dataset.colorImage;
        if (mainImage && imageSrc) mainImage.src = imageSrc;
      }
    }

    // Clear marker so color resets next time
    sessionStorage.removeItem("from_cart_actions");
  }

  // ✅ Handle color click
  colorOptions.forEach(option => {
    option.addEventListener("click", () => {
      const colorId = option.dataset.colorId;
      const colorName = option.dataset.colorName;
      const imageSrc = option.dataset.colorImage;

      // Update UI
      colorOptions.forEach(opt => opt.classList.remove("active"));
      option.classList.add("active");
      selectedColorInput.value = colorId;
      selectedColorName.textContent = colorName;

      if (mainImage && imageSrc) mainImage.src = imageSrc;

      // Save color for this product
      sessionStorage.setItem("selected_color_" + productId, colorId);
    });
  });
});
</script>
