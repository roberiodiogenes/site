<?php
$ADMIN_PAGE = 'assinaturas';
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

    if ($acao === 'cancelar' && $id) {
        $pdo->prepare("UPDATE assinaturas SET status='cancelada' WHERE id=?")->execute([$id]);
        echo json_encode(['ok'=>true]); exit;
    }
    if ($acao === 'criar' ) {
        $uid     = (int)($_POST['usuario_id'] ?? 0);
        $planoId = (int)($_POST['plano_id']   ?? 0);
        if (!$uid || !$planoId) { echo json_encode(['ok'=>false,'erro'=>'Dados incompletos']); exit; }
        $dur = (int)$pdo->prepare("SELECT duracao_dias FROM planos WHERE id=?")->execute([$planoId]) ? (function() use ($pdo,$planoId){ $s=$pdo->prepare("SELECT duracao_dias FROM planos WHERE id=?"); $s->execute([$planoId]); return (int)$s->fetchColumn(); })() : 30;
        $sDur = $pdo->prepare("SELECT duracao_dias FROM planos WHERE id=?"); $sDur->execute([$planoId]); $dur=(int)$sDur->fetchColumn();
        try {
            $pdo->prepare("INSERT INTO assinaturas (usuario_id,plano_id,status,inicio_em,expira_em,gateway)
                VALUES (?,?,'ativa',NOW(),DATE_ADD(NOW(),INTERVAL ? DAY),'manual')")->execute([$uid,$planoId,$dur]);
            echo json_encode(['ok'=>true]); exit;
        } catch(Exception $e){ echo json_encode(['ok'=>false,'erro'=>$e->getMessage()]); exit; }
    }
    echo json_encode(['ok'=>false,'erro'=>'Ação desconhecida']); exit;
}

/* ── Query ── */
$where=[]; $params=[];
if ($busca) { $where[]="(u.nome LIKE ? OR u.email LIKE ?)"; $params[]="%$busca%"; $params[]="%$busca%"; }
$statusMap=['ativas'=>"a.status='ativa' AND a.expira_em>NOW()",'expiradas'=>"a.status='expirada' OR (a.status='ativa' AND a.expira_em<=NOW())",'canceladas'=>"a.status='cancelada'",'pendentes'=>"a.status='pendente'"];
if (isset($statusMap[$filtro])) $where[]=$statusMap[$filtro];
$wsql = $where ? 'WHERE '.implode(' AND ',$where) : '';

$stC=$pdo->prepare("SELECT COUNT(*) FROM assinaturas a JOIN usuarios u ON u.id=a.usuario_id $wsql");
$stC->execute($params); $total=(int)$stC->fetchColumn();
$totalPags=max(1,(int)ceil($total/$porPag));

$stL=$pdo->prepare("SELECT a.id,a.status,a.inicio_em,a.expira_em,a.gateway,a.ref_externa,
    DATEDIFF(a.expira_em,NOW()) AS dias_rest,
    u.id AS uid,u.nome,u.email,
    p.nome AS plano_nome,p.preco
    FROM assinaturas a
    JOIN usuarios u ON u.id=a.usuario_id
    JOIN planos   p ON p.id=a.plano_id
    $wsql ORDER BY a.criado_em DESC LIMIT $porPag OFFSET $offset");
$stL->execute($params); $lista=$stL->fetchAll();

$planos=$pdo->query("SELECT id,nome,preco,duracao_dias FROM planos WHERE ativo=1")->fetchAll();
$sAtivas=$pdo->query("SELECT COUNT(*) FROM assinaturas WHERE status='ativa' AND expira_em>NOW()")->fetchColumn();
$sPend  =$pdo->query("SELECT COUNT(*) FROM assinaturas WHERE status='pendente'")->fetchColumn();
$sCanc  =$pdo->query("SELECT COUNT(*) FROM assinaturas WHERE status='cancelada'")->fetchColumn();
$sRec   =$pdo->query("SELECT COALESCE(SUM(p.preco),0) FROM assinaturas a JOIN planos p ON p.id=a.plano_id WHERE a.status='ativa' AND MONTH(a.inicio_em)=MONTH(NOW()) AND YEAR(a.inicio_em)=YEAR(NOW())")->fetchColumn();
?>
<div class="page-header">
  <h1 class="page-titulo">Assinaturas</h1>
  <p class="page-sub"><?= $total ?> registros · filtro: <?= adm_esc($filtro) ?></p>
</div>

<div class="stats-grade" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr))">
  <div class="stat-card"><i class="fa fa-crown stat-icone"></i><div><div class="stat-valor"><?= $sAtivas ?></div><div class="stat-label">Ativas agora</div></div></div>
  <div class="stat-card"><i class="fa fa-clock stat-icone"></i><div><div class="stat-valor"><?= $sPend ?></div><div class="stat-label">Pendentes</div></div></div>
  <div class="stat-card"><i class="fa fa-ban stat-icone"></i><div><div class="stat-valor"><?= $sCanc ?></div><div class="stat-label">Canceladas</div></div></div>
  <div class="stat-card"><i class="fa fa-dollar-sign stat-icone"></i><div><div class="stat-valor">R$ <?= number_format((float)$sRec,0,',','.') ?></div><div class="stat-label">Receita este mês</div></div></div>
</div>

<div class="secao">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-crown"></i> Lista</span>
    <div class="secao-acoes">
      <form method="get" class="filtros">
        <input type="search" name="q" placeholder="Buscar…" value="<?= adm_esc($busca) ?>">
        <select name="status">
          <option value="todas"    <?= $filtro==='todas'    ?'selected':'' ?>>Todas</option>
          <option value="ativas"   <?= $filtro==='ativas'   ?'selected':'' ?>>Ativas</option>
          <option value="expiradas"<?= $filtro==='expiradas'?'selected':'' ?>>Expiradas</option>
          <option value="canceladas"<?=$filtro==='canceladas'?'selected':''?>>Canceladas</option>
          <option value="pendentes"<?= $filtro==='pendentes'?'selected':'' ?>>Pendentes</option>
        </select>
        <button type="submit" class="btn btn-ghost btn-sm"><i class="fa fa-search"></i></button>
      </form>
      <button class="btn btn-primario btn-sm" onclick="abrirModal('modalAssin')">
        <i class="fa fa-plus"></i> Nova assinatura
      </button>
    </div>
  </div>
  <?php if (!$lista): ?>
    <div class="estado-vazio"><i class="fa fa-crown"></i><p>Nenhuma assinatura encontrada.</p></div>
  <?php else: ?>
  <table>
    <thead><tr><th>Assinante</th><th>Plano</th><th>Início</th><th>Vencimento</th><th>Dias rest.</th><th>Status</th><th>Gateway</th><th>Ações</th></tr></thead>
    <tbody>
    <?php foreach ($lista as $a):
      $venc7 = $a['status']==='ativa' && $a['dias_rest']>=0 && $a['dias_rest']<=7;
    ?>
    <tr>
      <td><div class="td-nome"><?= adm_esc($a['nome']) ?></div><div class="td-sub"><?= adm_esc($a['email']) ?></div></td>
      <td><span class="badge badge-amarelo"><?= adm_esc($a['plano_nome']) ?></span></td>
      <td style="font-size:.75rem;white-space:nowrap"><?= adm_data($a['inicio_em']) ?></td>
      <td style="font-size:.75rem;white-space:nowrap"><?= adm_data($a['expira_em']) ?></td>
      <td style="text-align:center">
        <?php if ($a['status']==='ativa'): ?>
          <?php if ($a['dias_rest'] < 0): ?>
            <span class="badge badge-vermelho">Expirada</span>
          <?php elseif ($venc7): ?>
            <span class="badge badge-vermelho"><?= $a['dias_rest'] ?>d</span>
          <?php else: ?>
            <span style="font-size:.78rem"><?= $a['dias_rest'] ?>d</span>
          <?php endif; ?>
        <?php else: ?>—<?php endif; ?>
      </td>
      <td><?= adm_badge($a['status']) ?></td>
      <td style="font-size:.72rem;color:var(--texto-3)"><?= adm_esc($a['gateway'] ?? '—') ?></td>
      <td>
        <?php if ($a['status']==='ativa'): ?>
        <button class="btn btn-sm btn-danger" onclick="cancelarAssin(<?= $a['id'] ?>)" title="Cancelar assinatura">
          <i class="fa fa-ban"></i>
        </button>
        <?php endif; ?>
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
      <?php for($i=max(1,$pagina-2);$i<=min($totalPags,$pagina+2);$i++): ?>
        <a href="?p=<?=$i?>&q=<?=urlencode($busca)?>&status=<?=$filtro?>" class="pag-btn <?=$i===$pagina?'ativo':''?>"><?=$i?></a>
      <?php endfor; ?>
      <?php if ($pagina<$totalPags): ?><a href="?p=<?=$pagina+1?>&q=<?=urlencode($busca)?>&status=<?=$filtro?>" class="pag-btn"><i class="fa fa-chevron-right"></i></a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Modal nova assinatura -->
<div class="modal-overlay" id="modalAssin">
  <div class="modal-box">
    <h2 class="modal-titulo"><i class="fa fa-crown"></i> Nova assinatura manual</h2>
    <div class="modal-campo">
      <label>ID do usuário</label>
      <input type="number" id="assinUid" placeholder="Ex: 42" min="1">
    </div>
    <div class="modal-campo">
      <label>Plano</label>
      <select id="assinPlano">
        <?php foreach ($planos as $pl): ?>
          <option value="<?= $pl['id'] ?>"><?= adm_esc($pl['nome']) ?> — R$ <?= number_format((float)$pl['preco'],2,',','.') ?> / <?= $pl['duracao_dias'] ?> dias</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="modal-btns">
      <button class="btn btn-ghost" onclick="fecharModal('modalAssin')">Cancelar</button>
      <button class="btn btn-primario" onclick="criarAssin()"><i class="fa fa-check"></i> Criar</button>
    </div>
  </div>
</div>

<script>
async function cancelarAssin(id){
  if(!confirm('Cancelar esta assinatura?')) return;
  const r=await fetch('assinaturas.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`acao=cancelar&id=${id}`});
  const d=await r.json();
  if(d.ok){toast('Assinatura cancelada.');setTimeout(()=>location.reload(),1200);}
  else toast(d.erro||'Erro.','erro');
}
async function criarAssin(){
  const uid=document.getElementById('assinUid').value;
  const plano=document.getElementById('assinPlano').value;
  if(!uid){toast('Informe o ID do usuário.','erro');return;}
  const r=await fetch('assinaturas.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`acao=criar&usuario_id=${uid}&plano_id=${plano}`});
  const d=await r.json();
  fecharModal('modalAssin');
  if(d.ok){toast('Assinatura criada!');setTimeout(()=>location.reload(),1200);}
  else toast(d.erro||'Erro.','erro');
}
</script>
<?= $ADMIN_FOOTER_HTML ?>
</main></body></html>
