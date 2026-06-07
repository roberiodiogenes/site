<?php
/* ================================================================
   ROBÉRIO DIÓGENES — admin/crons.php
   Painel de gestão dos cron jobs de e-mail automático
   ================================================================ */

$ADMIN_PAGE = 'crons';
require_once __DIR__ . '/_admin.php';

/* Token compartilhado pelos dois crons — deve bater com o
   define('CRON_TOKEN_...') em cada arquivo de cron.        */
define('ADMIN_CRON_TOKEN', 'RD_CRON_2025_SEGURO');

/* ── Dados de status das tabelas ──────────────────────────────── */
$stats = [];
try {
    $stats['carrinho_pendentes'] = (int)$pdo->query(
        "SELECT COUNT(*) FROM carrinhos
         WHERE em_checkout=0 AND lembrete_env=0
           AND JSON_LENGTH(itens) > 0
           AND atualizado_em < DATE_SUB(NOW(), INTERVAL 1 HOUR)"
    )->fetchColumn();
} catch (Throwable $e) { $stats['carrinho_pendentes'] = null; }

try {
    $stats['carrinho_total_enviados'] = (int)$pdo->query(
        "SELECT COUNT(*) FROM carrinhos WHERE lembrete_env=1"
    )->fetchColumn();
} catch (Throwable $e) { $stats['carrinho_total_enviados'] = null; }

try {
    $stats['leitura_pendentes'] = (int)$pdo->query(
        "SELECT COUNT(*) FROM leitor_progresso p
         WHERE p.percentual BETWEEN 5 AND 98
           AND p.concluido = 0
           AND p.ultima_leitura < DATE_SUB(NOW(), INTERVAL 7 DAY)
           AND p.ultima_leitura > DATE_SUB(NOW(), INTERVAL 60 DAY)
           AND NOT EXISTS (
             SELECT 1 FROM leitor_lembretes_enviados le
             WHERE le.usuario_id = p.usuario_id
               AND le.livro_slug = p.livro_slug
               AND le.enviado_em > DATE_SUB(NOW(), INTERVAL 14 DAY)
           )"
    )->fetchColumn();
} catch (Throwable $e) { $stats['leitura_pendentes'] = null; }

try {
    $stats['leitura_total_enviados'] = (int)$pdo->query(
        "SELECT COUNT(*) FROM leitor_lembretes_enviados"
    )->fetchColumn();
} catch (Throwable $e) { $stats['leitura_total_enviados'] = null; }

/* Token de teste codificado para uso nos botões */
$tokenURL = urlencode(ADMIN_CRON_TOKEN);
$baseURL  = SITE_URL . '/backend/';

$crons = [
    [
        'id'          => 'carrinho',
        'icone'       => 'fa-cart-shopping',
        'titulo'      => 'Recuperação de Carrinho',
        'descricao'   => 'Detecta carrinhos com itens não comprados há mais de 1 hora e envia um e-mail amigável de lembrete. Cada carrinho recebe no máximo 1 lembrete.',
        'frequencia'  => 'A cada hora',
        'cron_expr'   => '0 * * * *',
        'arquivo'     => 'cron_carrinho_abandonado.php',
        'pendentes'   => $stats['carrinho_pendentes'],
        'enviados'    => $stats['carrinho_total_enviados'],
        'label_pend'  => 'Carrinhos aguardando',
        'label_env'   => 'Lembretes enviados',
        'url_teste'   => $baseURL . 'cron_carrinho_abandonado.php?token=' . $tokenURL,
        'migration'   => 'migration_crons.sql',
    ],
    [
        'id'          => 'leitura',
        'icone'       => 'fa-book-open',
        'titulo'      => 'Abandono de Leitura',
        'descricao'   => 'Identifica leitores com progresso entre 5% e 98% que não acessam o leitor há 7 dias. Envia e-mail personalizado com o progresso e link direto para o ponto de parada. Throttle de 14 dias por livro.',
        'frequencia'  => 'Uma vez por dia (9h)',
        'cron_expr'   => '0 9 * * *',
        'arquivo'     => 'cron_lembrete_leitura.php',
        'pendentes'   => $stats['leitura_pendentes'],
        'enviados'    => $stats['leitura_total_enviados'],
        'label_pend'  => 'Leitores elegíveis agora',
        'label_env'   => 'Lembretes enviados (total)',
        'url_teste'   => $baseURL . 'cron_lembrete_leitura.php?token=' . $tokenURL,
        'migration'   => 'migration_crons.sql',
    ],
];

/* Caminho do PHP na Hostgator (padrão cPanel) */
$phpPath = '/usr/bin/php';
$pubPath = '/home/USUARIO/public_html/backend/'; // lembrar de trocar USUARIO
?>

<!-- ══ CABEÇALHO ════════════════════════════════════════════════ -->
<div class="page-header">
  <h1 class="page-titulo"><i class="fa fa-robot"></i> Automações de E-mail</h1>
  <p class="page-sub">Cron jobs · disparo automático sem intervenção manual</p>
</div>

<!-- ══ AVISO DE MIGRATION ═══════════════════════════════════════ -->
<?php if ($stats['carrinho_pendentes'] === null || $stats['leitura_pendentes'] === null): ?>
<div style="background:rgba(184,134,11,.08);border:1px solid var(--borda-2);border-radius:var(--raio-lg);padding:1.25rem;margin-bottom:1.5rem;font-size:.85rem;color:var(--texto-2)">
  <i class="fa fa-triangle-exclamation" style="color:var(--ouro)"></i>
  <strong>Migration pendente.</strong> Execute <code>database/migration_crons.sql</code> no phpMyAdmin antes de ativar os crons.
</div>
<?php endif; ?>

<!-- ══ CARDS DOS CRONS ══════════════════════════════════════════ -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(420px,1fr));gap:1.25rem;margin-bottom:2rem">
  <?php foreach ($crons as $c): ?>
  <?php $ok = $c['pendentes'] !== null; ?>
  <div class="secao" style="padding:1.5rem">

    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;margin-bottom:1.25rem">
      <div style="display:flex;align-items:center;gap:.85rem">
        <div style="width:40px;height:40px;border-radius:50%;background:var(--ouro-bg);display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="fa <?= $c['icone'] ?>" style="color:var(--ouro)"></i>
        </div>
        <div>
          <div style="font-weight:600;font-size:.95rem;color:var(--texto)"><?= $c['titulo'] ?></div>
          <div style="font-size:.72rem;color:var(--texto-3)">
            <i class="fa fa-clock" style="font-size:.65rem"></i> <?= $c['frequencia'] ?>
          </div>
        </div>
      </div>
      <?php if ($ok): ?>
        <span class="badge badge-verde">Configurado</span>
      <?php else: ?>
        <span class="badge badge-amarelo">Migration pendente</span>
      <?php endif; ?>
    </div>

    <p style="font-size:.83rem;color:var(--texto-2);line-height:1.65;margin-bottom:1.25rem">
      <?= $c['descricao'] ?>
    </p>

    <!-- Stats -->
    <div style="display:flex;gap:1.5rem;padding:.85rem;background:var(--fundo-2);border-radius:var(--raio);margin-bottom:1.25rem">
      <div>
        <div style="font-size:1.5rem;font-weight:700;color:var(--ouro);line-height:1">
          <?= $c['pendentes'] !== null ? number_format($c['pendentes']) : '—' ?>
        </div>
        <div style="font-size:.62rem;text-transform:uppercase;letter-spacing:.08em;color:var(--texto-3);margin-top:.15rem">
          <?= $c['label_pend'] ?>
        </div>
      </div>
      <div>
        <div style="font-size:1.5rem;font-weight:700;color:var(--texto-2);line-height:1">
          <?= $c['enviados'] !== null ? number_format($c['enviados']) : '—' ?>
        </div>
        <div style="font-size:.62rem;text-transform:uppercase;letter-spacing:.08em;color:var(--texto-3);margin-top:.15rem">
          <?= $c['label_env'] ?>
        </div>
      </div>
    </div>

    <!-- Ações -->
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
      <?php if ($ok): ?>
      <button class="btn btn-primario btn-sm"
              onclick="confirmarTeste('<?= adm_esc($c['id']) ?>', '<?= adm_esc($c['titulo']) ?>', '<?= adm_esc($c['url_teste']) ?>')">
        <i class="fa fa-play"></i> Testar agora
      </button>
      <?php endif; ?>
      <button class="btn btn-ghost btn-sm"
              onclick="abrirModal('modal_config_<?= $c['id'] ?>')">
        <i class="fa fa-gear"></i> Ver configuração cPanel
      </button>
    </div>
  </div>

  <!-- Modal de configuração cPanel por cron -->
  <div class="modal-overlay" id="modal_config_<?= $c['id'] ?>"
       onclick="if(event.target===this)fecharModal('modal_config_<?= $c['id'] ?>')">
    <div class="modal-box" style="max-width:600px;width:95%">
      <div class="modal-titulo">
        <i class="fa fa-gear"></i> Configurar no cPanel — <?= adm_esc($c['titulo']) ?>
      </div>

      <p style="color:var(--texto-2);font-size:.85rem;margin-bottom:1.25rem;line-height:1.6">
        Acesse o cPanel da Hostgator → <strong>Cron Jobs</strong> → <em>Add New Cron Job</em>
        e preencha conforme abaixo:
      </p>

      <div class="modal-campo">
        <label>Frequência (campo "Common Settings")</label>
        <div style="font-size:.85rem;color:var(--ouro);font-weight:600"><?= $c['frequencia'] ?></div>
        <small style="color:var(--texto-3);font-size:.68rem">Ou use a expressão cron manual:</small>
        <input type="text" value="<?= $c['cron_expr'] ?>" readonly
               onclick="this.select()"
               style="font-family:monospace;font-size:.82rem;cursor:copy;margin-top:.3rem" />
      </div>

      <div class="modal-campo">
        <label>Comando</label>
        <input type="text"
               value="<?= $phpPath . ' ' . $pubPath . $c['arquivo'] ?>"
               readonly onclick="this.select()"
               style="font-family:monospace;font-size:.78rem;cursor:copy" />
        <small style="color:var(--texto-3);font-size:.68rem;margin-top:.25rem;display:block">
          Substitua <code>USUARIO</code> pelo seu usuário cPanel da Hostgator.
        </small>
      </div>

      <div class="modal-campo">
        <label>Migration necessária (execute antes de ativar)</label>
        <code style="font-size:.8rem;color:var(--ouro)">database/<?= $c['migration'] ?></code>
      </div>

      <div class="modal-btns">
        <button class="btn btn-ghost" onclick="fecharModal('modal_config_<?= $c['id'] ?>')">Fechar</button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ══ MODAL DE CONFIRMAÇÃO DE TESTE ════════════════════════════ -->
<div class="modal-overlay" id="modalTeste"
     onclick="if(event.target===this)fecharModal('modalTeste')">
  <div class="modal-box" style="max-width:460px;width:95%">
    <div class="modal-titulo"><i class="fa fa-play"></i> Executar cron agora</div>
    <p id="testeDesc" style="color:var(--texto-2);font-size:.88rem;line-height:1.65;margin-bottom:1rem"></p>
    <p style="color:var(--texto-3);font-size:.78rem;margin-bottom:1.25rem">
      <i class="fa fa-triangle-exclamation" style="color:var(--ouro)"></i>
      Em produção, e-mails reais serão enviados. Certifique-se de que o SMTP está configurado.
    </p>

    <!-- Output do cron -->
    <div id="testeOutput" style="display:none;background:var(--fundo);border:1px solid var(--borda);border-radius:var(--raio);padding:.85rem;margin-bottom:1rem;font-family:monospace;font-size:.75rem;color:var(--texto-2);max-height:200px;overflow-y:auto;white-space:pre-wrap;line-height:1.6"></div>

    <div class="modal-btns">
      <button class="btn btn-ghost" id="testeBtnCancelar" onclick="fecharModal('modalTeste')">Cancelar</button>
      <button class="btn btn-primario" id="testeBtnExec" onclick="executarTeste()">
        <i class="fa fa-play"></i> Executar
      </button>
    </div>
  </div>
</div>

<!-- ══ GUIA DE CONFIGURAÇÃO GERAL ═══════════════════════════════ -->
<div class="secao">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-book"></i> Passo a passo — Hostgator cPanel</span>
  </div>
  <div style="padding:1.25rem;color:var(--texto-2);font-size:.85rem;line-height:1.8">
    <ol style="padding-left:1.25rem;display:flex;flex-direction:column;gap:.6rem">
      <li>Execute <code style="background:var(--fundo-2);padding:.1rem .4rem;border-radius:3px">database/migration_crons.sql</code> no phpMyAdmin para criar as colunas e tabelas necessárias.</li>
      <li>No cPanel da Hostgator, acesse <strong>Cron Jobs</strong> (seção "Advanced").</li>
      <li>Clique em <strong>Add New Cron Job</strong>.</li>
      <li>Em <em>Common Settings</em>, escolha a frequência desejada (ou escreva a expressão cron manualmente).</li>
      <li>Em <em>Command</em>, cole o comando PHP correspondente ao script (botão "Ver configuração cPanel" acima).</li>
      <li>Descubra seu usuário cPanel em: <em>cPanel → Informações da conta → Nome de usuário</em>.</li>
      <li>Clique em <strong>Add New Cron Job</strong> para salvar.</li>
      <li>Use o botão <strong>Testar agora</strong> neste painel para verificar se o script executa sem erros.</li>
    </ol>
    <p style="margin-top:1rem;color:var(--texto-3);font-size:.78rem">
      <i class="fa fa-info-circle"></i>
      O token de acesso HTTP é <code style="background:var(--fundo-2);padding:.1rem .4rem;border-radius:3px"><?= ADMIN_CRON_TOKEN ?></code>.
      Troque-o nos arquivos PHP de cada cron antes de ir para produção.
    </p>
  </div>
</div>

<?php echo $ADMIN_FOOTER_HTML; ?>

<script>
let _testeURL = '';

function confirmarTeste(id, titulo, url) {
  _testeURL = url;
  document.getElementById('testeDesc').innerHTML =
    `Você está prestes a executar <strong>${titulo}</strong> manualmente. `
    + `O script processará todos os registros elegíveis agora.`;
  document.getElementById('testeOutput').style.display = 'none';
  document.getElementById('testeOutput').textContent   = '';
  document.getElementById('testeBtnExec').disabled     = false;
  document.getElementById('testeBtnExec').innerHTML    = '<i class="fa fa-play"></i> Executar';
  document.getElementById('testeBtnCancelar').textContent = 'Cancelar';
  abrirModal('modalTeste');
}

async function executarTeste() {
  const btn    = document.getElementById('testeBtnExec');
  const output = document.getElementById('testeOutput');
  const cancel = document.getElementById('testeBtnCancelar');

  btn.disabled = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Executando...';
  output.style.display = 'none';
  output.textContent   = '';

  try {
    const res  = await fetch(_testeURL, { credentials: 'include' });
    const text = await res.text();

    output.textContent   = text || '(sem output)';
    output.style.display = 'block';
    btn.innerHTML = '<i class="fa fa-check"></i> Concluído';
    cancel.textContent = 'Fechar';
    toast('Cron executado. Veja o output.', 'ok');
  } catch (err) {
    output.textContent   = 'Erro: ' + err.message;
    output.style.display = 'block';
    btn.disabled = false;
    btn.innerHTML = '<i class="fa fa-play"></i> Tentar novamente';
    toast('Erro ao executar o cron.', 'erro');
  }
}
</script>
