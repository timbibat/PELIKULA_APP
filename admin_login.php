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
      --toggle-btn-bg: #23272f;
      --toggle-btn-color: #0d6efd;
      --toggle-btn-border: #0d6efd;
    }
    html, body {
      max-width: 100vw;
      overflow-x: hidden;
    }
    body { background: var(--bg-main); color: var(--text-main); }
    .navbar { background: var(--navbar-bg) !important; box-shadow:0 2px 12px rgba(0,0,0,0.25);}
    .navbar .navbar-brand { color: var(--accent) !important; }
    #toggleModeBtn {
      background: var(--toggle-btn-bg) !important;
      color: var(--toggle-btn-color) !important;
      border: 2px solid var(--toggle-btn-border) !important;
      transition: background 0.2s, color 0.2s, border 0.2s;
    }
    #toggleModeBtn:focus {
      outline: 2px solid var(--toggle-btn-border);
    }
    .card { background: var(--bg-card); color: var(--text-main);}
    .login-container {
      max-width: 400px;
      width: 100%;
      margin: 80px auto 0 auto;
      padding: 0 1rem;
    }
    @media (max-width: 600px) {
      .login-container {
        max-width: 98vw;
        padding: 0 0.3rem;
        margin-top: 40px;
      }
      .card {
        padding: 0.5rem;
      }
      h4.mb-3 {
        font-size: 1.4rem;
      }
      .form-label {
        font-size: 0.98rem;
      }
      .btn, .btn-link {
        font-size: 1rem;
      }
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
    </div>
  </div>
</nav>
    <div class="login-container">
        <div class="card shadow">
            <div class="card-body">
                <h4 class="mb-3" style="color:var(--accent);">Admin Login</h4>
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
                    <button type="submit" class="btn" style="background:var(--accent);color:#fff;font-weight:600; width:100%;">Login</button>
                    <a href="index.php" class="btn btn-link w-100 mt-2">Back to Home</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>