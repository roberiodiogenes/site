<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/tracker.php
   Rastreia cliques de e-mail, confirma endereço e redireciona.

   GET ?token={token}&acao={acao}

   Ações:
     baixar_conto        → confirma e-mail + registra + serve PDF
     visitar_biblioteca  → confirma e-mail + registra + redireciona
   ================================================================ */

require_once __DIR__ . '/config.php';
iniciarSessao();

$token = trim($_GET['token'] ?? '');
$acao  = preg_replace('/[^a-z_]/', '', trim($_GET['acao'] ?? ''));

$base = defined('SITE_URL') ? SITE_URL : 'https://roberiodiogenes.com';

$destinos = [
    'baixar_conto'      => null,                    // servido como PDF diretamente
    'visitar_biblioteca'=> $base . '/livros.html',
    'clicar_pdf'        => $base . '/livros.html',  // clique vindo de dentro de um PDF
];

if (!array_key_exists($acao, $destinos)) {
    header('Location: ' . $base . '/livros.html');
    exit;
}

$pdo       = db();
$usuarioId = null;

/* ── 1. Validar token ───────────────────────────────────────── */
if ($token) {
    try {
        $st = $pdo->prepare(
            "SELECT id, verificado
             FROM usuarios
             WHERE token_confirmacao = ?
               AND (token_expira_em IS NULL OR token_expira_em > NOW())
             LIMIT 1"
        );
        $st->execute([$token]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $usuarioId = (int) $row['id'];

            /* ── 2. Confirmação de conta ─────────────────────
               Só atualiza se ainda "Pendente" (verificado = 0).
               Se já confirmado, mantém o rastreamento mas pula o UPDATE.
               UPDATE dividido em dois: o principal nunca falha por causa
               de coluna opcional (email_confirmado_em). */
            if ((int) $row['verificado'] === 0) {
                // UPDATE crítico — sempre deve funcionar
                $pdo->prepare(
                    "UPDATE usuarios
                     SET verificado        = 1,
                         token_confirmacao = NULL,
                         token_expira_em   = NULL
                     WHERE id = ?"
                )->execute([$usuarioId]);

                // UPDATE opcional — só executa se a coluna existir
                try {
                    $pdo->prepare(
                        "UPDATE usuarios SET email_confirmado_em = NOW() WHERE id = ?"
                    )->execute([$usuarioId]);
                } catch (PDOException $e) {
                    // Coluna ainda não criada no banco — ignorar
                    error_log('[tracker] email_confirmado_em ausente: ' . $e->getMessage());
                }
            }
        }
    } catch (PDOException $e) {
        error_log('[tracker] Erro ao validar token: ' . $e->getMessage());
    }
}

/* Fallback: usa usuário da sessão se o token não resolveu */
if (!$usuarioId) {
    $sessaoUsuario = getUsuarioSessao();
    if ($sessaoUsuario) $usuarioId = (int) $sessaoUsuario['id'];
}

/* ── 3. Registrar métrica de engajamento ────────────────────── */
if ($acao === 'clicar_pdf') {
    /* Clique vindo de dentro de um PDF — registra sempre, mesmo anônimo */
    $origem = preg_replace('/[^a-z0-9_-]/', '', substr($_GET['origem'] ?? 'desconhecida', 0, 80));
    try {
        $pdo->prepare(
            "INSERT INTO pdf_cliques (usuario_id, pdf_nome, ip, user_agent)
             VALUES (?, ?, ?, ?)"
        )->execute([
            $usuarioId ?: null,
            $origem,
            getIP(),
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300),
        ]);
    } catch (PDOException $e) {
        error_log('[tracker] PDF clique: ' . $e->getMessage());
    }
} elseif ($usuarioId) {
    try {
        $pdo->prepare(
            "INSERT INTO email_cliques (usuario_id, acao, ip, user_agent)
             VALUES (?, ?, ?, ?)"
        )->execute([
            $usuarioId,
            $acao,
            getIP(),
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300),
        ]);
    } catch (PDOException $e) {
        error_log('[tracker] Erro ao registrar clique: ' . $e->getMessage());
    }
}

/* ── 4. Redirecionamento inteligente ────────────────────────── */
if ($acao === 'baixar_conto') {
    $raiz    = realpath(__DIR__ . '/../');
    $arquivo = $raiz . '/livros-conteudo/contos/o-colecionador-de-paginas.pdf';

    if ($arquivo && is_file($arquivo)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="O-Colecionador-de-Paginas-Roberio-Diogenes.pdf"');
        header('Content-Length: ' . filesize($arquivo));
        header('Cache-Control: no-store');
        readfile($arquivo);
        exit;
    }

    // PDF não encontrado — página de aviso (sem redirect que possa cair no leitor)
    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Robério Diógenes</title>
    <style>body{font-family:Georgia,serif;background:#0D0A07;color:#E8DCC8;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;text-align:center;padding:2rem}
    .card{max-width:420px}.titulo{color:#B8860B;font-size:1.4rem;margin-bottom:1rem}
    p{color:#C8B898;line-height:1.7;font-size:.95rem}
    a{color:#B8860B;font-size:.85rem}</style></head>
    <body><div class="card">
    <p class="titulo">O conto está chegando</p>
    <p>O arquivo está sendo preparado. Tente novamente em alguns instantes ou acesse a biblioteca pelo site.</p>
    <p><a href="' . $base . '/livros.html">← Ir para a biblioteca</a></p>
    </div></body></html>';
    exit;
}

header('Location: ' . $destinos[$acao]);
exit;
