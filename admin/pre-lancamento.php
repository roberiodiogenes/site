<?php
/* ================================================================
   ROBÉRIO DIÓGENES — admin/pre-lancamento.php
   Gestão de campanhas de lista de espera / pré-lançamento
   ================================================================ */

$ADMIN_PAGE = 'pre-lancamento';
require_once __DIR__ . '/_admin.php';   // ← único include; já emite HTML, sidebar e abre <main>

/* ── Dados ──────────────────────────────────────────────────────── */
$campanhas = [];
$erroDB    = null;
try {
    $campanhas = $pdo->query(
        "SELECT p.*,
                (SELECT COUNT(*) FROM pre_lancamento_leads WHERE lancamento_id=p.id) AS total_leads,
                (SELECT COUNT(*) FROM pre_lancamento_leads WHERE lancamento_id=p.id AND lancamento_enviado=1) AS notificados
         FROM pre_lancamentos p ORDER BY p.id DESC"
    )->fetchAll();
} catch (Throwable $e) {
    $erroDB = 'Tabela não encontrada. Execute <code>database/migration_pre_lancamento.sql</code> no phpMyAdmin.';
}

/* Campanha selecionada para ver leads */
$campId  = (int)($_GET['id'] ?? 0);
$campSel = null;
$leads   = [];
if ($campId && !$erroDB) {
    try {
        $st = $pdo->prepare("SELECT * FROM pre_lancamentos WHERE id=? LIMIT 1");
        $st->execute([$campId]);
        $campSel = $st->fetch();
        if ($campSel) {
            $st2 = $pdo->prepare(
                "SELECT * FROM pre_lancamento_leads WHERE lancamento_id=? ORDER BY inscrito_em DESC"
            );
            $st2->execute([$campId]);
            $leads = $st2->fetchAll();
        }
    } catch (Throwable $e) { $leads = []; }
}
?>

<!-- ══ CABEÇALHO DA PÁGINA ══════════════════════════════════════ -->
<div class="page-header">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem">
    <div>
      <h1 class="page-titulo"><i class="fa fa-hourglass-start"></i> Lista de Espera</h1>
      <p class="page-sub">Campanhas de pré-lançamento · brinde imediato + notificação no lançamento</p>
    </div>
    <button class="btn btn-primario" onclick="abrirFormCampanha()">
      <i class="fa fa-plus"></i> Nova campanha
    </button>
  </div>
</div>

<!-- ══ AVISO DE MIGRATION ════════════════════════════════════════ -->
<?php if ($erroDB): ?>
<div style="background:rgba(184,134,11,.08);border:1px solid var(--borda-2);border-radius:var(--raio-lg);padding:1.25rem 1.5rem;margin-bottom:1.5rem;font-size:.85rem;color:var(--texto-2)">
  <i class="fa fa-triangle-exclamation" style="color:var(--ouro)"></i>
  <?= $erroDB ?>
</div>
<?php endif; ?>

<!-- ══ GRID DE CAMPANHAS ════════════════════════════════════════ -->
<?php if (empty($campanhas) && !$erroDB): ?>
<div class="estado-vazio" style="margin-top:3rem">
  <i class="fa fa-hourglass"></i>
  <p>Nenhuma campanha cadastrada ainda.</p>
  <button class="btn btn-primario" style="margin-top:1rem" onclick="abrirFormCampanha()">
    Criar primeira campanha
  </button>
</div>
<?php else: ?>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem;margin-bottom:2rem">
  <?php foreach ($campanhas as $c):
    $ativo   = (bool)$c['ativo'];
    $lancado = (bool)$c['lancado'];
    $total   = (int)$c['total_leads'];
    $notif   = (int)$c['notificados'];
    $pendentes = $total - $notif;
  ?>
  <div class="secao" style="position:relative;padding:1.25rem">

    <!-- Badge de status -->
    <?php if ($lancado): ?>
      <span class="badge badge-verde" style="position:absolute;top:1rem;right:1rem">Lançado</span>
    <?php elseif ($ativo): ?>
      <span class="badge badge-amarelo" style="position:absolute;top:1rem;right:1rem">Ativa</span>
    <?php else: ?>
      <span class="badge badge-cinza" style="position:absolute;top:1rem;right:1rem">Inativa</span>
    <?php endif; ?>

    <!-- Capa + título -->
    <div style="display:flex;gap:.85rem;align-items:flex-start;margin-bottom:1rem;padding-right:75px">
      <?php if ($c['capa_img']): ?>
        <img src="<?= adm_esc($c['capa_img']) ?>" alt=""
             style="width:48px;height:72px;object-fit:cover;border-radius:3px;flex-shrink:0" />
      <?php else: ?>
        <div style="width:48px;height:72px;background:var(--fundo-2);border:1px solid var(--borda);border-radius:3px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="fa fa-book" style="color:var(--texto-3);font-size:.85rem"></i>
        </div>
      <?php endif; ?>
      <div style="min-width:0">
        <div style="font-weight:600;font-size:.92rem;color:var(--texto);line-height:1.3;margin-bottom:.2rem">
          <?= adm_esc($c['titulo']) ?>
        </div>
        <?php if ($c['subtitulo']): ?>
        <div style="font-size:.75rem;color:var(--texto-3);font-style:italic;margin-bottom:.3rem">
          <?= adm_esc($c['subtitulo']) ?>
        </div>
        <?php endif; ?>
        <code style="font-size:.68rem;color:var(--texto-3)"><?= adm_esc($c['slug']) ?></code>
      </div>
    </div>

    <!-- Stats -->
    <div style="display:flex;gap:1.5rem;padding:.75rem 0;border-top:1px solid var(--borda);border-bottom:1px solid var(--borda);margin-bottom:1rem">
      <div>
        <div style="font-size:1.4rem;font-weight:700;color:var(--ouro);line-height:1"><?= $total ?></div>
        <div style="font-size:.62rem;text-transform:uppercase;letter-spacing:.08em;color:var(--texto-3)">Inscritos</div>
      </div>
      <div>
        <div style="font-size:1.4rem;font-weight:700;color:var(--texto-2);line-height:1"><?= $notif ?></div>
        <div style="font-size:.62rem;text-transform:uppercase;letter-spacing:.08em;color:var(--texto-3)">Notificados</div>
      </div>
      <?php if ($c['data_lancamento']): ?>
      <div>
        <div style="font-size:.82rem;font-weight:600;color:var(--texto-2);line-height:1.2"><?= adm_data($c['data_lancamento']) ?></div>
        <div style="font-size:.62rem;text-transform:uppercase;letter-spacing:.08em;color:var(--texto-3)">Lançamento</div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Ações -->
    <div style="display:flex;gap:.4rem;flex-wrap:wrap">
      <a href="?id=<?= $c['id'] ?>" class="btn btn-ghost btn-sm">
        <i class="fa fa-users"></i> Leads
      </a>
      <button class="btn btn-ghost btn-sm"
              onclick='abrirFormCampanha(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)'>
        <i class="fa fa-pencil"></i> Editar
      </button>
      <a href="../pre-lancamento.html?slug=<?= urlencode($c['slug']) ?>"
         target="_blank" class="btn btn-ghost btn-sm">
        <i class="fa fa-eye"></i> Ver
      </a>
      <?php if (!$lancado && $pendentes > 0): ?>
      <button class="btn btn-primario btn-sm"
              onclick="confirmarDisparo(<?= $c['id'] ?>, '<?= adm_esc($c['titulo']) ?>', <?= $pendentes ?>)">
        <i class="fa fa-rocket"></i> Disparar
      </button>
      <?php endif; ?>
      <button class="btn btn-ghost btn-sm"
              onclick="toggleAtivo(<?= $c['id'] ?>, <?= $ativo ? 0 : 1 ?>)"
              title="<?= $ativo ? 'Desativar' : 'Ativar' ?> campanha">
        <i class="fa fa-<?= $ativo ? 'eye-slash' : 'eye' ?>"></i>
      </button>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ══ LEADS DA CAMPANHA SELECIONADA ════════════════════════════ -->
<?php if ($campSel): ?>
<div class="secao">
  <div class="secao-header">
    <span class="secao-titulo">
      <i class="fa fa-users"></i>
      <?= adm_esc($campSel['titulo']) ?>
      <span style="font-size:.72rem;color:var(--texto-3);font-weight:400">(<?= count($leads) ?>)</span>
    </span>
    <div class="secao-acoes">
      <?php if ($leads): ?>
      <button class="btn btn-ghost btn-sm" onclick="exportarCSV()">
        <i class="fa fa-download"></i> CSV
      </button>
      <?php endif; ?>
      <a href="pre-lancamento.php" class="btn btn-ghost btn-sm">
        <i class="fa fa-arrow-left"></i> Voltar
      </a>
    </div>
  </div>

  <?php if (empty($leads)): ?>
  <div class="estado-vazio"><i class="fa fa-user-plus"></i><p>Nenhum inscrito ainda.</p></div>
  <?php else: ?>
  <table id="tabelaLeads">
    <thead>
      <tr>
        <th>Nome / E-mail</th>
        <th>Inscrição</th>
        <th>Brinde</th>
        <th>Notificado</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($leads as $l): ?>
      <tr>
        <td>
          <div class="td-nome"><?= adm_esc($l['nome'] ?: '—') ?></div>
          <div class="td-sub"><?= adm_esc($l['email']) ?></div>
        </td>
        <td style="font-size:.75rem;white-space:nowrap"><?= adm_data($l['inscrito_em'], 'd/m/Y H:i') ?></td>
        <td><?= $l['brinde_enviado']
              ? '<span class="badge badge-verde">Enviado</span>'
              : '<span class="badge badge-cinza">Não</span>' ?></td>
        <td><?= $l['lancamento_enviado']
              ? '<span class="badge badge-verde">Sim</span>'
              : '<span class="badge badge-cinza">Não</span>' ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- ══ MODAL: criar / editar campanha ════════════════════════════ -->
<div class="modal-overlay" id="modalCampanha"
     onclick="if(event.target===this)fecharModal('modalCampanha')">
  <div class="modal-box" style="max-width:640px;width:95%;max-height:92vh;overflow-y:auto">
    <div class="modal-titulo" id="modalCampanhaTitulo">Nova campanha</div>
    <form id="formCampanha">
      <input type="hidden" id="fcId" />

      <div class="modal-campo">
        <label for="fcTitulo">Título do livro *</label>
        <input type="text" id="fcTitulo" required maxlength="200"
               placeholder="Ex.: O Nome das Sombras" />
      </div>
      <div class="modal-campo">
        <label for="fcSubtitulo">Subtítulo / tagline</label>
        <input type="text" id="fcSubtitulo" maxlength="300"
               placeholder="Ex.: Um romance que você não vai esquecer" />
      </div>
      <div class="modal-campo">
        <label for="fcDescricao">Sinopse (exibida na página)</label>
        <textarea id="fcDescricao" rows="3"
                  placeholder="Breve sinopse para a página de pré-lançamento..."></textarea>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
        <div class="modal-campo">
          <label for="fcCapa">URL da capa</label>
          <input type="url" id="fcCapa" maxlength="500" placeholder="https://..." />
        </div>
        <div class="modal-campo">
          <label for="fcData">Data prevista de lançamento</label>
          <input type="date" id="fcData" />
        </div>
      </div>
      <div class="modal-campo">
        <label for="fcBrindeTitulo">Nome do brinde imediato</label>
        <input type="text" id="fcBrindeTitulo" maxlength="200"
               placeholder='Ex.: Primeiro capítulo exclusivo' />
        <small style="color:var(--texto-3);font-size:.68rem;margin-top:.25rem;display:block">
          Aparece na página e no assunto do e-mail enviado ao inscrito.
        </small>
      </div>
      <div class="modal-campo">
        <label for="fcBrindeHtml">Conteúdo do brinde (HTML) — enviado imediatamente ao inscrito</label>
        <textarea id="fcBrindeHtml" rows="7"
                  placeholder="Cole aqui o trecho ou nota do autor em HTML simples. Ex.:
&lt;p&gt;&lt;em&gt;O vento trazia cheiro de coisa encerrada...&lt;/em&gt;&lt;/p&gt;
Se deixar vazio, nenhum brinde é enviado."></textarea>
      </div>

      <div id="fcFeedback" style="display:none;padding:.6rem .8rem;border-radius:var(--raio);font-size:.8rem;margin-bottom:.75rem"></div>

      <div class="modal-btns">
        <button type="button" class="btn btn-ghost" onclick="fecharModal('modalCampanha')">Cancelar</button>
        <button type="submit" class="btn btn-primario" id="fcSubmit">
          <i class="fa fa-save"></i> Salvar
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ══ MODAL: confirmar disparo ══════════════════════════════════ -->
<div class="modal-overlay" id="modalDisparo"
     onclick="if(event.target===this)fecharModal('modalDisparo')">
  <div class="modal-box" style="max-width:440px;width:95%">
    <div class="modal-titulo"><i class="fa fa-rocket"></i> Confirmar disparo</div>
    <p id="disparoDesc" style="color:var(--texto-2);line-height:1.7;margin-bottom:1rem;font-size:.88rem"></p>
    <p style="color:var(--texto-3);font-size:.78rem;margin-bottom:1.25rem">
      <i class="fa fa-triangle-exclamation" style="color:var(--ouro)"></i>
      Ação irreversível — cada inscrito recebe apenas uma vez.
    </p>
    <div id="disparoFeedback" style="display:none;padding:.6rem .8rem;border-radius:var(--raio);font-size:.8rem;margin-bottom:.75rem"></div>
    <div class="modal-btns">
      <button class="btn btn-ghost" onclick="fecharModal('modalDisparo')">Cancelar</button>
      <button class="btn btn-primario" id="disparoBtn" onclick="executarDisparo()">
        <i class="fa fa-rocket"></i> Disparar agora
      </button>
    </div>
  </div>
</div>

<?php echo $ADMIN_FOOTER_HTML; ?>

<script>
/* ── Abrir / preencher modal de campanha ────────────────────── */
function abrirFormCampanha(camp) {
  const edit = !!camp;
  document.getElementById('modalCampanhaTitulo').textContent = edit ? 'Editar campanha' : 'Nova campanha';
  document.getElementById('fcId').value           = edit ? camp.id           : '';
  document.getElementById('fcTitulo').value       = edit ? camp.titulo       : '';
  document.getElementById('fcSubtitulo').value    = edit ? (camp.subtitulo   || '') : '';
  document.getElementById('fcDescricao').value    = edit ? (camp.descricao   || '') : '';
  document.getElementById('fcCapa').value         = edit ? (camp.capa_img    || '') : '';
  document.getElementById('fcData').value         = edit ? (camp.data_lancamento || '') : '';
  document.getElementById('fcBrindeTitulo').value = edit ? (camp.brinde_titulo || '') : '';
  document.getElementById('fcBrindeHtml').value   = edit ? (camp.brinde_html  || '') : '';
  document.getElementById('fcFeedback').style.display = 'none';
  abrirModal('modalCampanha');   // usa a função global do _admin.php
  document.getElementById('fcTitulo').focus();
}

/* ── Salvar campanha ────────────────────────────────────────── */
document.getElementById('formCampanha').addEventListener('submit', async function(e) {
  e.preventDefault();
  const id  = document.getElementById('fcId').value;
  const btn = document.getElementById('fcSubmit');
  const fb  = document.getElementById('fcFeedback');

  btn.disabled = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Salvando...';
  fb.style.display = 'none';

  try {
    const res  = await fetch('../backend/pre-lancamento.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        acao:            id ? 'editar' : 'criar',
        id:              id ? parseInt(id) : undefined,
        titulo:          document.getElementById('fcTitulo').value.trim(),
        subtitulo:       document.getElementById('fcSubtitulo').value.trim(),
        descricao:       document.getElementById('fcDescricao').value.trim(),
        capa_img:        document.getElementById('fcCapa').value.trim(),
        data_lancamento: document.getElementById('fcData').value || null,
        brinde_titulo:   document.getElementById('fcBrindeTitulo').value.trim(),
        brinde_html:     document.getElementById('fcBrindeHtml').value.trim(),
      }),
    });
    const json = await res.json();
    if (!json.ok) throw new Error(json.erro || 'Erro desconhecido.');
    location.reload();
  } catch (err) {
    fb.style.background = 'rgba(192,57,43,.1)';
    fb.style.color      = '#e74c3c';
    fb.style.border     = '1px solid rgba(192,57,43,.25)';
    fb.textContent = err.message;
    fb.style.display = 'block';
    btn.disabled = false;
    btn.innerHTML = '<i class="fa fa-save"></i> Salvar';
  }
});

/* ── Toggle ativo ───────────────────────────────────────────── */
async function toggleAtivo(id, ativo) {
  try {
    await fetch('../backend/pre-lancamento.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ acao: 'toggle', id, ativo }),
    });
    location.reload();
  } catch { toast('Erro ao atualizar. Tente novamente.', 'erro'); }
}

/* ── Disparo de lançamento ──────────────────────────────────── */
let _disparoId = 0;
function confirmarDisparo(id, titulo, pendentes) {
  _disparoId = id;
  document.getElementById('disparoDesc').innerHTML =
    `Você está prestes a enviar o <strong>e-mail de lançamento</strong> de <em>${titulo}</em> `
    + `para <strong>${pendentes} inscrito(s)</strong> que ainda não foram notificados.`;
  document.getElementById('disparoFeedback').style.display = 'none';
  document.getElementById('disparoBtn').disabled = false;
  document.getElementById('disparoBtn').innerHTML = '<i class="fa fa-rocket"></i> Disparar agora';
  abrirModal('modalDisparo');
}

async function executarDisparo() {
  const btn = document.getElementById('disparoBtn');
  const fb  = document.getElementById('disparoFeedback');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Enviando...';
  fb.style.display = 'none';

  try {
    const res  = await fetch('../backend/pre-lancamento.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ acao: 'disparar_lancamento', id: _disparoId }),
    });
    const json = await res.json();
    if (!json.ok) throw new Error(json.erro || 'Erro no disparo.');

    fb.style.background = 'rgba(46,125,50,.1)';
    fb.style.color      = '#4CAF50';
    fb.style.border     = '1px solid rgba(46,125,50,.25)';
    fb.textContent = json.mensagem;
    fb.style.display = 'block';
    btn.innerHTML = '<i class="fa fa-check"></i> Enviado!';
    setTimeout(() => location.reload(), 2500);
  } catch (err) {
    fb.style.background = 'rgba(192,57,43,.1)';
    fb.style.color      = '#e74c3c';
    fb.style.border     = '1px solid rgba(192,57,43,.25)';
    fb.textContent = err.message;
    fb.style.display = 'block';
    btn.disabled = false;
    btn.innerHTML = '<i class="fa fa-rocket"></i> Tentar novamente';
  }
}

/* ── Exportar CSV ───────────────────────────────────────────── */
function exportarCSV() {
  const rows = [['Nome','E-mail','Inscrição','Brinde enviado','Notificado']];
  document.querySelectorAll('#tabelaLeads tbody tr').forEach(tr => {
    const tds = tr.querySelectorAll('td');
    rows.push([
      tds[0]?.querySelector('.td-nome')?.textContent?.trim() || '',
      tds[0]?.querySelector('.td-sub')?.textContent?.trim()  || '',
      tds[1]?.textContent?.trim() || '',
      tds[2]?.textContent?.trim() || '',
      tds[3]?.textContent?.trim() || '',
    ]);
  });
  const csv  = rows.map(r => r.map(v => `"${v.replace(/"/g,'""')}"`).join(',')).join('\n');
  const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url; a.download = 'lista-espera.csv'; a.click();
  URL.revokeObjectURL(url);
}
</script>
