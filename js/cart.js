document.addEventListener("DOMContentLoaded", () => {
    let checkboxStates = {};

    // Unified badge update (counts total quantities)
    async function updateCartBadge() {
        try {
            const res = await fetch(SITE_URL + "actions/cart-fetch.php");
            const data = await res.json();

            if (data.status === "success") {
                const cartCount = document.getElementById("cart-count");
                if (cartCount) {
                    let totalQuantity = 0;
                    data.cart.forEach(item => {
                        totalQuantity += parseInt(item.quantity) || 0;
                    });
                    cartCount.textContent = totalQuantity;
                }
            }
        } catch (e) {
            console.error("Error updating cart badge", e);
        }
    }

    // Load cart items
    async function loadCart() {
        const cartItems = document.getElementById("cart-items");
        const cartTotal = document.getElementById("cart-total");
        cartItems.innerHTML = '<div class="cart-loading">Loading cart...</div>';

        try {
            const res = await fetch(SITE_URL + "actions/cart-fetch.php");
            const data = await res.json();

            if (data.status === "success" && data.cart.length > 0) {
                updateCartCount(data.cart);

                let html = "";
                data.cart.forEach(item => {
                    const isChecked = checkboxStates[item.cart_id] ?? true;
                    
                    // ✅ FIXED Image handling
                    let imageSrc = SITE_URL + 'uploads/sample1.jpg'; // Default fallback
                    
                    if (item.image) {
                        // If it's already a data URL
                        if (item.image.startsWith('data:')) {
                            imageSrc = item.image;
                        } 
                        // If it's a blob stored as base64 in the database
                        else if (item.image_format && item.image.length > 100) {
                            // The image is already base64 encoded in the database
                            imageSrc = 'data:' + item.image_format + ';base64,' + item.image;
                        }
                        // If it's just a filename
                        else {
                            imageSrc = SITE_URL + 'uploads/' + item.image;
                        }
                    }

                    // Calculate subtotal
                    const subtotal = (item.price * item.quantity).toFixed(2);
                    
                    // 🟣 Color display
                    const colorDisplay = item.color_name 
                        ? `<p class="item-color">Color: <span style="text-transform: capitalize;">${item.color_name}</span></p>` 
                        : `<p class="item-color">Color: <span style="opacity:0.6;">N/A</span></p>`;

                    html += `
                    <div class="cart-item" data-cart-id="${item.cart_id}">
                        <input type="checkbox" class="select-item" data-cart-id="${item.cart_id}" ${isChecked ? "checked" : ""}>
                        <img src="${imageSrc}" alt="${item.name}" width="80" 
                             onerror="this.onerror=null; this.src='${SITE_URL}uploads/sample1.jpg'">
                        <div class="item-details">
                            <h3>${item.name}</h3>
                            ${colorDisplay}
                            <p class="item-price">Price: ₱${parseFloat(item.price).toFixed(2)}</p>
                            <div class="item-controls">
                                <div class="control-group">
                                    <label>Size:</label>
                                    <select class="size-select">
                                        <option value="S" ${item.size === "S" ? "selected" : ""}>S</option>
                                        <option value="M" ${item.size === "M" ? "selected" : ""}>M</option>
                                        <option value="L" ${item.size === "L" ? "selected" : ""}>L</option>
                                        <option value="XL" ${item.size === "XL" ? "selected" : ""}>XL</option>
                                    </select>
                                </div>
                                <div class="control-group">
                                    <label>Quantity:</label>
                                    <input type="number" class="quantity-input" value="${item.quantity}" min="1" max="10">
                                </div>
                            </div>
                            <p class="subtotal">Subtotal: ₱<span class="item-subtotal">${subtotal}</span></p>
                        </div>
                        <button class="remove-item">× Remove</button>
                    </div>`;
                });

                cartItems.innerHTML = html;
                
                const total = calculateTotal();
                const shipping = total > 500 ? 0 : 50;
                const grandTotal = total + shipping;
                
                cartTotal.innerHTML = `
                    <div class="total-breakdown">
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <span>₱${total.toFixed(2)}</span>
                        </div>
                        <div class="total-row">
                            <span>Shipping:</span>
                            <span>${shipping === 0 ? 'FREE' : '₱' + shipping.toFixed(2)}</span>
                        </div>
                        <div class="total-row grand-total">
                            <span>Total:</span>
                            <span>₱${grandTotal.toFixed(2)}</span>
                        </div>
                    </div>
                `;
                
                attachCartEvents();
                updateSelectAllState();
            } else {
                updateCartCount([]);
                cartItems.innerHTML = `
                    <div class="cart-empty">
                        <h3>🛒 Your cart is empty</h3>
                        <p>Browse our products and add some items to your cart!</p>
                        <a href="${SITE_URL}pages/new.php" class="btn-continue-shopping">Continue Shopping</a>
                    </div>
                `;
                cartTotal.innerHTML = '';
            }
        } catch (error) {
            console.error('Error loading cart:', error);
            cartItems.innerHTML = '<div class="cart-loading">Error loading cart.</div>';
        }
    }

    // Attach cart item event listeners
    function attachCartEvents() {
        document.querySelectorAll(".quantity-input").forEach(input => {
            input.addEventListener("change", async () => {
                const cartItem = input.closest(".cart-item");
                const cartId = cartItem.dataset.cartId;
                const quantity = parseInt(input.value) || 1;
                const size = cartItem.querySelector(".size-select").value;

                cartItem.classList.add('updating');
                await updateCart(cartId, quantity, size);
                updateSubtotal(cartId, quantity);
                updateCartBadge();
                setTimeout(() => cartItem.classList.remove('updating'), 600);
            });
        });

        document.querySelectorAll(".size-select").forEach(select => {
            select.addEventListener("change", async () => {
                const cartItem = select.closest(".cart-item");
                const cartId = cartItem.dataset.cartId;
                const quantity = parseInt(cartItem.querySelector(".quantity-input").value) || 1;

                cartItem.classList.add('updating');
                await updateCart(cartId, quantity, select.value);
                setTimeout(() => cartItem.classList.remove('updating'), 600);
            });
        });

        document.querySelectorAll(".remove-item").forEach(btn => {
            btn.addEventListener("click", async () => {
                const cartItem = btn.closest(".cart-item");
                const cartId = cartItem.dataset.cartId;

                if (confirm('Are you sure you want to remove this item from your cart?')) {
                    cartItem.style.opacity = '0.5';
                    await removeCartItem(cartId);
                    delete checkboxStates[cartId];
                    loadCart();
                    updateCartBadge();
                }
            });
        });

        document.querySelectorAll(".select-item").forEach(checkbox => {
            checkbox.addEventListener("change", () => {
                checkboxStates[checkbox.dataset.cartId] = checkbox.checked;
                updateTotalOnSelection();
                updateSelectAllState();
            });
        });

        // Select All functionality
        const selectAll = document.getElementById("select-all");
        if (selectAll) {
            selectAll.addEventListener("change", function() {
                const checkboxes = document.querySelectorAll(".select-item");
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                    checkboxStates[checkbox.dataset.cartId] = this.checked;
                });
                updateTotalOnSelection();
            });
        }

        // Remove Selected functionality
        const removeSelected = document.getElementById("remove-selected");
        if (removeSelected) {
            removeSelected.addEventListener("click", async () => {
                const selectedItems = getSelectedCartIds();
                if (selectedItems.length === 0) {
                    alert("Please select items to remove.");
                    return;
                }

                if (confirm(`Are you sure you want to remove ${selectedItems.length} item(s) from your cart?`)) {
                    for (const cartId of selectedItems) {
                        await removeCartItem(cartId);
                        delete checkboxStates[cartId];
                    }
                    loadCart();
                    updateCartBadge();
                }
            });
        }
    }

    function updateSelectAllState() {
        const checkboxes = document.querySelectorAll(".select-item");
        const selectAll = document.getElementById("select-all");
        if (checkboxes.length > 0 && selectAll) {
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            selectAll.checked = allChecked;
        }
    }

    function getSelectedCartIds() {
        return Array.from(document.querySelectorAll(".select-item:checked"))
            .map(cb => cb.dataset.cartId)
            .filter(id => id && !isNaN(id));
    }

    // Update cart item
    async function updateCart(cartId, quantity, size) {
        try {
            await fetch(SITE_URL + "actions/cart-update.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `cart_id=${cartId}&quantity=${quantity}&size=${size}`
            });
            updateCartBadge();
        } catch (error) {
            console.error('Error updating cart:', error);
        }
    }

    // Remove cart item
    async function removeCartItem(cartId) {
        try {
            await fetch(SITE_URL + "actions/cart-remove.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `cart_id=${cartId}`
            });
            updateCartBadge();
        } catch (error) {
            console.error('Error removing cart item:', error);
        }
    }

    // Update subtotal
    function updateSubtotal(cartId, quantity) {
        const cartItem = document.querySelector(`.cart-item[data-cart-id="${cartId}"]`);
        if (cartItem) {
            const price = parseFloat(cartItem.querySelector(".item-price").innerText.replace("Price: ₱", ""));
            cartItem.querySelector(".item-subtotal").innerText = (price * quantity).toFixed(2);
            updateTotalOnSelection();
        }
    }

    // Calculate total
    function calculateTotal() {
        let total = 0;
        document.querySelectorAll(".cart-item").forEach(item => {
            const checkbox = item.querySelector(".select-item");
            if (checkbox && checkbox.checked) {
                const price = parseFloat(item.querySelector(".item-price").innerText.replace("Price: ₱", ""));
                const qty = parseInt(item.querySelector(".quantity-input").value) || 1;
                total += price * qty;
            }
        });
        return total;
    }

    function updateTotalOnSelection() {
        const total = calculateTotal();
        const shipping = total > 500 ? 0 : 50;
        const grandTotal = total + shipping;
        
        const cartTotal = document.getElementById("cart-total");
        if (cartTotal) {
            cartTotal.innerHTML = `
                <div class="total-breakdown">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>₱${total.toFixed(2)}</span>
                    </div>
                    <div class="total-row">
                        <span>Shipping:</span>
                        <span>${shipping === 0 ? 'FREE' : '₱' + shipping.toFixed(2)}</span>
                    </div>
                    <div class="total-row grand-total">
                        <span>Total:</span>
                        <span>₱${grandTotal.toFixed(2)}</span>
                    </div>
                </div>
            `;
        }
    }

    function updateCartCount(cart) {
        const cartCount = document.getElementById("cart-count");
        if (cartCount) {
            cartCount.textContent = cart.length; // unique product count
        }
    }

    document.getElementById("checkout-btn").addEventListener("click", async () => {
        const selectedItems = getSelectedCartIds();

        console.log("🛒 Selected items for checkout:", selectedItems);
        console.log("🛒 Selected items count:", selectedItems.length);
        
        if (selectedItems.length === 0) {
            alert("Please select at least one item to checkout.");
            return;
        }

        try {
            // Store selected items in session for checkout
            const response = await fetch(SITE_URL + "actions/cart-checkout.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `cart_ids=${selectedItems.join(",")}`
            });
            
            const result = await response.json();
            console.log("🛒 Checkout API response:", result);
            
            if (result.status === "success") {
                console.log(`🛒 Success! Redirecting to checkout with ${result.count} items`);
                console.log(`🛒 Item IDs:`, result.items);
                
                // Optional: Verify session data before redirect
                setTimeout(() => {
                    window.location.href = SITE_URL + "pages/checkout.php";
                }, 100);
                
            } else {
                console.error("🛒 Checkout failed:", result.message);
                alert("Error: " + (result.message || "Failed to proceed to checkout"));
            }
        } catch (error) {
            console.error("🛒 Checkout error:", error);
            alert("Network error. Please try again.");
        }
    });

    // Initial load
    updateCartBadge();
    if (document.getElementById("cart-items")) {
        loadCart();
    }
});