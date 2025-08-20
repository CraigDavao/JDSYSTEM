document.addEventListener("DOMContentLoaded", () => {
  const dropdownParents = document.querySelectorAll(".has-dropdown");
  const searchIcon = document.getElementById("search-icon");
  const searchDropdown = document.querySelector(".search-dropdown");
  
  dropdownParents.forEach(parent => {
    let hideTimeout;

    parent.addEventListener("mouseenter", () => {
      clearTimeout(hideTimeout);
      parent.classList.add("open");
      document.body.classList.add("no-scroll");
    });

    parent.addEventListener("mouseleave", () => {
      // Add a delay before closing (1000ms = 1s)
      hideTimeout = setTimeout(() => {
        parent.classList.remove("open");
        document.body.classList.remove("no-scroll");
      }, 100);
    });
     searchIcon.addEventListener("click", (e) => {
        e.preventDefault(); // prevent page navigation
        searchDropdown.classList.add("open");
    });

    // Close dropdown when clicking outside the form
    searchDropdown.addEventListener("click", (e) => {
        if (e.target === searchDropdown) {
            searchDropdown.classList.remove("open");
        }
    });
  });
});
