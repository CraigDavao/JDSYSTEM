document.addEventListener("DOMContentLoaded", () => {
    let checkboxStates = {};

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

    // Always update badge on every page
    updateCartBadge();

    if (document.getElementById("cart-items")) {
        loadCart();
    }

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
                        <h3>${item.name}</h3>
                        <p class="item-price">₱${item.price}</p>
                        <p>Size: 
                            <select class="size-select">
                                <option value="S" ${item.size === "S" ? "selected" : ""}>S</option>
                                <option value="M" ${item.size === "M" ? "selected" : ""}>M</option>
                                <option value="L" ${item.size === "L" ? "selected" : ""}>L</option>
                            </select>
                        </p>
                        <p>Quantity: <input type="number" class="quantity-input" value="${item.quantity}" min="1"></p>
                        <p>Subtotal: ₱<span class="item-subtotal">${item.subtotal}</span></p>
                        <button class="remove-item">Remove</button>
                    </div>
                    `;
                });

                cartItems.innerHTML = html;
                cartTotal.innerHTML = `<h3>Total: ₱${calculateTotal()}</h3>`;
                attachCartEvents(); // <-- Attach listeners every time cart reloads
            } else {
                updateCartCount([]);
                cartItems.innerHTML = `<div class="cart-empty"><h3>Your cart is empty</h3></div>`;
                cartTotal.innerHTML = '';
            }
        } catch (error) {
            cartItems.innerHTML = '<div class="cart-loading">Error loading cart.</div>';
        }
    }

    function attachCartEvents() {
        document.querySelectorAll(".quantity-input").forEach(input => {
            input.addEventListener("change", async () => {
                const cartItem = input.closest(".cart-item");
                const cartId = cartItem.dataset.cartId;
                const quantity = parseInt(input.value) || 1;
                const size = cartItem.querySelector(".size-select").value;

                cartItem.classList.add('updating');
                await updateCart(cartId, quantity, size);
                updateSubtotal(cartId, quantity, size);
                updateCartCountFromPage();
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
            });
        });
    }

    async function updateCart(cartId, quantity, size) {
        await fetch(SITE_URL + "actions/cart-update.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `cart_id=${cartId}&quantity=${quantity}&size=${size}`
        });
        updateCartBadge();
    }

    async function removeCartItem(cartId) {
        await fetch(SITE_URL + "actions/cart-remove.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `cart_id=${cartId}`
        });
        updateCartBadge();
    }

    function updateSubtotal(cartId, quantity, size) {
        const cartItem = document.querySelector(`.cart-item[data-cart-id="${cartId}"]`);
        const price = parseFloat(cartItem.querySelector(".item-price").innerText.replace("₱", ""));
        cartItem.querySelector(".item-subtotal").innerText = (price * quantity).toFixed(2);
        updateTotalOnSelection();
    }

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
        return total.toFixed(2);
    }

    function updateTotalOnSelection() {
        document.getElementById("cart-total").innerHTML = `<h3>Total: ₱${calculateTotal()}</h3>`;
    }

        function updateCartCount(cart) {
            const cartCount = document.getElementById("cart-count");
            if (cartCount) {
                cartCount.textContent = cart.length; // counts unique products
            }
        }


    async function updateCartBadge() {
    try {
        const res = await fetch(SITE_URL + "actions/cart-fetch.php");
        const data = await res.json();
        if (data.status === "success") {
            updateCartCount(data.cart);
        }
    } catch (e) {
        console.error("Error updating cart badge", e);
    }
}


    function updateCartCountFromPage() {
        const cartCount = document.getElementById("cart-count");
        if (cartCount) {
            let totalQuantity = 0;
            document.querySelectorAll(".quantity-input").forEach(input => {
                totalQuantity += parseInt(input.value) || 1;
            });
            cartCount.textContent = totalQuantity;
        }
    }

    document.getElementById("checkout-btn")?.addEventListener("click", () => {
        const selectedItems = [];
        document.querySelectorAll(".select-item:checked").forEach(checkbox => {
            selectedItems.push(checkbox.dataset.cartId);
        });

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

    updateCartBadge();
    if (document.getElementById("cart-items")) {
        loadCart();
    }
});
