<?php
require 'db.php';

$token = $_GET['token'] ?? '';
$status = '';
$message = '';
$alert_class = '';
$icon = '';

if ($token) {
    $stmt = $pdo->prepare("UPDATE users SET is_verified=1 WHERE verification_token=?");
    $stmt->execute([$token]);
    if ($stmt->rowCount()) {
        $status = 'success';
        $message = "Your email has been verified! You can now use all features.";
        $alert_class = 'success';
        $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-check-circle text-success mb-2" viewBox="0 0 16 16">
  <circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="2" fill="none"/>
  <path fill="currentColor" d="M10.97 6.97a.75.75 0 0 1 1.07 1.05l-3.477 4.243a.75.75 0 0 1-1.08.02L5.324 10.384a.75.75 0 1 1 1.06-1.06l1.093 1.093 3.492-4.438z"/>
</svg>';
    } else {
        $status = 'danger';
        $message = "Invalid or already used verification link.";
        $alert_class = 'danger';
        $icon = '<svg width="48" height="48" fill="currentColor" class="bi bi-x-circle text-danger mb-2" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14z"/><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/></svg>';
    }
} else {
    $status = 'danger';
    $message = "No verification token provided.";
    $alert_class = 'danger';
    $icon = '<svg width="48" height="48" fill="currentColor" class="bi bi-exclamation-circle text-danger mb-2" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14z"/><path d="M7.002 11a1 1 0 1 0 2 0 1 1 0 0 0-2 0zm.1-4.995a.905.905 0 0 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 6.005z"/></svg>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; }
        .verify-card { margin-top: 80px; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow verify-card">
                <div class="card-body text-center">
                    <?= $icon ?>
                    <h4 class="mb-3 text-<?= $alert_class ?>">
                        <?= ($status === 'success') ? 'Email Verified!' : 'Verification Failed' ?>
                    </h4>
                    <p class="mb-4"><?= htmlspecialchars($message) ?></p>
                    <a href="index.php" class="btn btn-<?= ($status === 'success') ? 'success' : 'secondary' ?>">Return to Home</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>