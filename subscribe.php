<?php
/**
 * subscribe.php — Inscrição na newsletter
 * ─────────────────────────────────────────────────────────────
 * Recebe POST com campo "email", valida, protege contra spam
 * e salva no banco de dados MySQL.
 *
 * Chamado via fetch() no index.html
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://roberiodiogenes.com');
header('Access-Control-Allow-Methods: POST');
header('X-Content-Type-Options: nosniff');

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

require_once __DIR__ . '/config/db.php';

// ─── Helpers ─────────────────────────────────────────────────
function responde(bool $ok, string $msg, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function getIP(): string {
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

// ─── Validação do e-mail ──────────────────────────────────────
$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    responde(false, 'Por favor, informe um e-mail.', 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responde(false, 'E-mail inválido. Verifique e tente novamente.', 422);
}

if (strlen($email) > 255) {
    responde(false, 'E-mail muito longo.', 422);
}

// ─── Proteção anti-spam: máx. 3 tentativas por IP em 10 min ──
$ip  = getIP();
$pdo = getDB();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM newsletter_log
    WHERE ip = :ip AND tentativa >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
");
$stmt->execute([':ip' => $ip]);
$tentativas = (int) $stmt->fetchColumn();

if ($tentativas >= 3) {
    responde(false, 'Muitas tentativas. Aguarde alguns minutos.', 429);
}

// Registrar tentativa
$pdo->prepare("INSERT INTO newsletter_log (ip) VALUES (:ip)")
    ->execute([':ip' => $ip]);

// ─── Verificar se já está inscrito ───────────────────────────
$stmt = $pdo->prepare("SELECT status FROM newsletter WHERE email = :email");
$stmt->execute([':email' => $email]);
$existente = $stmt->fetch();

if ($existente) {
    if ($existente['status'] === 'ativo') {
        responde(false, 'Este e-mail já está inscrito. Obrigado!');
    }
    // Reativar descadastrado
    $pdo->prepare("UPDATE newsletter SET status='ativo', created_at=NOW() WHERE email=:email")
        ->execute([':email' => $email]);
    responde(true, 'Sua inscrição foi reativada com sucesso. Bem-vindo de volta!');
}

// ─── Inserir novo inscrito ────────────────────────────────────
$pdo->prepare("INSERT INTO newsletter (email, ip) VALUES (:email, :ip)")
    ->execute([':email' => $email, ':ip' => $ip]);

responde(true, 'Inscrição realizada com sucesso! Em breve você receberá novidades.');
