<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_email']) || !$_SESSION['is_verified']) {
    header("Location: index.php");
    exit;
}
$email = $_SESSION['user_email'];
$movie = $_GET['movie'] ?? '';
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Book Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3>Book Ticket for <?php echo htmlspecialchars($movie); ?></h3>
    <form method="post" action="process_booking.php">
        <div class="mb-3">
            <label class="form-label">Email address (for confirmation):</label>
            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            <small class="form-text text-muted">You may change the email address.</small>
        </div>
        <input type="hidden" name="movie" value="<?php echo htmlspecialchars($movie); ?>">
        <button type="submit" class="btn btn-primary">Book Ticket</button>
    </form>
</div>
</body>
</html>