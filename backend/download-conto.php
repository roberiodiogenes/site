<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/download-conto.php
   Serve EPUBs gratuitos dos contos da bio.html (sem autenticação).

   GET ?slug=o-labirinto-dos-espelhos
   ================================================================ */

require_once __DIR__ . '/config.php';

$slug = trim($_GET['slug'] ?? '');

/* Whitelist: apenas os contos da bio.html são servidos aqui */
$CONTOS = [
    'o-labirinto-dos-espelhos' => 'O Labirinto dos Espelhos',
    'a-penultima-pagina'       => 'A Penúltima Página',
    'o-quarto-passageiro'      => 'O Quarto Passageiro',
    'o-peso-do-horizonte'      => 'O Peso do Horizonte',
];

if (!$slug || !array_key_exists($slug, $CONTOS)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Conto não encontrado.';
    exit;
}

$titulo  = $CONTOS[$slug];
$caminho = dirname(__DIR__) . '/livros-conteudo/contos/' . $slug . '.epub';

if (!file_exists($caminho)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Arquivo não disponível no momento.';
    exit;
}

$ip = getIP();

/* ── Rate limiting: máx. 20 downloads por IP por hora ────────── */
try {
    $pdo  = db();
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM downloads_log
         WHERE ip = ? AND formato = 'epub' AND baixado_em > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
    );
    $stmt->execute([$ip]);
    if ((int)$stmt->fetchColumn() > 20) {
        http_response_code(429);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Muitos downloads em pouco tempo. Tente novamente em alguns minutos.';
        exit;
    }
} catch (Throwable $e) {
    /* Ignora erro de rate limiting — não bloqueia o download */
}

/* ── Registrar métrica ────────────────────────────────────────── */
try {
    $pdo = db();
    $pdo->prepare(
        "INSERT INTO downloads_log (usuario_id, livro_slug, formato, arquivo, ip)
         VALUES (NULL, ?, 'epub', ?, ?)"
    )->execute([$slug, $slug . '.epub', $ip]);
} catch (Throwable $e) {
    /* Coluna usuario_id pode ser NOT NULL — tenta alternativa */
    try {
        $pdo->prepare(
            "INSERT INTO downloads_log (usuario_id, livro_slug, formato, arquivo, ip)
             VALUES (0, ?, 'epub', ?, ?)"
        )->execute([$slug, $slug . '.epub', $ip]);
    } catch (Throwable $e2) {
        error_log('[download-conto] Log de download falhou: ' . $e2->getMessage());
        /* Continua o download mesmo sem conseguir logar */
    }
}

/* ── Servir o arquivo ─────────────────────────────────────────── */
$nomeDownload = $titulo . '.epub';

header('Content-Type: application/epub+zip');
header('Content-Disposition: attachment; filename="' . rawurlencode($nomeDownload) . '"');
header('Content-Length: ' . filesize($caminho));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-cache');

if (ob_get_level()) ob_end_clean();
readfile($caminho);
exit;
