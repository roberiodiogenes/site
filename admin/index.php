<?php
/* ================================================================
   ROBÉRIO DIÓGENES — admin/index.php
   Painel de administração protegido.

   Acesso: /admin/  (redireciona para login se não autenticado)
   Login:  /admin/login.php
   ================================================================ */

session_name('rd_admin_sess');
session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../backend/config.php';
$pdo = db();

/* ── Estatísticas do dashboard ─────────────────────────────── */
$stats = [];

$stats['usuarios']      = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE ativo=1")->fetchColumn();
$stats['assinaturas']   = $pdo->query("SELECT COUNT(*) FROM assinaturas WHERE status='ativa' AND expira_em > NOW()")->fetchColumn();
$stats['compras']       = $pdo->query("SELECT COUNT(*) FROM compras WHERE status='aprovada'")->fetchColumn();
$stats['receita_mes']   = $pdo->query("SELECT COALESCE(SUM(preco_pago),0) FROM compras WHERE status='aprovada' AND MONTH(comprado_em)=MONTH(NOW()) AND YEAR(comprado_em)=YEAR(NOW())")->fetchColumn();
$stats['receita_assin'] = $pdo->query("SELECT COALESCE(SUM(p.preco),0) FROM assinaturas a JOIN planos p ON p.id=a.plano_id WHERE a.status='ativa' AND MONTH(a.inicio_em)=MONTH(NOW()) AND YEAR(a.inicio_em)=YEAR(NOW())")->fetchColumn();

$receitaTotal = (float)$stats['receita_mes'] + (float)$stats['receita_assin'];

/* ── Últimos usuários ──────────────────────────────────────── */
$ultimosUsuarios = $pdo->query(
    "SELECT id, nome, email, created_at, ultimo_login, ativo
     FROM usuarios ORDER BY created_at DESC LIMIT 8"
)->fetchAll();

/* ── Últimas compras ───────────────────────────────────────── */
$ultimasCompras = $pdo->query(
    "SELECT c.id, u.nome, u.email, c.livro_slug, c.preco_pago, c.status, c.comprado_em
     FROM compras c JOIN usuarios u ON u.id=c.usuario_id
     ORDER BY c.comprado_em DESC LIMIT 8"
)->fetchAll();

/* ── Assinaturas ativas ────────────────────────────────────── */
$assinaturasAtivas = $pdo->query(
    "SELECT a.id, u.nome, u.email, p.nome AS plano, a.inicio_em, a.expira_em,
            DATEDIFF(a.expira_em, NOW()) AS dias_rest
     FROM assinaturas a
     JOIN usuarios u ON u.id=a.usuario_id
     JOIN planos   p ON p.id=a.plano_id
     WHERE a.status='ativa' AND a.expira_em > NOW()
     ORDER BY a.expira_em ASC LIMIT 10"
)->fetchAll();

$adminNome = htmlspecialchars($_SESSION['admin_nome'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Painel Admin | Robério Diógenes</title>
  <link rel="icon" type="image/png" href="../img/favicon.png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --ouro: #B8860B; --fundo: #0D0A07; --fundo-2: #151008; --fundo-card: #1C1408;
      --texto: #E8DCC8; --texto-2: #C8B898; --texto-3: #8C7D65;
      --borda: rgba(184,134,11,0.18); --borda-2: rgba(184,134,11,0.30);
      --raio: 8px; --raio-lg: 12px;
      --verde: #2E7D32; --vermelho: #c0392b; --azul: #1565C0;
    }
    body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--fundo); color: var(--texto); min-height: 100vh; display: flex; }

    /* ── Sidebar ── */
    .sidebar {
      width: 240px; background: var(--fundo-2); border-right: 1px solid var(--borda);
      display: flex; flex-direction: column; position: fixed; top: 0; left: 0; height: 100vh;
      overflow-y: auto; z-index: 100;
    }
    .sidebar-logo {
      padding: 1.5rem 1.25rem; border-bottom: 1px solid var(--borda);
      font-size: 0.8rem; letter-spacing: 0.2em; text-transform: uppercase; color: var(--ouro);
    }
    .sidebar-logo strong { display: block; font-size: 1rem; letter-spacing: 0.05em; }
    .sidebar-nav { padding: 1rem 0; flex: 1; }
    .nav-item {
      display: flex; align-items: center; gap: 0.75rem;
      padding: 0.7rem 1.25rem; color: var(--texto-3); text-decoration: none;
      font-size: 0.85rem; transition: all 0.15s; border-left: 3px solid transparent;
    }
    .nav-item:hover { color: var(--ouro); background: rgba(184,134,11,0.06); border-left-color: var(--ouro); }
    .nav-item.ativo  { color: var(--ouro); background: rgba(184,134,11,0.10); border-left-color: var(--ouro); }
    .nav-item i { width: 16px; text-align: center; font-size: 0.9rem; }
    .nav-sep { border: none; border-top: 1px solid var(--borda); margin: 0.5rem 1.25rem; }
    .sidebar-footer { padding: 1rem 1.25rem; border-top: 1px solid var(--borda); }
    .admin-info { font-size: 0.78rem; color: var(--texto-3); margin-bottom: 0.5rem; }
    .btn-sair {
      display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem;
      color: var(--vermelho); text-decoration: none; opacity: 0.7; transition: opacity 0.2s;
      background: none; border: none; cursor: pointer; padding: 0;
    }
    .btn-sair:hover { opacity: 1; }

    /* ── Main ── */
    .main { margin-left: 240px; flex: 1; padding: 2rem; min-height: 100vh; }
    .page-titulo {
      font-size: 1.5rem; font-weight: 500; color: var(--ouro); margin-bottom: 0.25rem;
      font-family: Georgia, serif;
    }
    .page-sub { font-size: 0.82rem; color: var(--texto-3); margin-bottom: 2rem; }

    /* ── Cards de stats ── */
    .stats-grade { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 1rem; margin-bottom: 2rem; }
    .stat-card {
      background: var(--fundo-card); border: 1px solid var(--borda);
      border-radius: var(--raio-lg); padding: 1.25rem 1.5rem;
      display: flex; align-items: center; gap: 1rem;
    }
    .stat-icone { font-size: 1.6rem; color: var(--ouro); opacity: 0.7; width: 36px; text-align: center; }
    .stat-valor { font-size: 1.8rem; font-weight: 700; line-height: 1; color: var(--texto); }
    .stat-label { font-size: 0.72rem; color: var(--texto-3); text-transform: uppercase; letter-spacing: 0.08em; margin-top: 0.2rem; }

    /* ── Tabelas ── */
    .secao { background: var(--fundo-card); border: 1px solid var(--borda); border-radius: var(--raio-lg); margin-bottom: 1.5rem; overflow: hidden; }
    .secao-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 1rem 1.5rem; border-bottom: 1px solid var(--borda);
    }
    .secao-titulo { font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: var(--ouro); }
    .secao-link { font-size: 0.72rem; color: var(--texto-3); text-decoration: none; transition: color 0.2s; }
    .secao-link:hover { color: var(--ouro); }
    table { width: 100%; border-collapse: collapse; }
    th { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--texto-3); padding: 0.6rem 1.5rem; text-align: left; border-bottom: 1px solid var(--borda); }
    td { padding: 0.7rem 1.5rem; font-size: 0.83rem; color: var(--texto-2); border-bottom: 1px solid rgba(255,255,255,0.04); vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: rgba(184,134,11,0.04); }

    /* ── Badges ── */
    .badge {
      display: inline-block; padding: 0.2rem 0.6rem; border-radius: 20px;
      font-size: 0.65rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;
    }
    .badge-verde    { background: rgba(46,125,50,0.15);  color: #4CAF50; border: 1px solid rgba(46,125,50,0.3); }
    .badge-vermelho { background: rgba(192,57,43,0.15);  color: #e74c3c; border: 1px solid rgba(192,57,43,0.3); }
    .badge-amarelo  { background: rgba(184,134,11,0.15); color: var(--ouro); border: 1px solid var(--borda-2); }
    .badge-cinza    { background: rgba(255,255,255,0.06); color: var(--texto-3); border: 1px solid rgba(255,255,255,0.1); }

    /* ── Botões de ação ── */
    .btn-acao {
      padding: 0.25rem 0.6rem; border-radius: 4px; border: 1px solid var(--borda);
      background: transparent; color: var(--texto-3); font-size: 0.72rem;
      cursor: pointer; transition: all 0.15s; text-decoration: none; display: inline-flex;
      align-items: center; gap: 0.3rem;
    }
    .btn-acao:hover { border-color: var(--ouro); color: var(--ouro); }
    .btn-acao.danger:hover { border-color: var(--vermelho); color: var(--vermelho); }

    /* Grade de 2 colunas */
    .grade-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }

    @media (max-width: 900px) {
      .sidebar { width: 60px; }
      .sidebar-logo, .nav-item span, .sidebar-footer { display: none; }
      .main { margin-left: 60px; }
      .grade-2 { grid-template-columns: 1fr; }
    }
    @media (max-width: 600px) {
      .main { padding: 1rem; }
      .stats-grade { grid-template-columns: 1fr 1fr; }
    }
  </style>
</head>
<body>

<!-- ══ SIDEBAR ═════════════════════════════════════════════════ -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <strong>Robério Diógenes</strong>
    Painel Admin
  </div>
  <nav class="sidebar-nav">
    <a href="index.php"         class="nav-item ativo"><i class="fa fa-gauge"></i><span>Dashboard</span></a>
    <a href="usuarios.php"      class="nav-item"><i class="fa fa-users"></i><span>Usuários</span></a>
    <a href="assinaturas.php"   class="nav-item"><i class="fa fa-crown"></i><span>Assinaturas</span></a>
    <a href="compras.php"       class="nav-item"><i class="fa fa-shopping-cart"></i><span>Compras</span></a>
    <hr class="nav-sep">
    <a href="livros.php"        class="nav-item"><i class="fa fa-book"></i><span>Livros</span></a>
    <a href="blog.php"          class="nav-item"><i class="fa fa-pen-nib"></i><span>Blog</span></a>
    <hr class="nav-sep">
    <a href="../index.html" target="_blank" class="nav-item"><i class="fa fa-globe"></i><span>Ver site</span></a>
  </nav>
  <div class="sidebar-footer">
    <div class="admin-info"><?= $adminNome ?></div>
    <form action="logout.php" method="post" style="display:inline">
      <button type="submit" class="btn-sair"><i class="fa fa-sign-out-alt"></i> Sair</button>
    </form>
  </div>
</aside>

<!-- ══ MAIN ════════════════════════════════════════════════════ -->
<main class="main">
  <h1 class="page-titulo">Dashboard</h1>
  <p class="page-sub"><?= date('l, d \d\e F \d\e Y') ?></p>

  <!-- Stats -->
  <div class="stats-grade">
    <div class="stat-card">
      <i class="fa fa-users stat-icone"></i>
      <div>
        <div class="stat-valor"><?= number_format((int)$stats['usuarios']) ?></div>
        <div class="stat-label">Usuários ativos</div>
      </div>
    </div>
    <div class="stat-card">
      <i class="fa fa-crown stat-icone"></i>
      <div>
        <div class="stat-valor"><?= number_format((int)$stats['assinaturas']) ?></div>
        <div class="stat-label">Assinantes ativos</div>
      </div>
    </div>
    <div class="stat-card">
      <i class="fa fa-shopping-cart stat-icone"></i>
      <div>
        <div class="stat-valor"><?= number_format((int)$stats['compras']) ?></div>
        <div class="stat-label">Compras aprovadas</div>
      </div>
    </div>
    <div class="stat-card">
      <i class="fa fa-dollar-sign stat-icone"></i>
      <div>
        <div class="stat-valor">R$ <?= number_format($receitaTotal, 0, ',', '.') ?></div>
        <div class="stat-label">Receita este mês</div>
      </div>
    </div>
  </div>

  <div class="grade-2">

    <!-- Últimos usuários -->
    <div class="secao">
      <div class="secao-header">
        <span class="secao-titulo"><i class="fa fa-users"></i> Últimos usuários</span>
        <a href="usuarios.php" class="secao-link">Ver todos →</a>
      </div>
      <table>
        <thead><tr><th>Nome</th><th>Cadastro</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($ultimosUsuarios as $u): ?>
        <tr>
          <td>
            <div style="font-weight:500;color:var(--texto)"><?= htmlspecialchars($u['nome']) ?></div>
            <div style="font-size:0.72rem;color:var(--texto-3)"><?= htmlspecialchars($u['email']) ?></div>
          </td>
          <td style="font-size:0.75rem"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
          <td><span class="badge <?= $u['ativo'] ? 'badge-verde' : 'badge-vermelho' ?>"><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Assinaturas próximas do vencimento -->
    <div class="secao">
      <div class="secao-header">
        <span class="secao-titulo"><i class="fa fa-crown"></i> Assinaturas ativas</span>
        <a href="assinaturas.php" class="secao-link">Ver todas →</a>
      </div>
      <table>
        <thead><tr><th>Assinante</th><th>Plano</th><th>Vence em</th></tr></thead>
        <tbody>
        <?php foreach ($assinaturasAtivas as $a): ?>
        <tr>
          <td>
            <div style="font-weight:500;color:var(--texto)"><?= htmlspecialchars($a['nome']) ?></div>
            <div style="font-size:0.72rem;color:var(--texto-3)"><?= htmlspecialchars($a['email']) ?></div>
          </td>
          <td><span class="badge badge-amarelo"><?= htmlspecialchars($a['plano']) ?></span></td>
          <td style="font-size:0.75rem">
            <?= date('d/m/Y', strtotime($a['expira_em'])) ?>
            <?php if ($a['dias_rest'] <= 7): ?>
            <span class="badge badge-vermelho" style="margin-left:0.3rem"><?= $a['dias_rest'] ?>d</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>

  <!-- Últimas compras -->
  <div class="secao">
    <div class="secao-header">
      <span class="secao-titulo"><i class="fa fa-shopping-cart"></i> Últimas compras</span>
      <a href="compras.php" class="secao-link">Ver todas →</a>
    </div>
    <table>
      <thead>
        <tr>
          <th>Cliente</th>
          <th>Livro</th>
          <th>Valor</th>
          <th>Status</th>
          <th>Data</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($ultimasCompras as $c): ?>
      <tr>
        <td>
          <div style="font-weight:500;color:var(--texto)"><?= htmlspecialchars($c['nome']) ?></div>
          <div style="font-size:0.72rem;color:var(--texto-3)"><?= htmlspecialchars($c['email']) ?></div>
        </td>
        <td><?= htmlspecialchars($c['livro_slug']) ?></td>
        <td>R$ <?= number_format((float)$c['preco_pago'], 2, ',', '.') ?></td>
        <td>
          <?php
          $badgeComp = ['aprovada'=>'badge-verde','pendente'=>'badge-amarelo','cancelada'=>'badge-vermelho','reembolsada'=>'badge-cinza'];
          $bc = $badgeComp[$c['status']] ?? 'badge-cinza';
          ?>
          <span class="badge <?= $bc ?>"><?= ucfirst($c['status']) ?></span>
        </td>
        <td style="font-size:0.75rem"><?= date('d/m/Y H:i', strtotime($c['comprado_em'])) ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</main>
</body>
</html>
