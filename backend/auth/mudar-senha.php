<?php
/* ================================================================
 * ROBÉRIO DIÓGENES — backend/auth/mudar-senha.php
 * Endpoint: POST /backend/auth/mudar-senha.php
 * Altera a senha do usuário autenticado (não confundir com reset)
 * ================================================================ */

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  responderErro('Método não permitido.', 405);
}

// Rate limiting: máximo 5 tentativas por hora por IP
verificarRateLimit('mudar_senha', 5, 3600);

iniciarSessao();

// ── Verificar autenticação ──────────────────────────────────
if (empty($_SESSION['usuario_id'])) {
  responderErro('Você deve estar autenticado.', 401);
}

$usuario_id = (int) $_SESSION['usuario_id'];

// ── Receber dados ───────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$senha_atual = $body['senha_atual'] ?? '';
$nova_senha = $body['nova_senha'] ?? '';

// ── Validações de entrada ───────────────────────────────────
if (!$senha_atual) {
  responderErro('Senha atual é obrigatória.');
}

if (!$nova_senha) {
  responderErro('Nova senha é obrigatória.');
}

if (strlen($nova_senha) < 8) {
  responderErro('Senha deve ter no mínimo 8 caracteres.');
}

if (!preg_match('/[A-Za-z]/', $nova_senha)) {
  responderErro('Senha deve conter letras.');
}

if (!preg_match('/[0-9]/', $nova_senha)) {
  responderErro('Senha deve conter números.');
}

try {
  $pdo = db();

  // ── Buscar usuário ───────────────────────────────────
  $stmt = $pdo->prepare("SELECT id, senha FROM usuarios WHERE id = ?");
  $stmt->execute([$usuario_id]);
  $usuario = $stmt->fetch();

  if (!$usuario) {
    responderErro('Usuário não encontrado.', 404);
  }

  // ── Verificar senha atual (proteção contra roubo de sessão) ──
  // Usa password_verify para evitar timing attack
  if (!password_verify($senha_atual, $usuario['senha'])) {
    responderErro('Senha atual incorreta.');
  }

  // ── Gerar hash da nova senha (bcrypt cost 12) ──────────────
  $nova_senha_hash = password_hash($nova_senha, PASSWORD_BCRYPT, ['cost' => 12]);

  // ── Atualizar senha ──────────────────────────────────────
  $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, updated_at = NOW() WHERE id = ?");
  
  if (!$stmt->execute([$nova_senha_hash, $usuario_id])) {
    responderErro('Erro ao atualizar senha.', 500);
  }

  // ── Log de auditoria ────────────────────────────────────
  error_log("Senha alterada para usuário ID: {$usuario_id} em " . date('Y-m-d H:i:s'));

  // ── Responder com sucesso ──────────────────────────────
  responderOk([
    'mensagem' => 'Senha alterada com sucesso!',
  ]);

} catch (PDOException $e) {
  error_log("Erro em mudar-senha.php: " . $e->getMessage());
  responderErro('Erro ao atualizar senha.', 500);
} catch (Exception $e) {
  error_log("Erro geral em mudar-senha.php: " . $e->getMessage());
  responderErro('Erro ao atualizar senha.', 500);
}

?>