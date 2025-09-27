<?php
require 'vendor/autoload.php';
require 'db.php';
session_start();

if (!isset($_SESSION['user_email'])) {
    header('Location: index.php');
    exit;
}

$email = $_SESSION['user_email'];
$verification_token = $_SESSION['verification_token'] ?? bin2hex(random_bytes(16));

// Store token in DB (in case of resend)
$stmt = $pdo->prepare("UPDATE users SET verification_token=? WHERE email=?");
$stmt->execute([$verification_token, $email]);

$client = new Google\Client();
$client->setAuthConfig(__DIR__ . '/credentials.json');
$client->setAccessToken($_SESSION['access_token']);
$service = new Google\Service\Gmail($client);

// Email content
$verify_link = "http://" . $_SERVER['HTTP_HOST'] . "/PELIKULA_APP/verify.php?token=$verification_token";
$subject = "PELIKULA Email Verification";
$body = "Thank you for registering! Please verify your email by clicking this link:\n\n$verify_link\n\nIf you did not request this, please ignore.";

$raw_message = "To: $email\r\nSubject: $subject\r\n\r\n$body";
$encoded_message = base64_encode($raw_message);
$encoded_message = str_replace(['+', '/', '='], ['-', '_', ''], $encoded_message);
$message = new Google\Service\Gmail\Message();
$message->setRaw($encoded_message);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; }
        .card { margin-top: 60px; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body text-center">
                <?php
                try {
                    $service->users_messages->send("me", $message);
                    ?>
                    <div class="mb-3">
                        <svg width="64" height="64" fill="currentColor" class="bi bi-envelope-check text-success" viewBox="0 0 16 16">
                            <path d="M2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2zm0 1h12a1 1 0 0 1 1 1v.217l-7 4.2-7-4.2V4a1 1 0 0 1 1-1zm13 2.383v6.634l-4.708-2.825L15 5.383zm-.034 7.212A1 1 0 0 1 14 13H2a1 1 0 0 1-1-1V5.383l6.708 4.028a.5.5 0 0 0 .584 0L14.966 12.595z"/>
                            <path d="M10.854 7.646a.5.5 0 0 1 .11.638l-.057.07-2 2a.5.5 0 0 1-.638.057l-.07-.057-1-1a.5.5 0 0 1 .638-.765l.07.057.646.647 1.647-1.646a.5.5 0 0 1 .707 0z"/>
                        </svg>
                    </div>
                    <h4 class="mb-2 text-success">Verification Email Sent!</h4>
                    <p class="mb-3">
                        We’ve sent a verification link to <strong><?= htmlspecialchars($email) ?></strong>.<br>
                        Please check your inbox and follow the instructions to complete your registration.
                    </p>
                    <a href="index.php" class="btn btn-primary">Back to Home</a>
                    <?php
                } catch (Exception $e) {
                    ?>
                    <div class="mb-3">
                        <svg width="64" height="64" fill="currentColor" class="bi bi-exclamation-triangle text-danger" viewBox="0 0 16 16">
                            <path d="M7.938 2.016a.13.13 0 0 1 .125 0l6.857 3.94c.11.063.18.177.18.302V12.5c0 .125-.07.239-.18.302l-6.857 3.94a.13.13 0 0 1-.125 0l-6.857-3.94A.344.344 0 0 1 1 12.5V6.258c0-.125.07-.239.18-.302l6.857-3.94zM8 1a1 1 0 0 0-.516.142l-6.857 3.94C.21 5.267 0 5.62 0 6.008V12.5c0 .388.21.741.627.926l6.857 3.94A1 1 0 0 0 8 17a1 1 0 0 0 .516-.142l6.857-3.94A1 1 0 0 0 16 12.5V6.008c0-.388-.21-.741-.627-.926l-6.857-3.94A1 1 0 0 0 8 1z"/>
                            <path d="M8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1-2.002 0 1 1 0 0 1 2.002 0z"/>
                        </svg>
                    </div>
                    <h4 class="mb-2 text-danger">Failed to Send Email</h4>
                    <p class="mb-3">
                        Sorry, we couldn't send the verification email.<br>
                        <span class="text-muted"><?= htmlspecialchars($e->getMessage()) ?></span>
                    </p>
                    <a href="index.php" class="btn btn-secondary">Back to Home</a>
                    <?php
                }
                ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>