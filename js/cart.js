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
                    html += `
                    <div class="cart-item" data-cart-id="${item.cart_id}">
                        <input type="checkbox" class="select-item" data-cart-id="${item.cart_id}" ${isChecked ? "checked" : ""}>
                        <img src="${SITE_URL}uploads/${item.image}" alt="${item.name}">
                        <div class="item-details">
                            <h3>${item.name}</h3>
                            <p class="item-price">₱${item.price}</p>
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
                            <p class="subtotal">Subtotal: ₱<span class="item-subtotal">${item.subtotal}</span></p>
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
                        <a href="<?php echo SITE_URL; ?>pages/new.php" class="btn-continue-shopping">Continue Shopping</a>
                    </div>
                `;
                cartTotal.innerHTML = '';
            }
        } catch (error) {
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
        const selectedItems = [];
        document.querySelectorAll(".select-item:checked").forEach(checkbox => {
            selectedItems.push(checkbox.dataset.cartId);
        });
        return selectedItems;
    }

    // Update cart item
    async function updateCart(cartId, quantity, size) {
        await fetch(SITE_URL + "actions/cart-update.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `cart_id=${cartId}&quantity=${quantity}&size=${size}`
        });
        updateCartBadge();
    }

    // Remove cart item
    async function removeCartItem(cartId) {
        await fetch(SITE_URL + "actions/cart-remove.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `cart_id=${cartId}`
        });
        updateCartBadge();
    }

    // Update subtotal
    function updateSubtotal(cartId, quantity) {
        const cartItem = document.querySelector(`.cart-item[data-cart-id="${cartId}"]`);
        const price = parseFloat(cartItem.querySelector(".item-price").innerText.replace("₱", ""));
        cartItem.querySelector(".item-subtotal").innerText = (price * quantity).toFixed(2);
        updateTotalOnSelection();
    }

    // Calculate total
    function calculateTotal() {
        let total = 0;
        document.querySelectorAll(".cart-item").forEach(item => {
            const checkbox = item.querySelector(".select-item");
            if (checkbox.checked) {
                const price = parseFloat(item.querySelector(".item-price").innerText.replace("₱", ""));
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

    document.getElementById("checkout-btn")?.addEventListener("click", () => {
        const selectedItems = getSelectedCartIds();

        if (selectedItems.length === 0) {
            alert("Please select at least one item to checkout.");
            return;
        }

        fetch(SITE_URL + "actions/cart-checkout.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `cart_ids=${selectedItems.join(",")}`
        }).then(() => {
            window.location.href = SITE_URL + "pages/checkout.php";
        });
    });

    // Initial load
    updateCartBadge();
    if (document.getElementById("cart-items")) {
        loadCart();
    }
});