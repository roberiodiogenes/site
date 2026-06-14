<?php
/* ================================================================
   ROBÉRIO DIÓGENES — admin/reset-admin-temp.php
   Script TEMPORÁRIO para criar/resetar o admin no HostGator.

   ⚠ APAGUE IMEDIATAMENTE após usar.
   ================================================================ */

// Chave secreta — obrigatória na URL: ?key=RD2025reset
define('CHAVE_SECRETA', 'RD2025reset');

if (($_GET['key'] ?? '') !== CHAVE_SECRETA) {
    http_response_code(404);
    die('Not found.');
}

require_once __DIR__ . '/../backend/config.php';

$nova_senha = 'RD@Admin2025!';
$hash       = password_hash($nova_senha, PASSWORD_BCRYPT, ['cost' => 12]);
$msgs       = [];
$ok         = false;

try {
    $pdo = db();

    // Criar tabela se não existir
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
    $msgs[] = ['ok', 'Tabela admin_users verificada/criada.'];

    // Inserir ou atualizar
    $pdo->prepare(
        "INSERT INTO admin_users (username, password)
         VALUES ('admin', ?)
         ON DUPLICATE KEY UPDATE password = VALUES(password)"
    )->execute([$hash]);
    $msgs[] = ['ok', 'Usuário admin criado/atualizado.'];

    // Verificar
    $row = $pdo->query("SELECT password FROM admin_users WHERE username='admin' LIMIT 1")->fetch();
    if ($row && password_verify($nova_senha, $row['password'])) {
        $msgs[] = ['ok', 'Senha verificada com sucesso.'];
        $ok = true;
    } else {
        $msgs[] = ['err', 'Falha ao verificar a senha.'];
    }

} catch (Exception $e) {
    $msgs[] = ['err', 'Erro: ' . $e->getMessage()];
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Reset Admin</title>
<style>
body{font-family:sans-serif;background:#0D0A07;color:#E8DCC8;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
.card{background:#1C1408;border:1px solid rgba(184,134,11,.3);border-radius:10px;padding:2rem;max-width:440px;width:100%}
h2{color:#B8860B;font-family:Georgia,serif;margin:0 0 1.25rem}
.m{padding:.45rem .8rem;border-radius:5px;font-size:.85rem;margin-bottom:.5rem}
.ok{background:rgba(46,125,50,.15);border:1px solid rgba(46,125,50,.3);color:#4CAF50}
.err{background:rgba(192,57,43,.12);border:1px solid rgba(192,57,43,.3);color:#e74c3c}
.box{background:rgba(184,134,11,.08);border:1px solid rgba(184,134,11,.25);border-radius:8px;padding:1rem;margin-top:1.25rem}
.lbl{font-size:.6rem;letter-spacing:.2em;text-transform:uppercase;color:#B8860B;margin-bottom:.75rem}
.row{display:flex;gap:1rem;margin-bottom:.4rem;font-size:.9rem}
.k{color:#8C7D65;min-width:70px}
.v{color:#E8DCC8;font-weight:600;font-family:monospace}
.aviso{background:rgba(192,57,43,.1);border:1px solid rgba(192,57,43,.3);border-radius:6px;padding:.85rem;margin-top:1rem;font-size:.8rem;color:#e74c3c;line-height:1.5}
a.btn{display:inline-block;margin-top:1.25rem;padding:.6rem 1.2rem;background:#B8860B;color:#1A0F00;border-radius:5px;font-weight:700;font-size:.78rem;text-transform:uppercase;letter-spacing:.1em;text-decoration:none}
</style>
</head>
<body>
<div class="card">
  <h2>Reset Admin</h2>
  <?php foreach ($msgs as [$t, $m]): ?>
    <div class="m <?= $t ?>"><?= htmlspecialchars($m) ?></div>
  <?php endforeach; ?>

  <?php if ($ok): ?>
    <div class="box">
      <div class="lbl">Credenciais</div>
      <div class="row"><span class="k">Usuário</span><span class="v">admin</span></div>
      <div class="row"><span class="k">Senha</span><span class="v">RD@Admin2025!</span></div>
      <div class="row"><span class="k">URL</span><span class="v">admin/login.php</span></div>
    </div>
    <div class="aviso">
      ⚠ <strong>Apague este arquivo agora:</strong><br>
      <code>admin/reset-admin-temp.php</code><br>
      Pelo File Manager do HostGator ou via FTP.
    </div>
    <a href="login.php" class="btn">Ir para o Login →</a>
  <?php endif; ?>
</div>
</body>
</html>
