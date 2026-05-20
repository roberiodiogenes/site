<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/newsletter.php
   Endpoint: POST /backend/newsletter.php
   Inscreve um e-mail na tabela newsletter
   ================================================================ */

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderErro('Método não permitido.', 405);
}

// ── Rate limiting: máx. 3 inscrições por IP por hora ─────────
verificarRateLimit('newsletter', 3, 3600);

// ── Receber e validar dados ───────────────────────────────────
$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim(strtolower($body['email'] ?? ''));

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responderErro('E-mail inválido.');
}
if (strlen($email) > 255) {
    responderErro('E-mail muito longo.');
}

// ── Inserir no banco ──────────────────────────────────────────
$pdo = db();

// Verificar se já existe
$stmt = $pdo->prepare("SELECT id, status FROM newsletter WHERE email = ?");
$stmt->execute([$email]);
$existente = $stmt->fetch();

if ($existente) {
    if ($existente['status'] === 'ativo') {
        // Já inscrito — responde como sucesso para não revelar e-mails cadastrados
        responderOk(['mensagem' => 'Inscrição confirmada!']);
    }
    // Estava descadastrado — reativar
    $pdo->prepare("UPDATE newsletter SET status = 'ativo', ip = ? WHERE email = ?")
        ->execute([getIP(), $email]);
    responderOk(['mensagem' => 'Sua inscrição foi reativada! Bem-vindo de volta.']);
}

// Novo cadastro
$pdo->prepare("INSERT INTO newsletter (email, ip) VALUES (?, ?)")
    ->execute([$email, getIP()]);

responderOk(['mensagem' => 'Inscrição realizada com sucesso! Bem-vindo à família.']);
