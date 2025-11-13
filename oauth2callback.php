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

// Ensure user exists in DB and handle verification token
try {
    // generate a token in case we need to send verification
    $verification_token = bin2hex(random_bytes(16));

    $stmt = $pdo->prepare("SELECT id, is_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if (!empty($user['is_verified'])) {
            // already verified
            $_SESSION['is_verified'] = 1;
            // clear any session verification token
            unset($_SESSION['verification_token']);
        } else {
            // existing, unverified user: update token and mark in session
            $_SESSION['is_verified'] = 0;
            $_SESSION['verification_token'] = $verification_token;
            $stmt = $pdo->prepare("UPDATE users SET verification_token = ? WHERE email = ?");
            $stmt->execute([$verification_token, $email]);
        }
    } else {
        // new user: insert with is_verified = 0, store token
        $_SESSION['is_verified'] = 0;
        $_SESSION['verification_token'] = $verification_token;
        $stmt = $pdo->prepare("INSERT INTO users (email, is_verified, verification_token, created_at) VALUES (?, 0, ?, NOW())");
        $stmt->execute([$email, $verification_token]);
    }
} catch (Exception $e) {
    // In case of DB issues, default to unverified but allow the flow to continue
    $_SESSION['is_verified'] = 0;
    $_SESSION['verification_token'] = $verification_token ?? bin2hex(random_bytes(16));
}

// If user is unverified send them to the verification sender page (it will use the session access token)
if (!empty($_SESSION['is_verified']) && $_SESSION['is_verified'] == 1) {
    header('Location: index.php');
    exit;
} else {
    // send_verification.php will use $_SESSION['access_token'], $_SESSION['user_email'], $_SESSION['verification_token']
    header('Location: send_verification.php');
    exit;
}
?>