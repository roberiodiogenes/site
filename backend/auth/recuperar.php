<?php
/* ================================================================
 * ROBÉRIO DIÓGENES — backend/auth/recuperar.php
 * Endpoint: POST /backend/auth/recuperar.php
 * Solicita redefinição de senha via e-mail
 * ================================================================ */

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  responderErro('Método não permitido.', 405);
}

// Rate limiting: máximo 3 tentativas por hora por IP
verificarRateLimit('recuperar_senha', 3, 3600);

iniciarSessao();

// ── Receber e validar e-mail ─────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim(strtolower($body['email'] ?? ''));

// Validação básica
if (!$email) {
  responderErro('E-mail é obrigatório.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  responderErro('E-mail inválido.');
}

if (strlen($email) > 255) {
  responderErro('E-mail muito longo.');
}

// ── Verificar se o usuário existe ────────────────────────────
try {
  $pdo = db();
  
  $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE email = ? AND ativo = 1");
  $stmt->execute([$email]);
  $usuario = $stmt->fetch();

  if (!$usuario) {
    // Responder como sucesso mesmo que e-mail não exista
    // Segurança: não revelar se e-mail está cadastrado ou não
    responderOk([
      'mensagem' => 'Se este e-mail estiver cadastrado, enviaremos um link de redefinição em breve.',
    ]);
  }

  $usuario_id = (int) $usuario['id'];
  $nome = htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8');

  // ── Gerar token de redefinição ───────────────────────────
  // Token aleatório de 64 caracteres (256 bits)
  $token_raw = bin2hex(random_bytes(32));
  
  // Hash do token para armazenar no BD (assim o token em texto não fica no BD)
  $token_hash = hash('sha256', $token_raw);
  
  // Expiração: 1 hora a partir de agora
  $expira_em = date('Y-m-d H:i:s', time() + 3600);

  // ── Limpar tokens anteriores expirados ou não usados ──────
  $stmt = $pdo->prepare("DELETE FROM password_reset WHERE usuario_id = ?");
  $stmt->execute([$usuario_id]);

  // ── Inserir novo token no banco ──────────────────────────
  $stmt = $pdo->prepare("
    INSERT INTO password_reset (usuario_id, token, expira_em, criado_em)
    VALUES (?, ?, ?, NOW())
  ");
  
  if (!$stmt->execute([$usuario_id, $token_hash, $expira_em])) {
    responderErro('Erro ao gerar token. Tente novamente.', 500);
  }

  // ── Construir link de redefinição ────────────────────────
  $link_reset = SITE_URL . '/resetar-senha.html?token=' . urlencode($token_raw);

  // ── Enviar e-mail (simulado) ─────────────────────────────
  // Em produção, use PHPMailer, SwiftMailer ou sendgrid
  $assunto = 'Redefinir sua senha - Robério Diógenes';
  
  $corpo_email = "
Olá {$nome},

Você solicitou redefinição de senha. Clique no link abaixo para continuar:

{$link_reset}

Este link é válido por 1 hora.

Se você não solicitou isso, ignore este e-mail.

---
Robério Diógenes
";

  // ── Log de simulação (em produção, integrar com provedor de e-mail) ──
  $log_file = __DIR__ . '/../../logs/email_log.txt';
  $log_entry = "[" . date('Y-m-d H:i:s') . "] Email para {$email}\n";
  if (is_writable(dirname($log_file))) {
    file_put_contents($log_file, $log_entry, FILE_APPEND);
  }

  // ── Responder com sucesso ────────────────────────────────
  $resposta = [
    'mensagem' => 'Se este e-mail estiver cadastrado, enviaremos um link de redefinição em breve.',
  ];

  // Em ambiente local, retornar o link direto para facilitar testes
  // (em produção, este bloco é ignorado)
  if (AMBIENTE === 'local') {
    $resposta['_debug_link'] = $link_reset;
    $resposta['_debug_aviso'] = 'Modo local: use este link diretamente. Em produção será enviado por e-mail.';
  }

  responderOk($resposta);

} catch (PDOException $e) {
  // Log de erro (não revelar ao usuário)
  error_log("Erro em recuperar.php: " . $e->getMessage());
  responderErro('Erro ao processar solicitação. Tente novamente mais tarde.', 500);
} catch (Exception $e) {
  error_log("Erro geral em recuperar.php: " . $e->getMessage());
  responderErro('Erro ao processar solicitação.', 500);
}

?>