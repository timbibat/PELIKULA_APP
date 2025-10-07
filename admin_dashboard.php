<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: admin_login.php");
    exit;
}
$admin_email = $_SESSION['admin_email'];

// Fetch dashboard stats
$total_movies = $pdo->query("SELECT COUNT(*) FROM tbl_movies")->fetchColumn();
$total_bookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin=0")->fetchColumn();
$new_replies = $pdo->query("
  SELECT COUNT(*) FROM replies r
  WHERE (r.user_id IS NOT NULL OR LOWER(r.email) != 'admin@pelikulacinema.com')
    AND NOT EXISTS (
      SELECT 1 FROM replies a WHERE a.parent_reply_id = r.id AND LOWER(a.email) = 'admin@pelikulacinema.com'
    )
")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Pelikula</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
    :root {
      --accent: #FF4500;
      --bg-main: #f7f8fa;
      --bg-card: #fff;
      --text-main: #1a1a22;
      --navbar-bg: #e9ecef;
      --navbar-text: #1a1a22;
      --sidebar-bg: #fff;
      --sidebar-text: #1a1a22;
      --toggle-btn-bg: #FF4500;
      --toggle-btn-color: #fff;
      --toggle-btn-border: #FF4500;
    }
    body.dark-mode {
      --accent: #0d6efd;
      --bg-main: #10121a;
      --bg-card: #181a20;
      --text-main: #e6e9ef;
      --navbar-bg: #23272f;
      --navbar-text: #fff;
      --sidebar-bg: #181a20;
      --sidebar-text: #fff;
      --toggle-btn-bg: #23272f;
      --toggle-btn-color: #0d6efd;
      --toggle-btn-border: #0d6efd;
    }
    body { background: var(--bg-main); color: var(--text-main);}
    .navbar { background: var(--navbar-bg) !important; box-shadow:0 2px 12px rgba(0,0,0,0.25);}
    .navbar .navbar-brand { color: var(--accent) !important; }
    .navbar-profile-pic { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; }
    #toggleModeBtn {
      background: var(--toggle-btn-bg) !important;
      color: var(--toggle-btn-color) !important;
      border: 2px solid var(--toggle-btn-border) !important;
      transition: background 0.2s, color 0.2s, border 0.2s;
    }
    #toggleModeBtn:focus {
      outline: 2px solid var(--toggle-btn-border);
    }
    .dashboard-sidebar {
      background: var(--sidebar-bg);
      min-height: 100vh;
      padding-top: 2rem;
      box-shadow: 1px 0 12px rgba(0,0,0,0.07);
    }
    .dashboard-sidebar .nav-link {
      color: var(--sidebar-text);
      font-weight: 500;
      font-size: 1.1rem;
      margin-bottom: 18px;
      padding: 12px 20px;
      border-radius: 8px;
      transition: background 0.12s;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .dashboard-sidebar .nav-link.active,
    .dashboard-sidebar .nav-link:hover {
      background: var(--accent);
      color: #fff !important;
    }
    .dashboard-main {
      padding: 2.5rem 2rem;
    }
    .dashboard-cards {
      display: flex;
      gap: 2rem;
      flex-wrap: wrap;
      margin-bottom: 2rem;
    }
    .dashboard-card {
      background: var(--bg-card);
      color: var(--text-main);
      border-radius: 16px;
      box-shadow: 0 4px 18px 0 rgba(0,0,0,0.08);
      padding: 2rem 2.5rem 1.5rem 2.5rem;
      min-width: 240px;
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      margin-bottom: 1rem;
    }
    .dashboard-card h5 {
      font-size: 1.3rem;
      margin-bottom: 0.5rem;
      color: var(--accent);
    }
    .dashboard-card .display-5 {
      font-size: 2.4rem;
      font-weight: 700;
    }
    .admin-welcome {
      margin-bottom: 1.5rem;
      font-weight: 600;
      font-size: 1.5rem;
      color: var(--accent);
    }
    @media (max-width: 991.98px) {
      .dashboard-sidebar { min-height: auto; }
      .dashboard-cards { flex-direction: column; }
      .dashboard-card { padding: 1.4rem 1rem; }
    }
    </style>
    <script>
    function setMode(mode) {
      const dark = (mode === 'dark');
      if (dark) {
        document.body.classList.add('dark-mode');
        localStorage.setItem('theme', 'dark');
        document.getElementById('modeIcon').className = 'bi bi-brightness-high';
      } else {
        document.body.classList.remove('dark-mode');
        localStorage.setItem('theme', 'light');
        document.getElementById('modeIcon').className = 'bi bi-moon-stars';
      }
    }
    document.addEventListener('DOMContentLoaded', function() {
      const theme = localStorage.getItem('theme') || 'light';
      setMode(theme);
      const toggleBtn = document.getElementById('toggleModeBtn');
      if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
          const isDark = document.body.classList.contains('dark-mode');
          setMode(isDark ? 'light' : 'dark');
        });
      }
    });
    </script>
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="admin_dashboard.php" style="color:var(--accent);">
      <img src="pictures/gwapobibat1.png" alt="PELIKULA Logo" height="34" class="me-2">
      Pelikula Admin
    </a>
    <div class="d-flex ms-auto align-items-center">
      <button id="toggleModeBtn" class="btn btn-outline-warning me-3" title="Toggle light/dark mode">
        <i class="bi bi-moon-stars" id="modeIcon"></i>
      </button>
      <div class="dropdown">
        <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">
          <img src="https://ui-avatars.com/api/?name=Admin&background=0D8ABC&color=fff" alt="Admin" class="navbar-profile-pic me-2">
          <span style="color:var(--accent);font-weight:600;">Admin</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow">
          <li><span class="dropdown-item-text">Email: <b><?= htmlspecialchars($admin_email) ?></b></span></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>
<div class="container-fluid">
  <div class="row flex-wrap">
    <!-- Sidebar -->
    <nav class="col-lg-2 col-md-3 dashboard-sidebar d-flex flex-column flex-md-column flex-lg-column flex-row flex-wrap">
      <a href="admin_dashboard.php" class="nav-link active"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a href="view_user_bookings.php" class="nav-link"><i class="bi bi-ticket-detailed"></i> User Bookings</a>
      <a href="view_user_replies.php" class="nav-link"><i class="bi bi-chat-dots"></i> User Replies</a>
      <a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>
    <!-- Main Content -->
    <main class="col-lg-10 col-md-9 dashboard-main">
      <div class="admin-welcome mb-3">Welcome, Admin!</div>
      <div class="mb-3">Email: <strong><?= htmlspecialchars($admin_email) ?></strong></div>
      <div class="dashboard-cards">
        <div class="dashboard-card">
          <h5><i class="bi bi-film"></i> Total Movies</h5>
          <span class="display-5"><?= $total_movies ?></span>
          <small>Manage and add movies</small>
        </div>
        <div class="dashboard-card">
          <h5><i class="bi bi-ticket-perforated"></i> Total Bookings</h5>
          <span class="display-5"><?= $total_bookings ?></span>
          <small>All user bookings</small>
        </div>
        <div class="dashboard-card">
          <h5><i class="bi bi-people-fill"></i> Registered Users</h5>
          <span class="display-5"><?= $total_users ?></span>
          <small>Total accounts</small>
        </div>
        <div class="dashboard-card">
          <h5><i class="bi bi-chat-dots"></i> New Replies</h5>
          <span class="display-5"><?= $new_replies ?></span>
          <small>Unanswered user replies</small>
        </div>
      </div>
      <div>
        <h4 class="mt-4 mb-3" style="color:var(--accent);">Quick Actions</h4>
        <a class="btn" style="background:var(--accent);color:#fff;font-weight:600; margin-right:10px;" href="view_user_bookings.php"><i class="bi bi-ticket-perforated"></i> View User Bookings</a>
        <a class="btn btn-info text-white" style="font-weight:600; margin-right:10px;" href="view_user_replies.php"><i class="bi bi-chat-dots"></i> View User Replies</a>
        <a class="btn btn-danger text-white" style="font-weight:600;" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
      </div>
    </main>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>