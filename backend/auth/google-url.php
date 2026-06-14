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

// Cookie extra SameSite=None: necessário quando o Google usa response_mode=form_post,
// pois o POST é cross-site (accounts.google.com → roberiodiogenes.com) e o SameSite=Lax
// do PHPSESSID impede que a sessão chegue no callback. Este cookie CHEGA no form_post.
setcookie('oauth_state', $state, [
    'expires'  => time() + 600,                  // 10 minutos
    'path'     => '/backend/auth/',
    'secure'   => AMBIENTE === 'producao',
    'httponly' => true,
    'samesite' => 'None',
]);

$params = http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'response_mode' => 'form_post',  // Google envia POST ao callback (evita ModSecurity no GET)
    'scope'         => 'openid email profile',
    'state'         => $state,
    'prompt'        => 'select_account',
]);

$url = 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;

responderOk(['url' => $url]);
