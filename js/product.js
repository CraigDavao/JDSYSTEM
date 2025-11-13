// ✅ product.js - FIXED VERSION WITH PROPER COLOR ID UPDATES
document.addEventListener("DOMContentLoaded", () => {
    console.log("🔧 product.js loaded - Starting initialization");
    
    // Automatically detect your local base URL
    const SITE_URL = window.location.origin + "/JDSystem/"; // e.g., "http://localhost/JDSystem/"
    console.log("🌐 SITE_URL detected:", SITE_URL);
    
    const loginModal = document.getElementById("profile-modal");
    console.log("🔍 Login modal found:", !!loginModal);

    // 🆕 ADD CSS TO ENSURE ONLY ONE FORM IS VISIBLE
    function addModalStyles() {
        const style = document.createElement('style');
        style.textContent = `
            /* 🆕 FORCE ONLY ONE FORM TO BE VISIBLE AT A TIME */
            #login-form, #reset-form, #register-form, #verify-form {
                display: none !important;
            }
            #login-form:not(.hidden), 
            #reset-form:not(.hidden), 
            #register-form:not(.hidden), 
            #verify-form:not(.hidden) {
                display: block !important;
            }
            .hidden {
                display: none !important;
                opacity: 0 !important;
                visibility: hidden !important;
            }
        `;
        document.head.appendChild(style);
        console.log("✅ Added modal visibility styles");
    }

    // 🆕 FUNCTION TO COMPLETELY RESET MODAL TO LOGIN FORM
    function resetModalToLogin() {
        console.log("🔄 Resetting modal to login form");
        
        // Get all form elements
        const loginForm = document.getElementById("login-form");
        const registerForm = document.getElementById("register-form");
        const verifyForm = document.getElementById("verify-form");
        const resetForm = document.getElementById("reset-form");
        
        // 🆕 HIDE ALL FORMS USING BOTH CLASSES AND STYLES
        if (loginForm) {
            loginForm.classList.remove("hidden");
            loginForm.style.display = 'block';
        }
        if (resetForm) {
            resetForm.classList.add("hidden");
            resetForm.style.display = 'none';
        }
        if (registerForm) {
            registerForm.classList.add("hidden");
            registerForm.style.display = 'none';
        }
        if (verifyForm) {
            verifyForm.classList.add("hidden");
            verifyForm.style.display = 'none';
        }
        
        console.log("✅ Modal reset to login form only");
    }

    // 🧹 Hide reset form whenever any other UI action happens (Add to Cart, Wishlist, Buy Now, etc.)
    function hideResetPasswordFormIfVisible() {
        const resetForm = document.getElementById("reset-form");
        const loginForm = document.getElementById("login-form");

        if (resetForm && !resetForm.classList.contains("hidden")) {
            console.log("🧹 Hiding reset password form due to another action");
            resetForm.classList.add("hidden");
            resetForm.style.display = "none";

            // Show the login form back
            if (loginForm) {
                loginForm.classList.remove("hidden");
                loginForm.style.display = "block";
            }
        }
    }

    // 🆕 FUNCTION TO SHOW ONLY RESET PASSWORD FORM
    function showResetPasswordForm() {
        console.log("🔄 Showing reset password form only");
        
        const loginForm = document.getElementById("login-form");
        const resetForm = document.getElementById("reset-form");
        
        if (loginForm && resetForm) {
            // Hide login form
            loginForm.classList.add("hidden");
            loginForm.style.display = 'none';
            
            // Show reset form
            resetForm.classList.remove("hidden");
            resetForm.style.display = 'block';
            
            console.log("✅ Reset password form shown, login form hidden");
        }
    }

    // 🆕 FUNCTION TO SETUP FORGOT PASSWORD RESET BEHAVIOR
    function setupForgotPasswordReset() {
        console.log("🔄 Setting up forgot password reset behavior");
        
        // Find forgot password link by text content
        const allLinks = document.querySelectorAll('a');
        let forgotPasswordLink = null;
        
        allLinks.forEach(link => {
            if (link.textContent.includes('Forgot your password') || 
                link.textContent.includes('Forgot password')) {
                forgotPasswordLink = link;
            }
        });
        
        if (forgotPasswordLink) {
            console.log("✅ Found forgot password link");
            
            forgotPasswordLink.addEventListener('click', function(e) {
                e.preventDefault();
                console.log("🔗 Forgot password clicked - showing reset form");
                showResetPasswordForm();
            });
        } else {
            console.warn("⚠️ Forgot password link not found");
        }
        
        // 🆕 SETUP "BACK TO LOGIN" BUTTON
        const allButtons = document.querySelectorAll('button, a');
        let backToLoginBtn = null;
        
        allButtons.forEach(btn => {
            if (btn.textContent.includes('Back to Login') || 
                btn.textContent.includes('Back to login')) {
                backToLoginBtn = btn;
            }
        });
        
        if (backToLoginBtn) {
            console.log("✅ Found back to login button");
            
            backToLoginBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log("🔙 Back to login clicked - resetting to login form");
                resetModalToLogin();
            });
        } else {
            console.warn("⚠️ Back to login button not found");
        }
    }

    // 🟣 Show login modal - UPDATED WITH COMPLETE RESET
    function showLoginModal() {
        console.log("🔄 Showing login modal - Resetting to login form");
        if (loginModal) {
            loginModal.style.display = "flex";
            document.body.style.overflow = "hidden";
            
            // 🆕 COMPLETE RESET TO LOGIN FORM
            resetModalToLogin();
            
        } else {
            console.warn("⚠️ Login modal not found in DOM.");
            window.location.href = SITE_URL + "auth/login.php";
        }
    }

    function closeLoginModal() {
        if (loginModal) {
            loginModal.style.display = "none";
            document.body.style.overflow = "auto";
            hideResetPasswordFormIfVisible(); // 🆕 Add this line
            setTimeout(resetModalToLogin, 100);
        }
    }

    // 🔵 Enhanced modal close setup
    function setupModalClose() {
        window.addEventListener("click", (e) => {
            if (e.target === loginModal) {
                closeLoginModal();
            }
        });

        const closeBtn = document.getElementById("close-modal");
        if (closeBtn) {
            closeBtn.addEventListener("click", closeLoginModal);
        }

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && loginModal.style.display === "flex") {
                closeLoginModal();
            }
        });
    }

    // Initialize modal close functionality
    setupModalClose();

    // ✅ SIZE SELECTION - NEW FUNCTION
    function initializeSizeSelection() {
        console.log("📏 Initializing size selection...");
        
        const sizeOptions = document.querySelectorAll('.size-option:not(.disabled)');
        
        sizeOptions.forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all size options
                document.querySelectorAll('.size-option').forEach(b => b.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                document.getElementById('selected-size').value = this.dataset.size;
                
                // Update quantity limits based on selected size
                updateQuantityLimits();
            });
        });
        
        // Auto-select first available size if none selected
        const activeSize = document.querySelector('.size-option.active');
        if (!activeSize && sizeOptions.length > 0) {
            sizeOptions[0].click();
        }
        
        console.log("✅ Size selection initialized");
    }

    // ✅ UPDATE QUANTITY LIMITS - IMPROVED FUNCTION
    function updateQuantityLimits() {
        const selectedSize = document.querySelector('.size-option.active')?.dataset.size;
        const sizeStockElements = document.querySelectorAll('.size-stock-item');
        let maxQuantity = 0;
        
        // Find the stock for selected size
        if (selectedSize) {
            sizeStockElements.forEach(item => {
                const sizeLabel = item.querySelector('.size-label');
                if (sizeLabel && sizeLabel.textContent.includes(selectedSize)) {
                    const quantityElement = item.querySelector('.size-quantity');
                    if (quantityElement && quantityElement.classList.contains('in-stock')) {
                        const stockText = quantityElement.textContent;
                        const match = stockText.match(/(\d+)/);
                        if (match) {
                            maxQuantity = parseInt(match[1]);
                        }
                    } else {
                        maxQuantity = 0; // Out of stock for this size
                    }
                }
            });
        }
        
        // If no size-specific stock found, use total stock
        if (maxQuantity === 0) {
            const stockTextElement = document.querySelector('.stock-available .stock-text, .stock-low .stock-text');
            if (stockTextElement) {
                const match = stockTextElement.textContent.match(/(\d+)/);
                if (match) {
                    maxQuantity = parseInt(match[1]);
                }
            }
        }
        
        // Update quantity input limits
        const quantityInput = document.getElementById('quantity');
        const minusBtn = document.getElementById('minus-btn');
        const plusBtn = document.getElementById('plus-btn');
        
        quantityInput.max = Math.max(0, maxQuantity);
        
        // Adjust current quantity if it exceeds new limit
        const currentQuantity = parseInt(quantityInput.value);
        if (currentQuantity > maxQuantity) {
            quantityInput.value = Math.max(1, maxQuantity);
        }
        
        // Enable/disable quantity controls
        const isOutOfStock = maxQuantity === 0;
        quantityInput.disabled = isOutOfStock;
        minusBtn.disabled = isOutOfStock;
        plusBtn.disabled = isOutOfStock;
        
        // Update Add to Cart button state
        const addToCartBtn = document.querySelector('.add-to-cart');
        const buyNowBtn = document.getElementById('buy-now-btn');
        const wishlistBtn = document.querySelector('.wishlist-btn');
        
        if (isOutOfStock) {
            if (addToCartBtn) {
                addToCartBtn.disabled = true;
                addToCartBtn.textContent = 'Out of Stock';
            }
            if (buyNowBtn) {
                buyNowBtn.disabled = true;
                buyNowBtn.textContent = 'Out of Stock';
            }
            if (wishlistBtn) {
                wishlistBtn.disabled = true;
            }
        } else {
            if (addToCartBtn) {
                addToCartBtn.disabled = false;
                addToCartBtn.textContent = 'Add to Cart';
            }
            if (buyNowBtn) {
                buyNowBtn.disabled = false;
                buyNowBtn.textContent = 'Buy Now';
            }
            if (wishlistBtn) {
                wishlistBtn.disabled = false;
            }
        }
    }

    // ✅ QUANTITY LOGIC
    function initializeQuantityControls() {
        const minusBtn = document.getElementById('minus-btn');
        const plusBtn = document.getElementById('plus-btn');
        const quantityInput = document.getElementById('quantity');

        if (minusBtn && plusBtn && quantityInput) {
            minusBtn.addEventListener('click', () => {
                let val = parseInt(quantityInput.value);
                if (val > 1) {
                    quantityInput.value = val - 1;
                }
            });

            plusBtn.addEventListener('click', () => {
                let val = parseInt(quantityInput.value);
                const max = parseInt(quantityInput.max);
                if (val < max) {
                    quantityInput.value = val + 1;
                }
            });

            quantityInput.addEventListener('change', () => {
                let val = parseInt(quantityInput.value);
                const max = parseInt(quantityInput.max);
                const min = parseInt(quantityInput.min);
                
                if (val < min) quantityInput.value = min;
                if (val > max) quantityInput.value = max;
            });
        }
    }

    // 🟣 CRITICAL FIX: Get the CURRENT selected color ID
    function getSelectedColorId() {
        // Method 1: Check the hidden field (updated by color selector)
        const hiddenColorField = document.getElementById('selected-color-id');
        if (hiddenColorField && hiddenColorField.value) {
            return hiddenColorField.value;
        }
        
        // Method 2: Check active color option
        const activeColor = document.querySelector('.color-option.active');
        if (activeColor && activeColor.dataset.colorId) {
            return activeColor.dataset.colorId;
        }
        
        // Method 3: Fallback to Add to Cart button data (initial page load)
        const addToCartBtn = document.querySelector('.add-to-cart');
        if (addToCartBtn && addToCartBtn.dataset.id) {
            return addToCartBtn.dataset.id;
        }
        
        console.error('❌ No color ID found!');
        return null;
    }

    // 🛒 ADD TO CART - FIXED TO USE CURRENT SELECTED COLOR
    function initializeAddToCart() {
        console.log("🛒 Initializing Add to Cart functionality...");
        
        const addToCartBtn = document.querySelector(".add-to-cart");
        if (!addToCartBtn) {
            console.error("❌ Add to Cart button not found!");
            return;
        }

        addToCartBtn.addEventListener("click", async () => {
            // ✅ GET CURRENT SELECTED COLOR, SIZE AND QUANTITY
            const colorId = getSelectedColorId();
            const activeSize = document.querySelector('.size-option.active');
            const size = activeSize ? activeSize.dataset.size : 'M';
            const quantity = document.getElementById("quantity")?.value || 1;

            console.log("🛒 Add to cart clicked - FINAL SELECTION:", {
                colorId: colorId,
                size: size,
                quantity: quantity
            });
            
            if (!colorId) {
                alert("⚠️ Please select a color.");
                return;
            }

            hideResetPasswordFormIfVisible();

            try {
                const formData = new URLSearchParams();
                formData.append("color_id", colorId);
                formData.append("quantity", quantity);
                formData.append("size", size);

                const response = await fetch(SITE_URL + "actions/cart-add.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: formData,
                    credentials: "include",
                });

                const result = await response.json();
                console.log("🛒 Cart API response:", result);

                if (result.status === "success") {
                    showNotification("✅ Product added to cart successfully!", 'success');
                    updateCartAfterAdd();
                } else if (result.status === "exists") {
                    showNotification("🛒 This product is already in your cart.", 'info');
                } else if (
                    result.message === "Please log in first." ||
                    result.message === "not_logged_in" ||
                    result.status === "not_logged_in"
                ) {
                    showLoginModal();
                } else {
                    showNotification(result.message || "⚠️ Something went wrong.", 'error');
                }
            } catch (error) {
                console.error("Cart Error:", error);
                showNotification("⚠️ Network error.", 'error');
            }
        });
        
        console.log("✅ Add to Cart initialized");
    }

    // 🟢 UPDATE CART BADGE
    async function updateCartAfterAdd() {
        try {
            const res = await fetch(SITE_URL + "actions/cart-fetch.php", {
                credentials: "include",
            });
            const data = await res.json();

            if (data.status === "success" && Array.isArray(data.cart)) {
                const cartCount = document.getElementById("cart-count");
                if (cartCount) {
                    cartCount.textContent = data.cart.length;
                }
            }
        } catch (e) {
            console.error("Error updating cart badge:", e);
        }
    }

    // 💖 WISHLIST FEATURE - FIXED TO USE CURRENT SELECTED COLOR
    function initializeWishlist() {
        console.log("💖 Initializing wishlist functionality...");

        const wishlistButtons = document.querySelectorAll(".wishlist-btn");
        console.log("🔍 Found wishlist buttons:", wishlistButtons.length);

        if (wishlistButtons.length === 0) {
            console.error("❌ NO WISHLIST BUTTONS FOUND! Check your HTML class names.");
            return;
        }

        wishlistButtons.forEach((btn) => {
            btn.addEventListener("click", async function (event) {
                console.log("💖 Wishlist button CLICKED!");

                event.preventDefault();
                event.stopPropagation();
                hideResetPasswordFormIfVisible();

                // ✅ GET CURRENT SELECTED COLOR
                const colorId = getSelectedColorId();
                const productId = this.dataset.id;

                console.log("💖 Wishlist Data:", {
                    productId: productId,
                    colorId: colorId,
                    activeColor: document.querySelector('.color-option.active')?.dataset.colorName
                });

                if (!productId) {
                    showNotification("⚠️ Product information missing.", 'error');
                    return;
                }

                if (!colorId) {
                    showNotification("⚠️ Please select a color before adding to wishlist.", 'error');
                    return;
                }

                const originalText = this.textContent;
                this.disabled = true;
                this.textContent = "Adding...";

                try {
                    const formData = new URLSearchParams();
                    formData.append("product_id", productId);
                    formData.append("color_id", colorId); // ✅ Send color_id

                    const res = await fetch(SITE_URL + "actions/wishlist-add.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: formData,
                        credentials: "include",
                    });

                    const data = await res.json();
                    console.log("💖 Wishlist API response:", data);

                    if (data.status === "success") {
                        this.textContent = "✓ Added";
                        updateWishlistCount();
                        // Show success message with color info
                        const colorName = document.querySelector('.color-option.active')?.dataset.colorName || 'selected color';
                        showNotification(`Added ${colorName} variant to wishlist!`, 'success');
                    } else if (data.status === "exists") {
                        this.textContent = "✓ Already in wishlist";
                        showNotification(data.message || 'Already in wishlist', 'info');
                    } else if (data.status === "not_logged_in" || data.message === "not_logged_in") {
                        showLoginModal();
                        this.textContent = originalText;
                        this.disabled = false;
                    } else {
                        showNotification(data.message || "⚠️ Something went wrong.", 'error');
                        this.textContent = originalText;
                        this.disabled = false;
                    }
                } catch (err) {
                    console.error("💖 NETWORK ERROR:", err);
                    showNotification("⚠️ Network error. Please try again.", 'error');
                    this.textContent = originalText;
                    this.disabled = false;
                }
            });
        });

        console.log("✅ Wishlist event listeners attached successfully");
    }

    // 🆕 ADD NOTIFICATION FUNCTION
    function showNotification(message, type = 'info') {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notif => notif.remove());
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer;">×</button>
        `;
        
        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 300px;
            animation: slideIn 0.3s ease;
            background-color: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : type === 'info' ? '#17a2b8' : '#6c757d'};
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 3000);
    }

    // 💌 UPDATE WISHLIST BADGE
    function updateWishlistCount() {
        fetch(SITE_URL + "actions/wishlist-count.php", { credentials: "include" })
            .then((res) => res.json())
            .then((data) => {
                const badge = document.getElementById("wishlist-count");
                if (badge) badge.textContent = data.count ?? 0;
            })
            .catch((err) => console.error("💌 Error updating wishlist badge:", err));
    }

    // 🚀 BUY NOW FUNCTIONALITY - FIXED TO USE CURRENT SELECTED COLOR
    function initializeBuyNow() {
        console.log("🚀 Initializing Buy Now functionality...");

        const buyNowBtn = document.getElementById("buy-now-btn");
        if (!buyNowBtn) {
            console.error("❌ BUY NOW BUTTON NOT FOUND!");
            return;
        }

        buyNowBtn.addEventListener("click", async function (event) {
            console.log("🚀 Buy Now button CLICKED!");
            
            event.preventDefault();
            event.stopPropagation();

            // ✅ CRITICAL FIX: Get the CURRENT selected color
            const colorId = getSelectedColorId();
            const productId = this.dataset.productId;
            const price = this.dataset.price;
            
            // Get current size and quantity
            const activeSize = document.querySelector('.size-option.active');
            const size = activeSize ? activeSize.dataset.size : 'M';
            const quantity = document.getElementById("quantity")?.value || 1;

            console.log("📦 Buy Now - FINAL SELECTION:", {
                colorId: colorId,
                productId: productId,
                quantity: quantity,
                size: size,
                price: price
            });

            // Validation
            if (!colorId) {
                showNotification("⚠️ Please select a color before buying.", 'error');
                return;
            }

            if (!productId) {
                showNotification("⚠️ Product information missing.", 'error');
                return;
            }

            const originalText = this.textContent;
            this.disabled = true;
            this.textContent = "Processing...";

            try {
                const formData = new URLSearchParams();
                formData.append("color_id", colorId);
                formData.append("product_id", productId);
                formData.append("quantity", quantity);
                formData.append("size", size);
                formData.append("price", price);

                console.log("📤 Sending Buy Now request...");
                console.log("🎯 Color ID being sent:", colorId);

                const response = await fetch(SITE_URL + "actions/buy_now.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: formData,
                    credentials: "include",
                });

                const result = await response.json();
                console.log("🚀 Buy Now API response:", result);

                if (result.success) {
                    console.log("✅ Buy Now successful!");
                    window.location.href = result.redirect_url || SITE_URL + "pages/checkout.php";
                } else if (result.message === 'not_logged_in' || result.requires_login) {
                    showLoginModal();
                    this.textContent = originalText;
                    this.disabled = false;
                } else {
                    showNotification(result.message || "⚠️ Something went wrong.", 'error');
                    this.textContent = originalText;
                    this.disabled = false;
                }
            } catch (error) {
                console.error("🚀 Buy Now Network Error:", error);
                showNotification("⚠️ Network error. Please try again.", 'error');
                this.textContent = originalText;
                this.disabled = false;
            }
        });

        console.log("✅ Buy Now event listener attached");
    }

    // ✅ LISTEN FOR COLOR CHANGES TO UPDATE BUTTONS
    function setupColorChangeListener() {
        document.addEventListener('colorChanged', function(e) {
            console.log('🎨 Color change detected, updating buttons...');
            
            // Update Add to Cart button data-id
            const addToCartBtn = document.querySelector('.add-to-cart');
            if (addToCartBtn && e.detail.colorId) {
                addToCartBtn.dataset.id = e.detail.colorId;
                console.log('✅ Updated Add to Cart button color ID:', e.detail.colorId);
            }
            
            // Update Buy Now button data-color-id
            const buyNowBtn = document.getElementById('buy-now-btn');
            if (buyNowBtn && e.detail.colorId) {
                buyNowBtn.dataset.colorId = e.detail.colorId;
                console.log('✅ Updated Buy Now button color ID:', e.detail.colorId);
            }
        });
    }

    // 🚀 INITIALIZE EVERYTHING
    function initialize() {
        console.log("🚀 Starting full initialization...");
        addModalStyles();
        initializeSizeSelection();
        initializeQuantityControls();
        initializeAddToCart(); // 🆕 ADD THIS - FIXED ADD TO CART
        initializeWishlist();
        initializeBuyNow();
        setupForgotPasswordReset();
        setupColorChangeListener(); // 🆕 ADD THIS - LISTEN FOR COLOR CHANGES
        updateWishlistCount();
        updateCartAfterAdd();
        
        // Initialize quantity limits
        updateQuantityLimits();
        
        console.log("✅ Full initialization complete");
    }

    // Start the application
    initialize();
});

