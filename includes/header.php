<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration
require_once __DIR__ . '/../config.php';

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JDSystem</title>

    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/style.css">

    <!-- Font Awesome (for icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- JS -->
    <script src="<?php echo SITE_URL; ?>js/script.js" defer></script>
</head>

<body>
<nav class="site-nav">
    <div class="logo">
        <a href="<?php echo SITE_URL; ?>index.php">
            <img src="<?php echo SITE_URL; ?>uploads/jdlogo.jpg" alt="Jolly Dolly Logo">
        </a>
    </div>

    <div class="link-container">
        <ul class="nav-links">
            <!-- NEW with mega dropdown -->
            <li class="link has-dropdown">
                <a href="#" class="dropdown-toggle">
                    NEW
                    <svg class="hdt-menu-item-arrow" xmlns="http://www.w3.org/2000/svg" width="10" height="7" viewBox="0 0 10 7" fill="none">
                        <path d="M10 1.24243L5 6.24243L0 1.24243L0.8875 0.354932L5 4.46743L9.1125 0.354931L10 1.24243Z" fill="currentColor"></path>
                    </svg>
                </a>

                <div class="mega-dropdown">
                    <div class="mega-content">
                        <div class="mega-categories">
                            <h4>Categories</h4>
                            <ul>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=girls">Girls</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=boys">Boys</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=baby-girls">Baby Girls</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=baby-boys">Baby Boys</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=newborn">Newborn</a></li>
                            </ul>
                        </div>

                        <div class="mega-images">
                            <img src="<?php echo SITE_URL; ?>uploads/sample1.jpg" alt="Sample 1">
                            <img src="<?php echo SITE_URL; ?>uploads/sample2.jpg" alt="Sample 2">
                            <img src="<?php echo SITE_URL; ?>uploads/sample3.jpg" alt="Sample 3">
                        </div>
                    </div>
                </div>
            </li>

            <li class="link"><a href="<?php echo SITE_URL; ?>pages/products.php">KID</a></li>
            <li class="link"><a href="<?php echo SITE_URL; ?>pages/products.php">BABY</a></li>
            <li class="link"><a href="<?php echo SITE_URL; ?>pages/products.php">ACCESSORIES</a></li>
            <li class="link"><a href="<?php echo SITE_URL; ?>pages/products.php">SALE</a></li>
        </ul>
    </div>

    <div class="icon-header">
        <a href="<?php echo SITE_URL; ?>pages/search.php" title="Search"><i class="fa-solid fa-magnifying-glass"></i></a>
        <a href="<?php echo SITE_URL; ?>pages/profile.php" title="Profile"><i class="fa-regular fa-user"></i></a>
        <a href="<?php echo SITE_URL; ?>pages/favorites.php" title="Wishlist"><i class="fa-regular fa-heart"></i><span class="badge">0</span></a>
        <a href="<?php echo SITE_URL; ?>pages/cart.php" title="Cart"><i class="fa-solid fa-bag-shopping"></i><span class="badge">0</span></a>
    </div>
</nav>