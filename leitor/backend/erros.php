<?php
/* leitor/backend/erros.php
   POST { slug, cfi, trecho, descricao } → reportar erro ortográfico */
ob_start();
require_once __DIR__ . '/../../backend/config.php';
iniciarSessao();

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario) { ob_end_clean(); http_response_code(401); header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok'=>false,'erro'=>'Login necessário.']); exit; }

$pdo  = db();
$uid  = (int)$usuario['id'];
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$slug = preg_replace('/[^a-z0-9_-]/', '', trim($body['slug'] ?? ''));
$cfi  = trim($body['cfi'] ?? '');
$trecho    = trim($body['trecho']    ?? '');
$descricao = trim($body['descricao'] ?? '');

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!$slug || !$trecho) { echo json_encode(['ok'=>false,'erro'=>'Dados insuficientes.']); exit; }

// Rate limit: máx 5 erros por hora por usuário
$stR = $pdo->prepare("SELECT COUNT(*) FROM leitor_erros WHERE usuario_id=? AND criado_em > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$stR->execute([$uid]);
if ((int)$stR->fetchColumn() >= 5) { echo json_encode(['ok'=>false,'erro'=>'Limite de reportes atingido. Tente em 1 hora.']); exit; }

$pdo->prepare("INSERT INTO leitor_erros (usuario_id,livro_slug,cfi,trecho,descricao) VALUES(?,?,?,?,?)")
    ->execute([$uid,$slug,$cfi,$trecho,$descricao]);

echo json_encode(['ok' => true, 'mensagem' => 'Erro reportado. Obrigado pela colaboração!']);
