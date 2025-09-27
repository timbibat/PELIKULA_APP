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

// Save to session
$_SESSION['access_token'] = $token;
$_SESSION['user_email'] = $email;

// Redirect back to index
header('Location: index.php');
exit;
?>