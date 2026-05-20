<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/visitas.php
   Endpoint: GET /backend/visitas.php
   - Registra visita única (por hash SHA-256 de IP + User-Agent)
   - Incrementa o contador global
   - Retorna o total de visitas únicas
   ================================================================ */

require_once __DIR__ . '/config.php';

// Só aceita GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderErro('Método não permitido.', 405);
}

$pdo = db();
$ip  = getIP();
$ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Hash anônimo do visitante (sem data — a data fica em coluna separada)
$hash = hash('sha256', $ip . '|' . $ua);
$hoje = date('Y-m-d');

// Tenta registrar esta visita (IGNORE descarta se (hash, data) já existe)
$novo = false;
try {
    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO visitas_log (visitor_hash, visit_date) VALUES (?, ?)"
    );
    $stmt->execute([$hash, $hoje]);
    $novo = $stmt->rowCount() > 0;
} catch (Exception $e) {
    // Silencioso — não bloqueia a resposta
}

// Se visita nova, incrementa o contador global
if ($novo) {
    $pdo->exec("UPDATE visitas SET total = total + 1 WHERE id = 1");
}

// Retorna o total atual
$stmt = $pdo->prepare("SELECT total FROM visitas WHERE id = 1");
$stmt->execute();
$total = (int) ($stmt->fetchColumn() ?: 0);

responderOk(['total' => $total]);
