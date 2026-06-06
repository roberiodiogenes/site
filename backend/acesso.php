<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/acesso.php
   Verifica acesso de leitura a um livro.
   GET ?livro=slug → { ok, tem_acesso, motivo, livro }
   ================================================================ */

require_once __DIR__ . '/config.php';
iniciarSessao();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderErro('Método não permitido.', 405);
}

$slug = trim($_GET['livro'] ?? '');
if (!$slug) responderErro('Parâmetro livro não informado.');

$pdo = db();

/* ── 1. Busca o livro ──────────────────────────────────────────── */
$stL = $pdo->prepare(
    "SELECT id, titulo,
            COALESCE(total_capitulos,1)   AS total_capitulos,
            COALESCE(gratuito,0)          AS gratuito,
            COALESCE(formato,'html')      AS formato,
            COALESCE(arquivo_epub,'')     AS arquivo_epub,
            COALESCE(pasta_conteudo,'')   AS pasta_conteudo
     FROM livros WHERE slug = ? AND ativo = 1 LIMIT 1"
);
$stL->execute([$slug]);
$livro = $stL->fetch();

if (!$livro) {
    responderErro('Livro não encontrado ou indisponível.', 404);
}

/* Helper: monta o objeto livro para a resposta */
function _dadosLivro(array $livro, string $slug): array {
    return [
        'slug'            => $slug,
        'titulo'          => $livro['titulo'],
        'total_capitulos' => (int) $livro['total_capitulos'],
        'formato'         => $livro['formato'],
        'arquivo_epub'    => $livro['arquivo_epub'],
        'pasta_conteudo'  => $livro['pasta_conteudo'],
    ];
}

/* ── 2. Livro gratuito — qualquer pessoa pode ler (sem login) ─── */
if (!empty($livro['gratuito'])) {
    responderOk([
        'tem_acesso'  => true,
        'tipo_acesso' => 'gratuito',
        'motivo'      => 'gratuito',
        'livro'       => _dadosLivro($livro, $slug),
    ]);
}

/* ── 3. A partir daqui é conteúdo pago — exige login ──────────── */
$uid = (int) ($_SESSION['usuario_id'] ?? 0);
if (!$uid) {
    responderOk([
        'tem_acesso' => false,
        'motivo'     => 'nao_logado',
        'mensagem'   => 'Faça login para acessar este livro.',
        'livro'      => ['slug' => $slug, 'titulo' => $livro['titulo']],
    ]);
}

/* ── 4. Compra avulsa ──────────────────────────────────────────── */
$stC = $pdo->prepare(
    "SELECT comprado_em FROM compras
     WHERE usuario_id = ? AND livro_slug = ? AND status = 'aprovada'
     LIMIT 1"
);
$stC->execute([$uid, $slug]);
$compra = $stC->fetch();

if ($compra) {
    $resp = [
        'tem_acesso'  => true,
        'tipo_acesso' => 'compra',
        'motivo'      => 'compra_aprovada',
        'livro'       => _dadosLivro($livro, $slug),
    ];
    if (!empty($_GET['debug'])) $resp['_debug'] = ['comprado_em' => $compra['comprado_em']];
    responderOk($resp);
}

/* ── 5. Assinatura ativa ───────────────────────────────────────── */
$stA = $pdo->prepare(
    "SELECT a.expira_em, p.nome AS plano_nome
     FROM assinaturas a
     JOIN planos p ON p.id = a.plano_id
     WHERE a.usuario_id = ? AND a.status = 'ativa' AND a.expira_em > NOW()
     ORDER BY a.expira_em DESC LIMIT 1"
);
$stA->execute([$uid]);
$assin = $stA->fetch();

if ($assin) {
    $expira   = new DateTime($assin['expira_em']);
    $diasRest = (int) (new DateTime())->diff($expira)->days;
    responderOk([
        'tem_acesso'  => true,
        'tipo_acesso' => 'assinatura',
        'motivo'      => 'assinatura_ativa',
        'livro'       => _dadosLivro($livro, $slug),
        'assinatura'  => [
            'plano'     => $assin['plano_nome'],
            'expira_em' => $expira->format('d/m/Y'),
            'dias_rest' => $diasRest,
        ],
    ]);
}

/* ── 6. Sem acesso ─────────────────────────────────────────────── */
$stE = $pdo->prepare(
    "SELECT expira_em FROM assinaturas
     WHERE usuario_id = ? AND status IN ('ativa','expirada')
     ORDER BY expira_em DESC LIMIT 1"
);
$stE->execute([$uid]);
$expAnterior = $stE->fetchColumn();

responderOk([
    'tem_acesso' => false,
    'motivo'     => $expAnterior ? 'assinatura_expirada' : 'sem_acesso',
    'mensagem'   => $expAnterior
        ? 'Sua assinatura expirou em ' . (new DateTime($expAnterior))->format('d/m/Y') . '. Renove para continuar lendo.'
        : 'Você precisa comprar este livro ou assinar um plano para lê-lo.',
    'livro'      => ['slug' => $slug, 'titulo' => $livro['titulo']],
]);
