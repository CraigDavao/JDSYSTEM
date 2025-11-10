// checkout.js - Simple and reliable version
document.addEventListener('DOMContentLoaded', function() {
    console.log('🛒 Checkout page loaded');
    
    // API Endpoints
    const GET_ADDRESS_URL = '../actions/get-address.php';
    const SAVE_ADDRESS_URL = '../actions/save-address.php';
    const GET_ADDRESSES_URL = '../actions/get-addresses.php';
    const PLACE_ORDER_URL = '../actions/place-order.php';
    
    // Elements
    const changeAddressBtn = document.getElementById('change-address-btn');
    const addressModal = document.getElementById('address-modal');
    const closeModal = document.querySelector('.close-modal');
    const addressList = document.getElementById('address-list');
    const addNewAddressBtn = document.getElementById('add-new-address-btn');
    const addAddressForm = document.getElementById('add-address-form');
    const saveAddressBtn = document.getElementById('save-address-btn');
    const cancelAddressBtn = document.getElementById('cancel-address-btn');
    const placeOrderBtn = document.getElementById('place-order-btn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const messageContainer = document.getElementById('message-container');
    const currentAddressDisplay = document.getElementById('current-address');
    const selectedAddressIdInput = document.getElementById('selected-address-id');

    // Storage keys
    const CHECKOUT_SELECTED_ADDRESS = 'checkout_selected_address';
    const CHECKOUT_VISIT_TIMESTAMP = 'checkout_visit_timestamp';

    // Initialize
    initializeAddressSelection();

    // Modal functionality
    if (changeAddressBtn && addressModal) {
        changeAddressBtn.addEventListener('click', function() {
            console.log('📭 Opening address modal');
            addressModal.style.display = 'block';
            loadAddresses();
        });
    }

    if (closeModal && addressModal) {
        closeModal.addEventListener('click', function() {
            addressModal.style.display = 'none';
            resetAddressForm();
        });
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === addressModal) {
            addressModal.style.display = 'none';
            resetAddressForm();
        }
    });

    // Add new address form toggle
    if (addNewAddressBtn && addAddressForm) {
        addNewAddressBtn.addEventListener('click', function() {
            console.log('➕ Add New Address button clicked');
            const isVisible = addAddressForm.style.display !== 'none';
            addAddressForm.style.display = isVisible ? 'none' : 'block';
            
            if (!isVisible) {
                resetAddressForm();
            }
        });
    }

    // Cancel address form
    if (cancelAddressBtn && addAddressForm) {
        cancelAddressBtn.addEventListener('click', function() {
            console.log('❌ Cancel add address');
            addAddressForm.style.display = 'none';
            resetAddressForm();
        });
    }

    // Save new address
    if (saveAddressBtn) {
        saveAddressBtn.addEventListener('click', function() {
            saveNewAddress();
        });
    }

    // Place order button
    if (placeOrderBtn) {
        placeOrderBtn.addEventListener('click', function() {
            placeOrder();
        });
    }

    // Initialize address selection with smart detection
    function initializeAddressSelection() {
        console.log('🔍 Initializing address selection...');
        
        const defaultAddressId = selectedAddressIdInput ? selectedAddressIdInput.value : null;
        const previousTimestamp = sessionStorage.getItem(CHECKOUT_VISIT_TIMESTAMP);
        const currentTimestamp = Date.now();
        
        // Check if we have a saved selection
        const savedSelection = sessionStorage.getItem(CHECKOUT_SELECTED_ADDRESS);
        
        console.log('💾 Saved selection:', savedSelection);
        console.log('🏠 Default address ID:', defaultAddressId);
        console.log('⏰ Previous visit:', previousTimestamp);
        console.log('⏰ Current visit:', currentTimestamp);
        
        // Determine if this is a refresh or new visit
        const isRefresh = previousTimestamp && (currentTimestamp - parseInt(previousTimestamp)) < 2000; // 2 second window
        
        let addressIdToUse;
        
        if (isRefresh && savedSelection) {
            // This is a refresh - use saved selection
            console.log('🔄 Page refresh detected - using saved selection');
            addressIdToUse = savedSelection;
        } else {
            // This is a new visit - use default address
            console.log('🚀 New checkout visit - using default address');
            addressIdToUse = defaultAddressId;
            // Clear any old selection
            sessionStorage.removeItem(CHECKOUT_SELECTED_ADDRESS);
        }
        
        // Save current timestamp for next visit detection
        sessionStorage.setItem(CHECKOUT_VISIT_TIMESTAMP, currentTimestamp.toString());
        
        if (addressIdToUse && addressIdToUse !== '0') {
            console.log('🎯 Using address ID:', addressIdToUse);
            
            if (selectedAddressIdInput) {
                selectedAddressIdInput.value = addressIdToUse;
            }
            
            fetchAddressDetails(addressIdToUse);
        } else {
            console.log('❌ No address available');
            if (currentAddressDisplay) {
                currentAddressDisplay.textContent = 'No address selected. Please add a shipping address.';
            }
        }
    }

    // Save selection
    function saveSelection(addressId) {
        sessionStorage.setItem(CHECKOUT_SELECTED_ADDRESS, addressId);
        console.log('💾 Saved selection:', addressId);
    }

    // Clear selection
    function clearSelection() {
        sessionStorage.removeItem(CHECKOUT_SELECTED_ADDRESS);
        sessionStorage.removeItem(CHECKOUT_VISIT_TIMESTAMP);
        console.log('🗑️ Cleared all checkout data');
    }

    // Fetch address details for display
    function fetchAddressDetails(addressId) {
        console.log('📋 Fetching details for address:', addressId);
        
        if (!addressId || addressId === '0') {
            return;
        }
        
        fetch(GET_ADDRESS_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ address_id: parseInt(addressId) }),
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                console.log('✅ Address details fetched successfully');
                updateSelectedAddressDisplay(data.address);
            } else {
                console.error('❌ Failed to fetch address details');
                clearSelection();
                useDefaultAddress();
            }
        })
        .catch(error => {
            console.error('💥 Error fetching address details:', error);
            clearSelection();
            useDefaultAddress();
        });
    }

    // Fall back to default address
    function useDefaultAddress() {
        const defaultAddressId = selectedAddressIdInput ? selectedAddressIdInput.value : null;
        if (defaultAddressId && defaultAddressId !== '0') {
            console.log('🔄 Falling back to default address');
            if (selectedAddressIdInput) {
                selectedAddressIdInput.value = defaultAddressId;
            }
            fetchAddressDetails(defaultAddressId);
        }
    }

    // Load addresses for modal
    function loadAddresses() {
        console.log('🔄 Loading addresses...');
        
        if (addressList) {
            addressList.innerHTML = '<div class="loading-message">Loading addresses...</div>';
        }
        
        fetch(GET_ADDRESSES_URL, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                console.log(`✅ Found ${data.addresses.length} addresses`);
                renderAddressList(data.addresses);
            } else {
                showMessage('Error loading addresses', 'error');
                if (addressList) {
                    addressList.innerHTML = '<div class="error-message">Failed to load addresses</div>';
                }
            }
        })
        .catch(error => {
            console.error('💥 Fetch error:', error);
            showMessage('Error loading addresses', 'error');
            if (addressList) {
                addressList.innerHTML = '<div class="error-message">Error loading addresses</div>';
            }
        });
    }

    // Render address list in modal
    function renderAddressList(addresses) {
        if (!addressList) return;
        
        console.log('🎨 Rendering addresses');
        
        if (!addresses || addresses.length === 0) {
            addressList.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #666;">
                    <p>No addresses found.</p>
                    <p>Click "Add New Address" to create your first shipping address.</p>
                </div>
            `;
            return;
        }

        const savedSelection = sessionStorage.getItem(CHECKOUT_SELECTED_ADDRESS);
        const defaultAddressId = selectedAddressIdInput ? selectedAddressIdInput.value : null;

        addressList.innerHTML = addresses.map((address) => {
            const addressId = address.id;
            const displayName = address.fullname || 'No Name Specified';
            
            // Check if this address is currently selected
            const isSavedSelection = savedSelection && addressId.toString() === savedSelection;
            const isDefault = address.is_default === 1;
            const isCurrentlySelected = isSavedSelection || (!savedSelection && isDefault);
            
            return `
                <div class="address-item ${isCurrentlySelected ? 'selected' : ''}" data-id="${addressId}">
                    <div class="address-details">
                        <p style="font-weight: 600; color: #2c3e50; margin-bottom: 8px;">${displayName}</p>
                        <p style="color: #555; line-height: 1.5; margin-bottom: 8px;">
                            ${address.street}, ${address.city}, ${address.state}, ${address.zip_code}, ${address.country}
                        </p>
                        <div class="address-meta">
                            ${isDefault ? 
                                '<span class="address-badge badge-default">⭐ Default Address</span>' : 
                                '<span class="address-badge badge-other">📍 Additional Address</span>'
                            }
                            ${isSavedSelection ? 
                                '<span class="address-badge badge-selected">✅ Selected</span>' : 
                                ''
                            }
                        </div>
                    </div>
                    <button class="select-address-btn ${isCurrentlySelected ? 'selected' : ''}" data-id="${addressId}">
                        ${isCurrentlySelected ? 'Selected' : 'Select'}
                    </button>
                </div>
            `;
        }).join('');

        // Add event listeners to select buttons
        document.querySelectorAll('.select-address-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const addressId = this.getAttribute('data-id');
                console.log('🔘 Address selection clicked:', addressId);
                
                if (!addressId || addressId === '0') {
                    showMessage('Invalid address selection', 'error');
                    return;
                }
                
                selectAddress(addressId);
            });
        });
    }

    // Select address function
    function selectAddress(addressId) {
        console.log('🎯 Selecting address:', addressId);
        
        if (!addressId || addressId === '0') {
            showMessage('Invalid address ID', 'error');
            return;
        }
        
        // Save selection
        saveSelection(addressId);
        
        // Show loading state
        const selectedButton = document.querySelector(`.select-address-btn[data-id="${addressId}"]`);
        if (selectedButton) {
            selectedButton.textContent = 'Selecting...';
            selectedButton.disabled = true;
        }
        
        fetch(GET_ADDRESS_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ address_id: parseInt(addressId) }),
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            // Reset button state
            if (selectedButton) {
                selectedButton.textContent = 'Selected';
                selectedButton.disabled = false;
            }
            
            if (data.status === 'success') {
                updateSelectedAddress(data.address);
                addressModal.style.display = 'none';
                showMessage('✅ Shipping address selected!', 'success');
                
                // Reload addresses to update selection indicators
                setTimeout(() => {
                    loadAddresses();
                }, 500);
            } else {
                showMessage('Error selecting address', 'error');
                clearSelection();
            }
        })
        .catch(error => {
            console.error('💥 Error selecting address:', error);
            if (selectedButton) {
                selectedButton.textContent = 'Select';
                selectedButton.disabled = false;
            }
            showMessage('Error selecting address', 'error');
            clearSelection();
        });
    }

    // Update selected address display and input
    function updateSelectedAddress(address) {
        if (selectedAddressIdInput) {
            selectedAddressIdInput.value = address.id;
        }
        updateSelectedAddressDisplay(address);
    }

    // Update the address display only
    function updateSelectedAddressDisplay(address) {
        if (currentAddressDisplay) {
            const displayName = address.fullname || '';
            const fullAddress = `${address.street}, ${address.city}, ${address.state}, ${address.zip_code}, ${address.country}`;
            
            if (displayName) {
                currentAddressDisplay.innerHTML = `
                    <strong>${displayName}</strong><br>
                    ${fullAddress}
                `;
            } else {
                currentAddressDisplay.textContent = fullAddress;
            }
        }
    }

    // Save new address
    function saveNewAddress() {
        // Get form values
        const fullname = document.getElementById('new-fullname').value.trim();
        const street = document.getElementById('new-street').value.trim();
        const city = document.getElementById('new-city').value.trim();
        const state = document.getElementById('new-state').value.trim();
        const zip = document.getElementById('new-zip').value.trim();
        const country = document.getElementById('new-country').value.trim();
        const setAsDefault = document.getElementById('set-as-default').checked;

        // Simple validation
        if (!fullname || !street || !city || !state || !zip) {
            showMessage('Please fill in all required fields', 'error');
            return;
        }

        // Prepare address data
        const addressData = {
            fullname: fullname,
            street: street,
            city: city,
            state: state,
            zip_code: zip,
            country: country,
            set_as_default: setAsDefault ? 1 : 0,
            type: 'shipping'
        };

        // Show loading state
        const saveButton = document.getElementById('save-address-btn');
        const originalText = saveButton.textContent;
        saveButton.textContent = 'Saving...';
        saveButton.disabled = true;

        fetch(SAVE_ADDRESS_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(addressData),
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            // Reset button state
            saveButton.textContent = originalText;
            saveButton.disabled = false;
            
            if (data.status === 'success') {
                showMessage('✅ Address saved successfully!', 'success');
                
                // Reset and hide form
                resetAddressForm();
                addAddressForm.style.display = 'none';
                
                // Reload addresses
                loadAddresses();
                
                // If set as default, select it automatically
                if (setAsDefault && data.address_id) {
                    setTimeout(() => {
                        selectAddress(data.address_id);
                    }, 1000);
                }
            } else {
                showMessage('Error saving address', 'error');
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
        document.getElementById('new-fullname').value = '';
        document.getElementById('new-street').value = '';
        document.getElementById('new-city').value = '';
        document.getElementById('new-state').value = '';
        document.getElementById('new-zip').value = '';
        document.getElementById('new-country').value = 'Philippines';
        document.getElementById('set-as-default').checked = false;
    }

    // Place order function
    function placeOrder() {
        const selectedAddressId = selectedAddressIdInput ? selectedAddressIdInput.value : null;
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
        
        // Validation
        if (!selectedAddressId) {
            showMessage('Please select a shipping address', 'error');
            return;
        }

        if (!paymentMethod) {
            showMessage('Please select a payment method', 'error');
            return;
        }

        // Get order data
        const items = JSON.parse(placeOrderBtn.getAttribute('data-items'));
        const totals = JSON.parse(placeOrderBtn.getAttribute('data-totals'));
        const isBuyNow = placeOrderBtn.getAttribute('data-is-buy-now') === '1';

        const orderData = {
            address_id: parseInt(selectedAddressId),
            payment_method: paymentMethod.value,
            items: items,
            totals: totals,
            is_buy_now: isBuyNow
        };

        // Show loading
        showLoading(true);

        fetch(PLACE_ORDER_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(orderData),
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            showLoading(false);
            
            if (data.status === 'success') {
                showMessage('✅ Order placed successfully! Redirecting...', 'success');
                
                // Clear all checkout data after successful order
                clearSelection();
                if (isBuyNow) {
                    sessionStorage.removeItem('buy_now_product');
                } else {
                    sessionStorage.removeItem('checkout_items');
                }
                
                // Redirect to order confirmation
                setTimeout(() => {
                    window.location.href = SITE_URL + 'pages/order-confirmation.php?order_id=' + data.order_id;
                }, 2000);
            } else {
                showMessage('Error placing order: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showLoading(false);
            console.error('Error placing order:', error);
            showMessage('Error placing order', 'error');
        });
    }

    // Utility functions
    function showLoading(show) {
        if (loadingOverlay) {
            loadingOverlay.style.display = show ? 'flex' : 'none';
        }
        
        if (placeOrderBtn) {
            placeOrderBtn.disabled = show;
            placeOrderBtn.textContent = show ? 'Processing...' : `Place Order - ₱${parseFloat(JSON.parse(placeOrderBtn.getAttribute('data-totals')).total).toFixed(2)}`;
        }
    }

    function showMessage(message, type) {
        if (!messageContainer) return;
        
        const messageDiv = document.createElement('div');
        messageDiv.className = type === 'success' ? 'success-message' : 'error-message';
        messageDiv.textContent = message;
        
        messageContainer.innerHTML = '';
        messageContainer.appendChild(messageDiv);
        
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);
    }

    console.log('✅ Checkout initialized with smart refresh detection');
});