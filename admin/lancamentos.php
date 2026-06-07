<?php
ob_start();
$ADMIN_PAGE = 'lancamentos';
require_once __DIR__ . '/_admin.php';

$campanhas = [];
try {
    $campanhas = $pdo->query(
        "SELECT p.*, (SELECT COUNT(*) FROM pre_lancamento_leads WHERE lancamento_id=p.id) AS total_leads
         FROM pre_lancamentos p ORDER BY p.id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

function adm_e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>

<style>
.sh{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem}
.st{font-family:Georgia,serif;font-size:1.3rem;font-weight:400}.st em{color:var(--ouro);font-style:italic}
.grid-camp{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1.25rem}
.camp-card{background:var(--fundo-card);border:1px solid var(--borda);border-radius:10px;overflow:hidden;transition:border-color .2s}
.camp-card:hover{border-color:var(--borda-media)}
.camp-header{padding:1rem 1.25rem;display:flex;align-items:center;gap:1rem;border-bottom:1px solid var(--borda)}
.camp-capa{width:48px;height:68px;object-fit:cover;border-radius:4px;flex-shrink:0;border:1px solid var(--borda)}
.camp-capa-ph{width:48px;height:68px;background:var(--fundo-2);border-radius:4px;display:flex;align-items:center;justify-content:center;color:var(--ouro);opacity:.3;flex-shrink:0;font-size:1.2rem}
.camp-titulo{font-family:Georgia,serif;font-size:.95rem;font-weight:500;color:var(--texto);line-height:1.3}
.camp-sub{font-size:.72rem;color:var(--texto-3);margin-top:.15rem}
.camp-body{padding:.85rem 1.25rem;display:flex;flex-direction:column;gap:.5rem;font-size:.82rem;color:var(--texto-2)}
.camp-stat{display:flex;align-items:center;gap:.4rem;font-family:var(--fonte-display);font-size:.65rem;letter-spacing:.08em;text-transform:uppercase;color:var(--texto-3)}
.camp-stat strong{color:var(--ouro);font-size:.9rem}
.camp-actions{padding:.75rem 1.25rem;border-top:1px solid var(--borda);display:flex;gap:.4rem;flex-wrap:wrap}
.ba{display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .7rem;border-radius:6px;font-family:var(--fonte-display);font-size:.62rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;border:none;text-decoration:none;transition:all .15s;white-space:nowrap}
.be{background:rgba(52,152,219,.15);color:#3498db;border:1px solid #2980b9}.be:hover{background:rgba(52,152,219,.3)}
.bg{background:rgba(39,174,96,.12);color:#27ae60;border:1px solid #27ae60}.bg:hover{background:rgba(39,174,96,.25)}
.bv{background:rgba(241,196,15,.1);color:#f1c40f;border:1px solid #d4ac0d}.bv:hover{background:rgba(241,196,15,.25)}
.br{background:rgba(231,76,60,.12);color:#e74c3c;border:1px solid #c0392b}.br:hover{background:rgba(231,76,60,.25)}
.bp{background:rgba(149,165,166,.1);color:#95a5a6;border:1px solid #7f8c8d}.bp:hover{background:rgba(149,165,166,.25)}
.bn{display:inline-flex;align-items:center;gap:.45rem;padding:.5rem 1.25rem;background:var(--ouro);color:#1A0F00;border:none;border-radius:6px;cursor:pointer;text-decoration:none;font-family:var(--fonte-display);font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.bn:hover{opacity:.85}
.bdg{padding:.15rem .5rem;border-radius:20px;font-size:.62rem;letter-spacing:.08em;text-transform:uppercase}
.bdg-ativo{background:rgba(39,174,96,.12);border:1px solid #27ae60;color:#2ecc71}
.bdg-inativo{background:rgba(149,165,166,.1);border:1px solid #7f8c8d;color:#95a5a6}
.bdg-lancado{background:rgba(241,196,15,.1);border:1px solid #d4ac0d;color:#f1c40f}

/* Modal */
.overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:500;display:none;align-items:center;justify-content:center;padding:1rem}
.overlay.aberto{display:flex}
.modal{background:var(--fundo-card);border:1px solid var(--borda-media);border-radius:12px;width:100%;max-width:700px;max-height:90vh;overflow-y:auto;padding:1.75rem}
.modal-titulo{font-family:Georgia,serif;font-size:1.2rem;font-weight:400;color:var(--ouro);margin-bottom:1.25rem}
.fm{display:flex;flex-direction:column;gap:.9rem}
.fg{display:flex;flex-direction:column;gap:.3rem}
.fg label{font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:var(--ouro)}
.fi{padding:.6rem .85rem;background:var(--fundo-2);border:1px solid var(--borda-media);border-radius:6px;color:var(--texto);font-family:Georgia,serif;font-size:.88rem;width:100%;transition:border-color .2s}
.fi:focus{outline:none;border-color:var(--ouro)}
.fia{min-height:80px;resize:vertical;line-height:1.65}
.fibig{min-height:200px;font-size:.85rem;font-family:monospace}
.fr2{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
.fa2{display:flex;gap:.75rem;padding-top:.5rem;flex-wrap:wrap;align-items:center}
.bs{display:inline-flex;align-items:center;gap:.45rem;padding:.6rem 1.5rem;background:var(--ouro);color:#1A0F00;border:none;border-radius:6px;cursor:pointer;font-family:var(--fonte-display);font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;transition:opacity .2s}.bs:hover{opacity:.85}.bs:disabled{opacity:.45;cursor:not-allowed}
.bv2{display:inline-flex;align-items:center;gap:.4rem;padding:.6rem 1.25rem;background:transparent;color:var(--texto-3);border:1px solid var(--borda-media);border-radius:6px;cursor:pointer;font-family:var(--fonte-display);font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;transition:all .2s}.bv2:hover{border-color:var(--ouro);color:var(--ouro)}
.hint{font-size:.68rem;color:var(--texto-3);line-height:1.5}
</style>

<div class="sh">
  <h2 class="st">Lista de <em>Espera</em> · Pré-lançamentos</h2>
  <button class="bn" onclick="abrirModal(null)"><i class="fa fa-plus"></i> Nova Campanha</button>
</div>

<?php if (empty($campanhas)): ?>
<div style="text-align:center;padding:3rem;color:var(--texto-3)">
  <i class="fa fa-hourglass-half" style="font-size:2.5rem;color:var(--ouro);opacity:.25;display:block;margin-bottom:1rem"></i>
  <p>Nenhuma campanha ainda. Crie a primeira lista de espera para o próximo lançamento.</p>
  <p style="font-size:.75rem;margin-top:.5rem">Lembre-se de executar <code>database/migration_lista_espera.sql</code> primeiro.</p>
</div>
<?php else: ?>
<div class="grid-camp">
  <?php foreach ($campanhas as $c): ?>
  <div class="camp-card" id="card-<?= $c['id'] ?>">
    <div class="camp-header">
      <?php if ($c['capa_img']): ?>
        <img src="../<?= adm_e($c['capa_img']) ?>" alt="" class="camp-capa">
      <?php else: ?>
        <div class="camp-capa-ph"><i class="fa fa-book"></i></div>
      <?php endif; ?>
      <div>
        <div class="camp-titulo">
          <?= adm_e($c['titulo']) ?>
          <?php if ($c['lancado']): ?>
            <span class="bdg bdg-lancado" style="margin-left:.4rem">Lançado</span>
          <?php elseif ($c['ativo']): ?>
            <span class="bdg bdg-ativo" style="margin-left:.4rem">Ativo</span>
          <?php else: ?>
            <span class="bdg bdg-inativo" style="margin-left:.4rem">Inativo</span>
          <?php endif; ?>
        </div>
        <?php if ($c['subtitulo']): ?>
          <div class="camp-sub"><?= adm_e($c['subtitulo']) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <div class="camp-body">
      <div class="camp-stat">
        <i class="fa fa-users"></i> Leads: <strong><?= $c['total_leads'] ?></strong>
      </div>
      <?php if ($c['data_lancamento']): ?>
      <div class="camp-stat">
        <i class="fa fa-calendar"></i> Lançamento: <strong><?= date('d/m/Y', strtotime($c['data_lancamento'])) ?></strong>
      </div>
      <?php endif; ?>
      <?php if ($c['brinde_titulo']): ?>
      <div class="camp-stat">
        <i class="fa fa-gift"></i> Brinde: <strong><?= adm_e(mb_substr($c['brinde_titulo'],0,40)) ?></strong>
      </div>
      <?php endif; ?>
      <div class="camp-stat" style="margin-top:.25rem">
        <i class="fa fa-link"></i> Link:
        <a href="../lancamento.php?slug=<?= adm_e($c['slug']) ?>" target="_blank"
           style="color:var(--ouro);font-size:.72rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:180px">
          lancamento.php?slug=<?= adm_e($c['slug']) ?>
        </a>
      </div>
    </div>
    <div class="camp-actions">
      <button class="ba be" onclick="abrirModal(<?= htmlspecialchars(json_encode($c)) ?>)">
        <i class="fa fa-pen"></i> Editar
      </button>
      <button class="ba bv" onclick="verLeads(<?= $c['id'] ?>, '<?= adm_e($c['titulo']) ?>')">
        <i class="fa fa-users"></i> Leads
      </button>
      <button class="ba <?= $c['ativo'] ? 'bp' : 'bg' ?>"
              onclick="toggleAtivo(<?= $c['id'] ?>, <?= $c['ativo'] ? 0 : 1 ?>)">
        <i class="fa fa-<?= $c['ativo'] ? 'eye-slash' : 'eye' ?>"></i>
        <?= $c['ativo'] ? 'Desativar' : 'Ativar' ?>
      </button>
      <?php if (!$c['lancado'] && $c['total_leads'] > 0): ?>
      <button class="ba bg" onclick="dispararLancamento(<?= $c['id'] ?>, '<?= adm_e($c['titulo']) ?>')">
        <i class="fa fa-paper-plane"></i> Disparar
      </button>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal criar/editar -->
<div class="overlay" id="overlayForm">
<div class="modal">
  <h2 class="modal-titulo" id="modalTitulo"><i class="fa fa-hourglass-half"></i> Nova Campanha</h2>
  <form class="fm" id="fCamp" novalidate>
    <input type="hidden" id="fId" value="0">
    <div class="fr2">
      <div class="fg">
        <label for="fTitulo">Título do livro *</label>
        <input type="text" id="fTitulo" class="fi" required placeholder="Ex: O Nome do Vento">
      </div>
      <div class="fg">
        <label for="fSubtitulo">Subtítulo</label>
        <input type="text" id="fSubtitulo" class="fi" placeholder="Frase de efeito ou gênero">
      </div>
    </div>
    <div class="fg">
      <label for="fDescricao">Descrição (apresentação do livro)</label>
      <textarea id="fDescricao" class="fi fia" rows="3" placeholder="Sinopse curta para despertar curiosidade…"></textarea>
    </div>
    <div class="fr2">
      <div class="fg">
        <label for="fCapa">Capa (caminho relativo)</label>
        <input type="text" id="fCapa" class="fi" placeholder="img/capa-meu-livro.jpg">
      </div>
      <div class="fg">
        <label for="fData">Data de lançamento</label>
        <input type="date" id="fData" class="fi">
      </div>
    </div>
    <div class="fg">
      <label for="fBrindeT">Título do brinde</label>
      <input type="text" id="fBrindeT" class="fi" placeholder="Ex: Primeiro capítulo gratuito · Nota do autor">
    </div>
    <div class="fg">
      <label for="fBrindeH">Conteúdo do brinde (HTML)</label>
      <textarea id="fBrindeH" class="fi fibig" rows="8"
                placeholder="Cole aqui o trecho do capítulo, nota do autor, ou qualquer conteúdo HTML que será enviado por e-mail ao inscrever."></textarea>
      <p class="hint">Este conteúdo vai no e-mail enviado imediatamente após a inscrição. Use HTML básico: &lt;p&gt;, &lt;em&gt;, &lt;strong&gt;, &lt;blockquote&gt;.</p>
    </div>
    <div class="fa2">
      <button type="submit" class="bs" id="fBtn"><i class="fa fa-floppy-disk"></i> Salvar</button>
      <button type="button" class="bv2" onclick="fecharModal()">Cancelar</button>
      <span id="fMsg" style="font-size:.82rem"></span>
    </div>
  </form>
</div>
</div>

<!-- Modal leads -->
<div class="overlay" id="overlayLeads">
<div class="modal" style="max-width:680px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem">
    <h2 class="modal-titulo" style="margin-bottom:0" id="leadsTitulo"><i class="fa fa-users"></i> Leads</h2>
    <button class="bv2" onclick="document.getElementById('overlayLeads').classList.remove('aberto')">Fechar</button>
  </div>
  <div id="leadsConteudo"><p style="color:var(--texto-3);text-align:center;padding:2rem">Carregando…</p></div>
</div>
</div>

<?php echo $ADMIN_FOOTER_HTML; ?>

<script>
/* ── Modal form ── */
function abrirModal(c) {
  document.getElementById('fId').value       = c?.id      || 0;
  document.getElementById('fTitulo').value   = c?.titulo  || '';
  document.getElementById('fSubtitulo').value= c?.subtitulo || '';
  document.getElementById('fDescricao').value= c?.descricao || '';
  document.getElementById('fCapa').value     = c?.capa_img || '';
  document.getElementById('fData').value     = c?.data_lancamento || '';
  document.getElementById('fBrindeT').value  = c?.brinde_titulo || '';
  document.getElementById('fBrindeH').value  = c?.brinde_html || '';
  document.getElementById('modalTitulo').innerHTML =
    '<i class="fa fa-hourglass-half"></i> ' + (c ? 'Editar Campanha' : 'Nova Campanha');
  document.getElementById('fMsg').textContent = '';
  document.getElementById('overlayForm').classList.add('aberto');
  document.getElementById('fTitulo').focus();
}
function fecharModal() { document.getElementById('overlayForm').classList.remove('aberto'); }

document.getElementById('fCamp')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const btn = document.getElementById('fBtn');
  const msg = document.getElementById('fMsg');
  const id  = parseInt(document.getElementById('fId').value) || 0;
  btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Salvando…';
  const body = {
    acao:             id ? 'editar' : 'criar',
    id,
    titulo:           document.getElementById('fTitulo').value.trim(),
    subtitulo:        document.getElementById('fSubtitulo').value.trim(),
    descricao:        document.getElementById('fDescricao').value.trim(),
    capa_img:         document.getElementById('fCapa').value.trim(),
    data_lancamento:  document.getElementById('fData').value,
    brinde_titulo:    document.getElementById('fBrindeT').value.trim(),
    brinde_html:      document.getElementById('fBrindeH').value.trim(),
  };
  try {
    const r = await fetch('../backend/pre-lancamento.php', {
      method:'POST', credentials:'same-origin',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(body),
    });
    const d = await r.json();
    if (d.ok) {
      msg.style.color = '#2ecc71';
      msg.textContent = '✓ ' + (d.mensagem || 'Salvo!');
      setTimeout(() => location.reload(), 900);
    } else {
      msg.style.color = '#e74c3c';
      msg.textContent = '✗ ' + (d.erro || 'Erro.');
      btn.disabled = false; btn.innerHTML = '<i class="fa fa-floppy-disk"></i> Salvar';
    }
  } catch {
    msg.style.color = '#e74c3c'; msg.textContent = '✗ Erro de conexão.';
    btn.disabled = false; btn.innerHTML = '<i class="fa fa-floppy-disk"></i> Salvar';
  }
});

/* ── Toggle ativo ── */
async function toggleAtivo(id, ativo) {
  const r = await fetch('../backend/pre-lancamento.php', {
    method:'POST', credentials:'same-origin',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({acao:'toggle', id, ativo}),
  });
  if ((await r.json()).ok) location.reload();
}

/* ── Leads ── */
async function verLeads(id, titulo) {
  document.getElementById('leadsTitulo').innerHTML = `<i class="fa fa-users"></i> Leads — ${titulo}`;
  document.getElementById('overlayLeads').classList.add('aberto');
  const el = document.getElementById('leadsConteudo');
  el.innerHTML = '<p style="color:var(--texto-3);text-align:center;padding:2rem">Carregando…</p>';
  try {
    const r = await fetch(`../backend/pre-lancamento.php?acao=leads&id=${id}`, {credentials:'same-origin'});
    const d = await r.json();
    if (!d.ok || !d.leads?.length) {
      el.innerHTML = '<p style="color:var(--texto-3);text-align:center;padding:2rem">Nenhum lead ainda.</p>';
      return;
    }
    const fmtDt = s => new Date(s).toLocaleDateString('pt-BR', {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'});
    const rows = d.leads.map(l => `<tr>
      <td style="padding:.5rem .75rem;color:var(--texto)">${l.nome || '—'}</td>
      <td style="padding:.5rem .75rem;color:var(--texto-2)">${l.email}</td>
      <td style="padding:.5rem .75rem;text-align:center">${l.brinde_enviado ? '✓' : '—'}</td>
      <td style="padding:.5rem .75rem;text-align:center">${l.lancamento_enviado ? '✓' : '—'}</td>
      <td style="padding:.5rem .75rem;font-size:.72rem;color:var(--texto-3)">${fmtDt(l.inscrito_em)}</td>
    </tr>`).join('');
    el.innerHTML = `<p style="font-size:.75rem;color:var(--texto-3);margin-bottom:.75rem">${d.leads.length} inscritos</p>
      <div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;font-size:.82rem">
        <thead><tr style="border-bottom:1px solid var(--borda)">
          <th style="padding:.4rem .75rem;text-align:left;color:var(--ouro);font-size:.6rem;letter-spacing:.1em;text-transform:uppercase">Nome</th>
          <th style="padding:.4rem .75rem;text-align:left;color:var(--ouro);font-size:.6rem;letter-spacing:.1em;text-transform:uppercase">E-mail</th>
          <th style="padding:.4rem .75rem;color:var(--ouro);font-size:.6rem;letter-spacing:.1em;text-transform:uppercase">Brinde</th>
          <th style="padding:.4rem .75rem;color:var(--ouro);font-size:.6rem;letter-spacing:.1em;text-transform:uppercase">Lançam.</th>
          <th style="padding:.4rem .75rem;color:var(--ouro);font-size:.6rem;letter-spacing:.1em;text-transform:uppercase">Inscrito em</th>
        </tr></thead>
        <tbody>${rows}</tbody>
      </table></div>`;
  } catch { el.innerHTML = '<p style="color:var(--texto-3);text-align:center">Erro ao carregar.</p>'; }
}

/* ── Disparar lançamento ── */
async function dispararLancamento(id, titulo) {
  if (!confirm(`Disparar o e-mail de LANÇAMENTO para TODOS os leads de "${titulo}"?\n\nEsta ação não pode ser desfeita.`)) return;
  try {
    const r = await fetch('../backend/pre-lancamento.php', {
      method:'POST', credentials:'same-origin',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({acao:'disparar_lancamento', id}),
    });
    const d = await r.json();
    if (d.ok) { alert(d.mensagem); location.reload(); }
    else alert(d.erro || 'Erro ao disparar.');
  } catch { alert('Erro de conexão.'); }
}

/* ── Fechar overlay ao clicar fora ── */
['overlayForm','overlayLeads'].forEach(id => {
  document.getElementById(id)?.addEventListener('click', (e) => {
    if (e.target.id === id) e.target.classList.remove('aberto');
  });
});
</script>
