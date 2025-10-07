<?php
require 'db.php';
session_start();
$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Admin credentials (password is hardcoded for now)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? AND is_admin=1");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && $password === 'password123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = $admin['email'];
        header("Location: admin_dashboard.php");
        exit;
    } else {
        $login_error = 'Invalid admin credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - Pelikula</title>
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
    html, body {
      background: var(--bg-main);
      color: var(--text-main);
      max-width: 100vw;
      overflow-x: hidden;
      min-height: 100vh;
    }
    .navbar { background: var(--navbar-bg) !important; box-shadow:0 2px 12px rgba(0,0,0,0.17);}
    .navbar .navbar-brand { color: var(--accent) !important; font-weight: bold; }
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
    .admin-login-bg {
      min-height: calc(100vh - 70px);
      display: flex;
      align-items: center;
      justify-content: center;
      background: none;
    }
    .admin-card {
      max-width: 420px;
      width: 100%;
      border-radius: 18px;
      box-shadow: 0 6px 24px rgba(0,0,0,0.13);
      background: var(--bg-card);
      padding: 2.5rem 2rem 2rem 2rem;
      margin: 2rem 0;
      color: var(--text-main);
    }
    .admin-card h4 {
      color: var(--accent);
      font-weight: 700;
      font-size: 2rem;
      margin-bottom: 1.3rem;
      text-align: center;
      letter-spacing: 0.03em;
    }
    .admin-card .form-label {
      font-weight: 600;
      color: var(--text-muted);
    }
    .btn-accent {
      background: var(--btn-accent-bg) !important;
      color: var(--btn-accent-text) !important;
      font-weight: 600;
      font-size: 1.09rem;
      padding: 0.7rem 2rem;
      border-radius: 10px;
      border: none;
      margin-top: 0.8rem;
      width: 100%;
      box-shadow: 0 2px 8px rgba(0,0,0,0.09);
      transition: background 0.15s, color 0.15s;
      letter-spacing: 0.04em;
    }
    .btn-accent:hover, .btn-accent:focus {
      background: #d13d00 !important;
      color: #fff !important;
    }
    .btn-link {
      color: var(--accent) !important;
      font-weight: 600;
      text-align: center;
      width: 100%;
      font-size: 1rem;
      margin-top: 0.8rem;
      border-radius: 8px;
      text-decoration: underline;
      transition: color 0.16s;
    }
    .btn-link:hover {
      color: #d13d00 !important;
      text-decoration: none;
    }
    .alert-danger {
      font-size: 1rem;
      border-radius: 8px;
      margin-bottom: 1rem;
      text-align: center;
    }
    @media (max-width: 700px) {
      .admin-card { padding: 1.1rem 0.5rem; font-size: 0.97rem;}
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
    <a class="navbar-brand fw-bold" href="admin_dashboard.php">
      <img src="pictures/gwapobibat1.png" alt="PELIKULA Logo" height="34" class="me-2">
      Pelikula Admin
    </a>
    <div class="d-flex ms-auto align-items-center">
      <button id="toggleModeBtn" class="btn btn-outline-warning me-3" title="Toggle light/dark mode">
        <i class="bi bi-moon-stars" id="modeIcon"></i>
      </button>
    </div>
  </div>
</nav>
<div class="admin-login-bg">
  <div class="admin-card animate__animated animate__fadeInUp">
    <h4>Admin Login</h4>
    <?php if ($login_error): ?>
      <div class="alert alert-danger"><?php echo $login_error; ?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
      <div class="mb-3">
        <label for="email" class="form-label">Admin Email</label>
        <input type="email" class="form-control" id="email" name="email" required autofocus>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Admin Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-accent">Login</button>
      <a href="index.php" class="btn btn-link">Back to Home</a>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>