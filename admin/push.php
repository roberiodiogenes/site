<?php
/* ================================================================
   ROBÉRIO DIÓGENES — admin/push.php
   Painel de gerenciamento de notificações push (OneSignal)
   ================================================================ */

$ADMIN_PAGE = 'push';
require_once __DIR__ . '/_admin.php';
require_once __DIR__ . '/../backend/push.php'; // carrega PushNotification

/* ── Dados de stats ─────────────────────────────────────────── */
$stats        = PushNotification::stats();
$configurado  = (ONESIGNAL_APP_ID !== 'SEU_ONESIGNAL_APP_ID');
$subscribers  = $configurado ? ($stats['subscribers'] ?? 0) : null;

/* ── Histórico de envios (últimas campanhas da tabela de campanhas) ─ */
$historico = [];
try {
    $historico = $pdo->query(
        "SELECT nome, assunto_email AS assunto, segmento, status, criado_em, n_envios
         FROM campanhas WHERE tipo='push' ORDER BY criado_em DESC LIMIT 20"
    )->fetchAll();
} catch (Throwable $e) { /* tabela ainda não tem tipo=push */ }

/* Segmentos disponíveis */
$segmentos = [
    'todos'          => 'Todos os subscribers',
    'leitor'         => 'Leitores (leitor online)',
    'blog'           => 'Leitores do Diário',
    'livro'          => 'Visitantes da biblioteca',
    'home'           => 'Visitantes da home',
    'pre_lancamento' => 'Lista de espera',
];
?>

<!-- ══ CABEÇALHO ════════════════════════════════════════════════ -->
<div class="page-header">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem">
    <div>
      <h1 class="page-titulo"><i class="fa fa-bell"></i> Notificações Push</h1>
      <p class="page-sub">Envio via OneSignal · engajamento em tempo real</p>
    </div>
    <?php if ($configurado): ?>
    <button class="btn btn-primario" onclick="abrirModal('modalPush')">
      <i class="fa fa-paper-plane"></i> Enviar notificação
    </button>
    <?php endif; ?>
  </div>
</div>

<!-- ══ AVISO DE CONFIGURAÇÃO ════════════════════════════════════ -->
<?php if (!$configurado): ?>
<div style="background:rgba(184,134,11,.08);border:1px solid var(--borda-2);border-radius:var(--raio-lg);padding:1.5rem;margin-bottom:1.5rem">
  <div style="font-size:.95rem;font-weight:600;color:var(--ouro);margin-bottom:.75rem">
    <i class="fa fa-gear"></i> Configuração necessária
  </div>
  <p style="color:var(--texto-2);font-size:.85rem;line-height:1.7;margin-bottom:1rem">
    Para ativar as notificações push, preencha as credenciais em <code>backend/push.php</code>
    e o App ID em <code>js/push-notifications.js</code>.
  </p>
  <ol style="color:var(--texto-2);font-size:.83rem;line-height:2;padding-left:1.25rem">
    <li>Acesse <strong>onesignal.com</strong> → Create account → New App → Web</li>
    <li>Site URL: <code>https://roberiodiogenes.com</code> · Ícone: <code>img/favicon.png</code></li>
    <li>Copie o <strong>App ID</strong> e a <strong>REST API Key</strong> (Settings → Keys & IDs)</li>
    <li>Em <code>backend/push.php</code>: cole nas constantes <code>ONESIGNAL_APP_ID</code> e <code>ONESIGNAL_REST_API_KEY</code></li>
    <li>Em <code>js/push-notifications.js</code>: cole o App ID na constante <code>RD_ONESIGNAL_APP_ID</code></li>
    <li>Certifique-se de que os arquivos <code>OneSignalSDKWorker.js</code> e <code>OneSignalSDKUpdaterWorker.js</code> estão na raiz do site</li>
  </ol>
</div>
<?php endif; ?>

<!-- ══ STATS ════════════════════════════════════════════════════ -->
<div class="stats-grade" style="margin-bottom:1.75rem">
  <div class="stat-card">
    <div class="stat-icone"><i class="fa fa-users"></i></div>
    <div>
      <div class="stat-valor"><?= $subscribers !== null ? number_format($subscribers) : '—' ?></div>
      <div class="stat-label">Subscribers ativos</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icone"><i class="fa fa-paper-plane"></i></div>
    <div>
      <div class="stat-valor"><?= count($historico) ?></div>
      <div class="stat-label">Envios registrados</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icone"><i class="fa fa-circle-check" style="color:<?= $configurado ? '#4CAF50' : 'var(--texto-3)' ?>"></i></div>
    <div>
      <div class="stat-valor" style="font-size:1rem;margin-top:.2rem">
        <?= $configurado ? 'Ativo' : 'Não configurado' ?>
      </div>
      <div class="stat-label">Status OneSignal</div>
    </div>
  </div>
  <?php if ($configurado): ?>
  <div class="stat-card" style="cursor:pointer" onclick="carregarStats()" title="Atualizar stats do OneSignal">
    <div class="stat-icone"><i class="fa fa-rotate"></i></div>
    <div>
      <div class="stat-valor" id="statSubscribers"><?= number_format($subscribers) ?></div>
      <div class="stat-label">Subscribers (live)</div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- ══ SEGMENTOS DISPONÍVEIS ════════════════════════════════════ -->
<h2 style="font-family:Georgia,serif;font-size:1rem;color:var(--ouro);margin-bottom:.85rem;letter-spacing:.05em">
  <i class="fa fa-tag"></i> Segmentos disponíveis
</h2>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.75rem;margin-bottom:2rem">
  <?php foreach ($segmentos as $seg => $label): ?>
  <div class="stat-card" style="flex-direction:column;align-items:flex-start;gap:.3rem;cursor:pointer"
       onclick="preencherSegmento('<?= $seg ?>')"
       title="Usar este segmento no próximo envio">
    <div style="font-size:.72rem;font-weight:700;color:var(--ouro)"><?= adm_esc($label) ?></div>
    <div style="font-size:.65rem;color:var(--texto-3)"><code><?= $seg ?></code></div>
    <div style="font-size:.62rem;color:var(--ouro);opacity:.6;margin-top:.2rem">
      <i class="fa fa-paper-plane" style="font-size:.55rem"></i> Usar no envio
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ══ GUIA DE TIMING DE OPT-IN ═════════════════════════════════ -->
<div class="secao" style="margin-bottom:1.5rem">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-clock"></i> Timing de opt-in configurado</span>
  </div>
  <table>
    <thead><tr><th>Página</th><th>Trigger</th><th>Lógica</th></tr></thead>
    <tbody>
      <tr><td>Home / Biblioteca</td><td><span class="badge badge-amarelo">90 segundos</span></td><td style="font-size:.78rem;color:var(--texto-3)">Após 90s na página sem interação</td></tr>
      <tr><td>Blog (lista)</td><td><span class="badge badge-amarelo">50% scroll</span></td><td style="font-size:.78rem;color:var(--texto-3)">Ao rolar metade da página de posts</td></tr>
      <tr><td>Post (artigo)</td><td><span class="badge badge-amarelo">60% scroll ou 60s</span></td><td style="font-size:.78rem;color:var(--texto-3)">Quem chegou até aqui, gostou do conteúdo</td></tr>
      <tr><td>Leitor online</td><td><span class="badge badge-amarelo">10% de progresso</span></td><td style="font-size:.78rem;color:var(--texto-3)">Chamado pelo leitor.js via <code>rdPushLeitorProgresso()</code></td></tr>
      <tr><td>Pré-lançamento</td><td><span class="badge badge-amarelo">Após inscrição</span></td><td style="font-size:.78rem;color:var(--texto-3)">Chamado pelo pre-lancamento.html via <code>rdPushPrompt()</code></td></tr>
    </tbody>
  </table>
</div>

<!-- ══ HISTÓRICO DE ENVIOS ═══════════════════════════════════════ -->
<?php if ($historico): ?>
<div class="secao">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-history"></i> Histórico de envios</span>
  </div>
  <table>
    <thead><tr><th>Notificação</th><th>Segmento</th><th>Status</th><th>Enviado em</th></tr></thead>
    <tbody>
      <?php foreach ($historico as $h): ?>
      <tr>
        <td>
          <div class="td-nome"><?= adm_esc($h['nome']) ?></div>
          <div class="td-sub"><?= adm_esc(mb_substr($h['assunto'] ?? '', 0, 60)) ?></div>
        </td>
        <td style="font-size:.75rem"><code><?= adm_esc($h['segmento'] ?? 'todos') ?></code></td>
        <td><?= adm_badge($h['status'] ?? 'enviada') ?></td>
        <td style="font-size:.75rem;white-space:nowrap"><?= adm_data($h['criado_em'], 'd/m/Y H:i') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<div class="estado-vazio">
  <i class="fa fa-bell-slash"></i>
  <p>Nenhum push enviado ainda.</p>
</div>
<?php endif; ?>

<!-- ══ MODAL DE ENVIO ════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalPush"
     onclick="if(event.target===this)fecharModal('modalPush')">
  <div class="modal-box" style="max-width:520px;width:95%">
    <div class="modal-titulo"><i class="fa fa-bell"></i> Enviar notificação push</div>

    <form id="formPush">
      <div class="modal-campo">
        <label for="pushTitulo">Título *</label>
        <input type="text" id="pushTitulo" required maxlength="100"
               placeholder='Ex.: "Um novo mistério se desvenda..."' />
        <small style="color:var(--texto-3);font-size:.68rem">Máx. 100 caracteres</small>
      </div>

      <div class="modal-campo">
        <label for="pushMensagem">Mensagem *</label>
        <textarea id="pushMensagem" rows="3" required maxlength="200"
                  placeholder='Ex.: "O Capítulo 5 de Lúmen já está disponível!"'></textarea>
        <small style="color:var(--texto-3);font-size:.68rem">Máx. 200 caracteres · <span id="pushCharCount">0</span>/200</small>
      </div>

      <div class="modal-campo">
        <label for="pushURL">URL de destino</label>
        <input type="url" id="pushURL"
               placeholder="<?= SITE_URL ?>/blog.html" />
        <small style="color:var(--texto-3);font-size:.68rem">Deixe vazio para usar a home</small>
      </div>

      <div class="modal-campo">
        <label for="pushImagem">URL da imagem (opcional)</label>
        <input type="url" id="pushImagem"
               placeholder="<?= SITE_URL ?>/img/lumen.jpg" />
        <small style="color:var(--texto-3);font-size:.68rem">Recomendado: capa do livro ou imagem do post</small>
      </div>

      <div class="modal-campo">
        <label for="pushSegmento">Segmento</label>
        <select id="pushSegmento">
          <?php foreach ($segmentos as $seg => $label): ?>
          <option value="<?= $seg ?>"><?= adm_esc($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="pushFeedback" style="display:none;padding:.6rem .8rem;border-radius:var(--raio);font-size:.8rem;margin-bottom:.75rem"></div>

      <div class="modal-btns">
        <button type="button" class="btn btn-ghost" onclick="fecharModal('modalPush')">Cancelar</button>
        <button type="submit" class="btn btn-primario" id="pushBtnEnviar">
          <i class="fa fa-paper-plane"></i> Enviar agora
        </button>
      </div>
    </form>
  </div>
</div>

<?php echo $ADMIN_FOOTER_HTML; ?>

<script>
/* ── Contador de caracteres ───────────────────────────────── */
document.getElementById('pushMensagem').addEventListener('input', function () {
  document.getElementById('pushCharCount').textContent = this.value.length;
});

/* ── Preencher segmento a partir dos cards ──────────────── */
function preencherSegmento(seg) {
  document.getElementById('pushSegmento').value = seg;
  abrirModal('modalPush');
}

/* ── Enviar push ────────────────────────────────────────── */
document.getElementById('formPush').addEventListener('submit', async function (e) {
  e.preventDefault();
  const btn = document.getElementById('pushBtnEnviar');
  const fb  = document.getElementById('pushFeedback');

  btn.disabled = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Enviando...';
  fb.style.display = 'none';

  try {
    const res  = await fetch('../backend/push.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({
        acao:      'enviar',
        titulo:    document.getElementById('pushTitulo').value.trim(),
        mensagem:  document.getElementById('pushMensagem').value.trim(),
        url:       document.getElementById('pushURL').value.trim() || null,
        imagem:    document.getElementById('pushImagem').value.trim() || null,
        segmento:  document.getElementById('pushSegmento').value,
      }),
    });
    const json = await res.json();

    if (!json.ok) throw new Error(json.erro || 'Erro ao enviar.');

    fb.style.cssText = 'display:block;background:rgba(46,125,50,.1);color:#4CAF50;border:1px solid rgba(46,125,50,.25);padding:.6rem .8rem;border-radius:var(--raio);font-size:.8rem';
    fb.textContent = `Enviado para ${json.total ?? 0} subscriber(s)! ID: ${json.id ?? '—'}`;
    btn.innerHTML = '<i class="fa fa-check"></i> Enviado!';
    toast('Push enviado com sucesso!', 'ok');
    setTimeout(() => location.reload(), 3000);

  } catch (err) {
    fb.style.cssText = 'display:block;background:rgba(192,57,43,.1);color:#e74c3c;border:1px solid rgba(192,57,43,.25);padding:.6rem .8rem;border-radius:var(--raio);font-size:.8rem';
    fb.textContent = err.message;
    btn.disabled = false;
    btn.innerHTML = '<i class="fa fa-paper-plane"></i> Enviar agora';
  }
});

/* ── Stats live ─────────────────────────────────────────── */
async function carregarStats() {
  try {
    const res  = await fetch('../backend/push.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ acao: 'stats' }),
    });
    const json = await res.json();
    if (json.ok) {
      const el = document.getElementById('statSubscribers');
      if (el) el.textContent = json.subscribers?.toLocaleString('pt-BR') ?? '0';
    }
  } catch (e) {}
}
</script>
