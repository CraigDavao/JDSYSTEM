let securityActive = false;
let waitingAction = null;

function initSecurity(verified) {
    securityActive = verified;
}

function showSection(sectionName) {
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    document.querySelectorAll('.menu-item').forEach(item => {
        item.classList.remove('active');
    });
    document.getElementById(sectionName).classList.add('active');
    document.querySelector(`[data-section="${sectionName}"]`).classList.add('active');
}

function needSecurityCheck(action) {
    if (securityActive) {
        action();
    } else {
        waitingAction = action;
        openSecurityModal();
    }
}

function openSecurityModal() {
    document.getElementById('securityModal').classList.add('show');
    document.getElementById('security_password').focus();
}

function closeSecurityModal() {
    document.getElementById('securityModal').classList.remove('show');
    document.getElementById('security_password').value = '';
    waitingAction = null;
}

function verifyPassword() {
    const password = document.getElementById('security_password').value;
    if (!password) {
        alert('Please enter your password');
        return;
    }
    fetch('dashboard.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'security_verify=1&password=' + encodeURIComponent(password)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            securityActive = true;
            closeSecurityModal();
            if (waitingAction) {
                waitingAction();
                waitingAction = null;
            }
        } else {
            alert(data.message || 'Verification failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error during verification');
    });
}

// Address Functions - SMOOTH VERSION
function removeAddress(addressId) {
    needSecurityCheck(() => {
        if (confirm('Are you sure you want to delete this address?')) {
            console.log('Deleting address:', addressId);
            
            // Show immediate loading feedback
            const addressCard = document.querySelector(`.address-panel[data-address-id="${addressId}"]`);
            if (addressCard) {
                addressCard.style.opacity = '0.5';
                addressCard.style.pointerEvents = 'none';
            }
            
            fetch('auth/delete_address.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'address_id=' + addressId
            })
            .then(response => {
                console.log('Address delete response status:', response.status);
                return response.json().catch(err => {
                    console.error('JSON parse error:', err);
                    return { success: false, message: 'Invalid response from server' };
                });
            })
            .then(data => {
                console.log('Address delete API response:', data);
                if (data.success) {
                    showMessage('Address deleted successfully!', 'success');
                    
                    // SMOOTH REMOVAL WITH IMMEDIATE UI UPDATES
                    if (addressCard) {
                        addressCard.style.transition = 'all 0.3s ease';
                        addressCard.style.opacity = '0';
                        addressCard.style.transform = 'scale(0.8)';
                        
                        setTimeout(() => {
                            addressCard.remove();
                            // Update everything immediately
                            updateAddressCount();
                            checkEmptyAddressState();
                        }, 300);
                    } else {
                        // If card not found, update counts anyway
                        updateAddressCount();
                        checkEmptyAddressState();
                    }
                } else {
                    showMessage('Error: ' + data.message, 'error');
                    // Restore card if error
                    if (addressCard) {
                        addressCard.style.opacity = '1';
                        addressCard.style.pointerEvents = 'auto';
                        addressCard.style.transform = 'scale(1)';
                    }
                }
            })
            .catch(error => {
                console.error('Address delete fetch error:', error);
                showMessage('Error deleting address. Please try again.', 'error');
                // Restore card if error
                if (addressCard) {
                    addressCard.style.opacity = '1';
                    addressCard.style.pointerEvents = 'auto';
                    addressCard.style.transform = 'scale(1)';
                }
            });
        }
    });
}

function makeDefaultAddress(addressId, type) {
    needSecurityCheck(() => {
        // Show loading state
        const addressCard = document.querySelector(`.address-panel[data-address-id="${addressId}"]`);
        if (addressCard) {
            addressCard.style.opacity = '0.7';
            addressCard.style.pointerEvents = 'none';
        }
        
        console.log('Setting address as default:', addressId, 'type:', type);
        
        fetch('auth/set_default_address.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'address_id=' + addressId + '&type=' + type
        })
        .then(response => {
            console.log('Set default response status:', response.status);
            return response.json().catch(err => {
                console.error('JSON parse error:', err);
                return { success: false, message: 'Invalid response from server' };
            });
        })
        .then(data => {
            console.log('Set default API response:', data);
            if (data.success) {
                showMessage(data.message, 'success');
                
                // SMOOTH UI UPDATE - Refresh addresses section with animation
                refreshAddressesSectionSmoothly();
                
                // Also update dashboard overview
                updateDashboardOverview();
            } else {
                showMessage('Error: ' + data.message, 'error');
                // Restore UI on error
                if (addressCard) {
                    addressCard.style.opacity = '1';
                    addressCard.style.pointerEvents = 'auto';
                }
            }
        })
        .catch(error => {
            console.error('Set default fetch error:', error);
            showMessage('Error setting default address. Please try again.', 'error');
            // Restore UI on error
            if (addressCard) {
                addressCard.style.opacity = '1';
                addressCard.style.pointerEvents = 'auto';
            }
        });
    });
}

// SMOOTH REFRESH FUNCTION FOR ADDRESSES
function refreshAddressesSectionSmoothly() {
    console.log('Smoothly refreshing addresses section...');
    
    const addressesSection = document.getElementById('addresses');
    if (!addressesSection) return;
    
    // Show loading state with smooth transition
    addressesSection.style.transition = 'all 0.3s ease';
    addressesSection.style.opacity = '0.7';
    addressesSection.style.pointerEvents = 'none';
    
    // Fetch updated content
    fetch(window.location.href + '?t=' + new Date().getTime())
    .then(response => response.text())
    .then(html => {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        const newAddressesSection = tempDiv.querySelector('#addresses');
        
        if (newAddressesSection) {
            // Smooth fade out and replace
            addressesSection.style.opacity = '0';
            setTimeout(() => {
                addressesSection.innerHTML = newAddressesSection.innerHTML;
                
                // Smooth fade in
                setTimeout(() => {
                    addressesSection.style.opacity = '1';
                }, 50);
                
                // Re-initialize event listeners
                initializeAddressEventListeners();
                updateAddressCount();
                
                console.log('Addresses section refreshed smoothly!');
            }, 300);
        }
    })
    .catch(error => {
        console.error('Error refreshing addresses:', error);
        addressesSection.style.opacity = '1';
    })
    .finally(() => {
        addressesSection.style.pointerEvents = 'auto';
    });
}

// UPDATE DASHBOARD OVERVIEW WITH SMOOTH TRANSITION
function updateDashboardOverview() {
    console.log('Updating dashboard overview with new default addresses...');
    
    const overviewSection = document.getElementById('overview');
    if (!overviewSection || !overviewSection.classList.contains('active')) {
        return; // Only update if we're on the overview page
    }
    
    // Fetch the latest data to update the dashboard overview
    fetch(window.location.href + '?t=' + new Date().getTime())
    .then(response => response.text())
    .then(html => {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        const newOverviewSection = tempDiv.querySelector('#overview');
        
        if (newOverviewSection) {
            // Get the current default addresses from the new data
            const defaultShippingElement = newOverviewSection.querySelector('.info-line:nth-child(5) .value');
            const defaultBillingElement = newOverviewSection.querySelector('.info-line:nth-child(6) .value');
            
            if (defaultShippingElement && defaultBillingElement) {
                // Update the dashboard overview with smooth transition
                const currentOverview = document.getElementById('overview');
                if (currentOverview) {
                    const currentShipping = currentOverview.querySelector('.info-line:nth-child(5) .value');
                    const currentBilling = currentOverview.querySelector('.info-line:nth-child(6) .value');
                    
                    if (currentShipping && currentBilling) {
                        // Smooth update
                        currentShipping.style.transition = 'all 0.3s ease';
                        currentBilling.style.transition = 'all 0.3s ease';
                        
                        currentShipping.style.opacity = '0.5';
                        currentBilling.style.opacity = '0.5';
                        
                        setTimeout(() => {
                            currentShipping.innerHTML = defaultShippingElement.innerHTML;
                            currentBilling.innerHTML = defaultBillingElement.innerHTML;
                            
                            currentShipping.style.opacity = '1';
                            currentBilling.style.opacity = '1';
                        }, 150);
                        
                        console.log('Dashboard overview updated smoothly with new default addresses');
                    }
                }
            }
        }
    })
    .catch(error => {
        console.error('Error updating dashboard overview:', error);
    });
}

// UPDATED: Function to update address count in sidebar - IMMEDIATE
function updateAddressCount() {
    const addressCount = document.querySelectorAll('.address-panel').length;
    const addressMenuItem = document.querySelector('.menu-item[data-section="addresses"]');
    
    if (addressMenuItem) {
        let addressBadge = addressMenuItem.querySelector('.item-count');
        
        if (addressCount > 0) {
            if (!addressBadge) {
                // Create badge if it doesn't exist
                addressBadge = document.createElement('span');
                addressBadge.className = 'item-count';
                addressBadge.style.transition = 'all 0.3s ease';
                addressMenuItem.appendChild(addressBadge);
            }
            addressBadge.textContent = addressCount;
        } else {
            // Remove badge if no addresses
            if (addressBadge) {
                addressBadge.style.opacity = '0';
                addressBadge.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    if (addressBadge.parentNode) {
                        addressBadge.remove();
                    }
                }, 300);
            }
        }
    }
    
    console.log('Address count updated to:', addressCount);
}

// UPDATED: Function to check and show empty state if needed
function checkEmptyAddressState() {
    const addressGrid = document.querySelector('.address-grid');
    const addressPanels = document.querySelectorAll('.address-panel');
    
    if (addressPanels.length === 0) {
        // Remove any existing empty state first
        const existingEmptyState = addressGrid.querySelector('.empty-state');
        if (existingEmptyState) {
            existingEmptyState.style.transition = 'all 0.3s ease';
            existingEmptyState.style.opacity = '0';
            existingEmptyState.style.transform = 'scale(0.8)';
            setTimeout(() => {
                if (existingEmptyState.parentNode) {
                    existingEmptyState.remove();
                }
            }, 300);
        }
        
        // Show empty state with smooth animation
        setTimeout(() => {
            const emptyState = document.createElement('div');
            emptyState.className = 'empty-state';
            emptyState.style.opacity = '0';
            emptyState.style.transform = 'translateY(20px)';
            emptyState.style.transition = 'all 0.5s ease';
            emptyState.innerHTML = `
                <i class="fas fa-map-marker-alt"></i>
                <h3>No addresses saved</h3>
                <p>Add your first address to make checkout easier.</p>
                <button class="primary-button" onclick="openAddressModal()">
                    <i class="fas fa-plus"></i> Add Address
                </button>
            `;
            addressGrid.appendChild(emptyState);
            
            // Animate in
            setTimeout(() => {
                emptyState.style.opacity = '1';
                emptyState.style.transform = 'translateY(0)';
            }, 100);
        }, 350);
    } else {
        // Remove empty state if addresses exist
        const existingEmptyState = addressGrid.querySelector('.empty-state');
        if (existingEmptyState) {
            existingEmptyState.style.transition = 'all 0.3s ease';
            existingEmptyState.style.opacity = '0';
            existingEmptyState.style.transform = 'scale(0.8)';
            setTimeout(() => {
                if (existingEmptyState.parentNode) {
                    existingEmptyState.remove();
                }
            }, 300);
        }
    }
}

// DEDICATED ADDRESS EVENT LISTENERS
function initializeAddressEventListeners() {
    console.log('Initializing address event listeners...');
    
    // Use event delegation for dynamic content
    document.addEventListener('click', function(e) {
        // Delete buttons
        if (e.target.closest('.address-panel .action-icon.delete-icon')) {
            e.preventDefault();
            const addressId = e.target.closest('.address-panel').getAttribute('data-address-id');
            removeAddress(addressId);
        }
        
        // Set as default buttons
        if (e.target.closest('.address-panel .secondary-button')) {
            e.preventDefault();
            const addressPanel = e.target.closest('.address-panel');
            const addressId = addressPanel.getAttribute('data-address-id');
            const header = addressPanel.querySelector('h4');
            const type = header.textContent.includes('Shipping') ? 'shipping' : 'billing';
            makeDefaultAddress(addressId, type);
        }
        
        // Edit buttons
        if (e.target.closest('.address-panel .action-icon:not(.delete-icon)')) {
            e.preventDefault();
            const addressId = e.target.closest('.address-panel').getAttribute('data-address-id');
            editAddress(addressId);
        }
    });
}

function editAddress(addressId) {
    needSecurityCheck(() => {
        showMessage('Edit address functionality would be implemented here. Address ID: ' + addressId, 'info');
    });
}

// Wishlist Functions - SMOOTH VERSION
function deleteWishlistItem(wishlistId) {
    if (confirm('Remove this item from your wishlist?')) {
        const wishlistItem = document.querySelector(`.wishlist-item[data-wishlist-id="${wishlistId}"]`);
        if (wishlistItem) {
            wishlistItem.style.opacity = '0.5';
            wishlistItem.style.pointerEvents = 'none';
        }
        
        console.log('Deleting wishlist item:', wishlistId);
        
        fetch('auth/remove_wishlist.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'wishlist_id=' + wishlistId
        })
        .then(response => {
            console.log('Wishlist delete response status:', response.status);
            return response.json().catch(err => {
                console.error('JSON parse error:', err);
                return { success: false, message: 'Invalid response from server' };
            });
        })
        .then(data => {
            console.log('Wishlist delete API response:', data);
            if (data.success) {
                showMessage('Item removed from wishlist successfully!', 'success');
                
                // SMOOTH REMOVAL WITH IMMEDIATE UI UPDATES
                if (wishlistItem) {
                    wishlistItem.style.transition = 'all 0.3s ease';
                    wishlistItem.style.opacity = '0';
                    wishlistItem.style.transform = 'scale(0.8)';
                    
                    setTimeout(() => {
                        wishlistItem.remove();
                        // Update wishlist count immediately
                        updateWishlistCount();
                        checkEmptyWishlistState();
                    }, 300);
                } else {
                    // If item not found, update counts anyway
                    updateWishlistCount();
                    checkEmptyWishlistState();
                }
            } else {
                showMessage('Error: ' + data.message, 'error');
                // Restore item if error
                if (wishlistItem) {
                    wishlistItem.style.opacity = '1';
                    wishlistItem.style.pointerEvents = 'auto';
                    wishlistItem.style.transform = 'scale(1)';
                }
            }
        })
        .catch(error => {
            console.error('Wishlist delete fetch error:', error);
            showMessage('Error removing item. Please try again.', 'error');
            // Restore item if error
            if (wishlistItem) {
                wishlistItem.style.opacity = '1';
                wishlistItem.style.pointerEvents = 'auto';
                wishlistItem.style.transform = 'scale(1)';
            }
        });
    }
}

// UPDATED: Function to update wishlist count - IMMEDIATE
function updateWishlistCount() {
    const wishlistCount = document.querySelectorAll('.wishlist-item').length;
    const wishlistMenuItem = document.querySelector('.menu-item[data-section="wishlist"]');
    
    if (wishlistMenuItem) {
        let wishlistBadge = wishlistMenuItem.querySelector('.item-count');
        
        if (wishlistCount > 0) {
            if (!wishlistBadge) {
                // Create badge if it doesn't exist
                wishlistBadge = document.createElement('span');
                wishlistBadge.className = 'item-count';
                wishlistBadge.style.transition = 'all 0.3s ease';
                wishlistMenuItem.appendChild(wishlistBadge);
            }
            wishlistBadge.textContent = wishlistCount;
        } else {
            // Remove badge if no wishlist items
            if (wishlistBadge) {
                wishlistBadge.style.opacity = '0';
                wishlistBadge.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    if (wishlistBadge.parentNode) {
                        wishlistBadge.remove();
                    }
                }, 300);
            }
        }
    }
    
    console.log('Wishlist count updated to:', wishlistCount);
}

// UPDATED: Function to check and show empty state if needed
function checkEmptyWishlistState() {
    const wishlistGrid = document.querySelector('.wishlist-grid');
    const wishlistItems = document.querySelectorAll('.wishlist-item');
    
    if (wishlistItems.length === 0) {
        // Remove any existing empty state first
        const existingEmptyState = wishlistGrid.querySelector('.empty-state');
        if (existingEmptyState) {
            existingEmptyState.style.transition = 'all 0.3s ease';
            existingEmptyState.style.opacity = '0';
            existingEmptyState.style.transform = 'scale(0.8)';
            setTimeout(() => {
                if (existingEmptyState.parentNode) {
                    existingEmptyState.remove();
                }
            }, 300);
        }
        
        // Show empty state with smooth animation
        setTimeout(() => {
            const emptyState = document.createElement('div');
            emptyState.className = 'empty-state';
            emptyState.style.opacity = '0';
            emptyState.style.transform = 'translateY(20px)';
            emptyState.style.transition = 'all 0.5s ease';
            emptyState.innerHTML = `
                <i class="fas fa-heart"></i>
                <h3>Your wishlist is empty</h3>
                <p>Start adding items you love to your wishlist.</p>
                <a href="pages/new.php" class="primary-button">
                    <i class="fas fa-shopping-bag"></i> Start Shopping
                </a>
            `;
            wishlistGrid.appendChild(emptyState);
            
            // Animate in
            setTimeout(() => {
                emptyState.style.opacity = '1';
                emptyState.style.transform = 'translateY(0)';
            }, 100);
        }, 350);
    } else {
        // Remove empty state if wishlist items exist
        const existingEmptyState = wishlistGrid.querySelector('.empty-state');
        if (existingEmptyState) {
            existingEmptyState.style.transition = 'all 0.3s ease';
            existingEmptyState.style.opacity = '0';
            existingEmptyState.style.transform = 'scale(0.8)';
            setTimeout(() => {
                if (existingEmptyState.parentNode) {
                    existingEmptyState.remove();
                }
            }, 300);
        }
    }
}

// OPTIMIZED EVENT LISTENERS
function initializeAllEventListeners() {
    console.log('Initializing all event listeners...');
    
    // Menu items
    document.querySelectorAll('.menu-item[data-section]').forEach(item => {
        item.onclick = function() {
            const sectionName = this.getAttribute('data-section');
            showSection(sectionName);
        };
    });
    
    // Initialize address event listeners
    initializeAddressEventListeners();
    
    // Initialize wishlist event listeners
    initializeWishlistEventListeners();
    
    // Modal handlers
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.onclick = function(e) {
            if (e.target === this) {
                this.classList.remove('show');
            }
        };
    });
    
    document.querySelectorAll('.close-button').forEach(button => {
        button.onclick = function() {
            this.closest('.modal-overlay').classList.remove('show');
        };
    });
    
    console.log('All event listeners initialized!');
}

// DEDICATED WISHLIST EVENT LISTENERS
function initializeWishlistEventListeners() {
    console.log('Initializing wishlist event listeners...');
    
    // Use event delegation for dynamic content
    document.addEventListener('click', function(e) {
        // Wishlist delete buttons
        if (e.target.closest('.wishlist-item .remove-item')) {
            e.preventDefault();
            const wishlistId = e.target.closest('.wishlist-item').getAttribute('data-wishlist-id');
            deleteWishlistItem(wishlistId);
        }
        
        // Add to cart buttons in wishlist
        if (e.target.closest('.wishlist-item .cart-button')) {
            e.preventDefault();
            const productId = e.target.closest('.wishlist-item').getAttribute('data-product-id') || 
                             e.target.closest('.cart-button').getAttribute('onclick')?.match(/\d+/)?.[0];
            if (productId) {
                addToCart(productId);
            }
        }
    });
}

function addToCart(productId) {
    alert('This would add product ' + productId + ' to cart');
}

// Order Functions
function viewOrder(orderId) {
    alert('Viewing details for order ' + orderId);
}

function reorderItems(orderId) {
    if (confirm('Add all items from this order to your cart?')) {
        fetch('../auth/reorder.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'order_id=' + orderId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Items added to cart successfully!');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding items to cart');
        });
    }
}

// UI Update Functions
function showMessage(message, type) {
    const existingMessages = document.querySelectorAll('.flash-message');
    existingMessages.forEach(msg => msg.remove());
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `flash-message ${type}`;
    messageDiv.textContent = message;
    messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    
    if (type === 'success') {
        messageDiv.style.background = '#28a745';
    } else if (type === 'error') {
        messageDiv.style.background = '#dc3545';
    } else {
        messageDiv.style.background = '#17a2b8';
    }
    
    document.body.appendChild(messageDiv);
    
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.remove();
        }
    }, 3000);
}

// Modal Functions
function openEditModal() {
    document.getElementById('editModal').classList.add('show');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
}

function openAddressModal() {
    document.getElementById('addressModal').classList.add('show');
}

function closeAddressModal() {
    document.getElementById('addressModal').classList.remove('show');
}

function openEditAddressModal() {
    document.getElementById('editAddressModal').classList.add('show');
}

function closeEditAddressModal() {
    document.getElementById('editAddressModal').classList.remove('show');
    document.getElementById('editAddressForm').reset();
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    initializeAllEventListeners();
    
    document.querySelectorAll('.menu-item[data-section]').forEach(item => {
        item.addEventListener('click', function() {
            const sectionName = this.getAttribute('data-section');
            showSection(sectionName);
        });
    });
    
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('show');
            }
        });
    });
    
    setInterval(() => {
        securityActive = false;
    }, 900000);
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEditModal();
            closeAddressModal();
            closeEditAddressModal();
            closeSecurityModal();
        }
    });

    
});


// Add CSS for animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .address-panel, .wishlist-item {
        transition: all 0.3s ease;
    }
    
    .empty-state {
        transition: all 0.5s ease;
    }
    
    .item-count {
        transition: all 0.3s ease;
    }
    
    .default-address {
        border: 2px solid #28a745 !important;
        background: linear-gradient(135deg, #f8fff8 0%, #e8f5e8 100%) !important;
        transition: all 0.3s ease !important;
    }
    
    .address-grid, .wishlist-grid {
        transition: all 0.3s ease !important;
    }
    
    /* Smooth loading states */
    .loading-opacity {
        opacity: 0.7 !important;
        pointer-events: none !important;
        transition: opacity 0.3s ease !important;
    }
`;
document.head.appendChild(style);