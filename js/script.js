document.addEventListener("DOMContentLoaded", () => {
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

  const profileModal = document.getElementById("profile-modal");
  const closeModal = document.getElementById("close-modal");

  async function updateCartBadge() {
    try {
        const res = await fetch(SITE_URL + "actions/cart-fetch.php");
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

// Call updateCartBadge() whenever cart changes
document.addEventListener("click", async (e) => {
    if (e.target.matches(".add-to-cart, .remove-item, .quantity-input, .size-select")) {
        setTimeout(updateCartBadge, 300); // small delay for server update
    }
});


  updateCartBadge(); // Run immediately on page load

  // Close modal on X button click
  closeModal.addEventListener("click", () => {
    profileModal.style.display = "none";
  });

  profileModal.addEventListener("click", (e) => {
    if (e.target === profileModal) {
      profileModal.style.display = "none";
    }
  });

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

  // Search icon open
  searchIcon.addEventListener("click", (e) => {
    e.preventDefault();
    searchDropdown.classList.add("open");
  });

  searchDropdown.addEventListener("click", (e) => {
    if (e.target === searchDropdown) {
      searchDropdown.classList.remove("open");
    }
  });

  // Profile Modal
  profileIcon.addEventListener("click", (e) => {
    e.preventDefault();
    modal.style.display = "flex";
  });

  modal.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.style.display = "none";
    }
  });

  // Switch to Register
  showRegister.addEventListener("click", (e) => {
    e.preventDefault();
    loginForm.classList.add("hidden");
    registerForm.classList.remove("hidden");
    verifyForm.classList.add("hidden");
  });

  // Switch back to Login
  showLogin.addEventListener("click", (e) => {
    e.preventDefault();
    registerForm.classList.add("hidden");
    loginForm.classList.remove("hidden");
    verifyForm.classList.add("hidden");
  });

  // Handle registration via AJAX
  if (registerForm) {
    registerForm.querySelector("form").addEventListener("submit", async (e) => {
      e.preventDefault();

      const formData = new FormData(e.target);

      let response = await fetch("auth/register.php", {
        method: "POST",
        body: formData
      });

      let result;
      try {
        result = await response.json();
      } catch (err) {
        alert("Unexpected response from server.");
        return;
      }

      if (result.status === "success") {
        registerForm.classList.add("hidden");
        verifyForm.classList.remove("hidden");
        verifyEmailInput.value = result.email;
        alert(result.message);
      } else {
        alert(result.message);
      }
    });
  }

  // Back to Top button
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
});
