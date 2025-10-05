<?php 
// Include the header
require_once __DIR__ . '/includes/header.php';

// Fetch only 4 featured products from database for homepage
$featured_products = [];
$new_arrivals_sql = "
    SELECT * FROM products 
    WHERE is_active = 1 
    ORDER BY created_at DESC 
    LIMIT 4
";
$new_arrivals_result = $conn->query($new_arrivals_sql);

if ($new_arrivals_result && $new_arrivals_result->num_rows > 0) {
    while($row = $new_arrivals_result->fetch_assoc()) {
        $featured_products[] = $row;
    }
}

// Fetch products by category for featured sections
$categories_products = [];
$categories = ['kid', 'baby'];

foreach ($categories as $category) {
    $cat_sql = "
        SELECT * FROM products 
        WHERE category_group = ? AND is_active = 1 
        ORDER BY created_at DESC 
        LIMIT 4
    ";
    $stmt = $conn->prepare($cat_sql);
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories_products[$category] = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $categories_products[$category][] = $row;
        }
    }
    $stmt->close();
}
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1 class="titlenicraig">Jolly Dolly Kids Wear</h1>
        <p>Where precious moments meet timeless style</p>
        <div class="hero-buttons">
            <a href="<?php echo SITE_URL; ?>pages/new.php?category=all" class="btn btn-primary">Shop New Arrivals</a>
            <a href="<?php echo SITE_URL; ?>pages/kid.php" class="btn btn-secondary">Explore Collection</a>
        </div>
    </div>
    <div class="hero-image">
        <img src="<?php echo SITE_URL; ?>uploads/hero-main.jpg" alt="Jolly Dolly Kids Fashion" onerror="this.src='<?php echo SITE_URL; ?>uploads/sample1.jpg'">
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
            <div class="category-card">
                <a href="<?php echo SITE_URL; ?>pages/kid.php">
                    <div class="category-image">
                        <img src="<?php echo SITE_URL; ?>uploads/kids-category.jpg" alt="Kids Collection" onerror="this.src='<?php echo SITE_URL; ?>uploads/sample1.jpg'">
                    </div>
                    <div class="category-content">
                        <h3>Kids Collection</h3>
                        <p>Age 2-8 years</p>
                        <span class="shop-now">Shop Now →</span>
                    </div>
                </a>
            </div>
            
            <div class="category-card">
                <a href="<?php echo SITE_URL; ?>pages/baby.php">
                    <div class="category-image">
                        <img src="<?php echo SITE_URL; ?>uploads/baby-category.jpg" alt="Baby Collection" onerror="this.src='<?php echo SITE_URL; ?>uploads/sample1.jpg'">
                    </div>
                    <div class="category-content">
                        <h3>Baby Collection</h3>
                        <p>0-24 months</p>
                        <span class="shop-now">Shop Now →</span>
                    </div>
                </a>
            </div>
            
            <div class="category-card">
                <a href="<?php echo SITE_URL; ?>pages/accessories.php">
                    <div class="category-image">
                        <img src="<?php echo SITE_URL; ?>uploads/accessories-category.jpg" alt="Accessories" onerror="this.src='<?php echo SITE_URL; ?>uploads/sample1.jpg'">
                    </div>
                    <div class="category-content">
                        <h3>Accessories</h3>
                        <p>Complete the look</p>
                        <span class="shop-now">Shop Now →</span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- New Arrivals - Only 4 Products -->
<section class="new-arrivals">
    <div class="container">
        <div class="new-header">
            <h2 class="new-title">New Arrivals</h2>
            <p class="subtitle">Discover our latest additions</p>
        </div>
        
        <?php if (!empty($featured_products)): ?>
            <div class="product-grid">
                <?php foreach ($featured_products as $product): ?>
                    <!-- TEMPORARY: Using # link until you create product pages -->
                    <a href="#" class="product-card" onclick="return false;">
                        <div class="product-image-container">
                            <img src="<?php echo SITE_URL; ?>uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="product-thumb"
                                 onerror="this.src='<?php echo SITE_URL; ?>uploads/sample1.jpg'">
                            <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                <div class="sale-badge">Sale</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="product-price">
                                <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                    <span class="sale-price">₱<?php echo number_format($product['sale_price'], 2); ?></span>
                                    <span class="original-price">₱<?php echo number_format($product['price'], 2); ?></span>
                                <?php else: ?>
                                    <span class="current-price">₱<?php echo number_format($product['price'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="view-all-container">
                <a href="<?php echo SITE_URL; ?>pages/new.php?category=all" class="btn btn-outline">View All New Arrivals</a>
            </div>
        <?php else: ?>
            <div class="no-products">
                <p>No products available at the moment. Check back soon!</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Category Sections - Also 4 Products Each -->
<section class="category-sections">
    <div class="container">
        <!-- Kids Collection -->
        <?php if (!empty($categories_products['kid'])): ?>
        <div class="category-section">
            <div class="new-header">
                <h2 class="new-title">Kids Collection</h2>
                <p class="subtitle">Perfect for ages 2-8 years</p>
            </div>
            <div class="product-grid">
                <?php foreach ($categories_products['kid'] as $product): ?>
                    <!-- TEMPORARY: Using # link until you create product pages -->
                    <a href="#" class="product-card" onclick="return false;">
                        <div class="product-image-container">
                            <img src="<?php echo SITE_URL; ?>uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="product-thumb"
                                 onerror="this.src='<?php echo SITE_URL; ?>uploads/sample1.jpg'">
                            <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                <div class="sale-badge">Sale</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="product-price">
                                <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                    <span class="sale-price">₱<?php echo number_format($product['sale_price'], 2); ?></span>
                                    <span class="original-price">₱<?php echo number_format($product['price'], 2); ?></span>
                                <?php else: ?>
                                    <span class="current-price">₱<?php echo number_format($product['price'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="view-all-container">
                <a href="<?php echo SITE_URL; ?>pages/kid.php" class="btn btn-outline">View All Kids Collection</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Baby Collection -->
        <?php if (!empty($categories_products['baby'])): ?>
        <div class="category-section">
            <div class="new-header">
                <h2 class="new-title">Baby Collection</h2>
                <p class="subtitle">For your little ones 0-24 months</p>
            </div>
            <div class="product-grid">
                <?php foreach ($categories_products['baby'] as $product): ?>
                    <!-- TEMPORARY: Using # link until you create product pages -->
                    <a href="#" class="product-card" onclick="return false;">
                        <div class="product-image-container">
                            <img src="<?php echo SITE_URL; ?>uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="product-thumb"
                                 onerror="this.src='<?php echo SITE_URL; ?>uploads/sample1.jpg'">
                            <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                <div class="sale-badge">Sale</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="product-price">
                                <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                    <span class="sale-price">₱<?php echo number_format($product['sale_price'], 2); ?></span>
                                    <span class="original-price">₱<?php echo number_format($product['price'], 2); ?></span>
                                <?php else: ?>
                                    <span class="current-price">₱<?php echo number_format($product['price'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="view-all-container">
                <a href="<?php echo SITE_URL; ?>pages/baby.php" class="btn btn-outline">View All Baby Collection</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Brand Story -->
<section class="brand-story">
    <div class="container">
        <div class="story-content">
            <div class="story-text">
                <h2 class="new-title">Our Story</h2>
                <p>Jolly Dolly was born from a simple belief: childhood should be filled with magic, comfort, and style. We create timeless pieces that celebrate the joy of being little.</p>
                <p>Every stitch tells a story of quality, comfort, and the pure delight of childhood adventures.</p>
                <a href="#" class="btn btn-outline">Learn More About Us</a>
            </div>
            <div class="story-image">
                <img src="<?php echo SITE_URL; ?>uploads/brand-story.jpg" alt="Our Story" onerror="this.src='<?php echo SITE_URL; ?>uploads/sample1.jpg'">
            </div>
        </div>
    </div>
</section>

<!-- Newsletter -->
<section class="newsletter">
    <div class="container">
        <div class="newsletter-content">
            <h2 class="new-title">Join the Jolly Dolly Family</h2>
            <p class="subtitle">Be the first to know about new arrivals, exclusive offers, and special promotions</p>
            <form class="newsletter-form" method="POST" action="<?php echo SITE_URL; ?>auth/newsletter.php">
                <input type="email" name="email" placeholder="Enter your email address" required>
                <button type="submit" class="btn btn-primary">Subscribe</button>
            </form>
        </div>
    </div>
</section>

<?php
// Include footer
include_once './includes/footer.php';
?>