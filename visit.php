<?php
/**
 * visit.php — Contador de visitas únicas
 * ─────────────────────────────────────────────────────────────
 * Chamado via fetch() ao carregar a página.
 * Conta apenas uma visita por visitante (por IP + User-Agent).
 * Retorna o total de visitas em JSON.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://roberiodiogenes.com');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/config/db.php';

function getVisitorHash(): string {
    $ip = '0.0.0.0';
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            $candidate = trim(explode(',', $_SERVER[$k])[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) { $ip = $candidate; break; }
        }
    }
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    // Hash combina IP + UA — não armazenamos dado bruto pessoal
    return hash('sha256', $ip . '|' . $ua);
}

try {
    $pdo  = getDB();
    $hash = getVisitorHash();

    // Tentar registrar visita (INSERT IGNORE ignora duplicatas)
    $stmt = $pdo->prepare("INSERT IGNORE INTO visitas_log (visitor_hash) VALUES (:h)");
    $stmt->execute([':h' => $hash]);

    // Se inseriu uma nova linha, incrementar o contador
    if ($stmt->rowCount() > 0) {
        $pdo->exec("UPDATE visitas SET total = total + 1 WHERE id = 1");
    }

    // Retornar total
    $total = (int) $pdo->query("SELECT total FROM visitas WHERE id = 1")->fetchColumn();
    echo json_encode(['success' => true, 'total' => $total]);

} catch (Exception $e) {
    // Em caso de erro, retorna 0 sem quebrar a página
    echo json_encode(['success' => false, 'total' => 0]);
}
