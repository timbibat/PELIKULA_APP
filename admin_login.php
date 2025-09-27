<?php
require 'db.php';
session_start();
$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Admin credentials (password is hardcoded for now)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? AND is_admin=1");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && $password === 'password123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = $admin['email'];
        header("Location: admin_dashboard.php");
        exit;
    } else {
        $login_error = 'Invalid admin credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - Pelikula</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container" style="max-width: 400px; margin-top: 80px;">
        <div class="card shadow">
            <div class="card-body">
                <h4 class="mb-3">Admin Login</h4>
                <?php if ($login_error): ?>
                    <div class="alert alert-danger"><?php echo $login_error; ?></div>
                <?php endif; ?>
                <form method="post" autocomplete="off">
                    <div class="mb-3">
                        <label for="email" class="form-label">Admin Email</label>
                        <input type="email" class="form-control" id="email" name="email" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Admin Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                    <a href="index.php" class="btn btn-link w-100 mt-2">Back to Home</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>