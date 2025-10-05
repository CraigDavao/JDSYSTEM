<?php
include '../connection/connection.php';


if (isset($_GET['query'])) {
    $search = trim($_GET['query']);
    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ?");
    $like = "%" . $search . "%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Search results for "<?php echo htmlspecialchars($search); ?>"</title>
  <link rel="stylesheet" href="../css/search.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <a href="<?php echo SITE_URL; ?>" class="close-btn" title="Back to Home">
    <i class="fa-solid fa-xmark"></i>
    </a>
  <div class="search-header">
    <h1 class="search-title">Search results for "<?php echo htmlspecialchars($search); ?>"</h1>
    <p class="subtitle">Showing products that match your search</p>
  </div>

 <div class="product-grid">
    <?php if (!empty($result) && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <a href="#" class="product-card">
                <div class="product-image">
                    <img src="../uploads/<?php echo htmlspecialchars($row['image']); ?>" 
                         alt="<?php echo htmlspecialchars($row['name']); ?>"
                         onerror="this.style.display='none'; this.parentElement.classList.add('missing-image')">
                </div>
                <h2><?php echo htmlspecialchars($row['name']); ?></h2>
                <p class="price">$<?php echo htmlspecialchars($row['price']); ?></p>
            </a>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="no-results">No products found matching your search.</p>
    <?php endif; ?>
</div>


  <script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle missing product images
    const productImages = document.querySelectorAll('.product-image img');
    
    productImages.forEach(img => {
        // Check if image fails to load
        img.addEventListener('error', function() {
            this.style.display = 'none';
            this.parentElement.classList.add('missing-image');
        });
        
        // Check if image src is empty or undefined
        if (!img.src || img.src.includes('undefined')) {
            img.style.display = 'none';
            img.parentElement.classList.add('missing-image');
        }
    });
});
</script>
</body>
</html>
