<?php
require 'db.php';
require 'sms_gateway_config.php';
session_start();

$smsGateway = new SMSGateway();
$error = '';
$success = '';
$step = $_SESSION['login_step'] ?? 'phone';

// STEP 1: Phone Number Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone_number'])) {
    $phone = trim($_POST['phone_number']);
    $validPhone = $smsGateway->validatePhoneNumber($phone);
    
    if (!$validPhone) {
        $error = 'Invalid phone number format.';
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, email FROM users WHERE phone_number = ?");
        $stmt->execute([$validPhone]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = 'Phone number not registered. <a href="register_with_sms.php" style="color: var(--accent); font-weight: 600;">Register here</a>';
        } else {
            // Generate OTP
            $otp = $smsGateway->generateOTP();
            $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            $stmt = $pdo->prepare("INSERT INTO otp_sessions (phone_number, otp_code, expires_at, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$validPhone, $otp, $expires_at, $_SERVER['REMOTE_ADDR']]);
            $otp_session_id = $pdo->lastInsertId();
            
            // Send SMS
            $message = "PELIKULA Cinema\n\nYour login code: $otp\n\nValid for 10 minutes.";
            $result = $smsGateway->sendSMS($validPhone, $message);
            
            if ($result['success']) {
                $_SESSION['login_otp_id'] = $otp_session_id;
                $_SESSION['login_phone'] = $validPhone;
                $_SESSION['login_user_id'] = $user['id'];
                $_SESSION['login_step'] = 'verify';
                $success = 'OTP sent to your phone!';
                $step = 'verify';
            } else {
                $error = $result['error'];
            }
        }
    }
}

// STEP 2: Verify OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp_code'])) {
    $entered_otp = trim($_POST['otp_code']);
    
    if (!isset($_SESSION['login_otp_id'])) {
        $error = 'Session expired. Please start over.';
        $step = 'phone';
        unset($_SESSION['login_step']);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM otp_sessions WHERE id = ? AND verified = 0");
        $stmt->execute([$_SESSION['login_otp_id']]);
        $otp_record = $stmt->fetch();
        
        if (!$otp_record) {
            $error = 'Invalid session.';
        } elseif (strtotime($otp_record['expires_at']) < time()) {
            $error = 'OTP expired. Please request a new code.';
        } elseif ($otp_record['attempts'] >= 5) {
            $error = 'Too many failed attempts. Please request a new code.';
        } elseif ($entered_otp !== $otp_record['otp_code']) {
            $stmt = $pdo->prepare("UPDATE otp_sessions SET attempts = attempts + 1 WHERE id = ?");
            $stmt->execute([$_SESSION['login_otp_id']]);
            $remaining = 5 - ($otp_record['attempts'] + 1);
            $error = "Incorrect OTP. $remaining attempts remaining.";
        } else {
            // SUCCESS - Log in user
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['login_user_id']]);
            $user = $stmt->fetch();
            
            // Mark OTP verified
            $stmt = $pdo->prepare("UPDATE otp_sessions SET verified = 1 WHERE id = ?");
            $stmt->execute([$_SESSION['login_otp_id']]);
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_phone'] = $user['phone_number'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_verified'] = 1;
            
            // Clean up
            unset($_SESSION['login_otp_id'], $_SESSION['login_phone'], $_SESSION['login_user_id'], $_SESSION['login_step']);
            
            header("Location: index.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - PELIKULA Cinema</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
    :root { --accent: #FF4500; --bg-main: #f7f8fa; --bg-card: #fff; --text-main: #1a1a22; --text-muted: #5a5a6e; }
    body.dark-mode { --accent: #0d6efd; --bg-main: #10121a; --bg-card: #181a20; --text-main: #e6e9ef; --text-muted: #aab1b8; }
    body { background: var(--bg-main); color: var(--text-main); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; padding: 1rem; }
    .auth-card { max-width: 450px; width: 100%; background: var(--bg-card); border-radius: 20px; padding: 2.5rem 2rem; box-shadow: 0 10px 40px rgba(0,0,0,0.15); }
    .logo-section { text-align: center; margin-bottom: 2rem; }
    .logo-section img { max-width: 120px; }
    .auth-card h4 { color: var(--accent); font-weight: 700; margin-bottom: 1.5rem; text-align: center; font-size: 1.75rem; }
    .btn-accent { background: var(--accent) !important; color: #fff !important; font-weight: 600; width: 100%; padding: 0.8rem; border-radius: 12px; border: none; transition: transform 0.2s; font-size: 1.05rem; }
    .btn-accent:hover { transform: translateY(-2px); }
    .otp-input { font-size: 2rem; text-align: center; letter-spacing: 1rem; font-weight: 700; padding: 1rem; border: 2px solid var(--accent); border-radius: 12px; }
    .form-control { border-radius: 10px; padding: 0.75rem; }
    .alert { border-radius: 10px; padding: 1rem; }
    .text-muted { color: var(--text-muted) !important; }
    </style>
</head>
<body>
<div class="auth-card">
    <div class="logo-section">
        <img src="pictures/gwapobibat1.png" alt="Logo">
        <h4 class="mt-3">Login to PELIKULA</h4>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if ($step === 'phone'): ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold"><i class="bi bi-phone"></i> Phone Number</label>
                <input type="tel" name="phone_number" class="form-control form-control-lg" placeholder="09XXXXXXXXX" required pattern="^(\+639|09)\d{9}$" autofocus>
                <small class="form-text text-muted">Philippine mobile number</small>
            </div>
            <button type="submit" class="btn btn-accent mt-3">
                <i class="bi bi-box-arrow-in-right"></i> Send Login Code
            </button>
        </form>
    <?php elseif ($step === 'verify'): ?>
        <div class="text-center mb-4">
            <i class="bi bi-shield-check" style="font-size: 3rem; color: var(--accent);"></i>
            <p class="text-muted mt-3">
                Code sent to<br><strong style="color: var(--accent);"><?= htmlspecialchars($_SESSION['login_phone'] ?? '') ?></strong>
            </p>
        </div>
        <form method="POST">
            <div class="mb-4">
                <input type="text" name="otp_code" class="form-control otp-input" maxlength="6" pattern="\d{6}" placeholder="000000" required autofocus inputmode="numeric">
            </div>
            <button type="submit" class="btn btn-accent">
                <i class="bi bi-check-circle"></i> Verify & Login
            </button>
        </form>
        <div class="text-center mt-4">
            <a href="login_with_sms.php?step=phone" class="btn btn-link" onclick="<?php unset($_SESSION['login_step']); ?>">Request New Code</a>
        </div>
    <?php endif; ?>
    
    <hr class="my-4">
    <div class="text-center">
        <span class="text-muted">Don't have an account?</span><br>
        <a href="register_with_sms.php" class="btn btn-outline-primary mt-2 w-100">
            <i class="bi bi-person-plus"></i> Register
        </a>
    </div>
    <div class="text-center mt-2">
        <a href="index.php" class="btn btn-link text-muted"><i class="bi bi-house"></i> Home</a>
    </div>
</div>
<script>
const theme = localStorage.getItem('theme') || 'light';
if (theme === 'dark') document.body.classList.add('dark-mode');
document.addEventListener('DOMContentLoaded', function() {
    const otpInput = document.querySelector('.otp-input');
    if (otpInput) {
        otpInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 6) this.form.submit();
        });
    }
});
</script>
</body>
</html>