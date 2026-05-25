<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/auth/register.php (ATUALIZADO)
   Cadastro de novo usuário com envio de e-mail de boas-vindas.

   MUDANÇA: adicionado Mailer::enviarBoasVindas() após o INSERT.
   O restante do arquivo é idêntico ao original.
   ================================================================ */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../mailer.php';   // ← NOVO

iniciarSessao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderErro('Método não permitido.', 405);
}

verificarRateLimit('cadastro', 5, 3600);

$body = json_decode(file_get_contents('php://input'), true) ?? [];

$nome  = trim($body['nome']  ?? '');
$email = trim(strtolower($body['email'] ?? ''));
$senha = $body['senha'] ?? '';

if (!$nome || mb_strlen($nome) < 2) {
    responderErro('Nome deve ter no mínimo 2 caracteres.');
}
if (mb_strlen($nome) > 120) {
    responderErro('Nome muito longo.');
}
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responderErro('E-mail inválido.');
}
if (!$senha || strlen($senha) < 8) {
    responderErro('Senha deve ter no mínimo 8 caracteres.');
}
if (!preg_match('/[A-Za-z]/', $senha) || !preg_match('/[0-9]/', $senha)) {
    responderErro('Senha deve conter letras e números.');
}

$pdo = db();

$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    responderErro('Este e-mail já está cadastrado. Tente fazer login.');
}

$hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
$ip   = getIP();

$pdo->prepare(
    "INSERT INTO usuarios (nome, email, senha, ip_cadastro) VALUES (?, ?, ?, ?)"
)->execute([$nome, $email, $hash, $ip]);

$userId = (int) $pdo->lastInsertId();

$_SESSION['usuario_id']    = $userId;
$_SESSION['usuario_nome']  = $nome;
$_SESSION['usuario_email'] = $email;

$pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?")
    ->execute([$userId]);

// ── E-mail de boas-vindas ──────────────────────────────────── ← NOVO
Mailer::enviarBoasVindas($email, $nome);

responderOk([
    'mensagem' => 'Cadastro realizado com sucesso! Bem-vindo, ' . $nome . '.',
    'usuario'  => [
        'id'    => $userId,
        'nome'  => $nome,
        'email' => $email,
    ],
]);
