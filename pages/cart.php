<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>css/cart.css?v=<?= time(); ?>">

<div class="cart-dashboard">
    <h2>My Cart</h2>
    
    <div class="cart-layout">
        <!-- Left Column - Cart Items -->
        <div class="cart-items-column">
            <!-- Cart Actions -->
            <div class="cart-actions">
                <label class="select-all-label">
                    <input type="checkbox" id="select-all"> Select All
                </label>
                <button class="btn-remove-selected" id="remove-selected">Remove Selected</button>
            </div>
            
            <!-- Cart Header -->
            <div class="cart-header">
                <div></div>
                <div>Image</div>
                <div>Product</div>
                <div>Controls</div>
                <div>Subtotal</div>
                <div>Action</div>
            </div>
            
            <!-- Cart Items -->
            <div id="cart-items"></div>
        </div>
        
        <!-- Right Column - Cart Summary -->
        <div class="cart-summary-column">
            <h3>Order Summary</h3>
            <div id="cart-total"></div>
            <div class="shipping-notice">
                <p>🚚 Free shipping on orders over ₱500</p>
            </div>
            <button id="checkout-btn">Proceed to Checkout</button>
        </div>
    </div>
</div>

<script>
const SITE_URL = "<?= SITE_URL; ?>";
</script>

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

                    // Calculate subtotal
                    const subtotal = (item.price * item.quantity).toFixed(2);

                    html += `
                    <div class="cart-item" data-cart-id="${item.cart_id}">
                        <input type="checkbox" class="select-item" data-cart-id="${item.cart_id}" ${isChecked ? "checked" : ""}>
                        <img src="${imageSrc}" alt="${item.name}" 
                             onerror="this.src='${SITE_URL}uploads/sample1.jpg'">
                        <div class="product-info">
                            <h3 class="product-name">${item.name}</h3>
                            <div class="product-details">
                                <span class="variant-color">${item.color_name || 'N/A'}</span>
                                <span class="product-price">₱${parseFloat(item.price).toFixed(2)}</span>
                            </div>
                        </div>
                        <div class="item-controls">
                            <div class="control-group">
                                <span class="control-label">Size</span>
                                <select class="size-select">
                                    <option value="S" ${item.size === "S" ? "selected" : ""}>S</option>
                                    <option value="M" ${item.size === "M" ? "selected" : ""}>M</option>
                                    <option value="L" ${item.size === "L" ? "selected" : ""}>L</option>
                                    <option value="XL" ${item.size === "XL" ? "selected" : ""}>XL</option>
                                </select>
                            </div>
                            <div class="control-group">
                                <span class="control-label">Qty</span>
                                <input type="number" class="quantity-input" value="${item.quantity}" min="1" max="10">
                            </div>
                        </div>
                        <div class="subtotal">₱<span class="item-subtotal">${subtotal}</span></div>
                        <button class="remove-item">Remove</button>
                    </div>
                    `;
                });

                cartItems.innerHTML = html;
                
                const total = calculateTotal();
                const shipping = total > 500 ? 0 : 50;
                const grandTotal = total + shipping;
                
                cartTotal.innerHTML = `
                    <div class="total-section">
                        <h4>Order Total</h4>
                        <div class="total-breakdown">
                            <div class="total-row">
                                <span class="total-label">Subtotal:</span>
                                <span class="total-value">₱${total.toFixed(2)}</span>
                            </div>
                            <div class="total-row">
                                <span class="total-label">Shipping:</span>
                                <span class="total-value">${shipping === 0 ? 'FREE' : '₱' + shipping.toFixed(2)}</span>
                            </div>
                            <div class="total-row grand-total">
                                <span class="total-label">Total:</span>
                                <span class="total-value">₱${grandTotal.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                `;

                attachCartEvents();
                updateSelectAllState();
            } else {
                // Empty cart
                cartItems.innerHTML = `
                    <div class="cart-empty">
                        <h3>Your cart is empty</h3>
                        <p>Add some products to get started</p>
                        <a href="<?php echo SITE_URL; ?>pages/products.php" class="btn-continue-shopping">Continue Shopping</a>
                    </div>
                `;
                cartTotal.innerHTML = `
                    <div class="total-section">
                        <h4>Order Total</h4>
                        <div class="total-breakdown">
                            <div class="total-row">
                                <span class="total-label">Subtotal:</span>
                                <span class="total-value">₱0.00</span>
                            </div>
                            <div class="total-row">
                                <span class="total-label">Shipping:</span>
                                <span class="total-value">₱0.00</span>
                            </div>
                            <div class="total-row grand-total">
                                <span class="total-label">Total:</span>
                                <span class="total-value">₱0.00</span>
                            </div>
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading cart:', error);
            document.getElementById('cart-items').innerHTML = `
                <div class="cart-loading">
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
            const price = parseFloat(cartItem.querySelector(".product-price").innerText.replace("₱", ""));
            cartItem.querySelector(".item-subtotal").innerText = (price * quantity).toFixed(2);
            updateTotalOnSelection();
        }
    }

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll(".cart-item").forEach(item => {
            const checkbox = item.querySelector(".select-item");
            if (checkbox && checkbox.checked) {
                const price = parseFloat(item.querySelector(".product-price").innerText.replace("₱", ""));
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
            <div class="total-section">
                <h4>Order Total</h4>
                <div class="total-breakdown">
                    <div class="total-row">
                        <span class="total-label">Subtotal:</span>
                        <span class="total-value">₱${total.toFixed(2)}</span>
                    </div>
                    <div class="total-row">
                        <span class="total-label">Shipping:</span>
                        <span class="total-value">${shipping === 0 ? 'FREE' : '₱' + shipping.toFixed(2)}</span>
                    </div>
                    <div class="total-row grand-total">
                        <span class="total-label">Total:</span>
                        <span class="total-value">₱${grandTotal.toFixed(2)}</span>
                    </div>
                </div>
            </div>
        `;
    }

    document.getElementById("checkout-btn").addEventListener("click", async () => {
        const selectedItems = getSelectedCartIds();

        console.log("🛒 Selected cart IDs:", selectedItems);

        if (selectedItems.length === 0) {
            alert("Please select at least one item to checkout.");
            return;
        }

        try {
            // Clear buy-now session (if any)
            await fetch(SITE_URL + "actions/clear-buy-now.php", {
                method: "POST",
                credentials: "include"
            });

            // Proceed to checkout
            const bodyData = new URLSearchParams({
                cart_ids: selectedItems.join(",")
            });

            const response = await fetch(SITE_URL + "actions/cart-checkout.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: bodyData.toString(),
                credentials: "include"
            });

            const result = await response.json();
            console.log("🛒 Checkout response:", result);

            if (result.status === "success") {
                window.location.href = SITE_URL + "pages/checkout.php";
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

<?php
require_once __DIR__ . '/../includes/footer.php';
?>