<?php
require 'vendor/autoload.php';
require 'db.php';
session_start();

/**
 * Updated profile.php with SMS Authentication Support
 * - Supports both phone-based (SMS) and email-based (Google OAuth) login
 * - Shows phone number as primary identifier for SMS users
 * - Email verification banner only shown for email-based users
 * - All users can view and manage their bookings
 */

// Check if user is logged in (either via phone or email)
if (!isset($_SESSION['user_phone']) && !isset($_SESSION['user_email']) && !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Determine primary identifier and fetch user details
$primaryIdentifier = '';
$displayPhone = '';
$displayEmail = '';
$isVerified = !empty($_SESSION['is_verified']) && $_SESSION['is_verified'];
$isSMSUser = isset($_SESSION['user_phone']);

if ($isSMSUser) {
    // SMS-based user
    $primaryIdentifier = $_SESSION['user_phone'];
    $displayPhone = $_SESSION['user_phone'];
    
    // Fetch user details from database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone_number = ?");
    $stmt->execute([$primaryIdentifier]);
    $user = $stmt->fetch();
    
    if ($user) {
        $displayEmail = $user['email'] ?? '';
        $_SESSION['user_id'] = $user['id'];
    }
} else {
    // Email-based user (Google OAuth or legacy)
    $primaryIdentifier = $_SESSION['user_email'];
    $displayEmail = $_SESSION['user_email'];
    
    // Fetch user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$primaryIdentifier]);
    $user = $stmt->fetch();
    
    if ($user) {
        $displayPhone = $user['phone_number'] ?? '';
        $_SESSION['user_id'] = $user['id'];
    }
}

// Update status for past bookings
$pdo->query("
    UPDATE bookings
    SET status='Done'
    WHERE status='Upcoming'
      AND NOW() > STR_TO_DATE(CONCAT(showdate, ' ', showtime), '%Y-%m-%d %l:%i %p')
");

// Fetch bookings - support both phone and email lookups
if ($isSMSUser && $user) {
    // For SMS users, get bookings by user_id
    $stmt = $pdo->prepare("
        SELECT b.*, m.title AS movie_title
        FROM bookings b
        LEFT JOIN tbl_movies m ON b.movie_id = m.id
        WHERE b.user_id = ?
        ORDER BY b.id DESC
    ");
    $stmt->execute([$user['id']]);
} else {
    // For email users, get bookings by email
    $stmt = $pdo->prepare("
        SELECT b.*, m.title AS movie_title
        FROM bookings b
        LEFT JOIN tbl_movies m ON b.movie_id = m.id
        WHERE b.email = ?
        ORDER BY b.id DESC
    ");
    $stmt->execute([$displayEmail]);
}
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
    body.dark-mode .verify-banner {
      background: linear-gradient(90deg, #2a2210, #3a2f1a);
      border: 1px solid rgba(13,110,253,0.12);
    }
    .verify-banner .title { font-weight:700; color:var(--accent); }
    .verified-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
      color: #155724;
      padding: 8px 16px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 0.95rem;
      border: 1px solid #c3e6cb;
      margin-top: 12px;
    }
    body.dark-mode .verified-badge {
      background: linear-gradient(135deg, #1e3a1e 0%, #2d4a2d 100%);
      color: #5cb85c;
      border-color: #2d4a2d;
    }
    .user-info-section {
      background: var(--bg-card);
      padding: 1.5rem;
      border-radius: 14px;
      margin-bottom: 1.5rem;
      box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }
    .info-row {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 0;
      border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    body.dark-mode .info-row {
      border-bottom-color: rgba(255,255,255,0.05);
    }
    .info-row:last-child {
      border-bottom: none;
    }
    .info-label {
      font-weight: 600;
      color: var(--text-muted);
      min-width: 100px;
    }
    .info-value {
      color: var(--accent);
      font-weight: 600;
      font-size: 1.1rem;
    }
    .card-section { background: var(--bg-card); border-radius: 16px; box-shadow: 0 3px 18px rgba(0,0,0,0.09); margin-bottom: 2rem; padding: 1.5rem; }
    .bookings-table { width:100%; border-collapse: collapse; background: var(--bg-card); border-radius: 12px; overflow:hidden; }
    .bookings-table th, .bookings-table td { padding: 0.85rem 0.5rem; vertical-align: middle; font-size: 1rem; }
    .bookings-table thead { background: var(--accent); color: #fff; }
    .badge-status { border-radius: 7px; padding: 5px 12px; font-weight:600; font-size:0.95rem; }
    .badge-status.done { background: #eafbe4; color: #1fa443; }
    .badge-status.cancelled { background: #ffe5e0; color: #cc0000; }
    .badge-status.upcoming { background: #fff4e5; color: var(--accent); }
    body.dark-mode .badge-status.done { background: #1e3a1e; color: #5cb85c; }
    body.dark-mode .badge-status.cancelled { background: #3a1e1e; color: #ff6b6b; }
    body.dark-mode .badge-status.upcoming { background: #2a2a3a; color: #6ea8fe; }
    .btn-action { border-radius: 7px; font-weight: 500; padding: 7px 12px; }
    .btn-action.cancel { background: #ffe5e0; color: #cc0000; border: none; }
    body.dark-mode .btn-action.cancel { background: #3a1e1e; color: #ff6b6b; }
    .no-bookings { text-align:center; color:#888; padding:2rem 0; }
    #toggleModeBtn {
      background: var(--accent) !important;
      color: #fff !important;
      border: 2px solid var(--accent) !important;
      transition: all 0.2s;
    }
    #toggleModeBtn:hover {
      transform: scale(1.05);
    }
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
        // Generate profile image
        if ($isSMSUser) {
            $displayName = substr($displayPhone, -4);
            $avatarText = '••••' . $displayName;
        } else {
            $displayName = explode('@', $displayEmail)[0];
            $avatarText = $displayName;
        }
        
        $profileImg = !empty($_SESSION['user_picture'])
            ? htmlspecialchars($_SESSION['user_picture'])
            : "https://ui-avatars.com/api/?name=" . urlencode($avatarText) . "&background=0D8ABC&color=fff";
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
            <div style="flex: 1;">
                <h2>
                    <i class="bi bi-person-circle"></i> Welcome!
                </h2>
                <small class="text-primary">Profile & Booking Overview</small>
                
                <!-- User Information Section -->
                <div class="user-info-section mt-3">
                    <?php if ($isSMSUser): ?>
                        <!-- SMS User - Show Phone as Primary -->
                        <div class="info-row">
                            <div class="info-label">
                                <i class="bi bi-phone-fill"></i> Phone
                            </div>
                            <div class="info-value">
                                <?= htmlspecialchars($displayPhone) ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($displayEmail)): ?>
                            <div class="info-row">
                                <div class="info-label">
                                    <i class="bi bi-envelope-fill"></i> Email
                                </div>
                                <div class="info-value" style="font-size: 1rem; font-weight: 500;">
                                    <?= htmlspecialchars($displayEmail) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="verified-badge">
                            <i class="bi bi-shield-check-fill"></i>
                            <span>Phone Verified</span>
                        </div>
                        
                    <?php else: ?>
                        <!-- Email User - Show Email as Primary -->
                        <div class="info-row">
                            <div class="info-label">
                                <i class="bi bi-envelope-fill"></i> Email
                            </div>
                            <div class="info-value">
                                <?= htmlspecialchars($displayEmail) ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($displayPhone)): ?>
                            <div class="info-row">
                                <div class="info-label">
                                    <i class="bi bi-phone-fill"></i> Phone
                                </div>
                                <div class="info-value" style="font-size: 1rem; font-weight: 500;">
                                    <?= htmlspecialchars($displayPhone) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($isVerified): ?>
                            <div class="verified-badge">
                                <i class="bi bi-shield-check-fill"></i>
                                <span>Email Verified</span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                    <i class="bi bi-arrow-left"></i> Back
                </button>
                <a href="logout.php" class="btn btn-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>

        <?php if (!$isSMSUser && !$isVerified): ?>
            <!-- Email verification banner (only for email-based users) -->
            <div class="verify-banner mt-3">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" fill="currentColor" class="bi bi-envelope-check" viewBox="0 0 16 16" style="color:var(--accent)"><path d="M2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2z"/><path d="M10.854 7.646a.5.5 0 0 1 .11.638l-.057.07-2 2a.5.5 0 0 1-.638.057l-.07-.057-1-1a.5.5 0 0 1 .638-.765l.07.057L9 8.293l1.854-1.647z"/></svg>
                </div>
                <div style="flex:1">
                    <div class="title">Email not verified</div>
                    <div class="small text-muted">Please verify your email to access all features (bookings, cancellations, etc.).</div>
                </div>
                <div class="text-end">
                    <a href="send_verification.php" class="btn btn-outline-primary">Resend verification email</a>
                    <div class="mt-1 small text-muted">Verification token: <?php echo htmlspecialchars($_SESSION['verification_token'] ?? $user['verification_token'] ?? '—'); ?></div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card-section">
      <h4 class="mb-4" style="font-weight:600; color:var(--accent);">
          <i class="bi bi-ticket-perforated-fill"></i> Your Bookings
      </h4>
      <?php if (count($bookings) === 0): ?>
          <div class="no-bookings">
              <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
              <p class="mt-2">You don't have any bookings yet.</p>
              <a href="index.php" class="btn btn-primary mt-2">
                  <i class="bi bi-film"></i> Browse Movies
              </a>
          </div>
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
              <td><strong>#<?php echo $b['id']; ?></strong></td>
              <td><div style="font-weight:600; color:var(--accent);"><?php echo htmlspecialchars($b['movie_title'] ?? 'N/A'); ?></div></td>
              <td><?php echo htmlspecialchars($b['showdate']); ?></td>
              <td><?php echo htmlspecialchars($b['showtime']); ?></td>
              <td><code><?php echo htmlspecialchars($b['seat']); ?></code></td>
              <td><?php echo htmlspecialchars($b['quantity']); ?></td>
              <td><?php echo date('M d, Y h:i A', strtotime($b['booked_at'])); ?></td>
              <td>
                <?php if ($b['status'] === "Done"): ?>
                  <span class="badge-status done"><i class="bi bi-check-circle-fill"></i> Done</span>
                <?php elseif ($b['status'] === "Cancelled"): ?>
                  <span class="badge-status cancelled"><i class="bi bi-x-circle-fill"></i> Cancelled</span>
                <?php else: ?>
                  <span class="badge-status upcoming"><i class="bi bi-clock-fill"></i> Upcoming</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($b['status'] === "Upcoming"): ?>
                  <?php if ($isVerified || $isSMSUser): ?>
                    <a href="cancel_booking.php?id=<?php echo $b['id']; ?>" 
                       class="btn-action cancel btn btn-sm"
                       onclick="return confirm('Are you sure you want to cancel this booking?')">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                  <?php else: ?>
                    <a href="send_verification.php" class="btn btn-sm btn-outline-secondary" title="Verify your email to cancel bookings">
                        <i class="bi bi-envelope"></i> Verify to Cancel
                    </a>
                  <?php endif; ?>
                <?php elseif ($b['status'] === "Done"): ?>
                  <button class="btn btn-sm btn-outline-success" disabled>
                      <i class="bi bi-check2-circle"></i> Completed
                  </button>
                <?php elseif ($b['status'] === "Cancelled"): ?>
                  <button class="btn btn-sm btn-outline-danger" disabled>
                      <i class="bi bi-x-circle"></i> Cancelled
                  </button>
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