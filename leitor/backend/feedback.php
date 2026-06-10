<?php
/* leitor/backend/feedback.php
   POST { slug, estrelas, texto, compartilhou }  → salva feedback ao concluir */
ob_start();
require_once __DIR__ . '/../../backend/config.php';
iniciarSessao();

$usuario = getUsuarioSessao();
if (!$usuario) { ob_end_clean(); http_response_code(401); header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok'=>false,'erro'=>'Login necessário.']); exit; }

$pdo   = db();
$uid   = (int)$usuario['id'];
$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$slug  = preg_replace('/[^a-z0-9_-]/', '', trim($body['slug'] ?? ''));
$stars = min(5, max(1, (int)($body['estrelas'] ?? 5)));
$texto = trim($body['texto'] ?? '');
$comp  = !empty($body['compartilhou']) ? 1 : 0;

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!$slug) { echo json_encode(['ok'=>false,'erro'=>'Slug obrigatório.']); exit; }

$pdo->prepare(
    "INSERT INTO leitor_feedback (usuario_id,livro_slug,estrelas,texto,compartilhou)
     VALUES(?,?,?,?,?)
     ON DUPLICATE KEY UPDATE estrelas=VALUES(estrelas), texto=VALUES(texto), compartilhou=VALUES(compartilhou)"
)->execute([$uid,$slug,$stars,$texto,$comp]);

// Salvar avaliação também na tabela avaliacoes (pública)
$pdo->prepare(
    "INSERT INTO avaliacoes (usuario_id,livro_slug,estrelas) VALUES(?,?,?)
     ON DUPLICATE KEY UPDATE estrelas=VALUES(estrelas)"
)->execute([$uid,$slug,$stars]);

echo json_encode(['ok'=>true,'mensagem'=>'Obrigado pelo seu feedback!']);
