<?php
ob_start();

/* ── POST handler — retorna JSON ──────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ini_set('display_errors', '0');
    session_name('rd_admin_sess');
    session_start();
    if (empty($_SESSION['admin_id'])) {
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'erro' => 'Sessão expirada.']);
        exit;
    }
    require_once __DIR__ . '/../backend/config.php';
    $pdo = db();
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');

    $acao = trim($_POST['acao'] ?? '');

    /* ── criar enquete ── */
    if ($acao === 'criar') {
        $titulo   = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $multipla = !empty($_POST['multipla']) ? 1 : 0;
        if (!$titulo) { echo json_encode(['ok' => false, 'erro' => 'Título obrigatório.']); exit; }
        try {
            $pdo->prepare("INSERT INTO enquetes (titulo, descricao, multipla, ativo) VALUES (?,?,?,1)")
                ->execute([$titulo, $descricao ?: null, $multipla]);
            $id = (int)$pdo->lastInsertId();
            // Salvar opções
            $opcoes = array_filter(array_map('trim', $_POST['opcoes'] ?? []), fn($o) => $o !== '');
            $icones = $_POST['icones'] ?? [];
            foreach (array_values($opcoes) as $i => $texto) {
                $pdo->prepare("INSERT INTO enquetes_opcoes (enquete_id, texto, icone, ordem) VALUES (?,?,?,?)")
                    ->execute([$id, $texto, trim($icones[$i] ?? '') ?: null, $i]);
            }
            echo json_encode(['ok' => true, 'id' => $id, 'msg' => 'Enquete criada!']);
        } catch (PDOException $e) {
            echo json_encode(['ok' => false, 'erro' => 'Banco: ' . $e->getMessage()]);
        }
        exit;
    }

    /* ── editar enquete ── */
    if ($acao === 'editar') {
        $id       = (int)($_POST['id'] ?? 0);
        $titulo   = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $multipla = !empty($_POST['multipla']) ? 1 : 0;
        if (!$id || !$titulo) { echo json_encode(['ok' => false, 'erro' => 'Dados inválidos.']); exit; }
        try {
            $pdo->prepare("UPDATE enquetes SET titulo=?, descricao=?, multipla=? WHERE id=?")
                ->execute([$titulo, $descricao ?: null, $multipla, $id]);
            // Recria as opções
            $pdo->prepare("DELETE FROM enquetes_opcoes WHERE enquete_id=?")->execute([$id]);
            $opcoes = array_filter(array_map('trim', $_POST['opcoes'] ?? []), fn($o) => $o !== '');
            $icones = $_POST['icones'] ?? [];
            foreach (array_values($opcoes) as $i => $texto) {
                $pdo->prepare("INSERT INTO enquetes_opcoes (enquete_id, texto, icone, ordem) VALUES (?,?,?,?)")
                    ->execute([$id, $texto, trim($icones[$i] ?? '') ?: null, $i]);
            }
            echo json_encode(['ok' => true, 'msg' => 'Enquete atualizada!']);
        } catch (PDOException $e) {
            echo json_encode(['ok' => false, 'erro' => 'Banco: ' . $e->getMessage()]);
        }
        exit;
    }

    /* ── toggle ativo ── */
    if ($acao === 'toggle_ativo') {
        $id = (int)($_POST['id'] ?? 0);
        $v  = (int)($_POST['ativo'] ?? 0);
        $pdo->prepare("UPDATE enquetes SET ativo=? WHERE id=?")->execute([$v, $id]);
        echo json_encode(['ok' => true, 'ativo' => $v]);
        exit;
    }

    /* ── excluir ── */
    if ($acao === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['ok' => false, 'erro' => 'ID inválido.']); exit; }
        $pdo->prepare("DELETE FROM enquetes WHERE id=?")->execute([$id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['ok' => false, 'erro' => 'Ação desconhecida.']);
    exit;
}

/* ── GET — HTML ──────────────────────────────────────────────── */
$ADMIN_PAGE = 'enquetes';
require_once __DIR__ . '/_admin.php';

$acao = $_GET['acao'] ?? 'listar';
$enqEditar = null;
$opcoesEditar = [];
if ($acao === 'editar') {
    $id = (int)($_GET['id'] ?? 0);
    $st = $pdo->prepare("SELECT * FROM enquetes WHERE id=? LIMIT 1");
    $st->execute([$id]);
    $enqEditar = $st->fetch(PDO::FETCH_ASSOC);
    if (!$enqEditar) { header('Location: enquetes.php'); exit; }
    $stO = $pdo->prepare("SELECT * FROM enquetes_opcoes WHERE enquete_id=? ORDER BY ordem ASC");
    $stO->execute([$id]);
    $opcoesEditar = $stO->fetchAll(PDO::FETCH_ASSOC);
}

// Listar
$enquetes = [];
try {
    $enquetes = $pdo->query(
        "SELECT e.*,
                (SELECT COUNT(*) FROM enquetes_opcoes WHERE enquete_id=e.id) AS total_opcoes,
                (SELECT COUNT(*) FROM enquetes_respostas WHERE enquete_id=e.id) AS total_votos
         FROM enquetes e ORDER BY e.id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$flash = null;
if (!empty($_SESSION['enq_flash'])) { $flash = $_SESSION['enq_flash']; unset($_SESSION['enq_flash']); }
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
.ba:disabled{opacity:.35;cursor:not-allowed}
.bn2{display:inline-flex;align-items:center;gap:.45rem;padding:.5rem 1.25rem;background:var(--ouro);color:#1A0F00;border:none;border-radius:6px;cursor:pointer;text-decoration:none;font-family:var(--fonte-display,system-ui);font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.bn2:hover{opacity:.85}
.bv2{display:inline-flex;align-items:center;gap:.45rem;padding:.6rem 1.25rem;background:transparent;color:var(--texto-3);border:1px solid var(--borda-media);border-radius:6px;cursor:pointer;font-family:var(--fonte-display,system-ui);font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;text-decoration:none;transition:all .2s}.bv2:hover{border-color:var(--ouro);color:var(--ouro)}
.bs{display:inline-flex;align-items:center;gap:.45rem;padding:.6rem 1.5rem;background:var(--ouro);color:#1A0F00;border:none;border-radius:6px;cursor:pointer;font-family:var(--fonte-display,system-ui);font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;transition:opacity .2s}.bs:hover{opacity:.85}.bs:disabled{opacity:.45;cursor:not-allowed}
.fm{display:flex;flex-direction:column;gap:1.1rem;max-width:780px}
.fr{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.fg{display:flex;flex-direction:column;gap:.35rem}
.fg label{font-size:.68rem;letter-spacing:.1em;text-transform:uppercase;color:var(--ouro)}
.fi{padding:.6rem .85rem;background:var(--fundo-card,#1C1408);border:1px solid var(--borda-media,rgba(184,134,11,.25));border-radius:6px;color:var(--texto);font-family:Georgia,serif;font-size:.88rem;transition:border-color .2s;width:100%}
.fi:focus{outline:none;border-color:var(--ouro)}
.fia{min-height:80px;resize:vertical;line-height:1.65}
.ck{display:flex;align-items:center;gap:.55rem;cursor:pointer;font-size:.88rem;color:var(--texto-2)}
.ck input{accent-color:var(--ouro);width:15px;height:15px;cursor:pointer}
.fl{padding:.75rem 1.25rem;border-radius:6px;margin-bottom:1.5rem;font-size:.88rem;display:flex;align-items:center;gap:.6rem}
.fl.ok{background:rgba(39,174,96,.1);border:1px solid #27ae60;color:#2ecc71}
.fl.erro{background:rgba(231,76,60,.1);border:1px solid #c0392b;color:#e74c3c}
.fa2{display:flex;gap:.75rem;padding-top:.5rem;flex-wrap:wrap;align-items:center}
/* Opções */
.opc-lista{display:flex;flex-direction:column;gap:.5rem;margin-top:.25rem}
.opc-row{display:flex;gap:.5rem;align-items:center}
.opc-row .fi{flex:1}
.opc-row .fi-ic{width:120px;flex-shrink:0}
.opc-rm{width:32px;height:32px;border-radius:6px;background:rgba(231,76,60,.12);color:#e74c3c;border:1px solid #c0392b;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.78rem;flex-shrink:0;transition:background .15s}.opc-rm:hover{background:rgba(231,76,60,.28)}
.opc-add{display:inline-flex;align-items:center;gap:.4rem;padding:.4rem .9rem;background:transparent;border:1px dashed var(--borda-2,rgba(184,134,11,.3));border-radius:6px;color:var(--ouro);font-size:.78rem;cursor:pointer;transition:all .2s;margin-top:.25rem}.opc-add:hover{background:rgba(184,134,11,.07)}
.hint{font-size:.68rem;color:var(--texto-3);margin-top:.15rem;line-height:1.5}
.bdg{display:inline-flex;align-items:center;padding:.2rem .55rem;border-radius:20px;font-size:.68rem;letter-spacing:.08em;text-transform:uppercase}
.bdg-ativo{background:rgba(39,174,96,.12);border:1px solid #27ae60;color:#2ecc71}
.bdg-inativo{background:rgba(149,165,166,.1);border:1px solid #7f8c8d;color:#95a5a6}
.stat-chip{display:inline-flex;align-items:center;gap:.3rem;font-size:.72rem;color:var(--texto-3);white-space:nowrap}
</style>

<?php if ($flash): ?>
<div class="fl <?= adm_esc($flash['tipo']) ?>" role="alert">
  <i class="fa fa-<?= $flash['tipo'] === 'ok' ? 'check-circle' : 'triangle-exclamation' ?>"></i>
  <?= adm_esc($flash['msg']) ?>
</div>
<?php endif; ?>

<?php if ($acao === 'criar' || $acao === 'editar'): ?>
<!-- ════════════════════════════════════════════════════════════
     FORMULÁRIO
════════════════════════════════════════════════════════════ -->
<div class="sh">
  <h2 class="st"><?= $acao === 'criar' ? 'Nova <em>Enquete</em>' : 'Editar <em>Enquete</em>' ?></h2>
  <a href="enquetes.php" class="bv2"><i class="fa fa-arrow-left"></i> Voltar</a>
</div>

<form id="fE" class="fm" novalidate>
<input type="hidden" name="acao" value="<?= $acao ?>">
<?php if ($acao === 'editar'): ?>
  <input type="hidden" name="id" value="<?= (int)$enqEditar['id'] ?>">
<?php endif; ?>

<div class="fg">
  <label for="tit">Pergunta / Título *</label>
  <input type="text" id="tit" name="titulo" class="fi" required
         placeholder="Ex: Qual gênero você prefere?"
         value="<?= adm_esc($enqEditar['titulo'] ?? '') ?>">
</div>
<div class="fg">
  <label for="desc">Descrição <span style="font-size:.6rem;opacity:.6">(opcional)</span></label>
  <textarea id="desc" name="descricao" class="fi fia" rows="2"
            placeholder="Texto de apoio exibido abaixo da pergunta…"><?= adm_esc($enqEditar['descricao'] ?? '') ?></textarea>
</div>
<div class="fg">
  <label class="ck">
    <input type="checkbox" name="multipla" value="1"
           <?= !empty($enqEditar['multipla']) ? 'checked' : '' ?>>
    Permitir múltipla escolha
  </label>
</div>

<div class="fg">
  <label>Opções de resposta *</label>
  <div class="hint">Preencha o texto de cada opção. O ícone (Font Awesome) é opcional — ex: <code>fa-book</code></div>
  <div class="opc-lista" id="opcLista">
    <?php
    $opBase = $opcoesEditar ?: [['texto'=>'','icone'=>''],['texto'=>'','icone'=>'']];
    foreach ($opBase as $op):
    ?>
    <div class="opc-row">
      <input type="text" name="opcoes[]" class="fi" placeholder="Texto da opção…"
             value="<?= adm_esc($op['texto']) ?>">
      <input type="text" name="icones[]" class="fi fi-ic" placeholder="fa-book"
             value="<?= adm_esc($op['icone'] ?? '') ?>">
      <button type="button" class="opc-rm" title="Remover opção" onclick="rmOpc(this)">
        <i class="fa fa-xmark"></i>
      </button>
    </div>
    <?php endforeach; ?>
  </div>
  <button type="button" class="opc-add" onclick="addOpc()">
    <i class="fa fa-plus"></i> Adicionar opção
  </button>
</div>

<div class="fa2">
  <button type="submit" class="bs" id="bSv">
    <i class="fa fa-<?= $acao === 'criar' ? 'plus' : 'floppy-disk' ?>"></i>
    <?= $acao === 'criar' ? 'Criar Enquete' : 'Salvar Alterações' ?>
  </button>
  <a href="enquetes.php" class="bv2">Cancelar</a>
  <span id="fSt" style="font-size:.82rem"></span>
</div>
</form>

<?php else: ?>
<!-- ════════════════════════════════════════════════════════════
     LISTAGEM
════════════════════════════════════════════════════════════ -->
<div class="sh">
  <h2 class="st">Gerenciar <em>Enquetes</em></h2>
  <a href="enquetes.php?acao=criar" class="bn2"><i class="fa fa-plus"></i> Nova Enquete</a>
</div>

<div style="background:rgba(184,134,11,.07);border:1px solid var(--borda);border-radius:6px;padding:.85rem 1rem;margin-bottom:1.5rem;font-size:.82rem;color:var(--texto-3);line-height:1.6">
  <i class="fa fa-circle-info" style="color:var(--ouro)"></i>
  Para vincular uma enquete a um post: edite o post no painel <strong>Blog</strong> e escolha a enquete no campo <strong>Enquete vinculada</strong>.
</div>

<div style="overflow-x:auto">
<table class="tbl">
  <thead>
    <tr>
      <th>#</th><th>Pergunta</th><th>Opções</th><th>Votos</th>
      <th>Múltipla</th><th>Status</th><th>Ações</th>
    </tr>
  </thead>
  <tbody>
  <?php if (empty($enquetes)): ?>
    <tr><td colspan="7" style="text-align:center;padding:2.5rem;color:var(--texto-3)">
      Nenhuma enquete ainda. <a href="enquetes.php?acao=criar" style="color:var(--ouro)">Criar a primeira</a>.
    </td></tr>
  <?php else: foreach ($enquetes as $eq): ?>
  <tr id="row-<?= $eq['id'] ?>">
    <td style="color:var(--texto-3);font-size:.78rem"><?= $eq['id'] ?></td>
    <td style="max-width:260px">
      <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--texto)"
           title="<?= adm_esc($eq['titulo']) ?>">
        <?= adm_esc($eq['titulo']) ?>
      </div>
      <?php if ($eq['descricao']): ?>
      <div style="font-size:.72rem;color:var(--texto-3);margin-top:.1rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
        <?= adm_esc(mb_substr($eq['descricao'], 0, 60)) ?>…
      </div>
      <?php endif; ?>
    </td>
    <td><span class="stat-chip"><i class="fa fa-list"></i> <?= $eq['total_opcoes'] ?></span></td>
    <td><span class="stat-chip"><i class="fa fa-check-to-slot"></i> <?= $eq['total_votos'] ?></span></td>
    <td><?= $eq['multipla'] ? '<i class="fa fa-check" style="color:var(--ouro)"></i>' : '<span style="opacity:.35">—</span>' ?></td>
    <td>
      <span class="bdg bdg-<?= $eq['ativo'] ? 'ativo' : 'inativo' ?>" id="bdg-<?= $eq['id'] ?>">
        <?= $eq['ativo'] ? '✓ Ativa' : '● Inativa' ?>
      </span>
    </td>
    <td>
      <div class="ca">
        <a href="enquetes.php?acao=editar&id=<?= $eq['id'] ?>" class="ba be" title="Editar">
          <i class="fa fa-pen"></i>
        </a>
        <button class="ba <?= $eq['ativo'] ? 'bp' : 'bt' ?>"
                title="<?= $eq['ativo'] ? 'Desativar' : 'Ativar' ?>"
                onclick="tA(<?= $eq['id'] ?>,<?= $eq['ativo'] ? 0 : 1 ?>,this)">
          <i class="fa fa-<?= $eq['ativo'] ? 'eye-slash' : 'eye' ?>"></i>
        </button>
        <button class="ba bd" title="Excluir"
                onclick="eX(<?= $eq['id'] ?>,this)"
                <?= $eq['total_votos'] > 0 ? 'title="Já tem votos — excluir apagará todos os votos"' : '' ?>>
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
/* ── Opções dinâmicas ── */
function addOpc(){
  const row=document.createElement('div');
  row.className='opc-row';
  row.innerHTML='<input type="text" name="opcoes[]" class="fi" placeholder="Texto da opção…">'
    +'<input type="text" name="icones[]" class="fi fi-ic" placeholder="fa-book">'
    +'<button type="button" class="opc-rm" title="Remover" onclick="rmOpc(this)"><i class="fa fa-xmark"></i></button>';
  document.getElementById('opcLista').appendChild(row);
  row.querySelector('input').focus();
}
function rmOpc(btn){
  const lista=document.getElementById('opcLista');
  if(lista.children.length<=2){alert('Mantenha pelo menos 2 opções.');return;}
  btn.closest('.opc-row').remove();
}

/* ── Submit ── */
document.getElementById('fE')?.addEventListener('submit',async e=>{
  e.preventDefault();
  const btn=document.getElementById('bSv'),st=document.getElementById('fSt');
  // Validar mínimo 2 opções preenchidas
  const opc=[...document.querySelectorAll('#opcLista input[name="opcoes[]"]')]
    .map(i=>i.value.trim()).filter(Boolean);
  if(opc.length<2){st.style.color='#e74c3c';st.textContent='✗ Adicione pelo menos 2 opções.';return;}
  btn.disabled=true;btn.innerHTML='<i class="fa fa-spinner fa-spin"></i> Salvando…';st.textContent='';
  try{
    const r=await fetch('enquetes.php',{method:'POST',body:new FormData(e.target)});
    const txt=await r.text();let d;
    try{d=JSON.parse(txt);}catch{st.style.color='#e74c3c';st.textContent='✗ Resposta inválida. F12.';console.error(txt.substring(0,400));btn.disabled=false;btn.innerHTML='<i class="fa fa-floppy-disk"></i> Salvar';return;}
    if(d.ok){st.style.color='#2ecc71';st.textContent='✓ '+(d.msg||'Salvo!');setTimeout(()=>location.href='enquetes.php',900);}
    else{st.style.color='#e74c3c';st.textContent='✗ '+(d.erro||'Erro.');btn.disabled=false;btn.innerHTML='<i class="fa fa-floppy-disk"></i> Salvar';}
  }catch(err){st.style.color='#e74c3c';st.textContent='✗ '+err.message;btn.disabled=false;btn.innerHTML='<i class="fa fa-floppy-disk"></i> Salvar';}
});

/* ── Ações tabela ── */
async function _p(d){const f=new FormData();Object.entries(d).forEach(([k,v])=>f.append(k,v));const r=await fetch('enquetes.php',{method:'POST',body:f});return JSON.parse(await r.text());}

async function tA(id,ativo,btn){
  btn.disabled=true;
  try{
    const d=await _p({acao:'toggle_ativo',id,ativo});
    if(d.ok){
      const bdg=document.getElementById('bdg-'+id);
      if(bdg){bdg.className='bdg bdg-'+(ativo?'ativo':'inativo');bdg.textContent=ativo?'✓ Ativa':'● Inativa';}
      btn.className='ba '+(ativo?'bp':'bt');
      btn.title=ativo?'Desativar':'Ativar';
      btn.querySelector('i').className='fa fa-'+(ativo?'eye-slash':'eye');
      btn.onclick=()=>tA(id,ativo?0:1,btn);
    }
  }catch{}
  btn.disabled=false;
}

async function eX(id,btn){
  if(!confirm('Excluir esta enquete e todos os votos?'))return;
  btn.disabled=true;
  try{
    const d=await _p({acao:'excluir',id});
    if(d.ok){const r=document.getElementById('row-'+id);if(r){r.style.opacity='0';r.style.transition='opacity .3s';setTimeout(()=>r.remove(),350);}}
    else{alert(d.erro||'Erro.');btn.disabled=false;}
  }catch{alert('Erro.');btn.disabled=false;}
}
</script>
