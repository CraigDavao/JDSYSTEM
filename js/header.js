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