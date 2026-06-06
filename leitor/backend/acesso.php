<?php
/* ================================================================
   ROBÉRIO DIÓGENES — leitor/backend/acesso.php
   Verifica se o usuário tem permissão de ler um livro/conto.
   Também serve o arquivo EPUB de forma segura.

   GET ?acao=verificar&slug=lumen
   GET ?acao=servir&slug=lumen        → stream do EPUB
   GET ?acao=minha_biblioteca         → livros acessíveis do usuário
   ================================================================ */

ob_start();
require_once __DIR__ . '/../../backend/config.php';
iniciarSessao();

$acao    = trim($_GET['acao'] ?? '');
$slug    = preg_replace('/[^a-z0-9_-]/', '', trim($_GET['slug'] ?? ''));
$usuario = $_SESSION['usuario'] ?? null;

function jsonR(array $d): void {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}

function verificarAcesso(PDO $pdo, ?array $usuario, string $slug): array {
    if (!$slug) return ['acesso' => false, 'motivo' => 'slug_invalido'];
    $stL = $pdo->prepare("SELECT * FROM livros WHERE slug=? AND ativo=1 LIMIT 1");
    $stL->execute([$slug]);
    $livro = $stL->fetch(PDO::FETCH_ASSOC);
    if (!$livro) return ['acesso' => false, 'motivo' => 'nao_encontrado', 'livro' => null];
    if ($livro['gratuito']) {
        if (!$usuario) return ['acesso' => false, 'motivo' => 'login_necessario', 'livro' => $livro];
        return ['acesso' => true, 'tipo' => 'gratuito', 'expira_em' => null, 'livro' => $livro];
    }
    if (!$usuario) return ['acesso' => false, 'motivo' => 'login_necessario', 'livro' => $livro];
    $uid = (int)$usuario['id'];
    $stC = $pdo->prepare("SELECT id FROM compras WHERE usuario_id=? AND livro_slug=? AND status='aprovada' LIMIT 1");
    $stC->execute([$uid, $slug]);
    if ($stC->fetchColumn()) return ['acesso' => true, 'tipo' => 'compra', 'expira_em' => null, 'livro' => $livro];
    $stA = $pdo->prepare("SELECT expira_em FROM assinaturas WHERE usuario_id=? AND status='ativa' AND expira_em>NOW() ORDER BY expira_em DESC LIMIT 1");
    $stA->execute([$uid]);
    $ass = $stA->fetch(PDO::FETCH_ASSOC);
    if ($ass) return ['acesso' => true, 'tipo' => 'assinatura', 'expira_em' => $ass['expira_em'], 'livro' => $livro];
    // Usuário logado sem acesso → modo amostra (10% grátis)
    return ['acesso' => true, 'tipo' => 'amostra', 'percentual_max' => 10, 'expira_em' => null, 'livro' => $livro];
}

$pdo = db();

if ($acao === 'verificar') {
    $r = verificarAcesso($pdo, $usuario, $slug);
    jsonR(array_merge(['ok' => true], $r));
}

if ($acao === 'servir') {
    $r = verificarAcesso($pdo, $usuario, $slug);
    if (!$r['acesso']) {
        ob_end_clean(); http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'erro' => $r['motivo'] ?? 'Acesso negado.']); exit;
    }
    $arquivo = basename($r['livro']['arquivo_epub'] ?? '');
    if (!$arquivo) { ob_end_clean(); http_response_code(404); echo 'Arquivo não configurado.'; exit; }
    $caminho = realpath(__DIR__ . '/../epub/' . $arquivo);
    $pasta   = realpath(__DIR__ . '/../epub/');
    if (!$caminho || !$pasta || strpos($caminho, $pasta) !== 0 || !file_exists($caminho)) {
        ob_end_clean(); http_response_code(404); echo 'Arquivo não encontrado.'; exit;
    }
    if ($usuario) {
        try {
            $pdo->prepare("INSERT INTO downloads_log (usuario_id,livro_slug,formato,arquivo,ip) VALUES(?,?,'epub',?,?)")
                ->execute([(int)$usuario['id'], $slug, $arquivo, getIP()]);
        } catch (PDOException $e) {}
    }
    ob_end_clean();
    header('Content-Type: application/epub+zip');
    header('Content-Disposition: inline; filename="' . $arquivo . '"');
    header('Content-Length: ' . filesize($caminho));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('X-Content-Type-Options: nosniff');
    readfile($caminho); exit;
}

if ($acao === 'minha_biblioteca') {
    if (!$usuario) jsonR(['ok' => true, 'livros' => []]);
    $uid = (int)$usuario['id'];
    $campos = "slug, titulo, tipo, capa_img, tempo_leitura, total_capitulos";
    $stG = $pdo->query("SELECT $campos, 'gratuito' AS acesso, NULL AS expira_em FROM livros WHERE gratuito=1 AND ativo=1 ORDER BY ordem");
    $gratuitos = $stG->fetchAll(PDO::FETCH_ASSOC);
    $stC = $pdo->prepare("SELECT l.$campos, 'compra' AS acesso, NULL AS expira_em FROM compras c JOIN livros l ON l.slug=c.livro_slug WHERE c.usuario_id=? AND c.status='aprovada' AND l.ativo=1");
    $stC->execute([$uid]); $comprados = $stC->fetchAll(PDO::FETCH_ASSOC);
    $stA = $pdo->prepare("SELECT expira_em FROM assinaturas WHERE usuario_id=? AND status='ativa' AND expira_em>NOW() ORDER BY expira_em DESC LIMIT 1");
    $stA->execute([$uid]); $ass = $stA->fetch(PDO::FETCH_ASSOC);
    $assinatura = [];
    if ($ass) {
        $stL = $pdo->query("SELECT $campos, 'assinatura' AS acesso FROM livros WHERE ativo=1 ORDER BY ordem");
        $todos = $stL->fetchAll(PDO::FETCH_ASSOC);
        foreach ($todos as &$l) { $l['expira_em'] = $ass['expira_em']; }
        $assinatura = $todos;
    }
    $mapa = [];
    foreach (array_merge($gratuitos, $comprados, $assinatura) as $l) { $mapa[$l['slug']] = $l; }
    if ($mapa) {
        $slugs = array_keys($mapa); $ph = implode(',', array_fill(0, count($slugs), '?'));
        $stP = $pdo->prepare("SELECT livro_slug,percentual,cfi,ultima_leitura,concluido FROM leitor_progresso WHERE usuario_id=? AND livro_slug IN ($ph)");
        $stP->execute(array_merge([$uid], $slugs));
        foreach ($stP->fetchAll(PDO::FETCH_ASSOC) as $p) {
            if (isset($mapa[$p['livro_slug']])) {
                $mapa[$p['livro_slug']] = array_merge($mapa[$p['livro_slug']], [
                    'percentual'    => $p['percentual'],
                    'cfi'           => $p['cfi'],
                    'ultima_leitura'=> $p['ultima_leitura'],
                    'concluido'     => $p['concluido'],
                ]);
            }
        }
    }
    jsonR(['ok' => true, 'livros' => array_values($mapa), 'assinatura_expira' => $ass['expira_em'] ?? null]);
}

jsonR(['ok' => false, 'erro' => 'Ação desconhecida.']);
