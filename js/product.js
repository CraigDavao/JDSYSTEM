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
});
