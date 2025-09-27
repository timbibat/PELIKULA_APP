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

if (isset($_SESSION['user_email']) && !isset($_SESSION['is_verified'])) {
    $_SESSION['is_verified'] = 1;
}

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

$stmt = $pdo->query("SELECT * FROM tbl_movies ORDER BY id ASC");
$movies = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
if (!isset($movies) || !is_array($movies)) {
    $movies = [];
}

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

function is_showtime_in_future($showdate, $showtime_string) {
    $dt_string = $showdate . ' ' . $showtime_string;
    $dt = DateTime::createFromFormat('Y-m-d h:i A', $dt_string);
    if (!$dt) return false;
    $now = new DateTime();
    return $dt > $now;
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
    $selected_seats = array_filter(explode(',', $_POST['selected_seats'] ?? ''));

    if (empty($firstName) || empty($lastName) || empty($showtime) || empty($email) || empty($quantity) || count($selected_seats) !== $quantity) {
        $errorMsg = "All fields are required, and the number of selected seats must match the quantity.";
    } elseif (!is_showtime_in_future($showdate, $showtime)) {
        $errorMsg = "Selected showtime has already passed. Please select a future showtime.";
    } else {
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
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$_SESSION['user_email']]);
            $user = $stmt->fetch();
            if (!$user) {
                $errorMsg = "User not found in database. Please log in again.";
            } else {
                $user_id = $user['id'];
                $stmt = $pdo->prepare("INSERT INTO bookings (user_id, movie_id, first_name, last_name, email, showtime, seat, quantity, showdate)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $success = $stmt->execute([
                    $user_id,
                    $_POST['movie_id'],
                    $firstName,
                    $lastName,
                    $email,
                    $showtime,
                    implode(',',$selected_seats),
                    $quantity,
                    $showdate
                ]);
                if ($success) {
                    $bookingId = $pdo->lastInsertId();
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
  <style>
    .card-img-fix { height: 400px; object-fit: cover; }
    .center-auth { display: flex; justify-content: center; align-items: center; height: 60vh; }
    .navbar-profile-pic { width: 52px; height: 52px; border-radius: 50%; object-fit: cover; border: 2.5px solid #fff; margin-left: 14px; cursor: pointer; }
    .showtime-btn[disabled] { opacity: 0.6; cursor: not-allowed; text-decoration: line-through; }
    .locked-video { pointer-events: none; user-select: none; }
    .error { color: red; text-align: center; margin-bottom: 16px; }
    .seat-map { display: grid; grid-template-columns: repeat(10, 32px); gap: 8px; margin: 0 auto 12px auto; width: max-content;}
    .seat-btn { width: 32px; height: 32px; font-size: 0.75rem; border-radius: 6px; border: 1px solid #888; cursor: pointer; }
    .seat-btn.available { background: #f5f5f5; }
    .seat-btn.selected { background: #017cff; color: #fff; border-color: #017cff; }
    .seat-btn.reserved { background: #e63946; color: #fff; cursor: not-allowed; border-color: #e63946;}
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg" style="padding: 1.5rem 1rem; background-color: #c8d8e4;">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">
      <img src="pictures/gwapobibat1.png" alt="PELIKULA Logo" height="30" class="d-inline-block align-text-top me-2">
      PELIKULA
    </a>
    <div class="d-flex ms-auto align-items-center">
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
<?php
if (!isset($_SESSION['access_token']) || empty($_SESSION['user_email'])) {
    $authUrl = $client->createAuthUrl();
    echo '<div class="center-auth">
            <div class="card p-4 shadow" style="min-width: 350px;">
              <h4 class="mb-3">Google Authentication Required</h4>
              <p class="mb-3">Connect your Gmail account to book tickets and receive email confirmations.</p>
              <a href="' . htmlspecialchars($authUrl) . '" class="btn btn-primary mb-2">Connect to Gmail</a>
              <hr>
              <a href="admin_login.php" class="btn" style="background-color:#41dc8e; color:#fff;">Login as Admin</a>
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
              <h3><?= htmlspecialchars($selectedMovie['title']) ?></h3>
              <p><?= htmlspecialchars($selectedMovie['description']) ?></p>
              <p><b>Run Time:</b> <?= htmlspecialchars($selectedMovie['duration']) ?></p>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">SM STO TOMAS</h5>
              <h6 class="card-subtitle mb-2 text-body-secondary"><?= date('l, j F Y') ?></h6>
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
                    <a href="index.php?id=<?= $selectedMovie['id'] ?>&showtime=<?= urlencode($time) ?>" class="btn btn-outline-primary showtime-btn <?= ($showtime_selected==$time)?'active':'' ?>" <?= $disabled ?>>
                      <?= htmlspecialchars($time) ?>
                    </a>
                  <?php endforeach; ?>
                </div>
                <div class="col">
                    <input type="number" name="quantity" id="quantity-input" class="form-control" placeholder="Quantity" min="1" required>
                  </div>
                <?php if ($showtime_selected): ?>
                  <div id="seat-section">
                    <label><b>Select your seats:</b></label>
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
                    <small class="text-muted">Red = reserved, Blue = selected, Gray = available</small>
                  </div>
                <?php endif; ?>
                <div class="row g-3 mt-2">
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
                <div class="col-12 mt-3">
                  <button type="submit" class="btn btn-primary">Book Ticket</button>
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
                    if (selectedSeats.length >= parseInt(quantityInput.value || 1)) {
                        alert('You can only select ' + quantityInput.value + ' seat(s).');
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
            while (selectedSeats.length > parseInt(quantityInput.value || 1)) {
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
    echo '<div class="container mt-4"><div class="alert alert-success text-center" role="alert">
            Gmail is authenticated and verified! You can now book your movie tickets.
          </div>
          <div class="row justify-content-center gap-3">';
    foreach ($movies as $movie) {
        $poster = !empty($movie['poster_url']) ? $movie['poster_url'] : 'pictures/default-movie.jpg';
        echo '<div class="card" style="width: 18rem; margin: 1rem;">
                <img src="' . htmlspecialchars($poster) . '" class="card-img-top card-img-fix" alt="Movie Poster">
                <div class="card-body" style="display: flex; flex-direction: column;">
                  <h4 class="card-title"><b>' . htmlspecialchars($movie['title']) . '</b></h4>
                  <p class="card-text" style="flex-grow: 1;">' . htmlspecialchars($movie['description']) . '</p>
                  <a href="index.php?id=' . $movie['id'] . '" class="btn btn-primary mt-auto">Book Ticket</a>
                </div>
              </div>';
    }
    echo '</div></div>';
}
?>
<footer class="py-3 my-4">
  <ul class="nav justify-content-center border-bottom pb-3 mb-3">
    <h6>Cinema Ticket Booking</h6>
  </ul>
  <p class="text-center text-body-secondary">© 2025 Pelikula Cinema, Inc</p>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>