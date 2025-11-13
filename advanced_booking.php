<?php
require 'db.php';
session_start();
date_default_timezone_set('Asia/Manila');

/*
 * Allow access for either:
 *  - Google OAuth users (have access_token in session)
 *  - SMS-authenticated users (have user_phone / user_id / user_email in session)
 * Still require that the user is verified (is_verified === truthy).
 */
$hasAuthToken = isset($_SESSION['access_token']);
$hasSmsLogin  = isset($_SESSION['user_phone']) || isset($_SESSION['user_id']) || isset($_SESSION['user_email']);

if ((!$hasAuthToken && !$hasSmsLogin) || !isset($_SESSION['is_verified']) || !$_SESSION['is_verified']) {
    header('Location: index.php');
    exit;
}

$errorMsg = "";
// prefer email for contact; fallback to phone if email is not available
$email = $_SESSION['user_email'] ?? ($_SESSION['user_phone'] ?? '');

// --- Fetch locked movie from database ---
// Accept id from GET or POST (POST was missing previously and caused the page to "refresh")
$lockedMovieId = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);
$lockedMovie = null;
if ($lockedMovieId) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_movies WHERE id=?");
    $stmt->execute([$lockedMovieId]);
    $lockedMovie = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($lockedMovie && isset($lockedMovie['showtimes'])) {
        $lockedMovie['showtimes'] = array_map('trim', explode(',', $lockedMovie['showtimes']));
    }
}

if (!$lockedMovie) {
    header('Location: index.php');
    exit;
}

function get_reserved_seats($pdo, $movie_id, $showdate, $showtime) {
    $stmt = $pdo->prepare("SELECT seat_code FROM seats WHERE movie_id=? AND showdate=? AND showtime=? AND status='reserved'");
    $stmt->execute([$movie_id, $showdate, $showtime]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$maxDaysAhead = 30;
$day = $_GET['day'] ?? $_POST['day'] ?? '';
$month = $_GET['month'] ?? $_POST['month'] ?? '';
$year = $_GET['year'] ?? $_POST['year'] ?? '';
$showtime_selected = $_GET['showtime'] ?? $_POST['showtime'] ?? '';
$showdate = ($day && $month && $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : '';
$selected_seats = array_filter(explode(',', $_POST['selected_seats'] ?? ''));

$reserved_seats = [];
if ($showdate && $showtime_selected) {
    $reserved_seats = get_reserved_seats($pdo, $lockedMovie['id'], $showdate, $showtime_selected);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movieId   = $lockedMovieId;
    $movie     = $lockedMovie;
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $showtime  = trim($_POST['showtime'] ?? '');
    $emailPost = trim($_POST['email'] ?? '');
    $quantity  = intval($_POST['quantity'] ?? 0);
    $selected_seats = array_filter(explode(',', $_POST['selected_seats'] ?? ''));

    // prefer posted email if provided (allow users to change)
    if (!empty($emailPost)) {
        $email = $emailPost;
    }

    if (!$movie || empty($firstName) || empty($lastName) || empty($showtime) || empty($email) || empty($quantity) || !$day || !$month || !$year || count($selected_seats) !== $quantity) {
        $errorMsg = "All fields are required, and the number of selected seats must match the quantity.";
    } else {
        $bookingDateTime = strtotime("$showdate $showtime");
        $now = time();
        $maxDateTime = strtotime("+$maxDaysAhead days");
        if ($bookingDateTime < $now) {
            $errorMsg = "You cannot book in the past. Please select a valid date and time.";
        } elseif ($bookingDateTime > $maxDateTime) {
            $errorMsg = "You can only book up to $maxDaysAhead days in advance.";
        } else {
            $unavailable = [];
            foreach ($selected_seats as $seat_code) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM seats WHERE movie_id=? AND showdate=? AND showtime=? AND seat_code=? AND status='reserved'");
                $stmt->execute([$movieId, $showdate, $showtime, $seat_code]);
                if ($stmt->fetchColumn() > 0) {
                    $unavailable[] = $seat_code;
                }
            }
            if ($unavailable) {
                $errorMsg = "Some seats are already reserved: " . implode(', ', $unavailable);
            } else {
                // Resolve user_id robustly: prefer session user_id, otherwise try email, then phone
                $user_id = $_SESSION['user_id'] ?? null;
                if (!$user_id) {
                    if (!empty($_SESSION['user_email'])) {
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                        $stmt->execute([$_SESSION['user_email']]);
                        $user = $stmt->fetch();
                        $user_id = $user['id'] ?? null;
                    }
                }
                if (!$user_id) {
                    if (!empty($_SESSION['user_phone'])) {
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone_number = ?");
                        $stmt->execute([$_SESSION['user_phone']]);
                        $user = $stmt->fetch();
                        $user_id = $user['id'] ?? null;
                    }
                }

                if (!$user_id) {
                    $errorMsg = "User not found in database. Please log in again.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, movie_id, first_name, last_name, email, showtime, seat, quantity, booked_at, showdate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $success = $stmt->execute([
                        $user_id,
                        $movie['id'],
                        $firstName,
                        $lastName,
                        $email,
                        $showtime,
                        implode(',', $selected_seats),
                        $quantity,
                        date('Y-m-d H:i:s'),
                        $showdate
                    ]);
                    if ($success) {
                        $bookingId = $pdo->lastInsertId();
                        foreach ($selected_seats as $seat_code) {
                            $stmt = $pdo->prepare("INSERT INTO seats (booking_id, movie_id, showdate, showtime, seat_code, status)
                                VALUES (?, ?, ?, ?, ?, 'reserved')");
                            $stmt->execute([$bookingId, $movieId, $showdate, $showtime, $seat_code]);
                        }
                        header("Location: confirm.php?booking_id=" . $bookingId);
                        exit;
                    } else {
                        $errorMsg = "Booking failed. Please try again.";
                    }
                }
            }
        }
    }
}

$now = time();
$years = [date('Y'), date('Y', strtotime('+1 year'))];
$months = range(1,12);
$days = range(1,31);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Advanced Booking - <?= htmlspecialchars($lockedMovie['title']) ?> - Pelikula</title>
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
    body { background: var(--bg-main); color: var(--text-main);}
    .navbar { background: var(--bg-card) !important; box-shadow:0 2px 12px rgba(0,0,0,0.25);}
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
    .card { border-radius: 16px !important; background: var(--bg-card); color: var(--text-main);}
    .card-title { font-weight:700; color:var(--accent);}
    .error { color: var(--seat-reserved-bg); text-align: center; margin-bottom: 16px; }
    .success { color: green; text-align: center; margin-bottom: 16px; }
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
    .badge { border-radius: 10px; }
    .badge.bg-secondary { background: var(--badge-bg) !important; }
    .form-text.text-muted, .text-muted { color: var(--text-muted) !important; }
    footer { background: var(--footer-bg); color: var(--brand); }
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
<div class="container d-flex justify-content-center">
  <div class="card d-flex" style="width: 100cap;">
    <div class="card-body">
      <h3 class="card-title mb-4">Book In Advance for <?= htmlspecialchars($lockedMovie['title']) ?></h3>
      <?php if ($errorMsg): ?>
        <div class="error"><?= htmlspecialchars($errorMsg) ?></div>
      <?php endif; ?>

      <form method="get" id="dateShowtimeForm">
        <input type="hidden" name="id" value="<?= htmlspecialchars($lockedMovieId) ?>">
        <div class="row g-2 mb-3">
          <div class="col">
            <label class="form-label">Day</label>
            <select class="form-select" name="day" onchange="document.getElementById('dateShowtimeForm').submit();">
              <option value="">Day</option>
              <?php foreach ($days as $d): ?>
                <option value="<?= $d ?>" <?= ($day==$d) ? 'selected' : '' ?>><?= $d ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col">
            <label class="form-label">Month</label>
            <select class="form-select" name="month" onchange="document.getElementById('dateShowtimeForm').submit();">
              <option value="">Month</option>
              <?php foreach ($months as $m): ?>
                <option value="<?= $m ?>" <?= ($month==$m) ? 'selected' : '' ?>>
                  <?= date('F', mktime(0,0,0,$m,1)) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col">
            <label class="form-label">Year</label>
            <select class="form-select" name="year" onchange="document.getElementById('dateShowtimeForm').submit();">
              <option value="">Year</option>
              <?php foreach ($years as $y): ?>
                <option value="<?= $y ?>" <?= ($year==$y) ? 'selected' : '' ?>><?= $y ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Showtime</label>
          <select class="form-select" name="showtime" onchange="document.getElementById('dateShowtimeForm').submit();">
            <option value="">Select Showtime</option>
            <?php foreach ($lockedMovie['showtimes'] ?? [] as $st): ?>
              <option value="<?= htmlspecialchars($st) ?>" <?= ($showtime_selected==$st) ? 'selected' : '' ?>><?= htmlspecialchars($st) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
      <?php if ($day && $month && $year && $showtime_selected): ?>
      <form action="" method="POST" id="advBookingForm">
        <input type="hidden" name="id" value="<?= htmlspecialchars($lockedMovie['id']) ?>">
        <input type="hidden" name="day" value="<?= htmlspecialchars($day) ?>">
        <input type="hidden" name="month" value="<?= htmlspecialchars($month) ?>">
        <input type="hidden" name="year" value="<?= htmlspecialchars($year) ?>">
        <input type="hidden" name="showtime" value="<?= htmlspecialchars($showtime_selected) ?>">

        <div id="seat-section">
          <label class="mb-2"><b>Select your seats:</b></label>
          <!-- Add this above your seat map -->
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
              $reserved_seats = get_reserved_seats($pdo, $lockedMovie['id'], $showdate, $showtime_selected);
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
          <input type="hidden" name="selected_seats" id="selected_seats" value="<?= htmlspecialchars(implode(',', $selected_seats)) ?>">
        </div>
        <div class="row g-3 mb-3">
          <div class="col">
            <input type="text" name="first_name" class="form-control only-letters" placeholder="First name" required>
          </div>
          <div class="col">
            <input type="text" name="last_name" class="form-control only-letters" placeholder="Last name" required>
          </div>
          <div class="col">
            <input type="number" name="quantity" id="quantity-input" class="form-control" placeholder="Quantity" min="1" required>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-form-label">Email</label>
          <div class="col-sm-10">
            <input type="email" name="email" class="form-control form-control-sm"
                   placeholder="Email Address" required
                   value="<?= htmlspecialchars($email) ?>">
            <small class="form-text text-muted">You may change the email address.</small>
          </div>
        </div>
        <h4>
          <span class="badge bg-secondary p-2 m-2">
          ₱<?= number_format($lockedMovie['price'], 2) ?>
          </span>
        </h4>
        <div class="col-12 mt-3">
          <button type="submit" class="btn" style="background:var(--accent);color:#fff;font-weight:600;">Book In Advance</button>
        </div>
      </form>
      <?php endif; ?>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>