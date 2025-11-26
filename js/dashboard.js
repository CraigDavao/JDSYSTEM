// Dashboard JavaScript functionality

// Section navigation
function showSection(sectionName) {
    // Hide all sections
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Remove active class from all menu items
    document.querySelectorAll('.menu-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Show selected section
    document.getElementById(sectionName).classList.add('active');
    
    // Activate corresponding menu item
    document.querySelector(`[data-section="${sectionName}"]`).classList.add('active');
}

// Modal functions
function openEditModal() {
    document.getElementById('editModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function openAddressModal() {
    document.getElementById('addressModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeAddressModal() {
    document.getElementById('addressModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    resetAddressForm();
}

function openSecurityModal() {
    document.getElementById('securityModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeSecurityModal() {
    document.getElementById('securityModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('security_password').value = '';
}

// Function to reset form to "add new" mode
function resetAddressForm() {
    document.getElementById('newFullname').value = '';
    document.getElementById('newType').value = 'shipping';
    document.getElementById('newStreet').value = '';
    document.getElementById('newCity').value = '';
    document.getElementById('newState').value = '';
    document.getElementById('newZip').value = '';
    document.getElementById('newCountry').value = 'Philippines';
    document.getElementById('setAsDefault').checked = false;
    
    // Reset edit mode
    const saveButton = document.getElementById('saveAddressBtn');
    saveButton.removeAttribute('data-edit-id');
    saveButton.textContent = 'Save Address';
    
    // Reset modal title
    document.querySelector('#addressModal .modal-header h3').textContent = 'Add New Address';
    document.querySelector('#addressModal h4').textContent = 'Add New Shipping Address';
}

// Edit address function - UPDATED TO STAY ON PAGE
function editAddress(addressId) {
    console.log('Editing address ID:', addressId);
    
    const saveButton = document.getElementById('saveAddressBtn');
    const originalText = saveButton.textContent;
    saveButton.textContent = 'Loading...';
    saveButton.disabled = true;

    const formData = new FormData();
    formData.append('address_id', addressId);

    fetch('actions/get-address.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Handle both response formats: 'success' for dashboard and 'status' for checkout
        const isSuccess = (data.success === true) || (data.status === 'success');
        
        if (isSuccess) {
            const address = data.address;
            
            // Populate form with existing data
            document.getElementById('newFullname').value = address.fullname || '';
            document.getElementById('newType').value = address.type || 'shipping';
            document.getElementById('newStreet').value = address.street || '';
            document.getElementById('newCity').value = address.city || '';
            document.getElementById('newState').value = address.state || '';
            document.getElementById('newZip').value = address.zip_code || '';
            document.getElementById('newCountry').value = address.country || 'Philippines';
            document.getElementById('setAsDefault').checked = address.is_default == 1;
            
            // Set edit mode
            saveButton.setAttribute('data-edit-id', addressId);
            saveButton.textContent = 'Update Address';
            
            // Update modal title
            document.querySelector('#addressModal .modal-header h3').textContent = 'Edit Address';
            document.querySelector('#addressModal h4').textContent = 'Edit Shipping Address';
            
            // Open the modal
            openAddressModal();
            
        } else {
            showMessage('Error: ' + (data.message || 'Failed to load address'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Network error: ' + error.message, 'error');
    })
    .finally(() => {
        saveButton.textContent = originalText;
        saveButton.disabled = false;
    });
}

// Save or update address - UPDATED TO STAY ON PAGE
function saveOrUpdateAddress() {
    const fullname = document.getElementById('newFullname').value.trim();
    const type = document.getElementById('newType').value;
    const street = document.getElementById('newStreet').value.trim();
    const city = document.getElementById('newCity').value.trim();
    const state = document.getElementById('newState').value.trim();
    const zip = document.getElementById('newZip').value.trim();
    const country = document.getElementById('newCountry').value.trim();
    const setAsDefault = document.getElementById('setAsDefault').checked;
    const editId = document.getElementById('saveAddressBtn').getAttribute('data-edit-id');

    // Validation
    if (!fullname || !street || !city || !state || !zip) {
        showMessage('Please fill in all required fields', 'error');
        return;
    }

    // Prepare form data
    const formData = new FormData();
    
    if (editId) {
        // UPDATE existing address
        formData.append('update_address', '1');
        formData.append('address_id', editId);
        formData.append('fullname', fullname);
        formData.append('type', type);
        formData.append('street', street);
        formData.append('city', city);
        formData.append('state', state);
        formData.append('zip_code', zip);
        formData.append('country', country);
        formData.append('is_default', setAsDefault ? '1' : '0');
    } else {
        // ADD new address
        formData.append('add_address', '1');
        formData.append('fullname', fullname);
        formData.append('type', type);
        formData.append('street', street);
        formData.append('city', city);
        formData.append('state', state);
        formData.append('zip_code', zip);
        formData.append('country', country);
        formData.append('is_default', setAsDefault ? '1' : '0');
    }

    // Show loading state
    const saveButton = document.getElementById('saveAddressBtn');
    const originalText = saveButton.textContent;
    saveButton.textContent = 'Saving...';
    saveButton.disabled = true;

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                showMessage(data.message || 'Address saved successfully!', 'success');
                closeAddressModal();
                
                // Refresh addresses without page reload
                refreshAddresses();
                
            } else {
                showMessage('Error: ' + (data.message || 'Failed to save address'), 'error');
            }
        } catch (e) {
            // If it's not JSON, assume success and refresh
            showMessage('Address saved successfully!', 'success');
            closeAddressModal();
            refreshAddresses();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error saving address: ' + error.message, 'error');
    })
    .finally(() => {
        saveButton.textContent = originalText;
        saveButton.disabled = false;
    });
}

// Remove address function - UPDATED TO STAY ON PAGE
function removeAddress(addressId) {
    if (confirm('Are you sure you want to remove this address?')) {
        const formData = new FormData();
        formData.append('remove_address', '1');
        formData.append('address_id', addressId);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    showMessage('Address removed successfully', 'success');
                    
                    // Remove address from DOM immediately
                    const addressElement = document.querySelector(`[data-address-id="${addressId}"]`);
                    if (addressElement) {
                        addressElement.remove();
                    }
                    
                    // Refresh addresses display
                    refreshAddresses();
                    
                } else {
                    showMessage('Error removing address: ' + data.message, 'error');
                }
            } catch (e) {
                // If it's not JSON, assume success and refresh
                showMessage('Address removed successfully', 'success');
                
                // Remove address from DOM immediately
                const addressElement = document.querySelector(`[data-address-id="${addressId}"]`);
                if (addressElement) {
                    addressElement.remove();
                }
                
                refreshAddresses();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error removing address', 'error');
        });
    }
}

// Set default address with AJAX - UPDATED TO STAY ON PAGE
function setDefaultAddress(addressId, addressType) {
    if (!confirm(`Set this as your default ${addressType} address?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('ajax_set_default_address', '1');
    formData.append('address_id', addressId);
    formData.append('address_type', addressType);

    // Show loading state
    const buttons = document.querySelectorAll('.set-default-btn');
    buttons.forEach(btn => btn.disabled = true);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                // Update addresses display
                updateAddressesDisplay(data.addresses);
                // Update overview display
                updateOverviewDisplay(data.default_shipping, data.default_billing);
                // Show success message
                showMessage(data.message, 'success');
            } else {
                showMessage(data.message, 'error');
            }
        } catch (e) {
            // If it's not JSON, just refresh everything
            showMessage('Default address updated successfully!', 'success');
            refreshAddresses();
        }
    })
    .catch(error => {
        console.error('Error setting default address:', error);
        showMessage('Error setting default address. Please try again.', 'error');
    })
    .finally(() => {
        // Re-enable buttons
        buttons.forEach(btn => btn.disabled = false);
    });
}

// NEW FUNCTION: Refresh addresses without page reload
function refreshAddresses() {
    fetch(window.location.href + '?refresh_addresses=1')
        .then(response => response.text())
        .then(html => {
            // Extract the addresses section from the response
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            const newAddressesContainer = tempDiv.querySelector('#addressesContainer');
            const currentAddressesContainer = document.getElementById('addressesContainer');
            
            if (newAddressesContainer && currentAddressesContainer) {
                currentAddressesContainer.innerHTML = newAddressesContainer.innerHTML;
            }
            
            // Also update the overview section if needed
            const newShippingDisplay = tempDiv.querySelector('#defaultShippingDisplay');
            const newBillingDisplay = tempDiv.querySelector('#defaultBillingDisplay');
            const currentShippingDisplay = document.getElementById('defaultShippingDisplay');
            const currentBillingDisplay = document.getElementById('defaultBillingDisplay');
            
            if (newShippingDisplay && currentShippingDisplay) {
                currentShippingDisplay.innerHTML = newShippingDisplay.innerHTML;
            }
            if (newBillingDisplay && currentBillingDisplay) {
                currentBillingDisplay.innerHTML = newBillingDisplay.innerHTML;
            }
        })
        .catch(error => {
            console.error('Error refreshing addresses:', error);
        });
}

// Update your dashboard-handlers.php to handle the refresh request

// Update addresses display
function updateAddressesDisplay(addresses) {
    const addressesContainer = document.getElementById('addressesContainer');
    
    if (addresses.length === 0) {
        addressesContainer.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-map-marker-alt"></i>
                <h3>No addresses saved</h3>
                <p>Add your first address to make checkout easier.</p>
                <button class="primary-button" onclick="openAddressModal()">
                    <i class="fas fa-plus"></i> Add Address
                </button>
            </div>
        `;
        return;
    }

    let addressesHTML = '';
    addresses.forEach(address => {
        addressesHTML += `
            <div class="address-panel ${address.is_default ? 'default-address' : ''}" 
                 data-address-id="${address.id}"
                 data-address-type="${address.type}">
                <div class="address-header">
                    <h4>
                        ${address.type.charAt(0).toUpperCase() + address.type.slice(1)} Address 
                        ${address.is_default ? '<span class="default-tag"><i class="fas fa-star"></i> Default</span>' : ''}
                    </h4>
                    <div class="address-actions">
                        <button class="action-icon" onclick="editAddress(${address.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-icon delete-icon" onclick="removeAddress(${address.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="address-info">
                    <p><strong>${escapeHtml(address.fullname || 'User')}</strong></p>
                    <p>${escapeHtml(address.street)}</p>
                    <p>${escapeHtml(address.city + ', ' + address.state + ' ' + address.zip_code)}</p>
                    <p>Philippines</p>
                </div>
                ${!address.is_default ? 
                    `<button class="secondary-button set-default-btn" 
                            onclick="setDefaultAddress(${address.id}, '${address.type}')">
                        <i class="fas fa-star"></i> Set as Default
                    </button>` : 
                    `<div class="default-indicator">
                        <i class="fas fa-check-circle"></i> Default Address
                    </div>`
                }
            </div>
        `;
    });
    
    addressesContainer.innerHTML = addressesHTML;
}

// Update overview display
function updateOverviewDisplay(defaultShipping, defaultBilling) {
    const shippingDisplay = document.getElementById('defaultShippingDisplay');
    const billingDisplay = document.getElementById('defaultBillingDisplay');
    
    // Update shipping address
    if (defaultShipping) {
        shippingDisplay.innerHTML = `
            <div class="address-display">
                <strong>${escapeHtml(defaultShipping.fullname || 'User')}</strong><br>
                ${escapeHtml(defaultShipping.street)}<br>
                ${escapeHtml(defaultShipping.city + ', ' + defaultShipping.state + ' ' + defaultShipping.zip_code)}<br>
                Philippines
                <a href="#" onclick="showSection('addresses'); return false;" class="change-address-link">Change</a>
            </div>
        `;
    } else {
        shippingDisplay.innerHTML = `
            <span class="not-set">Not set</span>
            <a href="#" onclick="showSection('addresses'); return false;" class="set-address-link">Set shipping address</a>
        `;
    }
    
    // Update billing address
    if (defaultBilling) {
        billingDisplay.innerHTML = `
            <div class="address-display">
                <strong>${escapeHtml(defaultBilling.fullname || 'User')}</strong><br>
                ${escapeHtml(defaultBilling.street)}<br>
                ${escapeHtml(defaultBilling.city + ', ' + defaultBilling.state + ' ' + defaultBilling.zip_code)}<br>
                Philippines
                <a href="#" onclick="showSection('addresses'); return false;" class="change-address-link">Change</a>
            </div>
        `;
    } else {
        billingDisplay.innerHTML = `
            <span class="not-set">Not set</span>
            <a href="#" onclick="showSection('addresses'); return false;" class="set-address-link">Set billing address</a>
        `;
    }
}

// Wishlist management - FIXED VERSION
function deleteWishlistItem(wishlistId) {
    if (confirm('Remove this item from your wishlist?')) {
        const formData = new FormData();
        formData.append('remove_wishlist', '1');
        formData.append('wishlist_id', wishlistId);

        // Get the item element
        const item = document.querySelector(`[data-wishlist-id="${wishlistId}"]`);
        
        if (item) {
            item.classList.add('removing');
        }

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            console.log('Wishlist remove response:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    // Remove item from DOM after animation
                    setTimeout(() => {
                        if (item && item.parentNode) {
                            item.remove();
                        }
                        // Update wishlist count
                        updateWishlistCount();
                        showMessage('Item removed from wishlist', 'success');
                    }, 300);
                } else {
                    if (item) {
                        item.classList.remove('removing');
                    }
                    showMessage('Error removing item: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                // If it's not JSON, assume success and remove from DOM
                setTimeout(() => {
                    if (item && item.parentNode) {
                        item.remove();
                    }
                    updateWishlistCount();
                    showMessage('Item removed from wishlist', 'success');
                }, 300);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (item) {
                item.classList.remove('removing');
            }
            showMessage('Error removing item from wishlist', 'error');
        });
    }
}

function updateWishlistCount() {
    const items = document.querySelectorAll('.wishlist-item');
    const countBadge = document.querySelector('.menu-item[data-section="wishlist"] .item-count');
    const sectionBadge = document.querySelector('#wishlist .count-badge');
    
    const currentCount = items.length;
    
    if (countBadge) {
        countBadge.textContent = currentCount;
        if (currentCount === 0) {
            countBadge.remove();
        }
    }
    
    if (sectionBadge) {
        sectionBadge.textContent = currentCount + ' items';
    }
    
    // Update the main wishlist count if it exists
    const mainWishlistCount = document.getElementById('wishlist-count');
    if (mainWishlistCount) {
        mainWishlistCount.textContent = currentCount;
    }
}

// Add to cart function - HANDLES HTML RESPONSES GRACEFULLY
function addToCart(productId) {
    const formData = new FormData();
    formData.append('add_to_cart', '1');
    formData.append('product_id', productId);
    formData.append('quantity', 1);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                showMessage('Item added to cart!', 'success');
            } else {
                showMessage('Error adding to cart: ' + data.message, 'error');
            }
        } catch (e) {
            // If it's not JSON, assume success
            showMessage('Item added to cart!', 'success');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error adding item to cart', 'error');
    });
}

// Security verification - HANDLES HTML RESPONSES GRACEFULLY
let securityVerified = false;
let pendingAction = null;

function initSecurity(isVerified) {
    securityVerified = isVerified;
}

function requireSecurityVerification(actionCallback) {
    if (securityVerified) {
        actionCallback();
    } else {
        pendingAction = actionCallback;
        openSecurityModal();
    }
}

function verifyPassword() {
    const password = document.getElementById('security_password').value;
    
    if (!password) {
        alert('Please enter your password');
        return;
    }

    const formData = new FormData();
    formData.append('security_verify', '1');
    formData.append('password', password);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                securityVerified = true;
                closeSecurityModal();
                document.getElementById('security_password').value = '';
                
                if (pendingAction) {
                    pendingAction();
                    pendingAction = null;
                }
            } else {
                alert(data.message || 'Incorrect password');
            }
        } catch (e) {
            alert('Error verifying password');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error verifying password');
    });
}

// Order details functionality
function viewOrderDetails(orderId) {
    fetch('includes/order-details.php?order_id=' + orderId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('orderDetailsContent').innerHTML = html;
            document.getElementById('orderDetailsModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        })
        .catch(error => {
            console.error('Error loading order details:', error);
            document.getElementById('orderDetailsContent').innerHTML = '<div class="error-message">Error loading order details.</div>';
        });
}

function closeOrderDetailsModal() {
    document.getElementById('orderDetailsModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Utility functions
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function showMessage(message, type) {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.message');
    existingMessages.forEach(msg => msg.remove());
    
    // Create new message
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.textContent = message;
    
    // Insert after page header
    const pageHeader = document.querySelector('.page-header');
    if (pageHeader && pageHeader.parentNode) {
        pageHeader.parentNode.insertBefore(messageDiv, pageHeader.nextSibling);
    }
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.remove();
        }
    }, 5000);
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Modal click outside handlers
    document.getElementById('editModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });
    
    document.getElementById('addressModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeAddressModal();
    });
    
    document.getElementById('securityModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeSecurityModal();
    });
    
    document.getElementById('orderDetailsModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeOrderDetailsModal();
    });

    // Menu item click handlers
    document.querySelectorAll('.menu-item').forEach(item => {
        if (!item.classList.contains('logout-link')) {
            item.addEventListener('click', function() {
                const section = this.getAttribute('data-section');
                showSection(section);
            });
        }
    });

    // Make wishlist items clickable
    document.addEventListener('click', function(e) {
        const wishlistItem = e.target.closest('.wishlist-item');
        if (wishlistItem && !e.target.closest('.remove-item')) {
            const link = wishlistItem.querySelector('.wishlist-item-link');
            if (link) {
                window.location.href = link.href;
            }
        }
    });
});
// Feedback Modal Functions
function openFeedbackModal(orderId, productId, productName) {
    document.getElementById('feedback_order_id').value = orderId;
    document.getElementById('feedback_product_id').value = productId;
    document.getElementById('feedback_product_name').textContent = productName;
    document.getElementById('feedback_order_number').textContent = orderId; // You might want to get actual order number
    
    // Reset form
    document.getElementById('feedbackForm').reset();
    document.getElementById('rating').value = '';
    document.getElementById('ratingText').textContent = 'Select rating';
    document.getElementById('imagePreview').innerHTML = '';
    
    // Reset stars
    document.querySelectorAll('#ratingStars .star i').forEach(star => {
        star.className = 'far fa-star';
    });
    
    document.getElementById('feedbackModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeFeedbackModal() {
    document.getElementById('feedbackModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Star rating functionality
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('#ratingStars .star');
    const ratingInput = document.getElementById('rating');
    const ratingText = document.getElementById('ratingText');
    
    const ratingLabels = {
        1: 'Poor',
        2: 'Fair',
        3: 'Good',
        4: 'Very Good',
        5: 'Excellent'
    };
    
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.getAttribute('data-rating'));
            ratingInput.value = rating;
            ratingText.textContent = ratingLabels[rating] || 'Select rating';
            
            // Update stars display
            stars.forEach((s, index) => {
                const starIcon = s.querySelector('i');
                if (index < rating) {
                    starIcon.className = 'fas fa-star';
                } else {
                    starIcon.className = 'far fa-star';
                }
            });
        });
        
        star.addEventListener('mouseenter', function() {
            const rating = parseInt(this.getAttribute('data-rating'));
            stars.forEach((s, index) => {
                const starIcon = s.querySelector('i');
                if (index < rating) {
                    starIcon.className = 'fas fa-star';
                } else {
                    starIcon.className = 'far fa-star';
                }
            });
        });
        
        star.addEventListener('mouseleave', function() {
            const currentRating = parseInt(ratingInput.value) || 0;
            stars.forEach((s, index) => {
                const starIcon = s.querySelector('i');
                if (index < currentRating) {
                    starIcon.className = 'fas fa-star';
                } else {
                    starIcon.className = 'far fa-star';
                }
            });
        });
    });
    
    // Image preview for feedback
    document.getElementById('feedback_images').addEventListener('change', function(e) {
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '';
        
        const files = e.target.files;
        const maxFiles = 4;
        
        if (files.length > maxFiles) {
            alert(`You can only upload up to ${maxFiles} images.`);
            this.value = '';
            return;
        }
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imgContainer = document.createElement('div');
                    imgContainer.className = 'preview-image';
                    imgContainer.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="remove-image" onclick="this.parentElement.remove()">×</button>
                    `;
                    preview.appendChild(imgContainer);
                };
                reader.readAsDataURL(file);
            }
        }
    });
    
    // Feedback form submission
    document.getElementById('feedbackForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('submit_feedback', '1');
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        submitBtn.disabled = true;
        
        fetch('includes/feedback-handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                closeFeedbackModal();
                // Refresh the feedback section
                showSection('feedback');
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error submitting feedback. Please try again.', 'error');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
    
    // Close modal when clicking outside
    document.getElementById('feedbackModal').addEventListener('click', function(e) {
        if (e.target === this) closeFeedbackModal();
    });
});

// ORDER CONFIRMATION FUNCTION
function confirmOrderReceived(orderId) {
    if (confirm('Have you received this order and are satisfied with your items?')) {
        const formData = new FormData();
        formData.append('confirm_order_received', '1');
        formData.append('order_id', orderId);

        // Show loading state
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Confirming...';
        button.disabled = true;

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    showMessage(data.message, 'success');
                    // Refresh the page to update the interface
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage(data.message, 'error');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            } catch (e) {
                // If it's not JSON, assume success
                showMessage('Order confirmed as received! You can now leave feedback.', 'success');
                setTimeout(() => location.reload(), 1500);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error confirming order receipt', 'error');
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
}

// IMAGE MODAL FUNCTIONS
function openImageModal(imageSrc) {
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('imageModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Add to your existing DOMContentLoaded event listener
document.addEventListener('DOMContentLoaded', function() {
    // ... your existing code
    
    // Add image modal click outside handler
    document.getElementById('imageModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeImageModal();
    });
});