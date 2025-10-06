document.addEventListener("DOMContentLoaded", () => {
  const loginModal = document.getElementById("profile-modal");
  const loginForm = document.getElementById("login-form");

  function showLoginModal() {
    if (loginModal) {
      loginModal.style.display = "flex";
      if (loginForm) loginForm.scrollIntoView({ behavior: "smooth" });
    }
  }

  document.querySelectorAll(".add-to-cart").forEach(btn => {
    btn.addEventListener("click", async () => {
      const productId = btn.dataset.id;
      if (!productId) return;

      try {
        const response = await fetch(SITE_URL + "actions/add-to-cart.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "product_id=" + encodeURIComponent(productId)
        });

        const result = await response.json();

        if (result.status === "success") {
          alert("✅ Product added to cart successfully!");
        } else if (result.status === "exists") {
          alert("🛒 This product is already in your cart.");
        } else if (result.message === "not_logged_in") {
          showLoginModal();
        } else {
          alert("⚠️ Something went wrong.");
        }
      } catch (error) {
        console.error(error);
        alert("⚠️ Network error.");
      }
    });
  });

  document.querySelectorAll(".require-login").forEach(btn => {
    btn.addEventListener("click", e => {
      e.preventDefault();
      showLoginModal();
    });
  });

  window.addEventListener("click", e => {
    if (e.target === loginModal) {
      loginModal.style.display = "none";
    }
  });
  // After successfully adding to cart, update the badge
async function updateCartAfterAdd() {
    try {
        const res = await fetch(SITE_URL + "actions/cart-fetch.php");
        const data = await res.json();
        
        if (data.status === "success") {
            const cartCount = document.getElementById("cart-count");
            if (cartCount) {
                cartCount.textContent = data.cart.length;
            }
        }
    } catch (e) {
        console.error("Error updating cart badge:", e);
    }
}

// After successfully adding an item to cart
async function handleAddToCart() {
    // Your existing add to cart code...
    
    // Then update the badge
    if (window.updateCartBadgeGlobal) {
        await window.updateCartBadgeGlobal();
    } else {
        // Fallback: reload the badge
        const res = await fetch(SITE_URL + "actions/cart-count.php");
        const data = await res.json();
        const cartCount = document.getElementById("cart-count");
        if (cartCount) {
            cartCount.textContent = data.count || 0;
        }
    }
}
// Call this after adding an item to cart
});
