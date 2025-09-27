<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_email']) || !isset($_GET['id'])) {
    header("Location: profile.php");
    exit;
}

$booking_id = intval($_GET['id']);

// Fetch booking details for email before updating
$stmt = $pdo->prepare("
    SELECT b.*, m.title AS movie_title 
    FROM bookings b 
    LEFT JOIN tbl_movies m ON b.movie_id = m.id 
    WHERE b.id = ? AND b.email = ? AND b.status = 'Upcoming'
");
$stmt->execute([$booking_id, $_SESSION['user_email']]);
$booking = $stmt->fetch();

if ($booking) {
    // Update status to Cancelled in bookings
    $update = $pdo->prepare("UPDATE bookings SET status='Cancelled' WHERE id=? AND email=? AND status='Upcoming'");
    $update->execute([$booking_id, $_SESSION['user_email']]);

    // Update seats to cancelled
    $seat_update = $pdo->prepare("UPDATE seats SET status='cancelled' WHERE booking_id=?");
    $seat_update->execute([$booking_id]);
    
    // Send cancellation email ...
    require_once 'send_email.php';
    if (function_exists('sendUserCancellationEmail')) {
        sendUserCancellationEmail(
            $booking['email'],
            $booking['movie_title'],
            $booking['showdate'],
            $booking['showtime'],
            $booking['first_name'],
            $booking['last_name'],
            $booking['id']
        );
    }
}

header("Location: profile.php");
exit;
?>