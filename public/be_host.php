<?php
include '../includes/public_session.php';
include '../includes/functions.php';
include '../includes/auth.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Be a Host - BookIT</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="/BookIT/assets/css/public/register.css" rel="stylesheet">
</head>

<body>
  <div class="register-container">
    <div class="register-card">
      
      <!-- Header -->
      <div class="register-header">
        <h1 class="mb-3"><i class="fas fa-handshake"></i> Become a Host</h1>
        <p class="lead mb-0">Partner with BookIT and start hosting your own branches and units today.</p>
      </div>

      <!-- Body -->
      <div class="register-body">
        <p class="text-muted mb-4">
          Hosting with BookIT gives you access to easy booking management, visibility to thousands of users, 
          and support every step of the way.
        </p>

        <ul class="feature-list mb-4">
          <li><i class="fas fa-check-circle"></i> Manage branches and units with ease</li>
          <li><i class="fas fa-check-circle"></i> Reach more customers across the platform</li>
          <li><i class="fas fa-check-circle"></i> Real-time reservation tracking</li>
          <li><i class="fas fa-check-circle"></i> Dedicated host support</li>
        </ul>

        <div class="d-flex flex-column flex-md-row justify-content-center align-items-center gap-3 mt-4">
          <?php if (!isLoggedIn()): ?>
            <a href="host_register.php" class="btn btn-register w-100 w-md-auto">
              <i class="fas fa-user-plus"></i> Register as Host
            </a>
            <a href="login.php" class="btn btn-outline-secondary w-100 w-md-auto">
              <i class="fas fa-sign-in-alt"></i> Login
            </a>
          <?php else: ?>
            <a href="../host/host_dashboard.php" class="btn btn-register w-100 w-md-auto">
              <i class="fas fa-tachometer-alt"></i> Go to Host Dashboard
            </a>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</body>
</html>