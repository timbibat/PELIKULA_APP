<?php
// Add this section after your existing authentication check in index.php
// Replace the existing auth section around line 60-80

if (!isset($_SESSION['access_token']) || empty($_SESSION['user_email'])) {
    $authUrl = $client->createAuthUrl();
    echo '<div class="center-auth">
            <div class="card p-4 shadow" style="min-width: 350px;">
              <h4 class="mb-3">Login / Register</h4>
              <p class="mb-3 text-muted">Choose your preferred verification method</p>
              
              <!-- Google OAuth -->
              <a href="' . htmlspecialchars($authUrl) . '" class="btn btn-brand mb-2">
                <i class="bi bi-google"></i> Continue with Google
              </a>
              
              <!-- SMS Registration -->
              <a href="register_with_sms.php" class="btn btn-success mb-2">
                <i class="bi bi-phone"></i> Register with Phone Number
              </a>
              
              <hr>
              <small class="text-muted text-center">Phone verification sends an SMS OTP to your mobile number</small>
              
              <hr>
              <a href="admin_login.php" class="btn btn-outline-secondary">Admin Login</a>
            </div>
          </div>';
    // Stop rendering the rest of the page
    echo '</body></html>';
    exit;
}
?>