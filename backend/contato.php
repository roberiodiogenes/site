<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/contato.php
   POST {nome, email, assunto, mensagem} → salva no banco
   ================================================================ */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderErro('Método não permitido.', 405);

verificarRateLimit('contato', 3, 3600);

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$nome     = trim($body['nome']     ?? '');
$email    = trim(strtolower($body['email']    ?? ''));
$assunto  = trim($body['assunto']  ?? '');
$mensagem = trim($body['mensagem'] ?? '');

if (!$nome || mb_strlen($nome) < 2)             responderErro('Informe seu nome.');
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) responderErro('E-mail inválido.');
if (!$mensagem || mb_strlen($mensagem) < 10)    responderErro('Mensagem muito curta.');
if (mb_strlen($mensagem) > 5000)                responderErro('Mensagem muito longa.');

$pdo = db();
$pdo->prepare("
    INSERT INTO contato (nome, email, assunto, mensagem, ip)
    VALUES (?, ?, ?, ?, ?)
")->execute([$nome, $email, $assunto ?: null, $mensagem, getIP()]);

// Notificação por e-mail (só em produção)
if (AMBIENTE === 'producao') {
    $para     = 'diogenes.escritor@gmail.com';
    $assuntoEmail = '[Site] Nova mensagem de contato: ' . ($assunto ?: 'Sem assunto');
    $corpo    = "Nome: $nome\nE-mail: $email\nAssunto: $assunto\n\n$mensagem";
    $headers  = "From: noreply@roberiodiogenes.com\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    @mail($para, $assuntoEmail, $corpo, $headers);
}

responderOk(['mensagem' => "Mensagem recebida, $nome! Responderei em breve."]);
