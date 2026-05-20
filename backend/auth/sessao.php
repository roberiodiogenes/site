<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/auth/sessao.php
   Endpoint: GET /backend/auth/sessao.php
   Retorna dados do usuário logado (para o JS verificar no carregamento)
   ================================================================ */

require_once __DIR__ . '/../config.php';

iniciarSessao();

if (!empty($_SESSION['usuario_id'])) {
    responderOk([
        'logado'  => true,
        'usuario' => [
            'id'    => $_SESSION['usuario_id'],
            'nome'  => $_SESSION['usuario_nome'],
            'email' => $_SESSION['usuario_email'],
            'foto'  => $_SESSION['usuario_foto'] ?? null,
        ],
    ]);
} else {
    responderOk(['logado' => false]);
}
