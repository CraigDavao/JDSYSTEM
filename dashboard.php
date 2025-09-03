<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once 'connection/connection.php';
require_once __DIR__ . '/includes/header.php';

// auto-login with cookie (optional)
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
            break;
        }
    }
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
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
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #fff;
      margin: 0;
      padding: 0;
    }

    .dashboard-container {
      display: flex;
      margin: 30px;
    }

    /* Sidebar */
    .dashboard-sidebar {
      width: 220px;
      border-right: 1px solid #ddd;
      background: #f8f8f8;
      min-height: 400px;
    }

    .dashboard-sidebar ul {
      list-style: none;
      margin: 0;
      padding: 0;
    }

    .dashboard-sidebar li {
      padding: 15px;
      border-bottom: 1px solid #eee;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .dashboard-sidebar li.active {
      background: #eee;
      font-weight: bold;
    }

    /* Main content */
    .dashboard-content {
      flex: 1;
      padding: 20px;
    }

    .order-box {
      border: 1px solid #228B22;
      padding: 10px;
      margin: 20px 0;
      color: #228B22;
    }

    table {
      border-collapse: collapse;
      width: 100%;
      margin-top: 20px;
    }

    table td {
      border: 1px solid rgba(146, 146, 146, 0.3);
      padding: 15px;
    }

    .logout-link {
      color: #d9534f;
      text-decoration: none;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <div class="dashboard-sidebar">
      <ul>
        <li class="active"><i class="fas fa-th-large"></i> Dashboard</li>
        <li><i class="fas fa-map-marker-alt"></i> Address</li>
        <li><i class="fas fa-heart"></i> Favorites</li>
        <li><a href="auth/logout.php" style="color:inherit;text-decoration:none;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </div>

    <!-- Main content -->
    <div class="dashboard-content">
      <p>Hello <?php echo htmlspecialchars($_SESSION['user_name']); ?> 
      (not <?php echo htmlspecialchars($_SESSION['user_name']); ?>? u
      <a class="logout-link" href="auth/logout.php">Log out</a>)</p>

      <div class="order-box">
        <i class="fas fa-check-circle"></i> 
        <a href="#">MAKE YOUR FIRST ORDER</a> You haven't placed any orders yet.
      </div>

      <table>
        <tr>
          <td>Name</td>
          <td><?php echo htmlspecialchars($_SESSION['user_name']); ?></td>
        </tr>
        <tr>
          <td>Email</td>
          <td><?php echo htmlspecialchars($_SESSION['user_email']); ?></td>
        </tr>
        <tr>
          <td>Address 1</td>
          <td></td>
        </tr>
        <tr>
          <td>Address 2</td>
          <td></td>
        </tr>
      </table>
    </div>
  </div>
</body>
</html>
