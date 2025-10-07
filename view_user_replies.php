<?php
ob_start();
include_once 'fetch_gmail_replies.php';
ob_end_clean();

session_start();
require 'db.php';

// Only allow admin access
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: admin_login.php");
    exit;
}
$admin_email = $_SESSION['admin_email'];

// Mark reply as seen
function markReplyAsSeen($pdo, $reply_id) {
    $stmt = $pdo->prepare("UPDATE replies SET is_seen = 1 WHERE id = ?");
    $stmt->execute([$reply_id]);
}

// Handle admin reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['parent_reply_id'], $_POST['admin_message'])) {
    $parent_reply_id = intval($_POST['parent_reply_id']);
    $admin_message = trim($_POST['admin_message']);
    $admin_email = 'admin@pelikulacinema.com';

    $stmt = $pdo->prepare("SELECT * FROM replies WHERE id=?");
    $stmt->execute([$parent_reply_id]);
    $parent_reply = $stmt->fetch();

    if ($admin_message && $parent_reply) {
        $stmt = $pdo->prepare("INSERT INTO replies (user_id, email, booking_id, message, parent_reply_id) VALUES (NULL, ?, ?, ?, ?)");
        $stmt->execute([$admin_email, $parent_reply['booking_id'], $admin_message, $parent_reply_id]);
        $admin_reply_id = $pdo->lastInsertId();

        require_once 'send_email.php';
        $user_email = $parent_reply['email'];
        if (filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
            sendAdminReplyEmail($user_email, $admin_message, $parent_reply['message'], $admin_reply_id);
        }

        header("Location: view_user_replies.php");
        exit;
    }
}

// Fetch all replies, including admin replies (threaded) and movie title
$stmt = $pdo->query("
    SELECT 
        r.id AS reply_id,
        COALESCE(u.email, r.email) AS user_email,
        r.booking_id,
        r.message,
        r.created_at,
        r.parent_reply_id,
        r.is_seen,
        b.movie_id,
        m.title AS movie_title
    FROM replies r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN bookings b ON r.booking_id = b.id
    LEFT JOIN tbl_movies m ON b.movie_id = m.id
    ORDER BY r.booking_id DESC, r.created_at ASC
");
$all_replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group replies by booking_id for dropdowns
$replies_by_booking = [];
foreach ($all_replies as $reply) {
    $replies_by_booking[$reply['booking_id']][] = $reply;
}

function isNewReply($reply) {
    return empty($reply['is_seen']) || $reply['is_seen'] == 0;
}

function displayRepliesThreaded($replies, $parent_id = 0, $level = 0) {
    global $pdo;
    $by_parent = [];
    foreach ($replies as $r) {
        $by_parent[$r['parent_reply_id'] ?? 0][] = $r;
    }
    if (isset($by_parent[$parent_id])) {
        foreach ($by_parent[$parent_id] as $reply) {
            $is_admin = (strtolower($reply['user_email']) === 'admin@pelikulacinema.com');
            $is_new = isNewReply($reply);
            if ($is_new) {
                markReplyAsSeen($pdo, $reply['reply_id']);
            }
            $avatar_url = $is_admin
                ? "https://ui-avatars.com/api/?name=Admin&background=0D8ABC&color=fff"
                : "https://ui-avatars.com/api/?name=" . urlencode(explode('@', $reply['user_email'])[0]) . "&background=FF4500&color=fff";
            $margin_left = $level * 36;
            ?>
            <div class="reply-card card mb-2 shadow-sm" style="margin-left:<?= $margin_left ?>px; border-left: 6px solid <?= $is_admin ? '#0d6efd' : '#FF4500' ?>">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap">
                        <div class="d-flex align-items-center flex-wrap">
                            <img src="<?= $avatar_url ?>" alt="avatar" class="rounded-circle me-2" style="width:32px;height:32px;">
                            <span class="fw-semibold <?= $is_admin ? 'text-primary' : 'text-danger' ?>">
                                <?= htmlspecialchars($reply['user_email']) ?>
                            </span>
                            <?php if ($is_admin): ?>
                                <span class="badge bg-primary ms-2">ADMIN</span>
                            <?php endif; ?>
                            <?php if ($is_new && !$is_admin): ?>
                                <span class="badge bg-success ms-2">New</span>
                            <?php endif; ?>
                        </div>
                        <span class="text-muted small"><?= date('M d, Y h:i A', strtotime($reply['created_at'])) ?></span>
                    </div>
                    <div class="mb-2 flex-wrap">
                        <span class="badge bg-secondary"><?= htmlspecialchars($reply['movie_title'] ?? '-') ?></span>
                        <span class="badge bg-light text-dark">Booking ID: <?= htmlspecialchars($reply['booking_id']) ?></span>
                    </div>
                    <div class="reply-message mb-2" style="white-space:pre-line;"><?= htmlspecialchars($reply['message']) ?></div>
                    <?php if (!$is_admin): ?>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <button class="btn btn-sm btn-primary" onclick="showReplyForm(<?= $reply['reply_id'] ?>)">
                                <i class="bi bi-reply"></i> Reply
                            </button>
                            <!-- Reply form -->
                            <form id="reply-form-<?= $reply['reply_id'] ?>" method="POST" style="display:none; margin-top:8px; width:100%;">
                                <input type="hidden" name="parent_reply_id" value="<?= $reply['reply_id'] ?>">
                                <textarea name="admin_message" class="form-control mb-2" rows="2" placeholder="Type admin reply..." required></textarea>
                                <button type="submit" class="btn btn-success btn-sm">Send</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            displayRepliesThreaded($replies, $reply['reply_id'], $level + 1);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Replies - Admin - Pelikula</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
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
    /* Sidebar styles */
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
    /* Main content styles */
    .dashboard-main {
      padding: 2.5rem 2rem;
      min-height: 100vh;
    }
    .reply-card.card {
      border-radius: 16px !important;
      background: var(--bg-card);
      word-break: break-word;
    }
    .reply-message {
      font-size: 1.1rem;
      color: var(--text-main);
      margin-bottom: 0.4rem;
    }
    .accordion-button:not(.collapsed), .accordion-button:focus {
      background: var(--accent) !important;
      color: #fff;
    }
    /* Responsive styles */
    @media (max-width:1200px) {
      .dashboard-main { padding: 2rem 0.5rem; }
    }
    @media (max-width:991.98px) {
      .dashboard-sidebar {
        min-height: auto;
        padding-top: 1rem;
        margin-bottom: 1rem;
        flex-direction: row;
        gap: 0.5rem;
        box-shadow: none;
        width: 100%;
      }
      .dashboard-sidebar .nav-link {
        font-size: 1rem;
        margin-bottom: 0;
        margin-right: 8px;
        padding: 10px 12px;
        border-radius: 6px;
        white-space: nowrap;
      }
      .dashboard-main { padding: 0.8rem 0.2rem; }
      .reply-card.card { font-size: 0.97rem; }
      .reply-message { font-size: 1rem; }
      .main-row {
        flex-direction: column;
      }
    }
    @media (max-width:700px) {
      .dashboard-sidebar { padding-top: 0.5rem; }
      .dashboard-sidebar .nav-link { font-size: 0.92rem; padding: 8px 7px; }
      .dashboard-main { padding: 0.3rem 0.1rem;}
      .reply-card.card { font-size: 0.93rem; padding: 0.2rem;}
      .reply-message { font-size: 0.92rem;}
      .main-row {
        flex-direction: column;
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
    function showReplyForm(replyId) {
        document.getElementById('reply-form-' + replyId).style.display = 'block';
    }
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
    <!-- Sidebar (always visible, even on mobile) -->
    <nav class="col-lg-2 col-md-3 dashboard-sidebar d-flex flex-column flex-md-column flex-lg-column flex-row flex-wrap">
      <a href="admin_dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a href="view_user_bookings.php" class="nav-link"><i class="bi bi-ticket-detailed"></i> User Bookings</a>
      <a href="view_user_replies.php" class="nav-link active"><i class="bi bi-chat-dots"></i> User Replies</a>
      <a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>
    <!-- Main Content -->
    <main class="col-lg-10 col-md-9 dashboard-main">
      <h2 class="mb-4" style="color:var(--accent);">User Replies by Booking</h2>
      <?php if (empty($replies_by_booking)): ?>
          <div class="alert alert-info">No replies found.</div>
      <?php else: ?>
          <div class="accordion" id="bookingAccordion">
              <?php foreach ($replies_by_booking as $booking_id => $booking_replies): ?>
              <div class="accordion-item mb-3">
                  <h2 class="accordion-header" id="heading<?= $booking_id ?>">
                      <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $booking_id ?>" aria-expanded="false" aria-controls="collapse<?= $booking_id ?>">
                          Booking ID: <?= htmlspecialchars($booking_id) ?> 
                          <span class="ms-2 badge bg-secondary"><?= htmlspecialchars($booking_replies[0]['movie_title'] ?? '-') ?></span>
                          <span class="ms-2 badge bg-info"><?= count($booking_replies) ?> Replies</span>
                      </button>
                  </h2>
                  <div id="collapse<?= $booking_id ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $booking_id ?>" data-bs-parent="#bookingAccordion">
                      <div class="accordion-body px-1 px-md-3">
                          <?php displayRepliesThreaded($booking_replies); ?>
                      </div>
                  </div>
              </div>
              <?php endforeach; ?>
          </div>
      <?php endif; ?>
    </main>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>