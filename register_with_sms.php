<?php
require 'db.php';
require 'sms_gateway_config.php';
session_start();

$smsGateway = new SMSGateway();
$error = '';
$success = '';
$step = $_SESSION['reg_step'] ?? 'phone';

// STEP 1: Phone Number Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone_number'])) {
    $phone = trim($_POST['phone_number']);
    $email = trim($_POST['email'] ?? '');
    
    $validPhone = $smsGateway->validatePhoneNumber($phone);
    
    if (!$validPhone) {
        $error = 'Invalid phone format. Use: 09XXXXXXXXX or +639XXXXXXXXX';
    } else {
        // Check if already registered
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone_number = ?");
        $stmt->execute([$validPhone]);
        if ($stmt->fetch()) {
            $error = 'Phone number already registered. <a href="login_with_sms.php" style="color: var(--accent); font-weight: 600;">Login here</a>';
        } else {
            // Generate OTP
            $otp = $smsGateway->generateOTP();
            $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Store in database
            $stmt = $pdo->prepare("INSERT INTO otp_sessions (phone_number, otp_code, expires_at, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$validPhone, $otp, $expires_at, $_SERVER['REMOTE_ADDR']]);
            $otp_session_id = $pdo->lastInsertId();
            
            // Send SMS
            $message = "PELIKULA Cinema\n\nYour verification code: $otp\n\nValid for 10 minutes. Do not share this code with anyone.";
            $result = $smsGateway->sendSMS($validPhone, $message);
            
            if ($result['success']) {
                $_SESSION['reg_otp_id'] = $otp_session_id;
                $_SESSION['reg_phone'] = $validPhone;
                $_SESSION['reg_email'] = $email;
                $_SESSION['reg_step'] = 'verify';
                $success = 'OTP sent to ' . $validPhone;
                $step = 'verify';
            } else {
                $error = $result['error'];
            }
        }
    }
}

// STEP 2: OTP Verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp_code'])) {
    $entered_otp = trim($_POST['otp_code']);
    
    if (!isset($_SESSION['reg_otp_id'])) {
        $error = 'Session expired. Please request a new code.';
        $step = 'phone';
        unset($_SESSION['reg_step']);
    } else {
        // Fetch OTP record
        $stmt = $pdo->prepare("SELECT * FROM otp_sessions WHERE id = ? AND phone_number = ? AND verified = 0");
        $stmt->execute([$_SESSION['reg_otp_id'], $_SESSION['reg_phone']]);
        $otp_record = $stmt->fetch();
        
        if (!$otp_record) {
            $error = 'Invalid session. Please start over.';
            $step = 'phone';
            unset($_SESSION['reg_step'], $_SESSION['reg_otp_id'], $_SESSION['reg_phone']);
        } elseif (strtotime($otp_record['expires_at']) < time()) {
            $error = 'OTP expired. Please request a new code.';
            $step = 'phone';
            unset($_SESSION['reg_step']);
        } elseif ($otp_record['attempts'] >= 5) {
            $error = 'Too many failed attempts. Please request a new code.';
            $step = 'phone';
            unset($_SESSION['reg_step']);
        } elseif ($entered_otp !== $otp_record['otp_code']) {
            // Increment attempts
            $stmt = $pdo->prepare("UPDATE otp_sessions SET attempts = attempts + 1 WHERE id = ?");
            $stmt->execute([$_SESSION['reg_otp_id']]);
            $remaining = 5 - ($otp_record['attempts'] + 1);
            $error = "Incorrect OTP. $remaining attempts remaining.";
        } else {
            // SUCCESS - Create user account
            $phone = $_SESSION['reg_phone'];
            $email = $_SESSION['reg_email'];
            
            try {
                // Create user
                $stmt = $pdo->prepare("INSERT INTO users (email, phone_number, is_verified, phone_verified, verification_method, created_at) VALUES (?, ?, 1, 1, 'sms', NOW())");
                $stmt->execute([empty($email) ? null : $email, $phone]);
                $user_id = $pdo->lastInsertId();
                
                // Mark OTP as verified
                $stmt = $pdo->prepare("UPDATE otp_sessions SET verified = 1 WHERE id = ?");
                $stmt->execute([$_SESSION['reg_otp_id']]);
                
                // Set session variables
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_phone'] = $phone;
                $_SESSION['user_email'] = $email;
                $_SESSION['is_verified'] = 1;
                
                // Clean up registration session
                unset($_SESSION['reg_otp_id'], $_SESSION['reg_phone'], $_SESSION['reg_email'], $_SESSION['reg_step']);
                
                // Redirect to index
                header("Location: index.php");
                exit;
                
            } catch (PDOException $e) {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - PELIKULA Cinema</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
    :root {
      --accent: #FF4500;
      --bg-main: #f7f8fa;
      --bg-card: #fff;
      --text-main: #1a1a22;
      --text-muted: #5a5a6e;
    }
    body.dark-mode {
      --accent: #0d6efd;
      --bg-main: #10121a;
      --bg-card: #181a20;
      --text-main: #e6e9ef;
      --text-muted: #aab1b8;
    }
    body { 
        background: var(--bg-main); 
        color: var(--text-main); 
        min-height: 100vh; 
        display: flex; 
        align-items: center; 
        justify-content: center;
        font-family: 'Segoe UI', system-ui, sans-serif;
        padding: 1rem;
    }
    .auth-card { 
        max-width: 450px; 
        width: 100%; 
        background: var(--bg-card); 
        border-radius: 20px; 
        padding: 2.5rem 2rem; 
        box-shadow: 0 10px 40px rgba(0,0,0,0.15); 
    }
    .logo-section {
        text-align: center;
        margin-bottom: 2rem;
    }
    .logo-section img {
        max-width: 120px;
        height: auto;
    }
    .auth-card h4 { 
        color: var(--accent); 
        font-weight: 700; 
        margin-bottom: 1.5rem; 
        text-align: center;
        font-size: 1.75rem;
    }
    .btn-accent { 
        background: var(--accent) !important; 
        color: #fff !important; 
        font-weight: 600; 
        width: 100%; 
        padding: 0.8rem; 
        border-radius: 12px; 
        border: none;
        transition: transform 0.2s, box-shadow 0.2s;
        font-size: 1.05rem;
    }
    .btn-accent:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 69, 0, 0.3);
    }
    .otp-input { 
        font-size: 2rem; 
        text-align: center; 
        letter-spacing: 1rem; 
        font-weight: 700;
        padding: 1rem;
        border: 2px solid var(--accent);
        border-radius: 12px;
    }
    .form-control, .form-control:focus {
        border-radius: 10px;
        padding: 0.75rem;
        font-size: 1rem;
    }
    .form-control:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 0.2rem rgba(255, 69, 0, 0.15);
    }
    .alert {
        border-radius: 10px;
        padding: 1rem;
    }
    .text-muted { color: var(--text-muted) !important; }
    </style>
</head>
<body>
<div class="auth-card">
    <div class="logo-section">
        <img src="pictures/gwapobibat1.png" alt="PELIKULA Logo">
        <h4 class="mt-3">Create Account</h4>
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
                <label class="form-label fw-bold">
                    <i class="bi bi-phone"></i> Phone Number
                </label>
                <input type="tel" 
                       name="phone_number" 
                       class="form-control form-control-lg" 
                       placeholder="09XXXXXXXXX" 
                       required 
                       pattern="^(\+639|09)\d{9}$"
                       autofocus>
                <small class="form-text text-muted">Philippine mobile number (09 format)</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">
                    <i class="bi bi-envelope"></i> Email Address (Optional)
                </label>
                <input type="email" 
                       name="email" 
                       class="form-control" 
                       placeholder="your@email.com">
                <small class="form-text text-muted">Email is optional. Used only for booking confirmations.</small>
            </div>
            
            <button type="submit" class="btn btn-accent mt-3">
                <i class="bi bi-arrow-right-circle"></i> Send Verification Code
            </button>
        </form>
        
    <?php elseif ($step === 'verify'): ?>
        <div class="text-center mb-4">
            <i class="bi bi-shield-check" style="font-size: 3rem; color: var(--accent);"></i>
            <p class="text-muted mt-3">
                Enter the 6-digit code sent to<br>
                <strong style="color: var(--accent);"><?= htmlspecialchars($_SESSION['reg_phone'] ?? '') ?></strong>
            </p>
        </div>
        
        <form method="POST">
            <div class="mb-4">
                <input type="text" 
                       name="otp_code" 
                       class="form-control otp-input" 
                       maxlength="6" 
                       pattern="\d{6}" 
                       placeholder="000000" 
                       required 
                       autofocus
                       inputmode="numeric">
            </div>
            
            <button type="submit" class="btn btn-accent">
                <i class="bi bi-check-circle"></i> Verify & Complete Registration
            </button>
        </form>
        
        <div class="text-center mt-4">
            <a href="register_with_sms.php?step=phone" class="btn btn-link" onclick="<?php unset($_SESSION['reg_step']); ?>">
                <i class="bi bi-arrow-counterclockwise"></i> Request New Code
            </a>
        </div>
    <?php endif; ?>
    
    <hr class="my-4">
    
    <div class="text-center">
        <span class="text-muted">Already have an account?</span><br>
        <a href="login_with_sms.php" class="btn btn-outline-secondary mt-2 w-100">
            <i class="bi bi-box-arrow-in-right"></i> Login
        </a>
    </div>
    
    <div class="text-center mt-2">
        <a href="index.php" class="btn btn-link text-muted">
            <i class="bi bi-house"></i> Back to Home
        </a>
    </div>
</div>

<script>
// Dark mode support
const theme = localStorage.getItem('theme') || 'light';
if (theme === 'dark') document.body.classList.add('dark-mode');

// Auto-submit OTP when 6 digits entered
document.addEventListener('DOMContentLoaded', function() {
    const otpInput = document.querySelector('.otp-input');
    if (otpInput) {
        otpInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 6) {
                this.form.submit();
            }
        });
    }
});
</script>
</body>
</html>