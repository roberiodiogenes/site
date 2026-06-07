<?php
/* ================================================================
   ROBÉRIO DIÓGENES — lancamento.php
   Página pública de pré-lançamento / lista de espera.
   URL: /lancamento.php?slug=meu-livro
   ================================================================ */

require_once __DIR__ . '/backend/config.php';

$slug = preg_replace('/[^a-z0-9-]/', '', strtolower(trim($_GET['slug'] ?? '')));
$camp = null;

if ($slug) {
    try {
        $pdo  = db();
        $stmt = $pdo->prepare(
            "SELECT id, slug, titulo, subtitulo, descricao, capa_img,
                    data_lancamento, brinde_titulo, ativo, lancado,
                    (SELECT COUNT(*) FROM pre_lancamento_leads WHERE lancamento_id=p.id) AS total_leads
             FROM pre_lancamentos p WHERE slug=? AND ativo=1 LIMIT 1"
        );
        $stmt->execute([$slug]);
        $camp = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { /* fallback */ }
}

$titulo    = $camp ? htmlspecialchars($camp['titulo']) : 'Pré-Lançamento';
$subtitulo = $camp ? htmlspecialchars($camp['subtitulo'] ?? '') : '';
$descricao = $camp ? htmlspecialchars(strip_tags($camp['descricao'] ?? '')) : '';
$capaImg   = $camp && $camp['capa_img'] ? SITE_URL . '/' . $camp['capa_img'] : SITE_URL . '/img/og-image.jpg';
$dataLancamento = $camp ? $camp['data_lancamento'] : null;
$totalLeads = $camp ? (int)$camp['total_leads'] : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $titulo ?> — Lista de Espera | Robério Diógenes</title>
  <meta name="description" content="<?= $subtitulo ?: "Inscreva-se na lista de espera e receba acesso antecipado a {$titulo}." ?>"/>
  <?php if (!$camp): ?><meta name="robots" content="noindex"/><?php endif; ?>
  <link rel="canonical" href="<?= SITE_URL ?>/lancamento.php?slug=<?= htmlspecialchars($slug) ?>"/>
  <meta property="og:type"        content="book"/>
  <meta property="og:title"       content="<?= $titulo ?> — Inscreva-se na Lista de Espera"/>
  <meta property="og:description" content="<?= $subtitulo ?: "Seja o primeiro a saber quando {$titulo} estiver disponível." ?>"/>
  <meta property="og:image"       content="<?= $capaImg ?>"/>
  <meta property="og:url"         content="<?= SITE_URL ?>/lancamento.php?slug=<?= htmlspecialchars($slug) ?>"/>
  <meta name="twitter:card"        content="summary_large_image"/>
  <meta name="twitter:title"       content="<?= $titulo ?> — Lista de Espera"/>
  <meta name="twitter:image"       content="<?= $capaImg ?>"/>
  <link rel="icon" type="image/png" href="img/favicon.png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
  <link rel="stylesheet" href="css/variables.css"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: var(--fonte-corpo); background: var(--fundo); color: var(--texto); min-height: 100vh; display: flex; flex-direction: column; }

    /* ── Nav ── */
    .nav { display:flex;align-items:center;justify-content:space-between;padding:0 2rem;height:52px;border-bottom:1px solid var(--borda);background:var(--fundo-nav,rgba(245,240,232,.95));position:sticky;top:0;z-index:100;backdrop-filter:blur(12px); }
    .nav-logo { font-family:var(--fonte-titulo);font-size:1rem;color:var(--ouro);text-decoration:none; }
    .nav-link { font-family:var(--fonte-display);font-size:.68rem;letter-spacing:.1em;text-transform:uppercase;color:var(--texto-3);text-decoration:none;transition:color .2s; }
    .nav-link:hover { color:var(--ouro); }

    /* ── Hero ── */
    .hero { flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4rem 1.5rem;text-align:center;position:relative;overflow:hidden; }
    .hero::before { content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 50% 0%,rgba(184,134,11,.09),transparent 65%);pointer-events:none; }

    /* ── Card central ── */
    .card { position:relative;z-index:1;width:100%;max-width:860px;display:grid;grid-template-columns:280px 1fr;gap:3rem;align-items:start;background:var(--fundo-card);border:1px solid var(--borda);border-radius:16px;padding:2.5rem;box-shadow:var(--sombra-lg,0 16px 60px rgba(0,0,0,.15)); }
    .capa-wrap { text-align:center; }
    .capa-img { width:100%;max-width:220px;border-radius:8px;box-shadow:var(--sombra-livro,-8px 10px 30px rgba(44,36,24,.25),4px -4px 16px rgba(184,134,11,.12));display:block;margin:0 auto; }
    .capa-placeholder { width:100%;max-width:220px;aspect-ratio:2/3;background:linear-gradient(135deg,var(--fundo-2),var(--fundo-3));border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--ouro);font-size:3rem;opacity:.25;margin:0 auto; }
    .info { display:flex;flex-direction:column;gap:1rem; }
    .info-sup { font-family:var(--fonte-display);font-size:.6rem;letter-spacing:.28em;text-transform:uppercase;color:var(--ouro); }
    .info-titulo { font-family:var(--fonte-titulo);font-size:clamp(1.6rem,3.5vw,2.4rem);font-weight:400;line-height:1.15;color:var(--texto); }
    .info-subtitulo { font-family:var(--fonte-titulo);font-style:italic;font-size:1rem;color:var(--texto-2); }
    .info-descricao { font-size:.95rem;color:var(--texto-2);line-height:1.75; }
    .contador-wrap { display:flex;gap:1rem;flex-wrap:wrap; }
    .contador-item { background:var(--fundo-2);border:1px solid var(--borda);border-radius:8px;padding:.6rem 1rem;text-align:center;min-width:60px; }
    .contador-num { font-family:var(--fonte-titulo);font-size:1.6rem;font-weight:400;color:var(--ouro);line-height:1; }
    .contador-label { font-family:var(--fonte-display);font-size:.55rem;letter-spacing:.12em;text-transform:uppercase;color:var(--texto-3);margin-top:.2rem; }

    /* ── Formulário ── */
    .form-wrap { background:var(--fundo-2);border:1px solid var(--borda-media);border-radius:10px;padding:1.5rem; }
    .form-titulo { font-family:var(--fonte-display);font-size:.65rem;letter-spacing:.18em;text-transform:uppercase;color:var(--ouro);margin-bottom:1rem; }
    .finput { width:100%;padding:.65rem 1rem;background:var(--fundo-card);border:1px solid var(--borda-media);border-radius:6px;color:var(--texto);font-family:var(--fonte-corpo);font-size:.9rem;margin-bottom:.6rem;transition:border-color .2s; }
    .finput:focus { outline:none;border-color:var(--ouro); }
    .fbtn { width:100%;padding:.8rem;background:var(--ouro);color:#1A0F00;border:none;border-radius:6px;font-family:var(--fonte-display);font-size:.75rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;cursor:pointer;transition:opacity .2s; }
    .fbtn:hover { opacity:.85; }
    .fbtn:disabled { opacity:.45;cursor:not-allowed; }
    .form-sub { font-size:.72rem;color:var(--texto-3);text-align:center;margin-top:.6rem;line-height:1.5; }
    .form-sub i { color:var(--ouro); }
    .total-leads { font-family:var(--fonte-display);font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:var(--texto-3);display:flex;align-items:center;gap:.4rem;margin-top:.5rem; }
    .total-leads strong { color:var(--ouro); }

    /* ── Sucesso ── */
    .sucesso-wrap { text-align:center;padding:1.5rem;display:none; }
    .sucesso-icon { font-size:2.5rem;color:var(--ouro);margin-bottom:.75rem; }
    .sucesso-titulo { font-family:var(--fonte-titulo);font-size:1.25rem;font-weight:400;margin-bottom:.5rem; }
    .sucesso-msg { font-size:.9rem;color:var(--texto-2);line-height:1.65; }

    /* ── Não encontrada ── */
    .nf-wrap { flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1rem;padding:4rem 2rem;text-align:center; }
    .nf-wrap h1 { font-family:var(--fonte-titulo);font-size:2rem;font-weight:400; }
    .nf-wrap p { color:var(--texto-3); }

    footer { text-align:center;padding:1.5rem;font-size:.75rem;color:var(--texto-3);border-top:1px solid var(--borda); }
    footer a { color:var(--ouro);text-decoration:none; }

    @media(max-width:720px){
      .card { grid-template-columns:1fr;gap:2rem;padding:1.75rem; }
      .capa-img,.capa-placeholder { max-width:180px; }
    }
    @media(max-width:420px) { .hero { padding:2rem 1rem; } .card { padding:1.25rem; } }
  </style>
</head>
<body>

<nav class="nav">
  <a href="index.html" class="nav-logo">Robério Diógenes</a>
  <a href="livros.html" class="nav-link"><i class="fa fa-book"></i> Biblioteca</a>
</nav>

<?php if (!$camp): ?>
<div class="nf-wrap">
  <i class="fa fa-book-open" style="font-size:3rem;color:var(--ouro);opacity:.25"></i>
  <h1>Campanha não encontrada</h1>
  <p>Esta página de pré-lançamento não existe ou não está ativa.</p>
  <a href="livros.html" style="color:var(--ouro)">← Ver todos os livros</a>
</div>
<?php else: ?>

<main class="hero" id="topo">
  <div class="card">
    <!-- Capa -->
    <div class="capa-wrap">
      <?php if ($camp['capa_img']): ?>
        <img src="../<?= htmlspecialchars($camp['capa_img']) ?>" alt="Capa de <?= $titulo ?>"
             class="capa-img" loading="eager">
      <?php else: ?>
        <div class="capa-placeholder"><i class="fa fa-book"></i></div>
      <?php endif; ?>
    </div>

    <!-- Info + Formulário -->
    <div class="info">
      <span class="info-sup"><i class="fa fa-hourglass-half"></i> Pré-lançamento · Em breve</span>
      <h1 class="info-titulo"><?= $titulo ?></h1>
      <?php if ($subtitulo): ?>
        <p class="info-subtitulo"><?= $subtitulo ?></p>
      <?php endif; ?>
      <?php if ($camp['descricao']): ?>
        <p class="info-descricao"><?= nl2br(htmlspecialchars($camp['descricao'])) ?></p>
      <?php endif; ?>

      <!-- Countdown (se tiver data) -->
      <?php if ($dataLancamento): ?>
      <div class="contador-wrap" id="countdown" aria-label="Contagem regressiva para o lançamento">
        <div class="contador-item"><div class="contador-num" id="cd-dias">--</div><div class="contador-label">dias</div></div>
        <div class="contador-item"><div class="contador-num" id="cd-horas">--</div><div class="contador-label">horas</div></div>
        <div class="contador-item"><div class="contador-num" id="cd-min">--</div><div class="contador-label">min</div></div>
        <div class="contador-item"><div class="contador-num" id="cd-seg">--</div><div class="contador-label">seg</div></div>
      </div>
      <?php endif; ?>

      <!-- Formulário de inscrição -->
      <div class="form-wrap" id="formWrap">
        <p class="form-titulo"><i class="fa fa-envelope-open-text"></i>
          <?= $camp['brinde_titulo'] ? "Inscreva-se e receba: {$camp['brinde_titulo']}" : 'Inscreva-se na lista de espera' ?>
        </p>
        <form id="formEspera" novalidate>
          <input type="text"  class="finput" id="feNome"  name="nome"  placeholder="Seu nome (opcional)" autocomplete="given-name">
          <input type="email" class="finput" id="feEmail" name="email" placeholder="Seu e-mail *" required autocomplete="email">
          <button type="submit" class="fbtn" id="feBotao">
            <i class="fa fa-envelope"></i>
            <?= $camp['brinde_titulo'] ? "Quero meu {$camp['brinde_titulo']}" : 'Entrar na lista de espera' ?>
          </button>
          <p class="form-sub"><i class="fa fa-lock"></i> Sem spam. Você receberá apenas as novidades sobre este livro.</p>
          <?php if ($totalLeads >= 10): ?>
          <p class="total-leads"><i class="fa fa-users"></i> <strong><?= number_format($totalLeads, 0, ',', '.') ?></strong> pessoas já na lista</p>
          <?php endif; ?>
        </form>

        <div class="sucesso-wrap" id="sucessoWrap">
          <div class="sucesso-icon"><i class="fa fa-circle-check"></i></div>
          <h2 class="sucesso-titulo">Você está na lista!</h2>
          <p class="sucesso-msg" id="sucessoMsg">Verifique seu e-mail — enviamos seu presente exclusivo.</p>
        </div>
      </div>
    </div>
  </div>
</main>

<?php if ($dataLancamento): ?>
<script>
const alvo = new Date('<?= $dataLancamento ?>T00:00:00').getTime();
function tick() {
  const diff = alvo - Date.now();
  if (diff <= 0) {
    document.getElementById('countdown')?.remove();
    return;
  }
  const d = Math.floor(diff / 86400000);
  const h = Math.floor((diff % 86400000) / 3600000);
  const m = Math.floor((diff % 3600000) / 60000);
  const s = Math.floor((diff % 60000) / 1000);
  document.getElementById('cd-dias').textContent  = String(d).padStart(2,'0');
  document.getElementById('cd-horas').textContent = String(h).padStart(2,'0');
  document.getElementById('cd-min').textContent   = String(m).padStart(2,'0');
  document.getElementById('cd-seg').textContent   = String(s).padStart(2,'0');
}
tick(); setInterval(tick, 1000);
</script>
<?php endif; ?>

<script>
document.getElementById('formEspera')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const btn   = document.getElementById('feBotao');
  const email = document.getElementById('feEmail').value.trim();
  const nome  = document.getElementById('feNome').value.trim();
  if (!email) { document.getElementById('feEmail').focus(); return; }
  btn.disabled = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Enviando…';
  try {
    const r = await fetch('backend/pre-lancamento.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ acao: 'inscrever', slug: '<?= htmlspecialchars($slug) ?>', nome, email }),
    });
    const d = await r.json();
    if (d.ok) {
      document.getElementById('formEspera').style.display = 'none';
      const sw = document.getElementById('sucessoWrap');
      document.getElementById('sucessoMsg').textContent = d.mensagem || 'Verifique seu e-mail.';
      sw.style.display = 'block';
    } else {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-envelope"></i> Tentar novamente';
      alert(d.erro || 'Erro ao inscrever. Tente novamente.');
    }
  } catch {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa fa-envelope"></i> Tentar novamente';
    alert('Erro de conexão. Tente novamente.');
  }
});
</script>

<?php endif; ?>

<footer>
  <p>© <?= date('Y') ?> <a href="index.html">Robério Diógenes</a> · <a href="privacidade.html">Privacidade</a> · <a href="contato.html">Contato</a></p>
</footer>
</body>
</html>
