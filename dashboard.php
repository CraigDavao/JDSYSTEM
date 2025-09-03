<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php"); // redirect guests to homepage
    exit;
}

require_once 'connection/connection.php';
require_once __DIR__ . '/includes/header.php';

// If no session but cookie exists, try auto-login
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    $stmt = $conn->prepare("SELECT id, fullname, email, remember_token FROM users");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if (password_verify($token, $row['remember_token'])) {
            // Set session again
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['fullname'];
            $_SESSION['user_email'] = $row['email'];
            break;
        }
    }
}

// If still not logged in → redirect
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
    :root {
      --primary: #4361ee;
      --secondary: #3a0ca3;
      --accent: #f72585;
      --light: #f8f9fa;
      --dark: #212529;
      --success: #4cc9f0;
      --border: #dee2e6;
      --card-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
      --transition: all 0.3s ease;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%);
      color: var(--dark);
      line-height: 1.6;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    
    .dashboard-container {
      width: 100%;
      max-width: 900px;
      background: white;
      border-radius: 16px;
      box-shadow: var(--card-shadow);
      overflow: hidden;
      margin: 2rem auto;
    }
    
    .dashboard-header {
      background: linear-gradient(120deg, var(--primary), var(--secondary));
      color: white;
      padding: 2.5rem;
      text-align: center;
      position: relative;
    }
    
    .user-avatar {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      background: white;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      font-size: 2.5rem;
      color: var(--primary);
      border: 4px solid rgba(255, 255, 255, 0.3);
    }
    
    .dashboard-header h1 {
      font-size: 1.8rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }
    
    .welcome-text {
      opacity: 0.9;
      font-size: 1.1rem;
      max-width: 500px;
      margin: 0 auto;
    }
    
    .dashboard-content {
      padding: 2.5rem;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
    }
    
    @media (max-width: 768px) {
      .dashboard-content {
        grid-template-columns: 1fr;
        gap: 1.5rem;
      }
    }
    
    .info-card {
      background: var(--light);
      border-radius: 12px;
      padding: 1.5rem;
      transition: var(--transition);
      border: 1px solid var(--border);
    }
    
    .info-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--card-shadow);
    }
    
    .card-header {
      display: flex;
      align-items: center;
      margin-bottom: 1.2rem;
    }
    
    .card-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: var(--primary);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 0.8rem;
      font-size: 1.2rem;
    }
    
    .card-title {
      font-size: 1.2rem;
      font-weight: 600;
      color: var(--dark);
    }
    
    .info-details {
      margin-left: 0.5rem;
    }
    
    .info-item {
      display: flex;
      justify-content: space-between;
      padding: 0.8rem 0;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .info-item:last-child {
      border-bottom: none;
    }
    
    .info-label {
      font-weight: 500;
      color: var(--dark);
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .info-value {
      font-weight: 600;
      color: var(--primary);
    }
    
    .action-section {
      display: flex;
      justify-content: center;
      gap: 1rem;
      padding: 2rem;
      background: var(--light);
      border-top: 1px solid var(--border);
    }
    
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.8rem 1.5rem;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 500;
      transition: var(--transition);
      border: none;
      cursor: pointer;
      font-size: 1rem;
    }
    
    .btn-primary {
      background: var(--primary);
      color: white;
    }
    
    .btn-primary:hover {
      background: var(--secondary);
      transform: translateY(-2px);
    }
    
    .btn-outline {
      background: transparent;
      color: var(--primary);
      border: 1px solid var(--primary);
    }
    
    .btn-outline:hover {
      background: var(--primary);
      color: white;
    }
    
    .stats-container {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1rem;
      padding: 0 2.5rem 2.5rem;
    }
    
    @media (max-width: 576px) {
      .stats-container {
        grid-template-columns: 1fr;
      }
    }
    
    .stat-card {
      background: white;
      border-radius: 12px;
      padding: 1.5rem;
      text-align: center;
      box-shadow: var(--card-shadow);
      border: 1px solid var(--border);
    }
    
    .stat-value {
      font-size: 2rem;
      font-weight: 700;
      color: var(--primary);
      margin-bottom: 0.5rem;
    }
    
    .stat-label {
      color: var(--dark);
      font-weight: 500;
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <div class="dashboard-header">
      <div class="user-avatar">
        <i class="fas fa-user"></i>
      </div>
      <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
      <p class="welcome-text">You have successfully logged in to your account dashboard</p>
    </div>
    
    <div class="stats-container">
      <div class="stat-card">
        <div class="stat-value">28</div>
        <div class="stat-label">Projects</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">12</div>
        <div class="stat-label">Tasks</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">97%</div>
        <div class="stat-label">Profile Complete</div>
      </div>
    </div>
    
    <div class="dashboard-content">
      <div class="info-card">
        <div class="card-header">
          <div class="card-icon">
            <i class="fas fa-user-circle"></i>
          </div>
          <h3 class="card-title">Account Information</h3>
        </div>
        <div class="info-details">
          <div class="info-item">
            <span class="info-label"><i class="fas fa-user"></i> User ID:</span>
            <span class="info-value"><?php echo htmlspecialchars($_SESSION['user_id']); ?></span>
          </div>
          <div class="info-item">
            <span class="info-label"><i class="fas fa-envelope"></i> Email:</span>
            <span class="info-value"><?php echo htmlspecialchars($_SESSION['user_email']); ?></span>
          </div>
          <div class="info-item">
            <span class="info-label"><i class="fas fa-calendar"></i> Member Since:</span>
            <span class="info-value">Jan 2023</span>
          </div>
        </div>
      </div>
      
      <div class="info-card">
        <div class="card-header">
          <div class="card-icon">
            <i class="fas fa-shield-alt"></i>
          </div>
          <h3 class="card-title">Security</h3>
        </div>
        <div class="info-details">
          <div class="info-item">
            <span class="info-label"><i class="fas fa-lock"></i> Status:</span>
            <span class="info-value" style="color: var(--success)">Protected</span>
          </div>
          <div class="info-item">
            <span class="info-label"><i class="fas fa-check-circle"></i> Verification:</span>
            <span class="info-value" style="color: var(--success)">Verified</span>
          </div>
          <div class="info-item">
            <span class="info-label"><i class="fas fa-clock"></i> Last Login:</span>
            <span class="info-value">Today, 10:30 AM</span>
          </div>
        </div>
      </div>
    </div>
    
    <div class="action-section">
      <a href="#" class="btn btn-primary"><i class="fas fa-cog"></i> Settings</a>
      <a href="#" class="btn btn-outline"><i class="fas fa-edit"></i> Edit Profile</a>
      <a href="auth/logout.php" class="btn btn-outline" style="color: #dc3545; border-color: #dc3545;">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </div>
</body>
</html>