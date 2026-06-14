<?php
/* ================================================================
   admin/erros.php — Erros ortográficos reportados no Leitor
   ================================================================ */
ini_set('display_errors', '1');
error_reporting(E_ALL);

/* ── AJAX POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ini_set('display_errors', '0');
    ob_start();
    session_name('rd_admin_sess');
    session_start();
    ob_end_clean();
    if (empty($_SESSION['admin_id'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>false,'erro'=>'Não autenticado.']);
        exit;
    }
    require_once __DIR__ . '/../backend/config.php';
    header('Content-Type: application/json; charset=utf-8');
    try {
        $pdo  = db();
        $acao = $_POST['acao'] ?? '';
        $id   = (int)($_POST['id'] ?? 0);

        if ($acao === 'resolver') {
            $pdo->prepare("UPDATE leitor_erros SET resolvido=1 WHERE id=?")->execute([$id]);
            echo json_encode(['ok'=>true,'mensagem'=>'Marcado como resolvido.']);
            exit;
        }
        if ($acao === 'reabrir') {
            $pdo->prepare("UPDATE leitor_erros SET resolvido=0 WHERE id=?")->execute([$id]);
            echo json_encode(['ok'=>true,'mensagem'=>'Reaberto.']);
            exit;
        }
        if ($acao === 'deletar') {
            $pdo->prepare("DELETE FROM leitor_erros WHERE id=?")->execute([$id]);
            echo json_encode(['ok'=>true,'mensagem'=>'Registro removido.']);
            exit;
        }
        echo json_encode(['ok'=>false,'erro'=>'Ação desconhecida.']);
    } catch (Throwable $e) {
        echo json_encode(['ok'=>false,'erro'=>$e->getMessage()]);
    }
    exit;
}

/* ── HTML ── */
$ADMIN_PAGE = 'erros';
require_once __DIR__ . '/_admin.php';

/* ── Filtros ── */
$filtroStatus = $_GET['status'] ?? 'pendente';
$filtroLivro  = trim($_GET['livro'] ?? '');
$pagina       = max(1, (int)($_GET['p'] ?? 1));
$porPagina    = 25;
$offset       = ($pagina - 1) * $porPagina;

/* ── Query ── */
$where  = ['1=1'];
$params = [];

if ($filtroStatus === 'pendente') {
    $where[] = 'e.resolvido = 0';
} elseif ($filtroStatus === 'resolvido') {
    $where[] = 'e.resolvido = 1';
}
if ($filtroLivro !== '') {
    $where[] = 'e.livro_slug LIKE ?';
    $params[] = '%' . $filtroLivro . '%';
}

$whereSql = implode(' AND ', $where);

$stCount = $pdo->prepare("SELECT COUNT(*) FROM leitor_erros e WHERE $whereSql");
$stCount->execute($params);
$total        = (int)$stCount->fetchColumn();
$totalPaginas = max(1, (int)ceil($total / $porPagina));

$stErros = $pdo->prepare(
    "SELECT e.id, e.livro_slug, e.cfi, e.trecho, e.descricao, e.resolvido, e.criado_em,
            u.nome AS usuario_nome, u.email AS usuario_email
     FROM leitor_erros e
     LEFT JOIN usuarios u ON u.id = e.usuario_id
     WHERE $whereSql
     ORDER BY e.criado_em DESC
     LIMIT " . $porPagina . " OFFSET " . $offset
);
$stErros->execute($params);
$erros = $stErros->fetchAll();

/* ── Contagens para badges ── */
$stP = $pdo->query("SELECT COUNT(*) FROM leitor_erros WHERE resolvido=0");
$nPendentes  = (int)$stP->fetchColumn();
$stR = $pdo->query("SELECT COUNT(*) FROM leitor_erros WHERE resolvido=1");
$nResolvidos = (int)$stR->fetchColumn();

/* ── Lista de livros para filtro ── */
$stL   = $pdo->query("SELECT DISTINCT livro_slug FROM leitor_erros ORDER BY livro_slug");
$livros = $stL->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="page-header">
  <h1 class="page-titulo"><i class="fa fa-spell-check" style="font-size:1rem;margin-right:.5rem;opacity:.6"></i>Erros Reportados no Leitor</h1>
  <p class="page-sub">Erros ortográficos e de digitação apontados pelos leitores.</p>
</div>

<!-- Stats -->
<div class="stats-grade" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:1.25rem">
  <div class="stat-card">
    <div class="stat-icone"><i class="fa fa-triangle-exclamation"></i></div>
    <div>
      <div class="stat-valor"><?= $nPendentes ?></div>
      <div class="stat-label">Pendentes</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icone" style="color:#4CAF50"><i class="fa fa-circle-check"></i></div>
    <div>
      <div class="stat-valor"><?= $nResolvidos ?></div>
      <div class="stat-label">Resolvidos</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icone"><i class="fa fa-book"></i></div>
    <div>
      <div class="stat-valor"><?= count($livros) ?></div>
      <div class="stat-label">Livros afetados</div>
    </div>
  </div>
</div>

<!-- Tabela -->
<div class="secao">
  <div class="secao-header">
    <div class="secao-titulo"><i class="fa fa-list"></i> Lista de Erros</div>
    <div class="secao-acoes">
      <form method="get" class="filtros" style="margin:0">
        <select name="status" onchange="this.form.submit()">
          <option value="todos"    <?= $filtroStatus==='todos'    ? 'selected':'' ?>>Todos</option>
          <option value="pendente" <?= $filtroStatus==='pendente' ? 'selected':'' ?>>Pendentes</option>
          <option value="resolvido"<?= $filtroStatus==='resolvido'? 'selected':'' ?>>Resolvidos</option>
        </select>
        <?php if ($livros): ?>
        <select name="livro" onchange="this.form.submit()">
          <option value="">Todos os livros</option>
          <?php foreach ($livros as $l): ?>
            <option value="<?= adm_esc($l) ?>" <?= $filtroLivro===$l ? 'selected':'' ?>><?= adm_esc($l) ?></option>
          <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <input type="hidden" name="p" value="1">
      </form>
    </div>
  </div>

  <?php if (!$erros): ?>
    <div class="estado-vazio">
      <i class="fa fa-spell-check"></i>
      <p><?= $filtroStatus==='pendente' ? 'Nenhum erro pendente. Tudo em ordem!' : 'Nenhum registro encontrado.' ?></p>
    </div>
  <?php else: ?>
  <div style="overflow-x:auto">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Livro</th>
          <th>Leitor</th>
          <th>Trecho / Descrição</th>
          <th>Data</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="tabelaErros">
        <?php foreach ($erros as $e): ?>
        <tr id="row-<?= $e['id'] ?>">
          <td style="color:var(--texto-3);font-size:.75rem"><?= $e['id'] ?></td>
          <td>
            <a href="../leitor/?livro=<?= urlencode($e['livro_slug']) ?>" target="_blank"
               style="color:var(--ouro);text-decoration:none;font-size:.8rem">
              <?= adm_esc($e['livro_slug']) ?> <i class="fa fa-arrow-up-right-from-square" style="font-size:.6rem;opacity:.6"></i>
            </a>
          </td>
          <td>
            <div class="td-nome"><?= adm_esc($e['usuario_nome'] ?? '—') ?></div>
            <div class="td-sub"><?= adm_esc($e['usuario_email'] ?? '') ?></div>
          </td>
          <td style="max-width:320px">
            <?php if ($e['trecho']): ?>
              <div style="background:rgba(184,134,11,.08);border-left:3px solid var(--ouro);padding:.3rem .6rem;border-radius:3px;font-size:.78rem;color:var(--texto-2);font-style:italic;margin-bottom:.3rem;word-break:break-word">
                "<?= adm_esc(mb_substr($e['trecho'], 0, 120)) ?><?= mb_strlen($e['trecho'])>120 ? '…' : '' ?>"
              </div>
            <?php endif; ?>
            <?php if ($e['descricao']): ?>
              <div style="font-size:.78rem;color:var(--texto-3);word-break:break-word"><?= adm_esc($e['descricao']) ?></div>
            <?php endif; ?>
            <?php if (!$e['trecho'] && !$e['descricao']): ?>
              <span style="color:var(--texto-3);font-size:.75rem;font-style:italic">Sem detalhes</span>
            <?php endif; ?>
          </td>
          <td style="white-space:nowrap;font-size:.78rem;color:var(--texto-3)"><?= adm_data($e['criado_em'], 'd/m/Y H:i') ?></td>
          <td>
            <?php if ($e['resolvido']): ?>
              <span class="badge badge-verde">Resolvido</span>
            <?php else: ?>
              <span class="badge badge-amarelo">Pendente</span>
            <?php endif; ?>
          </td>
          <td style="white-space:nowrap">
            <?php if ($e['resolvido']): ?>
              <button class="btn btn-ghost btn-sm" onclick="acao(<?= $e['id'] ?>, 'reabrir')">
                <i class="fa fa-rotate-left"></i> Reabrir
              </button>
            <?php else: ?>
              <button class="btn btn-primario btn-sm" onclick="acao(<?= $e['id'] ?>, 'resolver')">
                <i class="fa fa-check"></i> Resolver
              </button>
            <?php endif; ?>
            <button class="btn btn-danger btn-sm" onclick="acao(<?= $e['id'] ?>, 'deletar')" style="margin-left:.25rem">
              <i class="fa fa-trash"></i>
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Paginação -->
  <?php if ($totalPaginas > 1): ?>
  <div class="paginacao">
    <span class="pag-info"><?= $total ?> registro(s) · Página <?= $pagina ?> de <?= $totalPaginas ?></span>
    <div class="pag-btns">
      <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
        <a href="?status=<?= urlencode($filtroStatus) ?>&livro=<?= urlencode($filtroLivro) ?>&p=<?= $i ?>"
           class="pag-btn <?= $i === $pagina ? 'ativo' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<script>
async function acao(id, tipo) {
  if (tipo === 'deletar' && !confirm('Remover este registro permanentemente?')) return;
  const fd = new FormData();
  fd.append('acao', tipo);
  fd.append('id', id);
  const r = await fetch('erros.php', { method:'POST', body:fd });
  const j = await r.json();
  if (j.ok) {
    const row = document.getElementById('row-' + id);
    if (tipo === 'deletar') {
      row?.remove();
    } else {
      // Reload para atualizar badges e status
      location.reload();
    }
    toast(j.mensagem);
  } else {
    toast(j.erro || 'Erro.', 'erro');
  }
}
</script>

<?= $ADMIN_FOOTER_HTML ?>
