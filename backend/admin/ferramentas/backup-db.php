<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/admin/ferramentas/backup-db.php
   Gera e baixa um dump SQL completo do banco de dados.
   Acesso restrito: só funciona no servidor (não expõe em produção sem senha).
   ================================================================ */

require_once __DIR__ . '/../../config.php';

/* ── Proteção mínima: senha via query string ─────────────────── */
// Troque por uma senha forte depois de subir o arquivo
define('BACKUP_SENHA', 'RD_backup_2026');

if (($_GET['senha'] ?? '') !== BACKUP_SENHA) {
    http_response_code(403);
    echo '<p style="font-family:sans-serif">Acesso negado. Use: <code>?senha=SUA_SENHA</code></p>';
    exit;
}

/* ── Conexão direta (não usa PDO para o dump) ────────────────── */
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$mysqli->set_charset('utf8mb4');

if ($mysqli->connect_error) {
    die('Erro de conexão: ' . $mysqli->connect_error);
}

/* ── Nome do arquivo ─────────────────────────────────────────── */
$arquivo = 'backup_' . DB_NAME . '_' . date('Y-m-d_H-i-s') . '.sql';

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $arquivo . '"');
header('Cache-Control: no-cache, no-store');

/* ── Cabeçalho do dump ───────────────────────────────────────── */
echo "-- ============================================================\n";
echo "-- Banco: " . DB_NAME . "\n";
echo "-- Gerado em: " . date('Y-m-d H:i:s') . "\n";
echo "-- Site: roberiodiogenes.com\n";
echo "-- ============================================================\n\n";
echo "SET NAMES utf8mb4;\n";
echo "SET FOREIGN_KEY_CHECKS = 0;\n\n";

/* ── Listar todas as tabelas ─────────────────────────────────── */
$tabelas = [];
$res = $mysqli->query("SHOW TABLES");
while ($row = $res->fetch_row()) {
    $tabelas[] = $row[0];
}

/* ── Dump de cada tabela ─────────────────────────────────────── */
foreach ($tabelas as $tabela) {

    echo "-- ------------------------------------------------------------\n";
    echo "-- Tabela: `{$tabela}`\n";
    echo "-- ------------------------------------------------------------\n\n";

    /* DROP + CREATE */
    $res = $mysqli->query("SHOW CREATE TABLE `{$tabela}`");
    $row = $res->fetch_assoc();
    $create = $row['Create Table'] ?? $row['Create View'] ?? '';
    echo "DROP TABLE IF EXISTS `{$tabela}`;\n";
    echo $create . ";\n\n";

    /* Dados */
    $res = $mysqli->query("SELECT * FROM `{$tabela}`");
    if ($res->num_rows === 0) {
        echo "-- (sem registros)\n\n";
        continue;
    }

    echo "INSERT INTO `{$tabela}` VALUES\n";
    $linhas = [];
    while ($row = $res->fetch_row()) {
        $vals = array_map(function ($v) use ($mysqli) {
            if ($v === null) return 'NULL';
            return "'" . $mysqli->real_escape_string($v) . "'";
        }, $row);
        $linhas[] = '(' . implode(', ', $vals) . ')';
    }
    /* Agrupa em blocos de 500 para não travar importações */
    foreach (array_chunk($linhas, 500) as $i => $bloco) {
        if ($i > 0) echo ";\nINSERT INTO `{$tabela}` VALUES\n";
        echo implode(",\n", $bloco) . ";\n";
    }
    echo "\n";
}

echo "SET FOREIGN_KEY_CHECKS = 1;\n";
echo "-- Fim do backup\n";

$mysqli->close();
