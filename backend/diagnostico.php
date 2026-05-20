<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Diagnóstico — Robério Diógenes</title>
<style>
  body { font-family: monospace; background: #1a1a1a; color: #eee; padding: 2rem; max-width: 700px; margin: 0 auto; }
  h1 { color: #b8860b; margin-bottom: 2rem; font-size: 1.2rem; }
  .item { margin-bottom: 0.75rem; padding: 0.6rem 1rem; border-radius: 6px; display: flex; gap: 1rem; align-items: flex-start; }
  .ok   { background: rgba(74,124,74,0.2); border-left: 3px solid #4a7c4a; }
  .erro { background: rgba(180,60,60,0.2); border-left: 3px solid #c0392b; }
  .aviso{ background: rgba(180,130,60,0.2); border-left: 3px solid #b8860b; }
  .badge { min-width: 60px; font-weight: bold; }
  .badge.s { color: #4a7c4a; }
  .badge.e { color: #c0392b; }
  .badge.w { color: #b8860b; }
  .detalhe { font-size: 0.85rem; color: #aaa; margin-top: 0.25rem; }
  hr { border-color: #333; margin: 1.5rem 0; }
  .aviso-segurança {
    background: rgba(180,60,60,0.3); border: 1px solid #c0392b;
    padding: 1rem; border-radius: 6px; margin-top: 2rem;
    font-size: 0.85rem; color: #e88;
  }
</style>
</head>
<body>
<h1>🔧 Diagnóstico do Site — Robério Diógenes</h1>

<?php
function linha(string $status, string $titulo, string $detalhe = ''): void {
    $cls   = $status === 'OK' ? 'ok'   : ($status === 'ERRO' ? 'erro' : 'aviso');
    $bcls  = $status === 'OK' ? 's'    : ($status === 'ERRO' ? 'e'    : 'w');
    echo "<div class='item $cls'>";
    echo "<span class='badge $bcls'>$status</span>";
    echo "<div><strong>$titulo</strong>";
    if ($detalhe) echo "<div class='detalhe'>$detalhe</div>";
    echo "</div></div>";
}

// ── 1. PHP ────────────────────────────────────────────────────
$phpVer = phpversion();
if (version_compare($phpVer, '8.0', '>=')) {
    linha('OK', 'PHP ' . $phpVer, 'Versão compatível.');
} else {
    linha('ERRO', 'PHP ' . $phpVer, 'O site requer PHP 8.0+. Atualize o XAMPP.');
}

// ── 2. PDO MySQL ──────────────────────────────────────────────
if (extension_loaded('pdo_mysql')) {
    linha('OK', 'PDO MySQL habilitado', 'Extensão necessária para o banco de dados está ativa.');
} else {
    linha('ERRO', 'PDO MySQL NÃO habilitado', 'Abra php.ini e descomente: extension=pdo_mysql');
}

// ── 3. Conexão com o banco ────────────────────────────────────
echo "<hr>";
require_once __DIR__ . '/config.php';

try {
    $pdo = db();
    linha('OK', 'Conexão com o banco de dados', 'Conectado com sucesso a "' . DB_NAME . '" em ' . DB_HOST . '.');

    // ── 4. Tabelas ────────────────────────────────────────────
    $tabelas = ['usuarios','newsletter','auth_log','visitas','visitas_log','admin_users','password_reset','livros_favoritos','downloads','comentarios','contato'];
    $stmt    = $pdo->query("SHOW TABLES");
    $existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<hr>";
    foreach ($tabelas as $t) {
        if (in_array($t, $existentes)) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            linha('OK', "Tabela [$t]", "$count registro(s).");
        } else {
            linha('ERRO', "Tabela [$t] NÃO encontrada", 'Execute o setup.sql novamente no phpMyAdmin.');
        }
    }

} catch (Exception $e) {
    linha('ERRO', 'Falha na conexão com o banco', $e->getMessage());
    echo "<hr>";
    echo "<div class='item aviso'><span class='badge w'>DICA</span><div>";
    echo "<strong>O que verificar:</strong><br>";
    echo "<div class='detalhe'>1. O MySQL está ligado no XAMPP Control Panel?<br>";
    echo "2. O banco <strong>" . DB_NAME . "</strong> existe no phpMyAdmin?<br>";
    echo "3. O usuário é <strong>" . DB_USER . "</strong> com senha vazia (padrão XAMPP)?<br>";
    echo "4. Rode o <code>setup.sql</code> no banco após criá-lo.</div>";
    echo "</div></div>";
}

// ── 5. Ambiente detectado ─────────────────────────────────────
echo "<hr>";
$amb = AMBIENTE;
$host = $_SERVER['SERVER_NAME'] ?? 'desconhecido';
linha(
    $amb === 'local' ? 'OK' : 'AVISO',
    'Ambiente: ' . strtoupper($amb),
    "SERVER_NAME = $host  |  SITE_URL = " . SITE_URL
);

// ── 6. Sessões ────────────────────────────────────────────────
iniciarSessao();
$_SESSION['teste_diagnostico'] = true;
if (isset($_SESSION['teste_diagnostico'])) {
    linha('OK', 'Sessões PHP funcionando', 'session_id = ' . session_id());
} else {
    linha('ERRO', 'Sessões PHP com problema', 'Verifique as permissões da pasta de sessões.');
}
?>

<div class="aviso-segurança">
  ⚠ <strong>Atenção:</strong> apague ou renomeie este arquivo (<code>diagnostico.php</code>)
  assim que terminar os testes. Nunca deixe no servidor de produção.
</div>

</body>
</html>
