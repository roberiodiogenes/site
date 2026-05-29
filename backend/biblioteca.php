<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/biblioteca.php
   Serve o catálogo de livros e contos de forma dinâmica.

   GET ?acao=listar&tipo=todos&genero=thriller&p=1&q=busca
       → lista paginada para a biblioteca.html

   GET ?acao=contadores
       → total por tipo/segmento (para os cards do admin)

   POST { acao:'salvar', ...dados }   → cria ou atualiza livro/conto (admin)
   POST { acao:'deletar', id }        → remove (admin)
   POST { acao:'reordenar', ids:[] }  → salva nova ordem (admin, drag-drop)
   ================================================================ */

require_once __DIR__ . '/config.php';
iniciarSessao();

$metodo  = $_SERVER['REQUEST_METHOD'];
$ADMIN   = !empty($_SESSION['admin_id']);  // só admins podem escrever
$POR_PAG = 12;                             // cards por página na biblioteca

/* ──────────────────────────────────────────────────────────────
   GET
   ────────────────────────────────────────────────────────────── */
if ($metodo === 'GET') {
    $acao   = trim($_GET['acao'] ?? 'listar');
    $pdo    = db();

    /* ── Contadores para painel admin ── */
    if ($acao === 'contadores') {
        $total   = $pdo->query("SELECT COUNT(*) FROM livros WHERE ativo=1")->fetchColumn();
        $livros  = $pdo->query("SELECT COUNT(*) FROM livros WHERE ativo=1 AND tipo='livro'")->fetchColumn();
        $contos  = $pdo->query("SELECT COUNT(*) FROM livros WHERE ativo=1 AND tipo='conto'")->fetchColumn();
        $gratis  = $pdo->query("SELECT COUNT(*) FROM livros WHERE ativo=1 AND gratuito=1")->fetchColumn();
        $destaques = $pdo->query("SELECT COUNT(*) FROM livros WHERE ativo=1 AND destaque=1")->fetchColumn();
        responderOk(compact('total','livros','contos','gratis','destaques'));
    }

    /* ── Busca um item pelo ID (para edição no admin) ── */
    if ($acao === 'buscar' && $ADMIN) {
        $id   = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM livros WHERE id=?");
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) responderErro('Não encontrado.', 404);
        responderOk(['item' => $item]);
    }

    /* ── Listar catálogo (público) ── */
    $tipo   = trim($_GET['tipo']   ?? 'todos');    // 'todos'|'livro'|'conto'
    $genero = trim($_GET['genero'] ?? 'todos');    // 'todos'|'thriller'|etc.
    $busca  = trim($_GET['q']      ?? '');
    $pagina = max(1, (int)($_GET['p'] ?? 1));
    $offset = ($pagina - 1) * $POR_PAG;

    $where  = ["l.ativo = 1"];
    $params = [];

    if ($tipo !== 'todos')   { $where[] = "l.tipo = ?";             $params[] = $tipo; }
    if ($genero !== 'todos') { $where[] = "l.genero LIKE ?";        $params[] = "%$genero%"; }
    if ($busca)              { $where[] = "(l.titulo LIKE ? OR l.sinopse LIKE ?)"; $params[] = "%$busca%"; $params[] = "%$busca%"; }

    $wsql = 'WHERE ' . implode(' AND ', $where);

    // Total para paginação
    $stTotal = $pdo->prepare("SELECT COUNT(*) FROM livros l $wsql");
    $stTotal->execute($params);
    $total      = (int)$stTotal->fetchColumn();
    $totalPags  = (int)ceil($total / $POR_PAG);

    // Dados
    $stList = $pdo->prepare(
        "SELECT l.slug, l.tipo, l.titulo, l.subtitulo, l.genero,
                l.sinopse, l.capa_img, l.preco, l.preco_promocao,
                l.gratuito, l.destaque, l.novo, l.badges,
                l.link_amazon, l.data_pub, l.total_capitulos,
                l.pasta_conteudo,
                ROUND(AVG(a.estrelas),1)      AS media_aval,
                COUNT(DISTINCT a.usuario_id)   AS n_aval,
                COUNT(DISTINCT c.id)           AS n_compras
         FROM livros l
         LEFT JOIN avaliacoes a ON a.livro_slug = l.slug
         LEFT JOIN compras    c ON c.livro_slug = l.slug AND c.status = 'aprovada'
         $wsql
         GROUP BY l.id
         ORDER BY l.destaque DESC, l.ordem ASC, l.data_pub DESC
         LIMIT $POR_PAG OFFSET $offset"
    );
    $stList->execute($params);
    $itens = $stList->fetchAll();

    // Formata dados para o JS
    foreach ($itens as &$i) {
        $i['preco']          = $i['preco']          ? (float) $i['preco']          : null;
        $i['preco_promocao'] = $i['preco_promocao'] ? (float) $i['preco_promocao'] : null;
        $i['gratuito']       = (bool)  $i['gratuito'];
        $i['destaque']       = (bool)  $i['destaque'];
        $i['novo']           = (bool)  $i['novo'];
        $i['media_aval']     = $i['media_aval'] ? (float) $i['media_aval'] : null;
        $i['n_aval']         = (int)   $i['n_aval'];
        $i['n_compras']      = (int)   $i['n_compras'];
        $i['badges']         = $i['badges'] ? explode(',', $i['badges']) : [];
    }
    unset($i);

    responderOk([
        'itens'      => $itens,
        'total'      => $total,
        'total_pags' => $totalPags,
        'pagina'     => $pagina,
        'por_pag'    => $POR_PAG,
    ]);
}

/* ──────────────────────────────────────────────────────────────
   POST — apenas admins
   ────────────────────────────────────────────────────────────── */
if ($metodo === 'POST') {
    if (!$ADMIN) responderErro('Acesso negado.', 403);

    $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $acao = trim($body['acao'] ?? '');
    $pdo  = db();

    /* ── Deletar ── */
    if ($acao === 'deletar') {
        $id = (int)($body['id'] ?? 0);
        if (!$id) responderErro('ID inválido.');
        $pdo->prepare("DELETE FROM livros WHERE id=?")->execute([$id]);
        responderOk(['mensagem' => 'Item removido.']);
    }

    /* ── Reordenar ── */
    if ($acao === 'reordenar') {
        $ids = array_filter(array_map('intval', $body['ids'] ?? []));
        foreach ($ids as $ord => $id) {
            $pdo->prepare("UPDATE livros SET ordem=? WHERE id=?")->execute([$ord + 1, $id]);
        }
        responderOk(['mensagem' => 'Ordem salva.']);
    }

    /* ── Salvar (criar ou editar) ── */
    if ($acao === 'salvar') {
        // Campos obrigatórios
        $titulo = trim($body['titulo'] ?? '');
        $slug   = trim($body['slug']   ?? '');
        $tipo   = in_array($body['tipo'] ?? '', ['livro','conto']) ? $body['tipo'] : 'livro';

        if (!$titulo) responderErro('Título é obrigatório.');

        // Gera slug automático se não informado
        if (!$slug) {
            $slug = _slugify($titulo);
        } else {
            $slug = _slugify($slug);
        }

        // Verifica unicidade do slug (ignora o próprio registro em edições)
        $idExist = (int)($body['id'] ?? 0);
        $stSlug  = $pdo->prepare("SELECT id FROM livros WHERE slug=? AND id != ?");
        $stSlug->execute([$slug, $idExist]);
        if ($stSlug->fetch()) {
            // Slug duplicado — adiciona sufixo numérico
            $slug = $slug . '-' . time();
        }

        // Trata o preço promocional (vazio = NULL)
        $preco     = $body['preco']          !== '' ? (float) str_replace(',', '.', $body['preco']          ?? '') : null;
        $promocao  = $body['preco_promocao'] !== '' ? (float) str_replace(',', '.', $body['preco_promocao'] ?? '') : null;

        // Badges como CSV
        $badgesRaw = $body['badges'] ?? [];
        if (is_array($badgesRaw)) $badgesRaw = implode(',', array_filter($badgesRaw));

        // Pasta de conteúdo padrão
        $pasta = trim($body['pasta_conteudo'] ?? '') ?: "livros-conteudo/{$slug}/";

        $campos = [
            'slug'           => $slug,
            'tipo'           => $tipo,
            'titulo'         => $titulo,
            'subtitulo'      => trim($body['subtitulo']      ?? '') ?: null,
            'genero'         => trim($body['genero']         ?? '') ?: null,
            'sinopse'        => trim($body['sinopse']        ?? '') ?: null,
            'capa_img'       => trim($body['capa_img']       ?? '') ?: null,
            'preco'          => $preco,
            'preco_promocao' => $promocao,
            'total_capitulos'=> (int)($body['total_capitulos'] ?? 0) ?: null,
            'pasta_conteudo' => $pasta,
            'ativo'          => isset($body['ativo']) ? (int)(bool)$body['ativo'] : 1,
            'destaque'       => isset($body['destaque'])  ? 1 : 0,
            'gratuito'       => isset($body['gratuito'])  ? 1 : 0,
            'novo'           => isset($body['novo'])      ? 1 : 0,
            'badges'         => $badgesRaw ?: null,
            'link_amazon'    => trim($body['link_amazon'] ?? '') ?: null,
            'data_pub'       => trim($body['data_pub']    ?? '') ?: null,
            'ordem'          => (int)($body['ordem'] ?? 99),
        ];

        if ($idExist) {
            // UPDATE
            $sets   = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($campos)));
            $values = array_values($campos);
            $values[] = $idExist;
            $pdo->prepare("UPDATE livros SET $sets WHERE id = ?")->execute($values);
            responderOk(['mensagem' => 'Salvo com sucesso!', 'slug' => $slug, 'id' => $idExist]);
        } else {
            // INSERT
            $cols   = implode(', ', array_map(fn($k) => "`$k`", array_keys($campos)));
            $marks  = implode(', ', array_fill(0, count($campos), '?'));
            $pdo->prepare("INSERT INTO livros ($cols) VALUES ($marks)")->execute(array_values($campos));
            $novoId = (int)$pdo->lastInsertId();
            responderOk(['mensagem' => 'Criado com sucesso!', 'slug' => $slug, 'id' => $novoId]);
        }
    }

    responderErro('Ação inválida.');
}

responderErro('Método não permitido.', 405);

/* ── Helper: gera slug a partir de string ── */
function _slugify(string $str): string {
    $map = ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a',
            'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
            'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
            'ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
            'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
            'ç'=>'c','ñ'=>'n','ý'=>'y',
            'Á'=>'a','À'=>'a','Ã'=>'a','Â'=>'a',
            'É'=>'e','Ê'=>'e','Í'=>'i','Ó'=>'o','Ô'=>'o',
            'Õ'=>'o','Ú'=>'u','Ç'=>'c'];
    $str = strtr(mb_strtolower(trim($str)), $map);
    $str = preg_replace('/[^a-z0-9\s\-]/', '', $str);
    $str = preg_replace('/[\s\-]+/', '-', $str);
    return trim($str, '-');
}
