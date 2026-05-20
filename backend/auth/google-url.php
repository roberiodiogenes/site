<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/auth/google-url.php
   Endpoint: GET /backend/auth/google-url.php
   Gera e retorna a URL de autorização do Google com state CSRF
   ================================================================ */

require_once __DIR__ . '/../config.php';

iniciarSessao();

// Gerar state aleatório para proteção CSRF
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'prompt'        => 'select_account',
]);

$url = 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;

responderOk(['url' => $url]);
