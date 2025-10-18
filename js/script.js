document.addEventListener("DOMContentLoaded", () => {
  console.log("🔧 Script.js loaded - Checking elements...");

  // Safe element selection with null checks
  const dropdownParents = document.querySelectorAll(".has-dropdown");
  const searchIcon = document.getElementById("search-icon");
  const searchDropdown = document.querySelector(".search-dropdown");
  const profileIcon = document.getElementById("profile-icon");
  const modal = document.getElementById("profile-modal");
  const loginForm = document.getElementById("login-form");
  const registerForm = document.getElementById("register-form");
  const verifyForm = document.getElementById("verify-form");
  const verifyEmailInput = document.getElementById("verify-email");
  const backToTopButton = document.getElementById('backToTop');
  const showRegister = document.getElementById("show-register");
  const showLogin = document.getElementById("show-login");
  const closeModal = document.getElementById("close-modal");

  // Debug logging
  console.log("📋 Elements found:", {
    profileIcon: profileIcon ? "✅ Found" : "❌ Not found (user is logged in)",
    closeModal: closeModal ? "✅ Found" : "❌ Not found",
    showRegister: showRegister ? "✅ Found" : "❌ Not found", 
    showLogin: showLogin ? "✅ Found" : "❌ Not found",
    modal: modal ? "✅ Found" : "❌ Not found",
    SITE_URL: typeof SITE_URL !== 'undefined' ? "✅ Defined" : "❌ Not defined"
  });

  // Cart badge functionality with SITE_URL fallback
  async function updateCartBadge() {
    try {
      // Use SITE_URL if defined, otherwise use relative path
      const baseUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '../';
      const res = await fetch(baseUrl + "actions/cart-fetch.php");
      const data = await res.json();
      if (data.status === "success") {
        const cartCount = document.getElementById("cart-count");
        if (cartCount) {
          let totalQuantity = 0;
          data.cart.forEach(item => totalQuantity += parseInt(item.quantity));
          cartCount.textContent = totalQuantity;
        }
      }
    } catch (e) {
      console.error("Error updating cart badge", e);
    }
  }

  // Update cart badge on cart interactions
  document.addEventListener("click", async (e) => {
    if (e.target.matches(".add-to-cart, .remove-item, .quantity-input, .size-select")) {
      setTimeout(updateCartBadge, 300);
    }
  });

  // Initialize cart badge
  updateCartBadge();

  // Modal functionality - ONLY if elements exist
  if (closeModal && modal) {
    closeModal.addEventListener("click", () => {
      modal.style.display = "none";
    });

    modal.addEventListener("click", (e) => {
      if (e.target === modal) {
        modal.style.display = "none";
      }
    });
  } else {
    console.log("ℹ️ Modal elements not found - user might be logged in");
  }

  // Dropdown hover handling
  dropdownParents.forEach(parent => {
    let hideTimeout;

    parent.addEventListener("mouseenter", () => {
      clearTimeout(hideTimeout);
      parent.classList.add("open");
      document.body.classList.add("no-scroll");
    });

    parent.addEventListener("mouseleave", () => {
      hideTimeout = setTimeout(() => {
        parent.classList.remove("open");
        document.body.classList.remove("no-scroll");
      }, 100);
    });
  });

  // Search functionality - ONLY if elements exist
  if (searchIcon && searchDropdown) {
    searchIcon.addEventListener("click", (e) => {
      e.preventDefault();
      searchDropdown.classList.add("open");
    });

    searchDropdown.addEventListener("click", (e) => {
      if (e.target === searchDropdown) {
        searchDropdown.classList.remove("open");
      }
    });
  }

  // Profile Modal - ONLY if elements exist (only for logged out users)
  if (profileIcon && modal) {
    console.log("👤 Setting up profile modal for logged out user");
    profileIcon.addEventListener("click", (e) => {
      e.preventDefault();
      modal.style.display = "flex";
    });
  } else {
    console.log("ℹ️ Profile icon not found - user is logged in");
  }

  // Form switching - ONLY if elements exist
  if (showRegister && loginForm && registerForm && verifyForm) {
    showRegister.addEventListener("click", (e) => {
      e.preventDefault();
      loginForm.classList.add("hidden");
      registerForm.classList.remove("hidden");
      verifyForm.classList.add("hidden");
    });
  }

  if (showLogin && loginForm && registerForm && verifyForm) {
    showLogin.addEventListener("click", (e) => {
      e.preventDefault();
      registerForm.classList.add("hidden");
      loginForm.classList.remove("hidden");
      verifyForm.classList.add("hidden");
    });
  }

  // Handle registration via AJAX - ONLY if form exists
  if (registerForm) {
    registerForm.querySelector("form").addEventListener("submit", async (e) => {
      e.preventDefault();

      const formData = new FormData(e.target);

      try {
        let response = await fetch(SITE_URL + "auth/register.php", {
          method: "POST",
          body: formData
        });

        let result = await response.json();

        if (result.status === "success") {
          registerForm.classList.add("hidden");
          if (verifyForm) verifyForm.classList.remove("hidden");
          if (verifyEmailInput) verifyEmailInput.value = result.email;
          alert(result.message);
        } else {
          alert(result.message);
        }
      } catch (err) {
        console.error("Registration error:", err);
        alert("Unexpected response from server.");
      }
    });
  }

  // Back to Top button - ONLY if element exists
  if (backToTopButton) {
    window.addEventListener('scroll', () => {
      if (window.pageYOffset > 300) {
        backToTopButton.classList.add('visible');
      } else {
        backToTopButton.classList.remove('visible');
      }
    });

    backToTopButton.addEventListener('click', () => {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  }

  console.log("✅ Script.js initialization complete");
});