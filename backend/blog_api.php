<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/blog_api.php
   API REST do blog.
   NOTA: getIP() vem do config.php — não redeclarar aqui.
   ================================================================ */

ob_start();
require_once __DIR__ . '/config.php';

$acao = trim($_GET['acao'] ?? $_POST['acao'] ?? '');

try {
    $pdo = db();
} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'erro' => 'Banco indisponível.']);
    exit;
}

/* ── Helpers locais (sem conflito com config.php) ────────────── */
function _blog_usuarioLogado(): ?array {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        iniciarSessao(); // função do config.php
    }
    return $_SESSION['usuario'] ?? null;
}

function _blog_jsonOk(array $d = []): void {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => true], $d), JSON_UNESCAPED_UNICODE);
    exit;
}

function _blog_jsonErro(string $msg, int $status = 400): void {
    ob_end_clean();
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'erro' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

/* Detecta colunas disponíveis — evita erro se html_externo não existir */
function _blog_campos(PDO $pdo, bool $comConteudo = false): string {
    static $cols = null;
    if ($cols === null) {
        try {
            $st   = $pdo->query("SHOW COLUMNS FROM posts");
            $cols = array_column($st->fetchAll(PDO::FETCH_ASSOC), 'Field');
        } catch (Exception $e) {
            $cols = [];
        }
    }
    $base   = ['id','slug','titulo','subtitulo','categoria','resumo',
               'imagem_url','audio_url','tempo_leitura','destaque',
               'livro_slug','publicado_em'];
    if ($comConteudo)              $base[] = 'conteudo';
    if (in_array('html_externo', $cols)) $base[] = 'html_externo';
    if (in_array('updated_at',   $cols)) $base[] = 'updated_at';
    return implode(', ', $base);
}

/* ================================================================
   GET listar
   ================================================================ */
if ($acao === 'listar' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $pagina = max(1, (int)($_GET['pagina']    ?? 1));
    $porPag = min(50, max(1, (int)($_GET['por_pagina'] ?? 9)));
    $cat    = trim($_GET['cat']   ?? 'todos');
    $busca  = trim($_GET['busca'] ?? '');
    $offset = ($pagina - 1) * $porPag;

    $where  = ["status = 'publicado'"];
    $params = [];

    if ($cat && $cat !== 'todos') {
        if (in_array($cat, ['bastidores','reflexao','escritor','livros'], true)) {
            $where[]  = "categoria = ?";
            $params[] = $cat;
        }
    }
    if ($busca) {
        $where[]  = "(titulo LIKE ? OR resumo LIKE ? OR subtitulo LIKE ?)";
        $like = "%$busca%";
        $params[] = $like; $params[] = $like; $params[] = $like;
    }
    $wsql = 'WHERE ' . implode(' AND ', $where);

    try {
        $stCnt = $pdo->prepare("SELECT COUNT(*) FROM posts $wsql");
        $stCnt->execute($params);
        $total     = (int)$stCnt->fetchColumn();
        $totalPags = max(1, (int)ceil($total / $porPag));

        $campos = _blog_campos($pdo);
        $stL = $pdo->prepare(
            "SELECT $campos FROM posts $wsql
             ORDER BY destaque DESC, publicado_em DESC
             LIMIT $porPag OFFSET $offset"
        );
        $stL->execute($params);
        $posts = $stL->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        _blog_jsonErro('Erro ao listar posts: ' . $e->getMessage(), 500);
    }

    /* Posts lidos pelo usuário logado */
    $lidos   = [];
    $usuario = _blog_usuarioLogado();
    if ($usuario && !empty($posts)) {
        $slugs = array_column($posts, 'slug');
        $ph    = implode(',', array_fill(0, count($slugs), '?'));
        try {
            $stR = $pdo->prepare(
                "SELECT post_slug FROM posts_lidos
                 WHERE usuario_id = ? AND post_slug IN ($ph)"
            );
            $stR->execute(array_merge([$usuario['id']], $slugs));
            $lidos = $stR->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) { /* tabela pode não existir ainda */ }
    }

    /* Posts agendados nos próximos 7 dias (preview de expectativa) */
    $agendados = [];
    try {
        $camposAg = 'id, slug, titulo, subtitulo, categoria, resumo, imagem_url, publicado_em';
        $stAg = $pdo->prepare(
            "SELECT $camposAg FROM posts
             WHERE status = 'agendado'
               AND publicado_em > NOW()
               AND publicado_em <= DATE_ADD(NOW(), INTERVAL 7 DAY)
             ORDER BY publicado_em ASC
             LIMIT 5"
        );
        $stAg->execute();
        $agendados = $stAg->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { /* status agendado pode não existir ainda */ }

    /* Publicar automaticamente posts agendados com data no passado */
    try {
        $pdo->prepare(
            "UPDATE posts SET status = 'publicado'
             WHERE status = 'agendado' AND publicado_em <= NOW()"
        )->execute();
    } catch (PDOException $e) {}

    _blog_jsonOk([
        'posts'      => $posts,
        'agendados'  => $agendados,
        'lidos'      => $lidos,
        'pagina'     => $pagina,
        'total_pags' => $totalPags,
        'total'      => $total,
        'por_pagina' => $porPag,
    ]);
}

/* ================================================================
   GET post — único por slug
   ================================================================ */
if ($acao === 'post' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $slug = trim($_GET['slug'] ?? '');
    if (!$slug) _blog_jsonErro('Slug obrigatório.', 400);

    try {
        $campos = _blog_campos($pdo, true);
        $st = $pdo->prepare(
            "SELECT $campos FROM posts
             WHERE slug = ? AND status = 'publicado' LIMIT 1"
        );
        $st->execute([$slug]);
        $post = $st->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        _blog_jsonErro('Erro: ' . $e->getMessage(), 500);
    }

    if (!$post) _blog_jsonErro('Post não encontrado.', 404);

    /* Anterior e próximo */
    $anterior = null; $proximo = null;
    try {
        $stP = $pdo->prepare(
            "SELECT slug, titulo FROM posts
             WHERE status='publicado' AND publicado_em < ?
             ORDER BY publicado_em DESC LIMIT 1"
        );
        $stP->execute([$post['publicado_em']]);
        $anterior = $stP->fetch(PDO::FETCH_ASSOC) ?: null;

        $stN = $pdo->prepare(
            "SELECT slug, titulo FROM posts
             WHERE status='publicado' AND publicado_em > ?
             ORDER BY publicado_em ASC LIMIT 1"
        );
        $stN->execute([$post['publicado_em']]);
        $proximo = $stN->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {}

    /* Curtidas e status do usuário */
    $totalCurtidas = 0; $jaCurtiu = false; $jaLeu = false;
    $usuario = _blog_usuarioLogado();
    try {
        $stC = $pdo->prepare("SELECT COUNT(*) FROM posts_curtidas WHERE post_slug = ?");
        $stC->execute([$slug]);
        $totalCurtidas = (int)$stC->fetchColumn();

        if ($usuario) {
            $stCU = $pdo->prepare(
                "SELECT id FROM posts_curtidas WHERE post_slug = ? AND usuario_id = ?"
            );
            $stCU->execute([$slug, $usuario['id']]);
            $jaCurtiu = (bool)$stCU->fetchColumn();

            $stLU = $pdo->prepare(
                "SELECT id FROM posts_lidos WHERE post_slug = ? AND usuario_id = ?"
            );
            $stLU->execute([$slug, $usuario['id']]);
            $jaLeu = (bool)$stLU->fetchColumn();
        }
    } catch (PDOException $e) {}

    _blog_jsonOk([
        'post'           => $post,
        'anterior'       => $anterior,
        'proximo'        => $proximo,
        'curtidas'       => $totalCurtidas,
        'ja_curtiu'      => $jaCurtiu,
        'ja_leu'         => $jaLeu,
        'usuario_logado' => $usuario !== null,
    ]);
}

/* ================================================================
   GET lidos
   ================================================================ */
if ($acao === 'lidos' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $usuario = _blog_usuarioLogado();
    if (!$usuario) _blog_jsonOk(['lidos' => []]);
    try {
        $st = $pdo->prepare(
            "SELECT post_slug, progresso FROM posts_lidos WHERE usuario_id = ?"
        );
        $st->execute([$usuario['id']]);
        _blog_jsonOk(['lidos' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (PDOException $e) {
        _blog_jsonOk(['lidos' => []]);
    }
}

/* ================================================================
   POST curtir
   ================================================================ */
if ($acao === 'curtir' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $slug = trim($body['slug'] ?? $_POST['slug'] ?? '');
    if (!$slug) _blog_jsonErro('Slug obrigatório.', 400);

    try {
        $stChk = $pdo->prepare(
            "SELECT id FROM posts WHERE slug = ? AND status = 'publicado' LIMIT 1"
        );
        $stChk->execute([$slug]);
        if (!$stChk->fetchColumn()) _blog_jsonErro('Post não encontrado.', 404);

        $usuario = _blog_usuarioLogado();
        $ip      = getIP(); // função do config.php
        $curtiu  = false;

        if ($usuario) {
            $stC = $pdo->prepare(
                "SELECT id FROM posts_curtidas WHERE post_slug = ? AND usuario_id = ?"
            );
            $stC->execute([$slug, $usuario['id']]);
            if ($stC->fetchColumn()) {
                $pdo->prepare(
                    "DELETE FROM posts_curtidas WHERE post_slug = ? AND usuario_id = ?"
                )->execute([$slug, $usuario['id']]);
            } else {
                $pdo->prepare(
                    "INSERT INTO posts_curtidas (post_slug, usuario_id, ip) VALUES (?,?,?)"
                )->execute([$slug, $usuario['id'], $ip]);
                $curtiu = true;
            }
        } else {
            $stC = $pdo->prepare(
                "SELECT id FROM posts_curtidas
                 WHERE post_slug = ? AND ip = ? AND usuario_id IS NULL"
            );
            $stC->execute([$slug, $ip]);
            if ($stC->fetchColumn()) {
                $pdo->prepare(
                    "DELETE FROM posts_curtidas
                     WHERE post_slug = ? AND ip = ? AND usuario_id IS NULL"
                )->execute([$slug, $ip]);
            } else {
                $pdo->prepare(
                    "INSERT INTO posts_curtidas (post_slug, usuario_id, ip) VALUES (?,NULL,?)"
                )->execute([$slug, $ip]);
                $curtiu = true;
            }
        }

        $stT = $pdo->prepare("SELECT COUNT(*) FROM posts_curtidas WHERE post_slug = ?");
        $stT->execute([$slug]);
        _blog_jsonOk(['curtiu' => $curtiu, 'total' => (int)$stT->fetchColumn()]);

    } catch (PDOException $e) {
        _blog_jsonErro('Erro ao curtir.', 500);
    }
}

/* ================================================================
   POST marcar_lido
   ================================================================ */
if ($acao === 'marcar_lido' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = _blog_usuarioLogado();
    if (!$usuario) _blog_jsonErro('Login necessário.', 401);

    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $slug      = trim($body['slug'] ?? $_POST['slug'] ?? '');
    $progresso = min(100, max(0, (int)($body['progresso'] ?? 100)));
    if (!$slug) _blog_jsonErro('Slug obrigatório.', 400);

    try {
        $pdo->prepare(
            "INSERT INTO posts_lidos (usuario_id, post_slug, progresso)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
               progresso = GREATEST(progresso, VALUES(progresso)),
               lido_em   = IF(VALUES(progresso) >= 70, NOW(), lido_em)"
        )->execute([$usuario['id'], $slug, $progresso]);
        _blog_jsonOk();
    } catch (PDOException $e) {
        _blog_jsonOk(['aviso' => 'posts_lidos indisponível']);
    }
}

/* ── Rota não encontrada ─────────────────────────────────────── */
_blog_jsonErro('Ação desconhecida: ' . htmlspecialchars($acao), 404);
