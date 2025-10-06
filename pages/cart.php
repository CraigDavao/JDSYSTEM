<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>css/cart.css?v=<?= time(); ?>">

<div class="cart-dashboard">
    <h2>My Cart</h2>
    <div id="cart-items"></div>
    <div id="cart-total"></div>
    <button id="checkout-btn">Checkout Selected</button>
</div>

<script src="<?php echo SITE_URL; ?>js/cart.js?v=<?= time(); ?>"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    let checkboxStates = {};

    async function loadCart() {
        const res = await fetch(SITE_URL + "actions/cart-fetch.php");
        const data = await res.json();
        const cartItems = document.getElementById("cart-items");
        const cartTotal = document.getElementById("cart-total");

        if (data.status === "success") {
            let html = "";
            data.cart.forEach(item => {
                const isChecked = checkboxStates[item.cart_id] ?? true;
                html += `
                <div class="cart-item" data-cart-id="${item.cart_id}">
                    <input type="checkbox" class="select-item" data-cart-id="${item.cart_id}" ${isChecked ? "checked" : ""}>
                    <img src="${SITE_URL}uploads/${item.image}" alt="${item.name}" width="80">
                    <h3>${item.name}</h3>
                    <p class="item-price">Price: ₱${item.price}</p>
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

            attachCartEvents();
        }
    }

    function attachCartEvents() {
        document.querySelectorAll(".quantity-input").forEach(input => {
            input.addEventListener("change", async () => {
                const cartId = input.closest(".cart-item").dataset.cartId;
                const quantity = input.value;
                const size = input.closest(".cart-item").querySelector(".size-select").value;
                await updateCart(cartId, quantity, size);
                updateSubtotal(cartId, quantity, size);
                updateTotalOnSelection();
            });
        });

        document.querySelectorAll(".size-select").forEach(select => {
            select.addEventListener("change", async () => {
                const cartId = select.closest(".cart-item").dataset.cartId;
                const quantity = select.closest(".cart-item").querySelector(".quantity-input").value;
                const size = select.value;
                await updateCart(cartId, quantity, size);
                updateSubtotal(cartId, quantity, size);
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
            });
        });
    }

    async function updateCart(cartId, quantity, size) {
        await fetch(SITE_URL + "actions/cart-update.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `cart_id=${cartId}&quantity=${quantity}&size=${size}`
        });
    }

    async function removeCartItem(cartId) {
        await fetch(SITE_URL + "actions/cart-remove.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `cart_id=${cartId}`
        });
    }

    function updateSubtotal(cartId, quantity, size) {
        const cartItem = document.querySelector(`.cart-item[data-cart-id="${cartId}"]`);
        const price = parseFloat(cartItem.querySelector(".item-price").innerText.replace("Price: ₱", ""));
        cartItem.querySelector(".item-subtotal").innerText = (price * quantity).toFixed(2);
        updateTotalOnSelection();
    }

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll(".cart-item").forEach(item => {
            const checkbox = item.querySelector(".select-item");
            if (checkbox.checked) {
                const price = parseFloat(item.querySelector(".item-price").innerText.replace("Price: ₱", ""));
                const qty = parseInt(item.querySelector(".quantity-input").value);
                total += price * qty;
            }
        });
        return total.toFixed(2);
    }

    function updateTotalOnSelection() {
        document.getElementById("cart-total").innerHTML = `<h3>Total: ₱${calculateTotal()}</h3>`;
    }

    document.getElementById("checkout-btn").addEventListener("click", () => {
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

    loadCart();
});
</script>
