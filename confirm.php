<?php
// confirm.php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
session_start();

// Path to movies.json
$moviesFile = __DIR__ . '/data/movies.json';

// Basic guard for booking_id
$booking_id = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);
if (!$booking_id) {
    http_response_code(400);
    exit('Error: No booking ID provided or invalid booking ID.');
}

// Fetch booking info
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    http_response_code(404);
    exit('Error: Booking not found.');
}

// Load movies JSON (if available)
$movieTitle = 'Unknown Movie';
$basePrice = 315.00;
$movieId = $booking['movie_id'] ?? null;

if ($movieId) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_movies WHERE id = ?");
    $stmt->execute([$movieId]);
    $movie = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($movie) {
        $movieTitle = $movie['title'] ?? 'Unknown Movie';
        $basePrice = isset($movie['price']) ? floatval($movie['price']) : 315.00;
    }
}

$firstName  = $booking['first_name'] ?? '';
$lastName   = $booking['last_name'] ?? '';
$showtime   = $booking['showtime'] ?? '';
$seat       = $booking['seat'] ?? '';
$toEmail    = $booking['email'] ?? '';
$quantity   = isset($booking['quantity']) ? (int)$booking['quantity'] : 1;
$totalPrice = $basePrice * max(1, $quantity);

// Ensure $sendResult always exists to avoid undefined variable warnings
$sendResult = 'Email not attempted';

// Try to send the email (safe-guarded)
try {
    $sendEmailPath = __DIR__ . '/send_email.php';
    if (file_exists($sendEmailPath)) {
        require_once $sendEmailPath;

        if (function_exists('sendBookingEmail')) {
            $sendResult = sendBookingEmail(
                $toEmail,
                $movieTitle,
                $showtime,
                $seat,
                $firstName,
                $lastName,
                $quantity,
                $totalPrice,
                $booking_id
            );
        } else {
            $sendResult = 'sendBookingEmail() function not defined in send_email.php';
        }
    } else {
        $sendResult = 'send_email.php file not found. Email not sent.';
    }
} catch (\Throwable $e) {
    $sendResult = 'Error sending email: ' . $e->getMessage();
}

// Profile avatar logic (copied from index.php)
$displayName = isset($_SESSION['user_email']) ? explode('@', $_SESSION['user_email'])[0] : '';
$profileImg = (!empty($_SESSION['user_picture']))
    ? htmlspecialchars($_SESSION['user_picture'])
    : (
        $displayName
            ? "https://ui-avatars.com/api/?name=" . urlencode($displayName) . "&background=0D8ABC&color=fff"
            : "https://ui-avatars.com/api/?name=Guest&background=0D8ABC&color=fff"
    );
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Booking Confirmation — <?= htmlspecialchars($movieTitle) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
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
      --seat-available-bg: #f3f5f9;
      --seat-available-border: #FF4500;
      --seat-selected-bg: #FF4500;
      --seat-selected-text: #fff;
      --seat-reserved-bg: #6c757d;
      --seat-reserved-text: #fff;
      --btn-primary-bg: #FF4500;
      --btn-primary-text: #fff;
      --toggle-btn-bg: #FF4500;
      --toggle-btn-color: #fff;
      --toggle-btn-border: #FF4500;
      --ticket-head-bg: #c8d8e4;
      --ticket-head-title: var(--accent);
      --ticket-head-sub: var(--text-muted);
      --ticket-head-email: var(--text-muted);
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
      --seat-available-bg: #23272f;
      --seat-available-border: #0d6efd;
      --seat-selected-bg: #0d6efd;
      --seat-selected-text: #fff;
      --seat-reserved-bg: #6c757d;
      --seat-reserved-text: #fff;
      --btn-primary-bg: #0d6efd;
      --btn-primary-text: #fff;
      --toggle-btn-bg: #23272f;
      --toggle-btn-color: #0d6efd;
      --toggle-btn-border: #0d6efd;
      --ticket-head-bg: #212631; /* dark blue-gray */
      --ticket-head-title: #4e97ff;
      --ticket-head-sub: #aab1b8;
      --ticket-head-email: #aab1b8;
    }
    body {
      background: var(--bg-main) !important;
      color: var(--text-main);
      font-family: "Segoe UI", Roboto, -apple-system, "Helvetica Neue", Arial;
      padding: 0;
      margin: 0;
    }
    .navbar {
      background: var(--navbar-bg) !important;
      box-shadow: 0 2px 12px rgba(0,0,0,0.25);
    }
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
    .ticket-wrap {
      max-width: 760px;
      margin: 40px auto 0 auto;
    }
    .ticket {
      background: var(--bg-card);
      border-radius: 14px;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
      overflow: hidden;
      border: 1px solid var(--badge-bg);
    }
    .ticket-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 18px 22px;
      background: var(--ticket-head-bg);
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    .ticket-head .title {
      font-size: 20px;
      font-weight: 700;
      color: var(--ticket-head-title);
    }
    .ticket-head .sub {
      font-size: 13px;
      color: var(--ticket-head-sub);
    }
    .ticket-head .ticket-email {
      color: var(--ticket-head-email);
      font-size: 12px;
      text-align: right;
      margin-top: 6px;
    }
    .ticket-body {
      display: flex;
      gap: 18px;
      padding: 20px;
      align-items: flex-start;
    }
    .left { flex: 1.1; }
    .right {
      width: 220px;
      background: var(--bg-card);
      border-left: 1px dashed rgba(0, 0, 0, 0.05);
      padding: 18px;
      border-radius: 0 0 0 14px;
    }
    .detail-row {
      display: flex;
      justify-content: space-between;
      gap: 8px;
      padding: 8px 0;
      border-bottom: 1px dashed rgba(0, 0, 0, 0.05);
    }
    .detail-row:last-child { border-bottom: none; }
    .detail-row .label { color: var(--text-muted); font-size: 13px; }
    .detail-row .value { font-weight: 600; color: var(--text-main); }
    .total {
      margin-top: 14px;
      padding-top: 10px;
      border-top: 1px solid rgba(0, 0, 0, 0.05);
      font-size: 18px;
      font-weight: 700;
      color: var(--accent);
      text-align: right;
    }
    .info-message {
      text-align: center;
      margin-top: 18px;
      color: var(--text-muted);
      font-size: 14px;
    }
    .btn-home {
      display: block;
      margin: 26px auto 0;
      width: fit-content;
      background: var(--accent);
      color: #fff;
      border-radius: 10px;
      padding: 10px 18px;
      text-decoration: none;
      font-weight: 600;
    }
    .status-badge {
      padding: 6px 10px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 13px;
      display: inline-block;
    }
    .status-success {
      background: rgba(6, 200, 150, 0.12);
      color: #059e80;
      border: 1px solid rgba(7, 200, 150, 0.2);
    }
    .status-fail {
      background: rgba(255, 90, 90, 0.12);
      color: #cc0000;
      border: 1px solid rgba(255, 90, 90, 0.1);
    }
    @media (max-width: 720px) {
      .ticket-body { flex-direction: column; }
      .right {
        width: 100%;
        border-left: none;
        border-top: 1px dashed rgba(0, 0, 0, 0.05);
        padding-top: 14px;
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
    <a class="navbar-brand fw-bold" href="index.php" style="color:var(--accent);">
      <img src="pictures/gwapobibat1.png" alt="PELIKULA Logo" height="34" class="me-2">
      PELIKULA
    </a>
    <div class="d-flex ms-auto align-items-center">
      <button id="toggleModeBtn" class="btn btn-outline-warning me-3" title="Toggle light/dark mode">
        <i class="bi bi-moon-stars" id="modeIcon"></i>
      </button>
      <?php if (isset($_SESSION['user_email'])): ?>
        <a href="profile.php" title="Go to Profile">
          <img src="<?php echo $profileImg; ?>" class="navbar-profile-pic" alt="Profile">
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<div class="ticket-wrap">
  <?php if ($sendResult === true): ?>
    <div class="ticket">
      <div class="ticket-head">
        <div>
          <div class="title"> <?= htmlspecialchars($movieTitle) ?></div>
          <div class="sub">Booking ID: <?= htmlspecialchars((string)$booking_id) ?> &nbsp;•&nbsp; <?= htmlspecialchars($showtime) ?></div>
        </div>
        <div>
          <span class="status-badge status-success">Email Sent</span>
          <div class="ticket-email">
            <?= htmlspecialchars($toEmail) ?>
          </div>
        </div>
      </div>
      <div class="ticket-body">
        <div class="left">
          <div class="detail-row">
            <div class="label">Name</div>
            <div class="value"><?= htmlspecialchars(trim($firstName . ' ' . $lastName)) ?></div>
          </div>
          <div class="detail-row">
            <div class="label">Showtime</div>
            <div class="value"><?= htmlspecialchars($showtime) ?></div>
          </div>
          <div class="detail-row">
            <div class="label">Seat</div>
            <div class="value"><?= htmlspecialchars($seat) ?></div>
          </div>
          <div class="detail-row">
            <div class="label">Quantity</div>
            <div class="value"><?= htmlspecialchars((string)$quantity) ?></div>
          </div>
        </div>
        <div class="right">
          <div style="font-size:13px; color:var(--text-muted); margin-bottom:8px;">Order summary</div>
          <div class="detail-row">
            <div class="label">Ticket price</div>
            <div class="value">₱<?= number_format($basePrice, 2) ?></div>
          </div>
          <div class="detail-row">
            <div class="label">Qty</div>
            <div class="value"><?= htmlspecialchars((string)$quantity) ?></div>
          </div>
          <div class="total">Total: ₱<?= number_format($totalPrice, 2) ?></div>
        </div>
      </div>
    </div>
    <p class="info-message">An email (receipt/ticket) was sent to <strong><?= htmlspecialchars($toEmail) ?></strong>. Please present it at the cinema entrance.</p>
  <?php else: ?>
    <div class="ticket">
      <div class="ticket-head">
        <div>
          <div class="title">❗ Booking Notice</div>
          <div class="sub">Booking ID: <?= htmlspecialchars((string)$booking_id) ?></div>
        </div>
        <div>
          <span class="status-badge status-fail">Email Not Sent</span>
        </div>
      </div>
      <div class="ticket-body">
        <div class="left">
          <div class="detail-row">
            <div class="label">Name</div>
            <div class="value"><?= htmlspecialchars(trim($firstName . ' ' . $lastName)) ?></div>
          </div>
          <div style="padding-top:10px; color:var(--text-muted);">
            <strong>Note:</strong> There was an issue sending the confirmation email.
          </div>
          <div style="margin-top:12px; color:#ffdede; background:rgba(255,255,255,0.02); padding:10px; border-radius:6px;">
            <?= nl2br(htmlspecialchars((string)$sendResult)) ?>
          </div>
        </div>
        <div class="right">
          <div style="font-size:13px; color:var(--text-muted); margin-bottom:8px;">Order summary</div>
          <div class="detail-row">
            <div class="label">Movie</div>
            <div class="value"><?= htmlspecialchars($movieTitle) ?></div>
          </div>
          <div class="detail-row">
            <div class="label">Showtime</div>
            <div class="value"><?= htmlspecialchars($showtime) ?></div>
          </div>
          <div class="total">Total: ₱<?= number_format($totalPrice, 2) ?></div>
        </div>
      </div>
    </div>
    <p class="info-message">You can still present this page at the cinema. Please contact support if you need a resend.</p>
  <?php endif; ?>
  <a href="index.php" class="btn-home">← Back to Home</a>
</div>
</body>
</html>