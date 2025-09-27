<?php
require 'db.php';
session_start();
date_default_timezone_set('Asia/Manila'); // Set to your timezone

if (!isset($_SESSION['access_token']) || !isset($_SESSION['is_verified']) || !$_SESSION['is_verified']) {
    header('Location: index.php');
    exit;
}
$movies = json_decode(file_get_contents('data/movies.json'), true);

// Hardcode Demon Slayer (id=1)
$movieId = $_GET['id'] ?? 1;
$movie = array_filter($movies, fn($m) => $m['id'] == $movieId);
$movie = array_values($movie)[0] ?? null;

$errorMsg = "";
$email = $_SESSION['user_email'] ?? '';

// Returns true if showtime today is in the future
function is_showtime_in_future($showtime_string) {
    $showtime_today = date('Y-m-d') . ' ' . $showtime_string;
    $showtime_time = strtotime($showtime_today);
    $now = time();
    return $showtime_time > $now;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $showtime  = trim($_POST['showtime'] ?? '');
    $seat      = trim($_POST['seat'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $quantity  = trim($_POST['quantity'] ?? '');

    if (empty($firstName) || empty($lastName) || empty($showtime) || empty($seat) || empty($email) || empty($quantity)) {
        $errorMsg = "All fields are required. Please fill out the form completely.";
    } elseif (!is_showtime_in_future($showtime)) {
        $errorMsg = "Selected showtime has already passed. Please select a future showtime.";
    } else {
        // Look up user_id by email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$_SESSION['user_email']]);
        $user = $stmt->fetch();
        if (!$user) {
            $errorMsg = "User not found in database. Please log in again.";
        } else {
            $user_id = $user['id'];
            $showdate = date('Y-m-d'); // Always use today's date
            // Insert booking (add showdate)
            $stmt = $pdo->prepare("INSERT INTO bookings (user_id, movie_id, first_name, last_name, email, showtime, seat, quantity, showdate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $success = $stmt->execute([
                $user_id,
                $movie['id'],
                $firstName,
                $lastName,
                $email,
                $showtime,
                $seat,
                $quantity,
                $showdate
            ]);
            if ($success) {
                $bookingId = $pdo->lastInsertId();
                header("Location: confirm.php?booking_id=" . $bookingId);
                exit;
            } else {
                $errorMsg = "Booking failed. Please try again.";
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
  <title><?= htmlspecialchars($movie['title']) ?> - Booking</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { padding-bottom: 50px; }
    .locked-video { pointer-events: none; user-select: none; }
    .error { color: red; text-align: center; margin-bottom: 16px; }
    .showtime-btn[disabled] {
      opacity: 0.6;
      cursor: not-allowed;
      text-decoration: line-through;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg" style="padding: 1.5rem 1rem; background-color: #c8d8e4;">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">
      <img src="pictures/gwapobibat1.png" alt="PELIKULA Logo" height="30" class="d-inline-block align-text-top me-2">
      PELIKULA Cinema app
    </a>
  </div>
</nav>
<h1 class="text-center"><?= htmlspecialchars($movie['title']) ?></h1>
<div class="container d-flex justify-content-center mt-3">
  <div class="card mb-3" style="width: 100cap;">
    <div class="ratio ratio-16x9">
      <video
        src="videos/Demon Slayer_ Kimetsu no Yaiba Infinity Castle _ MAIN TRAILER.mp4"
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
      <h6>Tanjiro Kamado – a boy who joined an organization dedicated to hunting down demons called the Demon Slayer Corps after his younger sister Nezuko was turned into a demon. ...</h6>
      <p class="card-text"><?= htmlspecialchars($movie['description'] ?? '') ?></p>
      <p class="card-text"><small class="text-body-secondary">Run Time: <?= htmlspecialchars($movie['duration'] ?? '2 hours 35 minutes') ?></small></p>
    </div>
  </div>
</div>
<div class="container d-flex justify-content-center">
  <div class="card d-flex" style="width: 100cap;">
    <div class="card-body">
      <a href="advanced_booking.php?id=1" class="btn btn-success mb-3">Book In Advance</a>
      <h5 class="card-title">SM STO TOMAS</h5>
      <h6 class="card-subtitle mb-2 text-body-secondary">
        <?= date('l, j F Y') ?>
      </h6>

      <?php if ($errorMsg): ?>
        <div class="error"><?= htmlspecialchars($errorMsg) ?></div>
      <?php endif; ?>

      <form action="" method="POST" id="bookingForm">
        <input type="hidden" name="movie_id" value="<?= htmlspecialchars($movie['id']) ?>">
        <input type="hidden" name="seat" value="General Admission">
        <input type="hidden" name="showtime" id="showtime-input" value="">
        <div class="d-flex flex-wrap gap-2 mb-3">
          <?php foreach ($movie['showtimes'] as $time): 
            $disabled = !is_showtime_in_future($time) ? 'disabled' : '';
          ?>
            <button type="button" class="btn btn-outline-primary showtime-btn" data-time="<?= htmlspecialchars($time) ?>" <?= $disabled ?>>
              <?= htmlspecialchars($time) ?>
            </button>
          <?php endforeach; ?>
        </div>
        <h4><span class="badge bg-secondary p-2 m-2">₱315.00</span></h4>
        <div class="row g-3">
          <div class="col">
            <input type="text" name="first_name" class="form-control only-letters"
                     placeholder="First name" required>
          </div>
          <div class="col">
            <input type="text" name="last_name" class="form-control only-letters"
                     placeholder="Last name" required>
          </div>
          <div class="col">
            <input type="number" name="quantity" class="form-control"
                     placeholder="Quantity" min="1" required>
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
        </div>
        <div class="col-12 mt-3">
          <button type="submit" class="btn btn-primary">Book Ticket</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const showtimeButtons = document.querySelectorAll('.showtime-btn');
    const showtimeInput = document.getElementById('showtime-input');
    showtimeButtons.forEach(button => {
        if (button.hasAttribute('disabled')) return;
        button.addEventListener('click', () => {
            showtimeButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            showtimeInput.value = button.dataset.time;
        });
    });

    // Prevent keyboard controls on locked video
    document.querySelectorAll('.locked-video').forEach(function(video){
      video.addEventListener('keydown', function(e){e.preventDefault();});
    });

    // Require showtime selection
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
      if (!showtimeInput.value) {
        e.preventDefault();
        alert('Please select a showtime.');
      }
    });
});
// Block numbers/special chars in first/last name
document.querySelectorAll('.only-letters').forEach(input => {
  input.addEventListener('input', function () {
    this.value = this.value.replace(/[^A-Za-z\s]/g, '');
  });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>