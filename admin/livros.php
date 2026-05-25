<?php
session_name('rd_admin_sess');
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../backend/config.php';
$pdo = db();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"><title>Admin — <?= ucfirst('livros') ?> | Robério Diógenes</title>
  <meta name="robots" content="noindex">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">
  <style>*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}body{font-family:'Segoe UI',sans-serif;background:#0D0A07;color:#E8DCC8;display:flex;align-items:center;justify-content:center;min-height:100vh;flex-direction:column;gap:1rem;padding:2rem}.card{background:#1C1408;border:1px solid rgba(184,134,11,.25);border-radius:12px;padding:2.5rem;text-align:center;max-width:420px}.titulo{font-family:Georgia,serif;font-size:1.5rem;color:#B8860B;margin-bottom:.75rem}.sub{font-size:.9rem;color:#8C7D65;line-height:1.7}.btn{display:inline-flex;align-items:center;gap:.5rem;margin-top:1.5rem;padding:.7rem 1.4rem;background:#B8860B;color:#1A0F00;border-radius:8px;text-decoration:none;font-size:.78rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase}</style>
</head>
<body>
<div class="card">
  <h1 class="titulo"><?= ucfirst('livros') ?></h1>
  <p class="sub">Esta seção está em desenvolvimento e será implementada na próxima sprint do painel admin.</p>
  <a href="index.php" class="btn"><i class="fa fa-arrow-left"></i> Voltar ao dashboard</a>
</div>
</body>
</html>
