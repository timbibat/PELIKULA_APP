<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: admin_login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Pelikula</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
      <div class="container-fluid">
        <a class="navbar-brand" href="admin_dashboard.php">Pelikula Admin</a>
        <div class="collapse navbar-collapse">
          <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <a class="nav-link btn btn-primary text-white px-3 mx-2" href="view_user_bookings.php">View User Bookings</a>
            </li>
            <li class="nav-item">
              <a class="nav-link btn btn-info text-white px-3 mx-2" href="view_user_replies.php">View User Replies</a>
            </li>
            <li class="nav-item">
              <a class="nav-link btn btn-danger text-white px-3" href="logout.php">Logout</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container mt-5">
        <h2>Welcome, Admin!</h2>
        <p>Email: <?php echo htmlspecialchars($_SESSION['admin_email']); ?></p>
        <!-- Admin features go here -->
    </div>
</body>
</html>