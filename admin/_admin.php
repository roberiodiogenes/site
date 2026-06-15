<?php
/* ================================================================
   ROBÉRIO DIÓGENES — admin/_admin.php
   Include compartilhado por todas as páginas do painel.

   Uso no topo de cada página admin:
     $ADMIN_PAGE = 'usuarios'; // slug da página atual
     require_once __DIR__ . '/_admin.php';

   Fornece:
     $pdo         — conexão PDO pronta
     $adminNome   — nome do admin logado
     fn adm_badge($status) — retorna HTML de badge colorido
     fn adm_esc($s)        — htmlspecialchars shortcut
     fn adm_data($s, $fmt) — formata data pt-BR
   ================================================================ */

// Suporte a páginas em subdiretórios (ex: ferramentas/epub.php)
// Defina $ADM_HREF = '../' e $ADM_ROOT = '../../' antes de incluir este arquivo.
$ADM_HREF = $ADM_HREF ?? '';   // prefixo para links do menu (vazio em admin/, '../' em admin/subdir/)
$ADM_ROOT = $ADM_ROOT ?? '../'; // caminho para a raiz do site (assets como favicon)

session_name('rd_admin_sess');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_id'])) {
    header('Location: ' . $ADM_HREF . 'login.php');
    exit;
}

require_once __DIR__ . '/../backend/config.php';
$pdo       = db();
$adminNome = htmlspecialchars($_SESSION['admin_nome'] ?? 'Admin');
$ADMIN_PAGE = $ADMIN_PAGE ?? 'dashboard';

/* ── Helpers ──────────────────────────────────────────────── */
function adm_esc(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function adm_data(string $s, string $fmt = 'd/m/Y'): string {
    if (!$s) return '—';
    return date($fmt, strtotime($s));
}
function adm_badge(string $status): string {
    $map = [
        'ativa'       => ['verde',    'Ativa'],
        'aprovada'    => ['verde',    'Aprovada'],
        'ativo'       => ['verde',    'Ativo'],
        'pendente'    => ['amarelo',  'Pendente'],
        'cancelada'   => ['vermelho', 'Cancelada'],
        'expirada'    => ['cinza',    'Expirada'],
        'reembolsada' => ['cinza',    'Reembolsada'],
        'inativo'     => ['vermelho', 'Inativo'],
    ];
    [$cor, $label] = $map[strtolower($status)] ?? ['cinza', ucfirst($status)];
    return "<span class=\"badge badge-{$cor}\">{$label}</span>";
}

/* ── Menu de navegação ─────────────────────────────────────── */
// Badge de comentários flagged pendentes de revisão
$_nComentariosPendentes = 0;
try {
    $_nComentariosPendentes = (int)$pdo->query(
        "SELECT COUNT(*) FROM comentarios c WHERE c.flagged=1
         AND NOT EXISTS (SELECT 1 FROM comentarios_flags_log fl WHERE fl.comentario_id=c.id AND fl.acao_tomada!='pendente')"
    )->fetchColumn();
} catch (Throwable $e) { /* Tabela ainda não criada */ }

// Badge de erros ortográficos pendentes no leitor
$_nErrosPendentes = 0;
try {
    $_nErrosPendentes = (int)$pdo->query(
        "SELECT COUNT(*) FROM leitor_erros WHERE resolvido=0"
    )->fetchColumn();
} catch (Throwable $e) { /* Tabela ainda não criada */ }

$MENU = [
    ['slug'=>'dashboard',   'href'=>'index.php',       'icon'=>'fa-gauge',       'label'=>'Dashboard'],
    ['slug'=>'usuarios',    'href'=>'usuarios.php',    'icon'=>'fa-users',       'label'=>'Usuários'],
    ['slug'=>'assinaturas', 'href'=>'assinaturas.php', 'icon'=>'fa-crown',       'label'=>'Assinaturas'],
    ['slug'=>'compras',     'href'=>'compras.php',     'icon'=>'fa-cart-shopping','label'=>'Compras'],
    'sep',
    ['slug'=>'livros',      'href'=>'livros.php',      'icon'=>'fa-book',        'label'=>'Livros'],
    ['slug'=>'blog',        'href'=>'blog.php',        'icon'=>'fa-pen-nib',     'label'=>'Blog &amp; Clusters'],
    ['slug'=>'enquetes',   'href'=>'enquetes.php',    'icon'=>'fa-chart-bar',   'label'=>'Enquetes'],
    ['slug'=>'comentarios', 'href'=>'comentarios.php', 'icon'=>'fa-comments',    'label'=>'Comentários',
     'badge' => $_nComentariosPendentes > 0 ? $_nComentariosPendentes : null],
    ['slug'=>'erros',      'href'=>'erros.php',      'icon'=>'fa-spell-check', 'label'=>'Erros no Leitor',
     'badge' => $_nErrosPendentes > 0 ? $_nErrosPendentes : null],
    'sep',
    ['slug'=>'marketing',      'href'=>'marketing.php',      'icon'=>'fa-bullhorn',       'label'=>'Marketing'],
    ['slug'=>'pre-lancamento', 'href'=>'pre-lancamento.php', 'icon'=>'fa-hourglass-start', 'label'=>'Lista de Espera'],
    ['slug'=>'crons',          'href'=>'crons.php',          'icon'=>'fa-robot',           'label'=>'Automações'],
    ['slug'=>'push',           'href'=>'push.php',           'icon'=>'fa-bell',            'label'=>'Push'],
    ['slug'=>'bio',            'href'=>'bio.php',            'icon'=>'fa-link',            'label'=>'Bio / Links'],
    ['slug'=>'ferramentas-epub', 'href'=>'ferramentas/epub.php', 'icon'=>'fa-scroll', 'label'=>'Gerador EPUB'],
    'sep',
    ['slug'=>'_site',          'href'=>'../index.html',       'icon'=>'fa-globe',    'label'=>'Ver site', 'target'=>'_blank'],
    'sep',
    ['slug'=>'configuracoes',  'href'=>'configuracoes.php',   'icon'=>'fa-sliders',  'label'=>'Configurações'],
];

/* ── CSS e início do HTML ──────────────────────────────────── */
$TITULO_MAP = [
    'dashboard'      => 'Dashboard',
    'usuarios'       => 'Usuários',
    'assinaturas'    => 'Assinaturas',
    'compras'        => 'Compras',
    'livros'         => 'Livros',
    'blog'           => 'Blog',
    'configuracoes'      => 'Configurações',
    'ferramentas-epub'   => 'Gerador de EPUB',
];
$tituloPagina = $TITULO_MAP[$ADMIN_PAGE] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="robots" content="noindex, nofollow"/>
  <title><?= $tituloPagina ?> | Admin | Robério Diógenes</title>
  <link rel="icon" type="image/png" href="<?= $ADM_ROOT ?>img/favicon.png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --ouro:#B8860B; --ouro-bg:rgba(184,134,11,.10);
      --fundo:#0D0A07; --fundo-2:#151008; --fundo-card:#1C1408; --fundo-input:rgba(255,255,255,.04);
      --texto:#E8DCC8; --texto-2:#C8B898; --texto-3:#8C7D65;
      --borda:rgba(184,134,11,.18); --borda-2:rgba(184,134,11,.30);
      --raio:6px; --raio-lg:10px;
      --verde:#2E7D32; --vermelho:#c0392b; --amarelo:#B8860B; --azul:#1565C0;
      --sidebar-w:230px;
    }
    body { font-family:'Segoe UI',system-ui,sans-serif; background:var(--fundo); color:var(--texto); min-height:100vh; display:flex; }

    /* ── SIDEBAR ── */
    .sidebar {
      width:var(--sidebar-w); background:var(--fundo-2); border-right:1px solid var(--borda);
      display:flex; flex-direction:column; position:fixed; top:0; left:0; height:100vh;
      overflow-y:auto; z-index:100; transition:width .2s;
    }
    .sidebar-logo { padding:1.25rem 1rem; border-bottom:1px solid var(--borda); }
    .sidebar-logo strong { display:block; font-family:Georgia,serif; font-size:.95rem; color:var(--ouro); }
    .sidebar-logo small  { font-size:.6rem; letter-spacing:.2em; text-transform:uppercase; color:var(--texto-3); }
    .sidebar-nav { padding:.5rem 0; flex:1; }
    .nav-item {
      display:flex; align-items:center; gap:.7rem;
      padding:.65rem 1rem; color:var(--texto-3); text-decoration:none;
      font-size:.83rem; transition:all .15s; border-left:3px solid transparent;
      white-space:nowrap; overflow:hidden;
    }
    .nav-item:hover { color:var(--ouro); background:var(--ouro-bg); border-left-color:var(--ouro); }
    .nav-item.ativo  { color:var(--ouro); background:var(--ouro-bg); border-left-color:var(--ouro); font-weight:600; }
    .nav-item i { width:16px; text-align:center; font-size:.85rem; flex-shrink:0; }
    .nav-sep { border:none; border-top:1px solid var(--borda); margin:.4rem .75rem; }
    .sidebar-footer { padding:.85rem 1rem; border-top:1px solid var(--borda); }
    .admin-info { font-size:.72rem; color:var(--texto-3); margin-bottom:.4rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .btn-sair { display:flex; align-items:center; gap:.4rem; font-size:.72rem; color:var(--vermelho); background:none; border:none; cursor:pointer; padding:0; opacity:.7; transition:opacity .2s; }
    .btn-sair:hover { opacity:1; }

    /* ── MAIN ── */
    .main { margin-left:var(--sidebar-w); flex:1; padding:1.75rem 2rem; min-height:100vh; }
    .page-header { margin-bottom:1.75rem; }
    .page-titulo { font-family:Georgia,serif; font-size:1.4rem; font-weight:400; color:var(--ouro); }
    .page-sub    { font-size:.78rem; color:var(--texto-3); margin-top:.2rem; }

    /* ── CARDS STAT ── */
    .stats-grade { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:1rem; margin-bottom:1.75rem; }
    .stat-card { background:var(--fundo-card); border:1px solid var(--borda); border-radius:var(--raio-lg); padding:1.1rem 1.25rem; display:flex; align-items:center; gap:.85rem; }
    .stat-icone { font-size:1.4rem; color:var(--ouro); opacity:.7; width:30px; text-align:center; flex-shrink:0; }
    .stat-valor { font-size:1.65rem; font-weight:700; line-height:1; color:var(--texto); }
    .stat-label { font-size:.65rem; color:var(--texto-3); text-transform:uppercase; letter-spacing:.08em; margin-top:.15rem; }

    /* ── SEÇÃO / TABELA ── */
    .secao { background:var(--fundo-card); border:1px solid var(--borda); border-radius:var(--raio-lg); margin-bottom:1.25rem; overflow:hidden; }
    .secao-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem; padding:.85rem 1.25rem; border-bottom:1px solid var(--borda); }
    .secao-titulo { font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:var(--ouro); display:flex; align-items:center; gap:.5rem; }
    .secao-acoes  { display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
    table { width:100%; border-collapse:collapse; }
    th { font-size:.63rem; text-transform:uppercase; letter-spacing:.1em; color:var(--texto-3); padding:.55rem 1.25rem; text-align:left; border-bottom:1px solid var(--borda); white-space:nowrap; }
    td { padding:.65rem 1.25rem; font-size:.82rem; color:var(--texto-2); border-bottom:1px solid rgba(255,255,255,.04); vertical-align:middle; }
    tr:last-child td { border-bottom:none; }
    tr:hover td { background:rgba(184,134,11,.04); }
    .td-nome { font-weight:500; color:var(--texto); font-size:.85rem; }
    .td-sub  { font-size:.7rem; color:var(--texto-3); margin-top:.1rem; }

    /* ── BADGES ── */
    .badge { display:inline-block; padding:.18rem .55rem; border-radius:20px; font-size:.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; white-space:nowrap; }
    .badge-verde    { background:rgba(46,125,50,.15);   color:#4CAF50; border:1px solid rgba(46,125,50,.3); }
    .badge-vermelho { background:rgba(192,57,43,.15);   color:#e74c3c; border:1px solid rgba(192,57,43,.3); }
    .badge-amarelo  { background:rgba(184,134,11,.15);  color:var(--ouro); border:1px solid var(--borda-2); }
    .badge-cinza    { background:rgba(255,255,255,.06); color:var(--texto-3); border:1px solid rgba(255,255,255,.1); }
    .badge-azul     { background:rgba(21,101,192,.15);  color:#42A5F5; border:1px solid rgba(21,101,192,.3); }

    /* ── BOTÕES ── */
    .btn { display:inline-flex; align-items:center; gap:.35rem; padding:.38rem .8rem; border-radius:var(--raio); font-size:.72rem; font-weight:600; letter-spacing:.05em; cursor:pointer; transition:all .15s; text-decoration:none; white-space:nowrap; }
    .btn-primario { background:var(--ouro); color:#1A0F00; border:none; }
    .btn-primario:hover { opacity:.85; }
    .btn-ghost    { background:transparent; border:1px solid var(--borda); color:var(--texto-3); }
    .btn-ghost:hover { border-color:var(--ouro); color:var(--ouro); }
    .btn-danger   { background:transparent; border:1px solid rgba(192,57,43,.4); color:#e74c3c; }
    .btn-danger:hover { background:rgba(192,57,43,.12); border-color:var(--vermelho); }
    .btn-sm { padding:.25rem .55rem; font-size:.68rem; }

    /* ── FORMULÁRIOS (filtros/busca) ── */
    .filtros { display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; }
    .filtros input, .filtros select {
      padding:.38rem .7rem; background:var(--fundo-input); border:1px solid var(--borda);
      border-radius:var(--raio); color:var(--texto); font-size:.78rem;
      transition:border-color .2s;
    }
    .filtros input:focus, .filtros select:focus { outline:none; border-color:var(--ouro); }
    .filtros input { min-width:180px; }
    .filtros select option { background:#1C1408; }

    /* ── PAGINAÇÃO ── */
    .paginacao { display:flex; align-items:center; justify-content:space-between; padding:.75rem 1.25rem; border-top:1px solid var(--borda); flex-wrap:wrap; gap:.5rem; }
    .pag-info  { font-size:.72rem; color:var(--texto-3); }
    .pag-btns  { display:flex; gap:.3rem; }
    .pag-btn   { padding:.3rem .65rem; background:var(--fundo-input); border:1px solid var(--borda); border-radius:var(--raio); color:var(--texto-3); font-size:.75rem; cursor:pointer; text-decoration:none; transition:all .15s; }
    .pag-btn:hover { border-color:var(--ouro); color:var(--ouro); }
    .pag-btn.ativo { background:var(--ouro); color:#1A0F00; border-color:var(--ouro); font-weight:700; }
    .pag-btn:disabled { opacity:.35; cursor:not-allowed; }

    /* ── MODAL ── */
    .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.65); z-index:500; display:flex; align-items:center; justify-content:center; padding:1rem; opacity:0; pointer-events:none; transition:opacity .2s; }
    .modal-overlay.aberto { opacity:1; pointer-events:all; }
    .modal-box { background:var(--fundo-card); border:1px solid var(--borda-2); border-radius:var(--raio-lg); padding:1.75rem; max-width:480px; width:100%; box-shadow:0 20px 60px rgba(0,0,0,.5); transform:translateY(12px); transition:transform .2s; }
    .modal-overlay.aberto .modal-box { transform:translateY(0); }
    .modal-titulo { font-family:Georgia,serif; font-size:1.1rem; color:var(--ouro); margin-bottom:1rem; }
    .modal-campo  { margin-bottom:.85rem; }
    .modal-campo label { display:block; font-size:.65rem; letter-spacing:.1em; text-transform:uppercase; color:var(--texto-3); margin-bottom:.35rem; }
    .modal-campo input, .modal-campo select, .modal-campo textarea {
      width:100%; padding:.6rem .75rem; background:var(--fundo-input);
      border:1px solid var(--borda); border-radius:var(--raio);
      color:var(--texto); font-size:.85rem; transition:border-color .2s;
    }
    .modal-campo input:focus, .modal-campo select:focus, .modal-campo textarea:focus { outline:none; border-color:var(--ouro); }
    .modal-btns { display:flex; gap:.5rem; justify-content:flex-end; margin-top:1.25rem; flex-wrap:wrap; }

    /* ── TOAST ── */
    .toast { position:fixed; bottom:1.5rem; right:1.5rem; background:var(--fundo-card); border:1px solid var(--ouro); color:var(--texto); padding:.65rem 1.1rem; border-radius:var(--raio-lg); font-size:.82rem; box-shadow:0 6px 24px rgba(0,0,0,.4); z-index:2000; opacity:0; transform:translateY(.75rem); transition:opacity .25s,transform .25s; pointer-events:none; max-width:320px; }
    .toast.visivel { opacity:1; transform:translateY(0); }
    .toast.erro { border-color:var(--vermelho); }

    /* ── VAZIO ── */
    .estado-vazio { text-align:center; padding:3rem 2rem; color:var(--texto-3); }
    .estado-vazio i { font-size:2rem; color:var(--ouro); opacity:.25; display:block; margin-bottom:.75rem; }
    .estado-vazio p { font-size:.88rem; }

    /* ── GRADE 2 COL ── */
    .grade-2 { display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; }

    @media (max-width:960px) {
      :root { --sidebar-w:56px; }
      .sidebar-logo span, .nav-item span, .sidebar-footer .admin-info, .sidebar-footer .btn-sair span { display:none; }
      .sidebar-logo { padding:.75rem; text-align:center; }
      .nav-item { justify-content:center; padding:.65rem; gap:0; }
      .sidebar-footer { padding:.75rem; text-align:center; }
      .grade-2 { grid-template-columns:1fr; }
    }
    @media (max-width:600px) {
      .main { padding:1rem; }
      .stats-grade { grid-template-columns:1fr 1fr; }
      th, td { padding:.5rem .75rem; }
    }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <strong>Robério Diógenes</strong>
    <small>Painel Admin</small>
  </div>
  <nav class="sidebar-nav">
    <?php foreach ($MENU as $item): ?>
      <?php if ($item === 'sep'): ?>
        <hr class="nav-sep">
      <?php else: ?>
        <a href="<?= $ADM_HREF . $item['href'] ?>"
           class="nav-item <?= $ADMIN_PAGE === $item['slug'] ? 'ativo' : '' ?>"
           <?= isset($item['target']) ? 'target="' . $item['target'] . '"' : '' ?>>
          <i class="fa <?= $item['icon'] ?>"></i>
          <span><?= $item['label'] ?></span>
          <?php if (!empty($item['badge'])): ?>
            <span style="margin-left:auto;background:#e74c3c;color:#fff;border-radius:12px;padding:.1rem .45rem;font-size:.58rem;font-weight:700;min-width:18px;text-align:center;flex-shrink:0"><?= (int)$item['badge'] ?></span>
          <?php endif; ?>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-footer">
    <div class="admin-info"><?= $adminNome ?></div>
    <form action="<?= $ADM_HREF ?>logout.php" method="post">
      <button type="submit" class="btn-sair">
        <i class="fa fa-sign-out-alt"></i>
        <span>Sair</span>
      </button>
    </form>
  </div>
</aside>

<!-- MAIN -->
<main class="main">
<?php
// JS de toast compartilhado (injetado no final de cada página)
ob_start();
?>
<div id="toastAdmin" class="toast" role="status" aria-live="polite"></div>
<script>
function toast(msg, tipo = 'ok') {
  const t = document.getElementById('toastAdmin');
  t.textContent = msg;
  t.className = 'toast visivel' + (tipo === 'erro' ? ' erro' : '');
  clearTimeout(t._t);
  t._t = setTimeout(() => t.classList.remove('visivel'), 3500);
}
function fecharModal(id) {
  document.getElementById(id)?.classList.remove('aberto');
}
function abrirModal(id) {
  document.getElementById(id)?.classList.add('aberto');
}
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.aberto').forEach(m => m.classList.remove('aberto'));
});
</script>
<?php
$ADMIN_FOOTER_HTML = ob_get_clean();
