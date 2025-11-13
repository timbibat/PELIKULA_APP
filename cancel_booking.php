<?php
require 'db.php';
session_start();

// Ensure an id is provided
if (!isset($_GET['id'])) {
    header("Location: profile.php");
    exit;
}

$booking_id = intval($_GET['id']);
if ($booking_id <= 0) {
    header("Location: profile.php");
    exit;
}

// Fetch booking + movie title + user phone (if available)
$stmt = $pdo->prepare("
    SELECT b.*, m.title AS movie_title, u.phone_number AS owner_phone
    FROM bookings b
    LEFT JOIN tbl_movies m ON b.movie_id = m.id
    LEFT JOIN users u ON b.user_id = u.id
    WHERE b.id = ? AND b.status = 'Upcoming'
");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if (!$booking) {
    // No such upcoming booking
    header("Location: profile.php");
    exit;
}

// Determine ownership: prefer session user_id, then email, then phone -> user_id
$is_owner = false;

// 1) If session has user_id and it matches booking.user_id
if (!empty($_SESSION['user_id']) && !empty($booking['user_id']) && intval($_SESSION['user_id']) === intval($booking['user_id'])) {
    $is_owner = true;
}

// 2) If session has user_email and matches booking email (case-insensitive)
if (!$is_owner && !empty($_SESSION['user_email']) && !empty($booking['email'])) {
    if (trim(strtolower($_SESSION['user_email'])) === trim(strtolower($booking['email']))) {
        $is_owner = true;
    }
}

// 3) If session has user_phone, resolve it to a user_id and compare
if (!$is_owner && !empty($_SESSION['user_phone'])) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone_number = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_phone']]);
    $u = $stmt->fetch();
    if ($u && !empty($booking['user_id']) && intval($u['id']) === intval($booking['user_id'])) {
        $is_owner = true;
    }
}

if (!$is_owner) {
    // Not allowed to cancel this booking
    header("Location: profile.php");
    exit;
}

// Proceed to cancel booking
try {
    $pdo->beginTransaction();

    $update = $pdo->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ? AND status = 'Upcoming'");
    $update->execute([$booking_id]);

    $seat_update = $pdo->prepare("UPDATE seats SET status = 'cancelled' WHERE booking_id = ?");
    $seat_update->execute([$booking_id]);

    $pdo->commit();

    // Send cancellation email if booking has a valid email
    if (!empty($booking['email']) && filter_var($booking['email'], FILTER_VALIDATE_EMAIL)) {
        require_once 'send_email.php';
        if (function_exists('sendUserCancellationEmail')) {
            // Use booking values (first_name, last_name, etc.)
            try {
                sendUserCancellationEmail(
                    $booking['email'],
                    $booking['movie_title'] ?? '',
                    $booking['showdate'] ?? '',
                    $booking['showtime'] ?? '',
                    $booking['first_name'] ?? '',
                    $booking['last_name'] ?? '',
                    $booking['id'] ?? null
                );
            } catch (Exception $e) {
                // Email failure should not block the flow. You can log $e->getMessage() to a log file.
            }
        }
    }
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // On error, redirect back (optionally log error)
    header("Location: profile.php");
    exit;
}

header("Location: profile.php");
exit;
?>