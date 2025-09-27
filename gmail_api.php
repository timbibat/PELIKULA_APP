<?php
require __DIR__ . '/vendor/autoload.php';

use Google\Client;
use Google\Service\Gmail;

function getClient() {
    $client = new Client();
    $client->setApplicationName('Pelikula App');
    $client->setScopes([Gmail::GMAIL_SEND, Gmail::GMAIL_READONLY]);
    $client->setAuthConfig(__DIR__ . '/credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');
    if (file_exists(__DIR__ . '/token.json')) {
        $accessToken = json_decode(file_get_contents(__DIR__ . '/token.json'), true);
        $client->setAccessToken($accessToken);
    }
    return $client;
}