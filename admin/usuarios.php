<?php
$ADMIN_PAGE = 'usuarios';
require_once __DIR__ . '/_admin.php';

$pagina = max(1,(int)($_GET['p'] ?? 1));
$busca  = trim($_GET['q'] ?? '');
$filtro = $_GET['status'] ?? 'todos';
$porPag = 20;
$offset = ($pagina - 1) * $porPag;

/* ── Ação POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $acao = $_POST['acao'] ?? '';
    $uid  = (int)($_POST['uid'] ?? 0);
    if (!$uid) { echo json_encode(['ok'=>false,'erro'=>'ID inválido']); exit; }

    if ($acao === 'toggle_ativo') {
        $st = $pdo->prepare("SELECT ativo FROM usuarios WHERE id=?");
        $st->execute([$uid]);
        $novo = $st->fetchColumn() ? 0 : 1;
        $pdo->prepare("UPDATE usuarios SET ativo=? WHERE id=?")->execute([$novo,$uid]);
        echo json_encode(['ok'=>true,'novo'=>$novo]);
        exit;
    }
    if ($acao === 'acesso_manual') {
        $slug = trim($_POST['livro_slug'] ?? '');
        if (!$slug) { echo json_encode(['ok'=>false,'erro'=>'Livro não informado']); exit; }
        try {
            $pdo->prepare("INSERT INTO compras (usuario_id,livro_slug,preco_pago,status,gateway)
                VALUES (?,?,'0.00','aprovada','manual')
                ON DUPLICATE KEY UPDATE status='aprovada'")->execute([$uid,$slug]);
            echo json_encode(['ok'=>true]);
        } catch (Exception $e) { echo json_encode(['ok'=>false,'erro'=>$e->getMessage()]); }
        exit;
    }
    echo json_encode(['ok'=>false,'erro'=>'Ação desconhecida']); exit;
}

/* ── Query ── */
$where=[]; $params=[];
if ($busca) { $where[]="(u.nome LIKE ? OR u.email LIKE ?)"; $params[]="%$busca%"; $params[]="%$busca%"; }
if ($filtro==='ativos')   $where[]="u.ativo=1";
if ($filtro==='inativos') $where[]="u.ativo=0";
$wsql = $where ? 'WHERE '.implode(' AND ',$where) : '';

$total = (int)$pdo->prepare("SELECT COUNT(*) FROM usuarios u $wsql")->execute($params) ? (function() use ($pdo,$wsql,$params){ $s=$pdo->prepare("SELECT COUNT(*) FROM usuarios u $wsql"); $s->execute($params); return (int)$s->fetchColumn(); })() : 0;
// repete corretamente:
$stCount = $pdo->prepare("SELECT COUNT(*) FROM usuarios u $wsql");
$stCount->execute($params);
$total     = (int)$stCount->fetchColumn();
$totalPags = max(1,(int)ceil($total/$porPag));

$stList = $pdo->prepare("SELECT u.id,u.nome,u.email,u.ativo,u.foto_url,u.created_at,u.ultimo_login,
    (u.google_id IS NOT NULL) AS tem_google,
    (SELECT COUNT(*) FROM compras WHERE usuario_id=u.id AND status='aprovada') AS n_compras,
    (SELECT COUNT(*) FROM assinaturas WHERE usuario_id=u.id AND status='ativa' AND expira_em>NOW()) AS tem_assin
    FROM usuarios u $wsql ORDER BY u.created_at DESC LIMIT $porPag OFFSET $offset");
$stList->execute($params);
$lista = $stList->fetchAll();

$livros = $pdo->query("SELECT slug,titulo FROM livros WHERE ativo=1 ORDER BY titulo")->fetchAll();

$st = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE ativo=1")->fetchColumn();
$si = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE ativo=0")->fetchColumn();
$sg = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE google_id IS NOT NULL")->fetchColumn();
$s7 = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();
?>
<div class="page-header">
  <h1 class="page-titulo">Usuários</h1>
  <p class="page-sub"><?= $total ?> cadastros no total</p>
</div>

<div class="stats-grade" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr))">
  <div class="stat-card"><i class="fa fa-user-check stat-icone"></i><div><div class="stat-valor"><?= $st ?></div><div class="stat-label">Ativos</div></div></div>
  <div class="stat-card"><i class="fa fa-user-xmark stat-icone"></i><div><div class="stat-valor"><?= $si ?></div><div class="stat-label">Inativos</div></div></div>
  <div class="stat-card"><i class="fa-brands fa-google stat-icone"></i><div><div class="stat-valor"><?= $sg ?></div><div class="stat-label">Via Google</div></div></div>
  <div class="stat-card"><i class="fa fa-calendar-plus stat-icone"></i><div><div class="stat-valor"><?= $s7 ?></div><div class="stat-label">Últimos 7 dias</div></div></div>
</div>

<div class="secao">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-users"></i> Lista</span>
    <form method="get" class="filtros">
      <input type="search" name="q" placeholder="Buscar nome ou e-mail…" value="<?= adm_esc($busca) ?>">
      <select name="status">
        <option value="todos"   <?= $filtro==='todos'   ?'selected':'' ?>>Todos</option>
        <option value="ativos"  <?= $filtro==='ativos'  ?'selected':'' ?>>Ativos</option>
        <option value="inativos"<?= $filtro==='inativos'?'selected':'' ?>>Inativos</option>
      </select>
      <button type="submit" class="btn btn-ghost btn-sm"><i class="fa fa-search"></i></button>
    </form>
  </div>
  <?php if (!$lista): ?>
    <div class="estado-vazio"><i class="fa fa-users"></i><p>Nenhum usuário encontrado.</p></div>
  <?php else: ?>
  <table>
    <thead><tr><th>Usuário</th><th>Cadastro</th><th>Último login</th><th>Compras</th><th>Assinatura</th><th>Status</th><th>Ações</th></tr></thead>
    <tbody>
    <?php foreach ($lista as $u): ?>
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:.55rem">
          <?php if ($u['foto_url']): ?>
            <img src="<?= adm_esc($u['foto_url']) ?>" style="width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0" alt="">
          <?php else: ?>
            <div style="width:28px;height:28px;border-radius:50%;background:var(--fundo-input);display:flex;align-items:center;justify-content:center;color:var(--texto-3);font-size:.7rem;flex-shrink:0"><i class="fa fa-user"></i></div>
          <?php endif; ?>
          <div>
            <div class="td-nome"><?= adm_esc($u['nome']) ?></div>
            <div class="td-sub"><?= adm_esc($u['email']) ?><?= $u['tem_google'] ? ' <span style="color:var(--ouro);font-size:.58rem">● Google</span>' : '' ?></div>
          </div>
        </div>
      </td>
      <td style="font-size:.75rem;white-space:nowrap"><?= adm_data($u['created_at']) ?></td>
      <td style="font-size:.75rem;white-space:nowrap"><?= $u['ultimo_login'] ? adm_data($u['ultimo_login'],'d/m/Y H:i') : '<span style="opacity:.35">—</span>' ?></td>
      <td style="text-align:center"><?= $u['n_compras'] ?: '—' ?></td>
      <td style="text-align:center"><?= $u['tem_assin'] ? '<span class="badge badge-verde">Ativa</span>' : '—' ?></td>
      <td><?= adm_badge($u['ativo'] ? 'ativo' : 'inativo') ?></td>
      <td>
        <div style="display:flex;gap:.3rem">
          <button class="btn btn-sm btn-ghost" onclick="toggleAtivo(<?= $u['id'] ?>,<?= $u['ativo'] ?>)" title="<?= $u['ativo']?'Desativar':'Ativar' ?>">
            <i class="fa <?= $u['ativo']?'fa-user-slash':'fa-user-check' ?>"></i>
          </button>
          <button class="btn btn-sm btn-ghost" onclick="abrirAcesso(<?= $u['id'] ?>,'<?= adm_esc(addslashes($u['nome'])) ?>')" title="Acesso manual a livro">
            <i class="fa fa-key"></i>
          </button>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php if ($totalPags > 1): ?>
  <div class="paginacao">
    <span class="pag-info">Pág. <?= $pagina ?>/<?= $totalPags ?> (<?= $total ?> registros)</span>
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

<!-- Modal acesso manual -->
<div class="modal-overlay" id="modalAcesso">
  <div class="modal-box">
    <h2 class="modal-titulo"><i class="fa fa-key"></i> Acesso manual</h2>
    <p style="font-size:.85rem;color:var(--texto-3);margin-bottom:1rem">Concede acesso gratuito a <strong id="nomeAcesso"></strong>.</p>
    <input type="hidden" id="uidAcesso">
    <div class="modal-campo">
      <label>Livro</label>
      <select id="livroAcesso">
        <?php foreach ($livros as $l): ?><option value="<?= adm_esc($l['slug']) ?>"><?= adm_esc($l['titulo']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="modal-btns">
      <button class="btn btn-ghost" onclick="fecharModal('modalAcesso')">Cancelar</button>
      <button class="btn btn-primario" onclick="salvarAcesso()"><i class="fa fa-check"></i> Conceder</button>
    </div>
  </div>
</div>

<script>
async function toggleAtivo(uid,atual){
  if(!confirm(atual?'Desativar esta conta?':'Reativar esta conta?')) return;
  const r=await fetch('usuarios.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`acao=toggle_ativo&uid=${uid}`});
  const d=await r.json();
  if(d.ok){toast(d.novo?'Conta reativada.':'Conta desativada.');setTimeout(()=>location.reload(),1200);}
  else toast(d.erro||'Erro.','erro');
}
function abrirAcesso(uid,nome){
  document.getElementById('uidAcesso').value=uid;
  document.getElementById('nomeAcesso').textContent=nome;
  abrirModal('modalAcesso');
}
async function salvarAcesso(){
  const uid=document.getElementById('uidAcesso').value;
  const livro=document.getElementById('livroAcesso').value;
  const r=await fetch('usuarios.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`acao=acesso_manual&uid=${uid}&livro_slug=${encodeURIComponent(livro)}`});
  const d=await r.json();
  fecharModal('modalAcesso');
  toast(d.ok?'Acesso concedido!':d.erro||'Erro.', d.ok?'ok':'erro');
}
</script>
<?= $ADMIN_FOOTER_HTML ?>
</main></body></html>
