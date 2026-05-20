<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/auth/register.php
   Endpoint: POST /backend/auth/register.php
   Cadastra novo usuário com e-mail e senha
   ================================================================ */

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderErro('Método não permitido.', 405);
}

verificarRateLimit('register', 5, 3600);
iniciarSessao();

// ── Receber dados ─────────────────────────────────────────────
$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$nome  = trim($body['nome']  ?? '');
$email = trim(strtolower($body['email'] ?? ''));
$senha = $body['senha'] ?? '';
$conf  = $body['confirmar_senha'] ?? '';

// ── Validações ────────────────────────────────────────────────
if (!$nome || mb_strlen($nome) < 2) {
    responderErro('Nome inválido. Mínimo de 2 caracteres.');
}
if (mb_strlen($nome) > 120) {
    responderErro('Nome muito longo.');
}
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responderErro('E-mail inválido.');
}
if (strlen($senha) < 8) {
    responderErro('A senha deve ter no mínimo 8 caracteres.');
}
if ($senha !== $conf) {
    responderErro('As senhas não conferem.');
}
// Força mínima: ao menos 1 letra e 1 número
if (!preg_match('/[A-Za-z]/', $senha) || !preg_match('/[0-9]/', $senha)) {
    responderErro('A senha deve conter letras e números.');
}

// ── Verificar e-mail duplicado ────────────────────────────────
$pdo  = db();
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    responderErro('Este e-mail já está cadastrado. Tente fazer login.');
}

// ── Criar usuário ─────────────────────────────────────────────
$hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
$ip   = getIP();

$pdo->prepare("
    INSERT INTO usuarios (nome, email, senha, ip_cadastro)
    VALUES (?, ?, ?, ?)
")->execute([$nome, $email, $hash, $ip]);

$userId = (int) $pdo->lastInsertId();

// ── Iniciar sessão ────────────────────────────────────────────
$_SESSION['usuario_id']   = $userId;
$_SESSION['usuario_nome'] = $nome;
$_SESSION['usuario_email']= $email;

// Atualizar último login
$pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?")
    ->execute([$userId]);

responderOk([
    'mensagem' => 'Cadastro realizado com sucesso! Bem-vindo, ' . $nome . '.',
    'usuario'  => [
        'id'    => $userId,
        'nome'  => $nome,
        'email' => $email,
    ],
]);
