<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/tracking.php
   Endpoint BI para receber dados de sessão, eventos e
   tempo de permanência enviados por js/tracking.js.

   Aceita apenas POST com JSON body.
   ================================================================ */

require_once __DIR__ . '/config.php';

ob_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . (defined('SITE_URL') ? SITE_URL : '*'));
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { ob_end_clean(); http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { ob_end_clean(); http_response_code(405); echo json_encode(['ok'=>false]); exit; }

$raw  = file_get_contents('php://input');
$body = $raw ? (json_decode($raw, true) ?? []) : [];

if (empty($body)) {
    ob_end_clean(); http_response_code(400);
    echo json_encode(['ok'=>false,'erro'=>'Payload vazio.']);
    exit;
}

$acao       = trim($body['acao'] ?? '');
$session_id = trim($body['session_id'] ?? '');

// Session ID obrigatório e com formato mínimo
if (!$session_id || strlen($session_id) < 5) {
    ob_end_clean(); echo json_encode(['ok'=>false,'erro'=>'session_id inválido.']); exit;
}

// Anonimiza o IP (SHA-256, LGPD)
function anonimizarIP(): string {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $ip = trim(explode(',', $ip)[0]);
    return hash('sha256', $ip . 'rd_salt_lgpd_2025');
}

function sanitize(mixed $v, int $max = 200): string {
    return mb_substr(strip_tags(trim((string)($v ?? ''))), 0, $max, 'UTF-8');
}

function sanitizeJson(mixed $v): ?string {
    if (is_null($v) || $v === '') return null;
    return mb_substr(json_encode($v, JSON_UNESCAPED_UNICODE), 0, 2000, 'UTF-8');
}

$pdo = db();
ob_end_clean();

/* ──────────────────────────────────────────────────────────────
   AÇÃO: sessao — registra nova sessão com UTMs e dispositivo
   ────────────────────────────────────────────────────────────── */
if ($acao === 'sessao') {
    try {
        $uid = null;
        iniciarSessao();
        if (!empty($_SESSION['usuario_id'])) $uid = (int)$_SESSION['usuario_id'];

        $pdo->prepare(
            "INSERT IGNORE INTO analytics_sessoes
             (session_id, usuario_id, utm_source, utm_medium, utm_campaign,
              utm_term, utm_content, dispositivo, idioma, referrer,
              landing_page, pagina_tipo, ip_hash)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
        )->execute([
            $session_id,
            $uid,
            sanitize($body['utm_source']   ?? ''),
            sanitize($body['utm_medium']   ?? ''),
            sanitize($body['utm_campaign'] ?? ''),
            sanitize($body['utm_term']     ?? ''),
            sanitize($body['utm_content']  ?? ''),
            in_array($body['dispositivo']??'', ['mobile','tablet','desktop']) ? $body['dispositivo'] : 'desktop',
            sanitize($body['idioma']       ?? '', 10),
            sanitize($body['referrer']     ?? '', 500),
            sanitize($body['landing_page'] ?? '', 500),
            sanitize($body['pagina_tipo']  ?? ''),
            anonimizarIP(),
        ]);
        echo json_encode(['ok'=>true]);
    } catch (PDOException $e) {
        error_log('[tracking:sessao] ' . $e->getMessage());
        echo json_encode(['ok'=>false]);
    }
    exit;
}

/* ──────────────────────────────────────────────────────────────
   AÇÃO: evento — registra evento personalizado (ViewContent, Lead, etc.)
   ────────────────────────────────────────────────────────────── */
if ($acao === 'evento') {
    try {
        $uid = null;
        iniciarSessao();
        if (!empty($_SESSION['usuario_id'])) $uid = (int)$_SESSION['usuario_id'];

        $pdo->prepare(
            "INSERT INTO analytics_eventos
             (session_id, usuario_id, tipo_evento, conteudo_slug,
              conteudo_titulo, params)
             VALUES (?,?,?,?,?,?)"
        )->execute([
            $session_id,
            $uid,
            sanitize($body['tipo_evento']     ?? '', 80),
            sanitize($body['conteudo_slug']   ?? ''),
            sanitize($body['conteudo_titulo'] ?? '', 300),
            sanitizeJson($body['params']      ?? null),
        ]);
        echo json_encode(['ok'=>true]);
    } catch (PDOException $e) {
        error_log('[tracking:evento] ' . $e->getMessage());
        echo json_encode(['ok'=>false]);
    }
    exit;
}

/* ──────────────────────────────────────────────────────────────
   AÇÃO: tempo_pagina — atualiza tempo de permanência
   ────────────────────────────────────────────────────────────── */
if ($acao === 'tempo_pagina') {
    try {
        $segundos = max(0, min(7200, (int)($body['tempo_segundos'] ?? 0)));
        if ($segundos < 2) { echo json_encode(['ok'=>true]); exit; }

        // Primeiro tenta atualizar o último evento desta sessão para aquela página
        $updated = $pdo->prepare(
            "UPDATE analytics_eventos
             SET tempo_permanencia = ?
             WHERE session_id = ?
               AND conteudo_slug = ?
               AND tipo_evento = 'ViewContent'
             ORDER BY registrado_em DESC LIMIT 1"
        );
        $updated->execute([
            $segundos,
            $session_id,
            sanitize($body['conteudo_slug'] ?? ''),
        ]);

        // Se não havia evento ViewContent, registra um evento de tempo simples
        if ($updated->rowCount() === 0) {
            $pdo->prepare(
                "INSERT INTO analytics_eventos
                 (session_id, tipo_evento, conteudo_slug, tempo_permanencia)
                 VALUES (?,?,?,?)"
            )->execute([
                $session_id,
                'Tempo_Pagina',
                sanitize($body['conteudo_slug'] ?? ''),
                $segundos,
            ]);
        }
        echo json_encode(['ok'=>true]);
    } catch (PDOException $e) {
        error_log('[tracking:tempo] ' . $e->getMessage());
        echo json_encode(['ok'=>false]);
    }
    exit;
}

echo json_encode(['ok'=>false,'erro'=>'Ação desconhecida.']);
