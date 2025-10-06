<?php
session_start();
require 'db.php';

// Only allow admin access
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: admin_login.php");
    exit;
}

// Auto-update bookings: Mark as Done past showdate+showtime and not already Cancelled
$pdo->query("
    UPDATE bookings
    SET status='Done'
    WHERE status='Upcoming'
      AND NOW() > STR_TO_DATE(CONCAT(showdate, ' ', showtime), '%Y-%m-%d %l:%i %p')
");

// Fetch all bookings (always show b.email, even if no matching user exists)
$stmt = $pdo->query("
    SELECT 
        b.id AS booking_id,
        b.email AS booking_email,
        u.email AS user_email,
        u.id AS user_id,
        b.movie_id,
        b.showdate,
        b.showtime,
        b.seat,
        b.quantity,
        b.booked_at,
        b.status
    FROM bookings b
    LEFT JOIN users u ON b.email = u.email
    ORDER BY b.booked_at DESC
");
$bookings = $stmt->fetchAll();
$admin_email = $_SESSION['admin_email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Bookings - Admin - Pelikula</title>
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
    .status-done { color: green; font-weight: bold; }
    .status-upcoming { color: var(--accent); font-weight: bold; }
    .status-cancelled { color: red; font-weight: bold; }
    .table thead { background: var(--accent); color: #fff; }
    .table-bordered {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    }
    @media (max-width: 991.98px) {
      .dashboard-sidebar { min-height: auto; }
      .dashboard-main { padding: 1.4rem 0.5rem; }
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
  <div class="row">
    <!-- Sidebar -->
    <nav class="col-lg-2 col-md-3 dashboard-sidebar d-flex flex-column">
      <a href="admin_dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a href="view_user_bookings.php" class="nav-link active"><i class="bi bi-ticket-detailed"></i> User Bookings</a>
      <a href="view_user_replies.php" class="nav-link"><i class="bi bi-chat-dots"></i> User Replies</a>
      <a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>
    <!-- Main Content -->
    <main class="col-lg-10 col-md-9 dashboard-main">
      <h2 class="mb-4" style="color:var(--accent);">All User Bookings</h2>
      <?php if (count($bookings) === 0): ?>
          <div class="alert alert-info">No bookings found.</div>
      <?php else: ?>
          <div class="table-responsive">
          <table class="table table-bordered align-middle">
              <thead>
                  <tr>
                      <th>Booking ID</th>
                      <th>Booking Email</th>
                      <th>User Email (if registered)</th>
                      <th>Movie ID</th>
                      <th>Show Date</th>
                      <th>Showtime</th>
                      <th>Seat</th>
                      <th>Quantity</th>
                      <th>Booked At</th>
                      <th>Status</th>
                  </tr>
              </thead>
              <tbody>
                  <?php foreach ($bookings as $b): ?>
                      <tr>
                          <td><?php echo $b['booking_id']; ?></td>
                          <td><?php echo htmlspecialchars($b['booking_email']); ?></td>
                          <td><?php echo $b['user_email'] ? htmlspecialchars($b['user_email']) : '—'; ?></td>
                          <td><?php echo htmlspecialchars($b['movie_id']); ?></td>
                          <td><?php echo htmlspecialchars($b['showdate']); ?></td>
                          <td><?php echo htmlspecialchars($b['showtime']); ?></td>
                          <td><?php echo htmlspecialchars($b['seat']); ?></td>
                          <td><?php echo htmlspecialchars($b['quantity']); ?></td>
                          <td><?php echo htmlspecialchars($b['booked_at']); ?></td>
                          <td>
                              <?php if ($b['status'] === 'Done'): ?>
                                  <span class="status-done">Done</span>
                              <?php elseif ($b['status'] === 'Cancelled'): ?>
                                  <span class="status-cancelled">Cancelled</span>
                              <?php else: ?>
                                  <span class="status-upcoming">Upcoming</span>
                              <?php endif; ?>
                          </td>
                      </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>
          </div>
      <?php endif; ?>
    </main>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>