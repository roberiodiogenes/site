<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/compras.php
   Consultas do usuário sobre suas compras.

   GET  ?acao=meus_livros          → slugs de livros comprados/com acesso
   GET  ?acao=historico            → compras e assinaturas detalhadas
   ================================================================ */

require_once __DIR__ . '/config.php';
iniciarSessao();

if (empty($_SESSION['usuario_id'])) {
    responderErro('Não autenticado.', 401);
}

$uid    = (int) $_SESSION['usuario_id'];
$pdo    = db();
$metodo = $_SERVER['REQUEST_METHOD'];
$acao   = trim($_GET['acao'] ?? '');

/* ── GET: slugs de livros com acesso ──────────────────────────── */
if ($metodo === 'GET' && $acao === 'meus_livros') {
    // Compras avulsas aprovadas
    $stC = $pdo->prepare(
        "SELECT livro_slug AS slug FROM compras
         WHERE usuario_id = ? AND status = 'aprovada'"
    );
    $stC->execute([$uid]);
    $comprados = $stC->fetchAll(PDO::FETCH_COLUMN, 0);

    // Assinatura ativa → acesso a todos os livros
    $stA = $pdo->prepare(
        "SELECT id FROM assinaturas
         WHERE usuario_id = ? AND status = 'ativa' AND expira_em > NOW()
         LIMIT 1"
    );
    $stA->execute([$uid]);
    $temAssinatura = (bool) $stA->fetchColumn();

    $slugs = $comprados;
    if ($temAssinatura) {
        // Retornar todos os slugs ativos
        $stL = $pdo->query("SELECT slug FROM livros WHERE ativo = 1");
        $slugs = $stL->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    responderOk([
        'slugs'          => array_values(array_unique($slugs)),
        'tem_assinatura' => $temAssinatura,
    ]);
}

/* ── GET: histórico detalhado ─────────────────────────────────── */
if ($metodo === 'GET' && $acao === 'historico') {
    $stC = $pdo->prepare(
        "SELECT c.id, 'compra' AS tipo,
                l.titulo AS item, l.capa_img,
                c.preco_pago AS valor, c.status,
                c.criado_em AS data
         FROM compras c
         LEFT JOIN livros l ON l.slug = c.livro_slug
         WHERE c.usuario_id = ?
         ORDER BY c.criado_em DESC"
    );
    $stC->execute([$uid]);
    $compras = $stC->fetchAll();

    $stA = $pdo->prepare(
        "SELECT a.id, 'assinatura' AS tipo,
                p.nome AS item, NULL AS capa_img,
                p.preco AS valor, a.status,
                a.inicio_em AS data,
                a.expira_em
         FROM assinaturas a
         JOIN planos p ON p.id = a.plano_id
         WHERE a.usuario_id = ?
         ORDER BY a.inicio_em DESC"
    );
    $stA->execute([$uid]);
    $assinaturas = $stA->fetchAll();

    responderOk([
        'compras'     => $compras,
        'assinaturas' => $assinaturas,
    ]);
}

responderErro('Ação inválida ou método não permitido.');
