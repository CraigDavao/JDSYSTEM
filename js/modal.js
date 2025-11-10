// modal.js - Dynamic address handling
document.addEventListener('DOMContentLoaded', function() {
    // Modal elements
    const modal = document.getElementById('addressModal');
    const btnChangeAddress = document.getElementById('btn-change-address');
    const closeModal = document.querySelector('.close-modal');
    const addNewAddressBtn = document.getElementById('add-new-address-btn');
    const saveAddressBtn = document.getElementById('save-address-btn');
    const cancelAddressBtn = document.getElementById('cancel-address-btn');
    const addAddressForm = document.getElementById('add-address-form');

    // Open modal when "Change Address" is clicked
    if (btnChangeAddress) {
        btnChangeAddress.addEventListener('click', function() {
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        });
    }

    // Close modal when X is clicked
    if (closeModal) {
        closeModal.addEventListener('click', function() {
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    }

    // Close modal when clicking outside
    if (modal) {
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    }

    // Show add address form when "Add New Address" is clicked
    if (addNewAddressBtn) {
        addNewAddressBtn.addEventListener('click', function() {
            if (addAddressForm) {
                addAddressForm.style.display = 'block';
                addNewAddressBtn.style.display = 'none';
            }
        });
    }

    // Cancel adding new address
    if (cancelAddressBtn) {
        cancelAddressBtn.addEventListener('click', function() {
            if (addAddressForm) {
                addAddressForm.style.display = 'none';
                if (addNewAddressBtn) {
                    addNewAddressBtn.style.display = 'block';
                }
                clearAddressForm();
            }
        });
    }

    // Save new address
    if (saveAddressBtn) {
        saveAddressBtn.addEventListener('click', function() {
            saveNewAddress();
        });
    }

    // Make address items selectable
    const addressItems = document.querySelectorAll('.address-item');
    addressItems.forEach(item => {
        item.addEventListener('click', function() {
            selectAddressItem(this);
        });
    });

    // Select address buttons
    const selectAddressBtns = document.querySelectorAll('.select-address-btn');
    selectAddressBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const addressItem = this.closest('.address-item');
            selectAddress(addressItem);
        });
    });

    // Functions
    function selectAddressItem(addressItem) {
        // Remove selected class from all items
        addressItems.forEach(i => i.classList.remove('selected'));
        // Add selected class to clicked item
        addressItem.classList.add('selected');
    }

    function selectAddress(addressItem) {
        const addressId = addressItem.getAttribute('data-address-id');
        const addressText = addressItem.querySelector('.address-text').textContent;
        
        // Update the main address display
        updateSelectedAddress(addressId, addressText);
        
        // Close modal
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    function clearAddressForm() {
        const formInputs = addAddressForm.querySelectorAll('input');
        formInputs.forEach(input => {
            if (input.type !== 'checkbox') {
                input.value = '';
            } else {
                input.checked = false;
            }
        });
    }

    function saveNewAddress() {
        const street = document.getElementById('street').value.trim();
        const city = document.getElementById('city').value.trim();
        const state = document.getElementById('state').value.trim();
        const zipCode = document.getElementById('zip_code').value.trim();
        const country = document.getElementById('country').value.trim();
        const setDefault = document.getElementById('set_default').checked;

        // Basic validation
        if (!street || !city || !state || !zipCode || !country) {
            showMessage('Please fill in all required fields', 'error');
            return;
        }

        // Show loading state
        saveAddressBtn.innerHTML = '<div class="loading-spinner"></div>Saving...';
        saveAddressBtn.disabled = true;

        // Prepare data
        const formData = new FormData();
        formData.append('action', 'add_address');
        formData.append('street', street);
        formData.append('city', city);
        formData.append('state', state);
        formData.append('zip_code', zipCode);
        formData.append('country', country);
        formData.append('set_default', setDefault ? '1' : '0');

        // Send AJAX request
        fetch('<?php echo SITE_URL; ?>includes/handle_address.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Address saved successfully!', 'success');
                // Reload after a short delay to show the new address
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showMessage('Error: ' + data.message, 'error');
                saveAddressBtn.innerHTML = 'Save Address';
                saveAddressBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('An error occurred while saving the address', 'error');
            saveAddressBtn.innerHTML = 'Save Address';
            saveAddressBtn.disabled = false;
        });
    }

    function updateSelectedAddress(addressId, addressText) {
        // Update the address display box
        const addressDisplay = document.querySelector('.address-display-box p');
        if (addressDisplay) {
            addressDisplay.textContent = addressText;
        }

        // Update the hidden form field
        const addressIdInput = document.getElementById('address_id');
        if (addressIdInput) {
            addressIdInput.value = addressId;
        }

        // Update the select dropdown if it exists
        const addressSelect = document.getElementById('address-select');
        if (addressSelect) {
            addressSelect.value = addressId;
        }

        showMessage('Address updated successfully!', 'success');
    }

    function showMessage(message, type) {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.message');
        existingMessages.forEach(msg => msg.remove());

        // Create message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}-message`;
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 6px;
            color: white;
            font-weight: 500;
            z-index: 10001;
            animation: slideInRight 0.3s ease;
        `;
        
        messageDiv.style.background = type === 'success' ? '#27ae60' : '#e74c3c';
        messageDiv.textContent = message;
        document.body.appendChild(messageDiv);

        // Remove message after 3 seconds
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 3000);
    }

    // Keyboard support
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.style.display === 'block') {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
});

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .loading-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s linear infinite;
        margin-right: 8px;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none !important;
    }
    
    .address-item {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .select-address-btn {
        cursor: pointer;
    }
    
    #add-new-address-btn {
        cursor: pointer;
    }
    
    #save-address-btn {
        cursor: pointer;
    }
    
    #cancel-address-btn {
        cursor: pointer;
    }
    
    .close-modal {
        cursor: pointer;
    }
    
    .btn-change-address {
        cursor: pointer;
    }
    
    .empty-addresses {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }
    
    .empty-addresses .icon {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.5;
    }
    
    .empty-addresses h4 {
        color: #495057;
        margin-bottom: 10px;
        font-size: 1.3em;
    }
    
    .empty-addresses p {
        font-size: 15px;
        line-height: 1.5;
    }
`;
document.head.appendChild(style);