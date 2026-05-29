<?php
/* ================================================================
   ROBÉRIO DIÓGENES — leitor/livro.php
   Leitor de livros dinâmico.

   Substituição definitiva do livro.html estático.
   Todo o controle de acesso, dados do livro e configuração
   do leitor são resolvidos no servidor, antes do HTML chegar
   ao navegador. O JS recebe dados prontos via LEITOR_CONFIG.

   URL: leitor/livro.php?livro=lumen
   ================================================================ */

require_once __DIR__ . '/../backend/config.php';
iniciarSessao();

/* ──────────────────────────────────────────────────────────────
   1. PARÂMETRO — slug do livro
   ────────────────────────────────────────────────────────────── */
$slug = trim($_GET['livro'] ?? '');

// Sanitiza: apenas letras minúsculas, números e hífen
$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));

if (!$slug) {
    // Sem slug: redireciona para a biblioteca
    header('Location: ../livros.html');
    exit;
}

/* ──────────────────────────────────────────────────────────────
   2. SESSÃO — usuário logado?
   ────────────────────────────────────────────────────────────── */
$logado    = !empty($_SESSION['usuario_id']);
$uid       = $logado ? (int) $_SESSION['usuario_id'] : 0;
$nomeUser  = $logado ? htmlspecialchars($_SESSION['usuario_nome'] ?? '', ENT_QUOTES) : '';

/* ──────────────────────────────────────────────────────────────
   3. BANCO — dados do livro
   ────────────────────────────────────────────────────────────── */
$pdo   = db();
$stmt  = $pdo->prepare(
    "SELECT slug, titulo, total_capitulos, pasta_conteudo, sinopse, capa_img, preco, preco_promocao
     FROM livros
     WHERE slug = ? AND ativo = 1
     LIMIT 1"
);
$stmt->execute([$slug]);
$livro = $stmt->fetch();

if (!$livro) {
    // Livro não existe: redireciona para 404 ou biblioteca
    header('Location: ../livros.html?erro=livro-nao-encontrado');
    exit;
}

$titulo          = htmlspecialchars($livro['titulo'],          ENT_QUOTES);
$totalCapitulos  = max(1, (int) ($livro['total_capitulos'] ?? 1));
$pastaConteudo   = htmlspecialchars($livro['pasta_conteudo'] ?? "../livros-conteudo/{$slug}/", ENT_QUOTES);

/* ──────────────────────────────────────────────────────────────
   4. ACESSO — usuário tem direito de ler?
   Verifica compra avulsa aprovada OU assinatura ativa.
   ────────────────────────────────────────────────────────────── */
$temAcesso   = false;
$tipoAcesso  = null;   // 'compra' | 'assinatura'
$planoNome   = null;
$assinaExp   = null;

if ($logado) {
    // Compra avulsa
    $stmtC = $pdo->prepare(
        "SELECT id FROM compras
         WHERE usuario_id = ? AND livro_slug = ? AND status = 'aprovada'
         LIMIT 1"
    );
    $stmtC->execute([$uid, $slug]);
    if ($stmtC->fetch()) {
        $temAcesso  = true;
        $tipoAcesso = 'compra';
    }

    // Assinatura ativa
    if (!$temAcesso) {
        $stmtA = $pdo->prepare(
            "SELECT a.expira_em, p.nome AS plano_nome
             FROM assinaturas a
             JOIN planos p ON p.id = a.plano_id
             WHERE a.usuario_id = ? AND a.status = 'ativa' AND a.expira_em > NOW()
             ORDER BY a.expira_em DESC
             LIMIT 1"
        );
        $stmtA->execute([$uid]);
        $assin = $stmtA->fetch();
        if ($assin) {
            $temAcesso  = true;
            $tipoAcesso = 'assinatura';
            $planoNome  = htmlspecialchars($assin['plano_nome'], ENT_QUOTES);
            $assinaExp  = (new DateTime($assin['expira_em']))->format('d/m/Y');
        }
    }
}

/* ──────────────────────────────────────────────────────────────
   5. PROGRESSO SALVO — retoma onde parou
   ────────────────────────────────────────────────────────────── */
$capInicial    = 1;
$scrollInicial = 0;
$percentSalvo  = 0.0;

if ($logado && $temAcesso) {
    $stmtP = $pdo->prepare(
        "SELECT capitulo_atual, posicao_scroll, percentual
         FROM leitor_progresso
         WHERE usuario_id = ? AND livro_slug = ?
         LIMIT 1"
    );
    $stmtP->execute([$uid, $slug]);
    $prog = $stmtP->fetch();
    if ($prog) {
        $capInicial    = max(1, (int)   $prog['capitulo_atual']);
        $scrollInicial = max(0, (int)   $prog['posicao_scroll']);
        $percentSalvo  = min(100, (float) $prog['percentual']);
    }
}

/* ──────────────────────────────────────────────────────────────
   6. PREFERÊNCIAS TIPOGRÁFICAS SALVAS
   ────────────────────────────────────────────────────────────── */
$prefs = [
    'fonte'          => 'serifada',
    'tamanho_fonte'  => 18,
    'fundo_leitura'  => 'bege',
    'largura_coluna' => 'media',
    'altura_linha'   => 1.8,
];

if ($logado) {
    $stmtPref = $pdo->prepare(
        "SELECT fonte, tamanho_fonte, fundo_leitura, largura_coluna, altura_linha
         FROM leitor_preferencias WHERE usuario_id = ?"
    );
    $stmtPref->execute([$uid]);
    $prefDB = $stmtPref->fetch();
    if ($prefDB) {
        $prefs['fonte']          = $prefDB['fonte'];
        $prefs['tamanho_fonte']  = (int)   $prefDB['tamanho_fonte'];
        $prefs['fundo_leitura']  = $prefDB['fundo_leitura'];
        $prefs['largura_coluna'] = $prefDB['largura_coluna'];
        $prefs['altura_linha']   = (float) $prefDB['altura_linha'];
    }
}

/* ──────────────────────────────────────────────────────────────
   7. AVALIAÇÃO ATUAL DO USUÁRIO
   ────────────────────────────────────────────────────────────── */
$avaliacaoAtual = 0;
if ($logado) {
    $stmtAv = $pdo->prepare(
        "SELECT estrelas FROM avaliacoes WHERE usuario_id = ? AND livro_slug = ? LIMIT 1"
    );
    $stmtAv->execute([$uid, $slug]);
    $av = $stmtAv->fetchColumn();
    if ($av !== false) $avaliacaoAtual = (int) $av;
}

/* ──────────────────────────────────────────────────────────────
   8. MOTIVO DE ACESSO NEGADO (para mensagem personalizada)
   ────────────────────────────────────────────────────────────── */
$motivoNegado = 'sem_acesso';
if (!$logado) {
    $motivoNegado = 'nao_logado';
} elseif (!$temAcesso) {
    // Verifica se teve assinatura expirada
    $stmtExp = $pdo->prepare(
        "SELECT expira_em FROM assinaturas
         WHERE usuario_id = ? AND status IN ('ativa','expirada')
         ORDER BY expira_em DESC LIMIT 1"
    );
    $stmtExp->execute([$uid]);
    $expAnterior = $stmtExp->fetchColumn();
    if ($expAnterior) $motivoNegado = 'assinatura_expirada';
}

/* ──────────────────────────────────────────────────────────────
   9. MONTA O OBJETO DE CONFIGURAÇÃO PARA O JS
   (emitido inline como JSON — nenhum dado sensível aqui)
   ────────────────────────────────────────────────────────────── */
$leitorConfig = json_encode([
    'slug'           => $slug,
    'titulo'         => $livro['titulo'],
    'totalCapitulos' => $totalCapitulos,
    'pastaConteudo'  => $pastaConteudo,
    'capInicial'     => $capInicial,
    'scrollInicial'  => $scrollInicial,
    'percentSalvo'   => $percentSalvo,
    'temAcesso'      => $temAcesso,
    'motivoNegado'   => $motivoNegado,
    'avaliacao'      => $avaliacaoAtual,
    'prefs'          => $prefs,
], JSON_UNESCAPED_UNICODE);

/* ──────────────────────────────────────────────────────────────
   10. LOG DE ACESSO (para estatísticas futuras — opcional)
   ────────────────────────────────────────────────────────────── */
if ($logado && $temAcesso) {
    try {
        $pdo->prepare(
            "INSERT INTO leitor_progresso
                (usuario_id, livro_slug, capitulo_atual, posicao_scroll, percentual, total_paginas)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE ultima_leitura = NOW()"
        )->execute([$uid, $slug, $capInicial, $scrollInicial, $percentSalvo, $totalCapitulos]);
    } catch (Exception $e) {
        // Silencioso — não bloqueia a página
    }
}

/* ──────────────────────────────────────────────────────────────
   HTML — tudo abaixo é a página renderizada
   ────────────────────────────────────────────────────────────── */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $titulo ?> — Leitor | Robério Diógenes</title>
  <meta name="robots" content="noindex, nofollow" />

  <?php if (!empty($livro['sinopse'])): ?>
  <meta name="description" content="<?= htmlspecialchars(mb_substr($livro['sinopse'], 0, 160), ENT_QUOTES) ?>" />
  <?php endif; ?>

  <!-- Open Graph mínimo para compartilhamento -->
  <meta property="og:title"       content="<?= $titulo ?> — Leitor | Robério Diógenes" />
  <meta property="og:type"        content="website" />
  <meta property="og:url"         content="<?= SITE_URL ?>/leitor/livro.php?livro=<?= urlencode($slug) ?>" />
  <?php if (!empty($livro['capa_img'])): ?>
  <meta property="og:image"       content="<?= SITE_URL ?>/<?= htmlspecialchars($livro['capa_img'], ENT_QUOTES) ?>" />
  <?php endif; ?>

  <link rel="icon" type="image/png" href="../img/favicon.png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="../css/variables.css" />
  <link rel="stylesheet" href="../css/leitor-livro.css" />

  <!-- ══ CONFIGURAÇÃO DO LEITOR (injetada pelo PHP, sem fetch extra) ══ -->
  <script>
    window.LEITOR_CONFIG = <?= $leitorConfig ?>;
  </script>
</head>

<!--
  Preferências tipográficas aplicadas diretamente no body pelo PHP.
  O JS não precisa fazer nenhuma requisição extra para buscar prefs —
  elas já chegam no HTML, eliminando o flash de estilo incorreto.
-->
<body
  class="leitor-corpo"
  data-fundo="<?= htmlspecialchars($prefs['fundo_leitura'], ENT_QUOTES) ?>"
  data-fonte="<?= htmlspecialchars($prefs['fonte'],         ENT_QUOTES) ?>"
  data-largura="<?= htmlspecialchars($prefs['largura_coluna'], ENT_QUOTES) ?>"
  style="--leitor-tamanho:<?= (int)$prefs['tamanho_fonte'] ?>px;--leitor-linha:<?= number_format((float)$prefs['altura_linha'], 1, '.', '') ?>"
>

<a href="#conteudo-principal" class="pular-nav">Pular para o conteúdo</a>

<!-- ══ BARRA DE PROGRESSO ═════════════════════════════════════ -->
<div class="leitor-barra-progresso" role="progressbar"
     aria-label="Progresso de leitura"
     aria-valuenow="<?= round($percentSalvo) ?>"
     aria-valuemin="0" aria-valuemax="100">
  <div class="leitor-barra-progresso-fill"
       id="barra-progresso-fill"
       style="width:<?= number_format($percentSalvo, 1, '.', '') ?>%">
  </div>
</div>

<!-- ══ BARRA DE NAVEGAÇÃO ═════════════════════════════════════ -->
<nav class="leitor-nav" aria-label="Navegação do leitor">

  <div class="leitor-nav-esq">
    <a href="../livros.html"
       class="leitor-nav-btn"
       title="Voltar à biblioteca"
       aria-label="Voltar à biblioteca">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
    </a>
  </div>

  <span class="leitor-nav-titulo" id="leitor-titulo-nav" aria-live="polite">
    <?= $titulo ?>
  </span>

  <div class="leitor-nav-dir">
    <span class="leitor-percentual-badge"
          id="leitor-percentual"
          aria-label="Progresso de leitura"
          aria-live="polite">
      <?= round($percentSalvo) ?>%
    </span>

    <?php if ($logado && $temAcesso): ?>
    <button class="leitor-nav-btn"
            id="btn-painel"
            title="Abrir painel (anotações, marcações, configurações)"
            aria-label="Abrir painel"
            aria-expanded="false"
            aria-controls="leitor-painel">
      <i class="fa-solid fa-sliders" aria-hidden="true"></i>
    </button>
    <?php endif; ?>
  </div>

</nav>

<!-- ══ WRAPPER PRINCIPAL ══════════════════════════════════════ -->
<div id="leitor-wrapper">

<?php if (!$logado || !$temAcesso): ?>
  <!-- ══ TELA DE ACESSO NEGADO (renderizada pelo PHP) ══════════ -->
  <?php
    $mapaNegado = [
      'nao_logado'           => ['icone'=>'🔐', 'titulo'=>'Login necessário',    'msg'=>'Faça login para acessar este livro.',        'btn_href'=>'../login.html',                    'btn_label'=>'Fazer login'],
      'sem_acesso'           => ['icone'=>'📖', 'titulo'=>'Livro não adquirido', 'msg'=>'Você precisa comprar este livro ou assinar um plano para lê-lo.', 'btn_href'=>"../livros/{$slug}.html", 'btn_label'=>'Ver o livro'],
      'assinatura_expirada'  => ['icone'=>'⏳', 'titulo'=>'Assinatura expirada', 'msg'=>'Sua assinatura expirou. Renove para continuar lendo.', 'btn_href'=>'../pagamento/assinatura.html', 'btn_label'=>'Renovar assinatura'],
    ];
    $info = $mapaNegado[$motivoNegado] ?? $mapaNegado['sem_acesso'];
  ?>
  <div class="leitor-acesso-negado" role="alert">
    <span class="acesso-icone" aria-hidden="true"><?= $info['icone'] ?></span>
    <h2><?= $info['titulo'] ?></h2>
    <p><?= htmlspecialchars($info['msg'], ENT_QUOTES) ?></p>

    <?php if ($motivoNegado !== 'nao_logado'): ?>
    <!-- Mostra o preço se o livro tiver um definido -->
    <?php
      $preco = $livro['preco_promocao'] ?? $livro['preco'];
      if ($preco): ?>
    <p style="font-size:0.82rem;color:var(--texto-3);margin-bottom:1rem">
      Compra avulsa:
      <strong style="color:var(--ouro)">R$ <?= number_format((float)$preco, 2, ',', '.') ?></strong>
    </p>
    <?php endif; ?>
    <?php endif; ?>

    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;justify-content:center">
      <a href="<?= $info['btn_href'] ?>" class="btn-acesso">
        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        <?= $info['btn_label'] ?>
      </a>
      <?php if ($motivoNegado !== 'nao_logado'): ?>
      <a href="../pagamento/assinatura.html" class="btn-acesso"
         style="background:transparent;border:1px solid var(--ouro);color:var(--ouro)">
        <i class="fa-solid fa-crown" aria-hidden="true"></i>
        Ver planos de assinatura
      </a>
      <?php endif; ?>
    </div>
  </div>

<?php else: ?>
  <!-- ══ LEITOR COMPLETO (só renderiza se tem acesso) ══════════ -->

  <?php if ($tipoAcesso === 'assinatura' && $assinaExp): ?>
  <!-- Badge discreto de assinatura ativa -->
  <div style="
    text-align:center;padding:0.4rem 1rem;
    background:rgba(184,134,11,0.07);
    border-bottom:1px solid var(--leitor-borda);
    font-family:var(--fonte-display);font-size:0.65rem;
    letter-spacing:0.1em;text-transform:uppercase;color:var(--ouro);
  " aria-label="Assinatura ativa">
    <i class="fa-solid fa-crown" aria-hidden="true"></i>
    <?= htmlspecialchars($planoNome, ENT_QUOTES) ?> — ativo até <?= $assinaExp ?>
  </div>
  <?php endif; ?>

  <div class="leitor-layout">

    <!-- ── ÁREA DE LEITURA ────────────────────────────────────── -->
    <main class="leitor-conteudo-area">
      <div class="leitor-texto-wrapper">

        <div id="leitor-texto-area">
          <div class="leitor-loading" role="status" aria-live="polite">
            <div class="leitor-loading-spinner" aria-hidden="true"></div>
            <span>Carregando capítulo <?= $capInicial ?>…</span>
          </div>
        </div>

        <!-- Navegação entre capítulos -->
        <nav class="leitor-nav-caps" aria-label="Navegação entre capítulos">
          <button class="leitor-btn-cap" id="btn-cap-anterior"
                  aria-label="Capítulo anterior"
                  <?= $capInicial <= 1 ? 'disabled' : '' ?>>
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            Anterior
          </button>

          <div class="leitor-cap-info" id="cap-info-atual" aria-live="polite">
            Capítulo <?= $capInicial ?> de <?= $totalCapitulos ?>
          </div>

          <button class="leitor-btn-cap" id="btn-cap-proximo"
                  aria-label="Próximo capítulo"
                  <?= $capInicial >= $totalCapitulos ? 'disabled' : '' ?>>
            Próximo
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
          </button>
        </nav>

        <!-- Tela de conclusão -->
        <div class="leitor-conclusao" id="leitor-conclusao"
             style="display:<?= $percentSalvo >= 100 ? 'block' : 'none' ?>"
             aria-live="polite">
          <span class="conclusao-ornamento" aria-hidden="true">✦ ✦ ✦</span>
          <h2 class="conclusao-titulo">Você chegou ao fim.</h2>
          <p class="conclusao-sub">
            Obrigado por ler até aqui. O que você achou desta história?
          </p>
          <div class="estrelas-leitor" role="group" aria-label="Avalie o livro">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <span class="estrela-leitor <?= $i <= $avaliacaoAtual ? 'iluminada' : '' ?>"
                  role="button" tabindex="0"
                  aria-label="<?= $i ?> <?= $i === 1 ? 'estrela' : 'estrelas' ?>"
                  data-nota="<?= $i ?>">★</span>
            <?php endfor; ?>
          </div>
          <a href="../livros.html" class="leitor-btn-cap"
             style="margin:1.5rem auto;display:inline-flex">
            <i class="fa-solid fa-book" aria-hidden="true"></i>
            Explorar mais livros
          </a>
        </div>

      </div><!-- /.leitor-texto-wrapper -->
    </main>

    <!-- ── PAINEL LATERAL ─────────────────────────────────────── -->
    <aside class="leitor-painel" id="leitor-painel"
           aria-label="Painel do leitor" role="complementary">

      <div class="painel-abas" role="tablist" aria-label="Seções do painel">
        <button class="painel-aba-btn ativa" role="tab" aria-selected="true"
                data-aba="config" aria-controls="secao-config">
          <i class="fa-solid fa-gear" aria-hidden="true"></i><br>Config
        </button>
        <button class="painel-aba-btn" role="tab" aria-selected="false"
                data-aba="anotacoes" aria-controls="secao-anotacoes">
          <i class="fa-solid fa-pen" aria-hidden="true"></i><br>Notas
        </button>
        <button class="painel-aba-btn" role="tab" aria-selected="false"
                data-aba="marcacoes" aria-controls="secao-marcacoes">
          <i class="fa-solid fa-highlighter" aria-hidden="true"></i><br>Marcações
        </button>
        <button class="painel-aba-btn" role="tab" aria-selected="false"
                data-aba="avaliacao" aria-controls="secao-avaliacao">
          <i class="fa-solid fa-star" aria-hidden="true"></i><br>Avaliar
        </button>
      </div>

      <div class="painel-conteudo">

        <!-- Config -->
        <section class="painel-secao ativa" data-secao="config"
                 id="secao-config" role="tabpanel">

          <div class="config-grupo">
            <span class="config-label">Fonte</span>
            <div class="config-opcoes">
              <?php
              $fontes = ['serifada'=>'Serifada','classica'=>'Clássica','sans'=>'Sem serifa','manuscrito'=>'Manuscrito'];
              foreach ($fontes as $val => $label):
              ?>
              <button class="config-op-btn <?= $prefs['fonte'] === $val ? 'ativo' : '' ?>"
                      data-config-fonte="<?= $val ?>">
                <?= $label ?>
              </button>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="config-grupo">
            <span class="config-label">Tamanho do texto</span>
            <div class="config-slider-row">
              <span style="font-size:0.75rem;opacity:0.6">A</span>
              <input type="range" class="config-slider" id="slider-tamanho"
                     min="14" max="28" step="1"
                     value="<?= (int)$prefs['tamanho_fonte'] ?>"
                     aria-label="Tamanho da fonte" />
              <span style="font-size:1.1rem;opacity:0.6">A</span>
              <span class="config-valor" id="valor-tamanho"><?= (int)$prefs['tamanho_fonte'] ?>px</span>
            </div>
          </div>

          <div class="config-grupo">
            <span class="config-label">Espaçamento entre linhas</span>
            <div class="config-slider-row">
              <i class="fa-solid fa-align-justify" style="font-size:0.75rem;opacity:0.5" aria-hidden="true"></i>
              <input type="range" class="config-slider" id="slider-linha"
                     min="1.4" max="2.4" step="0.1"
                     value="<?= number_format((float)$prefs['altura_linha'], 1, '.', '') ?>"
                     aria-label="Altura de linha" />
              <i class="fa-solid fa-align-justify" style="font-size:1rem;opacity:0.5" aria-hidden="true"></i>
              <span class="config-valor" id="valor-linha"><?= number_format((float)$prefs['altura_linha'], 1, '.', '') ?></span>
            </div>
          </div>

          <div class="config-grupo">
            <span class="config-label">Largura da coluna</span>
            <div class="config-opcoes">
              <?php foreach (['estreita'=>'Estreita','media'=>'Média','larga'=>'Larga'] as $val => $label): ?>
              <button class="config-op-btn <?= $prefs['largura_coluna'] === $val ? 'ativo' : '' ?>"
                      data-config-largura="<?= $val ?>">
                <?= $label ?>
              </button>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="config-grupo">
            <span class="config-label">Cor do fundo</span>
            <div class="fundo-amostras" role="group" aria-label="Escolha a cor do fundo">
              <?php foreach (['branco'=>'Branco','bege'=>'Bege (padrão)','cinza'=>'Cinza','preto'=>'Preto (noturno)'] as $val => $label): ?>
              <button class="fundo-amostra <?= $prefs['fundo_leitura'] === $val ? 'ativo' : '' ?>"
                      data-fundo="<?= $val ?>"
                      title="Fundo <?= $label ?>"
                      aria-label="Fundo <?= $label ?>">
              </button>
              <?php endforeach; ?>
            </div>
          </div>

        </section><!-- /config -->

        <!-- Anotações -->
        <section class="painel-secao" data-secao="anotacoes"
                 id="secao-anotacoes" role="tabpanel">
          <div class="anotacoes-lista" id="lista-anotacoes" aria-live="polite">
            <p style="font-size:0.85rem;opacity:0.6;text-align:center;padding:1rem">
              Nenhuma anotação ainda.
            </p>
          </div>
          <div class="anot-form">
            <textarea class="anot-textarea" id="anot-textarea"
                      placeholder="Escreva uma anotação sobre o capítulo atual…"
                      rows="4" aria-label="Nova anotação" maxlength="5000"></textarea>
            <div class="anot-cores" aria-label="Cor da anotação">
              <span style="font-size:0.75rem;opacity:0.7;margin-right:0.3rem">Cor:</span>
              <button class="anot-cor-btn ativo" data-cor="#FFD700" style="background:#FFD700" title="Amarelo" aria-label="Cor amarela"></button>
              <button class="anot-cor-btn" data-cor="#50C878" style="background:#50C878" title="Verde"  aria-label="Cor verde"></button>
              <button class="anot-cor-btn" data-cor="#FF69B4" style="background:#FF69B4" title="Rosa"   aria-label="Cor rosa"></button>
              <button class="anot-cor-btn" data-cor="#4682DC" style="background:#4682DC" title="Azul"   aria-label="Cor azul"></button>
            </div>
            <button class="btn-salvar-anot" id="btn-salvar-anot">
              <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
              Salvar anotação
            </button>
          </div>
        </section>

        <!-- Marcações -->
        <section class="painel-secao" data-secao="marcacoes"
                 id="secao-marcacoes" role="tabpanel">
          <p style="font-size:0.8rem;color:var(--texto-3);margin-bottom:1rem;line-height:1.5">
            <i class="fa-solid fa-info-circle" aria-hidden="true"></i>
            Selecione um trecho do texto e use o menu que aparece para marcá-lo.
          </p>
          <div class="marcacoes-lista" id="lista-marcacoes" aria-live="polite">
            <p style="font-size:0.85rem;opacity:0.6;text-align:center;padding:1rem">
              Nenhum trecho marcado ainda.
            </p>
          </div>
        </section>

        <!-- Avaliação -->
        <section class="painel-secao" data-secao="avaliacao"
                 id="secao-avaliacao" role="tabpanel">
          <p style="font-size:0.9rem;line-height:1.6;margin-bottom:1.25rem;opacity:0.8">
            Como você avalia este livro?
          </p>
          <div class="estrelas-leitor" role="group" aria-label="Avalie o livro">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <span class="estrela-leitor <?= $i <= $avaliacaoAtual ? 'iluminada' : '' ?>"
                  role="button" tabindex="0"
                  aria-label="<?= $i ?> <?= $i === 1 ? 'estrela' : 'estrelas' ?>"
                  data-nota="<?= $i ?>">★</span>
            <?php endfor; ?>
          </div>
          <p style="font-size:0.78rem;opacity:0.55;text-align:center;margin-top:0.5rem">
            Sua avaliação é salva automaticamente e ajuda outros leitores.
          </p>
          <hr style="border:none;border-top:1px solid var(--leitor-borda);margin:1.5rem 0">
          <p style="font-size:0.85rem;opacity:0.7;line-height:1.6">
            Quer deixar um comentário mais completo?
          </p>
          <a href="../livros/<?= $slug ?>.html#comentarios"
             style="display:inline-flex;align-items:center;gap:0.5rem;margin-top:0.5rem;color:var(--ouro);font-size:0.85rem;text-decoration:none">
            <i class="fa-solid fa-comment" aria-hidden="true"></i>
            Escrever comentário na página do livro
          </a>
        </section>

      </div><!-- /.painel-conteudo -->
    </aside>

  </div><!-- /.leitor-layout -->

<?php endif; // fim do bloco de acesso ?>

</div><!-- /#leitor-wrapper -->

<!-- ══ MENU DE SELEÇÃO DE TEXTO ═══════════════════════════════ -->
<div class="leitor-menu-selecao" id="menu-selecao" role="toolbar"
     aria-label="Opções para o texto selecionado">
  <button class="sel-btn sel-btn-amarelo" data-marca-cor="amarela" title="Marcar em amarelo" aria-label="Marcar em amarelo"><i class="fa-solid fa-highlighter" aria-hidden="true"></i></button>
  <button class="sel-btn sel-btn-verde"   data-marca-cor="verde"   title="Marcar em verde"   aria-label="Marcar em verde"  ><i class="fa-solid fa-highlighter" aria-hidden="true"></i></button>
  <button class="sel-btn sel-btn-rosa"    data-marca-cor="rosa"    title="Marcar em rosa"    aria-label="Marcar em rosa"   ><i class="fa-solid fa-highlighter" aria-hidden="true"></i></button>
  <button class="sel-btn sel-btn-azul"    data-marca-cor="azul"    title="Marcar em azul"    aria-label="Marcar em azul"   ><i class="fa-solid fa-highlighter" aria-hidden="true"></i></button>
  <button class="sel-btn sel-btn-anot" id="btn-sel-anotar" title="Criar anotação com este trecho" aria-label="Criar anotação"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>
</div>

<!-- ══ TOAST ══════════════════════════════════════════════════ -->
<div class="leitor-toast" id="leitor-toast" role="status" aria-live="polite"></div>

<!-- ══ RODAPÉ LEGAL ═══════════════════════════════════════════ -->
<footer class="leitor-rodape-legal" aria-label="Informações legais">
  <a href="documentacao-legal.html" target="_blank" rel="noopener"
     title="Termos de uso, direitos autorais e política de privacidade do leitor">
    <i class="fa-solid fa-scale-balanced" aria-hidden="true"></i>
    Termos de uso do leitor
  </a>
  <span class="leitor-rodape-sep" aria-hidden="true">·</span>
  <span>© <?= date('Y') ?> Robério Diógenes</span>
  <span class="leitor-rodape-sep" aria-hidden="true">·</span>
  <span>Conteúdo protegido pela Lei nº 9.610/98</span>
</footer>

<!-- ══ SCRIPTS ════════════════════════════════════════════════ -->
<?php if ($logado && $temAcesso): ?>
<script src="../js/leitor.js"></script>
<script>
  // Injeta avaliação inicial nas estrelas já renderizadas pelo PHP
  // (o JS do leitor.js vai sobrescrever quando carregarAvaliacao() rodar,
  //  mas isso garante que as estrelas já apareçam corretas imediatamente)
  document.querySelectorAll('.estrela-leitor').forEach(el => {
    el.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        avaliar(parseInt(el.dataset.nota));
      }
    });
  });
</script>
<?php else: ?>
<script>
  // Sem acesso: JS mínimo — apenas garante que não há erros de referência
  // O leitor.js NÃO é carregado para usuários sem acesso (economia de banda)
  console.info('[Leitor] Acesso não autorizado — JS do leitor não carregado.');
</script>
<?php endif; ?>

</body>
</html>
