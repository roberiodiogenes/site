<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/bio-certificado.php
   Envia certificado de Leitor Embaixador por e-mail.

   POST { email, genero, sessao_id }
   ================================================================ */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderErro('Método não permitido.', 405);
}

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$email     = trim($body['email']      ?? '');
$genero    = substr(trim($body['genero']    ?? ''), 0, 20);
$sessao_id = substr(trim($body['sessao_id'] ?? ''), 0, 64);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responderErro('E-mail inválido.');
}

/* ── Buscar número do embaixador ────────────────────────────── */
$numero = 0;
try {
    $pdo  = db();
    $stmt = $pdo->prepare("SELECT id FROM embaixadores WHERE sessao_id = ? LIMIT 1");
    $stmt->execute([$sessao_id]);
    $numero = (int)($stmt->fetchColumn() ?: 0);
} catch (Throwable $e) { /* silencioso */ }

/* ── Enviar certificado ─────────────────────────────────────── */
try {
    Mailer::enviarCertificadoEmbaixador($email, $genero, $numero);
} catch (Throwable $e) {
    error_log('[bio-certificado] ' . $e->getMessage());
}

responderOk();
