document.addEventListener("DOMContentLoaded", () => {
    let orderData = {
        items: [],
        subtotal: 0,
        shipping: 0,
        total: 0,
        shippingInfo: {},
        paymentMethod: 'cod'
    };

    // Load checkout items
    async function loadCheckoutItems() {
        try {
            const response = await fetch(SITE_URL + 'actions/checkout-items.php');
            const data = await response.json();

            if (data.status === 'success') {
                orderData.items = data.items;
                orderData.subtotal = data.totals.subtotal;
                orderData.shipping = data.totals.shipping;
                orderData.total = data.totals.total;

                displayCheckoutItems();
                displayOrderTotals();
            } else {
                alert('Error loading checkout items');
                window.location.href = SITE_URL + 'pages/cart.php';
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error loading checkout items');
        }
    }

    // Display checkout items
    function displayCheckoutItems() {
        const itemsContainer = document.getElementById('checkout-items');
        let html = '';

        orderData.items.forEach(item => {
            html += `
                <div class="checkout-item">
                    <img src="${SITE_URL}uploads/${item.image}" alt="${item.name}">
                    <div class="item-info">
                        <h4>${item.name}</h4>
                        <p>Size: ${item.size} | Qty: ${item.quantity}</p>
                        <p class="item-price">₱${item.price} × ${item.quantity} = ₱${item.subtotal}</p>
                    </div>
                </div>
            `;
        });

        itemsContainer.innerHTML = html;
    }

    // Display order totals
    function displayOrderTotals() {
        const totalsContainer = document.getElementById('order-totals');
        const html = `
            <div class="totals-breakdown">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>₱${orderData.subtotal.toFixed(2)}</span>
                </div>
                <div class="total-row">
                    <span>Shipping:</span>
                    <span>${orderData.shipping === 0 ? 'FREE' : '₱' + orderData.shipping.toFixed(2)}</span>
                </div>
                <div class="total-row grand-total">
                    <span>Total:</span>
                    <span>₱${orderData.total.toFixed(2)}</span>
                </div>
            </div>
        `;
        totalsContainer.innerHTML = html;
    }

    // Handle payment method selection
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            orderData.paymentMethod = this.value;
        });
    });

    // Place order
    document.getElementById('place-order-btn').addEventListener('click', async function() {
        const btn = this;
        const originalText = btn.innerHTML;
        
        // Validate shipping form
        if (!validateShippingForm()) {
            alert('Please fill in all required shipping information.');
            return;
        }

        // Collect shipping information
        orderData.shippingInfo = {
            fullname: document.getElementById('fullname').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value,
            address: document.getElementById('address').value,
            city: document.getElementById('city').value,
            province: document.getElementById('province').value,
            zipcode: document.getElementById('zipcode').value,
            notes: document.getElementById('notes').value
        };

        btn.innerHTML = 'Placing Order...';
        btn.disabled = true;

        try {
            const response = await fetch(SITE_URL + 'actions/place-order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(orderData)
            });

            const result = await response.json();

            if (result.status === 'success') {
                // Redirect to order confirmation page
                window.location.href = SITE_URL + 'pages/order-confirmation.php?order_id=' + result.order_id;
            } else {
                alert('Error placing order: ' + result.message);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error placing order. Please try again.');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });

    // Validate shipping form
    function validateShippingForm() {
        const requiredFields = ['fullname', 'email', 'phone', 'address', 'city', 'province', 'zipcode'];
        
        for (const field of requiredFields) {
            const input = document.getElementById(field);
            if (!input.value.trim()) {
                input.focus();
                return false;
            }
        }
        
        return true;
    }

    // Initialize checkout
    loadCheckoutItems();
});