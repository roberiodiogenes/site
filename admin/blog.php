<?php
$ADMIN_PAGE = 'blog';
require_once __DIR__ . '/_admin.php';

$pagina = max(1,(int)($_GET['p'] ?? 1));
$busca  = trim($_GET['q'] ?? '');
$filtro = $_GET['tipo'] ?? 'todos';
$porPag = 25;
$offset = ($pagina-1)*$porPag;

/* ── Ação POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $acao = $_POST['acao'] ?? '';
    $id   = (int)($_POST['id'] ?? 0);

    if ($acao === 'aprovar' && $id) {
        $pdo->prepare("UPDATE comentarios SET aprovado=1 WHERE id=?")->execute([$id]);
        echo json_encode(['ok'=>true]); exit;
    }
    if ($acao === 'reprovar' && $id) {
        $pdo->prepare("UPDATE comentarios SET aprovado=0 WHERE id=?")->execute([$id]);
        echo json_encode(['ok'=>true]); exit;
    }
    if ($acao === 'deletar' && $id) {
        $pdo->prepare("DELETE FROM comentarios WHERE id=?")->execute([$id]);
        echo json_encode(['ok'=>true]); exit;
    }
    echo json_encode(['ok'=>false,'erro'=>'Ação desconhecida']); exit;
}

/* ── Query de comentários ── */
$where=[]; $params=[];
if ($busca) {
    $where[]="(c.texto LIKE ? OR u.nome LIKE ? OR c.referencia LIKE ?)";
    $params[]="%$busca%"; $params[]="%$busca%"; $params[]="%$busca%";
}
if ($filtro==='blog')    { $where[]="c.tipo='blog'"; }
if ($filtro==='livros')  { $where[]="c.tipo='livro'"; }
if ($filtro==='aguard')  { $where[]="c.aprovado=0"; }
if ($filtro==='aprov')   { $where[]="c.aprovado=1"; }
$wsql=$where?'WHERE '.implode(' AND ',$where):'';

$stC=$pdo->prepare("SELECT COUNT(*) FROM comentarios c LEFT JOIN usuarios u ON u.id=c.usuario_id $wsql");
$stC->execute($params); $total=(int)$stC->fetchColumn();
$totalPags=max(1,(int)ceil($total/$porPag));

$stL=$pdo->prepare("SELECT c.id,c.referencia,c.tipo,c.texto,c.aprovado,c.criado_em,c.nome AS nome_anon,
    u.id AS uid,u.nome AS nome_user,u.email,u.foto_url
    FROM comentarios c
    LEFT JOIN usuarios u ON u.id=c.usuario_id
    $wsql ORDER BY c.criado_em DESC LIMIT $porPag OFFSET $offset");
$stL->execute($params); $lista=$stL->fetchAll();

$sTot   =$pdo->query("SELECT COUNT(*) FROM comentarios")->fetchColumn();
$sAguard=$pdo->query("SELECT COUNT(*) FROM comentarios WHERE aprovado=0")->fetchColumn();
$sAprov =$pdo->query("SELECT COUNT(*) FROM comentarios WHERE aprovado=1")->fetchColumn();
$sBlog  =$pdo->query("SELECT COUNT(*) FROM comentarios WHERE tipo='blog'")->fetchColumn();
?>
<div class="page-header">
  <h1 class="page-titulo">Blog & Comentários</h1>
  <p class="page-sub"><?= $total ?> registros · filtro: <?= adm_esc($filtro) ?></p>
</div>

<div class="stats-grade" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr))">
  <div class="stat-card"><i class="fa fa-comments stat-icone"></i><div><div class="stat-valor"><?= $sTot ?></div><div class="stat-label">Total coment.</div></div></div>
  <div class="stat-card" style="<?= (int)$sAguard>0?'border-color:var(--vermelho)':'' ?>">
    <i class="fa fa-clock stat-icone" style="<?= (int)$sAguard>0?'color:var(--vermelho)':'' ?>"></i>
    <div><div class="stat-valor" style="<?= (int)$sAguard>0?'color:var(--vermelho)':'' ?>"><?= $sAguard ?></div><div class="stat-label">Aguardando</div></div>
  </div>
  <div class="stat-card"><i class="fa fa-check-circle stat-icone"></i><div><div class="stat-valor"><?= $sAprov ?></div><div class="stat-label">Aprovados</div></div></div>
  <div class="stat-card"><i class="fa fa-pen-nib stat-icone"></i><div><div class="stat-valor"><?= $sBlog ?></div><div class="stat-label">Do blog</div></div></div>
</div>

<?php if ((int)$sAguard > 0): ?>
<div style="background:rgba(183,28,28,.08);border:1px solid rgba(183,28,28,.25);border-radius:var(--raio-lg);padding:.75rem 1.1rem;margin-bottom:1rem;display:flex;align-items:center;gap:.75rem;font-size:.85rem">
  <i class="fa fa-triangle-exclamation" style="color:var(--vermelho)"></i>
  <span><?= $sAguard ?> comentário<?= (int)$sAguard>1?'s':'' ?> aguardando moderação.</span>
  <a href="?tipo=aguard" class="btn btn-sm btn-danger" style="margin-left:auto">Moderar agora</a>
</div>
<?php endif; ?>

<div class="secao">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-comments"></i> Comentários</span>
    <form method="get" class="filtros">
      <input type="search" name="q" placeholder="Buscar texto ou autor…" value="<?= adm_esc($busca) ?>">
      <select name="tipo">
        <option value="todos"  <?= $filtro==='todos' ?'selected':'' ?>>Todos</option>
        <option value="aguard" <?= $filtro==='aguard'?'selected':'' ?>>Aguardando</option>
        <option value="aprov"  <?= $filtro==='aprov' ?'selected':'' ?>>Aprovados</option>
        <option value="blog"   <?= $filtro==='blog'  ?'selected':'' ?>>Blog</option>
        <option value="livros" <?= $filtro==='livros'?'selected':'' ?>>Livros</option>
      </select>
      <button type="submit" class="btn btn-ghost btn-sm"><i class="fa fa-search"></i></button>
    </form>
  </div>

  <?php if (!$lista): ?>
    <div class="estado-vazio"><i class="fa fa-comments"></i><p>Nenhum comentário encontrado.</p></div>
  <?php else: ?>
  <table>
    <thead><tr><th>Autor</th><th>Referência</th><th>Comentário</th><th>Data</th><th>Status</th><th>Ações</th></tr></thead>
    <tbody>
    <?php foreach ($lista as $c):
      $nomeExib = $c['nome_user'] ?: ($c['nome_anon'] ?: 'Anônimo');
    ?>
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:.5rem">
          <?php if ($c['foto_url']): ?>
            <img src="<?= adm_esc($c['foto_url']) ?>" style="width:26px;height:26px;border-radius:50%;object-fit:cover;flex-shrink:0" alt="">
          <?php else: ?>
            <div style="width:26px;height:26px;border-radius:50%;background:var(--fundo-input);display:flex;align-items:center;justify-content:center;font-size:.65rem;color:var(--texto-3);flex-shrink:0"><i class="fa fa-user"></i></div>
          <?php endif; ?>
          <div>
            <div style="font-size:.82rem;font-weight:500;color:var(--texto)"><?= adm_esc($nomeExib) ?></div>
            <?php if ($c['email']): ?><div class="td-sub"><?= adm_esc($c['email']) ?></div><?php endif; ?>
          </div>
        </div>
      </td>
      <td>
        <span class="badge <?= $c['tipo']==='blog'?'badge-azul':'badge-amarelo' ?>">
          <?= $c['tipo']==='blog' ? 'Blog' : 'Livro' ?>
        </span>
        <div style="font-size:.7rem;color:var(--texto-3);margin-top:.2rem"><?= adm_esc($c['referencia'] ?? '—') ?></div>
      </td>
      <td style="max-width:320px">
        <div style="font-size:.82rem;color:var(--texto-2);line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical">
          <?= adm_esc($c['texto']) ?>
        </div>
      </td>
      <td style="font-size:.72rem;white-space:nowrap"><?= adm_data($c['criado_em'],'d/m/Y H:i') ?></td>
      <td>
        <?php if ($c['aprovado']): ?>
          <span class="badge badge-verde">Aprovado</span>
        <?php else: ?>
          <span class="badge badge-amarelo">Aguardando</span>
        <?php endif; ?>
      </td>
      <td>
        <div style="display:flex;gap:.3rem;flex-wrap:wrap">
          <?php if (!$c['aprovado']): ?>
          <button class="btn btn-sm btn-primario" onclick="moderar(<?= $c['id'] ?>,'aprovar')" title="Aprovar">
            <i class="fa fa-check"></i>
          </button>
          <?php else: ?>
          <button class="btn btn-sm btn-ghost" onclick="moderar(<?= $c['id'] ?>,'reprovar')" title="Desaprovar">
            <i class="fa fa-eye-slash"></i>
          </button>
          <?php endif; ?>
          <button class="btn btn-sm btn-danger" onclick="moderar(<?= $c['id'] ?>,'deletar')" title="Deletar">
            <i class="fa fa-trash"></i>
          </button>
          <button class="btn btn-sm btn-ghost" onclick="verTexto(`<?= adm_esc(addslashes($c['texto'])) ?>`)" title="Ver texto completo">
            <i class="fa fa-expand"></i>
          </button>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($totalPags > 1): ?>
  <div class="paginacao">
    <span class="pag-info">Pág. <?=$pagina?>/<?=$totalPags?> (<?=$total?> registros)</span>
    <div class="pag-btns">
      <?php if ($pagina>1): ?><a href="?p=<?=$pagina-1?>&q=<?=urlencode($busca)?>&tipo=<?=$filtro?>" class="pag-btn"><i class="fa fa-chevron-left"></i></a><?php endif; ?>
      <?php for($i=max(1,$pagina-2);$i<=min($totalPags,$pagina+2);$i++): ?>
        <a href="?p=<?=$i?>&q=<?=urlencode($busca)?>&tipo=<?=$filtro?>" class="pag-btn <?=$i===$pagina?'ativo':''?>"><?=$i?></a>
      <?php endfor; ?>
      <?php if ($pagina<$totalPags): ?><a href="?p=<?=$pagina+1?>&q=<?=urlencode($busca)?>&tipo=<?=$filtro?>" class="pag-btn"><i class="fa fa-chevron-right"></i></a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Modal texto completo -->
<div class="modal-overlay" id="modalTexto">
  <div class="modal-box" style="max-width:560px">
    <h2 class="modal-titulo"><i class="fa fa-comment"></i> Comentário completo</h2>
    <div id="textoCompleto" style="font-size:.9rem;line-height:1.7;color:var(--texto-2);white-space:pre-wrap;background:var(--fundo-input);padding:1rem;border-radius:var(--raio);max-height:300px;overflow-y:auto"></div>
    <div class="modal-btns">
      <button class="btn btn-ghost" onclick="fecharModal('modalTexto')">Fechar</button>
    </div>
  </div>
</div>

<script>
const MSGS_MOD = {aprovar:'Aprovar este comentário?',reprovar:'Desaprovar e ocultar?',deletar:'Deletar permanentemente?'};
async function moderar(id,acao){
  if(!confirm(MSGS_MOD[acao]||'Confirmar?')) return;
  const r=await fetch('blog.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`acao=${acao}&id=${id}`});
  const d=await r.json();
  if(d.ok){toast(acao==='deletar'?'Comentário deletado.':acao==='aprovar'?'Aprovado!':'Desaprovado.');setTimeout(()=>location.reload(),1100);}
  else toast(d.erro||'Erro.','erro');
}
function verTexto(txt){
  document.getElementById('textoCompleto').textContent=txt;
  abrirModal('modalTexto');
}
</script>
<?= $ADMIN_FOOTER_HTML ?>
</main></body></html>
