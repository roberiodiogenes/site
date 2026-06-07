<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/comentarios.php
   Sistema completo de comentários com recursos sociais.

   GET  ?acao=listar&slug=xxx        → lista comentários (com respostas aninhadas)
   POST { acao:'criar', slug, texto, parent_id? }  → cria comentário/resposta
   POST { acao:'curtir', id }                      → toggle curtida
   POST { acao:'deletar', id }                     → remove (dono ou admin)
   ================================================================ */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';
iniciarSessao();

$metodo = $_SERVER['REQUEST_METHOD'];

/* ══════════════════════════════════════════════════════════════
   DETECTOR DE SPAM, LINKS E CONTEÚDO REPETITIVO
   ══════════════════════════════════════════════════════════════ */
function detectarSpamOuLink(string $texto): array {
    $motivos = [];

    // Links e URLs (http, www, domínios comuns)
    if (preg_match('/(https?:\/\/|www\.|bit\.ly|t\.co|\.com\b|\.net\b|\.org\b|\.br\b|\.info\b)/i', $texto)) {
        $motivos[] = 'link externo';
    }

    // Texto inteiramente em caixa alta (mais de 4 palavras)
    $palavras = preg_split('/\s+/', trim($texto), -1, PREG_SPLIT_NO_EMPTY);
    if (count($palavras) >= 5) {
        $semPontuacao = preg_replace('/[^a-zA-ZÀ-ú]/u', '', $texto);
        if ($semPontuacao === mb_strtoupper($semPontuacao, 'UTF-8') && mb_strlen($semPontuacao) > 10) {
            $motivos[] = 'texto em caixa alta';
        }
    }

    // Caracteres repetidos excessivamente (aaaaaa, !!!!!, ?????  etc.)
    if (preg_match('/(.)\1{5,}/u', $texto)) {
        $motivos[] = 'caracteres repetidos excessivamente';
    }

    // Palavras repetidas (padrão de spam: "compre compre compre ganhe ganhe")
    if (count($palavras) >= 6) {
        $contagem = array_count_values(array_map('mb_strtolower', $palavras));
        $maxRep   = max($contagem);
        if ($maxRep >= 4 || ($maxRep / count($palavras)) > 0.45) {
            $motivos[] = 'conteúdo repetitivo';
        }
    }

    return [
        'flagged'  => !empty($motivos),
        'motivo'   => implode(', ', $motivos),
        'palavras' => $motivos,
    ];
}

/* ══════════════════════════════════════════════════════════════
   FILTRO DE CONTEÚDO NOCIVO
   Detecta ódio, racismo, conteúdo sexual explícito, violência.
   Retorna: ['flagged'=>bool, 'motivo'=>string, 'palavras'=>array]
   ══════════════════════════════════════════════════════════════ */
function filtrarConteudo(string $texto): array {
    // Normaliza: minúsculas, remove acentos, reduz espaços
    $normalizado = mb_strtolower($texto, 'UTF-8');
    $normalizado = strtr($normalizado, [
        'á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a',
        'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
        'ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
        'ç'=>'c','ñ'=>'n',
    ]);
    // Normaliza variações com números/símbolos comuns (l33tspeak)
    $normalizado = strtr($normalizado, [
        '0'=>'o','1'=>'i','3'=>'e','4'=>'a','5'=>'s','@'=>'a','$'=>'s',
    ]);
    // Remove caracteres não-alfanuméricos entre letras (pu*ta → puta)
    $semSeparadores = preg_replace('/[^a-z0-9\s]/', '', $normalizado);

    $categorias = [
        'racismo_odio' => [
            'patterns' => [
                '/\b(macaco|macacos|macaca|neguinha?|negao|nega\b|preto\s+sujo|preto\s+imundo|judeu\s+(de\s+merda|sujo|nojento)|vagabundo\s+negro|negro\s+vagabundo)\b/',
                '/\b(viado|viadao|viadinho|bicha|bichinha|veado\s+nojento)\b/',
                '/\b(terreiro\s+(e|eh|é|eh\s+o|do)\s+(diabo|satanas|demonio|lixo)|candomble\s+e\s+(coisa|lixo|errado|do\s+diabo)|macumba\s+(e|eh|lixo|nojento)|umbanda\s+e\s+(falsa|lixo|errada))\b/',
            ],
            'label' => 'ódio racial/discriminação',
        ],
        'sexual_explicito' => [
            'patterns' => [
                '/\b(porn[oa]|porno|sexo\s+explicito|cuzao|buceta|vagina\s+suja|pau\s+(enorme|grosso)|boquete|chupeta\s+sexual|putaria|safadeza|tarado|putona)\b/',
                '/\b(fode|fodeu|foda.se|vai\s+se\s+foder|xoxota|rola\s+grande|tesao|gozar|gozando|masturbacao)\b/',
            ],
            'label' => 'conteúdo sexual explícito',
        ],
        'violencia_ameaca' => [
            'patterns' => [
                '/\b(vou\s+te\s+(matar|bater|espancar|destruir)|te\s+(mato|pego|destruo)|morte\s+(para|ao?)\s+\w+|morra\s+(sua?|voce)|vai\s+morrer)\b/',
                '/\b(bomba|terroris[mt]a?|explosao|atentado|genocidio|massacre)\b/',
            ],
            'label' => 'violência/ameaça',
        ],
        'palavroes_agressivos' => [
            'patterns' => [
                '/\b(sua?\s+(mae|pai)\s+(e|eh)\s+(puta|vaca|cadela|vagabunda)|filho\s+da\s+puta|filha\s+da\s+puta|filhodaputa|fdp\b|vsf\b|vtnc\b)\b/',
                '/\b(merda|porra|caralho|cu\b|otario|idiota|imbecil|retardado|cretino|escroto)\b/',
            ],
            'label' => 'linguagem agressiva/ofensiva',
        ],
    ];

    $detectados  = [];
    $palavrasLog = [];

    foreach ($categorias as $cat => $config) {
        foreach ($config['patterns'] as $pattern) {
            if (preg_match($pattern, $semSeparadores, $matches)) {
                if (!in_array($config['label'], $detectados)) {
                    $detectados[] = $config['label'];
                }
                $palavrasLog[] = trim($matches[0]);
            }
        }
    }

    if (empty($detectados)) {
        return ['flagged' => false, 'motivo' => '', 'palavras' => []];
    }

    return [
        'flagged'  => true,
        'motivo'   => implode(', ', $detectados),
        'palavras' => array_unique($palavrasLog),
    ];
}

/* ══════════════════════════════════════════════════════════════
   GET — listar comentários de um post (estrutura aninhada)
   Retrocompatível: funciona mesmo antes de rodar migration_v2
   ══════════════════════════════════════════════════════════════ */
if ($metodo === 'GET') {
    $slug = trim($_GET['slug'] ?? '');
    if (!$slug) responderErro('Slug não informado.');

    $pdo = db();
    $uid = (int)($_SESSION['usuario_id'] ?? 0);

    // Detecta se as novas colunas já existem no banco
    $temNovasColunas = false;
    try {
        $pdo->query("SELECT parent_id, curtidas_count FROM comentarios LIMIT 0");
        $temNovasColunas = true;
    } catch (Throwable $e) {
        $temNovasColunas = false;
    }

    if ($temNovasColunas) {
        // ── Query completa (após migration_comentarios_v2) ──
        $stmt = $pdo->prepare(
            "SELECT c.id, c.parent_id, c.texto, c.curtidas_count, c.criado_em,
                    u.nome, u.foto_url,
                    IF(? > 0,
                       (SELECT COUNT(*) FROM comentario_curtidas cc
                        WHERE cc.comentario_id = c.id AND cc.usuario_id = ?), 0) AS eu_curti
             FROM comentarios c
             JOIN usuarios u ON u.id = c.usuario_id
             WHERE c.referencia = ? AND c.tipo = 'blog' AND c.aprovado = 1
             ORDER BY c.criado_em ASC
             LIMIT 500"
        );
        $stmt->execute([$uid, $uid, $slug]);
        $todos = $stmt->fetchAll();

        // Monta árvore pai → filhos
        $raiz   = [];
        $filhos = [];
        foreach ($todos as &$r) {
            $r['id']            = (int)$r['id'];
            $r['parent_id']     = $r['parent_id'] ? (int)$r['parent_id'] : null;
            $r['curtidas_count'] = (int)$r['curtidas_count'];
            $r['eu_curti']      = (bool)(int)$r['eu_curti'];
            $r['foto_url']      = $r['foto_url'] ?: null;
            $r['respostas']     = [];
            if ($r['parent_id']) {
                $filhos[$r['parent_id']][] = &$r;
            } else {
                $raiz[] = &$r;
            }
        }
        unset($r);
        foreach ($raiz as &$pai) {
            if (isset($filhos[$pai['id']])) {
                $pai['respostas'] = $filhos[$pai['id']];
            }
        }
        unset($pai);
    } else {
        // ── Query de fallback (schema antigo, sem parent_id) ──
        $stmt = $pdo->prepare(
            "SELECT c.id, c.texto, c.criado_em,
                    u.nome, u.foto_url
             FROM comentarios c
             JOIN usuarios u ON u.id = c.usuario_id
             WHERE c.referencia = ? AND c.tipo = 'blog' AND c.aprovado = 1
             ORDER BY c.criado_em ASC
             LIMIT 200"
        );
        $stmt->execute([$slug]);
        $todos = $stmt->fetchAll();
        $raiz  = [];
        foreach ($todos as &$r) {
            $r['id']            = (int)$r['id'];
            $r['parent_id']     = null;
            $r['curtidas_count'] = 0;
            $r['eu_curti']      = false;
            $r['foto_url']      = $r['foto_url'] ?: null;
            $r['respostas']     = [];
            $raiz[]             = $r;
        }
    }

    responderOk(['comentarios' => $raiz]);
}

/* ══════════════════════════════════════════════════════════════
   POST
   ══════════════════════════════════════════════════════════════ */
if ($metodo === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $acao = trim($body['acao'] ?? '');

    /* ─────────────────────────────────────────────────────────
       CRIAR comentário ou resposta
       ───────────────────────────────────────────────────────── */
    if ($acao === 'criar') {
        if (empty($_SESSION['usuario_id'])) {
            responderErro('Faça login para comentar.', 401);
        }
        $uid      = (int) $_SESSION['usuario_id'];
        $slug     = trim($body['slug']      ?? '');
        $texto    = trim($body['texto']     ?? '');
        $parentId = isset($body['parent_id']) ? (int)$body['parent_id'] : null;

        if (!$slug)  responderErro('Post não identificado.');
        if (!$texto) responderErro('Comentário não pode ser vazio.');
        if (mb_strlen($texto) < 5)    responderErro('Comentário muito curto (mín. 5 caracteres).');
        if (mb_strlen($texto) > 2000) responderErro('Comentário muito longo (máx. 2000 caracteres).');

        // Rate limiting: máx 5 comentários/respostas por hora
        verificarRateLimit('comentario_' . $uid, 5, 3600);

        $pdo = db();

        // Validar parent_id se fornecido
        if ($parentId) {
            $stPai = $pdo->prepare("SELECT id, usuario_id FROM comentarios WHERE id=? AND aprovado=1 LIMIT 1");
            $stPai->execute([$parentId]);
            $pai = $stPai->fetch();
            if (!$pai) {
                $parentId = null; // Comentário pai não encontrado, trata como raiz
            }
        }

        // Anti-spam: mesmo usuário, mesmo post, últimos 30s
        $recente = $pdo->prepare(
            "SELECT id FROM comentarios
             WHERE usuario_id = ? AND referencia = ? AND criado_em > DATE_SUB(NOW(), INTERVAL 30 SECOND)
             LIMIT 1"
        );
        $recente->execute([$uid, $slug]);
        if ($recente->fetch()) {
            responderErro('Aguarde alguns segundos antes de comentar novamente.');
        }

        // ── Filtros de conteúdo e spam ──
        $filtroConteudo = filtrarConteudo($texto);
        $filtroSpam     = detectarSpamOuLink($texto);

        // Mescla ambos os filtros
        $flagged = $filtroConteudo['flagged'] || $filtroSpam['flagged'];
        $motivos = array_filter([$filtroConteudo['motivo'], $filtroSpam['motivo']]);
        $filtro  = [
            'flagged'  => $flagged,
            'motivo'   => implode('; ', $motivos),
            'palavras' => array_merge($filtroConteudo['palavras'], $filtroSpam['palavras']),
        ];

        $ipBruto = getIP();
        $ipHash  = hash('sha256', $ipBruto); // LGPD: nunca IP em texto cru
        $ua      = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

        // Conteúdo suspeito ou spam → aguarda moderação (aprovado=0)
        // Comentário normal → publicado imediatamente (aprovado=1)
        $aprovado = $filtro['flagged'] ? 0 : 1;

        // Detecta se as novas colunas existem (migration_v2)
        $temColunasV2 = false;
        try {
            $pdo->query("SELECT parent_id FROM comentarios LIMIT 0");
            $temColunasV2 = true;
        } catch (Throwable $e) {}

        if ($temColunasV2) {
            $pdo->prepare(
                "INSERT INTO comentarios (parent_id, usuario_id, referencia, tipo, texto, aprovado, flagged, flag_motivo, ip_hash, user_agent)
                 VALUES (?, ?, ?, 'blog', ?, ?, ?, ?, ?, ?)"
            )->execute([
                $parentId, $uid, $slug, $texto, $aprovado,
                $filtro['flagged'] ? 1 : 0,
                $filtro['flagged'] ? $filtro['motivo'] : null,
                $ipHash, $ua,
            ]);
        } else {
            // Fallback: schema antigo (sem as novas colunas)
            $pdo->prepare(
                "INSERT INTO comentarios (usuario_id, referencia, tipo, texto, aprovado)
                 VALUES (?, ?, 'blog', ?, ?)"
            )->execute([$uid, $slug, $texto, $aprovado]);
        }

        $novoId = (int) $pdo->lastInsertId();

        // ── Se flagged e tabela de log existir: registrar prova circunstancial ──
        if ($filtro['flagged'] && $temColunasV2) {
            try {
                $stUser = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id=? LIMIT 1");
                $stUser->execute([$uid]);
                $autor = $stUser->fetch();

                $pdo->prepare(
                    "INSERT INTO comentarios_flags_log
                       (comentario_id, usuario_id, usuario_nome, usuario_email, ip_hash, user_agent,
                        texto_original, motivo_flag, palavras_detectadas, referencia_slug, acao_tomada)
                     VALUES (?,?,?,?,?,?,?,?,?,?,'pendente')"
                )->execute([
                    $novoId, $uid,
                    $autor['nome']  ?? null,
                    $autor['email'] ?? null,
                    $ipHash, $ua, $texto,
                    $filtro['motivo'],
                    implode(', ', $filtro['palavras']),
                    $slug,
                ]);
            } catch (Throwable $e) { /* Tabela de log ainda não criada */ }
        }

        // ── Notificação por e-mail ao autor do comentário pai (se for resposta) ──
        if ($parentId && isset($pai) && $pai) {
            $paiAutorId = (int)$pai['usuario_id'];
            if ($paiAutorId !== $uid) { // Não notifica quem está respondendo a si mesmo
                $stPaiUser = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id=? LIMIT 1");
                $stPaiUser->execute([$paiAutorId]);
                $paiUsuario = $stPaiUser->fetch();

                if ($paiUsuario && $paiUsuario['email']) {
                    // Busca título do post
                    $stPost = $pdo->prepare("SELECT titulo FROM posts WHERE slug=? LIMIT 1");
                    $stPost->execute([$slug]);
                    $post = $stPost->fetch();
                    $tituloPost = $post['titulo'] ?? $slug;

                    $primeiroNome   = explode(' ', trim($paiUsuario['nome']))[0];
                    $urlComentarios = SITE_URL . '/diario/' . $slug . '#comentarios';
                    $textoExcerto   = mb_strlen($texto) > 150 ? mb_substr($texto, 0, 147) . '...' : $texto;
                    $textoEscapado  = htmlspecialchars($textoExcerto, ENT_QUOTES, 'UTF-8');

                    Mailer::enviar([
                        'para_email' => $paiUsuario['email'],
                        'para_nome'  => $paiUsuario['nome'],
                        'assunto'    => 'Alguém respondeu ao seu comentário — ' . $tituloPost,
                        'html'       => "
                            <p>Olá, <strong>{$primeiroNome}</strong>.</p>
                            <p>Alguém respondeu ao seu comentário no post <strong>{$tituloPost}</strong>:</p>
                            <blockquote style='border-left:3px solid #B8860B;padding:.5rem 1rem;margin:1rem 0;background:#faf7f2;color:#555;font-style:italic'>
                                {$textoEscapado}
                            </blockquote>
                            <p style='text-align:center;margin:2rem 0'>
                                <a href='{$urlComentarios}' class='btn-email'>Ver conversa</a>
                            </p>
                            <p style='font-size:.8em;color:#888'>Se não quiser mais receber estas notificações, acesse as preferências do seu perfil.</p>
                        ",
                        'texto' => "Olá {$primeiroNome},\n\nAlguém respondeu ao seu comentário em \"{$tituloPost}\".\n\n\"{$textoExcerto}\"\n\nVer: {$urlComentarios}",
                    ]);
                }
            }
        }

        responderOk([
            'id'       => $novoId,
            'flagged'  => $filtro['flagged'],
            'pendente' => !$aprovado,
            'mensagem' => $aprovado
                ? 'Comentário publicado!'
                : 'Comentário enviado. Será revisado antes de ser publicado.',
        ]);
    }

    /* ─────────────────────────────────────────────────────────
       CURTIR — toggle like no comentário
       ───────────────────────────────────────────────────────── */
    if ($acao === 'curtir') {
        if (empty($_SESSION['usuario_id'])) {
            responderErro('Faça login para curtir.', 401);
        }
        $uid = (int) $_SESSION['usuario_id'];
        $id  = (int) ($body['id'] ?? 0);
        if (!$id) responderErro('ID inválido.');

        $pdo = db();

        // Verifica se comentario_curtidas existe (migration_v2)
        try {
            $pdo->query("SELECT 1 FROM comentario_curtidas LIMIT 0");
        } catch (Throwable $e) {
            responderErro('Recurso de curtidas ainda não disponível. Execute a migration_comentarios_v2.sql.', 503);
        }

        $stCom = $pdo->prepare("SELECT id FROM comentarios WHERE id=? AND aprovado=1 LIMIT 1");
        $stCom->execute([$id]);
        if (!$stCom->fetch()) responderErro('Comentário não encontrado.', 404);

        $stCheck = $pdo->prepare("SELECT id FROM comentario_curtidas WHERE comentario_id=? AND usuario_id=? LIMIT 1");
        $stCheck->execute([$id, $uid]);
        $jaCurtiu = $stCheck->fetch();

        if ($jaCurtiu) {
            $pdo->prepare("DELETE FROM comentario_curtidas WHERE comentario_id=? AND usuario_id=?")->execute([$id, $uid]);
            $pdo->prepare("UPDATE comentarios SET curtidas_count = GREATEST(0, curtidas_count - 1) WHERE id=?")->execute([$id]);
            $curti = false;
        } else {
            $pdo->prepare("INSERT INTO comentario_curtidas (comentario_id, usuario_id) VALUES (?,?)")->execute([$id, $uid]);
            $pdo->prepare("UPDATE comentarios SET curtidas_count = curtidas_count + 1 WHERE id=?")->execute([$id]);
            $curti = true;
        }

        $stCount = $pdo->prepare("SELECT curtidas_count FROM comentarios WHERE id=?");
        $stCount->execute([$id]);
        $novoTotal = (int)$stCount->fetchColumn();

        responderOk(['curtiu' => $curti, 'total' => $novoTotal]);
    }

    /* ─────────────────────────────────────────────────────────
       DELETAR comentário (dono ou admin)
       ───────────────────────────────────────────────────────── */
    if ($acao === 'deletar') {
        if (empty($_SESSION['usuario_id'])) responderErro('Não autenticado.', 401);
        $uid = (int) $_SESSION['usuario_id'];
        $id  = (int) ($body['id'] ?? 0);
        if (!$id) responderErro('ID inválido.');

        $pdo  = db();
        $stmt = $pdo->prepare("SELECT usuario_id FROM comentarios WHERE id = ?");
        $stmt->execute([$id]);
        $com = $stmt->fetch();

        if (!$com) responderErro('Comentário não encontrado.', 404);

        $ehAdmin = !empty($_SESSION['admin_id']);
        if ($com['usuario_id'] !== $uid && !$ehAdmin) {
            responderErro('Sem permissão.', 403);
        }

        $pdo->prepare("DELETE FROM comentarios WHERE id = ?")->execute([$id]);
        responderOk(['mensagem' => 'Comentário removido.']);
    }

    responderErro('Ação inválida.');
}

responderErro('Método não permitido.', 405);
