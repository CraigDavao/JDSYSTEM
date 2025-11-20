document.addEventListener("DOMContentLoaded", () => {
    console.log('🔧 wishlist.js loaded - Starting initialization');
    
    const SITE_URL = window.location.origin + "/JDSystem/";
    console.log("🌐 SITE_URL detected:", SITE_URL);

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

    // Update selected count
    function updateSelectedCount() {
        const selectedCount = document.querySelectorAll('.wishlist-select-item:checked').length;
        const selectedCountElement = document.getElementById('selected-count');
        if (selectedCountElement) {
            selectedCountElement.textContent = selectedCount;
        }
    }

    // Calculate price summary
    function calculatePriceSummary() {
        const selectedItems = document.querySelectorAll('.wishlist-select-item:checked');
        let totalValue = 0;
        let totalSavings = 0;

        selectedItems.forEach(checkbox => {
            const itemElement = checkbox.closest('.wishlist-item');
            const priceElement = itemElement.querySelector('.wishlist-product-price .final-price');
            if (priceElement) {
                const finalPriceText = priceElement.textContent;
                const finalPrice = parseFloat(finalPriceText.replace('₱', '').replace(/,/g, '')) || 0;
                totalValue += finalPrice;
                
                const originalPriceElement = itemElement.querySelector('.original-price');
                if (originalPriceElement) {
                    const originalPrice = parseFloat(originalPriceElement.textContent.replace('₱', '').replace(/,/g, '')) || 0;
                    totalSavings += (originalPrice - finalPrice);
                }
            }
        });

        const totalValueElement = document.getElementById('total-value');
        const totalSavingsElement = document.getElementById('total-savings');
        
        if (totalValueElement) totalValueElement.textContent = '₱' + totalValue.toFixed(2);
        if (totalSavingsElement) totalSavingsElement.textContent = '₱' + totalSavings.toFixed(2);
    }

    // Select All functionality
    const selectAllCheckbox = document.getElementById('wishlist-select-all');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.wishlist-select-item');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedCount();
            calculatePriceSummary();
        });
    }

    // Individual checkbox changes
    document.querySelectorAll('.wishlist-select-item').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedCount();
            calculatePriceSummary();
        });
    });

    // 🛒 ADD TO CART FROM WISHLIST - FIXED FOR WISHLIST (NO SIZE SELECTION)
    function initializeAddToCartFromWishlist() {
        console.log("🛒 Initializing Add to Cart from Wishlist functionality...");
        
        const addToCartButtons = document.querySelectorAll('.btn-move-to-cart');
        if (!addToCartButtons.length) {
            console.error("❌ Add to Cart buttons not found!");
            return;
        }

        addToCartButtons.forEach(button => {
            button.addEventListener("click", async function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const wishlistId = this.dataset.wishlistId;
                const productId = this.dataset.productId;
                const colorId = this.dataset.colorId;
                const price = this.dataset.price;
                const itemElement = this.closest('.wishlist-item');

                console.log("🛒 Add to cart from wishlist - SELECTION:", {
                    wishlistId: wishlistId,
                    productId: productId,
                    colorId: colorId,
                    price: price
                });
                
                if (!colorId) {
                    showNotification("⚠️ Please select a color.", 'error');
                    return;
                }

                try {
                    // 🔧 WISHLIST SPECIFIC: Default size "Small", quantity 1
                    const defaultSize = "Small";
                    const quantity = 1;

                    // 🔧 SEND EXACT DATA LIKE PRODUCT.JS
                    const formData = new URLSearchParams();
                    formData.append("product_id", productId);
                    formData.append("color_id", colorId);
                    formData.append("quantity", quantity.toString());
                    formData.append("size", defaultSize);
                    formData.append("price", price);

                    console.log("🛒 Sending to cart:", {
                        product_id: productId,
                        color_id: colorId,
                        size: defaultSize,
                        quantity: quantity,
                        price: price
                    });

                    const response = await fetch(SITE_URL + "actions/cart-add.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: formData,
                        credentials: "include",
                    });

                    const result = await response.json();
                    console.log("🛒 Cart API response:", result);

                    if (result.status === "success") {
                        // Remove from wishlist after successful add to cart
                        await removeFromWishlist(wishlistId, itemElement);
                        showNotification("✅ Item moved to cart successfully! (Size: Small)", 'success');
                        updateCartAfterAdd();
                    } else if (result.status === "exists") {
                        // 🔧 FIX: If item already exists in cart, still remove from wishlist
                        await removeFromWishlist(wishlistId, itemElement);
                        showNotification("🛒 Item already in cart! Quantity updated.", 'info');
                        updateCartAfterAdd();
                    } else if (
                        result.message === "Please log in first." ||
                        result.message === "not_logged_in" ||
                        result.status === "not_logged_in"
                    ) {
                        showNotification('Please log in to add items to cart', 'error');
                    } else {
                        showNotification(result.message || "⚠️ Something went wrong.", 'error');
                    }
                } catch (error) {
                    console.error("Cart Error:", error);
                    showNotification("⚠️ Network error.", 'error');
                }
            });
        });
        
        console.log("✅ Add to Cart from Wishlist initialized");
    }

    // Remove item functionality
    document.querySelectorAll('.btn-remove-wishlist').forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const wishlistId = this.dataset.id;
            const itemElement = this.closest('.wishlist-item');
            
            if (confirm('Remove this item from your wishlist?')) {
                await removeFromWishlist(wishlistId, itemElement);
            }
        });
    });

    // 🛒 MOVE ALL TO CART - FIXED FOR WISHLIST (NO SIZE SELECTION)
    function initializeMoveAllToCart() {
        console.log("🛒 Initializing Move All to Cart functionality...");

        const moveAllButton = document.getElementById('move-all-to-cart');
        if (!moveAllButton) {
            console.error("❌ Move All button not found!");
            return;
        }

        moveAllButton.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const selectedItems = document.querySelectorAll('.wishlist-select-item:checked');
            console.log("🛒 Move All - Selected items:", selectedItems.length);
            
            if (selectedItems.length === 0) {
                showNotification('Please select items to move to cart', 'error');
                return;
            }

            if (confirm(`Move ${selectedItems.length} item(s) to cart? All items will be added with Small size.`)) {
                let successCount = 0;
                let alreadyInCartCount = 0;
                let errorCount = 0;

                // Process items one by one
                for (const checkbox of selectedItems) {
                    const itemElement = checkbox.closest('.wishlist-item');
                    const moveButton = itemElement.querySelector('.btn-move-to-cart');
                    const wishlistId = checkbox.dataset.wishlistId;
                    const productId = moveButton.dataset.productId;
                    const colorId = moveButton.dataset.colorId;
                    const price = moveButton.dataset.price;
                    
                    try {
                        // 🔧 WISHLIST SPECIFIC: Default size "Small", quantity 1
                        const defaultSize = "Small";
                        const quantity = 1;

                        const formData = new URLSearchParams();
                        formData.append("product_id", productId);
                        formData.append("color_id", colorId);
                        formData.append("quantity", quantity.toString());
                        formData.append("size", defaultSize);
                        formData.append("price", price);

                        const response = await fetch(SITE_URL + "actions/cart-add.php", {
                            method: "POST",
                            headers: {"Content-Type": "application/x-www-form-urlencoded"},
                            body: formData,
                            credentials: "include",
                        });

                        const result = await response.json();
                        console.log("🛒 Item cart response:", result);
                        
                        if (result.status === "success") {
                            await removeWishlistItem(wishlistId, itemElement);
                            successCount++;
                        } else if (result.status === "exists") {
                            // Item already in cart - still remove from wishlist
                            await removeWishlistItem(wishlistId, itemElement);
                            alreadyInCartCount++;
                        } else {
                            errorCount++;
                            console.error('❌ Failed to add item to cart:', result);
                        }
                    } catch (error) {
                        console.error('❌ Error moving item to cart:', error);
                        errorCount++;
                    }
                }

                // Update cart badge
                updateCartAfterAdd();

                // Show results with clear messaging about size
                if (successCount > 0) {
                    let message = `✅ ${successCount} item(s) moved to cart successfully (Size: Small)`;
                    if (alreadyInCartCount > 0) {
                        message += `, ${alreadyInCartCount} item(s) were already in your cart (quantity updated)`;
                    }
                    if (errorCount > 0) {
                        message += `, ${errorCount} item(s) failed`;
                    }
                    showNotification(message, successCount === selectedItems.length ? 'success' : 'warning');
                } else if (alreadyInCartCount > 0) {
                    showNotification(`🛒 ${alreadyInCartCount} item(s) were already in your cart (quantity updated)!`, 'info');
                } else {
                    showNotification(`❌ Failed to move items to cart`, 'error');
                }

                // Update UI
                updateWishlistUI();
            }
        });

        console.log("✅ Move All to Cart initialized");
    }

    // Clear wishlist
    const clearWishlistButton = document.getElementById('clear-wishlist');
    if (clearWishlistButton) {
        clearWishlistButton.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (confirm('Clear all items from your wishlist?')) {
                try {
                    const res = await fetch(SITE_URL + "actions/wishlist-clear.php", {
                        method: "POST",
                        headers: {"Content-Type": "application/x-www-form-urlencoded"},
                        credentials: "include"
                    });
                    
                    const result = await res.text();
                    
                    if (result.trim() === "success") {
                        document.getElementById('wishlist-items').innerHTML = `
                            <div class="wishlist-empty">
                              <h3>Your wishlist is empty</h3>
                              <p>Save items you love for later!</p>
                              <a href='${SITE_URL}pages/new.php' class='btn-continue-shopping'>Continue Shopping</a>
                            </div>`;
                        updateWishlistUI();
                        showNotification('Wishlist cleared', 'success');
                    } else {
                        showNotification('Failed to clear wishlist: ' + result, 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showNotification('Network error: ' + error.message, 'error');
                }
            }
        });
    }

    // Helper function to remove wishlist item
    async function removeWishlistItem(wishlistId, itemElement) {
        try {
            const formData = new URLSearchParams();
            formData.append("wishlist_id", wishlistId);
            
            const res = await fetch(SITE_URL + "actions/wishlist-remove.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: formData,
                credentials: "include"
            });
            
            const result = await res.text();
            
            if (result.trim() === "success" && itemElement) {
                itemElement.remove();
                return true;
            }
            return false;
        } catch (error) {
            console.error('Error removing wishlist item:', error);
            return false;
        }
    }

    // Remove from wishlist with animation
    async function removeFromWishlist(wishlistId, itemElement) {
        try {
            const formData = new URLSearchParams();
            formData.append("wishlist_id", wishlistId);
            
            const res = await fetch(SITE_URL + "actions/wishlist-remove.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: formData,
                credentials: "include"
            });
            
            const result = await res.text();
            
            if (result.trim() === "success") {
                if (itemElement) {
                    itemElement.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(() => {
                        itemElement.remove();
                        updateWishlistUI();
                    }, 300);
                }
                return true;
            } else {
                showNotification('Failed to remove item', 'error');
                return false;
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Network error: ' + error.message, 'error');
            return false;
        }
    }

    // Update UI after changes
    function updateWishlistUI() {
        const items = document.querySelectorAll('.wishlist-item');
        const itemCount = items.length;
        
        updateSelectedCount();
        calculatePriceSummary();
        
        if (itemCount === 0 && !document.querySelector('.wishlist-empty')) {
            document.getElementById('wishlist-items').innerHTML = `
                <div class="wishlist-empty">
                  <h3>Your wishlist is empty</h3>
                  <p>Save items you love for later!</p>
                  <a href='${SITE_URL}pages/new.php' class='btn-continue-shopping'>Continue Shopping</a>
                </div>`;
        }
    }

    // Add CSS for animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeOut {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0; transform: scale(0.8); }
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);

    // 🚀 INITIALIZE EVERYTHING
    function initialize() {
        console.log("🚀 Starting wishlist initialization...");
        
        // Initialize core functionality
        initializeAddToCartFromWishlist();
        initializeMoveAllToCart();
        
        // Initialize UI
        updateSelectedCount();
        calculatePriceSummary();
        
        console.log("✅ Wishlist initialization complete");
    }

    // Start the application
    initialize();
});

// 🟢 UPDATE WISHLIST BADGE GLOBALLY
async function updateWishlistBadge() {
    try {
        // Use the nuclear function if it exists, otherwise fallback
        if (window.nuclearForceUpdateWishlistBadge) {
            window.nuclearForceUpdateWishlistBadge();
        } else {
            // Fallback: direct update
            const response = await fetch(SITE_URL + "actions/wishlist-count.php?t=" + Date.now());
            const data = await response.json();
            const badge = document.getElementById('wishlist-count');
            if (badge) {
                badge.textContent = data.count;
            }
        }
    } catch (error) {
        console.error("Error updating wishlist badge:", error);
    }
}