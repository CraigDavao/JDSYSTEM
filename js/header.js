document.addEventListener("DOMContentLoaded", () => {
    let lastScrollY = window.scrollY;
    const navbar = document.querySelector("nav.site-nav");

    window.addEventListener("scroll", () => {
      if (!navbar) return;
      
      if (window.scrollY > lastScrollY && window.scrollY > 80) {
        navbar.classList.add("hidden"); // Hide when scrolling down
      } else {
        navbar.classList.remove("hidden"); // Show when scrolling up
      }
      lastScrollY = window.scrollY;
    });
});

// Keep your second scroll listener but add null check
let lastScrollY = window.scrollY;
const navbar = document.querySelector("nav.site-nav");

window.addEventListener("scroll", () => {
  if (!navbar) return; // Add this null check
  
  if (window.scrollY > lastScrollY) {
    navbar.classList.add("hidden"); // hide on scroll down
  } else {
    navbar.classList.remove("hidden"); // show on scroll up
  }
  lastScrollY = window.scrollY;
});

// Forgot Password Submission - UPDATED
const forgotPasswordFormData = document.getElementById('forgot-password-form-data');
forgotPasswordFormData?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const email = this.querySelector('input[name="email"]').value.trim();

    if (!email) {
        showFormMessage(this, 'Please enter your email', false);
        return;
    }

    const btn = this.querySelector('button');
    const originalText = btn.textContent;
    btn.textContent = 'Sending...';
    btn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('email', email);

        const response = await fetch(SITE_URL + 'auth/forgot.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        
        if (result.status === 'success') {
            showFormMessage(this, result.message, true);
            // Store email and show code verification form
            document.getElementById('reset-email').value = result.email;
            setTimeout(() => {
                this.reset();
                hideAllForms();
                resetCodeForm.classList.remove('hidden');
            }, 2000);
        } else {
            showFormMessage(this, result.message, false);
        }
    } catch (error) {
        showFormMessage(this, 'Network error. Please try again', false);
    } finally {
        btn.textContent = originalText;
        btn.disabled = false;
    }
});

// Reset Code Verification Submission
const resetCodeFormData = document.getElementById('reset-code-form-data');
resetCodeFormData?.addEventListener('submit', async function (e) {
    e.preventDefault();
    
    const email = this.querySelector('#reset-email').value;
    const code = this.querySelector('input[name="code"]').value.trim();

    if (!code || code.length !== 6) {
        showFormMessage(this, 'Please enter a valid 6-digit code', false);
        return;
    }

    const btn = this.querySelector('button');
    const originalText = btn.textContent;
    btn.textContent = 'Verifying...';
    btn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('email', email);
        formData.append('code', code);

        const response = await fetch(SITE_URL + 'auth/verify-reset-code.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        
        if (result.status === 'success') {
            showFormMessage(this, result.message, true);
            // Store token and show reset password form
            document.getElementById('reset-token').value = result.token;
            setTimeout(() => {
                this.reset();
                hideAllForms();
                resetPasswordForm.classList.remove('hidden');
            }, 2000);
        } else {
            showFormMessage(this, result.message, false);
        }
    } catch (error) {
        showFormMessage(this, 'Network error. Please try again', false);
    } finally {
        btn.textContent = originalText;
        btn.disabled = false;
    }
});

// Add navigation back to forgot password
const showForgotFromCode = document.getElementById('show-forgot-from-code');
showForgotFromCode?.addEventListener('click', (e) => {
    e.preventDefault();
    hideAllForms();
    forgotPasswordForm.classList.remove('hidden');
});