<?php
require 'vendor/autoload.php';
require 'db.php';
session_start();

if (!isset($_SESSION['user_email']) || !isset($_SESSION['is_verified']) || !$_SESSION['is_verified']) {
    header("Location: index.php");
    exit;
}

$email = $_SESSION['user_email'];

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Update status for past bookings if not cancelled
$pdo->query("
    UPDATE bookings
    SET status='Done'
    WHERE status='Upcoming'
      AND NOW() > STR_TO_DATE(CONCAT(showdate, ' ', showtime), '%Y-%m-%d %l:%i %p')
");

// Fetch bookings (use status column directly)
$stmt = $pdo->prepare("
    SELECT b.*, m.title AS movie_title
    FROM bookings b
    LEFT JOIN tbl_movies m ON b.movie_id = m.id
    WHERE b.email = ?
    ORDER BY b.id DESC
");
$stmt->execute([$email]);
$bookings = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Profile - Pelikula</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body {
        background: linear-gradient(120deg, #f8fafc 0%, #e2eafc 100%);
        min-height: 100vh;
    }
    .profile-header {
        background: #c8d8e4;
        color: #000000;
        border-radius: 12px;
        padding: 2rem 1.5rem 1rem 1.5rem;
        box-shadow: 0 3px 12px rgba(0,0,0,0.07);
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }
    .profile-header h2 {
        margin-bottom: 0.2rem;
        font-weight: 700;
    }
    .profile-header small {
        color: #000000;
    }
    .profile-header .btn-container {
        display: flex;
        gap: 8px;
    }
    .goback-btn {
        background: #017cff;
        color: #fff;
        border-radius: 8px;
        border: none;
        padding: 4px 14px;
        font-weight: 500;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        transition: background 0.2s;
    }
    .goback-btn:hover {
        background: #015ecb;
        color: #fff;
    }
    .logout-btn {
        background: #e63946;
        color: #fff;
        border-radius: 8px;
        border: none;
        padding: 4px 14px;
        font-weight: 500;
        font-size: 0.85rem;
        transition: background 0.2s;
    }
    .logout-btn:hover {
        background: #b81f2d;
    }
    .table thead {
        background: #343a40;
        color: #fff;
    }
    .table-bordered {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    }
    .no-bookings {
        text-align: center;
        color: #888;
        font-style: italic;
        padding: 2rem 0;
    }
    </style>
</head>
<body>
<div class="container mt-5" style="max-width:900px;">
    <div class="profile-header">
        <div>
            <h2> Welcome, <?php echo htmlspecialchars($user['email']); ?></h2>
            <small>Profile & Booking Overview</small>
        </div>
        <div>
            <button class="goback-btn" onclick="window.location.href='index.php'"><i class="bi bi-arrow-left"></i> Back</button>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    <div class="card shadow-sm mb-5">
        <div class="card-body">
            <h4 class="mb-4" style="font-weight:600; color:#343a40;">Your Bookings</h4>
            <?php if (count($bookings) === 0): ?>
                <div class="no-bookings">You don't have any bookings yet.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:60px;">ID</th>
                            <th>Movie</th>
                            <th>Show Date</th>
                            <th>Showtime</th>
                            <th>Seat</th>
                            <th>Quantity</th>
                            <th>Booked At</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td><?php echo $b['id']; ?></td>
                            <td><?php echo htmlspecialchars($b['movie_title'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($b['showdate']); ?></td>
                            <td><?php echo htmlspecialchars($b['showtime']); ?></td>
                            <td><?php echo htmlspecialchars($b['seat']); ?></td>
                            <td><?php echo htmlspecialchars($b['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($b['booked_at']); ?></td>
                            <td>
                              <?php if ($b['status'] === "Done"): ?>
                                <span style="color: green; font-weight: bold;">Done</span>
                              <?php elseif ($b['status'] === "Cancelled"): ?>
                                <span style="color: red; font-weight: bold;">Cancelled</span>
                              <?php else: ?>
                                <span style="color: blue;">Upcoming</span>
                              <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($b['status'] === "Upcoming"): ?>
                                  <a href="cancel_booking.php?id=<?php echo $b['id']; ?>" class="btn btn-danger btn-sm">Cancel</a>
                                <?php elseif ($b['status'] === "Done"): ?>
                                  <button class="btn btn-secondary btn-sm" disabled>Done</button>
                                <?php elseif ($b['status'] === "Cancelled"): ?>
                                  <button class="btn btn-danger btn-sm" disabled>Cancelled</button>
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
</div>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</body>
</html>