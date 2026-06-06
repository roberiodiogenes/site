<?php
/* ================================================================
   leitor/backend/marcacoes.php
   GET  ?acao=listar&slug=lumen
   POST { acao:criar,  slug, cfi_range, trecho, cor }
   POST { acao:excluir, id }
   ================================================================ */
ob_start();
require_once __DIR__ . '/../../backend/config.php';
iniciarSessao();

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario) { ob_end_clean(); http_response_code(401); header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok'=>false,'erro'=>'Login necessário.']); exit; }

$pdo  = db();
$uid  = (int)$usuario['id'];
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$acao = trim($_GET['acao'] ?? $body['acao'] ?? '');
$slug = preg_replace('/[^a-z0-9_-]/', '', trim($_GET['slug'] ?? $body['slug'] ?? ''));

function jM(array $d): void { ob_end_clean(); header('Content-Type: application/json; charset=utf-8'); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

if ($acao === 'listar') {
    $st = $pdo->prepare("SELECT * FROM leitor_marcacoes WHERE usuario_id=? AND livro_slug=? ORDER BY criado_em");
    $st->execute([$uid, $slug]);
    jM(['ok' => true, 'marcacoes' => $st->fetchAll(PDO::FETCH_ASSOC)]);
}
if ($acao === 'criar') {
    $cfiRange = trim($body['cfi_range'] ?? '');
    $trecho   = trim($body['trecho']    ?? '');
    $cor      = in_array($body['cor']??'', ['amarelo','verde','azul','rosa','laranja']) ? $body['cor'] : 'amarelo';
    if (!$cfiRange || !$trecho || !$slug) jM(['ok'=>false,'erro'=>'Dados incompletos.']);
    $pdo->prepare("INSERT INTO leitor_marcacoes (usuario_id,livro_slug,cfi_range,trecho,cor) VALUES(?,?,?,?,?)")
        ->execute([$uid,$slug,$cfiRange,$trecho,$cor]);
    jM(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
}
if ($acao === 'excluir') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) jM(['ok'=>false,'erro'=>'ID obrigatório.']);
    $pdo->prepare("DELETE FROM leitor_marcacoes WHERE id=? AND usuario_id=?")->execute([$id,$uid]);
    jM(['ok' => true]);
}
jM(['ok' => false, 'erro' => 'Ação desconhecida.']);
