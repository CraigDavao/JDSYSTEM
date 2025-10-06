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
  <title>User Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/profile.css?v=<?= time(); ?>">
</head>
<body>
  <div class="dashboard-container">
    <div class="dashboard-sidebar">
      <ul>
        <li class="active"><i class="fas fa-th-large"></i> Dashboard</li>
        <li><i class="fas fa-map-marker-alt"></i> Address</li>
        <li><i class="fas fa-heart"></i> Favorites</li>
        <li><a href="<?php echo SITE_URL; ?>auth/logout.php" style="color:inherit;text-decoration:none;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </div>

    <div class="dashboard-content">
      <p>Hello <?php echo htmlspecialchars($_SESSION['user_name']); ?> 
      (not <?php echo htmlspecialchars($_SESSION['user_name']); ?>? 
      <a class="logout-link" href="<?php echo SITE_URL; ?>auth/logout.php">Log out</a>)</p>

      <div class="order-box">
        <i class="fas fa-check-circle"></i> 
        <a href="#">MAKE YOUR FIRST ORDER</a> You haven't placed any orders yet.
      </div>

      <table>
        <tr><td>Name</td><td><?php echo htmlspecialchars($_SESSION['user_name']); ?></td></tr>
        <tr><td>Email</td><td><?php echo htmlspecialchars($_SESSION['user_email']); ?></td></tr>
        <tr><td>Address 1</td><td></td></tr>
        <tr><td>Address 2</td><td></td></tr>
      </table>
    </div>
  </div>
</body>
</html>
