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
            <img src="uploads/jdlogo.jpg" alt="Jolly Dolly Logo">
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
                            <img src="<?php echo SITE_URL; ?>uploads/sample1.jpg" alt="Sample 2">
                            <img src="<?php echo SITE_URL; ?>uploads/sample1.jpg" alt="Sample 3">
                        </div>
                    </div>
                </div>
            </li>

            <!-- KID with mega dropdown -->
            <li class="link has-dropdown">
                <a href="#" class="dropdown-toggle">
                    KID
                    <svg class="hdt-menu-item-arrow" xmlns="http://www.w3.org/2000/svg" width="10" height="7" viewBox="0 0 10 7" fill="none">
                        <path d="M10 1.24243L5 6.24243L0 1.24243L0.8875 0.354932L5 4.46743L9.1125 0.354931L10 1.24243Z" fill="currentColor"></path>
                    </svg>
                </a>

                <div class="mega-dropdown">
                    <div class="mega-content four-cols">
                        <!-- Girls -->
                        <div class="mega-categories">
                            <h4>Girls</h4>
                            <ul>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=girls-sets">Sets</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=girls-tops">Tops</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=girls-bottoms">Bottoms</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=girls-sleepwear">Sleepwear & Underwear</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=girls-sleepwear">Dresses & Jumpsuits</a></li>
                            </ul>
                        </div>

                        <!-- Boys -->
                        <div class="mega-categories">
                            <h4>Boys</h4>
                            <ul>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=boys-sets">Sets</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=boys-tops">Tops</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=boys-bottoms">Bottoms</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=boys-sleepwear">Sleepwear & Underwear</a></li>
                            </ul>
                        </div>

                        <!-- Image 1 -->
                        <div class="mega-images">
                            <img src="<?php echo SITE_URL; ?>uploads/kids-girls.jpg" alt="Kids Girls">
                        </div>

                        <!-- Image 2 -->
                        <div class="mega-images">
                            <img src="<?php echo SITE_URL; ?>uploads/kids-boys.jpg" alt="Kids Boys">
                        </div>
                    </div>
                </div>
            </li>



            <!-- BABY with mega dropdown -->
            <li class="link has-dropdown">
                <a href="#" class="dropdown-toggle">
                    BABY
                    <svg class="hdt-menu-item-arrow" xmlns="http://www.w3.org/2000/svg" width="10" height="7" viewBox="0 0 10 7" fill="none">
                        <path d="M10 1.24243L5 6.24243L0 1.24243L0.8875 0.354932L5 4.46743L9.1125 0.354931L10 1.24243Z" fill="currentColor"></path>
                    </svg>
                </a>

                <div class="mega-dropdown">
                    <div class="mega-content five-cols">
                        <!-- Baby Girls -->
                        <div class="mega-categories">
                            <h4>Baby Girls</h4>
                            <ul>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=baby-girls-sets">Sets</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=baby-girls-tops">Tops</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=baby-girls-bottoms">Bottoms</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=baby-girls-sleepwear">Sleepwear & Underwear</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=baby-girls-accessories">Accessories</a></li>
                            </ul>
                        </div>

                        <!-- Baby Boys -->
                        <div class="mega-categories">
                            <h4>Baby Boys</h4>
                            <ul>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=baby-boys-sets">Sets</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=baby-boys-tops">Tops</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=baby-boys-bottoms">Bottoms</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=baby-boys-sleepwear">Sleepwear & Underwear</a></li>
                            </ul>
                        </div>

                        <!-- Newborn -->
                        <div class="mega-categories">
                            <h4>Newborn</h4>
                            <ul>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=newborn-bodysuits">Bodysuits & Sleepsuits</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=newborn-essentials">Essentials</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=newborn-sets">Sets</a></li>
                            </ul>
                        </div>

                        <!-- Image Column 1 -->
                        <div class="mega-images">
                            <img src="<?php echo SITE_URL; ?>uploads/baby1.jpg" alt="Baby Image 1">
                        </div>

                        <!-- Image Column 2 -->
                        <div class="mega-images">
                            <img src="<?php echo SITE_URL; ?>uploads/baby2.jpg" alt="Baby Image 2">
                        </div>
                    </div>
                </div>
            </li>

            <!-- ACCESSORIES with mega dropdown -->
            <li class="link has-dropdown">
                <a href="#" class="dropdown-toggle">
                    ACCESSORIES
                    <svg class="hdt-menu-item-arrow" xmlns="http://www.w3.org/2000/svg" width="10" height="7" viewBox="0 0 10 7" fill="none">
                        <path d="M10 1.24243L5 6.24243L0 1.24243L0.8875 0.354932L5 4.46743L9.1125 0.354931L10 1.24243Z" fill="currentColor"></path>
                    </svg>
                </a>

                <div class="mega-dropdown">
                    <div class="mega-content three-cols">
                        <!-- Categories -->
                        <div class="mega-categories">
                            <h4>Categories</h4>
                            <ul>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=hair-accessories">Hair Accessories</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=bags-hats">Bags & Hats</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=bow-ties">Bow Ties</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/products.php?category=toys-gifts">Toys & Gifts</a></li>
                            </ul>
                        </div>

                        <!-- Image Column 1 -->
                        <div class="mega-images">
                            <img src="<?php echo SITE_URL; ?>uploads/accessory1.jpg" alt="Accessory 1">
                        </div>

                        <!-- Image Column 2 -->
                        <div class="mega-images">
                            <img src="<?php echo SITE_URL; ?>uploads/accessory2.jpg" alt="Accessory 2">
                        </div>
                    </div>
                </div>
            </li>


            <li class="link"><a href="<?php echo SITE_URL; ?>pages/products.php">SALE</a></li>
        </ul>
    </div>

    <div class="icon-header">
        <a href="#" id="search-icon" title="Search"><i class="fa-solid fa-magnifying-glass"></i></a>
        <!-- Search Dropdown -->
        <div class="search-dropdown">
            <div class="search-container">
                <h3>Search our site</h3>
                <form action="<?php echo SITE_URL; ?>pages/search.php" method="GET">
                    <input type="text" name="query" placeholder="Type to search..." required>
                    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>
        </div>


        <a href="<?php echo SITE_URL; ?>pages/profile.php" title="Profile"><i class="fa-regular fa-user"></i></a>
        <a href="<?php echo SITE_URL; ?>pages/favorites.php" title="Wishlist"><i class="fa-regular fa-heart"></i><span class="badge">0</span></a>
        <a href="<?php echo SITE_URL; ?>pages/cart.php" title="Cart"><i class="fa-solid fa-bag-shopping"></i><span class="badge">0</span></a>
    </div>
</nav>