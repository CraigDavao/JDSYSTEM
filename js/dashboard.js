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

// In your JavaScript section, replace the address modal functions with:
function openAddressModal() {
    document.getElementById('addressModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Reset form when opening
    document.getElementById('addressForm').reset();
}

function closeAddressModal() {
    document.getElementById('addressModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Remove any complex address saving JavaScript and let the form submit traditionally

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    // Address modal
    const addressModal = document.getElementById('addressModal');
    if (addressModal) {
        addressModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddressModal();
            }
        });
    }
    
    // Other modals (keep your existing code)
    document.getElementById('editModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });
    
    document.getElementById('securityModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeSecurityModal();
    });
    
    document.getElementById('orderDetailsModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeOrderDetailsModal();
    });
});

// Enhanced Address Management
function loadAddressesForModal() {
    console.log('🔄 Loading addresses for modal...');
    
    const addressesContainer = document.getElementById('addressesContainerModal');
    if (addressesContainer) {
        addressesContainer.innerHTML = '<div class="loading-message">Loading addresses...</div>';
    }
    
    // Simple fetch without complex error handling
    fetch('../actions/get-addresses.php')
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            console.log(`✅ Found ${data.addresses.length} addresses`);
            renderAddressListForModal(data.addresses);
        } else {
            console.error('Error loading addresses:', data.message);
            if (addressesContainer) {
                addressesContainer.innerHTML = '<div class="error-message">Failed to load addresses</div>';
            }
        }
    })
    .catch(error => {
        console.error('💥 Fetch error:', error);
        // Fallback: Use the addresses already displayed on the page
        useExistingAddressesFallback();
    });
}

// Fallback function to use existing addresses
function useExistingAddressesFallback() {
    const addressesContainer = document.getElementById('addressesContainerModal');
    if (!addressesContainer) return;
    
    // Copy addresses from the main addresses section
    const mainAddressesContainer = document.getElementById('addressesContainer');
    if (mainAddressesContainer) {
        addressesContainer.innerHTML = mainAddressesContainer.innerHTML;
    } else {
        addressesContainer.innerHTML = '<div class="error-message">Unable to load addresses</div>';
    }
}

// Render address list in modal
function renderAddressListForModal(addresses) {
    const addressesContainer = document.getElementById('addressesContainerModal');
    if (!addressesContainer) return;
    
    console.log('🎨 Rendering addresses in modal');
    
    if (!addresses || addresses.length === 0) {
        addressesContainer.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-map-marker-alt"></i>
                <h3>No addresses saved</h3>
                <p>Add your first address to make checkout easier.</p>
            </div>
        `;
        return;
    }

    addressesContainer.innerHTML = addresses.map((address) => {
        const addressId = address.id;
        const displayName = address.fullname || 'No Name Specified';
        const isDefault = address.is_default === 1;
        
        return `
            <div class="address-panel ${isDefault ? 'default-address' : ''}" 
                 data-address-id="${addressId}"
                 data-address-type="${address.type}">
                <div class="address-header">
                    <h4>
                        ${address.type.charAt(0).toUpperCase() + address.type.slice(1)} Address 
                        ${isDefault ? '<span class="default-tag"><i class="fas fa-star"></i> Default</span>' : ''}
                    </h4>
                    <div class="address-actions">
                        <button class="action-icon" onclick="editAddress(${addressId})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-icon delete-icon" onclick="removeAddress(${addressId})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="address-info">
                    <p><strong>${escapeHtml(displayName)}</strong></p>
                    <p>${escapeHtml(address.street)}</p>
                    <p>${escapeHtml(address.city + ', ' + address.state + ' ' + address.zip_code)}</p>
                    <p>${escapeHtml(address.country)}</p>
                </div>
                ${!isDefault ? 
                    `<button class="secondary-button set-default-btn" 
                            onclick="setDefaultAddress(${addressId}, '${address.type}')">
                        <i class="fas fa-star"></i> Set as Default
                    </button>` : 
                    `<div class="default-indicator">
                        <i class="fas fa-check-circle"></i> Default Address
                    </div>`
                }
            </div>
        `;
    }).join('');
}

// Enhanced address functions
function editAddress(addressId) {
    // Fetch address details and populate form for editing
    fetch('../actions/get-address.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ address_id: parseInt(addressId) })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const address = data.address;
            // Populate form with existing data
            document.getElementById('newFullname').value = address.fullname || '';
            document.getElementById('newType').value = address.type || 'shipping';
            document.getElementById('newStreet').value = address.street || '';
            document.getElementById('newCity').value = address.city || '';
            document.getElementById('newState').value = address.state || '';
            document.getElementById('newZip').value = address.zip_code || '';
            document.getElementById('newCountry').value = address.country || 'Philippines';
            document.getElementById('setAsDefault').checked = address.is_default === 1;
            
            // Show form and set edit mode
            document.getElementById('addAddressForm').style.display = 'block';
            document.getElementById('saveAddressBtn').setAttribute('data-edit-id', addressId);
            document.getElementById('saveAddressBtn').textContent = 'Update Address';
            
            // Scroll to form
            document.getElementById('addAddressForm').scrollIntoView({ behavior: 'smooth' });
        } else {
            console.error('Error loading address details:', data.message);
        }
    })
    .catch(error => {
        console.error('Error loading address:', error);
    });
}

function removeAddress(addressId) {
    if (confirm('Are you sure you want to remove this address?')) {
        const formData = new FormData();
        formData.append('remove_address', '1');
        formData.append('address_id', addressId);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Address removed successfully', 'success');
                // Reload both modal and main addresses
                loadAddressesForModal();
                // Also reload the main addresses section
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showMessage('Error removing address: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error removing address', 'error');
        });
    }
}

// Save new address
function saveNewAddress() {
    // Get form values
    const fullname = document.getElementById('newFullname').value.trim();
    const type = document.getElementById('newType').value;
    const street = document.getElementById('newStreet').value.trim();
    const city = document.getElementById('newCity').value.trim();
    const state = document.getElementById('newState').value.trim();
    const zip = document.getElementById('newZip').value.trim();
    const country = document.getElementById('newCountry').value.trim();
    const setAsDefault = document.getElementById('setAsDefault').checked;

    // Validation
    if (!fullname || !street || !city || !state || !zip) {
        showMessage('Please fill in all required fields', 'error');
        return;
    }

    // Create a form and submit it (traditional form submission)
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';

    // Add address fields
    const fullnameInput = document.createElement('input');
    fullnameInput.type = 'hidden';
    fullnameInput.name = 'fullname';
    fullnameInput.value = fullname;
    form.appendChild(fullnameInput);

    const typeInput = document.createElement('input');
    typeInput.type = 'hidden';
    typeInput.name = 'type';
    typeInput.value = type;
    form.appendChild(typeInput);

    const streetInput = document.createElement('input');
    streetInput.type = 'hidden';
    streetInput.name = 'street';
    streetInput.value = street;
    form.appendChild(streetInput);

    const cityInput = document.createElement('input');
    cityInput.type = 'hidden';
    cityInput.name = 'city';
    cityInput.value = city;
    form.appendChild(cityInput);

    const stateInput = document.createElement('input');
    stateInput.type = 'hidden';
    stateInput.name = 'state';
    stateInput.value = state;
    form.appendChild(stateInput);

    const zipInput = document.createElement('input');
    zipInput.type = 'hidden';
    zipInput.name = 'zip_code';
    zipInput.value = zip;
    form.appendChild(zipInput);

    const countryInput = document.createElement('input');
    countryInput.type = 'hidden';
    countryInput.name = 'country';
    countryInput.value = country;
    form.appendChild(countryInput);

    const defaultInput = document.createElement('input');
    defaultInput.type = 'hidden';
    defaultInput.name = 'is_default';
    defaultInput.value = setAsDefault ? '1' : '0';
    form.appendChild(defaultInput);

    // Add the action
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'add_address';
    actionInput.value = '1';
    form.appendChild(actionInput);

    document.body.appendChild(form);
    form.submit();
}

// Save or update address
function saveOrUpdateAddress() {
    // Get form values
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

    // Prepare address data
    const addressData = {
        fullname: fullname,
        type: type,
        street: street,
        city: city,
        state: state,
        zip_code: zip,
        country: country,
        set_as_default: setAsDefault ? 1 : 0
    };

    // Add edit ID if in edit mode
    if (editId) {
        addressData.address_id = parseInt(editId);
    }

    // Show loading state
    const saveButton = document.getElementById('saveAddressBtn');
    const originalText = saveButton.textContent;
    saveButton.textContent = 'Saving...';
    saveButton.disabled = true;

    const formData = new FormData();
    formData.append(editId ? 'update_address' : 'add_address', '1');
    formData.append('address_data', JSON.stringify(addressData));

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Reset button state
        saveButton.textContent = originalText;
        saveButton.disabled = false;
        
        if (data.success) {
            showMessage(editId ? '✅ Address updated successfully!' : '✅ Address saved successfully!', 'success');
            
            // Reset form function
function resetAddressForm() {
    document.getElementById('newFullname').value = '';
    document.getElementById('newType').value = 'shipping';
    document.getElementById('newStreet').value = '';
    document.getElementById('newCity').value = '';
    document.getElementById('newState').value = '';
    document.getElementById('newZip').value = '';
    document.getElementById('newCountry').value = 'Philippines';
    document.getElementById('setAsDefault').checked = false;
}
            // Reload addresses
            loadAddressesForModal();
            
            // Close modal after successful save
            setTimeout(() => {
                closeAddressModal();
                location.reload(); // Reload to update main addresses display
            }, 1500);
        } else {
            showMessage('Error saving address: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('💥 Error saving address:', error);
        saveButton.textContent = originalText;
        saveButton.disabled = false;
        showMessage('Network error', 'error');
    });
}

// Reset form function
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
}

// Close modals when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    // Edit modal
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });

    // Address modal
    document.getElementById('addressModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeAddressModal();
        }
    });

    // Security modal
    document.getElementById('securityModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeSecurityModal();
        }
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

    // Initialize address modal
    initAddressModal();
});

// Wishlist management - UPDATED VERSION
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
        .then(response => response.json())
        .then(data => {
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
                showMessage('Error removing item: ' + data.message, 'error');
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

function addToCart(productId) {
    const formData = new FormData();
    formData.append('add_to_cart', '1');
    formData.append('product_id', productId);
    formData.append('quantity', 1);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('Item added to cart!', 'success');
        } else {
            showMessage('Error adding to cart: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error adding item to cart', 'error');
    });
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

// Make wishlist items clickable (backup in case CSS fails)
document.addEventListener('DOMContentLoaded', function() {
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

// Security verification
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
    .then(response => response.json())
    .then(data => {
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
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error verifying password');
    });
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

// Set default address with AJAX
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
    .then(response => response.json())
    .then(data => {
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

// Close order details modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const orderModal = document.getElementById('orderDetailsModal');
    if (orderModal) {
        orderModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeOrderDetailsModal();
            }
        });
    }
});