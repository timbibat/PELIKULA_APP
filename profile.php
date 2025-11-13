<?php
require 'vendor/autoload.php';
require 'db.php';
session_start();

/**
 * Modified profile.php:
 * - Do NOT immediately redirect unverified users to index.php.
 * - Require session user_email (must be logged in). If not logged in, redirect to index.php.
 * - If logged in but not verified, show a prominent verification banner with a "Resend verification" button
 *   that links to send_verification.php (which will use the session access token & verification_token).
 * - Still show the user's bookings (read-only). Actions that require verification (like booking) remain protected
 *   by their own pages. Cancel booking will still be available only when that booking's status is 'Upcoming'.
 *
 * Replace your existing profile.php with this file.
 */

// If not logged in at all, send back to index.php
if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit;
}

$email = $_SESSION['user_email'];
$isVerified = !empty($_SESSION['is_verified']) && $_SESSION['is_verified'];

// Fetch user info from DB (for display & double-check)
$stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Update status for past bookings if not cancelled (same as original behavior)
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
    }
    body {
      background: var(--bg-main);
      min-height: 100vh;
      color: var(--text-main);
    }
    .navbar { background: var(--navbar-bg) !important; box-shadow:0 2px 12px rgba(0,0,0,0.25);}
    .navbar .navbar-brand { color: var(--accent) !important; }
    .navbar-profile-pic { width: 46px; height: 46px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; }
    .verify-banner {
      border-radius: 12px;
      padding: 12px 16px;
      margin-bottom: 1rem;
      display:flex;
      align-items:center;
      gap:12px;
      background: linear-gradient(90deg, #fff8f0, #fff3ea);
      border: 1px solid rgba(255,69,0,0.12);
    }
    .verify-banner .title { font-weight:700; color:var(--accent); }
    .card-section { background: var(--bg-card); border-radius: 16px; box-shadow: 0 3px 18px rgba(0,0,0,0.09); margin-bottom: 2rem; padding: 1.5rem; }
    .bookings-table { width:100%; border-collapse: collapse; background: var(--bg-card); border-radius: 12px; overflow:hidden; }
    .bookings-table th, .bookings-table td { padding: 0.85rem 0.5rem; vertical-align: middle; font-size: 1rem; }
    .bookings-table thead { background: var(--accent); color: #fff; }
    .badge-status { border-radius: 7px; padding: 5px 12px; font-weight:600; font-size:0.95rem; }
    .badge-status.done { background: #eafbe4; color: #1fa443; }
    .badge-status.cancelled { background: #ffe5e0; color: #cc0000; }
    .badge-status.upcoming { background: #fff4e5; color: var(--accent); }
    .btn-action { border-radius: 7px; font-weight: 500; padding: 7px 12px; }
    .btn-action.cancel { background: #ffe5e0; color: #cc0000; border: none; }
    .no-bookings { text-align:center; color:#888; padding:2rem 0; }
    </style>
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
      <?php
        $displayName = explode('@', $email)[0];
        $profileImg = !empty($_SESSION['user_picture'])
            ? htmlspecialchars($_SESSION['user_picture'])
            : "https://ui-avatars.com/api/?name=" . urlencode($displayName) . "&background=0D8ABC&color=fff";
      ?>
      <a href="profile.php" title="Go to Profile">
        <img src="<?php echo $profileImg; ?>" class="navbar-profile-pic" alt="Profile">
      </a>
    </div>
  </div>
</nav>

<div class="container mt-5" style="max-width:900px;">
    <div class="card-section">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div>
                <h2> Welcome, <?php echo htmlspecialchars($user['email'] ?? $email); ?></h2>
                <small>Profile & Booking Overview</small>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-secondary" onclick="window.location.href='index.php'"><i class="bi bi-arrow-left"></i> Back</button>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <?php if (!$isVerified): ?>
            <div class="verify-banner mt-3">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" fill="currentColor" class="bi bi-envelope-check" viewBox="0 0 16 16" style="color:var(--accent)"><path d="M2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2z"/><path d="M10.854 7.646a.5.5 0 0 1 .11.638l-.057.07-2 2a.5.5 0 0 1-.638.057l-.07-.057-1-1a.5.5 0 0 1 .638-.765l.07.057L9 8.293l1.854-1.647z"/></svg>
                </div>
                <div style="flex:1">
                    <div class="title">Email not verified</div>
                    <div class="small text-muted">Please verify your email to access all features (bookings, cancellations, etc.).</div>
                </div>
                <div class="text-end">
                    <!-- send_verification.php will use the session access_token & verification_token to send the email -->
                    <a href="send_verification.php" class="btn btn-outline-primary">Resend verification email</a>
                    <div class="mt-1 small text-muted">Verification token: <?php echo htmlspecialchars($_SESSION['verification_token'] ?? $user['verification_token'] ?? '—'); ?></div>
                </div>
            </div>
        <?php else: ?>
            <div class="mt-3">
                <div class="small text-success">Your email is verified. You have full access.</div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card-section">
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
              <td><div style="font-weight:600; color:var(--accent);"><?php echo htmlspecialchars($b['movie_title'] ?? 'N/A'); ?></div></td>
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
                  <!-- Only allow cancel if user is verified; otherwise, show tooltip/link to verify -->
                  <?php if ($isVerified): ?>
                    <a href="cancel_booking.php?id=<?php echo $b['id']; ?>" class="btn-action cancel btn btn-sm"><i class="bi bi-x-circle"></i> Cancel</a>
                  <?php else: ?>
                    <a href="send_verification.php" class="btn btn-sm btn-outline-secondary" title="Verify your email to cancel bookings"><i class="bi bi-envelope"></i> Verify to Cancel</a>
                  <?php endif; ?>
                <?php elseif ($b['status'] === "Done"): ?>
                  <button class="btn-action done btn btn-sm" disabled><i class="bi bi-check2-circle"></i> Done</button>
                <?php elseif ($b['status'] === "Cancelled"): ?>
                  <button class="btn-action cancelled btn btn-sm" disabled><i class="bi bi-x-circle"></i> Cancelled</button>
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

<script>
function setMode(mode) {
  const dark = (mode === 'dark');
  if (dark) {
    document.body.classList.add('dark-mode');
    document.getElementById('modeIcon').className = 'bi bi-brightness-high';
    localStorage.setItem('theme', 'dark');
  } else {
    document.body.classList.remove('dark-mode');
    document.getElementById('modeIcon').className = 'bi bi-moon-stars';
    localStorage.setItem('theme', 'light');
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>