document.addEventListener('DOMContentLoaded', () => {
  const toggles = document.querySelectorAll('.dropdown-toggle');

  toggles.forEach((toggle) => {
    toggle.addEventListener('touchstart', (e) => {
      const parentLi = toggle.closest('.has-dropdown');

      // Prevent link navigation
      e.preventDefault();

      // Close other dropdowns
      document.querySelectorAll('.has-dropdown.open').forEach((openLi) => {
        if (openLi !== parentLi) openLi.classList.remove('open');
      });

      // Toggle this dropdown
      parentLi.classList.toggle('open');
    });
  });
});
