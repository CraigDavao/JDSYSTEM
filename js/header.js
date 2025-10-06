let lastScrollY = window.scrollY;
  const navbar = document.querySelector("nav.site-nav");

  window.addEventListener("scroll", () => {
    if (window.scrollY > lastScrollY) {
      navbar.classList.add("hidden"); // hide on scroll down
    } else {
      navbar.classList.remove("hidden"); // show on scroll up
    }
    lastScrollY = window.scrollY;
  });

  

  