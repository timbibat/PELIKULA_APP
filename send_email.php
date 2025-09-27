<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Booking confirmation function (existing)
function sendBookingEmail($to, $movie, $showtime, $seat, $firstName, $lastName, $quantity, $totalPrice, $bookingId = null) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'pelikulacinema@gmail.com';
        $mail->Password   = 'ropmliocwcdxwspk';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('pelikulacinema@gmail.com', 'PELIKULA');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = 'Booking Confirmation - ' . htmlspecialchars($movie);

        $body  = "<h2>Booking Confirmed!</h2>";
        if ($bookingId !== null) {
            $body .= "<p><strong>Booking ID:</strong> " . htmlspecialchars((string)$bookingId) . "</p>";
        }
        $body .= "<p><strong>Name:</strong> " . htmlspecialchars($firstName) . " " . htmlspecialchars($lastName) . "</p>";
        $body .= "<p><strong>Movie:</strong> " . htmlspecialchars($movie) . "</p>";
        $body .= "<p><strong>Showtime:</strong> " . htmlspecialchars($showtime) . "</p>";
        $body .= "<p><strong>Seat:</strong> " . htmlspecialchars($seat) . "</p>";
        $body .= "<p><strong>Quantity:</strong> " . htmlspecialchars($quantity) . "</p>";
        $body .= "<p><strong>Total Price:</strong> ₱" . htmlspecialchars($totalPrice) . "</p>";
        $body .= "<p>Thank you for booking with PELIKULA!</p>";

        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

// Admin reply function
function sendAdminReplyEmail($to, $admin_message, $user_message, $admin_reply_id) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'pelikulacinema@gmail.com';
        $mail->Password   = 'ropmliocwcdxwspk';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('pelikulacinema@gmail.com', 'PELIKULA Admin');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = "Admin reply to your message [Reply ID: {$admin_reply_id}] - Pelikula Cinema";

        $body  = "<p>Hello,</p>";
        $body .= "<p><strong>An admin has replied to your message:</strong></p>";
        $body .= "<blockquote style='background:#f5f5f5;padding:10px;border-left:3px solid #017cff;'>" . nl2br(htmlspecialchars($admin_message)) . "</blockquote>";
        $body .= "<hr>";
        $body .= "<p><small>Replying to Reply ID: {$admin_reply_id}</small></p>";
        $body .= "<p>Your original message:</p>";
        $body .= "<blockquote style='background:#f5f5f5;padding:10px;border-left:3px solid #aaa;'>" . nl2br(htmlspecialchars($user_message)) . "</blockquote>";
        $body .= "<br><p>Thank you,<br>Pelikula Cinema Admin</p>";

        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Admin Reply Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

// User cancellation notification function (FIXED)
function sendUserCancellationEmail($to, $movie, $showdate, $showtime, $firstName, $lastName, $bookingId = null) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'pelikulacinema@gmail.com';
        $mail->Password   = 'ropmliocwcdxwspk';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('pelikulacinema@gmail.com', 'PELIKULA');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = "Booking Cancelled - " . htmlspecialchars($movie);

        $body  = "<h2>Booking Cancelled</h2>";
        $body .= "<p>Hi " . htmlspecialchars($firstName) . " " . htmlspecialchars($lastName) . ",</p>";
        if ($bookingId !== null) {
            $body .= "<p><strong>Booking ID:</strong> " . htmlspecialchars((string)$bookingId) . "</p>";
        }
        $body .= "<p>Your booking for <strong>" . htmlspecialchars($movie) . "</strong> on <strong>"
            . htmlspecialchars($showdate) . "</strong> at <strong>"
            . htmlspecialchars($showtime) . "</strong> has been <span style='color:red;'>CANCELLED</span>.</p>";
        $body .= "<p>If you did not request this cancellation, please contact our support team immediately.</p>";
        $body .= "<p>Thank you,<br>PELIKULA Cinema</p>";

        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Cancellation email could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>