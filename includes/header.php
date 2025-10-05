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
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/homepage.css?v=<?= time(); ?>">

    <!-- Font Awesome (for icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- JS -->
    <script src="<?php echo SITE_URL; ?>js/script.js?v=<?= time(); ?>" defer></script>
</head>

<body>
<a href="#" class="product-card" onclick="return false;">
<nav class="site-nav">
    <div class="logo">
        <a href="<?php echo SITE_URL; ?>index.php">
            <img src="<?php echo SITE_URL; ?>uploads/logo.jpg" alt="Logo">
        </a>
    </div>

    <div class="link-container">
        <ul class="nav-links">
            <!-- NEW with mega dropdown -->
            <li class="link has-dropdown">
                <a href="<?php echo SITE_URL; ?>pages/new.php?category=all" class="dropdown-toggle">
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
                                <li><a href="<?php echo SITE_URL; ?>pages/new/girls.php">Girls</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/new/boys.php">Boys</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/new/babygirls.php">Baby Girls</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/new/babyboys.php">Baby Boys</a></li>
                                <li><a href="<?php echo SITE_URL; ?>pages/new/newborn.php">Newborn</a></li>
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
                <a href="<?= SITE_URL ?>pages/kid.php" class="dropdown-toggle">
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
                                <li><a href="<?= SITE_URL ?>pages/kid/setsgirl.php">Sets</a></li>
                                <li><a href="<?= SITE_URL ?>pages/kid/topsgirl.php">Tops</a></li>
                                <li><a href="<?= SITE_URL ?>pages/kid/bottomsgirl.php">Bottoms</a></li>
                                <li><a href="<?= SITE_URL ?>pages/kid/sleepgirl.php">Sleepwear & Underwear</a></li>
                                <li><a href="<?= SITE_URL ?>pages/kid/dressesgirl.php">Dresses & Jumpsuits</a></li>
                            </ul>
                        </div>

                        <!-- Boys -->
                        <div class="mega-categories">
                            <h4>Boys</h4>
                            <ul>
                                <li><a href="<?= SITE_URL ?>pages/kid/setsboy.php">Sets</a></li>
                                <li><a href="<?= SITE_URL ?>pages/kid/topsboy.php">Tops</a></li>
                                <li><a href="<?= SITE_URL ?>pages/kid/bottomsboy.php">Bottoms</a></li>
                                <li><a href="<?= SITE_URL ?>pages/kid/sleepboy.php">Sleepwear & Underwear</a></li>
                            </ul>
                        </div>

                        <!-- Image 1 -->
                        <div class="mega-images">
                            <img src="<?= SITE_URL ?>uploads/kids-girls.jpg" alt="Kids Girls">
                        </div>

                        <!-- Image 2 -->
                        <div class="mega-images">
                            <img src="<?= SITE_URL ?>uploads/kids-boys.jpg" alt="Kids Boys">
                        </div>
                    </div>
                </div>
            </li>




            <!-- BABY with mega dropdown -->
            <li class="link has-dropdown">
                <a href="<?= SITE_URL ?>pages/baby.php" class="dropdown-toggle">
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
                                <li><a href="<?= SITE_URL ?>pages/baby/bgsets.php">Sets</a></li>
                                <li><a href="<?= SITE_URL ?>pages/baby/bgtops.php">Tops</a></li>
                                <li><a href="<?= SITE_URL ?>pages/baby/bgbottoms.php">Bottoms</a></li>
                                <li><a href="<?= SITE_URL ?>pages/baby/bgsleep.php">Sleepwear & Underwear</a></li>
                                <li><a href="<?= SITE_URL ?>pages/baby/bgaccessories.php">Accessories</a></li>
                            </ul>
                        </div>

                        <!-- Baby Boys -->
                        <div class="mega-categories">
                            <h4>Baby Boys</h4>
                            <ul>
                                <li><a href="<?= SITE_URL ?>pages/baby/bbsets.php">Sets</a></li>
                                <li><a href="<?= SITE_URL ?>pages/baby/bbtops.php">Tops</a></li>
                                <li><a href="<?= SITE_URL ?>pages/baby/bbbottoms.php">Bottoms</a></li>
                                <li><a href="<?= SITE_URL ?>pages/baby/bbsleep.php">Sleepwear & Underwear</a></li>
                                <li><a href="<?= SITE_URL ?>pages/baby/bbaccessories.php">Accessories</a></li>
                            </ul>
                        </div>

                        <!-- Newborn -->
                        <div class="mega-categories">
                            <h4>Newborn</h4>
                            <ul>
                                <li><a href="<?= SITE_URL ?>pages/baby/nbody.php">Bodysuits & Sleepsuits</a></li>
                                <li><a href="<?= SITE_URL ?>pages/baby/essentials.php">Essentials</a></li>
                                <li><a href="<?= SITE_URL ?>pages/baby/nsets.php">Sets</a></li>
                            </ul>
                        </div>

                        <div class="mega-images">
                            <img src="<?= SITE_URL ?>uploads/baby1.jpg" alt="Baby Image 1">
                        </div>
                        <div class="mega-images">
                            <img src="<?= SITE_URL ?>uploads/baby2.jpg" alt="Baby Image 2">
                        </div>
                    </div>
                </div>
            </li>


            <!-- ACCESSORIES with mega dropdown -->
            <li class="link has-dropdown">
                <a href="<?= SITE_URL ?>pages/accessories.php" class="dropdown-toggle">
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
                                <li><a href="<?= SITE_URL ?>pages/accessories/hair.php">Hair Accessories</a></li>
                                <li><a href="<?= SITE_URL ?>pages/accessories/bags-hats.php">Bags & Hats</a></li>
                                <li><a href="<?= SITE_URL ?>pages/accessories/bows.php">Bow Ties</a></li>
                                <li><a href="<?= SITE_URL ?>pages/accessories/toys.php">Toys & Gifts</a></li>
                            </ul>
                        </div>

                        <!-- Image Column 1 -->
                        <div class="mega-images">
                            <img src="<?= SITE_URL ?>uploads/accessory1.jpg" alt="Accessory 1">
                        </div>

                        <!-- Image Column 2 -->
                        <div class="mega-images">
                            <img src="<?= SITE_URL ?>uploads/accessory2.jpg" alt="Accessory 2">
                        </div>

                    </div>
                </div>
            </li>



            <li class="link"><a href="<?php echo SITE_URL; ?>pages/sale.php">SALE</a></li>
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


        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- If logged in, go to dashboard -->
            <a href="<?php echo SITE_URL; ?>dashboard.php" title="My Account">
                <i class="fa-regular fa-user"></i>
            </a>
        <?php else: ?>
            <!-- If NOT logged in, open modal -->
            <a href="#" id="profile-icon" title="Login / Register">
                <i class="fa-regular fa-user"></i>
            </a>
        <?php endif; ?>

        <a href="<?php echo SITE_URL; ?>pages/favorites.php" title="Wishlist"><i class="fa-regular fa-heart"></i><span class="badge">0</span></a>
        <a href="<?php echo SITE_URL; ?>pages/cart.php" title="Cart"><i class="fa-solid fa-bag-shopping"></i><span class="badge">0</span></a>
    </div>
</nav>

<!-- Profile Modal -->
<div id="profile-modal" class="modal-overlay">
  <div class="modal-box">
    <!-- Close Button -->
    <button class="modal-close" id="close-modal">&times;</button>

    <!-- Login Form -->
    <div class="form-container" id="login-form">
      <h2>Log in</h2>
      <form method="POST" action="<?php echo SITE_URL; ?>auth/login.php">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>

        <a href="./auth/forgot.php" id="forgot-password">Forgot your password?</a>

        <div class="form-actions">
          <button type="submit">Log in</button>
          <a href="#" id="show-register">New customer? Create your account</a>
        </div>
      </form>
    </div>

    <!-- Register Form -->
<div class="form-container hidden" id="register-form">
  <h2>Create account</h2>
  <form method="POST" action="auth/register.php">
    <input type="text" name="fullname" placeholder="Full Name" required>
    <input type="text" name="number" placeholder="Mobile Number" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>

    <div class="form-actions">
      <button type="submit">Register</button>
      <a href="../auth/register.php" id="show-login">Already have an account? Log in</a>
    </div>
  </form>
</div>


    <!-- Verification Form -->
    <div class="form-container hidden" id="verify-form">
      <h2>Verify account</h2>
      <form method="POST" action="auth/verify.php">
        <input type="hidden" name="email" id="verify-email">
        <input type="text" name="code" placeholder="Enter Verification Code *" required>
        <button type="submit">Verify</button>
      </form>
    </div>
  </div>
</div>

