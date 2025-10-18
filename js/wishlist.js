document.addEventListener("DOMContentLoaded", () => {
  const wishlistCount = document.getElementById("wishlist-count");

  // Safety check
  if (!wishlistCount) return;

  // 🔧 Fetch the wishlist count
  function updateWishlistCount() {
    fetch("actions/wishlist-count.php") // ✅ no leading slash
      .then(response => {
        if (!response.ok) throw new Error("Network response was not ok");
        return response.json();
      })
      .then(data => {
        // ✅ Update badge number
        wishlistCount.textContent = data.count ?? 0;
      })
      .catch(error => {
        console.error("❌ Error fetching wishlist count:", error);
        wishlistCount.textContent = "0";
      });

      
  }

  // Run immediately
  updateWishlistCount();

  // Optional: update every 10 seconds
  setInterval(updateWishlistCount, 10000);
});
