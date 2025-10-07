<?php
require 'db.php';

$token = $_GET['token'] ?? '';
$status = '';
$message = '';
$alert_class = '';
$icon = '';

if ($token) {
    $stmt = $pdo->prepare("UPDATE users SET is_verified=1 WHERE verification_token=?");
    $stmt->execute([$token]);
    if ($stmt->rowCount()) {
        $status = 'success';
        $message = "Your email has been verified! You can now use all features.";
        $alert_class = 'success';
        $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-check-circle text-success mb-2" viewBox="0 0 16 16">
  <circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="2" fill="none"/>
  <path fill="currentColor" d="M10.97 6.97a.75.75 0 0 1 1.07 1.05l-3.477 4.243a.75.75 0 0 1-1.08.02L5.324 10.384a.75.75 0 1 1 1.06-1.06l1.093 1.093 3.492-4.438z"/>
</svg>';
    } else {
        $status = 'danger';
        $message = "Invalid or already used verification link.";
        $alert_class = 'danger';
        $icon = '<svg width="48" height="48" fill="currentColor" class="bi bi-x-circle text-danger mb-2" viewBox="0 0 16 16"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="2" fill="none"/><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/></svg>';
    }
} else {
    $status = 'danger';
    $message = "No verification token provided.";
    $alert_class = 'danger';
    $icon = '<svg width="48" height="48" fill="currentColor" class="bi bi-exclamation-circle text-danger mb-2" viewBox="0 0 16 16"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="2" fill="none"/><path d="M7.002 11a1 1 0 1 0 2 0 1 1 0 0 0-2 0zm.1-4.995a.905.905 0 0 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 6.005z"/></svg>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
          --btn-accent-bg: #FF4500;
          --btn-accent-text: #fff;
        }
        body.dark-mode {
          --accent: #0d6efd;
          --bg-main: #181a20;
          --bg-card: #23272f;
          --text-main: #e6e9ef;
          --text-muted: #aab1b8;
          --navbar-bg: #23272f;
          --navbar-text: #fff;
          --btn-accent-bg: #0d6efd;
          --btn-accent-text: #fff;
        }
        html, body { background: var(--bg-main); color: var(--text-main); min-height: 100vh; }
        .navbar { background: var(--navbar-bg) !important; box-shadow: 0 2px 12px rgba(0,0,0,0.17);}
        .navbar .navbar-brand { color: var(--accent) !important; font-weight: bold; }
        .navbar-profile-pic { width: 46px; height: 46px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; }
        #toggleModeBtn {
          background: transparent !important;
          color: var(--accent) !important;
          border: 2px solid var(--accent) !important;
          transition: background 0.2s, color 0.2s, border 0.2s;
          border-radius: 10px;
          font-size: 1.3rem;
        }
        #toggleModeBtn:focus {
          outline: 2px solid var(--accent);
        }
        .center-card-bg {
          min-height: calc(100vh - 70px);
          display: flex;
          align-items: center;
          justify-content: center;
          background: none;
        }
        .verify-card {
          max-width: 480px;
          width: 100%;
          border-radius: 18px;
          box-shadow: 0 6px 24px rgba(0,0,0,0.13);
          background: var(--bg-card);
          padding: 2.5rem 2rem;
          margin: 2rem 0;
          text-align: center;
          color: var(--text-main);
        }
        .verify-card h4 {
          font-weight: 700;
          font-size: 2rem;
          margin-top: 1rem;
        }
        .verify-card .text-success { color: #1fa443 !important; }
        .verify-card .text-danger { color: #e63946 !important; }
        .btn-accent {
          background: var(--btn-accent-bg) !important;
          color: var(--btn-accent-text) !important;
          font-weight: 600;
          font-size: 1.1rem;
          padding: 0.7rem 2rem;
          border-radius: 10px;
          border: none;
          margin-top: 1.4rem;
          box-shadow: 0 2px 8px rgba(0,0,0,0.09);
          transition: background 0.15s, color 0.15s;
        }
        .btn-accent:hover, .btn-accent:focus {
          background: #d13d00 !important;
          color: #fff !important;
        }
        .verify-card svg { margin-bottom: 20px; }
        @media (max-width: 700px) {
          .verify-card { padding: 1rem 0.5rem; font-size: 0.98rem;}
          .navbar-profile-pic { width: 35px; height: 35px;}
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="index.php">
      <img src="pictures/gwapobibat1.png" alt="Logo" height="34" class="me-2">
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
<div class="center-card-bg">
  <div class="verify-card animate__animated animate__fadeInUp">
    <?= $icon ?>
    <h4 class="mb-3 text-<?= $alert_class ?>">
      <?= ($status === 'success') ? 'Email Verified!' : 'Verification Failed' ?>
    </h4>
    <p class="mb-4" style="font-size:1.15rem;"><?= htmlspecialchars($message) ?></p>
    <a href="index.php" class="btn btn-accent">Return to Home</a>
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