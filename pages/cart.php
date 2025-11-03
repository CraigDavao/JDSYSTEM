<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>css/cart.css?v=<?= time(); ?>">

<div class="cart-dashboard">
    <h2>My Cart</h2>
    
    <!-- Cart Actions -->
    <div class="cart-actions">
        <label class="select-all-label">
            <input type="checkbox" id="select-all"> Select All
        </label>
        <button class="btn-remove-selected" id="remove-selected">Remove Selected</button>
    </div>
    
    <div id="cart-items"></div>
    
    <!-- Cart Summary -->
    <div class="cart-summary">
        <div id="cart-total"></div>
        <div class="shipping-notice">
            <p>🚚 Free shipping on orders over ₱500</p>
        </div>
        <button id="checkout-btn" class="checkout-btn">Proceed to Checkout</button>
    </div>
</div>

<script src="<?php echo SITE_URL; ?>js/cart.js?v=<?= time(); ?>"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    let checkboxStates = {};

    async function loadCart() {
        try {
            const res = await fetch(SITE_URL + "actions/cart-fetch.php");
            const data = await res.json();
            const cartItems = document.getElementById("cart-items");
            const cartTotal = document.getElementById("cart-total");

            if (data.status === "success" && data.cart && data.cart.length > 0) {
                let html = "";
                data.cart.forEach(item => {
                    const isChecked = checkboxStates[item.cart_id] ?? true;
                    
                    // ✅ Image handling
                    let imageSrc = item.image;
                    if (item.image && !item.image.startsWith('data:')) {
                        // If it's blob data stored as string, create data URL
                        if (item.image_format && item.image.length > 100) {
                            imageSrc = 'data:' + item.image_format + ';base64,' + btoa(item.image);
                        } else {
                            imageSrc = SITE_URL + 'uploads/' + item.image;
                        }
                    } else if (!item.image) {
                        imageSrc = SITE_URL + 'uploads/sample1.jpg';
                    }

                    // 🟣 Color display: show user-selected color or fallback
                    const colorDisplay = item.color_name 
                        ? `<p class="item-color">Color: <span style="text-transform: capitalize;">${item.color_name}</span></p>` 
                        : `<p class="item-color">Color: <span style="opacity:0.6;">N/A</span></p>`;

                    // Calculate subtotal
                    const subtotal = (item.price * item.quantity).toFixed(2);

                    html += `
                    <div class="cart-item" data-cart-id="${item.cart_id}">
                        <input type="checkbox" class="select-item" data-cart-id="${item.cart_id}" ${isChecked ? "checked" : ""}>
                        <img src="${imageSrc}" alt="${item.name}" width="80" 
                             onerror="this.src='${SITE_URL}uploads/sample1.jpg'">
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
                    </div>
                    `;
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
                // Empty cart
                cartItems.innerHTML = `
                    <div class="empty-cart">
                        <p>Your cart is empty</p>
                        <a href="<?php echo SITE_URL; ?>pages/products.php" class="continue-shopping">Continue Shopping</a>
                    </div>
                `;
                cartTotal.innerHTML = `
                    <div class="total-breakdown">
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <span>₱0.00</span>
                        </div>
                        <div class="total-row">
                            <span>Shipping:</span>
                            <span>₱0.00</span>
                        </div>
                        <div class="total-row grand-total">
                            <span>Total:</span>
                            <span>₱0.00</span>
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading cart:', error);
            document.getElementById('cart-items').innerHTML = `
                <div class="error-cart">
                    <p>Error loading cart. Please try again.</p>
                </div>
            `;
        }
    }

    function attachCartEvents() {
        document.querySelectorAll(".quantity-input").forEach(input => {
            input.addEventListener("change", async () => {
                const cartId = input.closest(".cart-item").dataset.cartId;
                const quantity = input.value;
                const size = input.closest(".cart-item").querySelector(".size-select").value;
                await updateCart(cartId, quantity, size);
                updateSubtotal(cartId, quantity);
                updateTotalOnSelection();
            });
        });

        document.querySelectorAll(".size-select").forEach(select => {
            select.addEventListener("change", async () => {
                const cartId = select.closest(".cart-item").dataset.cartId;
                const quantity = select.closest(".cart-item").querySelector(".quantity-input").value;
                const size = select.value;
                await updateCart(cartId, quantity, size);
                updateSubtotal(cartId, quantity);
            });
        });

        document.querySelectorAll(".remove-item").forEach(btn => {
            btn.addEventListener("click", async () => {
                const cartId = btn.closest(".cart-item").dataset.cartId;
                await removeCartItem(cartId);
                delete checkboxStates[cartId];
                loadCart();
            });
        });

        document.querySelectorAll(".select-item").forEach(checkbox => {
            checkbox.addEventListener("change", () => {
                checkboxStates[checkbox.dataset.cartId] = checkbox.checked;
                updateTotalOnSelection();
                updateSelectAllState();
            });
        });

        document.getElementById("select-all").addEventListener("change", function() {
            const checkboxes = document.querySelectorAll(".select-item");
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                checkboxStates[checkbox.dataset.cartId] = this.checked;
            });
            updateTotalOnSelection();
        });

        document.getElementById("remove-selected").addEventListener("click", async () => {
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
            }
        });
    }

    function updateSelectAllState() {
        const checkboxes = document.querySelectorAll(".select-item");
        const selectAll = document.getElementById("select-all");
        if (checkboxes.length > 0) {
            selectAll.checked = Array.from(checkboxes).every(cb => cb.checked);
        } else {
            selectAll.checked = false;
        }
    }

    function getSelectedCartIds() {
        const selectedItems = [];
        document.querySelectorAll(".select-item:checked").forEach(checkbox => {
            selectedItems.push(checkbox.dataset.cartId);
        });
        return selectedItems;
    }

    async function updateCart(cartId, quantity, size) {
        try {
            await fetch(SITE_URL + "actions/cart-update.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `cart_id=${cartId}&quantity=${quantity}&size=${size}`
            });
        } catch (error) {
            console.error('Error updating cart:', error);
        }
    }

    async function removeCartItem(cartId) {
        try {
            await fetch(SITE_URL + "actions/cart-remove.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `cart_id=${cartId}`
            });
        } catch (error) {
            console.error('Error removing cart item:', error);
        }
    }

    function updateSubtotal(cartId, quantity) {
        const cartItem = document.querySelector(`.cart-item[data-cart-id="${cartId}"]`);
        if (cartItem) {
            const price = parseFloat(cartItem.querySelector(".item-price").innerText.replace("Price: ₱", ""));
            cartItem.querySelector(".item-subtotal").innerText = (price * quantity).toFixed(2);
            updateTotalOnSelection();
        }
    }

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll(".cart-item").forEach(item => {
            const checkbox = item.querySelector(".select-item");
            if (checkbox && checkbox.checked) {
                const price = parseFloat(item.querySelector(".item-price").innerText.replace("Price: ₱", ""));
                const qty = parseInt(item.querySelector(".quantity-input").value);
                total += price * qty;
            }
        });
        return total;
    }

    function updateTotalOnSelection() {
        const total = calculateTotal();
        const shipping = total > 500 ? 0 : 50;
        const grandTotal = total + shipping;
        
        document.getElementById("cart-total").innerHTML = `
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

    document.getElementById("checkout-btn").addEventListener("click", async () => {
        const selectedItems = getSelectedCartIds();
        if (selectedItems.length === 0) {
            alert("Please select at least one item to checkout.");
            return;
        }

        try {
            await fetch(SITE_URL + "actions/clear-buy-now.php", {
                method: "POST",
                credentials: "include"
            });

            const response = await fetch(SITE_URL + "actions/cart-checkout.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `cart_ids=${selectedItems.join(",")}`,
                credentials: "include"
            });
            
            const result = await response.json();
            if (result.status === "success") {
                setTimeout(() => {
                    window.location.href = SITE_URL + "pages/checkout.php";
                }, 500);
            } else {
                alert("Error: " + (result.message || "Failed to proceed to checkout"));
            }
        } catch (error) {
            console.error("Checkout error:", error);
            alert("Network error. Please try again.");
        }
    });

    loadCart();
});
</script>