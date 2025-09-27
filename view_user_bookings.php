<?php
session_start();
require 'db.php';

// Only allow admin access
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: admin_login.php");
    exit;
}

// Auto-update bookings: Mark as Done past showdate+showtime and not already Cancelled
$pdo->query("
    UPDATE bookings
    SET status='Done'
    WHERE status='Upcoming'
      AND NOW() > STR_TO_DATE(CONCAT(showdate, ' ', showtime), '%Y-%m-%d %l:%i %p')
");

// Fetch all bookings (always show b.email, even if no matching user exists)
$stmt = $pdo->query("
    SELECT 
        b.id AS booking_id,
        b.email AS booking_email,   -- always show this
        u.email AS user_email,      -- null if not found
        u.id AS user_id,
        b.movie_id,
        b.showdate,
        b.showtime,
        b.seat,
        b.quantity,
        b.booked_at,
        b.status
    FROM bookings b
    LEFT JOIN users u ON b.email = u.email
    ORDER BY b.booked_at DESC
");
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Bookings - Admin - Pelikula</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-done { color: green; font-weight: bold; }
        .status-upcoming { color: blue; font-weight: bold; }
        .status-cancelled { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
      <div class="container-fluid">
        <a class="navbar-brand" href="admin_dashboard.php">Pelikula Admin</a>
        <div class="collapse navbar-collapse">
          <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <a class="nav-link btn btn-primary text-white px-3 mx-2" href="view_user_bookings.php">View User Bookings</a>
            </li>
            <li class="nav-item">
              <a class="nav-link btn btn-info text-white px-3 mx-2" href="view_user_replies.php">View User Replies</a>
            </li>
            <li class="nav-item">
              <a class="nav-link btn btn-danger text-white px-3" href="logout.php">Logout</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
    
    <div class="container mt-5">
        <h2 class="mb-4">All User Bookings</h2>
        <?php if (count($bookings) === 0): ?>
            <div class="alert alert-info">No bookings found.</div>
        <?php else: ?>
            <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Booking ID</th>
                        <th>Booking Email</th>
                        <th>User Email (if registered)</th>
                        <th>Movie ID</th>
                        <th>Show Date</th>
                        <th>Showtime</th>
                        <th>Seat</th>
                        <th>Quantity</th>
                        <th>Booked At</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td><?php echo $b['booking_id']; ?></td>
                            <td><?php echo htmlspecialchars($b['booking_email']); ?></td>
                            <td><?php echo $b['user_email'] ? htmlspecialchars($b['user_email']) : '—'; ?></td>
                            <td><?php echo htmlspecialchars($b['movie_id']); ?></td>
                            <td><?php echo htmlspecialchars($b['showdate']); ?></td>
                            <td><?php echo htmlspecialchars($b['showtime']); ?></td>
                            <td><?php echo htmlspecialchars($b['seat']); ?></td>
                            <td><?php echo htmlspecialchars($b['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($b['booked_at']); ?></td>
                            <td>
                                <?php if ($b['status'] === 'Done'): ?>
                                    <span class="status-done">Done</span>
                                <?php elseif ($b['status'] === 'Cancelled'): ?>
                                    <span class="status-cancelled">Cancelled</span>
                                <?php else: ?>
                                    <span class="status-upcoming">Upcoming</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
