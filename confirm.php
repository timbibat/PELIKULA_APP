<?php
// confirm.php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

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
        // Require once in a try/catch so we catch missing classes or other fatal-ish errors
        require_once $sendEmailPath;

        if (function_exists('sendBookingEmail')) {
            // Call the function and capture the result (true on success, string on error)
            $sendResult = sendBookingEmail(
                $toEmail,
                $movieTitle,
                $showtime,
                $seat,
                $firstName,
                $lastName,
                $quantity,
                $totalPrice,
                $booking_id // <- this is now valid!
            );
        } else {
            $sendResult = 'sendBookingEmail() function not defined in send_email.php';
        }
    } else {
        $sendResult = 'send_email.php file not found. Email not sent.';
    }
} catch (\Throwable $e) {
    // Capture any runtime error (including PHPMailer class missing, exceptions, etc.)
    $sendResult = 'Error sending email: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Booking Confirmation — <?= htmlspecialchars($movieTitle) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
  :root {
    --bg: #ffffff; /* white background */
    --card: #ffffff; /* light blue-gray card */
    --accent: #000000ff; /* deep crimson (unchanged) */
    --muted: #666666; /* dark gray for text */
    --ticket-border: ffff10; /* crimson border tint */
  }

  body {
    background: var(--bg);
    color: #1a1a1a;
    font-family: "Segoe UI", Roboto, -apple-system, "Helvetica Neue", Arial;
    padding: 40px 16px;
  }

  .ticket-wrap {
    max-width: 760px;
    margin: 0 auto;
  }

  .ticket {
    background: var(--card);
    border-radius: 14px;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
    overflow: hidden;
    border: 1px solid var(--ticket-border);
  }

  .ticket-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 18px 22px;
  background: #c8d8e4; /* changed from gradient to solid color */
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}


  .ticket-head .title {
    font-size: 20px;
    font-weight: 700;
    color: var(--accent);
  }

  .ticket-head .sub {
    font-size: 13px;
    color: var(--muted);
  }

  .ticket-body {
    display: flex;
    gap: 18px;
    padding: 20px;
    align-items: flex-start;
  }

  .left {
    flex: 1.1;
  }

.right {
  width: 220px;
  background: #ffffff; /* changed from gradient to solid color */
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

  .detail-row:last-child {
    border-bottom: none;
  }

  .detail-row .label {
    color: var(--muted);
    font-size: 13px;
  }

  .detail-row .value {
    font-weight: 600;
    color: #111;
  }

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
    color: var(--muted);
    font-size: 14px;
  }

 .btn-home {
  display: block;
  margin: 26px auto 0;
  width: fit-content;
  background: blue;   /* Changed to blue */
  color: #ffffff;
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
    .ticket-body {
      flex-direction: column;
    }

    .right {
      width: 100%;
      border-left: none;
      border-top: 1px dashed rgba(0, 0, 0, 0.05);
      padding-top: 14px;
    }
  }
</style>


</head>
<body>
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
            <div style="text-align:right; color:var(--muted); font-size:12px; margin-top:6px;">
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
            <div style="font-size:13px; color:var(--muted); margin-bottom:8px;">Order summary</div>
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

            <div style="padding-top:10px; color:var(--muted);">
              <strong>Note:</strong> There was an issue sending the confirmation email.
            </div>

            <div style="margin-top:12px; color:#ffdede; background:rgba(255,255,255,0.02); padding:10px; border-radius:6px;">
              <?= nl2br(htmlspecialchars((string)$sendResult)) ?>
            </div>
          </div>

          <div class="right">
            <div style="font-size:13px; color:var(--muted); margin-bottom:8px;">Order summary</div>
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