// ✅ product.js - FIXED VERSION WITH PROPER FORM VISIBILITY
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

    // 🛒 ADD TO CART - UPDATED TO INCLUDE COLOR & MODAL RESET
    document.querySelectorAll(".add-to-cart").forEach((btn) => {
        btn.addEventListener("click", async () => {
            const productId = btn.dataset.id;
            hideResetPasswordFormIfVisible();
            const colorId = btn.dataset.colorId || document.getElementById('selected-color-id')?.value;
            console.log("🛒 Add to cart clicked, product ID:", productId, "Color ID:", colorId);
            
            if (!productId) return;

            try {
                const formData = new URLSearchParams();
                formData.append("product_id", productId);
                if (colorId) formData.append("color_id", colorId);

                const response = await fetch(SITE_URL + "actions/cart-add.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: formData,
                    credentials: "include",
                });

                const result = await response.json();
                console.log("🛒 Cart API response:", result);

                if (result.status === "success") {
                    alert("✅ Product added to cart successfully!");
                    updateCartAfterAdd();
                } else if (result.status === "exists") {
                    alert("🛒 This product is already in your cart.");
                } else if (
                    result.message === "Please log in first." ||
                    result.message === "not_logged_in" ||
                    result.status === "not_logged_in"
                ) {
                    showLoginModal();
                } else {
                    alert(result.message || "⚠️ Something went wrong.");
                }
            } catch (error) {
                console.error("Cart Error:", error);
                alert("⚠️ Network error.");
            }
        });
    });

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

    // 💖 WISHLIST FEATURE - NOW AJAX ONLY, NO REDIRECT
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
                console.log("💖 Wishlist button CLICKED!", { productId: this.dataset.id });

                event.preventDefault();
                event.stopPropagation();
                hideResetPasswordFormIfVisible();

                const productId = this.dataset.id;
                if (!productId) return;

                const originalText = this.textContent;
                this.disabled = true;
                this.textContent = "Adding...";

                try {
                    const res = await fetch(SITE_URL + "actions/wishlist-add.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: "product_id=" + encodeURIComponent(productId),
                        credentials: "include",
                    });

                    const data = await res.json();
                    console.log("💖 Wishlist API response:", data);

                    if (data.status === "success") {
                        this.textContent = "✓ Added";
                        updateWishlistCount();
                    } else if (data.status === "exists") {
                        this.textContent = "✓ Already in wishlist";
                    } else if (data.status === "not_logged_in" || data.message === "not_logged_in") {
                        showLoginModal();
                        this.textContent = originalText;
                        this.disabled = false;
                    } else {
                        alert(data.message || "⚠️ Something went wrong.");
                        this.textContent = originalText;
                        this.disabled = false;
                    }
                } catch (err) {
                    console.error("💖 NETWORK ERROR:", err);
                    alert("⚠️ Network error. Please try again.");
                    this.textContent = originalText;
                    this.disabled = false;
                }
            });
        });

        console.log("✅ Wishlist event listeners attached successfully");
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

    // 🚀 BUY NOW FUNCTIONALITY - INTEGRATED LIKE ADD TO CART/WISHLIST
    function initializeBuyNow() {
        console.log("🚀 Initializing Buy Now functionality...");

        const buyNowBtn = document.getElementById("buy-now-btn");
        if (!buyNowBtn) {
            console.error("❌ BUY NOW BUTTON NOT FOUND! Check your HTML ID.");
            return;
        }

        buyNowBtn.addEventListener("click", async function (event) {
            console.log("🚀 Buy Now button CLICKED!");
            
            event.preventDefault();
            event.stopPropagation();

            // Get all necessary data
            const colorId = this.dataset.colorId;
            const productId = this.dataset.productId;
            const quantity = document.getElementById("quantity")?.value || 1;
            const size = document.getElementById("selected-size")?.value || "M";
            const price = this.dataset.price;

            console.log("📦 Buy Now Data:", {
                colorId,
                productId,
                quantity,
                size,
                price
            });

            if (!colorId || !productId) {
                alert("⚠️ Missing product information. Color ID or Product ID not found.");
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

                console.log("📤 Sending Buy Now request to:", SITE_URL + "actions/buy_now.php");

                const response = await fetch(SITE_URL + "actions/buy_now.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: formData,
                    credentials: "include",
                });

                const result = await response.json();
                console.log("🚀 Buy Now API response:", result);

                if (result.success) {
                    // Redirect to checkout page
                    console.log("✅ Buy Now successful, redirecting to checkout...");
                    window.location.href = result.redirect_url || SITE_URL + "pages/checkout.php";
                } else if (result.message === 'not_logged_in' || result.requires_login) {
                    console.log("🔐 User not logged in, showing login modal");
                    showLoginModal();
                    this.textContent = originalText;
                    this.disabled = false;
                } else {
                    alert(result.message || "⚠️ Something went wrong with Buy Now.");
                    this.textContent = originalText;
                    this.disabled = false;
                }
            } catch (error) {
                console.error("🚀 Buy Now Network Error:", error);
                alert("⚠️ Network error. Please try again.");
                this.textContent = originalText;
                this.disabled = false;
            }
        });

        console.log("✅ Buy Now event listener attached successfully");
    }

    // 🚀 INITIALIZE EVERYTHING
    function initialize() {
        console.log("🚀 Starting full initialization...");
        addModalStyles(); // 🆕 ADD THIS LINE - Adds the CSS styles first
        initializeWishlist();
        initializeBuyNow();
        setupForgotPasswordReset();
        updateWishlistCount();
        updateCartAfterAdd();
        console.log("✅ Full initialization complete");
    }

    // Start the application
    initialize();
});