<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/resgatar-presente.php
   Vincula um presente (token) ao usuário logado.

   POST { token: "abc123..." }
     → Adiciona o livro à biblioteca do usuário
     → Marca o presente como resgatado
     → Retorna { ok: true, livro_slug, leitor_url }
   ================================================================ */

require_once __DIR__ . '/config.php';
iniciarSessao();

if (empty($_SESSION['usuario_id'])) {
    responderErro('Você precisa estar logado para resgatar o presente.', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderErro('Método não permitido.', 405);
}

$uid  = (int) $_SESSION['usuario_id'];
$pdo  = db();
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$token= trim($body['token'] ?? '');

if (!$token || strlen($token) < 20) {
    responderErro('Token inválido.');
}

/* ── Buscar o presente ────────────────────────────────────────── */
$st = $pdo->prepare(
    "SELECT id, livro_slug, email_presenteado, status, resgatado_por
     FROM presentes
     WHERE token_acesso = ?
       AND status = 'aprovado'
     LIMIT 1"
);
$st->execute([$token]);
$presente = $st->fetch(PDO::FETCH_ASSOC);

if (!$presente) {
    responderErro('Presente não encontrado, ainda não pago ou token inválido.');
}

/* ── Verificar se já foi resgatado por outro usuário ─────────── */
if ($presente['resgatado_por'] && (int)$presente['resgatado_por'] !== $uid) {
    responderErro('Este presente já foi resgatado por outra conta.');
}

/* ── Adicionar livro à biblioteca do usuário ─────────────────── */
$slug = $presente['livro_slug'];

$pdo->prepare(
    "INSERT INTO compras (usuario_id, livro_slug, preco_pago, status, gateway, ref_externa)
     VALUES (?, ?, 0.00, 'aprovada', 'presente', ?)
     ON DUPLICATE KEY UPDATE
       status    = 'aprovada',
       gateway   = 'presente'"
)->execute([$uid, $slug, 'resgate_' . $presente['id']]);

/* ── Marcar o presente como resgatado ────────────────────────── */
$pdo->prepare(
    "UPDATE presentes
     SET resgatado_por = ?, resgatado_em = NOW()
     WHERE id = ? AND (resgatado_por IS NULL OR resgatado_por = ?)"
)->execute([$uid, $presente['id'], $uid]);

/* ── Verificar se o e-mail do presente bate com o usuário ─────── */
$stU = $pdo->prepare("SELECT email FROM usuarios WHERE id = ? LIMIT 1");
$stU->execute([$uid]);
$emailUsuario = $stU->fetchColumn();
$emailPresente = strtolower($presente['email_presenteado']);

// Avisar (mas não bloquear) se o e-mail for diferente
$aviso = null;
if ($emailUsuario && strtolower($emailUsuario) !== $emailPresente) {
    $aviso = 'Você está resgatando com uma conta de e-mail diferente da destinatária, mas o acesso foi liberado.';
}

responderOk([
    'livro_slug'  => $slug,
    'leitor_url'  => SITE_URL . '/leitor/index.html',
    'aviso'       => $aviso,
]);
