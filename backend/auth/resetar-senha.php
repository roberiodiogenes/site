<?php
/* ================================================================
 * ROBÉRIO DIÓGENES — backend/auth/resetar-senha.php
 * Endpoint: POST /backend/auth/resetar-senha.php
 * Processa redefinição de senha com token
 * ================================================================ */

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  responderErro('Método não permitido.', 405);
}

// Rate limiting: máximo 5 tentativas por hora por IP
verificarRateLimit('resetar_senha', 5, 3600);

iniciarSessao();

// ── Receber dados ────────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$token_raw = trim($body['token'] ?? '');
$nova_senha = $body['senha'] ?? '';

// Validação de entrada
if (!$token_raw) {
  responderErro('Token não fornecido ou inválido.');
}

if (strlen($token_raw) > 255) {
  responderErro('Token inválido.');
}

if (!$nova_senha) {
  responderErro('Nova senha é obrigatória.');
}

if (strlen($nova_senha) < 8) {
  responderErro('A senha deve ter no mínimo 8 caracteres.');
}

if (!preg_match('/[A-Za-z]/', $nova_senha)) {
  responderErro('A senha deve conter letras.');
}

if (!preg_match('/[0-9]/', $nova_senha)) {
  responderErro('A senha deve conter números.');
}

// ── Processar token ──────────────────────────────────────────
try {
  $pdo = db();

  // Validar hash do token
  $token_hash = hash('sha256', $token_raw);

  // Buscar token no banco
  $stmt = $pdo->prepare("
    SELECT id, usuario_id, expira_em, usado_em
    FROM password_reset
    WHERE token = ?
    LIMIT 1
  ");
  
  $stmt->execute([$token_hash]);
  $reset_record = $stmt->fetch();

  // Verificações de segurança
  if (!$reset_record) {
    responderErro('Token inválido ou expirado.');
  }

  // Token já foi usado?
  if ($reset_record['usado_em'] !== null) {
    responderErro('Este token já foi utilizado. Solicite um novo.');
  }

  // Token expirou?
  if (strtotime($reset_record['expira_em']) < time()) {
    responderErro('Link de redefinição expirou. Solicite um novo.');
  }

  $usuario_id = (int) $reset_record['usuario_id'];

  // ── Validar que o usuário existe e está ativo ────────────
  $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND ativo = 1");
  $stmt->execute([$usuario_id]);
  
  if (!$stmt->fetch()) {
    responderErro('Usuário não encontrado ou conta desativada.');
  }

  // ── Gerar hash da nova senha (bcrypt cost 12) ────────────
  // bcrypt é recomendado para senhas: lento, seguro contra força bruta
  $senha_hash = password_hash($nova_senha, PASSWORD_BCRYPT, ['cost' => 12]);

  // ── Atualizar senha do usuário ───────────────────────────
  $stmt = $pdo->prepare("
    UPDATE usuarios
    SET senha = ?, ultimo_login = NOW()
    WHERE id = ?
  ");
  
  if (!$stmt->execute([$senha_hash, $usuario_id])) {
    responderErro('Erro ao atualizar senha. Tente novamente.', 500);
  }

  // ── Marcar token como usado ──────────────────────────────
  // Isso previne reutilização do mesmo token
  $stmt = $pdo->prepare("
    UPDATE password_reset
    SET usado_em = NOW()
    WHERE id = ?
  ");
  
  $stmt->execute([$reset_record['id']]);

  // ── Log de auditoria ─────────────────────────────────────
  error_log("Senha redefinida para usuário ID: {$usuario_id} em " . date('Y-m-d H:i:s'));

  // ── Responder com sucesso ────────────────────────────────
  responderOk([
    'mensagem' => 'Senha redefinida com sucesso! Você será redirecionado para o login.',
  ]);

} catch (PDOException $e) {
  error_log("Erro em resetar-senha.php: " . $e->getMessage());
  responderErro('Erro ao processar redefinição. Tente novamente.', 500);
} catch (Exception $e) {
  error_log("Erro geral em resetar-senha.php: " . $e->getMessage());
  responderErro('Erro ao processar redefinição.', 500);
}

?>