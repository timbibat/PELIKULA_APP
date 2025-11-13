<?php
require 'vendor/autoload.php';
require 'db.php';
session_start();

$client = new Google\Client();
$client->setAuthConfig(__DIR__ . '/credentials.json');
$client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/PELIKULA_APP/oauth2callback.php');
$client->setScopes([Google\Service\Gmail::GMAIL_SEND, Google\Service\Gmail::GMAIL_READONLY, Google\Service\Oauth2::USERINFO_EMAIL]);

if (!isset($_GET['code'])) {
    header('Location: index.php');
    exit;
}

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
if (isset($token['error'])) {
    die('Error fetching OAuth token: ' . $token['error']);
}

$client->setAccessToken($token);

$oauth2 = new Google\Service\Oauth2($client);
$userInfo = $oauth2->userinfo->get();

if (empty($userInfo->email)) {
    die('Failed to retrieve email from Google. Please try again.');
}

$email = $userInfo->email;

// Save OAuth token + email to session
$_SESSION['access_token'] = $token;
$_SESSION['user_email'] = $email;

// Option A: Auto-verify Google-authenticated users (quick workaround)
// This will set is_verified = 1 in DB (or insert user as verified) so profile.php won't redirect.
// If you prefer to require email verification via send_verification.php, do NOT apply this change.

try {
    // check if user exists
    $stmt = $pdo->prepare("SELECT id, is_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // set session and, if not marked verified, optionally mark verified now
        $_SESSION['is_verified'] = !empty($user['is_verified']) ? 1 : 1; // force to 1 for Google sign-ins
        // persist to DB: mark user verified
        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE email = ?");
        $stmt->execute([$email]);
    } else {
        // create a verified user row
        $_SESSION['is_verified'] = 1;
        $stmt = $pdo->prepare("INSERT INTO users (email, is_verified, created_at) VALUES (?, 1, NOW())");
        $stmt->execute([$email]);
    }
} catch (Exception $e) {
    // on DB failure, set session verified so user can proceed (but log/inspect server logs)
    $_SESSION['is_verified'] = 1;
}

// Redirect back to index (now user will be treated as verified)
header('Location: index.php');
exit;
?>