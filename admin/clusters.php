<?php
ob_start();

function rd_slugify_cl(string $t): string {
    $m=['á'=>'a','à'=>'a','â'=>'a','ã'=>'a','é'=>'e','ê'=>'e','í'=>'i',
        'ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n'];
    $t=mb_strtolower(strtr($t,$m),'UTF-8');
    $t=preg_replace('/[^a-z0-9\s-]/','', $t);
    return substr(preg_replace('/[\s-]+/','-',trim($t)),0,160);
}

/* ── POST handler ─────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ini_set('display_errors','0');
    session_name('rd_admin_sess'); session_start();
    if (empty($_SESSION['admin_id'])) {
        ob_end_clean(); header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>false,'erro'=>'Sessão expirada.']); exit;
    }
    require_once __DIR__.'/../backend/config.php';
    $pdo = db();
    ob_end_clean(); header('Content-Type: application/json; charset=utf-8');

    $acao = trim($_POST['acao'] ?? '');

    /* ── criar ── */
    if ($acao === 'criar') {
        $titulo    = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $imagem    = trim($_POST['imagem_url'] ?? '');
        $pilar     = trim($_POST['pilar_slug'] ?? '');
        if (!$titulo) { echo json_encode(['ok'=>false,'erro'=>'Título obrigatório.']); exit; }
        $slug = rd_slugify_cl($titulo);
        $stC = $pdo->prepare("SELECT id FROM clusters WHERE slug=? LIMIT 1"); $stC->execute([$slug]);
        if ($stC->fetchColumn()) $slug .= '-'.substr(md5(uniqid('',true)),0,5);
        try {
            $pdo->prepare("INSERT INTO clusters(slug,titulo,descricao,imagem_url,pilar_slug,ativo) VALUES(?,?,?,?,?,1)")
               ->execute([$slug,$titulo,$descricao?:null,$imagem?:null,$pilar?:null]);
            echo json_encode(['ok'=>true,'msg'=>'Cluster criado!','slug'=>$slug]);
        } catch(PDOException $e) {
            echo json_encode(['ok'=>false,'erro'=>'Banco: '.$e->getMessage()]);
        }
        exit;
    }

    /* ── editar ── */
    if ($acao === 'editar') {
        $id        = (int)($_POST['id'] ?? 0);
        $titulo    = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $imagem    = trim($_POST['imagem_url'] ?? '');
        $pilar     = trim($_POST['pilar_slug'] ?? '');
        if (!$id || !$titulo) { echo json_encode(['ok'=>false,'erro'=>'Dados inválidos.']); exit; }
        try {
            $pdo->prepare("UPDATE clusters SET titulo=?,descricao=?,imagem_url=?,pilar_slug=? WHERE id=?")
               ->execute([$titulo,$descricao?:null,$imagem?:null,$pilar?:null,$id]);
            echo json_encode(['ok'=>true,'msg'=>'Cluster atualizado!']);
        } catch(PDOException $e) {
            echo json_encode(['ok'=>false,'erro'=>'Banco: '.$e->getMessage()]);
        }
        exit;
    }

    /* ── toggle_ativo ── */
    if ($acao === 'toggle_ativo') {
        $id = (int)($_POST['id']??0); $v = (int)($_POST['ativo']??0);
        $pdo->prepare("UPDATE clusters SET ativo=? WHERE id=?")->execute([$v,$id]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    /* ── excluir ── */
    if ($acao === 'excluir') {
        $id = (int)($_POST['id']??0);
        if (!$id) { echo json_encode(['ok'=>false,'erro'=>'ID inválido.']); exit; }
        // Desvincula posts
        $pdo->prepare("UPDATE posts SET cluster_id=NULL WHERE cluster_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM clusters WHERE id=?")->execute([$id]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    echo json_encode(['ok'=>false,'erro'=>'Ação desconhecida.']); exit;
}

/* ── GET — HTML ──────────────────────────────────────────────── */
$ADMIN_PAGE = 'clusters';
require_once __DIR__.'/_admin.php';

$acao = $_GET['acao'] ?? 'listar';
$clEditar = null;
if ($acao === 'editar') {
    $id = (int)($_GET['id'] ?? 0);
    $st = $pdo->prepare("SELECT * FROM clusters WHERE id=? LIMIT 1"); $st->execute([$id]);
    $clEditar = $st->fetch(PDO::FETCH_ASSOC);
    if (!$clEditar) { header('Location: clusters.php'); exit; }
}

// Posts disponíveis (para escolher pilar)
$postsList = [];
try {
    $postsList = $pdo->query("SELECT slug,titulo FROM posts WHERE status='publicado' ORDER BY titulo ASC")
                     ->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e) {}

// Lista clusters
$clusters = [];
try {
    $clusters = $pdo->query(
        "SELECT c.*, (SELECT COUNT(*) FROM posts WHERE cluster_id=c.id) AS total_posts
         FROM clusters c ORDER BY c.id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e) {}
?>

<style>
.sh{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem}
.st{font-family:Georgia,serif;font-size:1.3rem;font-weight:400}.st em{color:var(--ouro);font-style:italic}
.tbl{width:100%;border-collapse:collapse}
.tbl th{text-align:left;padding:.65rem 1rem;font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:var(--ouro);border-bottom:1px solid var(--borda);white-space:nowrap}
.tbl td{padding:.75rem 1rem;border-bottom:1px solid var(--borda);vertical-align:middle;font-size:.85rem;color:var(--texto-2)}
.tbl tr:hover td{background:rgba(255,255,255,.02)}
.ca{display:flex;gap:.3rem}
.ba{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:6px;font-size:.78rem;cursor:pointer;border:none;text-decoration:none;transition:all .15s}
.be{background:rgba(52,152,219,.15);color:#3498db;border:1px solid #2980b9}.be:hover{background:rgba(52,152,219,.3)}
.bd{background:rgba(231,76,60,.12);color:#e74c3c;border:1px solid #c0392b}.bd:hover{background:rgba(231,76,60,.28)}
.bt{background:rgba(39,174,96,.12);color:#2ecc71;border:1px solid #27ae60}.bt:hover{background:rgba(39,174,96,.28)}
.bp{background:rgba(149,165,166,.1);color:#95a5a6;border:1px solid #7f8c8d}.bp:hover{background:rgba(149,165,166,.25)}
.bx{background:rgba(255,165,0,.12);color:#ffa500;border:1px solid #cc8400}.bx:hover{background:rgba(255,165,0,.25)}
.ba:disabled{opacity:.35;cursor:not-allowed}
.bn2{display:inline-flex;align-items:center;gap:.45rem;padding:.5rem 1.25rem;background:var(--ouro);color:#1A0F00;border:none;border-radius:6px;cursor:pointer;text-decoration:none;font-family:var(--fonte-display,system-ui);font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.bn2:hover{opacity:.85}
.bv2{display:inline-flex;align-items:center;gap:.45rem;padding:.6rem 1.25rem;background:transparent;color:var(--texto-3);border:1px solid var(--borda-media);border-radius:6px;cursor:pointer;font-family:var(--fonte-display,system-ui);font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;text-decoration:none;transition:all .2s}.bv2:hover{border-color:var(--ouro);color:var(--ouro)}
.bs{display:inline-flex;align-items:center;gap:.45rem;padding:.6rem 1.5rem;background:var(--ouro);color:#1A0F00;border:none;border-radius:6px;cursor:pointer;font-family:var(--fonte-display,system-ui);font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;transition:opacity .2s}.bs:hover{opacity:.85}.bs:disabled{opacity:.45;cursor:not-allowed}
.fm{display:flex;flex-direction:column;gap:1.1rem;max-width:780px}
.fg{display:flex;flex-direction:column;gap:.35rem}
.fg label{font-size:.68rem;letter-spacing:.1em;text-transform:uppercase;color:var(--ouro)}
.fi{padding:.6rem .85rem;background:var(--fundo-card,#1C1408);border:1px solid var(--borda-media,rgba(184,134,11,.25));border-radius:6px;color:var(--texto);font-family:Georgia,serif;font-size:.88rem;transition:border-color .2s;width:100%}
.fi:focus{outline:none;border-color:var(--ouro)}
.fia{min-height:80px;resize:vertical;line-height:1.65}
.fa2{display:flex;gap:.75rem;padding-top:.5rem;flex-wrap:wrap;align-items:center}
.bdg{display:inline-flex;align-items:center;padding:.2rem .55rem;border-radius:20px;font-size:.68rem;letter-spacing:.08em;text-transform:uppercase}
.bdg-ativo{background:rgba(39,174,96,.12);border:1px solid #27ae60;color:#2ecc71}
.bdg-inativo{background:rgba(149,165,166,.1);border:1px solid #7f8c8d;color:#95a5a6}
.hint{font-size:.68rem;color:var(--texto-3);margin-top:.15rem;line-height:1.5}
.stat-chip{display:inline-flex;align-items:center;gap:.3rem;font-size:.72rem;color:var(--texto-3);white-space:nowrap}
</style>

<?php if ($acao === 'criar' || $acao === 'editar'): ?>
<!-- FORMULÁRIO -->
<div class="sh">
  <h2 class="st"><?= $acao==='criar' ? 'Novo <em>Cluster</em>' : 'Editar <em>Cluster</em>' ?></h2>
  <a href="clusters.php" class="bv2"><i class="fa fa-arrow-left"></i> Voltar</a>
</div>

<form id="fC" class="fm" novalidate>
<input type="hidden" name="acao" value="<?= $acao ?>">
<?php if ($acao === 'editar'): ?><input type="hidden" name="id" value="<?= (int)$clEditar['id'] ?>"><?php endif; ?>

<div class="fg">
  <label for="tit">Título do cluster / tema *</label>
  <input type="text" id="tit" name="titulo" class="fi" required
         placeholder="Ex: A Escrita Sombria — Técnicas e Inspirações"
         value="<?= adm_esc($clEditar['titulo'] ?? '') ?>">
</div>
<div class="fg">
  <label for="desc">Descrição (aparece na listagem e na pillar page)</label>
  <textarea id="desc" name="descricao" class="fi fia" rows="3"
            placeholder="Uma frase ou parágrafo descrevendo o tema do cluster…"><?= adm_esc($clEditar['descricao'] ?? '') ?></textarea>
</div>
<div class="fg">
  <label for="img">Imagem de capa (URL relativa ou absoluta)</label>
  <input type="text" id="img" name="imagem_url" class="fi"
         placeholder="img/clusters/meu-cluster.jpg"
         value="<?= adm_esc($clEditar['imagem_url'] ?? '') ?>">
</div>
<div class="fg">
  <label for="pilar">Post Pilar (hub central deste cluster)</label>
  <select name="pilar_slug" id="pilar" class="fi">
    <option value="">— Sem post pilar —</option>
    <?php foreach ($postsList as $post): ?>
      <option value="<?= adm_esc($post['slug']) ?>"
              <?= ($clEditar['pilar_slug'] ?? '') === $post['slug'] ? 'selected' : '' ?>>
        <?= adm_esc(mb_substr($post['titulo'], 0, 80)) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <span class="hint">O post pilar é a "Página de Autoridade" do cluster. Ele terá links para todos os satélites.</span>
</div>

<div style="background:rgba(184,134,11,.07);border:1px solid var(--borda);border-radius:6px;padding:.85rem 1rem;font-size:.8rem;color:var(--texto-3);line-height:1.6">
  <i class="fa fa-circle-info" style="color:var(--ouro)"></i>
  Para vincular posts satélites a este cluster: edite cada post no <strong>Blog</strong> e selecione o cluster no campo <strong>Cluster vinculado</strong>.
</div>

<div class="fa2">
  <button type="submit" class="bs" id="bSv">
    <i class="fa fa-<?= $acao==='criar' ? 'plus' : 'floppy-disk' ?>"></i>
    <?= $acao === 'criar' ? 'Criar Cluster' : 'Salvar' ?>
  </button>
  <a href="clusters.php" class="bv2">Cancelar</a>
  <?php if ($acao==='editar' && !empty($clEditar['slug'])): ?>
  <a href="../blog/cluster-template.html?slug=<?= adm_esc($clEditar['slug']) ?>" target="_blank" class="bv2">
    <i class="fa fa-arrow-up-right-from-square"></i> Ver Pillar Page
  </a>
  <?php endif; ?>
  <span id="fSt" style="font-size:.82rem"></span>
</div>
</form>

<?php else: ?>
<!-- LISTAGEM -->
<div class="sh">
  <h2 class="st">Clusters <em>Hub & Spoke</em></h2>
  <a href="clusters.php?acao=criar" class="bn2"><i class="fa fa-plus"></i> Novo Cluster</a>
</div>

<div style="background:rgba(184,134,11,.07);border:1px solid var(--borda);border-radius:6px;padding:.85rem 1rem;margin-bottom:1.5rem;font-size:.82rem;color:var(--texto-3);line-height:1.6">
  <i class="fa fa-circle-info" style="color:var(--ouro)"></i>
  <strong style="color:var(--texto)">Como funciona:</strong> Crie um cluster (tema), defina um Post Pilar como hub central, e vincule posts satélites pelo editor de posts. A Pillar Page é gerada automaticamente em <code>/blog/cluster-template.html?slug=seu-cluster</code>.
</div>

<div style="overflow-x:auto">
<table class="tbl">
  <thead><tr><th>#</th><th>Título</th><th>Pilar</th><th>Posts</th><th>Status</th><th>Ações</th></tr></thead>
  <tbody>
  <?php if (empty($clusters)): ?>
    <tr><td colspan="6" style="text-align:center;padding:2.5rem;color:var(--texto-3)">
      Nenhum cluster ainda. <a href="clusters.php?acao=criar" style="color:var(--ouro)">Criar o primeiro</a>.
    </td></tr>
  <?php else: foreach ($clusters as $cl): ?>
  <tr id="row-<?= $cl['id'] ?>">
    <td style="color:var(--texto-3);font-size:.78rem"><?= $cl['id'] ?></td>
    <td style="max-width:240px">
      <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--texto)"><?= adm_esc($cl['titulo']) ?></div>
      <div style="font-size:.72rem;color:var(--texto-3);margin-top:.1rem"><?= adm_esc($cl['slug']) ?></div>
    </td>
    <td style="font-size:.75rem;color:var(--texto-3);max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
      <?= $cl['pilar_slug'] ? adm_esc($cl['pilar_slug']) : '<span style="opacity:.4">—</span>' ?>
    </td>
    <td><span class="stat-chip"><i class="fa fa-file-lines"></i> <?= $cl['total_posts'] ?></span></td>
    <td>
      <span class="bdg bdg-<?= $cl['ativo'] ? 'ativo' : 'inativo' ?>" id="bdg-<?= $cl['id'] ?>">
        <?= $cl['ativo'] ? '✓ Ativo' : '● Inativo' ?>
      </span>
    </td>
    <td>
      <div class="ca">
        <a href="clusters.php?acao=editar&id=<?= $cl['id'] ?>" class="ba be" title="Editar"><i class="fa fa-pen"></i></a>
        <button class="ba <?= $cl['ativo'] ? 'bp' : 'bt' ?>" title="<?= $cl['ativo'] ? 'Desativar' : 'Ativar' ?>"
                onclick="tA(<?= $cl['id'] ?>,<?= $cl['ativo'] ? 0 : 1 ?>,this)">
          <i class="fa fa-<?= $cl['ativo'] ? 'eye-slash' : 'eye' ?>"></i>
        </button>
        <a href="../blog/cluster-template.html?slug=<?= adm_esc($cl['slug']) ?>" target="_blank" class="ba bx" title="Ver pillar page">
          <i class="fa fa-arrow-up-right-from-square"></i>
        </a>
        <button class="ba bd" title="Excluir" onclick="eX(<?= $cl['id'] ?>,this)">
          <i class="fa fa-trash"></i>
        </button>
      </div>
    </td>
  </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

<?php echo $ADMIN_FOOTER_HTML; ?>

<script>
document.getElementById('fC')?.addEventListener('submit', async e => {
  e.preventDefault();
  const btn=document.getElementById('bSv'), st=document.getElementById('fSt');
  btn.disabled=true; btn.innerHTML='<i class="fa fa-spinner fa-spin"></i> Salvando…'; st.textContent='';
  try {
    const r=await fetch('clusters.php',{method:'POST',body:new FormData(e.target)});
    const txt=await r.text(); let d;
    try{d=JSON.parse(txt);}catch{st.style.color='#e74c3c';st.textContent='✗ Resposta inválida. F12.';btn.disabled=false;btn.innerHTML='Salvar';return;}
    if(d.ok){st.style.color='#2ecc71';st.textContent='✓ '+(d.msg||'Salvo!');setTimeout(()=>location.href='clusters.php',900);}
    else{st.style.color='#e74c3c';st.textContent='✗ '+(d.erro||'Erro.');btn.disabled=false;btn.innerHTML='Salvar';}
  }catch(err){st.style.color='#e74c3c';st.textContent='✗ '+err.message;btn.disabled=false;btn.innerHTML='Salvar';}
});

async function _p(d){const f=new FormData();Object.entries(d).forEach(([k,v])=>f.append(k,v));const r=await fetch('clusters.php',{method:'POST',body:f});return JSON.parse(await r.text());}

async function tA(id,ativo,btn){
  btn.disabled=true;
  try{
    await _p({acao:'toggle_ativo',id,ativo});
    const bdg=document.getElementById('bdg-'+id);
    if(bdg){bdg.className='bdg bdg-'+(ativo?'ativo':'inativo');bdg.textContent=ativo?'✓ Ativo':'● Inativo';}
    btn.className='ba '+(ativo?'bp':'bt');
    btn.title=ativo?'Desativar':'Ativar';
    btn.querySelector('i').className='fa fa-'+(ativo?'eye-slash':'eye');
    btn.onclick=()=>tA(id,ativo?0:1,btn);
  }catch{}
  btn.disabled=false;
}

async function eX(id,btn){
  if(!confirm('Excluir cluster? Os posts satélites serão desvinculados mas não excluídos.'))return;
  btn.disabled=true;
  try{
    const d=await _p({acao:'excluir',id});
    if(d.ok){const r=document.getElementById('row-'+id);if(r){r.style.opacity='0';r.style.transition='opacity .3s';setTimeout(()=>r.remove(),350);}}
    else{alert(d.erro||'Erro.');btn.disabled=false;}
  }catch{alert('Erro.');btn.disabled=false;}
}
</script>
