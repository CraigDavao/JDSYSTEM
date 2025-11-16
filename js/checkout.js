// checkout.js - Complete working version
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

    // Initialize all functionality
    initializeCheckout();

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

    // Place order button - FIXED
    if (placeOrderBtn) {
        console.log('✅ Place order button found');
        placeOrderBtn.addEventListener('click', function(e) {
            console.log('🔄 Place order button clicked');
            e.preventDefault();
            placeOrder();
        });
    } else {
        console.error('❌ Place order button not found!');
    }

    // Initialize all checkout functionality
    function initializeCheckout() {
        console.log('🔄 Initializing checkout functionality...');
        initializeAddressSelection();
        initializeGCashPayment();
        initializeCourierSelection();
        initializeDeliverySchedule();
        console.log('✅ Checkout initialization complete');
    }

    // GCash payment functionality
    function initializeGCashPayment() {
        const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
        const gcashDetails = document.getElementById('gcash-payment-details');
        const receiptInput = document.getElementById('gcash-receipt');
        const receiptPreview = document.getElementById('receipt-preview');

        console.log('💰 Initializing GCash payment');

        // Toggle GCash details
        paymentMethods.forEach(method => {
            method.addEventListener('change', function() {
                console.log('💳 Payment method changed to:', this.value);
                if (this.value === 'gcash') {
                    if (gcashDetails) gcashDetails.style.display = 'block';
                    if (receiptInput) receiptInput.required = true;
                } else {
                    if (gcashDetails) gcashDetails.style.display = 'none';
                    if (receiptInput) {
                        receiptInput.required = false;
                        receiptInput.value = '';
                        if (receiptPreview) receiptPreview.innerHTML = '';
                    }
                }
            });
        });

        // Handle receipt preview
        if (receiptInput) {
            receiptInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file && receiptPreview) {
                    previewReceipt(file, receiptPreview);
                }
            });
        }
    }

    // Courier selection functionality
    function initializeCourierSelection() {
        const courierSelect = document.getElementById('courier');
        if (courierSelect) {
            console.log('🚚 Courier select found');
            courierSelect.addEventListener('change', function() {
                console.log('🚚 Selected courier:', this.value);
            });
        } else {
            console.error('❌ Courier select not found!');
        }
    }

    // Delivery schedule functionality
    function initializeDeliverySchedule() {
        const scheduleInput = document.getElementById('delivery-schedule');
        if (scheduleInput) {
            console.log('📅 Delivery schedule input found');
            // Set minimum date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            scheduleInput.min = tomorrow.toISOString().slice(0, 16);
            
            scheduleInput.addEventListener('change', function() {
                console.log('📅 Selected delivery schedule:', this.value);
            });
        } else {
            console.error('❌ Delivery schedule input not found!');
        }
    }

    // Preview uploaded receipt
    function previewReceipt(file, previewContainer) {
        if (!file.type.match('image.*') && file.type !== 'application/pdf') {
            showMessage('Please upload an image or PDF file', 'error');
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            showMessage('File size must be less than 5MB', 'error');
            return;
        }

        previewContainer.innerHTML = '';

        if (file.type.match('image.*')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.maxWidth = '200px';
                img.style.maxHeight = '200px';
                img.style.marginTop = '10px';
                img.style.border = '1px solid #ddd';
                img.style.borderRadius = '4px';
                previewContainer.appendChild(img);
            };
            reader.readAsDataURL(file);
        } else if (file.type === 'application/pdf') {
            const pdfInfo = document.createElement('div');
            pdfInfo.innerHTML = `
                <div style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                    <strong>📄 PDF Receipt:</strong> ${file.name}<br>
                    <small>File will be uploaded with your order</small>
                </div>
            `;
            previewContainer.appendChild(pdfInfo);
        }
    }

    // Initialize address selection
    function initializeAddressSelection() {
        console.log('🔍 Initializing address selection...');
        
        const defaultAddressId = selectedAddressIdInput ? selectedAddressIdInput.value : null;
        
        if (defaultAddressId && defaultAddressId !== '0') {
            console.log('🎯 Using default address ID:', defaultAddressId);
            fetchAddressDetails(defaultAddressId);
        } else {
            console.log('❌ No address available');
            if (currentAddressDisplay) {
                currentAddressDisplay.textContent = 'No address selected. Please add a shipping address.';
            }
        }
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
                showMessage('Error loading address details', 'error');
            }
        })
        .catch(error => {
            console.error('💥 Error fetching address details:', error);
            showMessage('Network error loading address', 'error');
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

        const defaultAddressId = selectedAddressIdInput ? selectedAddressIdInput.value : null;

        addressList.innerHTML = addresses.map((address) => {
            const addressId = address.id;
            const displayName = address.fullname || 'No Name Specified';
            const isDefault = address.is_default === 1;
            const isCurrentlySelected = addressId.toString() === defaultAddressId;
            
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
                            ${isCurrentlySelected ? 
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
            } else {
                showMessage('Error selecting address', 'error');
            }
        })
        .catch(error => {
            console.error('💥 Error selecting address:', error);
            if (selectedButton) {
                selectedButton.textContent = 'Select';
                selectedButton.disabled = false;
            }
            showMessage('Error selecting address', 'error');
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

    // Enhanced place order function with validation
    function placeOrder() {
        console.log('🚀 Starting place order process...');
        
        const selectedAddressId = selectedAddressIdInput ? selectedAddressIdInput.value : null;
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
        const courierSelect = document.getElementById('courier');
        const scheduleInput = document.getElementById('delivery-schedule');
        const receiptInput = document.getElementById('gcash-receipt');

        console.log('📋 Validation data:', {
            selectedAddressId,
            paymentMethod: paymentMethod ? paymentMethod.value : 'NOT FOUND',
            courierSelect: courierSelect ? courierSelect.value : 'NOT FOUND',
            scheduleInput: scheduleInput ? scheduleInput.value : 'NOT FOUND',
            receiptInput: receiptInput ? receiptInput.files : 'NOT FOUND'
        });

        // Validation
        if (!selectedAddressId || selectedAddressId === '0') {
            showMessage('Please select a shipping address', 'error');
            return;
        }

        if (!paymentMethod) {
            showMessage('Please select a payment method', 'error');
            return;
        }

        if (!courierSelect || !courierSelect.value) {
            showMessage('Please select your preferred courier', 'error');
            return;
        }

        if (!scheduleInput || !scheduleInput.value) {
            showMessage('Please select your preferred delivery schedule', 'error');
            return;
        }

        // GCash specific validation
        if (paymentMethod.value === 'gcash') {
            if (!receiptInput || !receiptInput.files || !receiptInput.files[0]) {
                showMessage('Please upload your GCash payment receipt', 'error');
                return;
            }
        }

        // Get order data
        const items = JSON.parse(placeOrderBtn.getAttribute('data-items'));
        const totals = JSON.parse(placeOrderBtn.getAttribute('data-totals'));
        const isBuyNow = placeOrderBtn.getAttribute('data-is-buy-now') === '1';

        console.log('📦 Order data:', {
            itemsCount: items.length,
            totals,
            isBuyNow
        });

        // Prepare form data for file upload
        const formData = new FormData();
        formData.append('address_id', selectedAddressId);
        formData.append('payment_method', paymentMethod.value);
        formData.append('courier', courierSelect.value);
        formData.append('delivery_schedule', scheduleInput.value);
        formData.append('items', JSON.stringify(items));
        formData.append('totals', JSON.stringify(totals));
        formData.append('is_buy_now', isBuyNow);

        // Add GCash receipt if applicable
        if (paymentMethod.value === 'gcash' && receiptInput.files[0]) {
            formData.append('gcash_receipt', receiptInput.files[0]);
            console.log('📎 Added GCash receipt to form data');
        }

        // Show loading
        showLoading(true);

        console.log('📤 Sending order data to:', PLACE_ORDER_URL);

        fetch(PLACE_ORDER_URL, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            console.log('📥 Received response, parsing JSON...');
            return response.json();
        })
        .then(data => {
            console.log('✅ Server response:', data);
            showLoading(false);
            
            if (data.status === 'success') {
                showMessage('✅ Order placed successfully! Redirecting...', 'success');
                
                // Clear sessions
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
                console.error('❌ Server error:', data.message);
                showMessage('Error placing order: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('💥 Fetch error:', error);
            showLoading(false);
            showMessage('Network error: ' + error.message, 'error');
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

    console.log('✅ Checkout fully initialized with all functionality');
});