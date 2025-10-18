// ✅ product.js
document.addEventListener("DOMContentLoaded", () => {
  // Automatically detect your local base URL
  const SITE_URL = window.location.origin + "/JDSystem/";

  const loginModal = document.getElementById("profile-modal");

  // 🟣 Show login modal
  function showLoginModal() {
    if (loginModal) {
      loginModal.style.display = "flex";
      document.body.style.overflow = "hidden"; // Prevent scrolling
      
      // Show login form and hide others
      const loginForm = document.getElementById("login-form");
      const registerForm = document.getElementById("register-form");
      const verifyForm = document.getElementById("verify-form");
      
      if (loginForm) loginForm.classList.remove("hidden");
      if (registerForm) registerForm.classList.add("hidden");
      if (verifyForm) verifyForm.classList.add("hidden");
      
      // Scroll to login form
      if (loginForm) loginForm.scrollIntoView({ behavior: "smooth" });
    } else {
      console.warn("⚠️ Login modal not found in DOM.");
      // Fallback: redirect to login page
      window.location.href = SITE_URL + "auth/login.php";
    }
  }

  // 🔵 Close login modal when clicking outside or close button
  function setupModalClose() {
    // Close when clicking outside
    window.addEventListener("click", (e) => {
      if (e.target === loginModal) {
        closeLoginModal();
      }
    });

    // Close when clicking close button
    const closeBtn = document.getElementById("close-modal");
    if (closeBtn) {
      closeBtn.addEventListener("click", closeLoginModal);
    }

    // Close with Escape key
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && loginModal.style.display === "flex") {
        closeLoginModal();
      }
    });
  }

  function closeLoginModal() {
    if (loginModal) {
      loginModal.style.display = "none";
      document.body.style.overflow = "auto"; // Re-enable scrolling
    }
  }

  // Initialize modal close functionality
  setupModalClose();

  // 🛒 ADD TO CART
  document.querySelectorAll(".add-to-cart").forEach((btn) => {
    btn.addEventListener("click", async () => {
      const productId = btn.dataset.id;
      if (!productId) return;

      try {
        const response = await fetch(`${SITE_URL}actions/cart-add.php`, {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: `product_id=${encodeURIComponent(productId)}`,
          credentials: "include",
        });

        const result = await response.json();

        // ✅ Handle responses
        if (result.status === "success") {
          alert("✅ Product added to cart successfully!");
          updateCartAfterAdd();
        } else if (result.status === "exists") {
          alert("🛒 This product is already in your cart.");
        } else if (
          result.message === "Please log in first." ||
          result.message === "not_logged_in" ||
          result.status === "not_logged_in"
        ) {
          showLoginModal();
        } else {
          alert(result.message || "⚠️ Something went wrong.");
        }
      } catch (error) {
        console.error("Cart Error:", error);
        alert("⚠️ Network error.");
      }
    });
  });

  // 🟢 UPDATE CART BADGE
  async function updateCartAfterAdd() {
    try {
      const res = await fetch(`${SITE_URL}actions/cart-fetch.php`, {
        credentials: "include",
      });
      const data = await res.json();

      if (data.status === "success" && Array.isArray(data.cart)) {
        const cartCount = document.getElementById("cart-count");
        if (cartCount) {
          cartCount.textContent = data.cart.length;
        }
      }
    } catch (e) {
      console.error("Error updating cart badge:", e);
    }
  }

  // 💖 WISHLIST FEATURE
  document.querySelectorAll(".add-to-wishlist, .wishlist-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      const productId = this.dataset.id;
      if (!productId) return;

      const originalText = this.textContent;
      const originalColor = this.style.color;
      this.textContent = "Adding...";
      this.disabled = true;

      fetch(`${SITE_URL}actions/wishlist-add.php`, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `product_id=${encodeURIComponent(productId)}`,
        credentials: "include",
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.status === "success") {
            this.textContent = "♥ Added to Wishlist";
            this.style.color = "red";
            updateWishlistCount();
          } else if (
            data.message === "Please log in first." ||
            data.message === "not_logged_in" ||
            data.status === "not_logged_in"
          ) {
            showLoginModal();
            this.textContent = originalText;
            this.style.color = originalColor;
          } else if (data.status === "exists") {
            this.textContent = "♥ Already in Wishlist";
            this.style.color = "red";
          } else {
            this.textContent = originalText;
            this.style.color = originalColor;
            alert(data.message || "Something went wrong.");
          }
        })
        .catch((err) => {
          console.error("Wishlist Error:", err);
          this.textContent = originalText;
          alert("Something went wrong. Try again.");
        })
        .finally(() => {
          this.disabled = false;
        });
    });
  });

  // 💌 UPDATE WISHLIST BADGE
  function updateWishlistCount() {
    fetch(`${SITE_URL}actions/wishlist-count.php`, {
      credentials: "include",
    })
      .then((res) => res.json())
      .then((data) => {
        const badge = document.getElementById("wishlist-count");
        if (badge) badge.textContent = data.count ?? 0;
      })
      .catch((err) => console.error("Error updating wishlist badge:", err));
  }

  // ✅ Initialize counts when page loads
  updateWishlistCount();
  updateCartAfterAdd();
});