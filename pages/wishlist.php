<?php
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo "<p style='text-align:center;margin-top:4rem;'>Please <a href='" . SITE_URL . "pages/login.php'>log in</a> to view your wishlist.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$sql = "SELECT w.id AS wishlist_id, p.id, p.name, p.image 
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        WHERE w.user_id = ?
        ORDER BY w.added_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= SITE_URL; ?>css/wishlist.css?v=<?= time(); ?>">
  <title>My Wishlist</title>
</head>
<body>

  <div class="wishlist-header">
    <h1 class="wishlist-title">My Wishlist</h1>
  </div>

  <div id="wishlist-items">
    <?php if ($result->num_rows > 0): ?>
      <?php while ($item = $result->fetch_assoc()): ?>
        <div class="wishlist-item">
          <a href="<?= SITE_URL; ?>pages/product.php?id=<?= $item['id'] ?>" class="wishlist-link">
            <img src="<?= SITE_URL; ?>uploads/<?= htmlspecialchars($item['image']); ?>" alt="<?= htmlspecialchars($item['name']); ?>">
            <h3><?= htmlspecialchars($item['name']); ?></h3>
          </a>
          <button class="remove-wishlist" data-id="<?= $item['wishlist_id']; ?>">Remove</button>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="wishlist-empty">
        <h3>Your wishlist is empty.</h3>
        <p>Start adding products you love!</p>
        <a href="<?= SITE_URL; ?>pages/new.php" class="continue-shopping">Continue Shopping</a>
      </div>
    <?php endif; ?>
  </div>

  <?php require_once __DIR__ . '/../includes/footer.php'; ?>

  <script>
  document.addEventListener("click", async (e) => {
      if (e.target.classList.contains("remove-wishlist")) {
          const id = e.target.dataset.id;
          const res = await fetch("<?= SITE_URL; ?>actions/wishlist-remove.php", {
              method: "POST",
              headers: {"Content-Type": "application/x-www-form-urlencoded"},
              body: "id=" + id
          });
          const text = await res.text();
          if (text.trim() === "success") {
              e.target.closest(".wishlist-item").remove();
              if (!document.querySelector(".wishlist-item")) {
                  document.getElementById("wishlist-items").innerHTML = `
                      <div class="wishlist-empty">
                        <h3>Your wishlist is empty.</h3>
                        <p>Start adding products you love!</p>
                        <a href='<?= SITE_URL; ?>pages/new.php' class='continue-shopping'>Continue Shopping</a>
                      </div>`;
              }
          }
      }
  });
  </script>
</body>
</html>


