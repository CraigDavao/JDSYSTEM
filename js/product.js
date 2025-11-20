// ✅ product.js - CONSISTENT VERSION WITH RELIABLE QUANTITY CONTROLS
document.addEventListener("DOMContentLoaded", () => {
    console.log("🔧 product.js loaded - Starting initialization");
    
    const SITE_URL = window.location.origin + "/JDSystem/";
    console.log("🌐 SITE_URL detected:", SITE_URL);
    
    const loginModal = document.getElementById("profile-modal");

    // 🆕 GLOBAL STATE to track current max quantity
    let currentMaxQuantity = 0;

    // �ADD CSS TO ENSURE ONLY ONE FORM IS VISIBLE
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
        
        const loginForm = document.getElementById("login-form");
        const registerForm = document.getElementById("register-form");
        const verifyForm = document.getElementById("verify-form");
        const resetForm = document.getElementById("reset-form");
        
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

    // 🧹 Hide reset form whenever any other UI action happens
    function hideResetPasswordFormIfVisible() {
        const resetForm = document.getElementById("reset-form");
        const loginForm = document.getElementById("login-form");

        if (resetForm && !resetForm.classList.contains("hidden")) {
            console.log("🧹 Hiding reset password form due to another action");
            resetForm.classList.add("hidden");
            resetForm.style.display = "none";

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
            loginForm.classList.add("hidden");
            loginForm.style.display = 'none';
            resetForm.classList.remove("hidden");
            resetForm.style.display = 'block';
            console.log("✅ Reset password form shown, login form hidden");
        }
    }

    // 🆕 FUNCTION TO SETUP FORGOT PASSWORD RESET BEHAVIOR
    function setupForgotPasswordReset() {
        console.log("🔄 Setting up forgot password reset behavior");
        
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
        }
        
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
        }
    }

    // 🟣 Show login modal
    function showLoginModal() {
        console.log("🔄 Showing login modal - Resetting to login form");
        if (loginModal) {
            loginModal.style.display = "flex";
            document.body.style.overflow = "hidden";
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
            hideResetPasswordFormIfVisible();
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

    // ✅ SIZE SELECTION - RELIABLE VERSION
    function initializeSizeSelection() {
        console.log("📏 Initializing size selection...");
        
        const sizeOptions = document.querySelectorAll('.size-option');
        
        // Clear active states
        sizeOptions.forEach(btn => btn.classList.remove('active'));
        
        // Add fresh event listeners
        sizeOptions.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                
                if (this.classList.contains('disabled')) {
                    console.log("❌ Size option disabled, ignoring click");
                    return;
                }
                
                console.log("✅ Size option clicked:", this.dataset.size);
                
                // Remove active class from all
                document.querySelectorAll('.size-option').forEach(b => {
                    b.classList.remove('active');
                });
                
                // Add active to clicked
                this.classList.add('active');
                
                // Update hidden field
                const sizeField = document.getElementById('selected-size');
                if (sizeField) {
                    sizeField.value = this.dataset.size;
                }
                
                // Update quantity limits
                updateQuantityLimits();
            }, { once: false }); // Ensure event listener persists
        });
        
        // Auto-select first available size
        const availableSizes = document.querySelectorAll('.size-option:not(.disabled)');
        const activeSize = document.querySelector('.size-option.active');
        
        if (!activeSize && availableSizes.length > 0) {
            console.log("🔄 Auto-selecting first available size");
            availableSizes[0].classList.add('active');
            const sizeField = document.getElementById('selected-size');
            if (sizeField) {
                sizeField.value = availableSizes[0].dataset.size;
            }
        }
        
        // Always update quantity limits after size selection
        updateQuantityLimits();
        
        console.log("✅ Size selection initialized");
    }

    // ✅ UPDATE QUANTITY LIMITS - RELIABLE VERSION
    function updateQuantityLimits() {
        console.log("📦 Updating quantity limits...");
        
        const selectedSize = document.querySelector('.size-option.active')?.dataset.size;
        const sizeStockElements = document.querySelectorAll('.size-stock-item');
        let maxQuantity = 0;
        
        // Method 1: Get stock from size-specific elements
        if (selectedSize) {
            sizeStockElements.forEach(item => {
                const sizeLabel = item.querySelector('.size-label');
                if (sizeLabel && sizeLabel.textContent.trim().includes(selectedSize)) {
                    const quantityElement = item.querySelector('.size-quantity');
                    if (quantityElement) {
                        const stockText = quantityElement.textContent;
                        const match = stockText.match(/(\d+)/);
                        if (match) {
                            maxQuantity = parseInt(match[1]);
                            console.log("✅ Found size-specific stock:", maxQuantity, "for size", selectedSize);
                        }
                    }
                }
            });
        }
        
        // Method 2: Get total stock
        if (maxQuantity === 0) {
            const stockTextElement = document.querySelector('.stock-available .stock-text, .stock-low .stock-text');
            if (stockTextElement) {
                const match = stockTextElement.textContent.match(/(\d+)/);
                if (match) {
                    maxQuantity = parseInt(match[1]);
                    console.log("📊 Using total stock:", maxQuantity);
                }
            }
        }
        
        // Method 3: Check out of stock
        if (maxQuantity === 0) {
            const outOfStockElement = document.querySelector('.out-of-stock, .stock-out');
            if (outOfStockElement) {
                maxQuantity = 0;
                console.log("❌ Product is out of stock");
            }
        }
        
        // 🆕 UPDATE GLOBAL STATE
        currentMaxQuantity = maxQuantity;
        console.log("🎯 Current max quantity set to:", currentMaxQuantity);
        
        // Update quantity input
        const quantityInput = document.getElementById('quantity');
        if (quantityInput) {
            quantityInput.max = maxQuantity;
            console.log("✅ Quantity input max set to:", maxQuantity);
            
            // Ensure current value doesn't exceed max
            const currentVal = parseInt(quantityInput.value);
            if (currentVal > maxQuantity && maxQuantity > 0) {
                quantityInput.value = maxQuantity;
                console.log("🔄 Adjusted quantity to max:", maxQuantity);
            }
        }
        
        // Update button states
        updateButtonStates(maxQuantity === 0);
    }

    // ✅ UPDATE BUTTON STATES - SEPARATE FUNCTION FOR RELIABILITY
    function updateButtonStates(isOutOfStock) {
        console.log("🔄 Updating button states - Out of Stock:", isOutOfStock);
        
        const quantityInput = document.getElementById('quantity');
        const minusBtn = document.getElementById('minus-btn');
        const plusBtn = document.getElementById('plus-btn');
        const addToCartBtn = document.querySelector('.add-to-cart');
        const buyNowBtn = document.getElementById('buy-now-btn');
        const wishlistBtn = document.querySelector('.wishlist-btn');
        
        // Update quantity controls
        if (quantityInput) quantityInput.disabled = isOutOfStock;
        if (minusBtn) minusBtn.disabled = isOutOfStock;
        if (plusBtn) plusBtn.disabled = isOutOfStock;
        
        // Update action buttons
        if (addToCartBtn) {
            addToCartBtn.disabled = isOutOfStock;
            addToCartBtn.textContent = isOutOfStock ? 'Out of Stock' : 'Add to Cart';
        }
        if (buyNowBtn) {
            buyNowBtn.disabled = isOutOfStock;
            buyNowBtn.textContent = isOutOfStock ? 'Out of Stock' : 'Buy Now';
        }
        if (wishlistBtn) {
            wishlistBtn.disabled = isOutOfStock;
        }
        
        console.log("✅ Button states updated");
    }

    // ✅ QUANTITY CONTROLS - ULTRA RELIABLE VERSION
    function initializeQuantityControls() {
        console.log("🔢 Initializing RELIABLE quantity controls...");
        
        const minusBtn = document.getElementById('minus-btn');
        const plusBtn = document.getElementById('plus-btn');
        const quantityInput = document.getElementById('quantity');
        
        if (!minusBtn || !plusBtn || !quantityInput) {
            console.error("❌ Quantity controls not found");
            return;
        }
        
        // 🆕 REMOVE ANY EXISTING EVENT LISTENERS FIRST
        const newMinusBtn = minusBtn.cloneNode(true);
        const newPlusBtn = plusBtn.cloneNode(true);
        minusBtn.parentNode.replaceChild(newMinusBtn, minusBtn);
        plusBtn.parentNode.replaceChild(newPlusBtn, plusBtn);
        
        // 🆕 GET FRESH REFERENCES
        const freshMinusBtn = document.getElementById('minus-btn');
        const freshPlusBtn = document.getElementById('plus-btn');
        
        // 🆕 MINUS BUTTON - SIMPLE AND RELIABLE
        freshMinusBtn.addEventListener('click', function() {
            const quantityInput = document.getElementById('quantity');
            let val = parseInt(quantityInput.value);
            if (val > 1) {
                quantityInput.value = val - 1;
                console.log("➖ Quantity decreased to:", quantityInput.value);
            }
        });
        
        // 🆕 PLUS BUTTON - SIMPLE AND RELIABLE
        freshPlusBtn.addEventListener('click', function() {
            const quantityInput = document.getElementById('quantity');
            let val = parseInt(quantityInput.value);
            const max = currentMaxQuantity > 0 ? currentMaxQuantity : parseInt(quantityInput.max) || 1000;
            
            if (val < max) {
                quantityInput.value = val + 1;
                console.log("➕ Quantity increased to:", quantityInput.value);
            } else {
                console.log("⚠️ Cannot exceed max quantity:", max);
            }
        });
        
        // 🆕 INPUT VALIDATION - SIMPLE AND RELIABLE
        quantityInput.addEventListener('change', function() {
            let val = parseInt(this.value);
            const max = currentMaxQuantity > 0 ? currentMaxQuantity : parseInt(this.max) || 1000;
            const min = 1;
            
            if (isNaN(val) || val < min) {
                this.value = min;
            } else if (val > max) {
                this.value = max;
            }
            console.log("📝 Quantity changed to:", this.value);
        });
        
        console.log("✅ Quantity controls initialized reliably");
    }

    // 🟣 CRITICAL FIX: Get the CURRENT selected color ID
    function getSelectedColorId() {
        const hiddenColorField = document.getElementById('selected-color-id');
        if (hiddenColorField && hiddenColorField.value) {
            return hiddenColorField.value;
        }
        
        const activeColor = document.querySelector('.color-option.active');
        if (activeColor && activeColor.dataset.colorId) {
            return activeColor.dataset.colorId;
        }
        
        const addToCartBtn = document.querySelector('.add-to-cart');
        if (addToCartBtn && addToCartBtn.dataset.id) {
            return addToCartBtn.dataset.id;
        }
        
        console.error('❌ No color ID found!');
        return null;
    }

    // 🛒 ADD TO CART - RELIABLE VERSION
    function initializeAddToCart() {
        console.log("🛒 Initializing Add to Cart functionality...");
        
        const addToCartBtn = document.querySelector(".add-to-cart");
        if (!addToCartBtn) {
            console.error("❌ Add to Cart button not found!");
            return;
        }

        addToCartBtn.addEventListener("click", async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
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
                } else if (result.status === "not_logged_in") {
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

    // 💖 WISHLIST FEATURE
    function initializeWishlist() {
        console.log("💖 Initializing wishlist functionality...");

        const wishlistButtons = document.querySelectorAll(".wishlist-btn");
        if (wishlistButtons.length === 0) {
            console.error("❌ NO WISHLIST BUTTONS FOUND!");
            return;
        }

        wishlistButtons.forEach((btn) => {
            btn.addEventListener("click", async function (event) {
                event.preventDefault();
                event.stopPropagation();
                hideResetPasswordFormIfVisible();

                const colorId = getSelectedColorId();
                const productId = this.dataset.id;

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
                    formData.append("color_id", colorId);

                    const res = await fetch(SITE_URL + "actions/wishlist-add.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: formData,
                        credentials: "include",
                    });

                    const data = await res.json();

                    if (data.status === "success") {
                        this.textContent = "✓ Added";
                        updateWishlistCount();
                        const colorName = document.querySelector('.color-option.active')?.dataset.colorName || 'selected color';
                        showNotification(`Added ${colorName} variant to wishlist!`, 'success');
                    } else if (data.status === "exists") {
                        this.textContent = "✓ Already in wishlist";
                        showNotification('Already in wishlist', 'info');
                    } else if (data.status === "not_logged_in") {
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
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notif => notif.remove());
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer;">×</button>
        `;
        
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

    // 🚀 BUY NOW FUNCTIONALITY
    function initializeBuyNow() {
        console.log("🚀 Initializing Buy Now functionality...");

        const buyNowBtn = document.getElementById("buy-now-btn");
        if (!buyNowBtn) {
            console.error("❌ BUY NOW BUTTON NOT FOUND!");
            return;
        }

        buyNowBtn.addEventListener("click", async function (event) {
            event.preventDefault();
            event.stopPropagation();

            const colorId = getSelectedColorId();
            const productId = this.dataset.productId;
            const price = this.dataset.price;
            const activeSize = document.querySelector('.size-option.active');
            const size = activeSize ? activeSize.dataset.size : 'M';
            const quantity = document.getElementById("quantity")?.value || 1;

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

                const response = await fetch(SITE_URL + "actions/buy_now.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: formData,
                    credentials: "include",
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = result.redirect_url || SITE_URL + "pages/checkout.php";
                } else if (result.message === 'not_logged_in') {
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
    }

    // ✅ SETUP COLOR CHANGE LISTENER
    function setupColorChangeListener() {
        console.log("🎨 Setting up color change listeners...");
        
        document.addEventListener('colorChanged', function(e) {
            console.log('🎨 Color change event detected');
            
            const addToCartBtn = document.querySelector('.add-to-cart');
            if (addToCartBtn && e.detail.colorId) {
                addToCartBtn.dataset.id = e.detail.colorId;
            }
            
            const buyNowBtn = document.getElementById('buy-now-btn');
            if (buyNowBtn && e.detail.colorId) {
                buyNowBtn.dataset.colorId = e.detail.colorId;
            }
            
            // Re-initialize everything for reliability
            setTimeout(() => {
                initializeSizeSelection();
                initializeQuantityControls();
            }, 100);
        });
    }

    // 🚀 INITIALIZE EVERYTHING - RELIABLE SEQUENCE
    function initialize() {
        console.log("🚀 Starting RELIABLE initialization...");
        
        // Step 1: Setup basics
        addModalStyles();
        setupModalClose();
        setupForgotPasswordReset();
        
        // Step 2: Initialize core functionality in sequence
        setTimeout(() => {
            initializeSizeSelection();
        }, 50);
        
        setTimeout(() => {
            initializeQuantityControls();
        }, 100);
        
        setTimeout(() => {
            initializeAddToCart();
            initializeWishlist();
            initializeBuyNow();
            setupColorChangeListener();
            updateWishlistCount();
            updateCartAfterAdd();
        }, 150);
        
        console.log("✅ Reliable initialization sequence started");
    }

    // Start the application
    initialize();
});