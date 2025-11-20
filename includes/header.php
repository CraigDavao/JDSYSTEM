<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../connection/connection.php';
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
    <link rel="stylesheet" href="<?= SITE_URL ?>css/search.css?v=<?= time() ?>">

    <!-- Font Awesome (for icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- JS -->
    <script src="<?php echo SITE_URL; ?>js/script.js?v=<?= time(); ?>" defer></script>
    <script src="<?= SITE_URL; ?>js/header.js?v=<?= time(); ?>"></script>
    <script src="<?= SITE_URL; ?>js/wishlist.js?v=<?= time(); ?>" defer></script>
    <script>
    const SITE_URL = "<?= SITE_URL ?>";
    </script>

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
                            <img src="<?php echo SITE_URL; ?>uploads/una.jpg" alt="Sample 1">
                            <img src="<?php echo SITE_URL; ?>uploads/pangalawa.jpg" alt="Sample 2">
                            <img src="<?php echo SITE_URL; ?>uploads/tatlo.png" alt="Sample 3">
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
                            <img src="<?= SITE_URL ?>uploads/kidgirl.webp" alt="Kids Girls">
                        </div>

                        <!-- Image 2 -->
                        <div class="mega-images">
                            <img src="<?= SITE_URL ?>uploads/kidboy.webp" alt="Kids Boys">
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

      <a href="<?php echo SITE_URL; ?>pages/wishlist.php" title="Wishlist">
  <i class="fa-regular fa-heart"></i>
  <span class="badge" id="wishlist-count">0</span>
</a>

       <a href="<?php echo SITE_URL; ?>pages/cart.php" title="Cart">
    <i class="fa-solid fa-bag-shopping"></i>
    <span class="badge" id="cart-count">0</span>
</a>

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
      <form method="POST" action="<?php echo SITE_URL; ?>auth/login.php" id="login-form-data">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>

        <a href="#" id="show-forgot-password">Forgot your password?</a>

        <div class="form-actions">
          <button type="submit">Log in</button>
          <a href="#" id="show-register">New customer? Create your account</a>
        </div>
      </form>
    </div>

    

    <!-- Register Form -->
    <div class="form-container hidden" id="register-form">
      <h2>Create account</h2>
      <form method="POST" action="<?php echo SITE_URL; ?>auth/register.php" id="register-form-data">
        <input type="text" name="fullname" placeholder="Full Name" required>
        <input type="text" name="number" placeholder="Mobile Number" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>

        <div class="form-actions">
          <button type="submit">Register</button>
          <a href="#" id="show-login">Already have an account? Log in</a>
        </div>
      </form>
    </div>

    <!-- Forgot Password Form -->
    <div class="form-container hidden" id="forgot-password-form">
      <h2>Reset Password</h2>
      <form id="forgot-password-form-data">
        <p>Enter your email address and we'll send you a password reset link.</p>
        <input type="email" name="email" placeholder="Email" required id="forgot-email">
        
        <div class="form-actions">
          <button type="submit">Send Reset Link</button>
          <a href="#" id="show-login-from-forgot">Back to Login</a>
        </div>
      </form>
    </div>

    <!-- Reset Password Form -->
    <div class="form-container hidden" id="reset-password-form">
      <h2>Create New Password</h2>
      <form id="reset-password-form-data">
        <input type="hidden" name="token" id="reset-token">
        <input type="password" name="password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
        
        <div class="form-actions">
          <button type="submit">Reset Password</button>
          <a href="#" id="show-login-from-reset">Back to Login</a>
        </div>
      </form>
    </div>

    <!-- Verification Form -->
    <div class="form-container hidden" id="verify-form">
      <h2>Verify account</h2>
      <form method="POST" action="<?php echo SITE_URL; ?>auth/verify.php">
        <input type="hidden" name="email" id="verify-email">
        <input type="text" name="code" placeholder="Enter Verification Code *" required>
        <button type="submit">Verify</button>
      </form>
    </div>
  </div>
</div>

<script src="<?= SITE_URL; ?>js/wishlist.js?v=<?= time(); ?>" defer></script>

<!-- 🟢 NUCLEAR CART BADGE FIX - WORKS ON ALL PAGES -->
<script>
// 🟢 NUCLEAR OPTION - Override everything to work on ALL pages
(function() {
    'use strict';
    
    console.log('💥 NUCLEAR CART BADGE ACTIVATED ON:', window.location.href);
    
    // 1. Override ALL possible cart-related functions globally
    const nuclearFunctions = ['updateCartBadge', 'updateCartCount', 'updateCart', 'cartUpdate', 'refreshCart'];
    nuclearFunctions.forEach(funcName => {
        window[funcName] = function() {
            console.log('💥 Nuclear: Overridden', funcName, 'called');
            nuclearForceUpdateCartBadge();
        };
    });
    
    // 2. Nuclear badge protection
    function nuclearBadgeProtection() {
        const badge = document.getElementById('cart-count');
        if (!badge) {
            // Badge doesn't exist yet, keep checking
            setTimeout(nuclearBadgeProtection, 500);
            return;
        }
        
        console.log('💥 Nuclear: Protecting badge element on', window.location.href);
        
        // Completely lock down the badge - make it read-only
        try {
            Object.defineProperty(badge, 'textContent', {
                get: function() { 
                    const correctCount = getNuclearCorrectCountSync();
                    return correctCount; 
                },
                set: function(value) { 
                    console.log('💥 Nuclear: Blocked textContent update to:', value, 'on', window.location.href);
                    nuclearForceUpdateCartBadge();
                },
                configurable: false,
                enumerable: true
            });
            
            Object.defineProperty(badge, 'innerText', {
                get: function() { 
                    const correctCount = getNuclearCorrectCountSync();
                    return correctCount; 
                },
                set: function(value) { 
                    console.log('💥 Nuclear: Blocked innerText update to:', value, 'on', window.location.href);
                    nuclearForceUpdateCartBadge();
                },
                configurable: false,
                enumerable: true
            });
            
            Object.defineProperty(badge, 'innerHTML', {
                get: function() { 
                    const correctCount = getNuclearCorrectCountSync();
                    return correctCount; 
                },
                set: function(value) { 
                    console.log('💥 Nuclear: Blocked innerHTML update to:', value, 'on', window.location.href);
                    nuclearForceUpdateCartBadge();
                },
                configurable: false,
                enumerable: true
            });
            
            // Set initial value
            nuclearForceUpdateCartBadge();
            
        } catch (e) {
            console.log('💥 Nuclear: Protection setup error:', e);
        }
    }
    
    // 3. Get correct count with fallback
    let nuclearCurrentCount = '0';
    
    function getNuclearCorrectCountSync() {
        return nuclearCurrentCount;
    }
    
    async function nuclearForceUpdateCartBadge() {
        try {
            const response = await fetch('<?php echo SITE_URL; ?>actions/cart-count.php?t=' + Date.now());
            const data = await response.json();
            
            nuclearCurrentCount = data.count.toString();
            const badge = document.getElementById('cart-count');
            
            if (badge) {
                // Use direct DOM manipulation to bypass our own protections
                badge.firstChild?.remove();
                badge.appendChild(document.createTextNode(nuclearCurrentCount));
                
                console.log('💥 Nuclear: Badge set to', nuclearCurrentCount, 'on', window.location.href);
            }
        } catch (error) {
            console.log('💥 Nuclear: Update error on', window.location.href, ':', error);
        }
    }
    
    // 4. Monitor ALL fetch requests for cart-related activity
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        const url = args[0];
        
        if (typeof url === 'string' && (url.includes('cart') || url.includes('Cart'))) {
            console.log('💥 Nuclear: Detected cart-related fetch:', url);
            return originalFetch.apply(this, args).then(response => {
                // After any cart request, force update
                setTimeout(nuclearForceUpdateCartBadge, 100);
                return response;
            });
        }
        
        return originalFetch.apply(this, args);
    };
    
    // 5. Start nuclear protection immediately
    nuclearBadgeProtection();
    
    // 6. Frequent updates to override any bad changes
    setInterval(nuclearForceUpdateCartBadge, 1500);
    
    // 7. Override on ALL possible events
    const nuclearEvents = ['DOMContentLoaded', 'load', 'pageshow', 'visibilitychange', 'mouseenter', 'click', 'keydown'];
    nuclearEvents.forEach(event => {
        window.addEventListener(event, nuclearForceUpdateCartBadge);
    });
    
    // 8. Monitor for badge element creation
    const nuclearObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 && (node.id === 'cart-count' || node.querySelector('#cart-count'))) {
                    console.log('💥 Nuclear: New badge element detected on', window.location.href);
                    setTimeout(nuclearBadgeProtection, 100);
                }
            });
        });
    });
    
    nuclearObserver.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    console.log('💥 Nuclear protection active on:', window.location.href);
    
    // Initial update
    setTimeout(nuclearForceUpdateCartBadge, 100);
    setTimeout(nuclearForceUpdateCartBadge, 1000);
    setTimeout(nuclearForceUpdateCartBadge, 3000);
    
})();
</script>

<!-- Improved Modal JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const SITE_URL = "<?= SITE_URL ?>";

    // Modal elements
    const profileModal = document.getElementById('profile-modal');
    const closeModal = document.getElementById('close-modal');
    const profileIcon = document.getElementById('profile-icon');

    // Form containers
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const forgotPasswordForm = document.getElementById('forgot-password-form');
    const resetPasswordForm = document.getElementById('reset-password-form');
    const verifyForm = document.getElementById('verify-form');

    // Navigation links
    const showForgotPassword = document.getElementById('show-forgot-password');
    const showLoginFromForgot = document.getElementById('show-login-from-forgot');
    const showLoginFromReset = document.getElementById('show-login-from-reset');
    const showRegister = document.getElementById('show-register');
    const showLogin = document.getElementById('show-login');

    /** -------------------------
     * Modal show / hide handling
     * ------------------------- */
    profileIcon?.addEventListener('click', (e) => {
        e.preventDefault();
        profileModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        hideAllForms();
        loginForm.classList.remove('hidden');
    });

    closeModal?.addEventListener('click', () => {
        profileModal.style.display = 'none';
        document.body.style.overflow = 'auto';
        hideAllForms();
        loginForm.classList.remove('hidden');
        // ✅ Clear any URL token so reset form won't reappear
        const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
    });

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === profileModal) {
            profileModal.style.display = 'none';
            document.body.style.overflow = 'auto';
            hideAllForms();
            loginForm.classList.remove('hidden');
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && profileModal.style.display === 'flex') {
            profileModal.style.display = 'none';
            document.body.style.overflow = 'auto';
            hideAllForms();
            loginForm.classList.remove('hidden');
        }
    });

    /** -------------------------
     * Form navigation logic
     * ------------------------- */
    showForgotPassword?.addEventListener('click', (e) => {
        e.preventDefault();
        hideAllForms();
        forgotPasswordForm.classList.remove('hidden');
    });

    showLoginFromForgot?.addEventListener('click', (e) => {
        e.preventDefault();
        hideAllForms();
        loginForm.classList.remove('hidden');
    });

    showLoginFromReset?.addEventListener('click', (e) => {
        e.preventDefault();
        hideAllForms();
        loginForm.classList.remove('hidden');
    });

    showRegister?.addEventListener('click', (e) => {
        e.preventDefault();
        hideAllForms();
        registerForm.classList.remove('hidden');
    });

    showLogin?.addEventListener('click', (e) => {
        e.preventDefault();
        hideAllForms();
        loginForm.classList.remove('hidden');
    });

    /** -------------------------
     * Utility functions
     * ------------------------- */
    function hideAllForms() {
        [loginForm, registerForm, forgotPasswordForm, resetPasswordForm, verifyForm].forEach(form => {
            if (form) form.classList.add('hidden');
        });
    }

    function clearFormMessages(form) {
        const existingMessages = form.querySelectorAll('.form-message');
        existingMessages.forEach(msg => msg.remove());
    }

    function showFormMessage(form, message, isSuccess = true) {
        clearFormMessages(form);
        const msg = document.createElement('div');
        msg.className = `form-message ${isSuccess ? 'success' : 'error'}`;
        msg.textContent = message;
        form.insertBefore(msg, form.firstChild);
    }

    /** -------------------------
     * Login Form Submission (AJAX)
     * ------------------------- */
    const loginFormData = document.getElementById('login-form-data');
    loginFormData?.addEventListener('submit', async function (e) {
        e.preventDefault();
        
        const email = this.querySelector('input[name="email"]').value.trim();
        const password = this.querySelector('input[name="password"]').value;

        if (!email || !password) {
            showFormMessage(this, '⚠️ Please fill in all fields.', false);
            return;
        }

        const btn = this.querySelector('button');
        const originalText = btn.textContent;
        btn.textContent = 'Logging in...';
        btn.disabled = true;

        try {
            const formData = new FormData(this);
            
            const response = await fetch(SITE_URL + 'auth/login.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.text();

            // Check if login was successful (redirects to dashboard)
            if (response.redirected || result.includes('dashboard') || result.includes('success')) {
                // Login successful - reload page to update header
                window.location.reload();
            } 
            // Check for specific error messages
            else if (result.includes('Invalid email or password') || result.includes('incorrect')) {
                showFormMessage(this, '❌ Invalid email or password. Please try again.', false);
            }
            else if (result.includes('Account not verified')) {
                showFormMessage(this, '⚠️ Please verify your account first.', false);
            }
            else if (result.includes('User not found')) {
                showFormMessage(this, '❌ No account found with this email.', false);
            }
            else {
                // Generic error
                showFormMessage(this, '❌ Login failed. Please try again.', false);
            }

        } catch (error) {
            console.error('Login error:', error);
            showFormMessage(this, '⚠️ Network error. Please try again.', false);
        } finally {
            btn.textContent = originalText;
            btn.disabled = false;
        }
    });

    /** -------------------------
     * Forgot Password Submission
     * ------------------------- */
    const forgotPasswordFormData = document.getElementById('forgot-password-form-data');
    forgotPasswordFormData?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const email = this.querySelector('input[name="email"]').value.trim();

        if (!email) {
            showFormMessage(this, '⚠️ Please enter your email.', false);
            return;
        }

        const btn = this.querySelector('button');
        const originalText = btn.textContent;
        btn.textContent = 'Sending...';
        btn.disabled = true;

        try {
            const res = await fetch(SITE_URL + 'auth/forgot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ email })
            });

            const text = await res.text();
            if (text.includes('✅') || text.includes('reset link has been sent')) {
                showFormMessage(this, '✅ Password reset link has been sent!', true);
                setTimeout(() => {
                    this.reset();
                    hideAllForms();
                    loginForm.classList.remove('hidden');
                }, 2500);
            } else {
                showFormMessage(this, text.replace(/<[^>]*>/g, '') || '⚠️ Error sending email.', false);
            }
        } catch {
            showFormMessage(this, '⚠️ Network error. Try again later.', false);
        } finally {
            btn.textContent = originalText;
            btn.disabled = false;
        }
    });

    /** -------------------------
     * Reset Password Submission
     * ------------------------- */
    const resetPasswordFormData = document.getElementById('reset-password-form-data');
    resetPasswordFormData?.addEventListener('submit', async function (e) {
        e.preventDefault();

        const token = this.querySelector('#reset-token').value.trim();
        const password = this.querySelector('input[name="password"]').value;
        const confirm = this.querySelector('input[name="confirm_password"]').value;

        if (!token) return showFormMessage(this, '⚠️ Invalid token.', false);
        if (!password || !confirm) return showFormMessage(this, '⚠️ Fill in all fields.', false);
        if (password !== confirm) return showFormMessage(this, '⚠️ Passwords do not match.', false);
        if (password.length < 6) return showFormMessage(this, '⚠️ Minimum 6 characters.', false);

        const btn = this.querySelector('button');
        const originalText = btn.textContent;
        btn.textContent = 'Resetting...';
        btn.disabled = true;

        try {
            const res = await fetch(SITE_URL + 'actions/reset-password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ token, new_password: password })
            });

            const result = await res.json();
            if (result.status === 'success') {
                showFormMessage(this, result.message, true);
                this.reset();
                setTimeout(() => {
                    hideAllForms();
                    loginForm.classList.remove('hidden');
                }, 2000);
            } else {
                showFormMessage(this, result.message, false);
            }
        } catch {
            showFormMessage(this, '⚠️ Network error.', false);
        } finally {
            btn.textContent = originalText;
            btn.disabled = false;
        }
    });

    /** -------------------------
     * Auto-show Reset Form by Token
     * ------------------------- */
    const urlParams = new URLSearchParams(window.location.search);
    const resetToken = urlParams.get('token');
    if (resetToken) {
        document.getElementById('reset-token').value = resetToken;
        profileModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        hideAllForms();
        resetPasswordForm.classList.remove('hidden');
        // Remove token from URL so it won't persist
        const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({}, document.title, cleanUrl);
    }

    // Add CSS for form messages
    const style = document.createElement('style');
    style.textContent = `
        .form-message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
        }
        .form-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .form-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .hidden {
            display: none !important;
        }
    `;
    document.head.appendChild(style);
});
</script>

<!-- 🟢 NUCLEAR WISHLIST BADGE FIX - WORKS ON ALL PAGES -->
<script>
// 🟢 NUCLEAR OPTION - Override everything to work on ALL pages
(function() {
    'use strict';
    
    console.log('💖 NUCLEAR WISHLIST BADGE ACTIVATED ON:', window.location.href);
    
    // 1. Override ALL possible wishlist-related functions globally
    const nuclearFunctions = ['updateWishlistBadge', 'updateWishlistCount', 'updateWishlist', 'wishlistUpdate', 'refreshWishlist'];
    nuclearFunctions.forEach(funcName => {
        window[funcName] = function() {
            console.log('💖 Nuclear: Overridden', funcName, 'called');
            nuclearForceUpdateWishlistBadge();
        };
    });
    
    // 2. Nuclear badge protection
    function nuclearBadgeProtection() {
        const badge = document.getElementById('wishlist-count');
        if (!badge) {
            // Badge doesn't exist yet, keep checking
            setTimeout(nuclearBadgeProtection, 500);
            return;
        }
        
        console.log('💖 Nuclear: Protecting wishlist badge element on', window.location.href);
        
        // Completely lock down the badge - make it read-only
        try {
            Object.defineProperty(badge, 'textContent', {
                get: function() { 
                    const correctCount = getNuclearCorrectCountSync();
                    return correctCount; 
                },
                set: function(value) { 
                    console.log('💖 Nuclear: Blocked textContent update to:', value, 'on', window.location.href);
                    nuclearForceUpdateWishlistBadge();
                },
                configurable: false,
                enumerable: true
            });
            
            Object.defineProperty(badge, 'innerText', {
                get: function() { 
                    const correctCount = getNuclearCorrectCountSync();
                    return correctCount; 
                },
                set: function(value) { 
                    console.log('💖 Nuclear: Blocked innerText update to:', value, 'on', window.location.href);
                    nuclearForceUpdateWishlistBadge();
                },
                configurable: false,
                enumerable: true
            });
            
            Object.defineProperty(badge, 'innerHTML', {
                get: function() { 
                    const correctCount = getNuclearCorrectCountSync();
                    return correctCount; 
                },
                set: function(value) { 
                    console.log('💖 Nuclear: Blocked innerHTML update to:', value, 'on', window.location.href);
                    nuclearForceUpdateWishlistBadge();
                },
                configurable: false,
                enumerable: true
            });
            
            // Set initial value
            nuclearForceUpdateWishlistBadge();
            
        } catch (e) {
            console.log('💖 Nuclear: Protection setup error:', e);
        }
    }
    
    // 3. Get correct count with fallback
    let nuclearCurrentCount = '0';
    
    function getNuclearCorrectCountSync() {
        return nuclearCurrentCount;
    }
    
    async function nuclearForceUpdateWishlistBadge() {
        try {
            const response = await fetch('<?php echo SITE_URL; ?>actions/wishlist-count.php?t=' + Date.now());
            const data = await response.json();
            
            nuclearCurrentCount = data.count.toString();
            const badge = document.getElementById('wishlist-count');
            
            if (badge) {
                // Use direct DOM manipulation to bypass our own protections
                badge.firstChild?.remove();
                badge.appendChild(document.createTextNode(nuclearCurrentCount));
                
                console.log('💖 Nuclear: Wishlist badge set to', nuclearCurrentCount, 'on', window.location.href);
            }
        } catch (error) {
            console.log('💖 Nuclear: Wishlist update error on', window.location.href, ':', error);
            // Fallback: try to get count from PHP session
            nuclearCurrentCount = '0';
        }
    }
    
    // 4. Monitor ALL fetch requests for wishlist-related activity
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        const url = args[0];
        
        if (typeof url === 'string' && (url.includes('wishlist') || url.includes('Wishlist'))) {
            console.log('💖 Nuclear: Detected wishlist-related fetch:', url);
            return originalFetch.apply(this, args).then(response => {
                // After any wishlist request, force update
                setTimeout(nuclearForceUpdateWishlistBadge, 100);
                return response;
            });
        }
        
        return originalFetch.apply(this, args);
    };
    
    // 5. Start nuclear protection immediately
    nuclearBadgeProtection();
    
    // 6. Frequent updates to override any bad changes
    setInterval(nuclearForceUpdateWishlistBadge, 1500);
    
    // 7. Override on ALL possible events
    const nuclearEvents = ['DOMContentLoaded', 'load', 'pageshow', 'visibilitychange', 'mouseenter', 'click', 'keydown'];
    nuclearEvents.forEach(event => {
        window.addEventListener(event, nuclearForceUpdateWishlistBadge);
    });
    
    // 8. Monitor for badge element creation
    const nuclearObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 && (node.id === 'wishlist-count' || node.querySelector('#wishlist-count'))) {
                    console.log('💖 Nuclear: New wishlist badge element detected on', window.location.href);
                    setTimeout(nuclearBadgeProtection, 100);
                }
            });
        });
    });
    
    nuclearObserver.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    console.log('💖 Nuclear wishlist protection active on:', window.location.href);
    
    // Initial update
    setTimeout(nuclearForceUpdateWishlistBadge, 100);
    setTimeout(nuclearForceUpdateWishlistBadge, 1000);
    setTimeout(nuclearForceUpdateWishlistBadge, 3000);
    
})();
</script>

 </body>
</html>