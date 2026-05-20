<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/config.example.php
   Modelo de configuração — COPIE PARA config.php e preencha.
   Este arquivo SIM entra no Git. O config.php NÃO entra.
   ================================================================ */

define('AMBIENTE', 'local'); // 'local' ou 'producao'

// Banco de dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'roberio_site');

// Google OAuth (https://console.cloud.google.com/)
define('GOOGLE_CLIENT_ID',     'COLE_AQUI_SEU_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'COLE_AQUI_SEU_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI',  'http://localhost/site/backend/auth/google-callback.php');

// JWT / Sessão
define('JWT_SECRET', 'TROQUE_POR_FRASE_SECRETA_LONGA');
define('SESSAO_DURACAO', 86400 * 30);
define('SITE_URL', 'http://localhost/site');

// ── Cole aqui o restante do config.php original ──────────────
