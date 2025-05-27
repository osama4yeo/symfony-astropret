<?php

require 'vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/config/google-credentials.json');
$client->addScope(Google_Service_Calendar::CALENDAR);
$client->setAccessType('offline');
$client->setPrompt('select_account consent');

$authUrl = $client->createAuthUrl();
printf("Ouvre ce lien dans ton navigateur :\n%s\n", $authUrl);
print('Code d\'authentification : ');
$authCode = trim(fgets(STDIN));

$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

file_put_contents(__DIR__ . '/config/token.json', json_encode($accessToken));
echo "✅ token.json enregistré !\n";
