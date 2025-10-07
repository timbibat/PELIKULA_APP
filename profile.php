<?php
require 'vendor/autoload.php';
require 'db.php';
session_start();

if (!isset($_SESSION['user_email']) || !isset($_SESSION['is_verified']) || !$_SESSION['is_verified']) {
    header("Location: index.php");
    exit;
}

$email = $_SESSION['user_email'];

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Update status for past bookings if not cancelled
$pdo->query("
    UPDATE bookings
    SET status='Done'
    WHERE status='Upcoming'
      AND NOW() > STR_TO_DATE(CONCAT(showdate, ' ', showtime), '%Y-%m-%d %l:%i %p')
");

// Fetch bookings (use status column directly)
$stmt = $pdo->prepare("
    SELECT b.*, m.title AS movie_title
    FROM bookings b
    LEFT JOIN tbl_movies m ON b.movie_id = m.id
    WHERE b.email = ?
    ORDER BY b.id DESC
");
$stmt->execute([$email]);
$bookings = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Profile - Pelikula</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
    :root {
      --accent: #FF4500;
      --bg-main: #f7f8fa;
      --bg-card: #fff;
      --text-main: #1a1a22;
      --text-muted: #5a5a6e;
      --navbar-bg: #e9ecef;
      --navbar-text: #1a1a22;
      --footer-bg: #e9ecef;
      --brand: #FF4500;
      --badge-bg: #6c757d;
      --toggle-btn-bg: #FF4500;
      --toggle-btn-color: #fff;
      --toggle-btn-border: #FF4500;
    }
    body.dark-mode {
      --accent: #0d6efd;
      --bg-main: #10121a;
      --bg-card: #181a20;
      --text-main: #e6e9ef;
      --text-muted: #aab1b8;
      --navbar-bg: #23272f;
      --navbar-text: #fff;
      --footer-bg: #181a20;
      --brand: #0d6efd;
      --badge-bg: #343a40;
      --toggle-btn-bg: #23272f;
      --toggle-btn-color: #0d6efd;
      --toggle-btn-border: #0d6efd;
    }
    body {
      background: var(--bg-main);
      min-height: 100vh;
      color: var(--text-main);
    }
    .navbar { background: var(--navbar-bg) !important; box-shadow:0 2px 12px rgba(0,0,0,0.25);}
    .navbar .navbar-brand { color: var(--accent) !important; }
    .navbar-profile-pic { width: 46px; height: 46px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; }
    #toggleModeBtn {
      background: var(--toggle-btn-bg) !important;
      color: var(--toggle-btn-color) !important;
      border: 2px solid var(--toggle-btn-border) !important;
      transition: background 0.2s, color 0.2s, border 0.2s;
    }
    #toggleModeBtn:focus {
      outline: 2px solid var(--toggle-btn-border);
    }
    .profile-header {
      background: var(--bg-card);
      color: var(--text-main);
      border-radius: 16px;
      padding: 2rem 1.5rem 1rem 1.5rem;
      box-shadow: 0 3px 12px rgba(0,0,0,0.07);
      margin-bottom: 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }
    @media (max-width:900px) {
      .profile-header { flex-direction: column; gap: 1rem; text-align: center; }
    }
    .card-section {
      background: var(--bg-card);
      border-radius: 16px;
      box-shadow: 0 3px 18px rgba(0,0,0,0.09);
      margin-bottom: 2rem;
      padding: 1.5rem 1.5rem 1.2rem 1.5rem;
    }
    .bookings-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      background: var(--bg-card);
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0,0,0,0.07);
    }
    .bookings-table th, .bookings-table td {
      padding: 0.85rem 0.5rem;
      vertical-align: middle;
      font-size: 1.05rem;
      border: none;
    }
    .bookings-table thead {
      background: var(--accent);
      color: #fff;
    }
    .bookings-table th {
      font-weight: 700;
      letter-spacing: 0.03em;
    }
    .bookings-table tbody tr {
      transition: background 0.16s;
    }
    .bookings-table tbody tr:hover {
      background: #fff5ec;
    }
    body.dark-mode .bookings-table tbody tr:hover {
      background: #23272f;
      color: var(--text-main);
    }
    .bookings-table td {
      font-size: 0.98rem;
    }
    .badge-status {
      border-radius: 7px;
      padding: 5px 16px;
      font-weight: 600;
      font-size: 0.98em;
      letter-spacing: 0.02em;
      border: none;
      display: inline-block;
    }
    .badge-status.done {
      background: #eafbe4;
      color: #1fa443;
    }
    .badge-status.cancelled {
      background: #ffe5e0;
      color: #cc0000;
    }
    .badge-status.upcoming {
      background: #fff4e5;
      color: var(--accent);
    }
    .btn-action {
      border-radius: 7px;
      font-weight: 500;
      font-size: 0.97em;
      padding: 7px 16px;
      display: flex;
      align-items: center;
      gap: 5px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.09);
      border: none;
    }
    .btn-action.cancel { background: #ffe5e0; color: #cc0000; }
    .btn-action.cancel:hover { background: #ffbdb2; color: #a70000; }
    .btn-action.done { background: #eafbe4; color: #888;}
    .btn-action.cancelled { background: #ffe5e0; color: #cc0000;}
    .no-bookings {
      text-align: center;
      color: #888;
      font-style: italic;
      padding: 2rem 0;
    }
    .goback-btn {
      background: var(--accent);
      color: #fff;
      border-radius: 8px;
      border: none;
      padding: 4px 14px;
      font-weight: 500;
      font-size: 0.85rem;
      letter-spacing: 0.5px;
      transition: background 0.2s;
    }
    .goback-btn:hover {
      background: #d13d00;
      color: #fff;
    }
    .logout-btn {
      background: #e63946;
      color: #fff;
      border-radius: 8px;
      border: none;
      padding: 4px 14px;
      font-weight: 500;
      font-size: 0.85rem;
      transition: background 0.2s;
    }
    .logout-btn:hover {
      background: #b81f2d;
    }
    @media (max-width: 700px) {
      .bookings-table th, .bookings-table td { padding: 0.55rem 0.14rem; font-size: 0.95rem;}
      .badge-status, .btn-action { font-size: 0.95em; padding: 6px 9px;}
      .profile-header { padding: 1.2rem 0.6rem;}
      .card-section { padding: 0.7rem 0.2rem; }
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
    <a class="navbar-brand fw-bold" href="index.php" style="color:var(--accent);">
      <img src="pictures/gwapobibat1.png" alt="PELIKULA Logo" height="34" class="me-2">
      PELIKULA
    </a>
    <div class="d-flex ms-auto align-items-center">
      <button id="toggleModeBtn" class="btn btn-outline-warning me-3" title="Toggle light/dark mode">
        <i class="bi bi-moon-stars" id="modeIcon"></i>
      </button>
      <?php if (isset($_SESSION['user_email'])): ?>
        <?php
          $displayName = explode('@', $_SESSION['user_email'])[0];
          $profileImg = !empty($_SESSION['user_picture'])
              ? htmlspecialchars($_SESSION['user_picture'])
              : "https://ui-avatars.com/api/?name=" . urlencode($displayName) . "&background=0D8ABC&color=fff";
        ?>
        <a href="profile.php" title="Go to Profile">
          <img src="<?php echo $profileImg; ?>" class="navbar-profile-pic" alt="Profile">
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<div class="container mt-5" style="max-width:900px;">
    <div class="profile-header">
        <div>
            <h2> Welcome, <?php echo htmlspecialchars($user['email']); ?></h2>
            <small>Profile & Booking Overview</small>
        </div>
        <div>
            <button class="goback-btn" onclick="window.location.href='index.php'"><i class="bi bi-arrow-left"></i> Back</button>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    <div class="card-section mb-4">
      <h4 class="mb-4" style="font-weight:600; color:var(--accent);">Your Bookings</h4>
      <?php if (count($bookings) === 0): ?>
          <div class="no-bookings">You don't have any bookings yet.</div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="bookings-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Movie</th>
              <th>Show Date</th>
              <th>Showtime</th>
              <th>Seat</th>
              <th>Qty</th>
              <th>Booked At</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($bookings as $b): ?>
            <tr>
              <td><?php echo $b['id']; ?></td>
              <td>
                <div style="font-weight:600; color:var(--accent);">
                  <?php echo htmlspecialchars($b['movie_title'] ?? 'N/A'); ?>
                </div>
              </td>
              <td><?php echo htmlspecialchars($b['showdate']); ?></td>
              <td><?php echo htmlspecialchars($b['showtime']); ?></td>
              <td><?php echo htmlspecialchars($b['seat']); ?></td>
              <td><?php echo htmlspecialchars($b['quantity']); ?></td>
              <td><?php echo htmlspecialchars($b['booked_at']); ?></td>
              <td>
                <?php if ($b['status'] === "Done"): ?>
                  <span class="badge-status done">Done</span>
                <?php elseif ($b['status'] === "Cancelled"): ?>
                  <span class="badge-status cancelled">Cancelled</span>
                <?php else: ?>
                  <span class="badge-status upcoming">Upcoming</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($b['status'] === "Upcoming"): ?>
                  <a href="cancel_booking.php?id=<?php echo $b['id']; ?>" class="btn-action cancel"><i class="bi bi-x-circle"></i>Cancel</a>
                <?php elseif ($b['status'] === "Done"): ?>
                  <button class="btn-action done" disabled><i class="bi bi-check2-circle"></i>Done</button>
                <?php elseif ($b['status'] === "Cancelled"): ?>
                  <button class="btn-action cancelled" disabled><i class="bi bi-x-circle"></i>Cancelled</button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
</div>
</body>
</html>