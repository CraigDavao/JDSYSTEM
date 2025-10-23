document.addEventListener("DOMContentLoaded", () => {
    console.log('Checkout page loaded');

    const placeOrderBtn = document.getElementById('place-order-btn');
    if (!placeOrderBtn) {
        console.error('Place order button not found.');
        return;
    }

    // Parse items & totals from button dataset
    const checkoutItems = JSON.parse(placeOrderBtn.dataset.items || "[]");
    const orderTotals = JSON.parse(placeOrderBtn.dataset.totals || "{}");
    const isBuyNow = placeOrderBtn.dataset.isBuyNow === "1" || placeOrderBtn.dataset.isBuyNow === "true";

    console.log('Checkout Items:', checkoutItems);
    console.log('Order Totals:', orderTotals);
    console.log('Buy Now Mode:', isBuyNow);

    // Default order data object
    let orderData = {
        items: checkoutItems,
        subtotal: orderTotals.subtotal || 0,
        shipping: orderTotals.shipping || 0,
        total: orderTotals.total || 0,
        paymentMethod: 'cod',
        shippingInfo: {}
    };

    // 🟣 Payment method toggle
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            orderData.paymentMethod = this.value;
            console.log('Payment method changed:', orderData.paymentMethod);
        });
    });

    // 🟣 Place Order button
    placeOrderBtn.addEventListener('click', () => {
        console.log('Place order clicked');

        if (!validateShippingForm()) return;

        if (!orderData.items || orderData.items.length === 0) {
            alert('No items to checkout. Please add items to your cart first.');
            return;
        }

        // Collect shipping details
        orderData.shippingInfo = {
            fullname: getValue('fullname'),
            email: getValue('email'),
            phone: getValue('phone'),
            address: getValue('address'),
            city: getValue('city'),
            province: getValue('province'),
            zipcode: getValue('zipcode'),
            notes: getValue('notes')
        };

        console.log('Final orderData to send:', orderData);

        // Disable button while submitting
        placeOrderBtn.innerHTML = 'Placing Order...';
        placeOrderBtn.disabled = true;

        // Send to backend
        fetch(SITE_URL + 'actions/place-order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        })
        .then(res => res.json())
        .then(result => {
            console.log('Server response:', result);

            if (result.status === 'success') {
                // 🟣 If Buy Now → clear session
                if (isBuyNow) {
                    fetch(SITE_URL + 'actions/clear-buy-now.php', { method: 'POST' });
                }

                window.location.href = SITE_URL + 'pages/order-confirmation.php?order_id=' + result.order_id;
            } else {
                alert(result.message || 'Failed to place order. Please try again.');
                placeOrderBtn.innerHTML = 'Place Order';
                placeOrderBtn.disabled = false;
            }
        })
        .catch(err => {
            console.error('Network error:', err);
            alert('Network error. Please check your connection.');
            placeOrderBtn.innerHTML = 'Place Order';
            placeOrderBtn.disabled = false;
        });
    });

    // Helper: Get trimmed value
    function getValue(id) {
        const el = document.getElementById(id);
        return el ? el.value.trim() : '';
    }

    // 🟣 Form validation
    function validateShippingForm() {
        const required = [
            {id: 'fullname', label: 'Full Name'},
            {id: 'email', label: 'Email'},
            {id: 'phone', label: 'Phone Number'},
            {id: 'address', label: 'Address'},
            {id: 'city', label: 'City'},
            {id: 'province', label: 'Province'},
            {id: 'zipcode', label: 'ZIP Code'}
        ];

        for (let field of required) {
            const value = getValue(field.id);
            if (!value) {
                alert(`Please fill in the ${field.label}.`);
                document.getElementById(field.id).focus();
                return false;
            }
        }

        const email = getValue('email');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Please enter a valid email address.');
            document.getElementById('email').focus();
            return false;
        }

        return true;
    }

    // 🟣 Fallback image handling
    document.querySelectorAll('.checkout-item img').forEach(img => {
        img.addEventListener('error', () => {
            img.src = SITE_URL + 'uploads/sample1.jpg';
        });
    });
});
