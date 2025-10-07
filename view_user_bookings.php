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

// Fetch all bookings (show movie title instead of id)
$stmt = $pdo->query("
    SELECT 
        b.id AS booking_id,
        b.email AS booking_email,
        u.email AS user_email,
        u.id AS user_id,
        b.movie_id,
        m.title AS movie_title,
        b.showdate,
        b.showtime,
        b.seat,
        b.quantity,
        b.booked_at,
        b.status
    FROM bookings b
    LEFT JOIN users u ON b.email = u.email
    LEFT JOIN tbl_movies m ON b.movie_id = m.id
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
    html, body {
      max-width: 100vw;
      overflow-x: hidden;
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
      position: sticky;
      top: 0;
      z-index: 100;
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
      white-space: nowrap;
    }
    .dashboard-sidebar .nav-link.active,
    .dashboard-sidebar .nav-link:hover {
      background: var(--accent);
      color: #fff !important;
    }
    .dashboard-main {
      padding: 2.5rem 2rem;
      min-height: 100vh;
    }
    .booking-card {
      background: var(--bg-card);
      border-radius: 14px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.06);
      margin-bottom: 1.2rem;
      padding: 1.2rem 1.5rem;
      display: flex;
      flex-wrap: wrap;
      gap: 24px;
      align-items: center;
      transition: box-shadow 0.15s;
    }
    .booking-card:hover {
      box-shadow: 0 6px 24px rgba(0,0,0,0.13);
    }
    .booking-left {
      flex:2 1 280px;
      min-width: 200px;
    }
    .booking-right {
      flex:1 1 180px;
      text-align:right;
      min-width: 120px;
    }
    .booking-id {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--accent);
      margin-bottom: 0.4rem;
    }
    .booking-detail {
      margin-bottom: 0.35rem;
      font-size: 0.99rem;
    }
    .badge-status {
      font-size: 0.95rem;
      padding: 6px 14px;
      border-radius: 8px;
      font-weight: 600;
      letter-spacing: 0.02em;
      margin-top: 7px;
      display:inline-block;
    }
    .status-done { background: #eafbe4; color: #1fa443; border: 1px solid #a2e7b1;}
    .status-upcoming { background: #fff4e5; color: var(--accent); border: 1px solid #ffd9b3;}
    .status-cancelled { background: #ffe5e0; color: #cc0000; border: 1px solid #ffbdb2;}
    .booking-email { color: #0d6efd; font-weight: 600;}
    .user-email { color: #0d6efd;}
    .booking-seats { letter-spacing: 0.04em; }
    .booking-card .icon {
      font-size: 1.2rem;
      opacity: 0.45;
      margin-right: 8px;
      vertical-align: middle;
    }
    .booked-at {
      font-size: 0.95rem;
      color: var(--text-main);
      margin-top: 0.4rem;
    }
    /* Responsive styles */
    @media (max-width:1200px) {
      .dashboard-main { padding: 2rem 0.5rem; }
      .booking-card { padding: 1rem 0.7rem;}
    }
    @media (max-width:991.98px) {
      .container-fluid, .main-row, .dashboard-sidebar, .dashboard-main {
        width: 100% !important;
        max-width: 100vw !important;
        margin: 0 !important;
        padding: 0 !important;
        box-sizing: border-box;
      }
      .dashboard-sidebar {
        min-height: auto;
        padding-top: 1rem;
        margin-bottom: 1rem;
        flex-direction: row;
        gap: 0.5rem;
        box-shadow: none;
        position: relative !important;
        top: 0 !important;
        left: 0 !important;
        z-index: 100;
      }
      .dashboard-sidebar .nav-link {
        font-size: 1rem;
        margin-bottom: 0;
        margin-right: 8px;
        padding: 10px 12px;
        border-radius: 6px;
        white-space: nowrap;
      }
      .dashboard-main { padding: 0.8rem 0.2rem !important; }
      .main-row { flex-direction: column;}
      .booking-card { padding: 0.7rem 0.2rem;}
    }
    @media (max-width:700px) {
      .dashboard-sidebar { padding-top: 0.5rem; }
      .dashboard-sidebar .nav-link { font-size: 0.92rem; padding: 8px 7px; }
      .dashboard-main { padding: 0.3rem 0.1rem !important;}
      .main-row { flex-direction: column;}
      .booking-card { font-size: 0.93rem; padding: 0.4rem;}
      .booking-id { font-size: 1rem;}
      .booking-detail { font-size: 0.93rem;}
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
  <div class="row main-row">
    <!-- Sidebar -->
    <nav class="col-lg-2 col-md-3 dashboard-sidebar d-flex flex-column flex-md-column flex-lg-column flex-row flex-wrap">
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
          <?php foreach ($bookings as $b): ?>
            <div class="booking-card">
              <div class="booking-left">
                <div class="booking-id">
                  <i class="bi bi-hash icon"></i>Booking #<?= $b['booking_id']; ?>
                </div>
                <div class="booking-detail booking-email">
                  <i class="bi bi-envelope icon"></i><?= htmlspecialchars($b['booking_email']); ?>
                </div>
                <div class="booking-detail user-email">
                  <i class="bi bi-person-circle icon"></i><?= $b['user_email'] ? htmlspecialchars($b['user_email']) : '—'; ?>
                </div>
                <div class="booking-detail">
                  <i class="bi bi-film icon"></i>
                  <?= htmlspecialchars($b['movie_title'] ?? 'N/A'); ?>
                </div>
                <div class="booking-detail">
                  <i class="bi bi-calendar-event icon"></i><?= htmlspecialchars($b['showdate']); ?> &nbsp;
                  <i class="bi bi-clock icon"></i><?= htmlspecialchars($b['showtime']); ?>
                </div>
                <div class="booking-detail booking-seats">
                  <i class="bi bi-grid icon"></i>Seat(s): <?= htmlspecialchars($b['seat']); ?>
                </div>
                <div class="booking-detail">
                  <i class="bi bi-123 icon"></i>Quantity: <?= htmlspecialchars($b['quantity']); ?>
                </div>
                <div class="booked-at">
                  <i class="bi bi-calendar-plus icon"></i>Booked at: <?= htmlspecialchars($b['booked_at']); ?>
                </div>
              </div>
              <div class="booking-right">
                <?php if ($b['status'] === 'Done'): ?>
                    <span class="badge-status status-done">Done</span>
                <?php elseif ($b['status'] === 'Cancelled'): ?>
                    <span class="badge-status status-cancelled">Cancelled</span>
                <?php else: ?>
                    <span class="badge-status status-upcoming">Upcoming</span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
      <?php endif; ?>
    </main>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>