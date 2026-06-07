<?php
/* ================================================================
   ROBÉRIO DIÓGENES — admin/configuracoes.php
   Painel de Configurações do site
   ================================================================ */
$ADMIN_PAGE = 'configuracoes';
require_once __DIR__ . '/_admin.php';

/* ── Carrega todas as configurações ────────────────────────────── */
$cfg = [];
try {
    foreach ($pdo->query("SELECT chave, valor FROM configuracoes") as $r) {
        $cfg[$r['chave']] = $r['valor'];
    }
} catch (Throwable $e) {
    // Tabela ainda não criada — exibe alerta
    $cfg = [];
    $semMigration = true;
}

/* ── Carrega temas sazonais ─────────────────────────────────────── */
$temas = [];
$temaAtivoHoje = null;
try {
    $temas = $pdo->query(
        "SELECT * FROM temas_sazonais ORDER BY prioridade ASC, mes_inicio ASC, dia_inicio ASC"
    )->fetchAll();
    foreach ($temas as $t) {
        if ((int)$t['ativo'] !== 1) continue;
        $hoje = (int)date('nd');
        $ini  = (int)($t['mes_inicio'] . sprintf('%02d', $t['dia_inicio']));
        $fim  = (int)($t['mes_fim']    . sprintf('%02d', $t['dia_fim']));
        $ativo = ($ini <= $fim) ? ($hoje >= $ini && $hoje <= $fim) : ($hoje >= $ini || $hoje <= $fim);
        if ($ativo) { $temaAtivoHoje = $t; break; }
    }
} catch (Throwable $e) { $temas = []; }

/* ── Info do sistema ────────────────────────────────────────────── */
$phpVer   = PHP_VERSION;
$mysqlVer = $pdo->query("SELECT VERSION()")->fetchColumn();
$ambiente = AMBIENTE;
$siteUrl  = SITE_URL;

/* ── Nomes dos meses ─────────────────────────────────────────────  */
$meses = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

/* ── Helper: pega config com fallback ───────────────────────────── */
function c(array $cfg, string $k, string $pad = ''): string {
    return htmlspecialchars($cfg[$k] ?? $pad, ENT_QUOTES, 'UTF-8');
}
?>

<style>
  /* ── Abas ──────────────────────────────────────────────────── */
  .abas-wrap  { display:flex; gap:0; border-bottom:1px solid var(--borda); margin-bottom:1.5rem; flex-wrap:wrap; }
  .aba-btn    { padding:.55rem 1rem; font-size:.72rem; font-weight:600; letter-spacing:.07em;
                text-transform:uppercase; background:none; border:none; border-bottom:2px solid transparent;
                color:var(--texto-3); cursor:pointer; transition:all .15s; white-space:nowrap; }
  .aba-btn:hover   { color:var(--ouro); }
  .aba-btn.ativa   { color:var(--ouro); border-bottom-color:var(--ouro); }
  .aba-painel      { display:none; }
  .aba-painel.ativa{ display:block; }

  /* ── Formulário de config ───────────────────────────────────── */
  .cfg-grid   { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
  .cfg-grupo  { background:var(--fundo-card); border:1px solid var(--borda); border-radius:var(--raio-lg); padding:1.25rem; }
  .cfg-grupo-titulo { font-size:.65rem; letter-spacing:.15em; text-transform:uppercase; color:var(--ouro); margin-bottom:1rem; display:flex; align-items:center; gap:.5rem; }
  .cfg-campo  { margin-bottom:.85rem; }
  .cfg-campo:last-child { margin-bottom:0; }
  .cfg-label  { display:block; font-size:.65rem; letter-spacing:.08em; text-transform:uppercase; color:var(--texto-3); margin-bottom:.35rem; }
  .cfg-input, .cfg-select, .cfg-textarea {
    width:100%; padding:.55rem .75rem; background:var(--fundo-input);
    border:1px solid var(--borda); border-radius:var(--raio);
    color:var(--texto); font-size:.83rem; transition:border-color .2s;
    font-family:inherit;
  }
  .cfg-input:focus, .cfg-select:focus, .cfg-textarea:focus { outline:none; border-color:var(--ouro); }
  .cfg-textarea { resize:vertical; min-height:80px; }
  .cfg-hint   { font-size:.62rem; color:var(--texto-3); margin-top:.3rem; }
  .cfg-toggle { display:flex; align-items:center; gap:.6rem; }
  .cfg-switch { position:relative; display:inline-block; width:38px; height:22px; flex-shrink:0; }
  .cfg-switch input { opacity:0; width:0; height:0; }
  .cfg-switch-track {
    position:absolute; inset:0; background:rgba(255,255,255,.1);
    border-radius:22px; cursor:pointer; transition:.2s;
    border:1px solid var(--borda);
  }
  .cfg-switch-track::before {
    content:''; position:absolute; left:3px; top:3px;
    width:14px; height:14px; background:var(--texto-3);
    border-radius:50%; transition:.2s;
  }
  .cfg-switch input:checked + .cfg-switch-track { background:var(--ouro); border-color:var(--ouro); }
  .cfg-switch input:checked + .cfg-switch-track::before { transform:translateX(16px); background:#fff; }
  .cfg-toggle-label { font-size:.83rem; color:var(--texto-2); }

  /* ── Temas sazonais ─────────────────────────────────────────── */
  .temas-grade { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:.85rem; }
  .tema-card   {
    background:var(--fundo-card); border:1px solid var(--borda);
    border-radius:var(--raio-lg); padding:1rem; position:relative;
    transition:border-color .2s;
  }
  .tema-card.hoje { border-color:var(--ouro); }
  .tema-card-header { display:flex; align-items:flex-start; justify-content:space-between; gap:.5rem; margin-bottom:.75rem; }
  .tema-card-nome { font-size:.82rem; font-weight:600; color:var(--texto); line-height:1.3; }
  .tema-card-badge-ativo { font-size:.55rem; background:rgba(46,125,50,.2); color:#4CAF50;
    border:1px solid rgba(46,125,50,.3); border-radius:20px; padding:.1rem .45rem; white-space:nowrap; }
  .tema-card-badge-hoje  { font-size:.55rem; background:rgba(184,134,11,.2); color:var(--ouro);
    border:1px solid var(--borda-2); border-radius:20px; padding:.1rem .45rem; white-space:nowrap; }
  .tema-card-inativo { opacity:.45; }
  .tema-card-data  { font-size:.68rem; color:var(--texto-3); margin-bottom:.75rem; display:flex; align-items:center; gap:.35rem; }
  .tema-cores { display:flex; gap:.4rem; margin-bottom:.85rem; }
  .tema-cor   { width:28px; height:28px; border-radius:50%; border:2px solid rgba(255,255,255,.15); cursor:default; }
  .tema-acoes { display:flex; gap:.4rem; flex-wrap:wrap; }

  /* ── Sistemas info ──────────────────────────────────────────── */
  .sys-grade  { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
  .sys-item   { background:var(--fundo-2); border:1px solid var(--borda); border-radius:var(--raio); padding:.75rem 1rem; }
  .sys-label  { font-size:.6rem; letter-spacing:.12em; text-transform:uppercase; color:var(--texto-3); margin-bottom:.25rem; }
  .sys-valor  { font-size:.88rem; color:var(--texto); font-family:monospace; }

  /* ── Alerta migração ─────────────────────────────────────────── */
  .alerta-migracao {
    background:rgba(192,57,43,.12); border:1px solid rgba(192,57,43,.4);
    border-radius:var(--raio-lg); padding:1rem 1.25rem;
    color:#e74c3c; font-size:.82rem; margin-bottom:1.25rem;
    display:flex; align-items:flex-start; gap:.75rem;
  }

  /* ── Barra salvar ────────────────────────────────────────────── */
  .barra-salvar { display:flex; justify-content:flex-end; padding-top:1rem; border-top:1px solid var(--borda); margin-top:1rem; }

  @media(max-width:768px){
    .cfg-grid { grid-template-columns:1fr; }
    .sys-grade{ grid-template-columns:1fr; }
    .temas-grade{ grid-template-columns:1fr; }
  }
</style>

<!-- ── Cabeçalho ──────────────────────────────────────────────── -->
<div class="page-header">
  <h1 class="page-titulo"><i class="fa fa-sliders"></i> Configurações</h1>
  <p class="page-sub">Gerencie as preferências globais do site</p>
</div>

<?php if (!empty($semMigration)): ?>
<div class="alerta-migracao">
  <i class="fa fa-triangle-exclamation" style="margin-top:.1rem;font-size:1rem"></i>
  <div>
    <strong>Migration necessária.</strong> Execute o arquivo
    <code>database/migration_configuracoes.sql</code> no phpMyAdmin antes de usar esta página.
  </div>
</div>
<?php endif; ?>

<!-- ── Abas ───────────────────────────────────────────────────── -->
<div class="abas-wrap" role="tablist">
  <button class="aba-btn ativa" data-aba="site"    role="tab"><i class="fa fa-globe"></i> Site</button>
  <button class="aba-btn"       data-aba="acesso"  role="tab"><i class="fa fa-lock"></i> Acesso</button>
  <button class="aba-btn"       data-aba="uploads" role="tab"><i class="fa fa-upload"></i> Uploads</button>
  <button class="aba-btn"       data-aba="email"   role="tab"><i class="fa fa-envelope"></i> E-mail</button>
  <button class="aba-btn"       data-aba="seo"     role="tab"><i class="fa fa-chart-simple"></i> SEO & Digital</button>
  <button class="aba-btn"       data-aba="temas"   role="tab"><i class="fa fa-palette"></i> Temas Sazonais
    <?php if ($temaAtivoHoje): ?>
      <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--ouro);margin-left:.35rem;vertical-align:middle;"></span>
    <?php endif; ?>
  </button>
  <button class="aba-btn"       data-aba="sistema" role="tab"><i class="fa fa-microchip"></i> Sistema</button>
</div>

<!-- ══════════════════════════════════════════════════════════════
     ABA: SITE
     ══════════════════════════════════════════════════════════════ -->
<div class="aba-painel ativa" id="aba-site">
  <div class="cfg-grade-wrap cfg-grid">

    <div class="cfg-grupo">
      <div class="cfg-grupo-titulo"><i class="fa fa-id-card"></i> Identidade</div>

      <div class="cfg-campo">
        <label class="cfg-label" for="site_nome">Nome do site</label>
        <input class="cfg-input" id="site_nome" name="site_nome" value="<?= c($cfg,'site_nome') ?>" />
      </div>
      <div class="cfg-campo">
        <label class="cfg-label" for="site_slogan">Slogan / subtítulo</label>
        <input class="cfg-input" id="site_slogan" name="site_slogan" value="<?= c($cfg,'site_slogan') ?>" />
      </div>
      <div class="cfg-campo">
        <label class="cfg-label" for="site_copyright">Texto de copyright (rodapé)</label>
        <input class="cfg-input" id="site_copyright" name="site_copyright"
               value="<?= c($cfg,'site_copyright') ?>"
               placeholder="Ex: © 2025 Robério Diógenes. Todos os direitos reservados." />
        <p class="cfg-hint">Deixe em branco para usar o padrão automático com ano atual.</p>
      </div>
    </div>

  </div>
  <div class="barra-salvar">
    <button class="btn btn-primario" onclick="salvarAba('site')">
      <i class="fa fa-floppy-disk"></i> Salvar alterações
    </button>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     ABA: ACESSO & SEGURANÇA
     ══════════════════════════════════════════════════════════════ -->
<div class="aba-painel" id="aba-acesso">
  <div class="cfg-grade-wrap cfg-grid">

    <div class="cfg-grupo">
      <div class="cfg-grupo-titulo"><i class="fa fa-user-plus"></i> Cadastros</div>
      <div class="cfg-campo">
        <div class="cfg-toggle">
          <label class="cfg-switch">
            <input type="checkbox" id="permitir_cadastros" <?= ($cfg['permitir_cadastros'] ?? '1') == '1' ? 'checked' : '' ?>>
            <span class="cfg-switch-track"></span>
          </label>
          <span class="cfg-toggle-label">Permitir novos cadastros de leitores</span>
        </div>
        <p class="cfg-hint">Desative para bloquear o registro de novos usuários.</p>
      </div>
    </div>

    <div class="cfg-grupo">
      <div class="cfg-grupo-titulo"><i class="fa fa-triangle-exclamation"></i> Manutenção</div>
      <div class="cfg-campo">
        <div class="cfg-toggle">
          <label class="cfg-switch">
            <input type="checkbox" id="modo_manutencao" <?= ($cfg['modo_manutencao'] ?? '0') == '1' ? 'checked' : '' ?>>
            <span class="cfg-switch-track"></span>
          </label>
          <span class="cfg-toggle-label">Ativar modo de manutenção</span>
        </div>
        <p class="cfg-hint">Visitantes verão a mensagem abaixo. Admins continuam acessando normalmente.</p>
      </div>
      <div class="cfg-campo">
        <label class="cfg-label" for="mensagem_manutencao">Mensagem de manutenção</label>
        <textarea class="cfg-textarea" id="mensagem_manutencao" name="mensagem_manutencao"><?= c($cfg,'mensagem_manutencao','Site em manutenção. Voltamos em breve.') ?></textarea>
      </div>
    </div>

  </div>
  <div class="barra-salvar">
    <button class="btn btn-primario" onclick="salvarAba('acesso')">
      <i class="fa fa-floppy-disk"></i> Salvar alterações
    </button>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     ABA: UPLOADS
     ══════════════════════════════════════════════════════════════ -->
<div class="aba-painel" id="aba-uploads">
  <div class="cfg-grade-wrap cfg-grid">

    <div class="cfg-grupo">
      <div class="cfg-grupo-titulo"><i class="fa fa-image"></i> Imagens</div>
      <div class="cfg-campo">
        <label class="cfg-label" for="upload_tamanho_max_kb">Tamanho máximo (KB)</label>
        <input class="cfg-input" type="number" id="upload_tamanho_max_kb" min="128" max="10240"
               value="<?= c($cfg,'upload_tamanho_max_kb','2048') ?>" />
        <p class="cfg-hint">Limite atual: <?= number_format((int)($cfg['upload_tamanho_max_kb'] ?? 2048) / 1024, 1) ?> MB. Máximo recomendado: 5 MB (5120 KB).</p>
      </div>
      <div class="cfg-campo">
        <label class="cfg-label" for="upload_formatos_imagem">Formatos aceitos — imagens</label>
        <input class="cfg-input" id="upload_formatos_imagem"
               value="<?= c($cfg,'upload_formatos_imagem','jpg,jpeg,png,webp') ?>" />
        <p class="cfg-hint">Separados por vírgula, sem espaço. Ex: <code>jpg,jpeg,png,webp</code></p>
      </div>
    </div>

    <div class="cfg-grupo">
      <div class="cfg-grupo-titulo"><i class="fa fa-file"></i> Documentos</div>
      <div class="cfg-campo">
        <label class="cfg-label" for="upload_formatos_doc">Formatos aceitos — documentos</label>
        <input class="cfg-input" id="upload_formatos_doc"
               value="<?= c($cfg,'upload_formatos_doc','pdf,epub') ?>" />
        <p class="cfg-hint">Ex: <code>pdf,epub</code></p>
      </div>
    </div>

  </div>
  <div class="barra-salvar">
    <button class="btn btn-primario" onclick="salvarAba('uploads')">
      <i class="fa fa-floppy-disk"></i> Salvar alterações
    </button>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     ABA: E-MAIL & NOTIFICAÇÕES
     ══════════════════════════════════════════════════════════════ -->
<div class="aba-painel" id="aba-email">
  <div class="cfg-grade-wrap cfg-grid">

    <div class="cfg-grupo">
      <div class="cfg-grupo-titulo"><i class="fa fa-server"></i> Servidor SMTP</div>
      <div class="cfg-campo">
        <label class="cfg-label" for="smtp_host">Host SMTP</label>
        <input class="cfg-input" id="smtp_host" placeholder="smtp.gmail.com"
               value="<?= c($cfg,'smtp_host') ?>" />
      </div>
      <div class="cfg-campo">
        <label class="cfg-label" for="smtp_porta">Porta</label>
        <input class="cfg-input" type="number" id="smtp_porta"
               value="<?= c($cfg,'smtp_porta','587') ?>" />
        <p class="cfg-hint">587 (TLS) ou 465 (SSL). Evite 25 em produção.</p>
      </div>
      <div class="cfg-campo">
        <label class="cfg-label" for="smtp_criptografia">Criptografia</label>
        <select class="cfg-select" id="smtp_criptografia">
          <option value="tls"  <?= ($cfg['smtp_criptografia']??'tls')==='tls'  ? 'selected':'' ?>>TLS (recomendado)</option>
          <option value="ssl"  <?= ($cfg['smtp_criptografia']??'')==='ssl'  ? 'selected':'' ?>>SSL</option>
          <option value="none" <?= ($cfg['smtp_criptografia']??'')==='none' ? 'selected':'' ?>>Nenhuma</option>
        </select>
      </div>
      <div class="cfg-campo">
        <label class="cfg-label" for="smtp_usuario">Usuário / e-mail</label>
        <input class="cfg-input" id="smtp_usuario" autocomplete="off"
               value="<?= c($cfg,'smtp_usuario') ?>" />
      </div>
      <div class="cfg-campo">
        <label class="cfg-label" for="smtp_senha">Senha</label>
        <input class="cfg-input" type="password" id="smtp_senha" autocomplete="new-password"
               placeholder="<?= !empty($cfg['smtp_senha']) ? '••••••••' : 'Não configurada' ?>" />
        <p class="cfg-hint">Deixe em branco para manter a senha atual.</p>
      </div>
    </div>

    <div class="cfg-grupo">
      <div class="cfg-grupo-titulo"><i class="fa fa-at"></i> Remetente & Notificações</div>
      <div class="cfg-campo">
        <label class="cfg-label" for="email_nome_remetente">Nome do remetente</label>
        <input class="cfg-input" id="email_nome_remetente"
               value="<?= c($cfg,'email_nome_remetente','Robério Diógenes') ?>" />
      </div>
      <div class="cfg-campo">
        <label class="cfg-label" for="email_remetente">E-mail remetente (From)</label>
        <input class="cfg-input" type="email" id="email_remetente" placeholder="noreply@roberiodiogenes.com"
               value="<?= c($cfg,'email_remetente') ?>" />
      </div>
      <div class="cfg-campo">
        <label class="cfg-label" for="email_admin">E-mail do admin (notificações internas)</label>
        <input class="cfg-input" type="email" id="email_admin" placeholder="seu@email.com"
               value="<?= c($cfg,'email_admin') ?>" />
        <p class="cfg-hint">Para onde vão alertas: novo comentário, novo cadastro, erro crítico etc.</p>
      </div>
      <div class="cfg-campo" style="margin-top:1rem">
        <button class="btn btn-ghost btn-sm" onclick="testarEmail()">
          <i class="fa fa-paper-plane"></i> Enviar e-mail de teste
        </button>
      </div>
    </div>

  </div>
  <div class="barra-salvar">
    <button class="btn btn-primario" onclick="salvarAba('email')">
      <i class="fa fa-floppy-disk"></i> Salvar alterações
    </button>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     ABA: SEO & DIGITAL
     ══════════════════════════════════════════════════════════════ -->
<div class="aba-painel" id="aba-seo">
  <div class="cfg-grade-wrap cfg-grid">

    <div class="cfg-grupo">
      <div class="cfg-grupo-titulo"><i class="fa fa-chart-line"></i> Analytics & Rastreamento</div>
      <div class="cfg-campo">
        <label class="cfg-label" for="analytics_id">Google Analytics ID</label>
        <input class="cfg-input" id="analytics_id" placeholder="G-XXXXXXXXXX"
               value="<?= c($cfg,'analytics_id') ?>" />
        <p class="cfg-hint">ID do GA4 (começa com G-). Cole apenas o ID, não o código completo.</p>
      </div>
      <div class="cfg-campo">
        <label class="cfg-label" for="tag_manager_id">Google Tag Manager ID</label>
        <input class="cfg-input" id="tag_manager_id" placeholder="GTM-XXXXXXX"
               value="<?= c($cfg,'tag_manager_id') ?>" />
      </div>
      <div class="cfg-campo">
        <label class="cfg-label" for="pixel_facebook">Meta Pixel (Facebook) ID</label>
        <input class="cfg-input" id="pixel_facebook" placeholder="000000000000000"
               value="<?= c($cfg,'pixel_facebook') ?>" />
      </div>
      <div class="cfg-campo">
        <label class="cfg-label" for="og_image_padrao">OG Image padrão (URL)</label>
        <input class="cfg-input" id="og_image_padrao" placeholder="https://roberiodiogenes.com/img/og-padrao.jpg"
               value="<?= c($cfg,'og_image_padrao') ?>" />
        <p class="cfg-hint">Imagem exibida ao compartilhar páginas sem imagem própria. Mínimo 1200×630px.</p>
      </div>
    </div>

    <div class="cfg-grupo">
      <div class="cfg-grupo-titulo"><i class="fa fa-share-nodes"></i> Redes Sociais</div>
      <?php
        $sociais = [
          'social_instagram' => ['Instagram','fa-instagram','https://instagram.com/seuusuario'],
          'social_tiktok'    => ['TikTok',   'fa-tiktok',   'https://tiktok.com/@seuusuario'],
          'social_youtube'   => ['YouTube',  'fa-youtube',  'https://youtube.com/@seucanal'],
          'social_facebook'  => ['Facebook', 'fa-facebook', 'https://facebook.com/suapagina'],
          'social_twitter'   => ['X (Twitter)','fa-x-twitter','https://x.com/seuusuario'],
          'social_linkedin'  => ['LinkedIn', 'fa-linkedin', 'https://linkedin.com/in/seuusuario'],
        ];
        foreach ($sociais as $chave => [$label, $icon, $ph]):
      ?>
      <div class="cfg-campo">
        <label class="cfg-label" for="<?= $chave ?>">
          <i class="fab <?= $icon ?>" style="width:14px"></i> <?= $label ?>
        </label>
        <input class="cfg-input" id="<?= $chave ?>" type="url"
               placeholder="<?= $ph ?>"
               value="<?= c($cfg, $chave) ?>" />
      </div>
      <?php endforeach; ?>
    </div>

  </div>
  <div class="barra-salvar">
    <button class="btn btn-primario" onclick="salvarAba('seo')">
      <i class="fa fa-floppy-disk"></i> Salvar alterações
    </button>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     ABA: TEMAS SAZONAIS
     ══════════════════════════════════════════════════════════════ -->
<div class="aba-painel" id="aba-temas">

  <?php if ($temaAtivoHoje): ?>
  <div style="background:rgba(184,134,11,.10);border:1px solid var(--borda-2);border-radius:var(--raio-lg);padding:.85rem 1.1rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.75rem;font-size:.82rem;color:var(--texto-2)">
    <i class="fa fa-palette" style="color:var(--ouro)"></i>
    Tema ativo hoje: <strong style="color:var(--ouro)"><?= adm_esc($temaAtivoHoje['nome']) ?></strong>
    <span style="width:18px;height:18px;border-radius:50%;background:<?= adm_esc($temaAtivoHoje['cor_ouro']) ?>;border:2px solid rgba(255,255,255,.2);display:inline-block;flex-shrink:0"></span>
  </div>
  <?php else: ?>
  <div style="background:rgba(255,255,255,.04);border:1px solid var(--borda);border-radius:var(--raio-lg);padding:.85rem 1.1rem;margin-bottom:1.25rem;font-size:.82rem;color:var(--texto-3)">
    <i class="fa fa-moon"></i> Nenhum tema ativo hoje. O site exibe as cores padrão.
  </div>
  <?php endif; ?>

  <div style="display:flex;justify-content:flex-end;margin-bottom:1rem">
    <button class="btn btn-primario btn-sm" onclick="abrirModalTema(null)">
      <i class="fa fa-plus"></i> Novo tema
    </button>
  </div>

  <?php if (empty($temas)): ?>
  <div class="estado-vazio">
    <i class="fa fa-palette"></i>
    <p>Execute a migration SQL para carregar os temas padrão.</p>
  </div>
  <?php else: ?>
  <div class="temas-grade" id="temasGrade">
    <?php foreach ($temas as $t):
      $hoje = (int)date('nd');
      $ini  = (int)($t['mes_inicio'] . sprintf('%02d', $t['dia_inicio']));
      $fim  = (int)($t['mes_fim']    . sprintf('%02d', $t['dia_fim']));
      $eHoje = (int)$t['ativo'] === 1 && (($ini <= $fim) ? ($hoje >= $ini && $hoje <= $fim) : ($hoje >= $ini || $hoje <= $fim));
      $meses = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    ?>
    <div class="tema-card <?= $eHoje ? 'hoje' : '' ?> <?= (int)$t['ativo'] !== 1 ? 'tema-card-inativo' : '' ?>"
         id="tema-card-<?= $t['id'] ?>">
      <div class="tema-card-header">
        <span class="tema-card-nome"><?= adm_esc($t['nome']) ?></span>
        <div style="display:flex;gap:.3rem;align-items:center">
          <?php if ($eHoje): ?>
            <span class="tema-card-badge-hoje"><i class="fa fa-star"></i> Hoje</span>
          <?php elseif ((int)$t['ativo']===1): ?>
            <span class="tema-card-badge-ativo">Ativo</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="tema-card-data">
        <i class="fa fa-calendar"></i>
        <?= sprintf('%d %s → %d %s', $t['dia_inicio'], $meses[$t['mes_inicio']], $t['dia_fim'], $meses[$t['mes_fim']]) ?>
        <?php if ($t['mes_inicio'] > $t['mes_fim']): ?><span style="color:var(--ouro);font-size:.6rem">(ano cruzado)</span><?php endif; ?>
      </div>
      <div class="tema-cores">
        <div class="tema-cor" style="background:<?= adm_esc($t['cor_ouro']) ?>" title="Destaque: <?= adm_esc($t['cor_ouro']) ?>"></div>
        <div class="tema-cor" style="background:<?= adm_esc($t['cor_ferrugem']) ?>" title="Acento: <?= adm_esc($t['cor_ferrugem']) ?>"></div>
      </div>
      <div class="tema-acoes">
        <button class="btn btn-ghost btn-sm" onclick='abrirModalTema(<?= json_encode($t) ?>)'>
          <i class="fa fa-pen"></i> Editar
        </button>
        <button class="btn btn-sm <?= (int)$t['ativo']===1 ? 'btn-ghost' : 'btn-primario' ?>"
                onclick="toggleTema(<?= $t['id'] ?>)" id="toggle-<?= $t['id'] ?>">
          <?= (int)$t['ativo']===1 ? '<i class="fa fa-eye-slash"></i> Desativar' : '<i class="fa fa-eye"></i> Ativar' ?>
        </button>
        <button class="btn btn-ghost btn-sm" onclick="previewTema('<?= adm_esc($t['cor_ouro']) ?>','<?= adm_esc($t['cor_ferrugem']) ?>')" title="Pré-visualizar">
          <i class="fa fa-eye"></i>
        </button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div style="margin-top:1rem;padding:.85rem 1rem;background:rgba(255,255,255,.03);border-radius:var(--raio);font-size:.72rem;color:var(--texto-3)">
    <i class="fa fa-circle-info"></i>
    <strong>Como funciona:</strong> O sistema verifica as datas fixas a cada acesso e aplica o primeiro tema ativo encontrado (por ordem de prioridade).
    Temas com datas sobrepostas: o de menor prioridade numérica vence.
    Datas do Carnaval, Dia das Mães e Dia dos Pais são aproximadas — ajuste pelo painel quando necessário.
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     ABA: SISTEMA
     ══════════════════════════════════════════════════════════════ -->
<div class="aba-painel" id="aba-sistema">
  <div class="cfg-grupo" style="max-width:640px">
    <div class="cfg-grupo-titulo"><i class="fa fa-microchip"></i> Informações do sistema</div>

    <div class="sys-grade">
      <div class="sys-item">
        <div class="sys-label">Ambiente</div>
        <div class="sys-valor" style="color:<?= $ambiente==='producao'?'#4CAF50':'var(--ouro)' ?>">
          <?= $ambiente === 'producao' ? '🟢 Produção' : '🟡 Local (XAMPP)' ?>
        </div>
      </div>
      <div class="sys-item">
        <div class="sys-label">PHP</div>
        <div class="sys-valor"><?= htmlspecialchars($phpVer) ?></div>
      </div>
      <div class="sys-item">
        <div class="sys-label">MySQL</div>
        <div class="sys-valor"><?= htmlspecialchars($mysqlVer) ?></div>
      </div>
      <div class="sys-item">
        <div class="sys-label">URL do site</div>
        <div class="sys-valor" style="font-size:.75rem;word-break:break-all"><?= htmlspecialchars($siteUrl) ?></div>
      </div>
      <div class="sys-item">
        <div class="sys-label">Data/hora do servidor</div>
        <div class="sys-valor"><?= date('d/m/Y H:i:s') ?></div>
      </div>
      <div class="sys-item">
        <div class="sys-label">Fuso horário</div>
        <div class="sys-valor"><?= date_default_timezone_get() ?: 'UTC' ?></div>
      </div>
      <div class="sys-item">
        <div class="sys-label">Extensões ativas</div>
        <div class="sys-valor" style="font-size:.72rem">
          <?php
            $ext = ['pdo_mysql'=>'PDO MySQL','gd'=>'GD (imagens)','mbstring'=>'mbstring','openssl'=>'OpenSSL'];
            foreach ($ext as $e => $l):
              $ok = extension_loaded($e);
          ?>
          <span style="color:<?= $ok?'#4CAF50':'#e74c3c' ?>;margin-right:.5rem">
            <?= $ok?'✓':'✗' ?> <?= $l ?>
          </span>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="sys-item">
        <div class="sys-label">Versão da migration SQL</div>
        <div class="sys-valor" style="font-size:.72rem">
          <?php
            try {
              $count = (int)$pdo->query("SELECT COUNT(*) FROM configuracoes")->fetchColumn();
              echo "$count chaves de configuração carregadas";
            } catch(Throwable $e) {
              echo '<span style="color:#e74c3c">Migration pendente</span>';
            }
          ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL: EDITAR / CRIAR TEMA
     ══════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalTema" onclick="if(event.target===this)fecharModal('modalTema')">
  <div class="modal-box" style="max-width:520px">
    <h2 class="modal-titulo" id="modalTemaTitulo">Editar Tema</h2>
    <input type="hidden" id="temaId" value="">

    <div style="display:grid;grid-template-columns:1fr;gap:.75rem">

      <div class="modal-campo">
        <label>Nome do tema</label>
        <input class="cfg-input" id="temaNome" placeholder="Ex: Páscoa" />
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
        <div class="modal-campo">
          <label>Início — Dia</label>
          <input class="cfg-input" type="number" id="temaDiaIni" min="1" max="31" />
        </div>
        <div class="modal-campo">
          <label>Início — Mês</label>
          <select class="cfg-select" id="temaMesIni">
            <?php for($m=1;$m<=12;$m++): ?>
            <option value="<?= $m ?>"><?= $meses[$m] ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="modal-campo">
          <label>Fim — Dia</label>
          <input class="cfg-input" type="number" id="temaDiaFim" min="1" max="31" />
        </div>
        <div class="modal-campo">
          <label>Fim — Mês</label>
          <select class="cfg-select" id="temaMesFim">
            <?php for($m=1;$m<=12;$m++): ?>
            <option value="<?= $m ?>"><?= $meses[$m] ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
        <div class="modal-campo">
          <label>Cor de destaque (ouro)</label>
          <div style="display:flex;gap:.5rem;align-items:center">
            <input type="color" id="temaCorOuro" value="#B8860B"
                   style="width:38px;height:32px;border:1px solid var(--borda);border-radius:var(--raio);padding:2px;background:transparent;cursor:pointer">
            <input class="cfg-input" id="temaCorOuroHex" maxlength="7" placeholder="#B8860B"
                   style="flex:1;font-family:monospace;font-size:.8rem"
                   oninput="sincronizarCor('temaCorOuro','temaCorOuroHex')">
          </div>
        </div>
        <div class="modal-campo">
          <label>Cor de acento (ferrugem)</label>
          <div style="display:flex;gap:.5rem;align-items:center">
            <input type="color" id="temaCorFerr" value="#8B3A2A"
                   style="width:38px;height:32px;border:1px solid var(--borda);border-radius:var(--raio);padding:2px;background:transparent;cursor:pointer">
            <input class="cfg-input" id="temaCorFerrHex" maxlength="7" placeholder="#8B3A2A"
                   style="flex:1;font-family:monospace;font-size:.8rem"
                   oninput="sincronizarCor('temaCorFerr','temaCorFerrHex')">
          </div>
        </div>
      </div>

      <!-- Preview ao vivo -->
      <div id="temaPreviewBox" style="border-radius:var(--raio);padding:.75rem;font-size:.78rem;transition:background .3s">
        <span style="color:rgba(255,255,255,.5);font-size:.65rem;letter-spacing:.1em;text-transform:uppercase">Pré-visualização das cores</span><br>
        <span id="temaPreviewOuro" style="font-weight:600">Destaque (ouro)</span>
        &nbsp;·&nbsp;
        <span id="temaPreviewFerr">Acento (ferrugem)</span>
      </div>
    </div>

    <div class="modal-btns">
      <button class="btn btn-ghost" onclick="fecharModal('modalTema')">Cancelar</button>
      <button class="btn btn-primario" onclick="salvarTema()">
        <i class="fa fa-floppy-disk"></i> Salvar tema
      </button>
    </div>
  </div>
</div>

<script>
/* ── Abas ──────────────────────────────────────────────────────── */
document.querySelectorAll('.aba-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.aba-btn').forEach(b => b.classList.remove('ativa'));
    document.querySelectorAll('.aba-painel').forEach(p => p.classList.remove('ativa'));
    btn.classList.add('ativa');
    document.getElementById('aba-' + btn.dataset.aba)?.classList.add('ativa');
  });
});

/* ── URL base ──────────────────────────────────────────────────── */
const BASE = location.hostname === 'localhost'
  ? `${location.protocol}//${location.host}/roberiodiogenes.com`
  : `${location.protocol}//${location.host}`;

/* ── Mapeamento de campos por aba ──────────────────────────────── */
const CAMPOS = {
  site:    ['site_nome','site_slogan','site_copyright'],
  acesso:  ['permitir_cadastros','modo_manutencao','mensagem_manutencao'],
  uploads: ['upload_tamanho_max_kb','upload_formatos_imagem','upload_formatos_doc'],
  email:   ['smtp_host','smtp_porta','smtp_criptografia','smtp_usuario','smtp_senha',
             'email_nome_remetente','email_remetente','email_admin'],
  seo:     ['analytics_id','tag_manager_id','pixel_facebook','og_image_padrao',
             'social_instagram','social_tiktok','social_youtube',
             'social_facebook','social_twitter','social_linkedin'],
};

/* ── Salvar aba ────────────────────────────────────────────────── */
async function salvarAba(aba) {
  const configs = {};
  (CAMPOS[aba] || []).forEach(k => {
    const el = document.getElementById(k);
    if (!el) return;
    if (el.type === 'checkbox') configs[k] = el.checked ? '1' : '0';
    else if (el.value !== '' || k !== 'smtp_senha') configs[k] = el.value; // pula senha vazia
  });
  // Remove senha vazia para não sobrescrever
  if (aba === 'email' && !configs['smtp_senha']) delete configs['smtp_senha'];

  try {
    const r = await fetch(BASE + '/backend/configuracoes.php', {
      method: 'POST', credentials: 'include',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({acao:'salvar', configs})
    });
    const d = await r.json();
    toast(d.ok ? '✓ ' + (d.msg || 'Salvo!') : '✗ ' + (d.erro || 'Erro'), d.ok ? 'ok' : 'erro');
  } catch(e) {
    toast('Erro de conexão', 'erro');
  }
}

/* ── Enviar e-mail de teste ─────────────────────────────────────── */
async function testarEmail() {
  const para = prompt('Enviar e-mail de teste para qual endereço?', document.getElementById('email_admin')?.value || '');
  if (!para) return;
  toast('Enviando...', 'ok');
  // TODO: implementar endpoint de teste de e-mail
  setTimeout(() => toast('Funcionalidade de teste disponível após salvar as configurações SMTP.', 'ok'), 800);
}

/* ── Toggle tema ─────────────────────────────────────────────────── */
async function toggleTema(id) {
  const r = await fetch(BASE + '/backend/configuracoes.php', {
    method: 'POST', credentials: 'include',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({acao:'toggle_tema', id})
  });
  const d = await r.json();
  if (d.ok) {
    toast('Tema ' + (d.ativo ? 'ativado' : 'desativado'));
    setTimeout(() => location.reload(), 800);
  } else {
    toast(d.erro || 'Erro', 'erro');
  }
}

/* ── Preview rápido (aplica cores na sidebar como amostra) ─────── */
function previewTema(ouro, ferr) {
  document.documentElement.style.setProperty('--ouro', ouro);
  document.documentElement.style.setProperty('--ouro-bg', hexRgbaJS(ouro, 0.12));
  toast('Pré-visualizando — recarregue para desfazer');
}

function hexRgbaJS(hex, a) {
  hex = hex.replace('#','');
  if (hex.length===3) hex=hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
  return `rgba(${parseInt(hex.slice(0,2),16)},${parseInt(hex.slice(2,4),16)},${parseInt(hex.slice(4,6),16)},${a})`;
}

/* ── Modal de tema ────────────────────────────────────────────────── */
function abrirModalTema(tema) {
  document.getElementById('modalTemaTitulo').textContent = tema ? 'Editar Tema' : 'Novo Tema';
  document.getElementById('temaId').value       = tema?.id       ?? '';
  document.getElementById('temaNome').value     = tema?.nome     ?? '';
  document.getElementById('temaDiaIni').value   = tema?.dia_inicio ?? 1;
  document.getElementById('temaMesIni').value   = tema?.mes_inicio ?? 1;
  document.getElementById('temaDiaFim').value   = tema?.dia_fim    ?? 1;
  document.getElementById('temaMesFim').value   = tema?.mes_fim    ?? 1;

  const ouro = tema?.cor_ouro     ?? '#B8860B';
  const ferr = tema?.cor_ferrugem ?? '#8B3A2A';
  document.getElementById('temaCorOuro').value    = ouro;
  document.getElementById('temaCorOuroHex').value = ouro;
  document.getElementById('temaCorFerr').value    = ferr;
  document.getElementById('temaCorFerrHex').value = ferr;
  atualizarPreviewModal(ouro, ferr);

  // Sync color inputs
  document.getElementById('temaCorOuro').oninput = () => {
    const v = document.getElementById('temaCorOuro').value;
    document.getElementById('temaCorOuroHex').value = v;
    atualizarPreviewModal(v, document.getElementById('temaCorFerr').value);
  };
  document.getElementById('temaCorFerr').oninput = () => {
    const v = document.getElementById('temaCorFerr').value;
    document.getElementById('temaCorFerrHex').value = v;
    atualizarPreviewModal(document.getElementById('temaCorOuro').value, v);
  };

  abrirModal('modalTema');
}

function sincronizarCor(inputColorId, inputHexId) {
  const hex = document.getElementById(inputHexId).value;
  if (/^#[0-9a-fA-F]{6}$/.test(hex)) {
    document.getElementById(inputColorId).value = hex;
    atualizarPreviewModal(
      document.getElementById('temaCorOuro').value,
      document.getElementById('temaCorFerr').value
    );
  }
}

function atualizarPreviewModal(ouro, ferr) {
  const box = document.getElementById('temaPreviewBox');
  box.style.background = hexRgbaJS(ouro, 0.12);
  box.style.borderLeft = `3px solid ${ouro}`;
  document.getElementById('temaPreviewOuro').style.color = ouro;
  document.getElementById('temaPreviewFerr').style.color = ferr;
}

async function salvarTema() {
  const payload = {
    acao:         'salvar_tema',
    id:           parseInt(document.getElementById('temaId').value) || 0,
    nome:         document.getElementById('temaNome').value.trim(),
    dia_inicio:   parseInt(document.getElementById('temaDiaIni').value),
    mes_inicio:   parseInt(document.getElementById('temaMesIni').value),
    dia_fim:      parseInt(document.getElementById('temaDiaFim').value),
    mes_fim:      parseInt(document.getElementById('temaMesFim').value),
    cor_ouro:     document.getElementById('temaCorOuro').value,
    cor_ferrugem: document.getElementById('temaCorFerr').value,
  };
  const r = await fetch(BASE + '/backend/configuracoes.php', {
    method: 'POST', credentials: 'include',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  });
  const d = await r.json();
  toast(d.ok ? '✓ ' + d.msg : '✗ ' + (d.erro || 'Erro'), d.ok ? 'ok' : 'erro');
  if (d.ok) { fecharModal('modalTema'); setTimeout(() => location.reload(), 900); }
}
</script>

<?php echo $ADMIN_FOOTER_HTML; ?>
