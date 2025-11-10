// checkout.js - Complete version with all functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('🛒 Checkout page loaded');
    
    // API Endpoints - Using relative paths
    const GET_ADDRESS_URL = '../actions/get-address.php';
    const SAVE_ADDRESS_URL = '../actions/save-address.php';
    const GET_ADDRESSES_URL = '../actions/get-addresses.php';
    const PLACE_ORDER_URL = '../actions/place-order.php';
    
    console.log('🔧 API Endpoints configured');
    
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
                // Reset form when showing
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
        .then(response => response.text().then(text => {
            console.log('📄 Raw response:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('❌ JSON parse error:', e);
                throw new Error('Invalid JSON response');
            }
        }))
        .then(data => {
            console.log('📦 Address data received:', data);
            
            if (data.status === 'success') {
                console.log(`✅ Found ${data.addresses.length} addresses`);
                renderAddressList(data.addresses);
            } else {
                console.error('❌ API Error:', data.message);
                showMessage('Error loading addresses: ' + (data.message || 'Unknown error'), 'error');
                if (addressList) {
                    addressList.innerHTML = '<div class="error-message">❌ ' + (data.message || 'Failed to load addresses') + '</div>';
                }
            }
        })
        .catch(error => {
            console.error('💥 Fetch error:', error);
            showMessage('Error loading addresses. Please check console.', 'error');
            if (addressList) {
                addressList.innerHTML = '<div class="error-message">💥 Error loading addresses</div>';
            }
        });
    }

    // Render address list in modal
    function renderAddressList(addresses) {
        if (!addressList) return;
        
        console.log('🎨 Rendering addresses:', addresses);
        
        if (!addresses || addresses.length === 0) {
            addressList.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #666;">
                    <p style="font-size: 16px; margin-bottom: 15px;">No addresses found.</p>
                    <p style="font-size: 14px; color: #888;">Click "Add New Address" to create your first shipping address.</p>
                </div>
            `;
            return;
        }

        addressList.innerHTML = addresses.map((address, index) => {
            const addressId = address.id;
            const displayName = address.fullname || 'No Name Specified';
            
            return `
                <div class="address-item ${address.is_default ? 'selected' : ''}" data-id="${addressId}">
                    <div class="address-details">
                        <p style="font-weight: 600; color: #2c3e50; margin-bottom: 8px;">${displayName}</p>
                        <p style="color: #555; line-height: 1.5; margin-bottom: 8px;">
                            ${address.street}, ${address.city}, ${address.state}, ${address.zip_code}, ${address.country}
                        </p>
                        <div class="address-meta">
                            ${address.is_default ? 
                                '<span class="address-badge badge-default">Default Address</span>' : 
                                '<span class="address-badge badge-other">Additional Address</span>'
                            }
                        </div>
                    </div>
                    <button class="select-address-btn" data-id="${addressId}">
                        ${address.is_default ? 'Selected' : 'Select'}
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
                    console.error('❌ Invalid address ID');
                    showMessage('Error: Invalid address selection', 'error');
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
        
        // Show loading state on the button
        const selectedButton = document.querySelector(`.select-address-btn[data-id="${addressId}"]`);
        const originalText = selectedButton ? selectedButton.textContent : 'Select';
        
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
        .then(response => response.text().then(text => {
            console.log('📄 Raw select response:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('❌ JSON parse error:', e);
                throw new Error('Invalid JSON response');
            }
        }))
        .then(data => {
            console.log('✅ Select address response:', data);
            
            // Reset button state
            if (selectedButton) {
                selectedButton.textContent = 'Selected';
                selectedButton.disabled = false;
            }
            
            if (data.status === 'success') {
                const address = data.address;
                updateSelectedAddress(address);
                addressModal.style.display = 'none';
                showMessage('✅ Shipping address updated successfully!', 'success');
            } else {
                showMessage('❌ Error selecting address: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('💥 Error selecting address:', error);
            // Reset button state
            if (selectedButton) {
                selectedButton.textContent = originalText;
                selectedButton.disabled = false;
            }
            showMessage('❌ Error selecting address. Please try again.', 'error');
        });
    }

    // Update selected address display
    function updateSelectedAddress(address) {
        if (currentAddressDisplay && selectedAddressIdInput) {
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
            
            selectedAddressIdInput.value = address.id;
            console.log('📍 Updated selected address to ID:', address.id);
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

        console.log('📝 Saving new address:', { fullname, street, city, state, zip, country, setAsDefault });

        // Simple validation
        if (!fullname || !street || !city || !state || !zip) {
            showMessage('❌ Please fill in all required fields.', 'error');
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

        console.log('📤 Sending address data:', addressData);

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
        .then(response => response.text().then(text => {
            console.log('📄 Raw save response:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('❌ JSON parse error:', e);
                throw new Error('Invalid JSON response');
            }
        }))
        .then(data => {
            console.log('✅ Save address response:', data);
            
            // Reset button state
            saveButton.textContent = originalText;
            saveButton.disabled = false;
            
            if (data.status === 'success') {
                showMessage('✅ Address saved successfully!', 'success');
                
                // Reset and hide form
                resetAddressForm();
                addAddressForm.style.display = 'none';
                
                // Reload addresses
                console.log('🔄 Reloading addresses...');
                loadAddresses();
                
                // If set as default, select it automatically
                if (setAsDefault && data.address_id) {
                    console.log('⭐ New address set as default, selecting it...');
                    setTimeout(() => {
                        selectAddress(data.address_id);
                    }, 1000);
                }
            } else {
                showMessage('❌ Error: ' + (data.message || 'Failed to save address'), 'error');
            }
        })
        .catch(error => {
            console.error('💥 Error saving address:', error);
            saveButton.textContent = originalText;
            saveButton.disabled = false;
            showMessage('❌ Network error. Please try again.', 'error');
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
            showMessage('❌ Please select a shipping address.', 'error');
            return;
        }

        if (!paymentMethod) {
            showMessage('❌ Please select a payment method.', 'error');
            return;
        }

        // Get order data from button data attributes
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

        console.log('🛍️ Placing order:', orderData);

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
            console.log('✅ Place order response:', data);
            
            if (data.status === 'success') {
                showMessage('✅ Order placed successfully! Redirecting...', 'success');
                
                // Clear checkout sessions
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
                showMessage('❌ Error placing order: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showLoading(false);
            console.error('💥 Error placing order:', error);
            showMessage('❌ Error placing order. Please try again.', 'error');
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
        messageDiv.className = type === 'success' ? 'success-message' : 
                              type === 'error' ? 'error-message' : 'info-message';
        messageDiv.textContent = message;
        
        messageContainer.innerHTML = '';
        messageContainer.appendChild(messageDiv);
        
        // Auto remove after 5 seconds (except for success messages that might redirect)
        if (type !== 'success') {
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.remove();
                }
            }, 5000);
        }
    }

    // Initialize
    console.log('✅ Checkout initialized successfully');
});