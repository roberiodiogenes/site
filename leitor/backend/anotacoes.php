<?php
/* ================================================================
   leitor/backend/anotacoes.php
   GET  ?acao=listar&slug=lumen
   POST { acao:criar,  slug, cfi, cfi_range, trecho, anotacao, cor }
   POST { acao:editar, id, anotacao }
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

function jA(array $d): void { ob_end_clean(); header('Content-Type: application/json; charset=utf-8'); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

if ($acao === 'listar') {
    $st = $pdo->prepare("SELECT * FROM leitor_anotacoes WHERE usuario_id=? AND livro_slug=? ORDER BY criado_em");
    $st->execute([$uid, $slug]);
    jA(['ok' => true, 'anotacoes' => $st->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($acao === 'criar') {
    $cfi      = trim($body['cfi']       ?? '');
    $cfiRange = trim($body['cfi_range'] ?? '');
    $trecho   = trim($body['trecho']    ?? '');
    $texto    = trim($body['anotacao']  ?? '');
    $cor      = preg_match('/^#[0-9A-Fa-f]{6}$/', $body['cor'] ?? '') ? $body['cor'] : '#FFD700';
    if (!$texto || !$slug) jA(['ok' => false, 'erro' => 'Texto e slug obrigatórios.']);
    $pdo->prepare("INSERT INTO leitor_anotacoes (usuario_id,livro_slug,cfi,cfi_range,trecho,anotacao,cor) VALUES(?,?,?,?,?,?,?)")
        ->execute([$uid,$slug,$cfi,$cfiRange,$trecho,$texto,$cor]);
    jA(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
}

if ($acao === 'editar') {
    $id    = (int)($body['id'] ?? 0);
    $texto = trim($body['anotacao'] ?? '');
    if (!$id || !$texto) jA(['ok' => false, 'erro' => 'ID e texto obrigatórios.']);
    $pdo->prepare("UPDATE leitor_anotacoes SET anotacao=? WHERE id=? AND usuario_id=?")->execute([$texto,$id,$uid]);
    jA(['ok' => true]);
}

if ($acao === 'excluir') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) jA(['ok' => false, 'erro' => 'ID obrigatório.']);
    $pdo->prepare("DELETE FROM leitor_anotacoes WHERE id=? AND usuario_id=?")->execute([$id,$uid]);
    jA(['ok' => true]);
}

jA(['ok' => false, 'erro' => 'Ação desconhecida.']);
