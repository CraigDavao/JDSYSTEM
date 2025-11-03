<?php
/**
 * Color Selector Component - FINAL VERSION
 * ✅ URL updates dynamically (?id=181)
 * ✅ No page reload
 * ✅ Image changes instantly
 */

if (!isset($product_id) || !isset($colors)) {
    return;
}

// ✅ Get ID from URL (product color id)
$current_color_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// ✅ Default to first or default color
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

  // ✅ Restore color from session
  const savedColorId = sessionStorage.getItem("selected_color_" + productId);
  if (savedColorId) {
    const savedOption = document.querySelector(`.color-option[data-color-id="${savedColorId}"]`);
    if (savedOption) {
      colorOptions.forEach(opt => opt.classList.remove("active"));
      savedOption.classList.add("active");
      selectedColorInput.value = savedColorId;
      const imageSrc = savedOption.dataset.colorImage;
      if (mainImage && imageSrc) mainImage.src = imageSrc;
    }
  }

  // ✅ Handle color click (no refresh but update URL)
  colorOptions.forEach(option => {
    option.addEventListener("click", () => {
      const colorId = option.dataset.colorId;
      const imageSrc = option.dataset.colorImage;

      // Update visuals
      colorOptions.forEach(opt => opt.classList.remove("active"));
      option.classList.add("active");
      selectedColorInput.value = colorId;

      // Update main image
      if (mainImage && imageSrc) mainImage.src = imageSrc;

      // Save to session
      sessionStorage.setItem("selected_color_" + productId, colorId);

      // ✅ Update the URL (without reload)
      const url = new URL(window.location.href);
      url.searchParams.set("id", colorId);
      window.history.pushState({}, "", url);
    });
  });
});
</script>