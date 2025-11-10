<?php 
require_once __DIR__ . '/includes/header.php';

/* ------------------------------------------------------------
   HELPER FUNCTION: Get default color ID for a product
------------------------------------------------------------ */
function getDefaultColorId($product_id, $conn) {
    $sql = "SELECT id FROM product_colors WHERE product_id = ? AND is_default = 1 LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    }
    
    // If no default color, get the first available color
    $sql = "SELECT id FROM product_colors WHERE product_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    }
    
    return null;
}

/* ------------------------------------------------------------
   FETCH PRODUCTS AND IMAGES WITH COLOR IDS
------------------------------------------------------------ */

// Fetch 4 latest active products (New Arrivals)
$featured_products = [];
$new_arrivals_sql = "
    SELECT p.*, 
           COALESCE(TO_BASE64(pi.image), NULL) AS product_image,
           pc.id as color_id
    FROM products p
    LEFT JOIN product_images pi ON p.id = pi.product_id
    LEFT JOIN product_colors pc ON p.id = pc.product_id AND pc.is_default = 1
    WHERE p.is_active = 1
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 4
";
$new_arrivals_result = $conn->query($new_arrivals_sql);
if ($new_arrivals_result && $new_arrivals_result->num_rows > 0) {
    while ($row = $new_arrivals_result->fetch_assoc()) {
        // If no color_id from join, get it manually
        if (empty($row['color_id'])) {
            $row['color_id'] = getDefaultColorId($row['id'], $conn);
        }
        $featured_products[] = $row;
    }
}

// Fetch category-based products
$categories_products = [];
$categories = ['kid', 'baby'];

foreach ($categories as $category) {
    $cat_sql = "
        SELECT p.*, 
               COALESCE(TO_BASE64(pi.image), NULL) AS product_image,
               pc.id as color_id
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id
        LEFT JOIN product_colors pc ON p.id = pc.product_id AND pc.is_default = 1
        WHERE p.category_group = ? AND p.is_active = 1
        GROUP BY p.id
        ORDER BY p.created_at DESC
        LIMIT 4
    ";
    $stmt = $conn->prepare($cat_sql);
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();

    $categories_products[$category] = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // If no color_id from join, get it manually
            if (empty($row['color_id'])) {
                $row['color_id'] = getDefaultColorId($row['id'], $conn);
            }
            $categories_products[$category][] = $row;
        }
    }
    $stmt->close();
}

/* ------------------------------------------------------------
   FETCH CATEGORY IMAGES (Random one per category)
------------------------------------------------------------ */
$category_images = [];
$category_names = [
    'kid' => 'Kids Collection', 
    'baby' => 'Baby Collection', 
    'accessories' => 'Accessories'
];

foreach ($category_names as $category_key => $display_name) {
    $img_sql = "
        SELECT TO_BASE64(pi.image) AS img 
        FROM products p
        JOIN product_images pi ON p.id = pi.product_id
        WHERE p.category_group = ? AND p.is_active = 1
        ORDER BY RAND()
        LIMIT 1
    ";
    $stmt = $conn->prepare($img_sql);
    $stmt->bind_param("s", $category_key);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $category_images[$category_key] = $row['img'] ? 'data:image/jpeg;base64,' . $row['img'] : SITE_URL . 'uploads/empty.jpg';
    } else {
        $category_images[$category_key] = SITE_URL . 'uploads/empty.jpg';
    }
    $stmt->close();
}

/* ------------------------------------------------------------
   HELPER: Sale condition and price calculation - FIXED
------------------------------------------------------------ */
function isOnSale($product) {
    // Check if we have an actual sale price that's different from regular price
    if (!empty($product['actual_sale_price']) && $product['actual_sale_price'] > 0 && $product['actual_sale_price'] < $product['price']) {
        return true;
    }
    // Fallback: if only sale_price (percentage) is available
    else if (!empty($product['sale_price']) && $product['sale_price'] > 0 && $product['sale_price'] < 100) {
        return true;
    }
    return false;
}

function getDisplayPrice($product) {
    // Check if we have an actual sale price that's different from regular price
    if (!empty($product['actual_sale_price']) && $product['actual_sale_price'] > 0 && $product['actual_sale_price'] < $product['price']) {
        return $product['actual_sale_price'];
    }
    // Fallback: if only sale_price (percentage) is available, calculate it
    else if (!empty($product['sale_price']) && $product['sale_price'] > 0 && $product['sale_price'] < 100) {
        $discountAmount = $product['price'] * ($product['sale_price'] / 100);
        return $product['price'] - $discountAmount;
    }
    // No sale
    return $product['price'];
}
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1 class="titlenicraig">Jolly Dolly Kids Wear</h1>
        <p>Where precious moments meet timeless style</p>
        <div class="hero-buttons">
            <a href="<?= SITE_URL ?>pages/new.php?category=all" class="btn btn-primary">Shop New Arrivals</a>
            <a href="<?= SITE_URL ?>pages/kid.php" class="btn btn-secondary">Explore Collection</a>
        </div>
    </div>
    <div class="hero-image">
        <img src="<?= SITE_URL ?>uploads/jimmy.png" alt="Jolly Dolly Kids Fashion" onerror="this.src='<?= SITE_URL ?>uploads/jimmy.png'">
    </div>
</section>

<!-- Featured Categories -->
<section class="featured-categories">
    <div class="container">
        <div class="new-header">
            <h2 class="new-title">Shop by Category</h2>
            <p class="subtitle">Discover our carefully curated collections</p>
        </div>
        <div class="categories-grid">
            <?php foreach ($category_names as $key => $title): ?>
                <div class="category-card">
                    <a href="<?= SITE_URL ?>pages/<?= $key ?>.php">
                        <div class="category-image">
                            <img src="<?= htmlspecialchars($category_images[$key]) ?>" 
                                 alt="<?= htmlspecialchars($title) ?>" 
                                 onerror="this.src='<?= SITE_URL ?>uploads/empty.jpg'">
                        </div>
                        <div class="category-content">
                            <h3><?= htmlspecialchars($title) ?></h3>
                            <p>
                                <?= $key === 'kid' ? 'Age 2-8 years' : ($key === 'baby' ? '0-24 months' : 'Complete the look') ?>
                            </p>
                            <span class="shop-now">Shop Now →</span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- New Arrivals -->
<section class="new-arrivals">
    <div class="container">
        <div class="new-header">
            <h2 class="new-title">New Arrivals</h2>
            <p class="subtitle">Discover our latest additions</p>
        </div>
        <?php if (!empty($featured_products)): ?>
            <div class="product-grid">
                <?php foreach ($featured_products as $product): 
                    $img = $product['product_image'] 
                        ? 'data:image/jpeg;base64,' . $product['product_image'] 
                        : SITE_URL . 'uploads/una.jpg';
                    
                    // ✅ FIXED: Use color_id for the product link instead of product id
                    $link_id = !empty($product['color_id']) ? $product['color_id'] : $product['id'];
                    $hasSale = isOnSale($product);
                    $displayPrice = getDisplayPrice($product);
                ?>
                    <a href="<?= SITE_URL ?>pages/product.php?id=<?= $link_id ?>" class="product-card">
                        <div class="product-image-container">
                            <img src="<?= $img ?>" 
                                 alt="<?= htmlspecialchars($product['name']); ?>"
                                 class="product-thumb">
                            <?php if ($hasSale): ?>
                                <div class="sale-badge">Sale</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?= htmlspecialchars($product['name']); ?></h3>
                            <div class="product-price">
                                <?php if ($hasSale): ?>
                                    <span class="sale-price">₱<?= number_format($displayPrice, 2); ?></span>
                                    <span class="original-price">₱<?= number_format($product['price'], 2); ?></span>
                                <?php else: ?>
                                    <span class="current-price">₱<?= number_format($displayPrice, 2); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="view-all-container">
                <a href="<?= SITE_URL ?>pages/new.php?category=all" class="btn btn-outline">View All New Arrivals</a>
            </div>
        <?php else: ?>
            <div class="no-products">
                <p>No products available at the moment. Check back soon!</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Category Sections -->
<section class="category-sections">
    <div class="container">
        <?php foreach ($categories_products as $category => $products): ?>
            <?php if (!empty($products)): ?>
                <div class="category-section">
                    <div class="new-header">
                        <h2 class="new-title"><?= ucfirst($category) ?> Collection</h2>
                        <p class="subtitle"><?= $category === 'kid' ? 'Perfect for ages 2-8 years' : 'Your little ones 0-24 months' ?></p>
                    </div>
                    <div class="product-grid">
                        <?php foreach ($products as $product): 
                            $img = $product['product_image'] 
                                ? 'data:image/jpeg;base64,' . $product['product_image'] 
                                : SITE_URL . 'uploads/sample1.jpg';
                            
                            // ✅ FIXED: Use color_id for the product link instead of product id
                            $link_id = !empty($product['color_id']) ? $product['color_id'] : $product['id'];
                            $hasSale = isOnSale($product);
                            $displayPrice = getDisplayPrice($product);
                        ?>
                            <a href="<?= SITE_URL ?>pages/product.php?id=<?= $link_id ?>" class="product-card">
                                <div class="product-image-container">
                                    <img src="<?= $img ?>" 
                                         alt="<?= htmlspecialchars($product['name']); ?>"
                                         class="product-thumb">
                                    <?php if ($hasSale): ?>
                                        <div class="sale-badge">Sale</div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-name"><?= htmlspecialchars($product['name']); ?></h3>
                                    <div class="product-price">
                                        <?php if ($hasSale): ?>
                                            <span class="sale-price">₱<?= number_format($displayPrice, 2); ?></span>
                                            <span class="original-price">₱<?= number_format($product['price'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="current-price">₱<?= number_format($displayPrice, 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="view-all-container">
                        <a href="<?= SITE_URL ?>pages/<?= $category ?>.php" class="btn btn-outline">View All <?= ucfirst($category) ?> Collection</a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>

<?php include_once './includes/footer.php'; ?>