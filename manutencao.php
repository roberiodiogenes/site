<?php
/* ================================================================
   ROBÉRIO DIÓGENES — manutencao.php
   Página de manutenção com imagem responsiva de plano de fundo.
   ================================================================ */

require_once __DIR__ . '/backend/config.php';
$pdo = db();

// ── Se manutenção estiver desligada no banco, volta para home ──
try {
    $manut = $pdo->query(
        "SELECT valor FROM configuracoes WHERE chave='modo_manutencao'"
    )->fetchColumn();
    if ($manut !== '1') {
        header('Location: ' . SITE_URL);
        exit;
    }
    $mensagem = $pdo->query(
        "SELECT valor FROM configuracoes WHERE chave='mensagem_manutencao'"
    )->fetchColumn() ?: 'Estamos em manutenção. Voltamos em breve.';
    $nomesite = $pdo->query(
        "SELECT valor FROM configuracoes WHERE chave='site_nome'"
    )->fetchColumn() ?: 'Robério Diógenes';
} catch (Throwable $e) {
    $mensagem = 'Estamos em manutenção. Voltamos em breve.';
    $nomesite = 'Robério Diógenes';
}

// ── Verifica se é admin ──────────────────────────────────────
session_name('rd_admin_sess');
if (session_status() === PHP_SESSION_NONE) session_start();
$isAdmin = !empty($_SESSION['admin_id']);

// ── HTTP 503 para mecanismos de busca (não admins) ───────────
if (!$isAdmin) {
    http_response_code(503);
    header('Retry-After: 3600');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Em manutenção | <?= htmlspecialchars($nomesite) ?></title>
  <link rel="icon" type="image/png" href="<?= SITE_URL ?>/img/favicon.png" />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,300;0,400;0,500;1,300;1,400&family=Cinzel:wght@400;500&family=Lora:ital,wght@0,400;1,400&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
      height: 100%;
      overflow: hidden;
      background: #080605; /* fallback escuro */
    }

    /* ── Imagem de fundo ────────────────────────────────────── */
    .manut-fundo {
      position: fixed;
      inset: 0;
      z-index: 0;
    }
    .manut-fundo picture,
    .manut-fundo img {
      display: block;
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center center;
    }

    /* ── Banner admin (topo fixo) ───────────────────────────── */
    .admin-banner {
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 200;
      background: rgba(8, 5, 2, 0.88);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(200,148,12,0.35);
      padding: .6rem 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: .5rem;
    }
    .admin-banner-texto {
      font-family: 'Lora', Georgia, serif;
      font-size: .78rem;
      color: #C8940C;
      display: flex;
      align-items: center;
      gap: .5rem;
    }
    .admin-banner-acoes {
      display: flex;
      gap: .5rem;
    }
    .admin-banner-link {
      font-family: 'Lora', Georgia, serif;
      font-size: .72rem;
      color: rgba(232,220,200,.7);
      text-decoration: none;
      padding: .3rem .75rem;
      border: 1px solid rgba(200,148,12,.3);
      border-radius: 20px;
      transition: all .2s;
    }
    .admin-banner-link:hover {
      border-color: rgba(200,148,12,.7);
      color: #C8940C;
    }

    /* ── Painel de texto ────────────────────────────────────── */
    /*  Desktop: canto inferior esquerdo (área escura da imagem)  */
    /*  Mobile:  faixa inferior com scrim                         */
    .manut-painel {
      position: fixed;
      z-index: 10;

      /* desktop */
      bottom: 8%;
      left: 4%;
    }

    /* scrim radial discreta atrás do texto */
    .manut-painel::before {
      content: '';
      position: absolute;
      inset: -2rem -2.5rem;
      background: radial-gradient(
        ellipse at 25% 65%,
        rgba(0,0,0,0.55) 0%,
        transparent 75%
      );
      border-radius: 14px;
      pointer-events: none;
      z-index: 0;
    }

    .manut-pretitulo {
      font-family: 'Cinzel', serif;
      font-size: .62rem;
      letter-spacing: .3em;
      text-transform: uppercase;
      color: rgba(200,148,12,.75);
      margin-bottom: 1.1rem;
      display: flex;
      align-items: center;
      gap: .75rem;
      position: relative;
      z-index: 1;
    }
    .manut-pretitulo::before {
      content: '';
      display: block;
      width: 2rem;
      height: 1px;
      background: rgba(200,148,12,.5);
    }

    .manut-titulo {
      font-family: 'Cormorant', Georgia, serif;
      font-size: clamp(2rem, 4.5vw, 3.5rem);
      font-weight: 300;
      color: #E8DCC8;
      line-height: 1.2;
      margin-bottom: 1.1rem;
      position: relative;
      z-index: 1;
    }
    .manut-titulo em {
      font-style: italic;
      color: #C8940C;
    }

    .manut-mensagem {
      font-family: 'Cormorant', Georgia, serif;
      font-style: italic;
      font-size: clamp(.95rem, 1.8vw, 1.1rem);
      color: rgba(232,220,200,.7);
      line-height: 1.8;
      max-width: 34ch;
      margin-bottom: 1.75rem;
      position: relative;
      z-index: 1;
    }

    .manut-assinatura {
      font-family: 'Cinzel', serif;
      font-size: .6rem;
      letter-spacing: .2em;
      text-transform: uppercase;
      color: rgba(200,148,12,.5);
      position: relative;
      z-index: 1;
      display: flex;
      align-items: center;
      gap: .6rem;
    }
    .manut-assinatura::before {
      content: '';
      display: block;
      width: 1.5rem;
      height: 1px;
      background: rgba(200,148,12,.35);
    }

    /* ═══════════════════════════════════════════════════════════
       MOBILE ≤ 768px
       ═══════════════════════════════════════════════════════════ */
    @media (max-width: 768px) {
      html, body { overflow: hidden; }

      .manut-painel {
        bottom: 0;
        left: 0;
        right: 0;
        padding: 3rem 1.5rem 2.75rem;
        text-align: center;
        background: linear-gradient(
          to top,
          rgba(5, 3, 1, 0.92) 0%,
          rgba(5, 3, 1, 0.65) 55%,
          transparent 100%
        );
      }

      .manut-painel::before { display: none; }

      .manut-pretitulo {
        justify-content: center;
      }
      .manut-pretitulo::before { display: none; }

      .manut-mensagem {
        max-width: 100%;
      }

      .manut-assinatura {
        justify-content: center;
      }
      .manut-assinatura::before { display: none; }
    }

    @media (max-width: 480px) {
      .admin-banner {
        flex-direction: column;
        align-items: flex-start;
      }
    }
  </style>
</head>
<body>

<!-- ── Imagem de fundo ────────────────────────────────────────── -->
<div class="manut-fundo" aria-hidden="true">
  <picture>
    <source media="(max-width: 768px)" srcset="<?= SITE_URL ?>/img/em-manutencao-celular.webp" />
    <img
      src="<?= SITE_URL ?>/img/em-manutencao-desktop.webp"
      alt=""
      loading="eager"
      decoding="async"
      fetchpriority="high"
    />
  </picture>
</div>

<?php if ($isAdmin): ?>
<!-- ── Banner de aviso para admin ────────────────────────────── -->
<div class="admin-banner" role="banner">
  <span class="admin-banner-texto">
    <span>⚙</span>
    Modo de manutenção <strong>ativo</strong> — visitantes estão sendo bloqueados. Você vê esta página por ser admin.
  </span>
  <div class="admin-banner-acoes">
    <a href="<?= SITE_URL ?>/admin/configuracoes.php#acesso" class="admin-banner-link">
      Desativar manutenção
    </a>
    <a href="<?= SITE_URL ?>" class="admin-banner-link">
      Ver site
    </a>
  </div>
</div>
<?php endif; ?>

<!-- ── Conteúdo sobre a imagem ───────────────────────────────── -->
<main class="manut-painel" aria-label="Site em manutenção">
  <p class="manut-pretitulo" aria-hidden="true">Em construção</p>

  <h1 class="manut-titulo">
    O site está sendo<br><em>reconstruído</em>
  </h1>

  <p class="manut-mensagem"><?= nl2br(htmlspecialchars($mensagem)) ?></p>

  <p class="manut-assinatura"><?= htmlspecialchars($nomesite) ?></p>
</main>

</body>
</html>
