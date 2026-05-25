<?php
/* ================================================================
   ROBÉRIO DIÓGENES — admin/login.php
   Login do painel administrativo — sessão separada do site público
   ================================================================ */

session_name('rd_admin_sess');
session_start();

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../backend/config.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user  = trim($_POST['username'] ?? '');
    $senha = $_POST['senha']         ?? '';

    if (!$user || !$senha) {
        $erro = 'Preencha usuário e senha.';
    } else {
        $pdo  = db();
        $stmt = $pdo->prepare("SELECT id, username, password FROM admin_users WHERE username = ? LIMIT 1");
        $stmt->execute([$user]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($senha, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_nome'] = $admin['username'];

            $pdo->prepare("UPDATE admin_users SET ultimo_login = NOW() WHERE id = ?")
                ->execute([$admin['id']]);

            header('Location: index.php');
            exit;
        } else {
            $erro = 'Usuário ou senha incorretos.';
            // Rate limiting simples via sessão
            $_SESSION['tentativas'] = ($_SESSION['tentativas'] ?? 0) + 1;
            if ($_SESSION['tentativas'] >= 5) {
                sleep(3);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Admin Login | Robério Diógenes</title>
  <link rel="icon" type="image/png" href="../img/favicon.png" />
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',system-ui,sans-serif;background:#0D0A07;color:#E8DCC8;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}
    .card{background:#1C1408;border:1px solid rgba(184,134,11,.25);border-radius:12px;padding:2.5rem 2rem;width:100%;max-width:380px}
    .logo{font-family:Georgia,serif;font-size:1.3rem;color:#B8860B;text-align:center;margin-bottom:.25rem}
    .logo small{display:block;font-size:.6rem;letter-spacing:.25em;text-transform:uppercase;color:#8C7D65;margin-top:.2rem}
    h1{font-size:.75rem;letter-spacing:.2em;text-transform:uppercase;color:#8C7D65;text-align:center;margin:1.5rem 0 1.75rem}
    .campo{margin-bottom:1rem}
    label{display:block;font-size:.72rem;color:#8C7D65;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.4rem}
    input{width:100%;padding:.7rem .9rem;background:rgba(255,255,255,.05);border:1px solid rgba(184,134,11,.2);border-radius:6px;color:#E8DCC8;font-size:.9rem;transition:border-color .2s}
    input:focus{outline:none;border-color:#B8860B}
    .btn{width:100%;padding:.8rem;background:#B8860B;color:#1A0F00;border:none;border-radius:6px;font-size:.8rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;cursor:pointer;margin-top:.5rem;transition:opacity .2s}
    .btn:hover{opacity:.85}
    .erro{background:rgba(192,57,43,.12);border:1px solid rgba(192,57,43,.3);color:#e74c3c;padding:.65rem .9rem;border-radius:6px;font-size:.82rem;margin-bottom:1rem;text-align:center}
    .rodape{text-align:center;margin-top:1.5rem;font-size:.72rem;color:#8C7D65}
    .rodape a{color:#B8860B;text-decoration:none}
  </style>
</head>
<body>
<div class="card">
  <div class="logo">Robério Diógenes<small>Painel Administrativo</small></div>
  <h1>Acesso restrito</h1>

  <?php if ($erro): ?>
  <div class="erro" role="alert"><i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <form method="post" action="login.php" autocomplete="off">
    <div class="campo">
      <label for="username">Usuário</label>
      <input type="text" id="username" name="username"
             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
             autocomplete="username" required autofocus />
    </div>
    <div class="campo">
      <label for="senha">Senha</label>
      <input type="password" id="senha" name="senha"
             autocomplete="current-password" required />
    </div>
    <button type="submit" class="btn">Entrar no painel</button>
  </form>

  <div class="rodape">
    <a href="../index.html">← Voltar ao site</a>
  </div>
</div>
</body>
</html>
