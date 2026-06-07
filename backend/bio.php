<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/bio.php
   API da página bio (link-in-bio).

   GET  ?acao=dados    → retorna config + links ativos (público)
   POST admin: { acao:'salvar_config', dados:{} }
   POST admin: { acao:'salvar_link', id?, titulo, url, ... }
   POST admin: { acao:'deletar_link', id }
   POST admin: { acao:'reordenar', ids:[1,3,2] }
   ================================================================ */

require_once __DIR__ . '/config.php';
iniciarSessao();

$metodo = $_SERVER['REQUEST_METHOD'];

/* ── GET público: dados da bio ───────────────────────────────── */
if ($metodo === 'GET') {
    $pdo = db();

    // Config (fallback se tabela não existir)
    $config = [];
    try {
        $rows = $pdo->query("SELECT chave, valor FROM bio_config")->fetchAll();
        foreach ($rows as $r) $config[$r['chave']] = $r['valor'];
    } catch (Throwable $e) {
        $config = [
            'nome'       => 'Robério Diógenes',
            'subtitulo'  => 'Escritor · Literatura Brasileira',
            'foto'       => 'img/autor2.jpg',
            'instagram'  => 'https://instagram.com/diogenesroberio',
            'whatsapp'   => 'https://wa.me/5585996409818',
            'telegram'   => 'https://t.me/5585996409818',
            'linkedin'   => 'https://linkedin.com/in/roberio-diogenes',
            'email'      => 'mailto:contato@roberiodiogenes.com',
        ];
    }

    // Links ativos
    $links = [];
    try {
        $links = $pdo->query(
            "SELECT id, titulo, subtitulo, url, icone, tipo, ordem
             FROM bio_links WHERE ativo=1 ORDER BY ordem ASC, id ASC"
        )->fetchAll();
        $siteUrl = SITE_URL;
        foreach ($links as &$l) {
            $l['id'] = (int)$l['id'];
            // Converte URLs root-relative (/livros.html) para absolutas usando SITE_URL
            if (isset($l['url']) && str_starts_with($l['url'], '/') && !str_starts_with($l['url'], '//')) {
                $l['url'] = $siteUrl . $l['url'];
            }
        }
        unset($l);
    } catch (Throwable $e) {
        // Fallback estático (URLs absolutas para funcionar em qualquer ambiente)
        $b = SITE_URL;
        $links = [
            ['id'=>1,'titulo'=>'Biblioteca de Obras','subtitulo'=>'Romances e contos','url'=>"$b/livros.html",'icone'=>'fa-book','tipo'=>'destaque','ordem'=>1],
            ['id'=>2,'titulo'=>'Diário do Escritor','subtitulo'=>'Blog e reflexões','url'=>"$b/blog.html",'icone'=>'fa-pen-nib','tipo'=>'link','ordem'=>2],
            ['id'=>3,'titulo'=>'Leitor Online','subtitulo'=>'Leia no navegador','url'=>"$b/leitor/index.html",'icone'=>'fa-book-open','tipo'=>'link','ordem'=>3],
            ['id'=>4,'titulo'=>'Planos de Assinatura','subtitulo'=>'Acesso completo','url'=>"$b/pagamento/assinatura.html",'icone'=>'fa-crown','tipo'=>'link','ordem'=>4],
            ['id'=>5,'titulo'=>'Sobre o Autor','subtitulo'=>'A história por trás','url'=>"$b/autor.html",'icone'=>'fa-user-pen','tipo'=>'link','ordem'=>5],
            ['id'=>6,'titulo'=>'Newsletter Gratuita','subtitulo'=>'Novos posts no e-mail','url'=>"$b/blog.html#newsletter",'icone'=>'fa-envelope-open-text','tipo'=>'link','ordem'=>7],
            ['id'=>7,'titulo'=>'Contato para Imprensa','subtitulo'=>'Entrevistas e parcerias','url'=>"$b/contato.html",'icone'=>'fa-newspaper','tipo'=>'link','ordem'=>8],
        ];
    }

    // ── Links dinâmicos: último post e últimos livros ─────────────
    $dinamicos = [];

    $base = SITE_URL; // ex: http://localhost/roberiodiogenes.com (local) ou https://roberiodiogenes.com (prod)

    // Último post publicado
    try {
        $stP = $pdo->query(
            "SELECT slug, titulo, publicado_em
             FROM posts WHERE status='publicado'
             ORDER BY publicado_em DESC LIMIT 1"
        );
        $post = $stP->fetch(PDO::FETCH_ASSOC);
        if ($post) {
            $dataFmt = date('d/m/Y', strtotime($post['publicado_em']));
            $dinamicos[] = [
                'id'        => 0,
                'link_slug' => 'ultimo-post',
                'titulo'    => mb_substr($post['titulo'], 0, 55, 'UTF-8'),
                'subtitulo' => "Novo no Diário · {$dataFmt}",
                'url'       => $base . '/blog/post-template.html?slug=' . urlencode($post['slug']),
                'icone'     => 'fa-pen-nib',
                'tipo'      => 'novo',
                'ordem'     => 0,
            ];
        }
    } catch (Throwable $e) {}

    // Últimos 2 livros adicionados
    try {
        $stL = $pdo->query(
            "SELECT slug, titulo, capa_img FROM livros WHERE ativo=1 ORDER BY id DESC LIMIT 2"
        );
        $livros = $stL->fetchAll(PDO::FETCH_ASSOC);
        foreach ($livros as $livro) {
            $dinamicos[] = [
                'id'        => 0,
                'link_slug' => 'livro-' . $livro['slug'],
                'titulo'    => $livro['titulo'],
                'subtitulo' => 'Disponível na biblioteca',
                'url'       => $base . '/livros/' . $livro['slug'] . '.html',
                'icone'     => 'fa-book',
                'tipo'      => 'livro',
                'capa'      => $livro['capa_img'] ?? null,
                'ordem'     => 0.5,
            ];
        }
    } catch (Throwable $e) {}

    responderOk(['config' => $config, 'links' => $links, 'dinamicos' => $dinamicos]);
}

/* ── GET admin: estatísticas de cliques ─────────────────────── */
if ($metodo === 'GET' && ($_GET['acao'] ?? '') === 'cliques') {
    if (empty($_SESSION['admin_id'])) responderErro('Não autenticado.', 401);
    $pdo = db();
    try {
        $periodo = max(7, min(365, (int)($_GET['dias'] ?? 30)));
        // Cliques por link (últimos N dias)
        $stL = $pdo->prepare(
            "SELECT COALESCE(bc.link_slug, CONCAT('link-', bc.link_id)) AS slug,
                    COALESCE(bl.titulo, bc.link_slug, CONCAT('Link #', bc.link_id)) AS titulo,
                    COUNT(*) AS total,
                    COUNT(DISTINCT bc.ip_hash) AS unicos,
                    MAX(bc.clicado_em) AS ultimo
             FROM bio_clicks bc
             LEFT JOIN bio_links bl ON bl.id = bc.link_id
             WHERE bc.clicado_em >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY slug, titulo
             ORDER BY total DESC
             LIMIT 20"
        );
        $stL->execute([$periodo]);
        $porLink = $stL->fetchAll(PDO::FETCH_ASSOC);

        // Top origens
        $stO = $pdo->prepare(
            "SELECT COALESCE(origem, 'Direto') AS origem, COUNT(*) AS total
             FROM bio_clicks
             WHERE clicado_em >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY origem ORDER BY total DESC LIMIT 10"
        );
        $stO->execute([$periodo]);
        $porOrigem = $stO->fetchAll(PDO::FETCH_ASSOC);

        // Total geral
        $stT = $pdo->prepare("SELECT COUNT(*), COUNT(DISTINCT ip_hash) FROM bio_clicks WHERE clicado_em >= DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stT->execute([$periodo]);
        [$totalCliques, $totalUnicos] = $stT->fetch(PDO::FETCH_NUM);

        responderOk([
            'por_link'    => $porLink,
            'por_origem'  => $porOrigem,
            'total'       => (int)$totalCliques,
            'unicos'      => (int)$totalUnicos,
            'periodo_dias'=> $periodo,
        ]);
    } catch (Throwable $e) {
        responderOk(['por_link'=>[],'por_origem'=>[],'total'=>0,'unicos'=>0,'periodo_dias'=>30]);
    }
}

/* ── POST: ações admin ───────────────────────────────────────── */
if ($metodo === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $acao = trim($body['acao'] ?? $_POST['acao'] ?? '');

    // ── click: rastrear clique (endpoint público, sem auth) ────────
    if ($acao === 'click') {
        $pdo      = db();
        $link_id  = (int)($body['link_id']  ?? 0) ?: null;
        $link_slug = trim($body['link_slug'] ?? '');
        $origem   = mb_substr(trim($body['origem']  ?? ''), 0, 200, 'UTF-8') ?: null;
        $ip_hash  = hash('sha256', getIP());
        try {
            $pdo->prepare(
                "INSERT INTO bio_clicks (link_id, link_slug, origem, ip_hash) VALUES (?,?,?,?)"
            )->execute([$link_id, $link_slug ?: null, $origem, $ip_hash]);
        } catch (Throwable $e) { /* tabela pode não existir ainda */ }
        responderOk();
    }

    if (empty($_SESSION['admin_id'])) {
        responderErro('Não autenticado.', 401);
    }
    $pdo  = db();

    /* Salvar configurações gerais */
    if ($acao === 'salvar_config') {
        $dados   = $body['dados'] ?? [];
        $campos  = ['nome','subtitulo','foto','instagram','whatsapp','telegram','linkedin','email','cor_fundo','cor_acento'];
        $stmt    = $pdo->prepare("INSERT INTO bio_config (chave, valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)");
        foreach ($campos as $c) {
            if (array_key_exists($c, $dados)) {
                $stmt->execute([$c, trim($dados[$c] ?? '')]);
            }
        }
        responderOk(['mensagem' => 'Configurações salvas.']);
    }

    /* Salvar link (criar ou editar) */
    if ($acao === 'salvar_link') {
        $id       = (int)($body['id'] ?? 0);
        $titulo   = trim($body['titulo']    ?? '');
        $subtitulo= trim($body['subtitulo'] ?? '');
        $url      = trim($body['url']       ?? '');
        $icone    = trim($body['icone']     ?? '');
        $tipo     = in_array($body['tipo']??'',['link','destaque']) ? $body['tipo'] : 'link';
        $ativo    = (int)($body['ativo']    ?? 1);
        $ordem    = (int)($body['ordem']    ?? 99);

        if (!$titulo) responderErro('Título obrigatório.');
        if (!$url)    responderErro('URL obrigatória.');

        if ($id) {
            $pdo->prepare(
                "UPDATE bio_links SET titulo=?,subtitulo=?,url=?,icone=?,tipo=?,ativo=?,ordem=? WHERE id=?"
            )->execute([$titulo,$subtitulo,$url,$icone,$tipo,$ativo,$ordem,$id]);
        } else {
            $pdo->prepare(
                "INSERT INTO bio_links (titulo,subtitulo,url,icone,tipo,ativo,ordem) VALUES (?,?,?,?,?,?,?)"
            )->execute([$titulo,$subtitulo,$url,$icone,$tipo,$ativo,$ordem]);
            $id = (int)$pdo->lastInsertId();
        }
        responderOk(['mensagem' => 'Link salvo.', 'id' => $id]);
    }

    /* Deletar link */
    if ($acao === 'deletar_link') {
        $id = (int)($body['id'] ?? 0);
        if ($id) $pdo->prepare("DELETE FROM bio_links WHERE id=?")->execute([$id]);
        responderOk(['mensagem' => 'Link removido.']);
    }

    /* Reordenar links */
    if ($acao === 'reordenar') {
        $ids  = array_filter(array_map('intval', $body['ids'] ?? []));
        $stmt = $pdo->prepare("UPDATE bio_links SET ordem=? WHERE id=?");
        foreach (array_values($ids) as $pos => $linkId) {
            $stmt->execute([$pos + 1, $linkId]);
        }
        responderOk(['mensagem' => 'Ordem salva.']);
    }

    /* Toggle ativo */
    if ($acao === 'toggle_ativo') {
        $id = (int)($body['id'] ?? 0);
        $pdo->prepare("UPDATE bio_links SET ativo = 1-ativo WHERE id=?")->execute([$id]);
        $novo = (int)$pdo->query("SELECT ativo FROM bio_links WHERE id=$id")->fetchColumn();
        responderOk(['ativo' => $novo]);
    }

    responderErro('Ação inválida.');
}

responderErro('Método não permitido.', 405);
