<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/acesso.php
   Verifica se o usuário logado tem direito de ler um livro.

   GET  ?livro=slug          → { ok, tem_acesso, motivo, usuario }
   GET  ?livro=slug&debug=1  → adiciona info de assinatura/compra

   Regras:
     1. Usuário deve estar logado (sessão ativa)
     2. Deve ter compra aprovada do livro  OU
        assinatura ativa e não expirada
   ================================================================ */

require_once __DIR__ . '/config.php';
iniciarSessao();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderErro('Método não permitido.', 405);
}

$slug = trim($_GET['livro'] ?? '');
if (!$slug) {
    responderErro('Parâmetro livro não informado.');
}

/* ── 1. Verificar sessão ─────────────────────────────────────── */
if (empty($_SESSION['usuario_id'])) {
    responderOk([
        'tem_acesso' => false,
        'motivo'     => 'nao_logado',
        'mensagem'   => 'Faça login para acessar este livro.',
    ]);
}

$uid = (int) $_SESSION['usuario_id'];
$pdo = db();

/* ── 2. Verificar se o livro existe e está ativo ─────────────── */
$stmtLivro = $pdo->prepare(
    "SELECT id, titulo, total_capitulos FROM livros WHERE slug = ? AND ativo = 1"
);
$stmtLivro->execute([$slug]);
$livro = $stmtLivro->fetch();

if (!$livro) {
    responderErro('Livro não encontrado ou indisponível.', 404);
}

/* ── 3. Verificar compra avulsa ──────────────────────────────── */
$stmtCompra = $pdo->prepare(
    "SELECT id, comprado_em FROM compras
     WHERE usuario_id = ? AND livro_slug = ? AND status = 'aprovada'
     LIMIT 1"
);
$stmtCompra->execute([$uid, $slug]);
$compra = $stmtCompra->fetch();

if ($compra) {
    $resposta = [
        'tem_acesso'  => true,
        'tipo_acesso' => 'compra',
        'motivo'      => 'compra_aprovada',
        'livro'       => [
            'slug'            => $slug,
            'titulo'          => $livro['titulo'],
            'total_capitulos' => (int) $livro['total_capitulos'],
        ],
    ];
    if (!empty($_GET['debug'])) {
        $resposta['_debug'] = ['comprado_em' => $compra['comprado_em']];
    }
    responderOk($resposta);
}

/* ── 4. Verificar assinatura ativa ───────────────────────────── */
$stmtAssin = $pdo->prepare(
    "SELECT a.id, a.expira_em, p.nome AS plano_nome
     FROM assinaturas a
     JOIN planos p ON p.id = a.plano_id
     WHERE a.usuario_id = ?
       AND a.status = 'ativa'
       AND a.expira_em > NOW()
     ORDER BY a.expira_em DESC
     LIMIT 1"
);
$stmtAssin->execute([$uid]);
$assinatura = $stmtAssin->fetch();

if ($assinatura) {
    $expira    = new DateTime($assinatura['expira_em']);
    $diasRest  = (int) (new DateTime())->diff($expira)->days;

    $resposta = [
        'tem_acesso'  => true,
        'tipo_acesso' => 'assinatura',
        'motivo'      => 'assinatura_ativa',
        'livro'       => [
            'slug'            => $slug,
            'titulo'          => $livro['titulo'],
            'total_capitulos' => (int) $livro['total_capitulos'],
        ],
        'assinatura'  => [
            'plano'     => $assinatura['plano_nome'],
            'expira_em' => $expira->format('d/m/Y'),
            'dias_rest' => $diasRest,
        ],
    ];
    responderOk($resposta);
}

/* ── 5. Sem acesso ───────────────────────────────────────────── */
// Verifica se ao menos tem assinatura expirada (para sugerir renovação)
$stmtExp = $pdo->prepare(
    "SELECT expira_em FROM assinaturas
     WHERE usuario_id = ? AND status IN ('ativa','expirada')
     ORDER BY expira_em DESC LIMIT 1"
);
$stmtExp->execute([$uid]);
$expAnterior = $stmtExp->fetchColumn();

responderOk([
    'tem_acesso' => false,
    'motivo'     => $expAnterior ? 'assinatura_expirada' : 'sem_acesso',
    'mensagem'   => $expAnterior
        ? 'Sua assinatura expirou em ' . (new DateTime($expAnterior))->format('d/m/Y') . '. Renove para continuar lendo.'
        : 'Você precisa comprar este livro ou assinar um plano para lê-lo.',
    'livro' => [
        'slug'   => $slug,
        'titulo' => $livro['titulo'],
    ],
]);
