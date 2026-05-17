<?php
/**
 * admin/index.php — Painel administrativo
 * Login + visualização de inscritos na newsletter
 */

session_start();
require_once __DIR__ . '/../config/db.php';

// ─── Logout ───────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// ─── Processar login ──────────────────────────────────────────
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SESSION['admin'])) {
    $user  = trim($_POST['username'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($user && $pass) {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT id, password FROM admin_users WHERE username = :u LIMIT 1");
        $stmt->execute([':u' => $user]);
        $row  = $stmt->fetch();

        if ($row && password_verify($pass, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin']    = $row['id'];
            $_SESSION['username'] = htmlspecialchars($user);
        } else {
            $erro = 'Usuário ou senha incorretos.';
            // Delay para dificultar força bruta
            sleep(1);
        }
    }
}

$logado = isset($_SESSION['admin']);

// ─── Dados do painel (só se logado) ──────────────────────────
$inscritos   = [];
$totalAtivos = 0;
$totalGeral  = 0;
$busca       = '';
$pagina      = max(1, (int)($_GET['p'] ?? 1));
$porPagina   = 20;
$offset      = ($pagina - 1) * $porPagina;
$exportar    = isset($_GET['export']) && $logado;

if ($logado) {
    $pdo     = getDB();
    $busca   = trim($_GET['q'] ?? '');
    $status  = $_GET['status'] ?? 'ativo';

    $where   = "WHERE status = :status";
    $params  = [':status' => in_array($status, ['ativo','descadastrado']) ? $status : 'ativo'];

    if ($busca) {
        $where  .= " AND email LIKE :q";
        $params[':q'] = "%$busca%";
    }

    // Total
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM newsletter $where");
    $stmt->execute($params);
    $totalFiltro = (int) $stmt->fetchColumn();

    // Totais gerais
    $totalAtivos = (int) $pdo->query("SELECT COUNT(*) FROM newsletter WHERE status='ativo'")->fetchColumn();
    $totalGeral  = (int) $pdo->query("SELECT COUNT(*) FROM newsletter")->fetchColumn();

    // Exportar CSV
    if ($exportar) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="inscritos_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8
        fputcsv($out, ['ID', 'E-mail', 'IP', 'Data de Inscrição', 'Status']);
        $stmt = $pdo->prepare("SELECT id, email, ip, created_at, status FROM newsletter $where ORDER BY created_at DESC");
        $stmt->execute($params);
        while ($row = $stmt->fetch()) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    // Listar com paginação
    $stmt = $pdo->prepare("SELECT id, email, ip, created_at, status FROM newsletter $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit',  $porPagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,    PDO::PARAM_INT);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->execute();
    $inscritos = $stmt->fetchAll();

    $totalPaginas = (int) ceil($totalFiltro / $porPagina);

    // Descadastrar via POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['descadastrar'])) {
        $id = (int) $_POST['descadastrar'];
        $pdo->prepare("UPDATE newsletter SET status='descadastrado' WHERE id=:id")->execute([':id' => $id]);
        header("Location: index.php?p=$pagina&q=" . urlencode($busca) . "&status=$status");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Painel Admin — Robério Diógenes</title>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600&family=EB+Garamond:ital,wght@0,400;1,400&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --bg: #0f0c09; --bg2: #1a1410; --bg3: #201a14;
      --text: #e8dfd2; --muted: #9e8672; --light: #5a4a3a;
      --gold: #c9a24a; --gold2: #e0be6e;
      --accent: #c4873f; --danger: #c0392b; --success: #27ae60;
      --border: #3a2e22;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: var(--bg); color: var(--text); font-family: 'EB Garamond', serif; font-size: 16px; min-height: 100vh; }

    /* ── LOGIN ── */
    .login-wrap {
      min-height: 100vh; display: flex; align-items: center; justify-content: center;
      background: radial-gradient(ellipse at center, #1a1208 0%, #0a0806 100%);
    }
    .login-box {
      width: 100%; max-width: 400px; padding: 3rem 2.5rem;
      border: 1px solid var(--border); background: var(--bg2);
    }
    .login-box h1 { font-family: 'Cinzel', serif; font-size: 1.3rem; color: var(--gold); text-align: center; margin-bottom: 0.4rem; letter-spacing: 0.1em; }
    .login-box p { font-style: italic; color: var(--muted); font-size: 0.9rem; text-align: center; margin-bottom: 2rem; }
    .form-group { margin-bottom: 1.2rem; }
    .form-group label { display: block; font-family: 'Cinzel', serif; font-size: 0.65rem; letter-spacing: 0.18em; text-transform: uppercase; color: var(--muted); margin-bottom: 0.5rem; }
    .form-group input { width: 100%; background: var(--bg3); border: 1px solid var(--border); color: var(--text); font-family: 'EB Garamond', serif; font-size: 1rem; padding: 0.75rem 1rem; outline: none; transition: border-color 0.3s; }
    .form-group input:focus { border-color: var(--gold); }
    .btn-login { width: 100%; font-family: 'Cinzel', serif; font-size: 0.72rem; letter-spacing: 0.2em; text-transform: uppercase; color: var(--bg); background: var(--gold); border: none; padding: 0.9rem; cursor: pointer; transition: background 0.3s; margin-top: 0.5rem; }
    .btn-login:hover { background: var(--gold2); }
    .erro { background: rgba(192,57,43,0.15); border: 1px solid var(--danger); color: #e74c3c; font-size: 0.9rem; padding: 0.7rem 1rem; margin-bottom: 1.2rem; text-align: center; }

    /* ── PAINEL ── */
    .admin-layout { display: grid; grid-template-columns: 220px 1fr; min-height: 100vh; }

    /* Sidebar */
    .sidebar { background: var(--bg2); border-right: 1px solid var(--border); padding: 2rem 1.5rem; display: flex; flex-direction: column; gap: 2rem; }
    .sidebar-brand { font-family: 'Cinzel', serif; font-size: 0.85rem; letter-spacing: 0.1em; color: var(--gold); }
    .sidebar-brand small { display: block; font-family: 'EB Garamond', serif; font-size: 0.8rem; color: var(--muted); font-style: italic; letter-spacing: 0; margin-top: 0.2rem; }
    .sidebar-nav { list-style: none; display: flex; flex-direction: column; gap: 0.4rem; }
    .sidebar-nav a { display: block; font-family: 'Cinzel', serif; font-size: 0.68rem; letter-spacing: 0.12em; text-transform: uppercase; color: var(--muted); text-decoration: none; padding: 0.55rem 0.8rem; border-radius: 2px; transition: all 0.2s; }
    .sidebar-nav a:hover, .sidebar-nav a.active { color: var(--gold); background: rgba(201,162,74,0.08); }
    .sidebar-nav a.danger { color: #e74c3c; }
    .sidebar-footer { margin-top: auto; font-size: 0.78rem; color: var(--light); font-style: italic; }

    /* Main */
    .main-content { padding: 2.5rem; overflow-x: auto; }
    .page-header { margin-bottom: 2rem; }
    .page-header h2 { font-family: 'Cinzel', serif; font-size: 1.2rem; color: var(--gold); letter-spacing: 0.08em; }
    .page-header p { font-style: italic; color: var(--muted); font-size: 0.9rem; margin-top: 0.3rem; }

    /* Stats */
    .stats { display: flex; gap: 1.5rem; margin-bottom: 2rem; flex-wrap: wrap; }
    .stat-card { background: var(--bg2); border: 1px solid var(--border); padding: 1.2rem 1.5rem; min-width: 140px; }
    .stat-num { font-family: 'Cinzel', serif; font-size: 2rem; color: var(--gold); line-height: 1; }
    .stat-label { font-size: 0.72rem; font-family: 'Cinzel', serif; letter-spacing: 0.15em; text-transform: uppercase; color: var(--muted); margin-top: 0.3rem; }

    /* Filtros */
    .filters { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center; }
    .filters form { display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center; }
    .filters input[type=text] { background: var(--bg2); border: 1px solid var(--border); color: var(--text); font-family: 'EB Garamond', serif; font-size: 0.95rem; padding: 0.55rem 1rem; outline: none; width: 240px; }
    .filters input[type=text]:focus { border-color: var(--gold); }
    .filters select { background: var(--bg2); border: 1px solid var(--border); color: var(--text); font-family: 'EB Garamond', serif; font-size: 0.95rem; padding: 0.55rem 0.8rem; outline: none; cursor: pointer; }
    .btn-small { font-family: 'Cinzel', serif; font-size: 0.65rem; letter-spacing: 0.15em; text-transform: uppercase; padding: 0.55rem 1.1rem; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; }
    .btn-gold { background: var(--gold); color: var(--bg); }
    .btn-gold:hover { background: var(--gold2); }
    .btn-outline { background: transparent; color: var(--muted); border: 1px solid var(--border); }
    .btn-outline:hover { border-color: var(--gold); color: var(--gold); }
    .btn-danger-sm { background: transparent; color: var(--danger); border: 1px solid rgba(192,57,43,0.4); font-size: 0.62rem; padding: 0.3rem 0.7rem; }
    .btn-danger-sm:hover { background: rgba(192,57,43,0.1); }
    .export-link { margin-left: auto; }

    /* Tabela */
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
    thead th { font-family: 'Cinzel', serif; font-size: 0.62rem; letter-spacing: 0.18em; text-transform: uppercase; color: var(--muted); padding: 0.7rem 1rem; text-align: left; border-bottom: 1px solid var(--border); background: var(--bg2); }
    tbody td { padding: 0.75rem 1rem; border-bottom: 1px solid rgba(58,46,34,0.5); vertical-align: middle; }
    tbody tr:hover { background: rgba(201,162,74,0.04); }
    .badge { display: inline-block; font-family: 'Cinzel', serif; font-size: 0.6rem; letter-spacing: 0.1em; text-transform: uppercase; padding: 0.2rem 0.6rem; border-radius: 2px; }
    .badge-ativo { background: rgba(39,174,96,0.15); color: #2ecc71; border: 1px solid rgba(39,174,96,0.3); }
    .badge-desc { background: rgba(192,57,43,0.12); color: #e74c3c; border: 1px solid rgba(192,57,43,0.3); }
    .text-muted { color: var(--muted); font-style: italic; font-size: 0.88rem; }
    .empty-msg { text-align: center; padding: 3rem; color: var(--muted); font-style: italic; }

    /* Paginação */
    .pagination { display: flex; gap: 0.4rem; margin-top: 1.5rem; align-items: center; flex-wrap: wrap; }
    .pagination a, .pagination span { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; font-family: 'Cinzel', serif; font-size: 0.72rem; text-decoration: none; border: 1px solid var(--border); color: var(--muted); transition: all 0.2s; }
    .pagination a:hover { border-color: var(--gold); color: var(--gold); }
    .pagination span.current { background: var(--gold); border-color: var(--gold); color: var(--bg); }
    .pagination-info { font-style: italic; color: var(--muted); font-size: 0.85rem; margin-left: 0.5rem; }
  </style>
</head>
<body>

<?php if (!$logado): ?>
<!-- ════════════════ LOGIN ════════════════ -->
<div class="login-wrap">
  <div class="login-box">
    <h1>Robério Diógenes</h1>
    <p>Painel Administrativo</p>
    <?php if ($erro): ?>
      <div class="erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label for="username">Usuário</label>
        <input type="text" id="username" name="username" autocomplete="username" required autofocus/>
      </div>
      <div class="form-group">
        <label for="password">Senha</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required/>
      </div>
      <button type="submit" class="btn-login">Entrar</button>
    </form>
  </div>
</div>

<?php else: ?>
<!-- ════════════════ PAINEL ════════════════ -->
<?php
  $statusAtual = in_array($_GET['status'] ?? 'ativo', ['ativo','descadastrado']) ? $_GET['status'] : 'ativo';
?>
<div class="admin-layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      Admin
      <small>Olá, <?= $_SESSION['username'] ?></small>
    </div>
    <ul class="sidebar-nav">
      <li><a href="index.php?status=ativo" class="active">📋 Inscritos</a></li>
      <li><a href="index.php?export=1&status=<?= $statusAtual ?>&q=<?= urlencode($busca) ?>" class="btn-small">⬇ Exportar CSV</a></li>
      <li><a href="index.php?logout=1" class="danger">↩ Sair</a></li>
    </ul>
    <div class="sidebar-footer">
      roberiodiogenes.com<br/>
      <?= date('d/m/Y') ?>
    </div>
  </aside>

  <!-- Conteúdo principal -->
  <main class="main-content">
    <div class="page-header">
      <h2>Inscritos na Newsletter</h2>
      <p>Gerencie os leitores inscritos para receber novidades.</p>
    </div>

    <!-- Stats -->
    <div class="stats">
      <div class="stat-card">
        <div class="stat-num"><?= $totalAtivos ?></div>
        <div class="stat-label">Ativos</div>
      </div>
      <div class="stat-card">
        <div class="stat-num"><?= $totalGeral - $totalAtivos ?></div>
        <div class="stat-label">Descadastrados</div>
      </div>
      <div class="stat-card">
        <div class="stat-num"><?= $totalGeral ?></div>
        <div class="stat-label">Total geral</div>
      </div>
    </div>

    <!-- Filtros -->
    <div class="filters">
      <form method="GET">
        <input type="text" name="q" placeholder="Buscar e-mail..." value="<?= htmlspecialchars($busca) ?>"/>
        <select name="status">
          <option value="ativo"         <?= $statusAtual==='ativo'?'selected':'' ?>>Ativos</option>
          <option value="descadastrado" <?= $statusAtual==='descadastrado'?'selected':'' ?>>Descadastrados</option>
        </select>
        <button type="submit" class="btn-small btn-gold">Filtrar</button>
        <a href="index.php" class="btn-small btn-outline">Limpar</a>
      </form>
      <a href="index.php?export=1&status=<?= $statusAtual ?>&q=<?= urlencode($busca) ?>" class="btn-small btn-outline export-link">⬇ Exportar CSV</a>
    </div>

    <!-- Tabela -->
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>E-mail</th>
            <th>Data de inscrição</th>
            <th>IP</th>
            <th>Status</th>
            <th>Ação</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($inscritos)): ?>
            <tr><td colspan="6" class="empty-msg">Nenhum inscrito encontrado.</td></tr>
          <?php else: foreach ($inscritos as $i): ?>
          <tr>
            <td class="text-muted"><?= $i['id'] ?></td>
            <td><?= htmlspecialchars($i['email']) ?></td>
            <td class="text-muted"><?= date('d/m/Y H:i', strtotime($i['created_at'])) ?></td>
            <td class="text-muted"><?= htmlspecialchars($i['ip'] ?? '—') ?></td>
            <td>
              <span class="badge <?= $i['status']==='ativo' ? 'badge-ativo' : 'badge-desc' ?>">
                <?= $i['status'] === 'ativo' ? 'Ativo' : 'Descadastrado' ?>
              </span>
            </td>
            <td>
              <?php if ($i['status'] === 'ativo'): ?>
              <form method="POST" onsubmit="return confirm('Descadastrar este e-mail?')">
                <input type="hidden" name="descadastrar" value="<?= $i['id'] ?>"/>
                <button type="submit" class="btn-small btn-danger-sm">Descadastrar</button>
              </form>
              <?php else: ?>
              <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Paginação -->
    <?php if (($totalPaginas ?? 1) > 1): ?>
    <div class="pagination">
      <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
        <?php if ($p === $pagina): ?>
          <span class="current"><?= $p ?></span>
        <?php else: ?>
          <a href="?p=<?= $p ?>&q=<?= urlencode($busca) ?>&status=<?= $statusAtual ?>"><?= $p ?></a>
        <?php endif; ?>
      <?php endfor; ?>
      <span class="pagination-info"><?= $totalFiltro ?> resultado(s)</span>
    </div>
    <?php endif; ?>
  </main>
</div>
<?php endif; ?>
</body>
</html>
