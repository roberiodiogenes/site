<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/auth/logout.php
   Endpoint: POST /backend/auth/logout.php
   Destrói a sessão do usuário
   ================================================================ */

require_once __DIR__ . '/../config.php';

iniciarSessao();
$_SESSION = [];
session_destroy();

responderOk(['mensagem' => 'Você saiu com sucesso.']);
