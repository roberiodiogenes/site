<?php
/* ================================================================
   admin/livros.php
   AJAX handlers antes de qualquer saída HTML.
   ================================================================ */
ob_start(); // Captura qualquer output acidental (warnings/notices) para não quebrar JSON

/* ── Detecta se é requisição AJAX ── */
$_isAjax = (
    $_SERVER['REQUEST_METHOD'] === 'POST' ||
    ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['acao'] ?? '') === 'buscar')
);

if ($_isAjax) {
    /* AJAX: inicia sessão e PDO manualmente, sem incluir _admin.php */
    ini_set('display_errors', '0');  // Garante que erros PHP não contaminem o JSON
    ob_end_clean();                  // Descarta qualquer output buffered antes de enviar headers
    session_name('rd_admin_sess');
    session_start();
    if (empty($_SESSION['admin_id'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'erro' => 'Não autenticado.']);
        exit;
    }
    require_once __DIR__ . '/../backend/config.php';
    try {
        $pdo = db();
    } catch (Throwable $e) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'erro' => 'Erro de conexão com o banco de dados.']);
        exit;
    }
}

/* ── Helper: slugify ── */
function _slugify(string $s): string {
    $m=['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n',
        'Á'=>'a','À'=>'a','Ã'=>'a','Â'=>'a','É'=>'e','Ê'=>'e','Í'=>'i',
        'Ó'=>'o','Ô'=>'o','Õ'=>'o','Ú'=>'u','Ç'=>'c'];
    $s=strtr(mb_strtolower(trim($s)),$m);
    $s=preg_replace('/[^a-z0-9\s\-]/','', $s);
    return trim(preg_replace('/[\s\-]+/','-',$s),'-');
}

/* ── GET: Buscar livro para o modal de edição ── */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['acao'] ?? '') === 'buscar') {
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)($_GET['id'] ?? 0);
    try {
        $st = $pdo->prepare('SELECT * FROM livros WHERE id = ?');
        $st->execute([$id]);
        $item = $st->fetch(PDO::FETCH_ASSOC);
        echo json_encode($item
            ? ['ok' => true,  'item' => $item]
            : ['ok' => false, 'erro' => 'Item não encontrado.']
        );
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'erro' => 'Erro ao buscar item: ' . $e->getMessage()]);
    }
    exit;
}

/* ── POST: Todas as ações AJAX ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'toggle_ativo') {
        try {
            $id = (int)($_POST['id'] ?? 0);
            $st = $pdo->prepare('SELECT ativo FROM livros WHERE id=?');
            $st->execute([$id]);
            $novo = $st->fetchColumn() ? 0 : 1;
            $pdo->prepare('UPDATE livros SET ativo=? WHERE id=?')->execute([$novo, $id]);
            echo json_encode(['ok'=>true,'novo'=>$novo]);
        } catch (Throwable $e) {
            echo json_encode(['ok'=>false,'erro'=>'Erro ao alterar status: '.$e->getMessage()]);
        }
        exit;
    }

    if ($acao === 'salvar_preco') {
        try {
            $id    = (int)($_POST['id']   ?? 0);
            $preco = (float)str_replace(',','.',($_POST['preco'] ?? '0'));
            $prom  = trim($_POST['preco_promocao'] ?? '') !== ''
                     ? (float)str_replace(',','.',($_POST['preco_promocao']??'0'))
                     : null;
            $pdo->prepare('UPDATE livros SET preco=?,preco_promocao=? WHERE id=?')->execute([$preco,$prom,$id]);
            echo json_encode(['ok'=>true]);
        } catch (Throwable $e) {
            echo json_encode(['ok'=>false,'erro'=>'Erro ao salvar preço: '.$e->getMessage()]);
        }
        exit;
    }

    if ($acao === 'salvar_caps') {
        try {
            $id   = (int)($_POST['id']              ?? 0);
            $caps = (int)($_POST['total_capitulos'] ?? 0);
            $pdo->prepare('UPDATE livros SET total_capitulos=? WHERE id=?')->execute([$caps,$id]);
            echo json_encode(['ok'=>true]);
        } catch (Throwable $e) {
            echo json_encode(['ok'=>false,'erro'=>'Erro ao salvar capítulos: '.$e->getMessage()]);
        }
        exit;
    }

    if ($acao === 'deletar') {
        try {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare('DELETE FROM livros WHERE id=?')->execute([$id]);
            echo json_encode(['ok'=>true]);
        } catch (Throwable $e) {
            echo json_encode(['ok'=>false,'erro'=>'Erro ao deletar: '.$e->getMessage()]);
        }
        exit;
    }

    if ($acao === 'salvar_item') {
        try {
            $id     = (int)($_POST['id'] ?? 0);
            $titulo = trim($_POST['titulo'] ?? '');
            $tipo   = in_array($_POST['tipo']??'',['livro','conto']) ? $_POST['tipo'] : 'livro';

            if (!$titulo) { echo json_encode(['ok'=>false,'erro'=>'Título obrigatório.']); exit; }

            $slug = trim($_POST['slug'] ?? '');
            $slug = $slug ? _slugify($slug) : _slugify($titulo);

            $stS = $pdo->prepare('SELECT id FROM livros WHERE slug=? AND id!=?');
            $stS->execute([$slug, $id]);
            if ($stS->fetch()) $slug .= '-' . substr(time(),-4);

            $preco    = trim($_POST['preco']          ?? '') !== '' ? (float)str_replace(',','.',($_POST['preco']??'0'))           : null;
            $promocao = trim($_POST['preco_promocao'] ?? '') !== '' ? (float)str_replace(',','.',($_POST['preco_promocao']??'0'))  : null;
            $pasta    = trim($_POST['pasta_conteudo'] ?? '') ?: "livros-conteudo/{$slug}/";

            $badgesArr = array_filter(array_map('trim', explode(',', $_POST['badges'] ?? '')));
            $badges    = $badgesArr ? implode(',', $badgesArr) : null;

            $campos = [
                'slug'            => $slug,
                'tipo'            => $tipo,
                'titulo'          => $titulo,
                'subtitulo'       => trim($_POST['subtitulo']      ?? '') ?: null,
                'genero'          => trim($_POST['genero']         ?? '') ?: null,
                'sinopse'         => trim($_POST['sinopse']        ?? '') ?: null,
                'capa_img'        => trim($_POST['capa_img']       ?? '') ?: null,
                'preco'           => $preco,
                'preco_promocao'  => $promocao,
                'total_capitulos' => (int)($_POST['total_capitulos'] ?? 0) ?: null,
                'pasta_conteudo'  => $pasta,
                // ativo vem de um <select> (não checkbox), então usar o valor diretamente
                'ativo'           => (int)($_POST['ativo'] ?? 0) === 1 ? 1 : 0,
                'destaque'        => isset($_POST['destaque']) ? 1 : 0,
                'gratuito'        => isset($_POST['gratuito']) ? 1 : 0,
                'novo'            => isset($_POST['novo'])     ? 1 : 0,
                'badges'          => $badges,
                'link_amazon'     => trim($_POST['link_amazon'] ?? '') ?: null,
                'data_pub'        => trim($_POST['data_pub']    ?? '') ?: null,
                'ordem'           => (int)($_POST['ordem'] ?? 99),
                'promo_ate'       => trim($_POST['promo_ate']    ?? '') ?: null,
                'gratuito_ate'    => trim($_POST['gratuito_ate'] ?? '') ?: null,
            ];

            if ($id) {
                $sets   = implode(',', array_map(fn($k) => "`$k`=?", array_keys($campos)));
                $vals   = array_values($campos);
                $vals[] = $id;
                $pdo->prepare("UPDATE livros SET $sets WHERE id=?")->execute($vals);
                echo json_encode(['ok'=>true,'mensagem'=>'Salvo!','slug'=>$slug,'id'=>$id]);
            } else {
                $cols  = implode(',', array_map(fn($k) => "`$k`", array_keys($campos)));
                $marks = implode(',', array_fill(0, count($campos), '?'));
                $pdo->prepare("INSERT INTO livros ($cols) VALUES ($marks)")->execute(array_values($campos));
                $novoId = (int)$pdo->lastInsertId();
                echo json_encode(['ok'=>true,'mensagem'=>'Criado!','slug'=>$slug,'id'=>$novoId]);
            }
        } catch (Throwable $e) {
            echo json_encode(['ok'=>false,'erro'=>'Erro ao salvar: '.$e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['ok'=>false,'erro'=>'Ação desconhecida.']);
    exit;
}

/* ── A partir daqui: HTML da página (GET normal, sem ?acao=buscar) ── */
$ADMIN_PAGE = 'livros';
require_once __DIR__ . '/_admin.php';

/* ── Dados da listagem ── */
$filtroTipo = $_GET['tipo'] ?? 'todos';
$tipoFiltro = $filtroTipo === 'conto' ? 'conto' : 'livro';
$where      = $filtroTipo !== 'todos' ? "WHERE l.tipo = '$tipoFiltro'" : '';

// Query adaptável: subqueries de tabelas opcionais são ignoradas se a tabela não existir
try {
    $livros = $pdo->query(
        "SELECT l.id,l.slug,l.tipo,l.titulo,l.subtitulo,l.genero,l.preco,l.preco_promocao,
         l.ativo,l.destaque,l.gratuito,l.novo,l.total_capitulos,l.capa_img,l.ordem,l.data_pub,
         (SELECT COUNT(*) FROM compras WHERE livro_slug=l.slug AND status='aprovada') AS n_compras,
         (SELECT COALESCE(SUM(preco_pago),0) FROM compras WHERE livro_slug=l.slug AND status='aprovada') AS receita,
         0 AS media_aval, 0 AS n_leitores,
         l.promo_ate, l.gratuito_ate
         FROM livros l $where ORDER BY l.tipo ASC, l.ordem ASC, l.titulo ASC"
    )->fetchAll();
} catch (Throwable $e) {
    // Fallback sem colunas de promoção (migration pendente)
    try {
        $livros = $pdo->query(
            "SELECT l.id,l.slug,l.tipo,l.titulo,l.subtitulo,l.genero,l.preco,l.preco_promocao,
             l.ativo,l.destaque,l.gratuito,l.novo,l.total_capitulos,l.capa_img,l.ordem,l.data_pub,
             0 AS n_compras, 0 AS receita, 0 AS media_aval, 0 AS n_leitores,
             NULL AS promo_ate, NULL AS gratuito_ate
             FROM livros l $where ORDER BY l.tipo ASC, l.ordem ASC, l.titulo ASC"
        )->fetchAll();
    } catch (Throwable $e2) { $livros = []; }
}
try { $nLivros = $pdo->query("SELECT COUNT(*) FROM livros WHERE tipo='livro'")->fetchColumn(); } catch (Throwable $e) { $nLivros = 0; }
try { $nContos = $pdo->query("SELECT COUNT(*) FROM livros WHERE tipo='conto'")->fetchColumn(); } catch (Throwable $e) { $nContos = 0; }
try { $nAtivos = $pdo->query("SELECT COUNT(*) FROM livros WHERE ativo=1")->fetchColumn(); } catch (Throwable $e) { $nAtivos = 0; }
try { $recTotal = $pdo->query("SELECT COALESCE(SUM(preco_pago),0) FROM compras WHERE status='aprovada'")->fetchColumn(); } catch (Throwable $e) { $recTotal = 0; }
?>
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
  <div>
    <h1 class="page-titulo">Livros & Contos</h1>
    <p class="page-sub">Catálogo completo — gerencie, publique e precifique</p>
  </div>
  <button class="btn btn-primario" onclick="abrirFormNovo()">
    <i class="fa fa-plus"></i> Novo item
  </button>
</div>

<div class="stats-grade" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr))">
  <div class="stat-card"><i class="fa fa-book stat-icone"></i><div><div class="stat-valor"><?= $nLivros ?></div><div class="stat-label">Livros</div></div></div>
  <div class="stat-card"><i class="fa fa-scroll stat-icone"></i><div><div class="stat-valor"><?= $nContos ?></div><div class="stat-label">Contos</div></div></div>
  <div class="stat-card"><i class="fa fa-eye stat-icone"></i><div><div class="stat-valor"><?= $nAtivos ?></div><div class="stat-label">Publicados</div></div></div>
  <div class="stat-card"><i class="fa fa-dollar-sign stat-icone"></i><div><div class="stat-valor">R$ <?= number_format((float)$recTotal,0,',','.') ?></div><div class="stat-label">Receita total</div></div></div>
</div>

<!-- Filtro de tipo -->
<div style="display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap">
  <?php foreach (['todos'=>'Todos','livro'=>'Livros','conto'=>'Contos'] as $val=>$lab): ?>
  <a href="?tipo=<?= $val ?>" class="btn <?= $filtroTipo===$val?'btn-primario':'btn-ghost' ?> btn-sm">
    <?= $lab ?>
    <?php if ($val==='livro'): ?>(<?= $nLivros ?>)<?php elseif ($val==='conto'): ?>(<?= $nContos ?>)<?php endif; ?>
  </a>
  <?php endforeach; ?>
</div>

<div class="secao">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-book"></i> Catálogo</span>
  </div>
  <?php if (!$livros): ?>
    <div class="estado-vazio"><i class="fa fa-book"></i><p>Nenhum item cadastrado ainda.</p></div>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Título</th>
        <th>Tipo</th>
        <th>Gênero</th>
        <th>Preço</th>
        <th>Caps</th>
        <th>Vendas</th>
        <th>Aval.</th>
        <th>Flags</th>
        <th>Status</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($livros as $l): ?>
    <tr>
      <td>
        <div class="td-nome" style="max-width:200px"><?= adm_esc($l['titulo']) ?></div>
        <?php if ($l['subtitulo']): ?><div class="td-sub"><?= adm_esc($l['subtitulo']) ?></div><?php endif; ?>
        <div class="td-sub" style="margin-top:.2rem"><code style="font-size:.62rem;background:var(--fundo-input);padding:.1rem .3rem;border-radius:3px"><?= adm_esc($l['slug']) ?></code></div>
      </td>
      <td>
        <span class="badge <?= $l['tipo']==='conto'?'badge-azul':'badge-amarelo' ?>">
          <?= $l['tipo']==='conto' ? 'Conto' : 'Livro' ?>
        </span>
      </td>
      <td style="font-size:.78rem"><?= adm_esc($l['genero'] ?? '—') ?></td>
      <td style="font-size:.78rem;white-space:nowrap">
        <?php if ($l['gratuito']): ?>
          <span class="badge badge-verde">Grátis</span>
        <?php elseif ($l['preco']): ?>
          R$ <?= number_format((float)$l['preco'],2,',','.') ?>
          <?php if ($l['preco_promocao']): ?>
            <span style="color:var(--ouro);font-size:.68rem;display:block">Promo: R$ <?= number_format((float)$l['preco_promocao'],2,',','.') ?></span>
          <?php endif; ?>
        <?php else: ?>—<?php endif; ?>
      </td>
      <td style="text-align:center"><?= $l['total_capitulos'] ?: '—' ?></td>
      <td style="text-align:center">
        <div><?= $l['n_compras'] ?: '—' ?></div>
        <?php if ($l['receita'] > 0): ?><div class="td-sub">R$ <?= number_format((float)$l['receita'],0,',','.') ?></div><?php endif; ?>
      </td>
      <td style="text-align:center"><?= $l['media_aval'] ? $l['media_aval'].'★' : '—' ?></td>
      <td style="font-size:.7rem">
        <?= $l['destaque'] ? '<span class="badge badge-ferrugem">⭐</span> ' : '' ?>
        <?= $l['novo']     ? '<span class="badge badge-ouro">Novo</span> '   : '' ?>
        <?= $l['n_leitores'] ? '<span style="color:var(--texto-3)">'.$l['n_leitores'].' leit.</span>' : '' ?>
      </td>
      <td>
        <?= adm_badge($l['ativo'] ? 'ativo' : 'inativo') ?>
        <?php
          // Badge de promoção ativa
          if (!empty($l['promo_ate']) && strtotime($l['promo_ate']) > time()): ?>
          <span class="badge badge-ouro" style="margin-top:.2rem;display:block" title="Promoção até <?= date('d/m H:i', strtotime($l['promo_ate'])) ?>">
            <i class="fa fa-tag"></i> Promo
          </span>
        <?php endif;
          // Badge de gratuito temporário
          if (!empty($l['gratuito_ate']) && strtotime($l['gratuito_ate']) > time()): ?>
          <span class="badge badge-verde" style="margin-top:.2rem;display:block" title="Grátis até <?= date('d/m H:i', strtotime($l['gratuito_ate'])) ?>">
            <i class="fa fa-gift"></i> Grátis
          </span>
        <?php endif; ?>
      </td>
      <td>
        <div style="display:flex;gap:.3rem;flex-wrap:wrap">
          <button class="btn btn-sm btn-ghost" onclick="editarItem(<?= $l['id'] ?>)" title="Editar">
            <i class="fa fa-pencil"></i>
          </button>
          <a href="../livros/<?= adm_esc($l['slug']) ?>.html" target="_blank"
             class="btn btn-sm btn-ghost" title="Ver página">
            <i class="fa fa-eye"></i>
          </a>
          <button class="btn btn-sm btn-ghost" onclick="toggleAtivo(<?= $l['id'] ?>,<?= $l['ativo'] ?>)"
                  title="<?= $l['ativo']?'Desativar':'Ativar' ?>">
            <i class="fa <?= $l['ativo']?'fa-eye-slash':'fa-eye' ?>"></i>
          </button>
          <button class="btn btn-sm btn-danger" onclick="deletarItem(<?= $l['id'] ?>, '<?= adm_esc(addslashes($l['titulo'])) ?>')"
                  title="Deletar">
            <i class="fa fa-trash"></i>
          </button>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<!-- ══ MODAL CADASTRO / EDIÇÃO ════════════════════════════════ -->
<div class="modal-overlay" id="modalItem" style="align-items:flex-start;padding:2rem 1rem;overflow-y:auto">
  <div class="modal-box" style="max-width:640px;width:100%;margin:auto">
    <h2 class="modal-titulo" id="modalItemTitulo"><i class="fa fa-plus"></i> Novo item</h2>

    <form id="formItem" onsubmit="salvarItem(event)">
      <input type="hidden" id="itemId" name="id" value="0">

      <!-- Linha 1: Tipo + Ativo -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
        <div class="modal-campo">
          <label>Tipo *</label>
          <select name="tipo" id="itemTipo" onchange="atualizarDicasTipo()">
            <option value="livro">📚 Livro</option>
            <option value="conto">📜 Conto</option>
          </select>
        </div>
        <div class="modal-campo">
          <label>Status</label>
          <select name="ativo" id="itemAtivo">
            <option value="1">Publicado (visível)</option>
            <option value="0">Rascunho (oculto)</option>
          </select>
        </div>
      </div>

      <!-- Título + Subtítulo -->
      <div class="modal-campo">
        <label>Título *</label>
        <input type="text" name="titulo" id="itemTitulo" placeholder="Nome da obra" required oninput="gerarSlug()">
      </div>
      <div class="modal-campo">
        <label>Subtítulo</label>
        <input type="text" name="subtitulo" id="itemSubtitulo" placeholder="Ex: A outra metade do céu">
      </div>

      <!-- Slug -->
      <div class="modal-campo">
        <label>Slug (URL) — gerado automaticamente</label>
        <input type="text" name="slug" id="itemSlug" placeholder="ex: lumen"
               style="font-family:monospace;font-size:.82rem">
      </div>

      <!-- Gênero + Total caps -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
        <div class="modal-campo">
          <label>Gênero</label>
          <input type="text" name="genero" id="itemGenero" placeholder="Ex: Romance · Thriller" list="generos-lista">
          <datalist id="generos-lista">
            <option value="Romance">
            <option value="Thriller">
            <option value="Drama">
            <option value="Horror">
            <option value="Suspense">
            <option value="Ficção Científica">
            <option value="Fantasia">
            <option value="Auto-Ajuda">
            <option value="Poesia">
          </datalist>
        </div>
        <div class="modal-campo">
          <label id="labelCaps">Total de capítulos</label>
          <input type="number" name="total_capitulos" id="itemCaps" min="0" placeholder="0">
        </div>
      </div>

      <!-- Sinopse -->
      <div class="modal-campo">
        <label>Sinopse (aparece na biblioteca)</label>
        <textarea name="sinopse" id="itemSinopse" rows="3"
                  placeholder="Resumo da obra para o catálogo (máx. 300 chars)"
                  maxlength="300"
                  style="width:100%;padding:.6rem .75rem;background:var(--fundo-input);border:1px solid var(--borda);border-radius:var(--raio);color:var(--texto);font-size:.85rem;resize:vertical"></textarea>
      </div>

      <!-- Capa -->
      <div class="modal-campo">
        <label>Caminho da imagem de capa</label>
        <input type="text" name="capa_img" id="itemCapa" placeholder="img/nome-do-livro.jpg">
      </div>

      <!-- Preços e Promoção -->
      <div style="background:var(--fundo-input);border:1px solid var(--borda);border-radius:var(--raio);padding:.85rem;margin-bottom:.85rem">
        <p style="font-size:.63rem;letter-spacing:.1em;text-transform:uppercase;color:var(--ouro);margin-bottom:.6rem">
          <i class="fa fa-tag"></i> Preços e Promoção
        </p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:.6rem">
          <div class="modal-campo" style="margin-bottom:0">
            <label>Preço normal (R$)</label>
            <input type="text" name="preco" id="itemPreco" placeholder="29,90">
          </div>
          <div class="modal-campo" style="margin-bottom:0">
            <label>Preço promocional (R$)</label>
            <input type="text" name="preco_promocao" id="itemPromo" placeholder="Ex: 14,90">
          </div>
        </div>
        <div class="modal-campo" style="margin-bottom:0">
          <label>Promoção ativa até <span style="color:var(--texto-3);font-size:.6rem">(deixe vazio para desativar)</span></label>
          <input type="datetime-local" name="promo_ate" id="itemPromoAte"
                 style="font-size:.8rem">
          <p style="font-size:.65rem;color:var(--texto-3);margin-top:.25rem">
            Enquanto esta data não passar, o preço promocional será exibido e cobrado.
          </p>
        </div>
      </div>

      <!-- Gratuito temporário -->
      <div style="background:rgba(46,125,50,.07);border:1px solid rgba(46,125,50,.2);border-radius:var(--raio);padding:.85rem;margin-bottom:.85rem">
        <p style="font-size:.63rem;letter-spacing:.1em;text-transform:uppercase;color:#4CAF50;margin-bottom:.6rem">
          <i class="fa fa-gift"></i> Gratuito temporário
        </p>
        <div class="modal-campo" style="margin-bottom:0">
          <label>Livro gratuito até <span style="color:var(--texto-3);font-size:.6rem">(deixe vazio para não aplicar)</span></label>
          <input type="datetime-local" name="gratuito_ate" id="itemGratuitoAte"
                 style="font-size:.8rem">
          <p style="font-size:.65rem;color:var(--texto-3);margin-top:.25rem">
            Durante este período, o livro fica disponível gratuitamente para qualquer usuário.
          </p>
        </div>
      </div>

      <!-- Pasta de conteúdo -->
      <div class="modal-campo">
        <label>Pasta de capítulos (leitor)</label>
        <input type="text" name="pasta_conteudo" id="itemPasta"
               style="font-family:monospace;font-size:.82rem"
               placeholder="livros-conteudo/slug/">
      </div>

      <!-- Link Amazon -->
      <div class="modal-campo">
        <label>Link Amazon (opcional)</label>
        <input type="url" name="link_amazon" id="itemAmazon" placeholder="https://amazon.com.br/dp/...">
      </div>

      <!-- Badges + Data publicação + Ordem -->
      <div style="display:grid;grid-template-columns:1fr 1fr 80px;gap:.75rem">
        <div class="modal-campo">
          <label>Badges extras (separados por vírgula)</label>
          <input type="text" name="badges" id="itemBadges" placeholder="e-book, Físico">
        </div>
        <div class="modal-campo">
          <label>Data de publicação</label>
          <input type="date" name="data_pub" id="itemDataPub">
        </div>
        <div class="modal-campo">
          <label>Ordem</label>
          <input type="number" name="ordem" id="itemOrdem" value="99" min="1">
        </div>
      </div>

      <!-- Flags booleanas -->
      <div style="display:flex;gap:1.25rem;flex-wrap:wrap;padding:.75rem;background:var(--fundo-input);border-radius:var(--raio);margin-bottom:.85rem">
        <label style="display:flex;align-items:center;gap:.45rem;font-size:.82rem;cursor:pointer">
          <input type="checkbox" name="destaque" id="itemDestaque"> ⭐ Destaque
        </label>
        <label style="display:flex;align-items:center;gap:.45rem;font-size:.82rem;cursor:pointer">
          <input type="checkbox" name="novo" id="itemNovo"> ✨ Novo
        </label>
        <label style="display:flex;align-items:center;gap:.45rem;font-size:.82rem;cursor:pointer">
          <input type="checkbox" name="gratuito" id="itemGratuito"> 🎁 Gratuito
        </label>
      </div>

      <div class="modal-btns">
        <button type="button" class="btn btn-ghost" onclick="fecharModal('modalItem')">Cancelar</button>
        <button type="submit" class="btn btn-primario" id="btnSalvarItem">
          <i class="fa fa-floppy-disk"></i> Salvar
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// ── Gera slug automático ──
function gerarSlug() {
  const t = document.getElementById('itemTitulo').value;
  const s = t.toLowerCase()
    .replace(/[áàãâä]/g,'a').replace(/[éèêë]/g,'e').replace(/[íìîï]/g,'i')
    .replace(/[óòõôö]/g,'o').replace(/[úùûü]/g,'u').replace(/[ç]/g,'c')
    .replace(/[^a-z0-9\s\-]/g,'').replace(/[\s\-]+/g,'-').replace(/^-|-$/g,'');
  document.getElementById('itemSlug').value = s;
  document.getElementById('itemPasta').value = `livros-conteudo/${s}/`;
}

// ── Atualiza rótulo de caps conforme tipo ──
function atualizarDicasTipo() {
  const t = document.getElementById('itemTipo').value;
  document.getElementById('labelCaps').textContent =
    t === 'conto' ? 'Tempo de leitura (min aprox.)' : 'Total de capítulos';
}

// ── Abre modal para novo item ──
function abrirFormNovo() {
  document.getElementById('modalItemTitulo').innerHTML = '<i class="fa fa-plus"></i> Novo item';
  document.getElementById('formItem').reset();
  document.getElementById('itemId').value = '0';
  document.getElementById('itemOrdem').value = '99';
  document.getElementById('itemAtivo').value  = '1';
  abrirModal('modalItem');
}

// ── Carrega dados e abre modal de edição ──
async function editarItem(id) {
  document.getElementById('modalItemTitulo').innerHTML = '<i class="fa fa-pencil"></i> Editar item';
  try {
    // CORREÇÃO: Agora chama o próprio arquivo livros.php
    const r = await fetch(`livros.php?acao=buscar&id=${id}`, {credentials:'same-origin'});
    const d = await r.json();
    if (!d.ok) { toast('Erro ao carregar dados.','erro'); return; }
    const i = d.item;
    document.getElementById('itemId').value          = i.id;
    document.getElementById('itemTipo').value         = i.tipo || 'livro';
    document.getElementById('itemAtivo').value        = i.ativo ? '1' : '0';
    document.getElementById('itemTitulo').value       = i.titulo || '';
    document.getElementById('itemSubtitulo').value    = i.subtitulo || '';
    document.getElementById('itemSlug').value         = i.slug || '';
    document.getElementById('itemGenero').value       = i.genero || '';
    document.getElementById('itemCaps').value         = i.total_capitulos || '';
    document.getElementById('itemSinopse').value      = i.sinopse || '';
    document.getElementById('itemCapa').value         = i.capa_img || '';
    document.getElementById('itemPreco').value        = i.preco ? String(i.preco).replace('.',',') : '';
    document.getElementById('itemPromo').value        = i.preco_promocao ? String(i.preco_promocao).replace('.',',') : '';
    document.getElementById('itemPasta').value        = i.pasta_conteudo || '';
    document.getElementById('itemAmazon').value       = i.link_amazon || '';
    document.getElementById('itemBadges').value       = i.badges || '';
    document.getElementById('itemDataPub').value      = i.data_pub ? i.data_pub.split(' ')[0] : '';
    document.getElementById('itemOrdem').value        = i.ordem || 99;
    document.getElementById('itemDestaque').checked   = !!i.destaque;
    document.getElementById('itemNovo').checked       = !!i.novo;
    document.getElementById('itemGratuito').checked   = !!i.gratuito;
    // Promoção e gratuito temporário
    document.getElementById('itemPromoAte').value     = i.promo_ate    ? i.promo_ate.replace(' ','T').slice(0,16)    : '';
    document.getElementById('itemGratuitoAte').value  = i.gratuito_ate ? i.gratuito_ate.replace(' ','T').slice(0,16) : '';
    atualizarDicasTipo();
    abrirModal('modalItem');
  } catch(e) { toast('Erro de conexão.','erro'); }
}

// ── Salvar formulário ──
async function salvarItem(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSalvarItem');
  btn.disabled = true; btn.innerHTML = '<i class="fa fa-circle-notch fa-spin"></i> Salvando…';

  const form = document.getElementById('formItem');
  const fd   = new FormData(form);
  fd.set('acao','salvar_item');

  // Checkboxes (FormData não inclui desmarcados)
  ['ativo','destaque','novo','gratuito'].forEach(n => {
    if (!form.querySelector(`[name="${n}"]`).checked &&
        form.querySelector(`[name="${n}"]`).type !== 'select-one') {
      fd.delete(n);
    }
  });
  if (document.getElementById('itemAtivo').value === '1') fd.set('ativo','1');

  try {
    const r = await fetch('livros.php', {method:'POST', body: new URLSearchParams(fd)});
    const d = await r.json();
    fecharModal('modalItem');
    if (d.ok) { toast(d.mensagem || 'Salvo!'); setTimeout(()=>location.reload(),1100); }
    else toast(d.erro||'Erro ao salvar.','erro');
  } catch { toast('Erro de conexão.','erro'); }
  finally { btn.disabled=false; btn.innerHTML='<i class="fa fa-floppy-disk"></i> Salvar'; }
}

async function toggleAtivo(id,atual){
  if(!confirm(atual?'Desativar este item?':'Publicar este item?')) return;
  const r=await fetch('livros.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`acao=toggle_ativo&id=${id}`});
  const d=await r.json();
  if(d.ok){toast(d.novo?'Publicado.':'Despublicado.');setTimeout(()=>location.reload(),1000);}
  else toast(d.erro||'Erro.','erro');
}

async function deletarItem(id,titulo){
  if(!confirm(`Deletar "${titulo}" permanentemente? Esta ação não pode ser desfeita.`)) return;
  const r=await fetch('livros.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`acao=deletar&id=${id}`});
  const d=await r.json();
  if(d.ok){toast('Item deletado.');setTimeout(()=>location.reload(),1000);}
  else toast(d.erro||'Erro.','erro');
}
</script>
<?= $ADMIN_FOOTER_HTML ?>
</main></body></html>
