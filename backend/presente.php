<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/presente.php
   Endpoint público para o presenteado acessar seu voucher.

   GET ?token=TOKEN_UNICO
     → { ok, presente: { titulo, capa_img, livro_slug,
                         nome_presenteado, comprador_nome,
                         dedicatoria, criado_em } }
   ================================================================ */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['ok' => false, 'erro' => 'Método não permitido.']);
    exit;
}

$token = trim($_GET['token'] ?? '');
if (!$token || strlen($token) < 20) {
    echo json_encode(['ok' => false, 'erro' => 'Token inválido.']);
    exit;
}

$pdo = db();
$st  = $pdo->prepare(
    "SELECT p.livro_slug, p.nome_presenteado, p.dedicatoria, p.criado_em,
            l.titulo, l.capa_img,
            u.nome AS comprador_nome
     FROM   presentes p
     JOIN   livros    l ON l.slug = p.livro_slug
     JOIN   usuarios  u ON u.id  = p.comprador_id
     WHERE  p.token_acesso = ?
       AND  p.status = 'aprovado'
     LIMIT 1"
);
$st->execute([$token]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['ok' => false, 'erro' => 'Presente não encontrado ou ainda não confirmado.']);
    exit;
}

echo json_encode(['ok' => true, 'presente' => $row], JSON_UNESCAPED_UNICODE);
