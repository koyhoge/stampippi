<?php

require_once 'init.php';

$authUrl = $provider->getAuthorizationUrl();

// CSRF対策のためにいまの状態を入れておく
$_SESSION['oauth2state'] = $provider->getState();

header('Location: '.$authUrl);
exit;

