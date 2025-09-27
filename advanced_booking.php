<?php
require 'db.php';
session_start();
date_default_timezone_set('Asia/Manila');
if (!isset($_SESSION['access_token']) || !isset($_SESSION['is_verified']) || !$_SESSION['is_verified']) {
    header('Location: index.php');
    exit;
}

$errorMsg = "";
$email = $_SESSION['user_email'] ?? '';

// --- Fetch locked movie from database ---
$lockedMovieId = isset($_GET['id']) ? intval($_GET['id']) : 0;
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

// Set booking window (e.g. next 30 days)
$maxDaysAhead = 30;

// Always take values from GET first, fallback to POST
$day = $_GET['day'] ?? $_POST['day'] ?? '';
$month = $_GET['month'] ?? $_POST['month'] ?? '';
$year = $_GET['year'] ?? $_POST['year'] ?? '';
$showtime_selected = $_GET['showtime'] ?? $_POST['showtime'] ?? '';
$showdate = ($day && $month && $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : '';

$selected_seats = array_filter(explode(',', $_POST['selected_seats'] ?? ''));

// Render reserved seats if all values are selected
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
    $email     = trim($_POST['email'] ?? '');
    $quantity  = intval($_POST['quantity'] ?? 0);
    $selected_seats = array_filter(explode(',', $_POST['selected_seats'] ?? ''));

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
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$_SESSION['user_email']]);
                $user = $stmt->fetch();
                if (!$user) {
                    $errorMsg = "User not found in database. Please log in again.";
                } else {
                    $user_id = $user['id'];
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

// Generate options for days, months, years
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
  <style>
    body { padding-bottom: 50px; }
    .error { color: red; text-align: center; margin-bottom: 16px; }
    .success { color: green; text-align: center; margin-bottom: 16px; }
    .seat-map { display: grid; grid-template-columns: repeat(10, 32px); gap: 8px; margin: 0 auto 12px auto; width: max-content;}
    .seat-btn { width: 32px; height: 32px; font-size: 0.75rem; border-radius: 6px; border: 1px solid #888; cursor: pointer; }
    .seat-btn.available { background: #f5f5f5; }
    .seat-btn.selected { background: #017cff; color: #fff; border-color: #017cff; }
    .seat-btn.reserved { background: #e63946; color: #fff; cursor: not-allowed; border-color: #e63946;}
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg mb-4" style="padding: 1.5rem 1rem; background-color: #c8d8e4;">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">
      <img src="pictures/gwapobibat1.png" alt="PELIKULA Logo" height="30" class="d-inline-block align-text-top me-2">
      PELIKULA
    </a>
    <span class="navbar-text">Advanced Booking: <?= htmlspecialchars($lockedMovie['title']) ?></span>
  </div>
</nav>
<div class="container d-flex justify-content-center">
  <div class="card d-flex" style="width: 100cap;">
    <div class="card-body">
      <h3 class="card-title mb-4">Book In Advance for <?= htmlspecialchars($lockedMovie['title']) ?></h3>
      <?php if ($errorMsg): ?>
        <div class="error"><?= htmlspecialchars($errorMsg) ?></div>
      <?php endif; ?>

      <!-- Mini GET form for date/showtime selection -->
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

      <!-- Main booking form (POST) -->
      <?php if ($day && $month && $year && $showtime_selected): ?>
      <form action="" method="POST" id="advBookingForm">
        <input type="hidden" name="id" value="<?= htmlspecialchars($lockedMovie['id']) ?>">
        <input type="hidden" name="day" value="<?= htmlspecialchars($day) ?>">
        <input type="hidden" name="month" value="<?= htmlspecialchars($month) ?>">
        <input type="hidden" name="year" value="<?= htmlspecialchars($year) ?>">
        <input type="hidden" name="showtime" value="<?= htmlspecialchars($showtime_selected) ?>">

        <div id="seat-section" style="margin-bottom:18px;">
          <label><b>Select your seats:</b></label>
          <div id="seat-map" class="seat-map">
            <?php
              $rows = range('A', 'E');
              $cols = range(1, 10);
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
          <input type="hidden" name="selected_seats" id="selected_seats" value="<?= htmlspecialchars(implode(',', $selected_seats)) ?>">
        </div>
        <input type="hidden" name="seat" value="General Admission">
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
          <button type="submit" class="btn btn-success">Book In Advance</button>
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
          if (btn.hasAttribute('disabled')) return;
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>