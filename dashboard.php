<?php
require_once 'config.php';
require_once 'connection/connection.php';
require_once __DIR__ . '/includes/header.php';

if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $conn->prepare("SELECT id, fullname, email, remember_token FROM users");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if (password_verify($token, $row['remember_token'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['fullname'];
            $_SESSION['user_email'] = $row['email'];

            header("Location: " . SITE_URL . "dashboard.php");
            exit();
        }
    }
}

if (!isset($_SESSION['user_id'])) {
    header("Location: " . SITE_URL . "index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Account | Jolly Dolly</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/profile.css?v=<?= time(); ?>">
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar Navigation -->
    <nav class="dashboard-sidebar">
      <div class="user-welcome">
        <h3>Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h3>
        <p>Manage your account</p>
      </div>
      
      <ul class="sidebar-nav">
        <li class="nav-item active">
          <i class="fas fa-user-circle"></i>
          <span>Account Overview</span>
        </li>
        <li class="nav-item">
          <i class="fas fa-map-marker-alt"></i>
          <span>Address Book</span>
        </li>
        <li class="nav-item">
          <i class="fas fa-heart"></i>
          <span>Wishlist</span>
        </li>
        <li class="nav-item">
          <i class="fas fa-shopping-bag"></i>
          <span>Order History</span>
        </li>
        <li class="nav-item logout-item">
          <a href="<?php echo SITE_URL; ?>auth/logout.php">
            <i class="fas fa-sign-out-alt"></i>
            <span>Sign Out</span>
          </a>
        </li>
      </ul>
    </nav>

    <!-- Main Content -->
    <main class="dashboard-content">
      <div class="content-header">
        <h1>Account Dashboard</h1>
        <p class="welcome-message">
          Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! 
          <span class="logout-notice">
            Not you? <a href="<?php echo SITE_URL; ?>auth/logout.php" class="logout-link">Sign out</a>
          </span>
        </p>
      </div>

      <!-- First Order CTA -->
      <div class="order-cta-card">
        <div class="cta-icon">
          <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="cta-content">
          <h3>Ready for your first order?</h3>
          <p>Discover our latest collections and start shopping today.</p>
          <a href="<?php echo SITE_URL; ?>pages/new.php" class="cta-button">Start Shopping</a>
        </div>
      </div>

      <!-- Account Information -->
      <div class="account-info-section">
        <h2>Account Information</h2>
        <div class="info-card">
          <div class="info-row">
            <div class="info-label">Full Name</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
          </div>
          <div class="info-row">
            <div class="info-label">Email Address</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['user_email']); ?></div>
          </div>
          <div class="info-row">
            <div class="info-label">Default Shipping Address</div>
            <div class="info-value empty">Not set</div>
          </div>
          <div class="info-row">
            <div class="info-label">Default Billing Address</div>
            <div class="info-value empty">Not set</div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-grid">
          <a href="<?php echo SITE_URL; ?>pages/new.php" class="action-card">
            <i class="fas fa-bag-shopping"></i>
            <span>Continue Shopping</span>
          </a>
          <a href="#" class="action-card">
            <i class="fas fa-heart"></i>
            <span>View Wishlist</span>
          </a>
          <a href="#" class="action-card">
            <i class="fas fa-map-marker-alt"></i>
            <span>Manage Addresses</span>
          </a>
          <a href="#" class="action-card">
            <i class="fas fa-user-edit"></i>
            <span>Edit Profile</span>
          </a>
        </div>
      </div>
    </main>
  </div>
</body>
</html>