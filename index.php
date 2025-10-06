<?php
require 'vendor/autoload.php';
require 'db.php';
session_start();

date_default_timezone_set('Asia/Manila');

$client = new Google\Client();
$client->setAuthConfig(__DIR__ . '/credentials.json');
$client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/PELIKULA_APP/oauth2callback.php');
$client->setScopes([
    Google\Service\Gmail::GMAIL_SEND,
    Google\Service\Gmail::GMAIL_READONLY,
    Google\Service\Oauth2::USERINFO_EMAIL,
    Google\Service\Oauth2::USERINFO_PROFILE
]);

// Ensure 'is_verified' is set after Google login (extra safety)
if (isset($_SESSION['user_email']) && !isset($_SESSION['is_verified'])) {
    $_SESSION['is_verified'] = 1;
}

// Fetch Google profile picture if token available
if (
    isset($_SESSION['access_token']) &&
    !isset($_SESSION['user_picture']) &&
    isset($_SESSION['user_email'])
) {
    try {
        $client->setAccessToken($_SESSION['access_token']);
        if ($client->isAccessTokenExpired()) {
            unset($_SESSION['access_token']);
        } else {
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
    // expects a 'seats' table with fields: booking_id, movie_id, showdate, showtime, seat_code, status
    $stmt = $pdo->prepare("SELECT seat_code FROM seats WHERE movie_id=? AND showdate=? AND showtime=? AND status='reserved'");
    $stmt->execute([$movie_id, $showdate, $showtime]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$errorMsg = "";
$email = $_SESSION['user_email'] ?? '';

function is_showtime_in_future($showdate, $showtime_string) {
    $dt_string = $showdate . ' ' . $showtime_string; // e.g., 2025-09-26 10:00 AM
    $dt = DateTime::createFromFormat('Y-m-d h:i A', $dt_string);
    if (!$dt) return false;
    $now = new DateTime();
    return $dt > $now;
}

// Handle booking submission
$showtime_selected = $_POST['showtime'] ?? $_GET['showtime'] ?? '';
$showdate = date('Y-m-d'); // default to today
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['movie_id'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $showtime  = trim($_POST['showtime'] ?? '');
    $showdate  = $_POST['showdate'] ?? date('Y-m-d');
    $email     = trim($_POST['email'] ?? '');
    $quantity  = intval($_POST['quantity'] ?? 1);
    $selected_seats = array_filter(explode(',', $_POST['selected_seats'] ?? ''));

    if (empty($firstName) || empty($lastName) || empty($showtime) || empty($email) || empty($quantity) || count($selected_seats) !== $quantity) {
        $errorMsg = "All fields are required, and the number of selected seats must match the quantity.";
    } elseif (!is_showtime_in_future($showdate, $showtime)) {
        $errorMsg = "Selected showtime has already passed. Please select a future showtime.";
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
            // Look up user_id by session email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$_SESSION['user_email']]);
            $user = $stmt->fetch();
            if (!$user) {
                $errorMsg = "User not found in database. Please log in again.";
            } else {
                $user_id = $user['id'];
                // Insert booking
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
                if ($success) {
                    $bookingId = $pdo->lastInsertId();
                    // Reserve seats
                    foreach ($selected_seats as $seat_code) {
                        $stmt = $pdo->prepare("INSERT INTO seats (booking_id, movie_id, showdate, showtime, seat_code, status)
                            VALUES (?, ?, ?, ?, ?, 'reserved')");
                        $stmt->execute([$bookingId, $_POST['movie_id'], $showdate, $showtime, $seat_code]);
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
    /* THEME VARIABLES */
    :root {
      /* Light mode */
      --bg-main: #f7f8fa;
      --bg-card: #ffffff;
      --text-main: #1a1a22;
      --text-muted: #5a5a6e;
      --navbar-bg: #e9ecef;
      --navbar-text: #1a1a22;
      --footer-bg: #e9ecef;
      --brand: #0d6efd;
      --accent: #ffd700; /* gold */
      --badge-bg: #6c757d;
      --seat-available-bg: #f3f5f9;
      --seat-available-border: #9aa3ad;
      --seat-selected-bg: #ffd700;
      --seat-selected-text: #23272f;
      --seat-reserved-bg: #e63946;
      --seat-reserved-text: #ffffff;
      --btn-primary-bg: #0d6efd;
      --btn-primary-text: #ffffff;
    }
    body.dark-mode {
      /* Dark mode */
      --bg-main: #10121a;
      --bg-card: #181a20;
      --text-main: #e6e9ef;
      --text-muted: #aab1b8;
      --navbar-bg: #23272f;
      --navbar-text: #ffffff;
      --footer-bg: #181a20;
      --brand: #ffd700;
      --accent: #017cff;
      --badge-bg: #343a40;
      --seat-available-bg: #23272f;
      --seat-available-border: #ffd700;
      --seat-selected-bg: #ffd700;
      --seat-selected-text: #23272f;
      --seat-reserved-bg: #e63946;
      --seat-reserved-text: #ffffff;
      --btn-primary-bg: #ffd700;
      --btn-primary-text: #23272f;
    }

    /* GLOBAL */
    body { background: var(--bg-main) !important; color: var(--text-main); }
    .text-muted { color: var(--text-muted) !important; }
    .card { border-radius: 16px !important; background: var(--bg-card); color: var(--text-main); }
    .card-title { font-family: 'Montserrat', sans-serif; font-weight: 700; }
    .card-img-fix, .card-img-top { height: 420px; object-fit: cover; border-bottom:4px solid var(--accent); }

    /* NAVBAR */
    .navbar { background: var(--navbar-bg) !important; box-shadow:0 2px 12px rgba(0,0,0,0.25); }
    .navbar .navbar-brand, .navbar .nav-link, .navbar .navbar-text { color: var(--navbar-text) !importanT; }
    .navbar .navbar-brand { color: var(--accent) !important; }

    /* HERO */
    .hero-section { box-shadow: 0 4px 32px 0 rgba(0,0,0,0.2); }

    /* AUTH */
    .center-auth { display: flex; justify-content: center; align-items: center; min-height: 60vh; }

    /* SHOWTIME BUTTONS */
    .showtime-btn[disabled] { opacity: 0.6; cursor: not-allowed; text-decoration: line-through; }

    /* TRAILER */
    .locked-video { pointer-events: none; user-select: none; }

    /* MESSAGES */
    .error { color: var(--seat-reserved-bg); text-align: center; margin-bottom: 16px; }

    /* SEATS */
    .seat-map { display: grid; grid-template-columns: repeat(10, 32px); gap: 8px; margin: 0 auto 12px auto; width: max-content;}
    .seat-btn { width: 32px; height: 32px; font-size: 0.75rem; border-radius: 6px; border: 1px solid var(--seat-available-border); cursor: pointer; }
    .seat-btn.available { background: var(--seat-available-bg) !important; color: var(--accent) !important; }
    .seat-btn.selected { background: var(--seat-selected-bg) !important; color: var(--seat-selected-text) !important; border-color: var(--seat-selected-bg) !important; }
    .seat-btn.reserved { background: var(--seat-reserved-bg) !important; color: var(--seat-reserved-text) !important; border-color: var(--seat-reserved-bg) !important; cursor: not-allowed; }

    /* BUTTONS */
    .btn-brand { background: var(--btn-primary-bg) !important; color: var(--btn-primary-text) !important; border: none; font-weight: 700; }
    .btn-accent { background: var(--accent) !important; color: #23272f !important; font-weight: 700; border: none; }

    /* BADGES */
    .badge { border-radius: 10px; }
    .badge.bg-secondary { background: var(--badge-bg) !important; }

    /* FOOTER */
    footer { background: var(--footer-bg); color: var(--brand); }
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
          <img src="<?php echo $profileImg; ?>" class="navbar-profile-pic" alt="Profile" style="width: 46px; height: 46px; border-radius: 50%; object-fit: cover; border: 2px solid #fff;">
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
if (!isset($_SESSION['access_token']) || empty($_SESSION['user_email'])) {
    $authUrl = $client->createAuthUrl();
    echo '<div class="center-auth">
            <div class="card p-4 shadow" style="min-width: 350px;">
              <h4 class="mb-3">Google Authentication Required</h4>
              <p class="mb-3 text-muted">Connect your Gmail account to book tickets and receive email confirmations.</p>
              <a href="' . htmlspecialchars($authUrl) . '" class="btn btn-brand mb-2">Connect to Gmail</a>
              <hr>
              <a href="admin_login.php" class="btn btn-success">Login as Admin</a>
            </div>
          </div>';
} elseif ($selectedMovie) {
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
                  <?php foreach ($selectedMovie['showtimes'] as $time):
                    $disabled = !is_showtime_in_future($showdate, $time) ? 'disabled' : '';
                  ?>
                    <a href="index.php?id=<?= $selectedMovie['id'] ?>&showtime=<?= urlencode($time) ?>"
                       class="btn btn-outline-primary showtime-btn <?= ($showtime_selected==$time)?'active':'' ?>"
                       <?= $disabled ?>>
                      <?= htmlspecialchars($time) ?>
                    </a>
                  <?php endforeach; ?>
                </div>

                <div class="col mb-3">
                  <input type="number" name="quantity" id="quantity-input" class="form-control" placeholder="Quantity" min="1" required>
                </div>

                <?php if ($showtime_selected): ?>
                  <div id="seat-section">
                    <label class="mb-2"><b>Select your seats:</b></label>
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
                    <small class="text-muted">Red = reserved, Gold = selected, Light = available</small>
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
                  <label class="col-form-label">Email</label>
                  <div class="col-sm-10">
                    <input type="email" name="email" class="form-control form-control-sm" placeholder="Email Address" required value="<?= htmlspecialchars($email) ?>">
                    <small class="form-text text-muted">You may change the email address.</small>
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
      // Alpha-only for names
      document.querySelectorAll('.only-letters').forEach(input => {
        input.addEventListener('input', function () {
          this.value = this.value.replace(/[^A-Za-z\s]/g, '');
        });
      });

      // Seat selection logic
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
    if (!isset($movies) || !is_array($movies)) {
        $movies = [];
    }
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
</footer>

<script>
// THEME TOGGLE
function setMode(mode) {
  // mode: 'dark' or 'light'
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
  // Initialize theme from localStorage (default: dark)
  const theme = localStorage.getItem('theme') || 'dark';
  setMode(theme);

  // Toggle on click
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