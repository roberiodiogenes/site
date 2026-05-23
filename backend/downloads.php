<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/downloads.php
   Serve arquivos protegidos de download (apenas usuários logados)

   GET ?livro=slug&formato=pdf|epub   → serve o arquivo
   GET ?acao=meus_downloads            → lista downloads do usuário
   ================================================================ */

require_once __DIR__ . '/config.php';
iniciarSessao();

$acao   = $_GET['acao']   ?? '';
$slug   = trim($_GET['livro']   ?? '');
$formato= trim($_GET['formato'] ?? 'pdf');

/* ── Listar meus downloads (para o perfil) ─────────────────── */
if ($acao === 'meus_downloads') {
    if (empty($_SESSION['usuario_id'])) {
        responderErro('Não autenticado.', 401);
    }
    $pdo  = db();
    $stmt = $pdo->prepare("
        SELECT dl.livro_slug, dl.formato, dl.arquivo, dl.baixado_em,
               l.titulo
        FROM downloads_log dl
        LEFT JOIN livros l ON l.slug = dl.livro_slug
        WHERE dl.usuario_id = ?
        ORDER BY dl.baixado_em DESC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $rows = $stmt->fetchAll();

    // Formatar datas e agrupar por livro
    $resultado = [];
    foreach ($rows as $r) {
        $resultado[] = [
            'slug'    => $r['livro_slug'],
            'titulo'  => $r['titulo'] ?? $r['livro_slug'],
            'formato' => $r['formato'],
            'arquivo' => $r['arquivo'],
            'data'    => (new DateTime($r['baixado_em']))->format('d/m/Y H:i'),
        ];
    }
    responderOk(['downloads' => $resultado]);
}

/* ── Servir arquivo ────────────────────────────────────────── */
if (!$slug) responderErro('Livro não informado.');

// Verificar login
if (empty($_SESSION['usuario_id'])) {
    // Redirecionar para login com mensagem
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(401);
    echo json_encode(['ok' => false, 'erro' => 'login_required', 'mensagem' => 'Faça login para baixar este capítulo gratuitamente.']);
    exit;
}

// Validar formato
if (!in_array($formato, ['pdf', 'epub'])) {
    responderErro('Formato inválido. Use pdf ou epub.');
}

// Buscar arquivo no banco
$pdo  = db();
$col  = $formato === 'pdf' ? 'arquivo_pdf' : 'arquivo_epub';
$stmt = $pdo->prepare("SELECT titulo, $col as arquivo FROM livros WHERE slug=? AND ativo=1");
$stmt->execute([$slug]);
$livro = $stmt->fetch();

if (!$livro || !$livro['arquivo']) {
    responderErro('Arquivo não disponível para este livro.', 404);
}

// Construir caminho absoluto
$raiz   = dirname(__DIR__); // pasta raiz do site
$caminho = $raiz . '/download/' . $formato . '/' . $livro['arquivo'];

if (!file_exists($caminho)) {
    responderErro('Arquivo não encontrado no servidor.', 404);
}

// Registrar download no log
$uid = $_SESSION['usuario_id'];
try {
    $pdo->prepare("
        INSERT INTO downloads_log (usuario_id, livro_slug, formato, arquivo, ip)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([$uid, $slug, $formato, $livro['arquivo'], getIP()]);
} catch (Exception $e) {
    // Não bloqueia o download se o log falhar
}

// Rate limiting suave: máx. 10 downloads por hora por usuário
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM downloads_log
    WHERE usuario_id=? AND baixado_em > DATE_SUB(NOW(), INTERVAL 1 HOUR)
");
$stmt->execute([$uid]);
if ((int)$stmt->fetchColumn() > 10) {
    responderErro('Muitos downloads em pouco tempo. Aguarde um momento.', 429);
}

// Servir arquivo com headers corretos
$mimes = ['pdf' => 'application/pdf', 'epub' => 'application/epub+zip'];
$nomeDownload = $livro['titulo'] . ' - Capítulo 1.' . $formato;

header('Content-Type: ' . $mimes[$formato]);
header('Content-Disposition: attachment; filename="' . rawurlencode($nomeDownload) . '"');
header('Content-Length: ' . filesize($caminho));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-cache');

// Desativar output buffering para arquivos grandes
if (ob_get_level()) ob_end_clean();
readfile($caminho);
exit;
