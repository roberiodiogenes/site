<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/blog_api.php
   API REST do blog.
   NOTA: getIP() vem do config.php — não redeclarar aqui.
   ================================================================ */

ob_start();
require_once __DIR__ . '/config.php';

// Suporta ação via GET, POST form-data ou JSON body (Content-Type: application/json)
// PHP 8+ permite reler php://input sem buffer extra.
$_blog_jb = [];
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $ct = strtolower($_SERVER['HTTP_CONTENT_TYPE'] ?? $_SERVER['CONTENT_TYPE'] ?? '');
    if (str_contains($ct, 'application/json')) {
        $_blog_jb = json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
$acao = trim($_GET['acao'] ?? $_POST['acao'] ?? ($_blog_jb['acao'] ?? ''));

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

/* Detecta colunas disponíveis — evita erro se coluna não existir */
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
    $base = ['id','slug','titulo','subtitulo','categoria','resumo',
             'imagem_url','audio_url','tempo_leitura','destaque',
             'livro_slug','publicado_em'];
    if ($comConteudo) $base[] = 'conteudo';
    foreach (['html_externo','updated_at','exclusivo','percentual_livre',
              'enquete_id','cluster_id','newsletter_enviado'] as $col) {
        if (in_array($col, $cols)) $base[] = $col;
    }
    return implode(', ', $base);
}

/* Verifica se usuário tem assinatura ativa (ou tipo admin/assinante) */
function _blog_temAssinatura(PDO $pdo, ?array $usuario): bool {
    if (!$usuario) return false;

    // ── Passo 1: verificar campo tipo na tabela usuarios ─────────────────────
    // Try separado: se a coluna não existir, não interrompe o restante da função.
    try {
        $stTipo = $pdo->prepare("SELECT tipo FROM usuarios WHERE id=? LIMIT 1");
        $stTipo->execute([$usuario['id']]);
        $tipo = (string)($stTipo->fetchColumn() ?? '');
        if (in_array($tipo, ['admin', 'assinante'], true)) return true;
    } catch (Throwable $e) {
        // Coluna tipo não existe ou falha na query — continua para verificar assinaturas
    }

    // ── Passo 2: verificar assinatura ativa na tabela assinaturas ────────────
    try {
        $st = $pdo->prepare(
            "SELECT COUNT(*) FROM assinaturas
             WHERE usuario_id = ? AND status = 'ativa' AND expira_em > NOW()"
        );
        $st->execute([$usuario['id']]);
        return (bool)(int)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

/* Trunca HTML mantendo apenas os primeiros N% dos parágrafos */
function _blog_truncarConteudo(string $html, int $percentual): string {
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $paras  = $dom->getElementsByTagName('p');
    $total  = $paras->length;
    if ($total <= 2) return $html; // post muito curto — não truncar
    $limite = max(1, (int)ceil($total * $percentual / 100));
    $trecho = '';
    for ($i = 0; $i < $limite; $i++) {
        $trecho .= $dom->saveHTML($paras->item($i));
    }
    return $trecho;
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

    /* ── Paywall: posts exclusivos para assinantes ── */
    $exclusivo = (bool)(int)($post['exclusivo'] ?? 0);
    $temAssin  = _blog_temAssinatura($pdo, $usuario);
    $acessoTotal = !$exclusivo || $temAssin;

    if ($exclusivo && !$acessoTotal && !empty($post['conteudo'])) {
        $pct = (int)($post['percentual_livre'] ?? 35);
        $post['conteudo'] = _blog_truncarConteudo($post['conteudo'], $pct);
    }

    /* ── Enquete vinculada ── */
    $enquete = null;
    if (!empty($post['enquete_id'])) {
        try {
            $stE = $pdo->prepare(
                "SELECT e.id, e.titulo, e.descricao, e.multipla,
                        (SELECT COUNT(*) FROM enquetes_respostas WHERE enquete_id=e.id) AS total_votos
                 FROM enquetes e WHERE e.id=? AND e.ativo=1 LIMIT 1"
            );
            $stE->execute([$post['enquete_id']]);
            $enquete = $stE->fetch(PDO::FETCH_ASSOC);
            if ($enquete) {
                $stO = $pdo->prepare(
                    "SELECT o.id, o.texto, o.icone, o.ordem,
                            COUNT(r.id) AS votos
                     FROM enquetes_opcoes o
                     LEFT JOIN enquetes_respostas r ON r.opcao_id=o.id
                     WHERE o.enquete_id=?
                     GROUP BY o.id ORDER BY o.ordem ASC"
                );
                $stO->execute([$post['enquete_id']]);
                $opcoes = $stO->fetchAll(PDO::FETCH_ASSOC);
                foreach ($opcoes as &$o) $o['votos'] = (int)$o['votos'];
                $enquete['opcoes']      = $opcoes;
                $enquete['total_votos'] = (int)$enquete['total_votos'];

                // Verificar se usuário já votou
                if ($usuario) {
                    $stV = $pdo->prepare("SELECT opcao_id FROM enquetes_respostas WHERE enquete_id=? AND usuario_id=? LIMIT 1");
                    $stV->execute([$post['enquete_id'], $usuario['id']]);
                    $votei = $stV->fetchColumn();
                } else {
                    $ipH  = hash('sha256', getIP());
                    $stV  = $pdo->prepare("SELECT opcao_id FROM enquetes_respostas WHERE enquete_id=? AND ip_hash=? LIMIT 1");
                    $stV->execute([$post['enquete_id'], $ipH]);
                    $votei = $stV->fetchColumn();
                }
                $enquete['ja_votei']    = $votei ? (int)$votei : null;
            }
        } catch (Throwable $e) { /* enquetes pode não existir ainda */ }
    }

    _blog_jsonOk([
        'post'           => $post,
        'anterior'       => $anterior,
        'proximo'        => $proximo,
        'curtidas'       => $totalCurtidas,
        'ja_curtiu'      => $jaCurtiu,
        'ja_leu'         => $jaLeu,
        'usuario_logado' => $usuario !== null,
        'exclusivo'      => $exclusivo,
        'acesso_total'   => $acessoTotal,
        'tem_assinatura' => $temAssin,
        'enquete'        => $enquete,
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
    $body = $_blog_jb; // JSON já lido no início do arquivo
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

    $body      = $_blog_jb; // JSON já lido no início do arquivo
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
    } catch (PDOException $e) {
        _blog_jsonOk(['aviso' => 'posts_lidos indisponível']);
    }

    /* ── Rastrear interesse por categoria ──────────────────────
       Só registra se o progresso for >= 30% (leitura real,
       não apenas abertura do post).                           */
    if ($progresso >= 30) {
        try {
            $stCat = $pdo->prepare("SELECT categoria FROM posts WHERE slug = ? LIMIT 1");
            $stCat->execute([$slug]);
            $cat = $stCat->fetchColumn();
            if ($cat) {
                $pdo->prepare(
                    "INSERT INTO usuario_interesses (usuario_id, categoria, contagem)
                     VALUES (?, ?, 1)
                     ON DUPLICATE KEY UPDATE
                       contagem     = contagem + 1,
                       ultima_vista = NOW()"
                )->execute([$usuario['id'], $cat]);
            }
        } catch (PDOException $e) {
            /* Silencioso — migration pode não ter rodado ainda */
        }
    }

    _blog_jsonOk();
}

/* ================================================================
   POST votar_enquete
   ================================================================ */
if ($acao === 'votar_enquete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $body     = $_blog_jb; // JSON já lido no início do arquivo
    $enqId    = (int)($body['enquete_id'] ?? 0);
    $opcaoId  = (int)($body['opcao_id']  ?? 0);
    if (!$enqId || !$opcaoId) _blog_jsonErro('Dados inválidos.');

    $usuario = _blog_usuarioLogado();
    $ipHash  = hash('sha256', getIP());

    try {
        // Verifica se opção pertence à enquete
        $stChk = $pdo->prepare("SELECT id FROM enquetes_opcoes WHERE id=? AND enquete_id=?");
        $stChk->execute([$opcaoId, $enqId]);
        if (!$stChk->fetchColumn()) _blog_jsonErro('Opção inválida.');

        // Verifica se já votou
        if ($usuario) {
            $stV = $pdo->prepare("SELECT id FROM enquetes_respostas WHERE enquete_id=? AND usuario_id=?");
            $stV->execute([$enqId, $usuario['id']]);
        } else {
            $stV = $pdo->prepare("SELECT id FROM enquetes_respostas WHERE enquete_id=? AND ip_hash=?");
            $stV->execute([$enqId, $ipHash]);
        }
        if ($stV->fetchColumn()) _blog_jsonErro('Você já votou nesta enquete.', 409);

        // Registra voto
        $pdo->prepare(
            "INSERT INTO enquetes_respostas (enquete_id, opcao_id, usuario_id, ip_hash)
             VALUES (?,?,?,?)"
        )->execute([$enqId, $opcaoId, $usuario['id'] ?? null, $ipHash]);

        // Retorna resultados atualizados
        $stR = $pdo->prepare(
            "SELECT o.id, o.texto, COUNT(r.id) AS votos
             FROM enquetes_opcoes o
             LEFT JOIN enquetes_respostas r ON r.opcao_id=o.id
             WHERE o.enquete_id=? GROUP BY o.id ORDER BY o.ordem ASC"
        );
        $stR->execute([$enqId]);
        $resultados = $stR->fetchAll(PDO::FETCH_ASSOC);
        foreach ($resultados as &$r) $r['votos'] = (int)$r['votos'];

        $total = array_sum(array_column($resultados, 'votos'));
        _blog_jsonOk(['resultados' => $resultados, 'total' => $total, 'meu_voto' => $opcaoId]);
    } catch (Throwable $e) {
        _blog_jsonErro('Erro ao registrar voto: ' . $e->getMessage(), 500);
    }
}

/* ================================================================
   POST enviar_newsletter — disparo de post para lista (admin only)
   ================================================================ */
if ($acao === 'enviar_newsletter' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar sessão admin — usa o mesmo nome de sessão do painel (rd_admin_sess)
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('rd_admin_sess');
        iniciarSessao();
    }
    if (empty($_SESSION['admin_id'])) _blog_jsonErro('Não autorizado.', 401);

    $body = $_blog_jb; // JSON já lido no início do arquivo
    $slug = trim($body['slug'] ?? '');
    if (!$slug) _blog_jsonErro('Slug obrigatório.');

    try {
        // Buscar post
        $stP = $pdo->prepare("SELECT titulo, resumo, imagem_url, publicado_em FROM posts WHERE slug=? AND status='publicado' LIMIT 1");
        $stP->execute([$slug]);
        $post = $stP->fetch();
        if (!$post) _blog_jsonErro('Post não encontrado ou não publicado.', 404);

        // Verificar se já foi enviado
        $stJ = $pdo->prepare("SELECT newsletter_enviado FROM posts WHERE slug=?");
        $stJ->execute([$slug]);
        if ((int)$stJ->fetchColumn()) _blog_jsonErro('Newsletter já foi enviada para este post.', 409);

        // Buscar assinantes da newsletter
        $stN = $pdo->prepare("SELECT email, nome FROM newsletter WHERE status='ativo' LIMIT 5000");
        $stN->execute();
        $lista = $stN->fetchAll();

        if (empty($lista)) _blog_jsonErro('Nenhum inscrito na newsletter.', 404);

        require_once __DIR__ . '/mailer.php';
        $urlPost  = SITE_URL . '/blog/' . $slug . '.html';
        $titulo   = $post['titulo'];
        $resumo   = $post['resumo'] ?: 'Novo post do Diário de Robério Diógenes.';
        $imgUrl   = $post['imagem_url'] ? SITE_URL . '/' . $post['imagem_url'] : '';

        $enviados = 0; $erros = 0;
        foreach ($lista as $assinante) {
            $primeiroNome = explode(' ', trim($assinante['nome'] ?: 'Leitor'))[0];
            $ok = Mailer::enviar([
                'para_email' => $assinante['email'],
                'para_nome'  => $assinante['nome'] ?: 'Leitor',
                'assunto'    => "Novo no Diário: {$titulo}",
                'html'       => "
                    <p>Olá, <strong>{$primeiroNome}</strong>.</p>
                    " . ($imgUrl ? "<p><img src='{$imgUrl}' alt='{$titulo}' style='width:100%;max-width:520px;border-radius:8px;display:block;margin:0 auto 1rem'></p>" : "") . "
                    <p style='font-family:Georgia,serif;font-size:1.05rem;line-height:1.75'>{$resumo}</p>
                    <p style='text-align:center;margin:2rem 0'>
                      <a href='{$urlPost}' class='btn-email'>Ler o post completo</a>
                    </p>
                    <p style='font-size:.8em;color:#888'>
                      Você recebe este e-mail por estar inscrito no Diário de Robério Diógenes.<br>
                      <a href='" . SITE_URL . "/newsletter/descadastrar?email={$assinante['email']}' style='color:#888'>Descadastrar</a>
                    </p>
                ",
                'texto' => "Olá {$primeiroNome},\n\nNovo no Diário: {$titulo}\n\n{$resumo}\n\nLer: {$urlPost}",
            ]);
            $ok ? $enviados++ : $erros++;
        }

        // Marcar post como disparado
        $pdo->prepare("UPDATE posts SET newsletter_enviado=1 WHERE slug=?")->execute([$slug]);

        // Registrar disparo
        $adminNome = $_SESSION['admin_nome'] ?? 'Admin';
        $pdo->prepare(
            "INSERT INTO newsletter_disparos (post_slug, total_envios, total_erros, disparado_por)
             VALUES (?,?,?,?)"
        )->execute([$slug, $enviados, $erros, $adminNome]);

        _blog_jsonOk([
            'mensagem' => "Newsletter enviada! {$enviados} e-mails, {$erros} erros.",
            'enviados' => $enviados,
            'erros'    => $erros,
        ]);
    } catch (Throwable $e) {
        _blog_jsonErro('Erro ao enviar newsletter: ' . $e->getMessage(), 500);
    }
}

/* ================================================================
   GET clusters — lista todos os clusters ativos com post count
   ================================================================ */
if ($acao === 'clusters' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stC = $pdo->query(
            "SELECT c.id, c.slug, c.titulo, c.descricao, c.imagem_url, c.pilar_slug,
                    COUNT(p.id) AS total_posts
             FROM clusters c
             LEFT JOIN posts p ON p.cluster_id = c.id AND p.status = 'publicado'
             WHERE c.ativo = 1
             GROUP BY c.id
             ORDER BY c.id ASC"
        );
        $clusters = $stC->fetchAll(PDO::FETCH_ASSOC);
        foreach ($clusters as &$cl) $cl['total_posts'] = (int)$cl['total_posts'];
        _blog_jsonOk(['clusters' => $clusters]);
    } catch (Throwable $e) {
        _blog_jsonOk(['clusters' => []]);
    }
}

/* ================================================================
   GET cluster — detalhe de um cluster com seus posts satélites
   ================================================================ */
if ($acao === 'cluster' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $slug = trim($_GET['slug'] ?? '');
    if (!$slug) _blog_jsonErro('Slug obrigatório.', 400);

    try {
        $stC = $pdo->prepare(
            "SELECT id, slug, titulo, descricao, imagem_url, pilar_slug
             FROM clusters WHERE slug = ? AND ativo = 1 LIMIT 1"
        );
        $stC->execute([$slug]);
        $cluster = $stC->fetch(PDO::FETCH_ASSOC);
        if (!$cluster) _blog_jsonErro('Cluster não encontrado.', 404);

        // Post pilar (se existir)
        $pilar = null;
        if (!empty($cluster['pilar_slug'])) {
            $campos = _blog_campos($pdo, false);
            $stP = $pdo->prepare(
                "SELECT $campos FROM posts WHERE slug = ? AND status = 'publicado' LIMIT 1"
            );
            $stP->execute([$cluster['pilar_slug']]);
            $pilar = $stP->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        // Posts satélites do cluster
        $campos = _blog_campos($pdo, false);
        $stS = $pdo->prepare(
            "SELECT $campos FROM posts
             WHERE cluster_id = ? AND status = 'publicado'
             ORDER BY publicado_em DESC"
        );
        $stS->execute([$cluster['id']]);
        $posts = $stS->fetchAll(PDO::FETCH_ASSOC);

        _blog_jsonOk([
            'cluster' => $cluster,
            'pilar'   => $pilar,
            'posts'   => $posts,
        ]);
    } catch (Throwable $e) {
        _blog_jsonErro('Erro: ' . $e->getMessage(), 500);
    }
}

/* ── Rota não encontrada ─────────────────────────────────────── */
_blog_jsonErro('Ação desconhecida: ' . htmlspecialchars($acao), 404);
