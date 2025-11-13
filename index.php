<?php
require 'vendor/autoload.php';
require 'db.php';
session_start();

date_default_timezone_set('Asia/Manila');

// Ensure PDO throws exceptions if not already set in db.php
try {
    if ($pdo && is_object($pdo)) {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
} catch (Exception $e) {
    // ignore if $pdo not set or attribute already configured
}

// SMS-Based Authentication Check (No Google OAuth Required for new users)
if (!isset($_SESSION['user_phone']) && !isset($_SESSION['user_id']) && !isset($_SESSION['user_email'])) {
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title>PELIKULA Cinema - Login Required</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <style>
        :root { 
            --accent: #FF4500; 
            --bg: #f7f8fa; 
            --card: #fff; 
            --text-main: #1a1a22;
        }
        body.dark-mode { 
            --accent: #0d6efd; 
            --bg: #10121a; 
            --card: #181a20; 
            color: #e6e9ef; 
            --text-main: #e6e9ef;
        }
        body { 
            background: var(--bg); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            padding: 1rem;
        }
        .auth-container { 
            max-width: 480px; 
            width: 100%; 
            background: var(--card); 
            border-radius: 24px; 
            padding: 3rem 2.5rem; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .auth-container h3 { 
            color: var(--accent); 
            font-weight: 700; 
            text-align: center; 
            margin-bottom: 1rem; 
            font-size: 2rem; 
        }
        .btn-auth { 
            width: 100%; 
            padding: 1rem; 
            border-radius: 14px; 
            font-weight: 600; 
            font-size: 1.1rem; 
            margin-bottom: 1rem; 
            transition: all 0.3s ease;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-auth:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 10px 25px rgba(0,0,0,0.2); 
        }
        .btn-auth:active {
            transform: translateY(-1px);
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--accent) 0%, #ff6b35 100%);
            color: #fff;
        }
        .btn-outline-custom {
            background: transparent;
            border: 2px solid var(--accent);
            color: var(--accent);
        }
        body.dark-mode .btn-outline-custom {
            border-color: var(--accent);
            color: var(--accent);
        }
        body.dark-mode .btn-outline-custom:hover {
            background: var(--accent);
            color: #fff;
        }
        .logo-hero { 
            text-align: center; 
            margin-bottom: 2.5rem; 
        }
        .logo-hero img { 
            max-width: 160px; 
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }
        .subtitle {
            text-align: center;
            color: var(--text-main);
            opacity: 0.8;
            margin-bottom: 2rem;
            font-size: 1rem;
        }
        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
        }
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: rgba(0,0,0,0.1);
        }
        body.dark-mode .divider::before {
            background: rgba(255,255,255,0.1);
        }
        .divider span {
            background: var(--card);
            padding: 0 1rem;
            position: relative;
            color: var(--text-main);
            opacity: 0.6;
            font-size: 0.9rem;
        }
        </style>
    </head>
    <body>
    <div class="auth-container">
        <div class="logo-hero">
            <img src="pictures/gwapobibat1.png" alt="PELIKULA">
            <h3 class="mt-3">Welcome to PELIKULA</h3>
            <p class="subtitle">Book your favorite movies with SMS verification</p>
        </div>
        
        <a href="login_with_sms.php" class="btn btn-auth btn-primary-custom">
            <i class="bi bi-phone-fill"></i>
            <span>Login with Phone Number</span>
        </a>
        
        <a href="register_with_sms.php" class="btn btn-auth btn-outline-custom">
            <i class="bi bi-person-plus-fill"></i>
            <span>Create New Account</span>
        </a>
        
        <div class="divider">
            <span>Administrator Access</span>
        </div>
        
        <div class="text-center">
            <a href="admin_login.php" class="btn btn-sm btn-outline-secondary px-4">
                <i class="bi bi-shield-lock-fill"></i> Admin Login
            </a>
        </div>
    </div>
    <script>
    const theme = localStorage.getItem('theme') || 'light';
    if (theme === 'dark') document.body.classList.add('dark-mode');
    </script>
    </body>
    </html>
    <?php
    exit;
}

// For logged-in users: ensure is_verified is set
if (!isset($_SESSION['is_verified'])) {
    try {
        if (isset($_SESSION['user_phone'])) {
            $stmt = $pdo->prepare("SELECT is_verified, email FROM users WHERE phone_number = ?");
            $stmt->execute([$_SESSION['user_phone']]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['is_verified'] = (int)$user['is_verified'];
                $_SESSION['user_email'] = $user['email']; // Set email for bookings
            } else {
                $_SESSION['is_verified'] = 1; // Default verified for SMS users
            }
        } elseif (isset($_SESSION['user_email'])) {
            $stmt = $pdo->prepare("SELECT is_verified FROM users WHERE email = ?");
            $stmt->execute([$_SESSION['user_email']]);
            $row = $stmt->fetch();
            $_SESSION['is_verified'] = $row ? (int)$row['is_verified'] : 0;
        } else {
            $_SESSION['is_verified'] = 1;
        }
    } catch (Exception $e) {
        $_SESSION['is_verified'] = 1;
    }
}

// Optional: Keep Google OAuth for existing users (backwards compatibility)
$client = new Google\Client();
$client->setAuthConfig(__DIR__ . '/credentials.json');
$client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/PELIKULA_APP/oauth2callback.php');
$client->setScopes([
    Google\Service\Gmail::GMAIL_SEND,
    Google\Service\Gmail::GMAIL_READONLY,
    Google\Service\Oauth2::USERINFO_EMAIL,
    Google\Service\Oauth2::USERINFO_PROFILE
]);

// Fetch Google profile picture if available (for Google OAuth users)
if (
    isset($_SESSION['access_token']) &&
    !isset($_SESSION['user_picture']) &&
    isset($_SESSION['user_email'])
) {
    try {
        $client->setAccessToken($_SESSION['access_token']);
        if (!$client->isAccessTokenExpired()) {
            $oauth2 = new Google\Service\Oauth2($client);
            $userinfo = $oauth2->userinfo->get();
            $_SESSION['user_picture'] = $userinfo->picture ?? null;
        }
    } catch (Exception $e) {
        $_SESSION['user_picture'] = null;
    }
}

// Fetch movies
$stmt = $pdo->query("SELECT * FROM tbl_movies ORDER BY id ASC");
$movies = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
if (!isset($movies) || !is_array($movies)) {
    $movies = [];
}

// If user clicked a movie, fetch it
$selectedMovie = null;
if (isset($_GET['id'])) {
    $movieId = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM tbl_movies WHERE id=?");
    $stmt->execute([$movieId]);
    $selectedMovie = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($selectedMovie) {
        $selectedMovie['showtimes'] = array_map('trim', explode(',', $selectedMovie['showtimes']));
    }
}

// Helper: Get reserved seats for a show
function get_reserved_seats($pdo, $movie_id, $showdate, $showtime) {
    $stmt = $pdo->prepare("SELECT seat_code FROM seats WHERE movie_id=? AND showdate=? AND showtime=? AND status='reserved'");
    $stmt->execute([$movie_id, $showdate, $showtime]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$errorMsg = "";
$email = $_SESSION['user_email'] ?? '';

/**
 * Accepts showdate (Y-m-d) and a showtime string in either:
 *   - 12-hour format with AM/PM like "10:30 AM"
 *   - 24-hour format like "22:15"
 * Returns true if combined datetime > now.
 */
function is_showtime_in_future($showdate, $showtime_string) {
    // Try 12-hour with AM/PM first
    $dt_string = $showdate . ' ' . $showtime_string;
    $formats = [
        'Y-m-d h:i A', // 12-hour, e.g. 2025-11-13 10:30 AM
        'Y-m-d g:i A', // 12-hour without leading zero
        'Y-m-d H:i',   // 24-hour, e.g. 2025-11-13 22:15
        'Y-m-d G:i'    // 24-hour without leading zero
    ];
    foreach ($formats as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $dt_string);
        if ($dt !== false) {
            $now = new DateTime();
            return $dt > $now;
        }
    }
    // If parsing failed, be conservative (assume it's in the future) — or return false to block
    return false;
}

// Handle booking submission
$showtime_selected = $_POST['showtime'] ?? $_GET['showtime'] ?? '';
$showdate = date('Y-m-d');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['movie_id'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $showtime  = trim($_POST['showtime'] ?? '');
    $showdate  = $_POST['showdate'] ?? date('Y-m-d');
    $email     = trim($_POST['email'] ?? '');
    $quantity  = intval($_POST['quantity'] ?? 1);
    $selected_seats_raw = $_POST['selected_seats'] ?? '';
    $selected_seats = array_filter(array_map('trim', explode(',', $selected_seats_raw)));

    if (empty($firstName) || empty($lastName) || empty($showtime) || empty($email) || empty($quantity)) {
        $errorMsg = "All required fields must be filled.";
    } elseif (count($selected_seats) !== $quantity) {
        $errorMsg = "Number of selected seats (" . count($selected_seats) . ") must match the quantity ($quantity).";
    } elseif (!is_showtime_in_future($showdate, $showtime)) {
        $errorMsg = "Selected showtime appears to have already passed or is in an unrecognized format. Please choose a valid future showtime.";
    } else {
        // Check seat availability
        $unavailable = [];
        foreach ($selected_seats as $seat_code) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM seats WHERE movie_id=? AND showdate=? AND showtime=? AND seat_code=? AND status='reserved'");
            $stmt->execute([$_POST['movie_id'], $showdate, $showtime, $seat_code]);
            if ($stmt->fetchColumn() > 0) {
                $unavailable[] = $seat_code;
            }
        }

        if ($unavailable) {
            $errorMsg = "Some seats are already reserved: " . implode(', ', $unavailable);
        } else {
            // Get user_id from session
            $user_id = $_SESSION['user_id'] ?? null;
            
            // If no user_id in session, try to fetch from database
            if (!$user_id) {
                try {
                    if (isset($_SESSION['user_phone'])) {
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone_number = ?");
                        $stmt->execute([$_SESSION['user_phone']]);
                    } elseif (isset($_SESSION['user_email'])) {
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                        $stmt->execute([$_SESSION['user_email']]);
                    } else {
                        $stmt = null;
                    }
                    $user = $stmt ? $stmt->fetch() : false;
                    $user_id = $user['id'] ?? null;
                } catch (Exception $e) {
                    $user_id = null;
                }
            }
            
            if (!$user_id) {
                $errorMsg = "User session expired or user not found. Please log in again.";
            } else {
                // Insert booking inside try/catch so we capture DB errors
                try {
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, movie_id, first_name, last_name, email, showtime, seat, quantity, showdate)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $success = $stmt->execute([
                        $user_id,
                        $_POST['movie_id'],
                        $firstName,
                        $lastName,
                        $email,
                        $showtime,
                        implode(',', $selected_seats),
                        $quantity,
                        $showdate
                    ]);
                    if (!$success) {
                        $info = $stmt->errorInfo();
                        throw new Exception("Booking insert failed: " . ($info[2] ?? 'unknown error'));
                    }

                    $bookingId = $pdo->lastInsertId();
                    // Reserve seats
                    $seatInsert = $pdo->prepare("INSERT INTO seats (booking_id, movie_id, showdate, showtime, seat_code, status)
                        VALUES (?, ?, ?, ?, ?, 'reserved')");
                    foreach ($selected_seats as $seat_code) {
                        $seatInsert->execute([$bookingId, $_POST['movie_id'], $showdate, $showtime, $seat_code]);
                    }

                    $pdo->commit();

                    // Redirect (PHP header). If headers already sent, fall back to JS below.
                    $redirectUrl = "confirm.php?booking_id=" . urlencode($bookingId);
                    if (!headers_sent()) {
                        header("Location: $redirectUrl");
                        exit;
                    } else {
                        echo "<script>window.location.href = " . json_encode($redirectUrl) . ";</script>";
                        exit;
                    }
                } catch (Exception $e) {
                    if ($pdo && $pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    // Sanitize for display
                    $safeMsg = htmlspecialchars($e->getMessage());
                    $errorMsg = "Booking failed: " . $safeMsg;
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PELIKULA App</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap" rel="stylesheet">
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
    }
    body { background: var(--bg-main) !important; color: var(--text-main) }
    .text-muted { color: var(--text-muted) !important; }
    .card { border-radius: 16px !important; background: var(--bg-card); color: var(--text-main); }
    .card-title { font-family: 'Montserrat', sans-serif; font-weight: 700; color: var(--accent) !important; }
    .card-img-fix, .card-img-top { height: 420px; object-fit: cover; border-bottom:4px solid var(--accent); }
    .navbar { background: var(--navbar-bg) !important; box-shadow:0 2px 12px rgba(0,0,0,0.25); }
    .navbar .navbar-brand, .navbar .nav-link, .navbar .navbar-text { color: var(--navbar-text) !important; }
    .navbar .navbar-brand { color: var(--accent) !important; }
    .hero-section { box-shadow: 0 4px 32px 0 rgba(0,0,0,0.2); }
    .center-auth { display: flex; justify-content: center; align-items: center; min-height: 60vh; }
    .showtime-btn[disabled] { opacity: 0.6; cursor: not-allowed; text-decoration: line-through; }
    .locked-video { pointer-events: none; user-select: none; }
    .error { color: var(--seat-reserved-bg); text-align: center; margin-bottom: 16px; }
    .seat-map {
      display: grid;
      grid-template-columns: repeat(10, 44px);
      gap: 15px;
      margin: 0 auto 18px auto;
      width: max-content;
      justify-content: center;
      align-items: center;
      padding: 18px 8px;
      background: transparent;
    }

    .seat-btn {
      width: 44px;
      height: 44px;
      font-size: 1.08rem;
      font-family: 'Montserrat', Arial, sans-serif;
      font-weight: 700;
      border-radius: 50%;
      border: 2.5px solid var(--seat-available-border);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: 
        background 0.17s,
        color 0.17s,
        border-color 0.17s,
        box-shadow 0.22s;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
      outline: none;
      position: relative;
      letter-spacing: 0.05em;
      user-select: none;
    }
    .seat-btn.available {
      background: linear-gradient(120deg, #f7f8fa 85%, #ffe0d4 100%) !important;
      color: var(--accent) !important;
      border-color: var(--accent);
    }
    .seat-btn.available:hover, .seat-btn.available:focus {
      background: #fff3ed !important;
      color: var(--accent) !important;
      border-color: var(--accent);
      box-shadow: 0 0 10px 2px #ffdab9;
    }
    .seat-btn.selected {
      background: linear-gradient(120deg, var(--accent) 75%, #ff7a3e 100%) !important;
      color: #fff !important;
      border-color: var(--accent) !important;
      box-shadow: 0 0 12px 2px var(--accent), 0 2px 12px rgba(255, 69, 0, 0.15);
      animation: seatPop 0.2s;
    }
    @keyframes seatPop {
      0% { transform: scale(1.08);}
      80% { transform: scale(0.95);}
      100% { transform: scale(1);}
    }
    .seat-btn.reserved {
      background: repeating-linear-gradient(135deg, #d2d5d8 0 10px, #6c757d 10px 20px) !important;
      color: #aab1b8 !important;
      border-color: #6c757d !important;
      cursor: not-allowed;
      opacity: 0.7;
      text-decoration: line-through;
      box-shadow: none;
    }
    body.dark-mode .seat-btn.available {
      background: linear-gradient(120deg, #23272f 80%, #181a20 100%) !important;
      color: var(--accent) !important;
      border-color: var(--accent);
    }
    body.dark-mode .seat-btn.selected {
      background: linear-gradient(120deg, var(--accent) 80%, #0d6efd 100%) !important;
      color: #fff !important;
      border-color: var(--accent) !important;
      box-shadow: 0 0 11px 2.5px var(--accent), 0 2px 12px rgba(13,110,253,0.13);
    }
    body.dark-mode .seat-btn.reserved {
      background: repeating-linear-gradient(135deg, #343a40 0 10px, #181a20 10px 20px) !important;
      color: #aab1b8 !important;
      border-color: #343a40 !important;
    }
    .seat-btn:active:not(.reserved) {
      box-shadow: 0 2px 4px rgba(0,0,0,0.18) inset;
    }
    body.dark-mode .screen-label {
      background: linear-gradient(90deg, #23272f 90%, #181a20 100%);
      color: #e6e9ef;
      border-bottom: 3px solid var(--accent);
    }
    .btn-brand, .btn-accent, .btn-warning, .btn-accent:focus, .btn-brand:focus { background: var(--btn-primary-bg) !important; color: var(--btn-primary-text) !important; border: none; font-weight: 700; }
    .btn-accent { background: var(--accent) !important; color: #fff !important; }
    .badge { border-radius: 10px; }
    .badge.bg-secondary { background: var(--badge-bg) !important; }
    footer { background: var(--footer-bg); color: var(--brand); }
    #toggleModeBtn {
      background: var(--accent) !important;
      color: #fff !important;
      border: 2px solid var(--accent) !important;
      transition: all 0.2s;
    }
    #toggleModeBtn:hover {
      transform: scale(1.05);
    }
    .navbar-profile-pic {
      width: 46px;
      height: 46px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #fff;
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
      <?php if (isset($_SESSION['user_phone']) || isset($_SESSION['user_email'])): ?>
        <?php
          // Display name/phone for navbar
          if (isset($_SESSION['user_phone'])) {
              $displayName = substr($_SESSION['user_phone'], -4); // Last 4 digits
              $displayText = '••••' . $displayName;
          } else {
              $displayName = explode('@', $_SESSION['user_email'])[0];
              $displayText = $displayName;
          }
          
          // Profile picture
          $profileImg = !empty($_SESSION['user_picture'])
              ? htmlspecialchars($_SESSION['user_picture'])
              : "https://ui-avatars.com/api/?name=" . urlencode($displayText) . "&background=0D8ABC&color=fff";
        ?>
        <a href="profile.php" title="Go to Profile">
          <img src="<?php echo $profileImg; ?>" class="navbar-profile-pic" alt="Profile">
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="hero-section text-white d-flex align-items-center" style="min-height: 350px; background: url('pictures/banner-cinema.jpg') center/cover no-repeat, linear-gradient(120deg,#0c2340 60%,#1e4356 100%);">
  <div class="container text-center">
    <h1 class="display-3 fw-bold mb-2" style="color: var(--accent);">PELIKULA CINEMA</h1>
    <h4 class="mb-4">Book your seats for the latest blockbusters!</h4>
    <a href="#movies-list" class="btn btn-accent btn-lg px-5 shadow">Reserve Now</a>
  </div>
</div>

<?php
if ($selectedMovie) {
    ?>
    <div class="container mt-4">
      <a href="index.php" class="btn btn-secondary mb-3">← Back to Movies</a>
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card mb-3">
            <div class="ratio ratio-16x9">
              <?php
                $videoFiles = [
                  1 => 'videos/Demon Slayer_ Kimetsu no Yaiba Infinity Castle _ MAIN TRAILER.mp4',
                  2 => 'videos/The Conjuring_ Last Rites _ Official Trailer.mp4',
                  3 => 'videos/JUJUTSU KAISEN Season 3 _ Official Teaser _ Crunchyroll.mp4',
                ];
                $defaultVideo = 'videos/default_trailer.mp4';
                $videoSrc = $videoFiles[$selectedMovie['id']] ?? $defaultVideo;
              ?>
              <video
                src="<?= htmlspecialchars($videoSrc) ?>"
                title="Movie Trailer"
                autoplay
                class="locked-video"
                tabindex="-1"
                oncontextmenu="return false;"
                playsinline
                loop
              ></video>
            </div>
            <div class="card-body">
              <h3 class="card-title" style="color: var(--accent);"><?= htmlspecialchars($selectedMovie['title']) ?></h3>
              <p class="text-muted"><?= htmlspecialchars($selectedMovie['description']) ?></p>
              <p><b>Run Time:</b> <?= htmlspecialchars($selectedMovie['duration']) ?></p>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <h5 class="card-title">SM STO TOMAS</h5>
              <h6 class="card-subtitle mb-2 text-muted"><?= date('l, j F Y') ?></h6>

              <?php if ($errorMsg): ?>
                <div class="error"><?= htmlspecialchars($errorMsg) ?></div>
              <?php endif; ?>

              <form action="" method="POST" id="bookingForm">
                <input type="hidden" name="movie_id" value="<?= htmlspecialchars($selectedMovie['id']) ?>">
                <input type="hidden" name="showdate" value="<?= htmlspecialchars($showdate) ?>">
                <input type="hidden" name="showtime" id="showtime-input" value="<?= htmlspecialchars($showtime_selected) ?>">
                <input type="hidden" name="selected_seats" id="selected_seats" value="">

                <div class="d-flex flex-wrap gap-2 mb-3">
                  <?php
                  // Replace the original foreach that generated showtime anchors with this block
                  foreach ($selectedMovie['showtimes'] as $time):
                      // Normalize and check if showtime is in the future
                      $timeTrimmed = trim($time);
                      $is_disabled = !is_showtime_in_future($showdate, $timeTrimmed);
                      $is_active = ($showtime_selected === $timeTrimmed);
                      $btn_classes = 'btn btn-outline-primary showtime-btn' . ($is_active ? ' active' : '');
                      if ($is_disabled) {
                          // Render a non-clickable, disabled button for past showtimes
                          echo '<button type="button" class="' . $btn_classes . ' disabled" disabled aria-disabled="true" tabindex="-1">'
                              . htmlspecialchars($timeTrimmed)
                              . '</button>';
                      } else {
                          // Render an anchor for selectable showtimes (keeps your existing GET behavior)
                          $href = 'index.php?id=' . urlencode($selectedMovie['id']) . '&showtime=' . urlencode($timeTrimmed);
                          echo '<a href="' . $href . '" class="' . $btn_classes . '">' . htmlspecialchars($timeTrimmed) . '</a>';
                      }
                  endforeach;
                  ?>
                </div>

                <div class="col mb-3">
                  <input type="number" name="quantity" id="quantity-input" class="form-control" placeholder="Quantity" min="1" required>
                </div>

                <?php if ($showtime_selected): ?>
                  <div id="seat-section">
                    <label class="mb-2"><b>Select your seats:</b></label>
                    <div class="text-center mb-2">
                      <span class="screen-label" style="
                        display: inline-block;
                        background: linear-gradient(90deg, #f3f5f9 90%, #ffe0d4 100%);
                        color: #333;
                        font-weight: 700;
                        font-size: 1.1rem;
                        border-radius: 16px 16px 30px 30px;
                        padding: 12px 40px 10px 40px;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.09);
                        letter-spacing: 0.15em;
                        border-bottom: 3px solid var(--accent);
                      ">
                        SCREEN
                      </span>
                    </div>
                    <div id="seat-map" class="seat-map">
                      <?php
                        $rows = range('A', 'E');
                        $cols = range(1, 10);
                        $reserved_seats = get_reserved_seats($pdo, $selectedMovie['id'], $showdate, $showtime_selected);
                        foreach ($rows as $row) {
                          foreach ($cols as $col) {
                            $seat_code = $row . $col;
                            $reserved = in_array($seat_code, $reserved_seats);
                            $disabled = $reserved;
                            echo '<button type="button" class="seat-btn '.($reserved ? 'reserved' : 'available').'" data-seat="'.$seat_code.'" '.($disabled?'disabled':'').'>'.$seat_code.'</button>';
                          }
                        }
                      ?>
                    </div>
                    <small class="text-muted">Gray = Reserved, Solid Color = Selected, Light = Available</small>
                  </div>
                <?php endif; ?>

                <div class="row g-3 mt-3">
                  <div class="col">
                    <input type="text" name="first_name" class="form-control only-letters" placeholder="First name" required>
                  </div>
                  <div class="col">
                    <input type="text" name="last_name" class="form-control only-letters" placeholder="Last name" required>
                  </div>
                </div>

                <div class="row mb-3 mt-2">
                  <label class="col-form-label">Email (for confirmation)</label>
                  <div class="col-sm-10">
                    <input type="email" name="email" class="form-control form-control-sm" placeholder="Email Address" required value="<?= htmlspecialchars($email) ?>">
                    <small class="form-text text-muted">Email will receive booking confirmation.</small>
                  </div>
                </div>

                <h4>
                  <span class="badge bg-secondary p-2 m-2">
                    ₱<?= number_format($selectedMovie['price'], 2) ?>
                  </span>
                </h4>

                <div class="col-12 mt-2">
                  <button type="submit" class="btn btn-brand">Book Ticket</button>
                </div>
              </form>

              <a href="advanced_booking.php?id=<?= $selectedMovie['id'] ?>" class="btn btn-success mt-3">Book In Advance</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.only-letters').forEach(input => {
        input.addEventListener('input', function () {
          this.value = this.value.replace(/[^A-Za-z\s]/g, '');
        });
      });

      const seatBtns = document.querySelectorAll('.seat-btn.available');
      const selectedSeatsInput = document.getElementById('selected_seats');
      const quantityInput = document.getElementById('quantity-input');
      let selectedSeats = [];
      seatBtns.forEach(btn => {
        btn.addEventListener('click', function () {
          if (btn.classList.contains('selected')) {
            btn.classList.remove('selected');
            selectedSeats = selectedSeats.filter(s => s !== btn.dataset.seat);
          } else {
            const maxSeats = parseInt(quantityInput.value || 1);
            if (selectedSeats.length >= maxSeats) {
              alert('You can only select ' + maxSeats + ' seat(s).');
              return;
            }
            btn.classList.add('selected');
            selectedSeats.push(btn.dataset.seat);
          }
          selectedSeatsInput.value = selectedSeats.join(',');
        });
      });

      if (quantityInput) {
        quantityInput.addEventListener('input', function () {
          const maxSeats = parseInt(quantityInput.value || 1);
          while (selectedSeats.length > maxSeats) {
            const seat = selectedSeats.pop();
            const btn = document.querySelector('.seat-btn[data-seat="'+seat+'"]');
            if (btn) btn.classList.remove('selected');
          }
          selectedSeatsInput.value = selectedSeats.join(',');
        });
      }
    });
    </script>
    <?php
} else {
    ?>
    <div class="container mt-5" id="movies-list">
      <div class="row justify-content-center g-4">
      <?php foreach ($movies as $movie): ?>
        <div class="col-lg-4 col-md-6">
          <div class="card shadow-lg border-0 h-100 animate__animated animate__fadeInUp">
            <img src="<?= htmlspecialchars(!empty($movie['poster_url']) ? $movie['poster_url'] : 'pictures/default-movie.jpg') ?>" class="card-img-top" alt="Movie Poster">
            <div class="card-body d-flex flex-column">
              <h3 class="card-title" style="color: var(--accent);"><?= htmlspecialchars($movie['title']) ?></h3>
              <p class="text-muted" style="min-height:80px;"><?= htmlspecialchars($movie['description']) ?></p>
              <div class="mb-2">
                <span class="badge bg-secondary me-2"><?= htmlspecialchars($movie['duration']) ?></span>
                <span class="badge bg-primary">₱<?= number_format($movie['price'],2) ?></span>
              </div>
              <a href="index.php?id=<?= $movie['id'] ?>" class="btn btn-accent mt-auto shadow">Book Now</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    </div>
    <?php
}
?>

<footer class="py-4 mt-5">
  <div class="container text-center">
    <h6 class="mb-2">Cinema Ticket Booking</h6>
    <p class="mb-0">© <?=date('Y')?> Pelikula Cinema, Inc</p>
  </div>
  <!-- Anchor navigates to chat.php (same-tab). Use target="_blank" if you prefer new tab. -->
<a href="chat.php" id="floatingChat" class="floating-chat" aria-label="Open chat with POPI" title="Chat with POPI">
  <span class="chat-icon" aria-hidden="true">💬</span>
  <span class="chat-label">Chat with POPI</span>
</a>

</footer>
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
  const theme = localStorage.getItem('theme') || 'dark';
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