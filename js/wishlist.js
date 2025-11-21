document.addEventListener("DOMContentLoaded", () => {
    console.log('🔧 wishlist.js loaded - Starting initialization');
    
    const SITE_URL = window.location.origin + "/JDSystem/";

    // Custom modal variables
    let currentWishlistId = null;
    let currentItemElement = null;
    let currentAction = null; // 'remove', 'clear', or 'clearSelected'
    const confirmationModal = document.getElementById('confirmationModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalConfirm = document.getElementById('modalConfirm');
    const modalCancel = document.getElementById('modalCancel');

    // Track if removal is in progress
    let removalInProgress = false;

    // Notification function
    function showNotification(message, type = 'info') {
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notif => notif.remove());
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer;">×</button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 3000);
    }

    // Show custom modal for different actions
    function showConfirmationModal(action, wishlistId = null, itemElement = null) {
        currentAction = action;
        currentWishlistId = wishlistId;
        currentItemElement = itemElement;
        
        if (action === 'remove') {
            modalTitle.textContent = 'Remove Item';
            modalMessage.textContent = 'Are you sure you want to remove this item from your wishlist?';
            modalConfirm.textContent = 'Remove';
            modalConfirm.className = 'custom-modal-btn custom-modal-remove';
        } else if (action === 'clear') {
            modalTitle.textContent = 'Clear Wishlist';
            modalMessage.textContent = 'Are you sure you want to clear ALL items from your wishlist? This action cannot be undone.';
            modalConfirm.textContent = 'Clear All';
            modalConfirm.className = 'custom-modal-btn custom-modal-clear';
        } else if (action === 'clearSelected') {
            const selectedCount = document.querySelectorAll('.wishlist-select-item:checked').length;
            modalTitle.textContent = 'Clear Selected Items';
            modalMessage.textContent = `Are you sure you want to remove ${selectedCount} selected item(s) from your wishlist?`;
            modalConfirm.textContent = 'Clear Selected';
            modalConfirm.className = 'custom-modal-btn custom-modal-remove';
        }
        
        confirmationModal.classList.add('show');
    }

    // Hide custom modal
    function hideConfirmationModal() {
        confirmationModal.classList.remove('show');
        currentWishlistId = null;
        currentItemElement = null;
        currentAction = null;
    }

    // Modal event listeners
    modalConfirm.addEventListener('click', () => {
        if (currentAction === 'remove' && currentWishlistId && currentItemElement) {
            removeWishlistItem(currentWishlistId, currentItemElement);
        } else if (currentAction === 'clear') {
            clearEntireWishlist();
        } else if (currentAction === 'clearSelected') {
            clearSelectedWishlistItems();
        }
        hideConfirmationModal();
    });

    modalCancel.addEventListener('click', hideConfirmationModal);

    // Close modal when clicking outside
    confirmationModal.addEventListener('click', (e) => {
        if (e.target === confirmationModal) {
            hideConfirmationModal();
        }
    });

    // SINGLE FUNCTION TO REMOVE WISHLIST ITEM
    async function removeWishlistItem(wishlistId, itemElement) {
        if (removalInProgress) {
            console.log('Removal already in progress, skipping...');
            return false;
        }

        removalInProgress = true;
        
        try {
            console.log("Removing wishlist item:", wishlistId);
            
            const formData = new URLSearchParams();
            formData.append("wishlist_id", wishlistId);
            
            const response = await fetch(SITE_URL + "actions/wishlist-remove.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: formData,
                credentials: "include"
            });
            
            const result = await response.text();
            console.log("Remove response:", result);
            
            if (result.trim() === "success") {
                // Remove the item from UI
                if (itemElement) {
                    itemElement.style.opacity = '0';
                    itemElement.style.transform = 'scale(0.8)';
                    itemElement.style.transition = 'all 0.3s ease';
                    
                    setTimeout(() => {
                        itemElement.remove();
                        updateWishlistUI();
                        removalInProgress = false;
                    }, 300);
                }
                showNotification('Item removed from wishlist', 'success');
                return true;
            } else {
                // Handle specific error cases
                if (result.trim() === "not_found") {
                    // Item already removed or doesn't exist - remove from UI anyway
                    if (itemElement) {
                        itemElement.remove();
                        updateWishlistUI();
                    }
                    showNotification('Item removed', 'info');
                } else {
                    showNotification('Failed to remove item', 'error');
                }
                removalInProgress = false;
                return false;
            }
        } catch (error) {
            console.error('Error removing item:', error);
            showNotification('Network error', 'error');
            removalInProgress = false;
            return false;
        }
    }

    // CLEAR SELECTED WISHLIST ITEMS
    async function clearSelectedWishlistItems() {
        const selectedItems = document.querySelectorAll('.wishlist-select-item:checked');
        const selectedIds = [];
        
        // Collect all selected wishlist IDs
        selectedItems.forEach(checkbox => {
            const wishlistId = checkbox.dataset.wishlistId;
            if (wishlistId) {
                selectedIds.push(wishlistId);
            }
        });
        
        if (selectedIds.length === 0) {
            showNotification('No items selected to clear', 'error');
            return;
        }
        
        try {
            const formData = new URLSearchParams();
            formData.append("wishlist_ids", JSON.stringify(selectedIds));
            
            const response = await fetch(SITE_URL + "actions/wishlist-clear-selected.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: formData,
                credentials: "include"
            });
            
            const result = await response.text();
            
            if (result.trim() === "success") {
                // Remove all selected items from UI
                selectedItems.forEach(checkbox => {
                    const itemElement = checkbox.closest('.wishlist-item');
                    if (itemElement) {
                        itemElement.style.opacity = '0';
                        itemElement.style.transform = 'scale(0.8)';
                        itemElement.style.transition = 'all 0.3s ease';
                        
                        setTimeout(() => {
                            itemElement.remove();
                        }, 300);
                    }
                });
                
                setTimeout(() => {
                    updateWishlistUI();
                    showNotification(`${selectedIds.length} item(s) removed from wishlist`, 'success');
                }, 350);
            } else {
                showNotification('Failed to clear selected items', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Network error', 'error');
        }
    }

    // CLEAR ENTIRE WISHLIST
    async function clearEntireWishlist() {
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
                showNotification('Failed to clear wishlist', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Network error', 'error');
        }
    }

    // ADD TO CART FUNCTIONALITY
    async function addToCartFromWishlist(wishlistId, productId, colorId, price, itemElement) {
        try {
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
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: formData,
                credentials: "include",
            });

            const result = await response.json();

            if (result.status === "success" || result.status === "exists") {
                // Remove from wishlist after successful add to cart
                await removeWishlistItem(wishlistId, itemElement);
                showNotification("Item moved to cart successfully!", 'success');
                updateCartAfterAdd();
            } else if (result.message === "Please log in first." || result.status === "not_logged_in") {
                showNotification('Please log in to add items to cart', 'error');
            } else {
                showNotification(result.message || "Something went wrong.", 'error');
            }
        } catch (error) {
            console.error("Cart Error:", error);
            showNotification("Network error.", 'error');
        }
    }

    // UPDATE CART BADGE
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

    // MOVE ALL TO CART
    function initializeMoveAllToCart() {
        const moveAllButton = document.getElementById('move-all-to-cart');
        if (moveAllButton) {
            moveAllButton.addEventListener('click', async function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const selectedItems = document.querySelectorAll('.wishlist-select-item:checked');
                
                if (selectedItems.length === 0) {
                    showNotification('Please select items to move to cart', 'error');
                    return;
                }

                if (confirm(`Move ${selectedItems.length} item(s) to cart? All items will be added with Small size.`)) {
                    let successCount = 0;
                    let errorCount = 0;

                    for (const checkbox of selectedItems) {
                        const itemElement = checkbox.closest('.wishlist-item');
                        const moveButton = itemElement.querySelector('.btn-move-to-cart');
                        const wishlistId = checkbox.dataset.wishlistId;
                        const productId = moveButton.dataset.productId;
                        const colorId = moveButton.dataset.colorId;
                        const price = moveButton.dataset.price;
                        
                        try {
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
                            
                            if (result.status === "success" || result.status === "exists") {
                                await removeWishlistItem(wishlistId, itemElement);
                                successCount++;
                            } else {
                                errorCount++;
                            }
                        } catch (error) {
                            console.error('Error moving item to cart:', error);
                            errorCount++;
                        }
                    }

                    updateCartAfterAdd();

                    if (successCount > 0) {
                        let message = `✅ ${successCount} item(s) moved to cart successfully!`;
                        if (errorCount > 0) {
                            message += `, ${errorCount} item(s) failed`;
                        }
                        showNotification(message, successCount === selectedItems.length ? 'success' : 'warning');
                    } else {
                        showNotification(`❌ Failed to move items to cart`, 'error');
                    }

                    updateWishlistUI();
                }
            });
        }
    }

    // CLEAR WISHLIST BUTTON
    function initializeClearWishlist() {
        const clearWishlistButton = document.getElementById('clear-wishlist');
        if (clearWishlistButton) {
            clearWishlistButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const selectedItems = document.querySelectorAll('.wishlist-select-item:checked');
                if (selectedItems.length > 0) {
                    // If items are selected, clear only selected
                    showConfirmationModal('clearSelected');
                } else {
                    // If no items selected, ask if they want to clear all
                    showConfirmationModal('clear');
                }
            });
        }
    }

    // UPDATE SELECTED COUNT
    function updateSelectedCount() {
        const selectedCount = document.querySelectorAll('.wishlist-select-item:checked').length;
        const selectedCountElement = document.getElementById('selected-count');
        if (selectedCountElement) {
            selectedCountElement.textContent = selectedCount;
        }
    }

    // CALCULATE PRICE SUMMARY
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

    // INITIALIZE CHECKBOXES
    function initializeCheckboxes() {
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

        document.querySelectorAll('.wishlist-select-item').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateSelectedCount();
                calculatePriceSummary();
            });
        });
    }

    // UPDATE UI AFTER CHANGES
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

    // SINGLE EVENT LISTENER FOR ALL BUTTONS
    document.addEventListener('click', function(e) {
        // Handle remove buttons
        if (e.target.classList.contains('btn-remove-wishlist') || 
            e.target.closest('.btn-remove-wishlist')) {
            
            e.preventDefault();
            e.stopPropagation();
            
            const button = e.target.classList.contains('btn-remove-wishlist') ? 
                e.target : e.target.closest('.btn-remove-wishlist');
            
            const wishlistId = button.dataset.id;
            const itemElement = button.closest('.wishlist-item');
            
            if (wishlistId) {
                showConfirmationModal('remove', wishlistId, itemElement);
            }
        }
        
        // Handle add to cart buttons
        if (e.target.classList.contains('btn-move-to-cart') || 
            e.target.closest('.btn-move-to-cart')) {
            
            e.preventDefault();
            e.stopPropagation();
            
            const button = e.target.classList.contains('btn-move-to-cart') ? 
                e.target : e.target.closest('.btn-move-to-cart');
            
            const wishlistId = button.dataset.wishlistId;
            const productId = button.dataset.productId;
            const colorId = button.dataset.colorId;
            const price = button.dataset.price;
            const itemElement = button.closest('.wishlist-item');
            
            if (!colorId) {
                showNotification("Please select a color.", 'error');
                return;
            }

            addToCartFromWishlist(wishlistId, productId, colorId, price, itemElement);
        }
    });

    // INITIALIZE EVERYTHING
    function initialize() {
        console.log("🚀 Starting wishlist initialization...");
        initializeMoveAllToCart();
        initializeClearWishlist();
        initializeCheckboxes();
        updateSelectedCount();
        calculatePriceSummary();
        console.log("✅ Wishlist initialization complete");
    }

    // START THE APPLICATION
    initialize();
});