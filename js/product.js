// ✅ product.js - FULLY DEBUGGED VERSION
document.addEventListener("DOMContentLoaded", () => {
    console.log("🔧 product.js loaded - Starting initialization");
    
    // Automatically detect your local base URL
   const SITE_URL = window.location.origin + "/JDSystem/"; // e.g., "http://localhost/JDSystem/"
    console.log("🌐 SITE_URL detected:", SITE_URL);
    
    const loginModal = document.getElementById("profile-modal");
    console.log("🔍 Login modal found:", !!loginModal);

    // 🟣 Show login modal
    function showLoginModal() {
        console.log("🔄 Showing login modal");
        if (loginModal) {
            loginModal.style.display = "flex";
            document.body.style.overflow = "hidden";
            
            const loginForm = document.getElementById("login-form");
            const registerForm = document.getElementById("register-form");
            const verifyForm = document.getElementById("verify-form");
            
            if (loginForm) loginForm.classList.remove("hidden");
            if (registerForm) registerForm.classList.add("hidden");
            if (verifyForm) verifyForm.classList.add("hidden");
            
            if (loginForm) loginForm.scrollIntoView({ behavior: "smooth" });
        } else {
            console.warn("⚠️ Login modal not found in DOM.");
            window.location.href = SITE_URL + "auth/login.php";
        }
    }

    // 🔵 Close login modal
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

    function closeLoginModal() {
        if (loginModal) {
            loginModal.style.display = "none";
            document.body.style.overflow = "auto";
        }
    }

    // Initialize modal close functionality
    setupModalClose();

    // 🛒 ADD TO CART
    document.querySelectorAll(".add-to-cart").forEach((btn) => {
        btn.addEventListener("click", async () => {
            const productId = btn.dataset.id;
            console.log("🛒 Add to cart clicked, product ID:", productId);
            if (!productId) return;

            try {
                const response = await fetch(SITE_URL + "actions/cart-add.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "product_id=" + encodeURIComponent(productId),
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

    // 💖 WISHLIST FEATURE - EXTENSIVE DEBUGGING
    function initializeWishlist() {
        console.log("💖 Initializing wishlist functionality...");
        
        // Find all wishlist buttons
        const wishlistButtons = document.querySelectorAll(".wishlist-btn");
        console.log("🔍 Found wishlist buttons:", wishlistButtons.length);
        
        // Debug each button found
        wishlistButtons.forEach((btn, index) => {
            console.log(`   Button ${index + 1}:`, {
                text: btn.textContent,
                id: btn.dataset.id,
                classes: btn.className,
                disabled: btn.disabled
            });
        });

        if (wishlistButtons.length === 0) {
            console.error("❌ NO WISHLIST BUTTONS FOUND! Check your HTML class names.");
            return;
        }

        // Add event listeners to each wishlist button
        wishlistButtons.forEach((btn) => {
            btn.addEventListener("click", async function (event) {
                console.log("💖 Wishlist button CLICKED!", {
                    productId: this.dataset.id,
                    text: this.textContent,
                    event: event
                });
                
                event.preventDefault();
                event.stopPropagation();

                const productId = this.dataset.id;
                if (!productId) {
                    console.error("❌ No product ID found in data-id attribute");
                    return;
                }

                const originalText = this.textContent;
                this.disabled = true;
                this.textContent = "Adding...";
                console.log("💖 Button state updated: disabled=true, text='Adding...'");

                try {
                    console.log("💖 Sending request to:", SITE_URL + "actions/wishlist-add.php");
                    console.log("💖 Request data:", { product_id: productId });
                    
                    const res = await fetch(SITE_URL + "actions/wishlist-add.php", {
                        method: "POST",
                        headers: { 
                            "Content-Type": "application/x-www-form-urlencoded" 
                        },
                        body: "product_id=" + encodeURIComponent(productId),
                        credentials: "include",
                    });

                    console.log("💖 Response status:", res.status, res.statusText);
                    const data = await res.json();
                    console.log("💖 Wishlist API response:", data);

                    if (data.status === "success") {
                        console.log("✅ SUCCESS: Product added to wishlist, redirecting...");
                        // ✅ SUCCESS: Redirect to wishlist page
                        window.location.href = SITE_URL + "pages/wishlist.php";
                    } else if (data.status === "exists") {
                        console.log("ℹ️ EXISTS: Product already in wishlist, redirecting...");
                        // ✅ ALREADY EXISTS: Redirect to wishlist page
                        window.location.href = SITE_URL + "pages/wishlist.php";
                    } else if (data.status === "not_logged_in" || data.message === "not_logged_in") {
                        console.log("🔐 NOT LOGGED IN: Showing login modal");
                        // ❌ NOT LOGGED IN: Show login modal
                        showLoginModal();
                        this.textContent = originalText;
                        this.disabled = false;
                    } else {
                        console.error("❌ OTHER ERROR:", data.message);
                        // ❌ OTHER ERROR: Show message
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
        console.log("💌 Updating wishlist count...");
        fetch(SITE_URL + "actions/wishlist-count.php", {
            credentials: "include",
        })
            .then((res) => res.json())
            .then((data) => {
                console.log("💌 Wishlist count response:", data);
                const badge = document.getElementById("wishlist-count");
                if (badge) {
                    badge.textContent = data.count ?? 0;
                    console.log("💌 Wishlist badge updated to:", data.count);
                } else {
                    console.warn("💌 Wishlist count badge element not found");
                }
            })
            .catch((err) => console.error("💌 Error updating wishlist badge:", err));
    }

    // 🚀 INITIALIZE EVERYTHING
    function initialize() {
        console.log("🚀 Starting full initialization...");
        initializeWishlist();
        updateWishlistCount();
        updateCartAfterAdd();
        console.log("✅ Full initialization complete");
    }

    // Start the application
    initialize();
});