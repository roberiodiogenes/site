<?php
/* ================================================================
   leitor/backend/notas_autor.php
   GET ?acao=listar&slug=lumen  → todas as notas do autor para o livro
   Apenas admin pode criar/editar notas.
   ================================================================ */
ob_start();
require_once __DIR__ . '/../../backend/config.php';
iniciarSessao();

$pdo  = db();
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$acao = trim($_GET['acao'] ?? $body['acao'] ?? '');
$slug = preg_replace('/[^a-z0-9_-]/', '', trim($_GET['slug'] ?? $body['slug'] ?? ''));

function jN(array $d): void { ob_end_clean(); header('Content-Type: application/json; charset=utf-8'); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

if ($acao === 'listar') {
    $st = $pdo->prepare("SELECT * FROM leitor_notas_autor WHERE livro_slug=? AND ativo=1 ORDER BY id");
    $st->execute([$slug]);
    jN(['ok' => true, 'notas' => $st->fetchAll(PDO::FETCH_ASSOC)]);
}

// Admin: criar/editar/excluir
if (session_status() === PHP_SESSION_ACTIVE) {
    // Verificar sessão admin (nome diferente)
    session_write_close();
    session_name('rd_admin_sess');
    session_start();
    $isAdmin = !empty($_SESSION['admin_id']);
    session_write_close();
} else {
    $isAdmin = false;
}

if (!$isAdmin) { ob_end_clean(); http_response_code(403); header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok'=>false,'erro'=>'Apenas admin.']); exit; }

if ($acao === 'criar') {
    $cfi      = trim($body['cfi']      ?? '');
    $tipo     = in_array($body['tipo']??'', ['bastidor','personagem','cena','curiosidade','outro']) ? $body['tipo'] : 'outro';
    $titulo   = trim($body['titulo']   ?? '');
    $conteudo = trim($body['conteudo'] ?? '');
    if (!$cfi || !$conteudo || !$slug) jN(['ok'=>false,'erro'=>'Dados incompletos.']);
    $pdo->prepare("INSERT INTO leitor_notas_autor (livro_slug,cfi,tipo,titulo,conteudo) VALUES(?,?,?,?,?)")
        ->execute([$slug,$cfi,$tipo,$titulo,$conteudo]);
    jN(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
}
if ($acao === 'excluir') {
    $id = (int)($body['id'] ?? 0);
    $pdo->prepare("DELETE FROM leitor_notas_autor WHERE id=?")->execute([$id]);
    jN(['ok' => true]);
}
jN(['ok' => false, 'erro' => 'Ação desconhecida.']);
