<?php
$ADMIN_PAGE = 'compras';
require_once __DIR__ . '/_admin.php';

$pagina = max(1,(int)($_GET['p'] ?? 1));
$busca  = trim($_GET['q'] ?? '');
$filtro = $_GET['status'] ?? 'todas';
$porPag = 20;
$offset = ($pagina-1)*$porPag;

/* ── Ação POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $acao = $_POST['acao'] ?? '';
    $id   = (int)($_POST['id'] ?? 0);
    if ($acao === 'aprovar' && $id) {
        $pdo->prepare("UPDATE compras SET status='aprovada' WHERE id=?")->execute([$id]);
        echo json_encode(['ok'=>true]); exit;
    }
    if ($acao === 'reembolsar' && $id) {
        $pdo->prepare("UPDATE compras SET status='reembolsada' WHERE id=?")->execute([$id]);
        echo json_encode(['ok'=>true]); exit;
    }
    if ($acao === 'cancelar' && $id) {
        $pdo->prepare("UPDATE compras SET status='cancelada' WHERE id=?")->execute([$id]);
        echo json_encode(['ok'=>true]); exit;
    }
    echo json_encode(['ok'=>false,'erro'=>'Ação desconhecida']); exit;
}

/* ── Query ── */
$where=[]; $params=[];
if ($busca) { $where[]="(u.nome LIKE ? OR u.email LIKE ? OR c.livro_slug LIKE ?)"; $params[]="%$busca%"; $params[]="%$busca%"; $params[]="%$busca%"; }
if ($filtro!=='todas') { $where[]="c.status=?"; $params[]=$filtro; }
$wsql=$where?'WHERE '.implode(' AND ',$where):'';

$stC=$pdo->prepare("SELECT COUNT(*) FROM compras c JOIN usuarios u ON u.id=c.usuario_id $wsql");
$stC->execute($params); $total=(int)$stC->fetchColumn();
$totalPags=max(1,(int)ceil($total/$porPag));

$stL=$pdo->prepare("SELECT c.id,c.livro_slug,c.preco_pago,c.status,c.comprado_em,c.gateway,c.ref_externa,
    u.id AS uid,u.nome,u.email,l.titulo
    FROM compras c
    JOIN usuarios u ON u.id=c.usuario_id
    LEFT JOIN livros l ON l.slug=c.livro_slug
    $wsql ORDER BY c.comprado_em DESC LIMIT $porPag OFFSET $offset");
$stL->execute($params); $lista=$stL->fetchAll();

$sAprov=$pdo->query("SELECT COUNT(*) FROM compras WHERE status='aprovada'")->fetchColumn();
$sPend =$pdo->query("SELECT COUNT(*) FROM compras WHERE status='pendente'")->fetchColumn();
$sRec  =$pdo->query("SELECT COALESCE(SUM(preco_pago),0) FROM compras WHERE status='aprovada' AND MONTH(comprado_em)=MONTH(NOW()) AND YEAR(comprado_em)=YEAR(NOW())")->fetchColumn();
$sTotal=$pdo->query("SELECT COALESCE(SUM(preco_pago),0) FROM compras WHERE status='aprovada'")->fetchColumn();
?>
<div class="page-header">
  <h1 class="page-titulo">Compras</h1>
  <p class="page-sub"><?= $total ?> registros</p>
</div>

<div class="stats-grade" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr))">
  <div class="stat-card"><i class="fa fa-check-circle stat-icone"></i><div><div class="stat-valor"><?= $sAprov ?></div><div class="stat-label">Aprovadas</div></div></div>
  <div class="stat-card"><i class="fa fa-clock stat-icone"></i><div><div class="stat-valor"><?= $sPend ?></div><div class="stat-label">Pendentes</div></div></div>
  <div class="stat-card"><i class="fa fa-calendar stat-icone"></i><div><div class="stat-valor">R$ <?= number_format((float)$sRec,0,',','.') ?></div><div class="stat-label">Este mês</div></div></div>
  <div class="stat-card"><i class="fa fa-dollar-sign stat-icone"></i><div><div class="stat-valor">R$ <?= number_format((float)$sTotal,0,',','.') ?></div><div class="stat-label">Total geral</div></div></div>
</div>

<div class="secao">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-cart-shopping"></i> Lista</span>
    <form method="get" class="filtros">
      <input type="search" name="q" placeholder="Buscar cliente ou livro…" value="<?= adm_esc($busca) ?>">
      <select name="status">
        <option value="todas"      <?= $filtro==='todas'      ?'selected':'' ?>>Todas</option>
        <option value="aprovada"   <?= $filtro==='aprovada'   ?'selected':'' ?>>Aprovadas</option>
        <option value="pendente"   <?= $filtro==='pendente'   ?'selected':'' ?>>Pendentes</option>
        <option value="cancelada"  <?= $filtro==='cancelada'  ?'selected':'' ?>>Canceladas</option>
        <option value="reembolsada"<?= $filtro==='reembolsada'?'selected':'' ?>>Reembolsadas</option>
      </select>
      <button type="submit" class="btn btn-ghost btn-sm"><i class="fa fa-search"></i></button>
    </form>
  </div>
  <?php if (!$lista): ?>
    <div class="estado-vazio"><i class="fa fa-cart-shopping"></i><p>Nenhuma compra encontrada.</p></div>
  <?php else: ?>
  <table>
    <thead><tr><th>Cliente</th><th>Livro</th><th>Valor</th><th>Status</th><th>Data</th><th>Gateway</th><th>Ações</th></tr></thead>
    <tbody>
    <?php foreach ($lista as $c): ?>
    <tr>
      <td><div class="td-nome"><?= adm_esc($c['nome']) ?></div><div class="td-sub"><?= adm_esc($c['email']) ?></div></td>
      <td style="font-size:.82rem"><?= adm_esc($c['titulo'] ?? $c['livro_slug']) ?></td>
      <td style="font-size:.82rem;white-space:nowrap">R$ <?= number_format((float)$c['preco_pago'],2,',','.') ?></td>
      <td><?= adm_badge($c['status']) ?></td>
      <td style="font-size:.72rem;white-space:nowrap"><?= adm_data($c['comprado_em'],'d/m/Y H:i') ?></td>
      <td style="font-size:.72rem;color:var(--texto-3)"><?= adm_esc($c['gateway'] ?? '—') ?></td>
      <td>
        <div style="display:flex;gap:.3rem;flex-wrap:wrap">
          <?php if ($c['status']==='pendente'): ?>
            <button class="btn btn-sm btn-primario" onclick="acao(<?=$c['id']?>,'aprovar')" title="Aprovar">
              <i class="fa fa-check"></i>
            </button>
          <?php endif; ?>
          <?php if ($c['status']==='aprovada'): ?>
            <button class="btn btn-sm btn-danger" onclick="acao(<?=$c['id']?>,'reembolsar')" title="Reembolsar">
              <i class="fa fa-rotate-left"></i>
            </button>
          <?php endif; ?>
          <?php if (in_array($c['status'],['pendente','aprovada'])): ?>
            <button class="btn btn-sm btn-ghost" onclick="acao(<?=$c['id']?>,'cancelar')" title="Cancelar">
              <i class="fa fa-ban"></i>
            </button>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php if ($totalPags>1): ?>
  <div class="paginacao">
    <span class="pag-info">Pág. <?=$pagina?>/<?=$totalPags?> (<?=$total?> registros)</span>
    <div class="pag-btns">
      <?php if ($pagina>1): ?><a href="?p=<?=$pagina-1?>&q=<?=urlencode($busca)?>&status=<?=$filtro?>" class="pag-btn"><i class="fa fa-chevron-left"></i></a><?php endif; ?>
      <?php for($i=max(1,$pagina-2);$i<=min($totalPags,$pagina+2);$i++): ?><a href="?p=<?=$i?>&q=<?=urlencode($busca)?>&status=<?=$filtro?>" class="pag-btn <?=$i===$pagina?'ativo':''?>"><?=$i?></a><?php endfor; ?>
      <?php if ($pagina<$totalPags): ?><a href="?p=<?=$pagina+1?>&q=<?=urlencode($busca)?>&status=<?=$filtro?>" class="pag-btn"><i class="fa fa-chevron-right"></i></a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<script>
const MSGS={aprovar:'Aprovar esta compra manualmente?',reembolsar:'Registrar reembolso desta compra?',cancelar:'Cancelar esta compra?'};
async function acao(id,tipo){
  if(!confirm(MSGS[tipo]||'Confirmar?')) return;
  const r=await fetch('compras.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`acao=${tipo}&id=${id}`});
  const d=await r.json();
  if(d.ok){toast('Operação realizada com sucesso.');setTimeout(()=>location.reload(),1200);}
  else toast(d.erro||'Erro.','erro');
}
</script>
<?= $ADMIN_FOOTER_HTML ?>
</main></body></html>
