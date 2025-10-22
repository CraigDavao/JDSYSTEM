<?php 
require_once __DIR__ . '/includes/header.php';

// Fetch only 4 featured products for homepage
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

// Fetch a single image for each category
$category_images = [];
$category_names = [
    'kid' => 'Kids Collection', 
    'baby' => 'Baby Collection', 
    'accessories' => 'Accessories'
];

foreach ($category_names as $category_key => $display_name) {
    // Select a random active image for each category
    $img_sql = "SELECT image FROM products 
                WHERE category_group = ? AND is_active = 1 
                ORDER BY RAND() 
                LIMIT 1";

    $stmt = $conn->prepare($img_sql);
    $stmt->bind_param("s", $category_key);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $category_images[$category_key] = !empty($row['image']) ? $row['image'] : "empty.jpg";
    } else {
        $category_images[$category_key] = "empty.jpg"; // fallback image
    }
    $stmt->close();
}

// Helper function for sale condition
function isOnSale($product) {
    return isset($product['sale_price']) &&
           is_numeric($product['sale_price']) &&
           $product['sale_price'] > 0 &&
           $product['sale_price'] < $product['price'];
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
        <img src="<?= SITE_URL ?>uploads/hero-main.png" alt="Jolly Dolly Kids Fashion" onerror="this.src='<?= SITE_URL ?>uploads/hero-main.jpg'">
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
            <!-- Kids Collection -->
            <div class="category-card">
                <a href="<?= SITE_URL ?>pages/kid.php">
                    <div class="category-image">
                        <img src="<?= SITE_URL ?>uploads/<?= htmlspecialchars($category_images['kid']) ?>" alt="Kids Collection" onerror="this.src='<?= SITE_URL ?>uploads/empty.jpg'">
                    </div>
                    <div class="category-content">
                        <h3>Kids Collection</h3>
                        <p>Age 2-8 years</p>
                        <span class="shop-now">Shop Now →</span>
                    </div>
                </a>
            </div>

            <!-- Baby Collection -->
            <div class="category-card">
                <a href="<?= SITE_URL ?>pages/baby.php">
                    <div class="category-image">
                        <img src="<?= SITE_URL ?>uploads/<?= htmlspecialchars($category_images['baby']) ?>" alt="Baby Collection" onerror="this.src='<?= SITE_URL ?>uploads/empty.jpg'">
                    </div>
                    <div class="category-content">
                        <h3>Baby Collection</h3>
                        <p>0-24 months</p>
                        <span class="shop-now">Shop Now →</span>
                    </div>  
                </a>
            </div>

            <!-- Accessories -->
            <div class="category-card">
                <a href="<?= SITE_URL ?>pages/accessories.php">
                    <div class="category-image">
                        <img src="<?= SITE_URL ?>uploads/<?= htmlspecialchars($category_images['accessories']) ?>" alt="Accessories" onerror="this.src='<?= SITE_URL ?>uploads/empty.jpg'">
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

<!-- New Arrivals -->
<section class="new-arrivals">
    <div class="container">
        <div class="new-header">
            <h2 class="new-title">New Arrivals</h2>
            <p class="subtitle">Discover our latest additions</p>
        </div>
        <?php if (!empty($featured_products)): ?>
            <div class="product-grid">
                <?php foreach ($featured_products as $product): ?>
                    <a href="<?= SITE_URL ?>pages/product.php?id=<?= $product['id'] ?>" class="product-card">
                        <div class="product-image-container">
                            <img src="<?= SITE_URL ?>uploads/<?= htmlspecialchars($product['image']); ?>" 
                                 alt="<?= htmlspecialchars($product['name']); ?>"
                                 class="product-thumb"
                                 onerror="this.src='<?= SITE_URL ?>uploads/una.jpg'">
                            <?php if (isOnSale($product)): ?>
                                <div class="sale-badge">Sale</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?= htmlspecialchars($product['name']); ?></h3>
                            <div class="product-price">
                                <?php if (isOnSale($product)): ?>
                                    <span class="sale-price">₱<?= number_format($product['sale_price'], 2); ?></span>
                                    <span class="original-price">₱<?= number_format($product['price'], 2); ?></span>
                                <?php else: ?>
                                    <span class="current-price">₱<?= number_format($product['price'], 2); ?></span>
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
                        <p class="subtitle">Perfect for <?= $category === 'kid' ? 'ages 2-8 years' : 'your little ones 0-24 months' ?></p>
                    </div>
                    <div class="product-grid">
                        <?php foreach ($products as $product): ?>
                            <a href="<?= SITE_URL ?>pages/product.php?id=<?= $product['id'] ?>" class="product-card">
                                <div class="product-image-container">
                                    <img src="<?= SITE_URL ?>uploads/<?= htmlspecialchars($product['image']); ?>" 
                                         alt="<?= htmlspecialchars($product['name']); ?>"
                                         class="product-thumb"
                                         onerror="this.src='<?= SITE_URL ?>uploads/sample1.jpg'">
                                    <?php if (isOnSale($product)): ?>
                                        <div class="sale-badge">Sale</div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-name"><?= htmlspecialchars($product['name']); ?></h3>
                                    <div class="product-price">
                                        <?php if (isOnSale($product)): ?>
                                            <span class="sale-price">₱<?= number_format($product['sale_price'], 2); ?></span>
                                            <span class="original-price">₱<?= number_format($product['price'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="current-price">₱<?= number_format($product['price'], 2); ?></span>
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
                <img src="<?= SITE_URL ?>uploads/brand-story.jpg" alt="Our Story" onerror="this.src='<?= SITE_URL ?>uploads/una.jpg'">
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
            <form class="newsletter-form" method="POST" action="<?= SITE_URL ?>auth/newsletter.php">
                <input type="email" name="email" placeholder="Enter your email address" required>
                <button type="submit" class="btn btn-primary">Subscribe</button>
            </form>
        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const backToTopButton = document.getElementById('backToTop');
    if (backToTopButton) {
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.add('visible');
            } else {
                backToTopButton.classList.remove('visible');
            }
        });
        backToTopButton.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
});
</script>

<?php
include_once './includes/footer.php';
?>
