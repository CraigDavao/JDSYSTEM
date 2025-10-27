<?php
/**
 * Color Selector Component
 * Usage: include this file in product pages
 */
 
if (!isset($product_id) || !isset($colors)) {
    return;
}

$default_color = null;
foreach ($colors as $color) {
    if ($color['is_default']) {
        $default_color = $color;
        break;
    }
}
if (!$default_color && !empty($colors)) {
    $default_color = $colors[0];
}

// Prepare default image
$default_image_data = '';
if ($default_color && !empty($default_color['image'])) {
    $mimeType = $default_color['image_format'] ?? 'image/jpeg';
    $default_image_data = 'data:' . $mimeType . ';base64,' . base64_encode($default_color['image']);
}
?>

<div class="color-selector" data-product-id="<?= $product_id ?>">
    <label class="color-label">
        Color: <span id="selected-color-name"><?= htmlspecialchars($default_color['color_name'] ?? 'Select Color') ?></span>
    </label>
    
    <div class="color-options">
        <?php foreach ($colors as $color): 
            // Prepare image data for each color
            $image_data = '';
            if (!empty($color['image'])) {
                $mimeType = $color['image_format'] ?? 'image/jpeg';
                $image_data = 'data:' . $mimeType . ';base64,' . base64_encode($color['image']);
            }
        ?>
            <div class="color-option <?= ($color['id'] == ($default_color['id'] ?? null)) ? 'active' : '' ?>" 
                 data-color-id="<?= $color['id'] ?>"
                 data-color-name="<?= htmlspecialchars($color['color_name']) ?>"
                 data-color-image="<?= htmlspecialchars($image_data) ?>"
                 title="<?= htmlspecialchars($color['color_name']) ?>">
                <span class="color-preview" style="background-color: <?= getColorCode($color['color_name']) ?>"></span>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Hidden field for form submission -->
    <input type="hidden" name="selected_color_id" id="selected-color-id" value="<?= $default_color['id'] ?? '' ?>">
</div>