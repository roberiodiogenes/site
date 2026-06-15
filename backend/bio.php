<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/bio.php
   Endpoints públicos da página bio.html (Ateliê de Histórias).
   Endpoints admin ficam em admin/bio.php para usar a sessão admin.

   GET  ?acao=contador  → leitores únicos hoje (bio.html)
   POST {evento,...}    → registrar evento de rastreamento (bio.html)
   ================================================================ */

require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

/* ══════════════════════════════════════════════════════
   GET ?acao=contador — leitores únicos hoje
═══════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['acao'] ?? '') === 'contador') {
    try {
        $pdo  = db();
        $stmt = $pdo->prepare(
            "SELECT COUNT(DISTINCT sessao_id) AS total
               FROM bio_eventos
              WHERE evento = 'conto_lido_50pct'
                AND DATE(criado_em) = CURDATE()"
        );
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['ok' => true, 'total' => (int)($row['total'] ?? 0)]);
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'total' => 0]);
    }
    exit;
}

/* ══════════════════════════════════════════════════════
   POST — registrar evento de rastreamento
═══════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);

    $evento      = substr(preg_replace('/[^a-z0-9_]/', '', $body['evento']    ?? ''), 0, 60);
    $sessao_id   = substr($body['sessao_id']   ?? '', 0, 64);
    $genero      = substr($body['genero']      ?? '', 0, 20);
    $estrategia  = substr($body['estrategia']  ?? '', 0, 30);
    $rede_social = substr($body['rede_social'] ?? '', 0, 20);
    $ip          = $_SERVER['REMOTE_ADDR'] ?? null;
    $referrer    = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 300);

    $validos = [
        'pagina_aberta', 'card_aberto',
        'conto_lido_50pct', 'conto_lido_100pct',
        'click_ler_final_site', 'click_download_pdf',
        'email_capturado', 'email_aberto',
        'compartilhamento', 'embaixador_gerado',
        'bau_aberto', 'bau_gaveta',
        'link_biblioteca', 'link_diario',
    ];

    if (!in_array($evento, $validos, true)) {
        echo json_encode(['ok' => true]);
        exit;
    }

    try {
        $pdo  = db();
        $stmt = $pdo->prepare(
            "INSERT INTO bio_eventos
               (sessao_id, evento, genero, estrategia, rede_social, ip, referrer)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $sessao_id,
            $evento,
            $genero      ?: null,
            $estrategia  ?: null,
            $rede_social ?: null,
            $ip,
            $referrer    ?: null,
        ]);
        echo json_encode(['ok' => true]);
    } catch (Throwable $e) {
        echo json_encode(['ok' => false]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['erro' => 'Método não permitido']);
