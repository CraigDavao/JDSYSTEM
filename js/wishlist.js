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

// ✅ FIXED: Remove item from wishlist
document.addEventListener("click", async (e) => {
    if (e.target.closest(".remove-wishlist")) {
        e.preventDefault();
        e.stopPropagation();
        
        const removeBtn = e.target.closest(".remove-wishlist");
        const wishlistId = removeBtn.dataset.id;
        const itemElement = removeBtn.closest('.wishlist-item');
        
        if (confirm('Remove this item from your wishlist?')) {
            try {
                console.log('Removing wishlist item ID:', wishlistId);
                
                const formData = new URLSearchParams();
                formData.append("wishlist_id", wishlistId);
                
                const res = await fetch("<?= SITE_URL; ?>actions/wishlist-remove.php", {
                    method: "POST",
                    headers: {"Content-Type": "application/x-www-form-urlencoded"},
                    body: formData
                });
                
                const result = await res.text();
                console.log('Remove response:', result);
                
                if (result.trim() === "success") {
                    itemElement.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(() => {
                        itemElement.remove();
                        updateWishlistUI();
                    }, 300);
                    showNotification('Item removed from wishlist', 'success');
                } else {
                    let errorMessage = 'Failed to remove item';
                    
                    switch(result.trim()) {
                        case 'not_logged_in':
                            errorMessage = 'Please log in to continue';
                            break;
                        case 'not_found':
                            errorMessage = 'Item not found in your wishlist';
                            break;
                        case 'database_error':
                            errorMessage = 'Database error occurred';
                            break;
                        case 'invalid_id':
                            errorMessage = 'Invalid item ID';
                            break;
                        case 'invalid_method':
                            errorMessage = 'Invalid request';
                            break;
                        default:
                            errorMessage = 'Failed to remove item: ' + result;
                    }
                    
                    showNotification(errorMessage, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Network error: ' + error.message, 'error');
            }
        }
    }
});
