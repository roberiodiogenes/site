<?php
/* ================================================================
   ROBÉRIO DIÓGENES — admin/setup-admin.php
   Script de emergência: cria/recria o usuário administrador.

   ⚠ USE UMA VEZ e depois APAGUE este arquivo do servidor.
   USUÁRIO: admin
   SENHA: RD@Admin2025!
   ================================================================ */

// Proteção simples: só funciona vindo do localhost
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, ['127.0.0.1', '::1', 'localhost'], true)) {
    http_response_code(403);
    die('Acesso permitido apenas em localhost.');
}

require_once __DIR__ . '/../backend/config.php';

$nova_senha = 'RD@Admin2025!';
$novo_hash  = password_hash($nova_senha, PASSWORD_BCRYPT, ['cost' => 12]);
$mensagens  = [];
$sucesso    = false;

try {
    $pdo = db();

    // 1. Criar tabela se não existir
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admin_users` (
          `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `username`     VARCHAR(80)  NOT NULL,
          `password`     VARCHAR(255) NOT NULL,
          `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `ultimo_login` DATETIME     DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $mensagens[] = '✅ Tabela admin_users verificada/criada.';

    // 2. Inserir ou atualizar o usuário admin
    $stmt = $pdo->prepare(
        "INSERT INTO admin_users (username, password)
         VALUES ('admin', ?)
         ON DUPLICATE KEY UPDATE password = VALUES(password)"
    );
    $stmt->execute([$novo_hash]);
    $mensagens[] = '✅ Usuário admin criado/atualizado com nova senha.';

    // 3. Verificar que a senha bate
    $check = $pdo->prepare("SELECT password FROM admin_users WHERE username='admin' LIMIT 1");
    $check->execute();
    $row = $check->fetch(PDO::FETCH_ASSOC);
    if ($row && password_verify($nova_senha, $row['password'])) {
        $mensagens[] = '✅ Senha verificada com sucesso — tudo certo!';
        $sucesso = true;
    } else {
        $mensagens[] = '❌ Falha ao verificar a senha. Tente novamente.';
    }

} catch (Exception $e) {
    $mensagens[] = '❌ Erro: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Setup Admin | Robério Diógenes</title>
  <style>
    body { font-family: 'Segoe UI', sans-serif; background: #0D0A07; color: #E8DCC8; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
    .card { background: #1C1408; border: 1px solid rgba(184,134,11,.3); border-radius: 12px; padding: 2.25rem; max-width: 480px; width: 100%; }
    h1 { font-family: Georgia, serif; color: #B8860B; font-size: 1.2rem; margin-bottom: 1.5rem; }
    .msg { padding: .55rem .85rem; border-radius: 6px; margin-bottom: .5rem; font-size: .88rem; }
    .ok  { background: rgba(46,125,50,.15); border: 1px solid rgba(46,125,50,.3); color: #4CAF50; }
    .err { background: rgba(192,57,43,.12); border: 1px solid rgba(192,57,43,.3); color: #e74c3c; }
    .cred-box { background: rgba(184,134,11,.08); border: 1px solid rgba(184,134,11,.25); border-radius: 8px; padding: 1.25rem; margin-top: 1.5rem; }
    .cred-titulo { font-size: .62rem; letter-spacing: .2em; text-transform: uppercase; color: #B8860B; margin-bottom: .85rem; }
    .cred-item { display: flex; gap: 1rem; margin-bottom: .45rem; font-size: .9rem; }
    .cred-label { color: #8C7D65; min-width: 80px; }
    .cred-valor { color: #E8DCC8; font-weight: 600; font-family: 'Courier New', monospace; }
    .aviso { background: rgba(192,57,43,.1); border: 1px solid rgba(192,57,43,.25); border-radius: 8px; padding: 1rem; margin-top: 1.25rem; font-size: .82rem; color: #e74c3c; line-height: 1.55; }
    .aviso strong { display: block; margin-bottom: .3rem; }
    .btn { display: inline-flex; align-items: center; gap: .4rem; margin-top: 1.5rem; padding: .65rem 1.25rem; background: #B8860B; color: #1A0F00; border-radius: 6px; font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; text-decoration: none; }
  </style>
</head>
<body>
<div class="card">
  <h1>⚙ Configuração do Administrador</h1>

  <?php foreach ($mensagens as $m): ?>
    <div class="msg <?= str_starts_with($m, '✅') ? 'ok' : 'err' ?>">
      <?= htmlspecialchars($m) ?>
    </div>
  <?php endforeach; ?>

  <?php if ($sucesso): ?>
    <div class="cred-box">
      <div class="cred-titulo">Credenciais para login</div>
      <div class="cred-item">
        <span class="cred-label">Usuário</span>
        <span class="cred-valor">admin</span>
      </div>
      <div class="cred-item">
        <span class="cred-label">Senha</span>
        <span class="cred-valor"><?= htmlspecialchars($nova_senha) ?></span>
      </div>
      <div class="cred-item">
        <span class="cred-label">URL</span>
        <span class="cred-valor">admin/login.php</span>
      </div>
    </div>

    <div class="aviso">
      <strong>⚠ Importante — faça isso agora:</strong>
      1. Acesse o painel com as credenciais acima.<br>
      2. <strong>Apague este arquivo</strong> do servidor imediatamente:<br>
      <code style="background:rgba(255,255,255,.06);padding:.2rem .4rem;border-radius:3px;font-size:.8rem">
        admin/setup-admin.php
      </code><br>
      3. Troque a senha depois de entrar.
    </div>

    <a href="login.php" class="btn">Ir para o Login →</a>
  <?php endif; ?>
</div>
</body>
</html>
