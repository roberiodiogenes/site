<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/bio-embaixador.php
   Registra um Leitor Embaixador e retorna seu número único.

   POST { genero, sessao_id }
   ← { ok:true, numero:47, total:47, novo:true }
   ================================================================ */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderErro('Método não permitido.', 405);
}

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$genero    = substr(trim($body['genero']    ?? ''), 0, 20);
$sessao_id = substr(trim($body['sessao_id'] ?? ''), 0, 64);
$ip        = getIP();

$pdo = db();

/* ── Cria tabela se ainda não existir (primeiro acesso) ────── */
$pdo->exec("
    CREATE TABLE IF NOT EXISTS embaixadores (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        genero     VARCHAR(20)  DEFAULT NULL,
        sessao_id  VARCHAR(64)  DEFAULT NULL,
        ip         VARCHAR(45)  DEFAULT NULL,
        criado_em  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_sessao (sessao_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

/* ── Sessão já registrada? Devolve o mesmo número ──────────── */
if ($sessao_id) {
    $stmt = $pdo->prepare("SELECT id FROM embaixadores WHERE sessao_id = ? LIMIT 1");
    $stmt->execute([$sessao_id]);
    $existe = $stmt->fetchColumn();

    if ($existe) {
        $total = (int)$pdo->query("SELECT COUNT(*) FROM embaixadores")->fetchColumn();
        responderOk(['numero' => (int)$existe, 'total' => $total, 'novo' => false]);
    }
}

/* ── Novo embaixador ────────────────────────────────────────── */
try {
    $pdo->prepare(
        "INSERT IGNORE INTO embaixadores (genero, sessao_id, ip)
         VALUES (?, ?, ?)"
    )->execute([$genero ?: null, $sessao_id ?: null, $ip]);

    $numero = (int)$pdo->lastInsertId();
    $total  = (int)$pdo->query("SELECT COUNT(*) FROM embaixadores")->fetchColumn();

    responderOk(['numero' => $numero, 'total' => $total, 'novo' => true]);

} catch (Throwable $e) {
    error_log('[bio-embaixador] ' . $e->getMessage());
    /* Fallback: não bloqueia a experiência */
    responderOk(['numero' => 0, 'total' => 0, 'novo' => false]);
}
