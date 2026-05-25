<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/auth/recuperar.php (ATUALIZADO)
   Solicita redefinição de senha via e-mail REAL com PHPMailer.

   MUDANÇA: substituída a simulação de e-mail pelo Mailer real.
   Integra com backend/mailer.php.
   ================================================================ */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../mailer.php';   // ← NOVO

iniciarSessao();

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderErro('Método não permitido.', 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

// ── Receber e validar e-mail ─────────────────────────────────
$email = trim(strtolower($body['email'] ?? ''));

if (!$email) {
    responderErro('E-mail é obrigatório.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responderErro('E-mail inválido.');
}
if (strlen($email) > 255) {
    responderErro('E-mail muito longo.');
}

// Rate limiting
verificarRateLimit('recuperar_senha_' . md5($email), 3, 3600);

$pdo = db();

try {
    $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE email = ? AND ativo = 1");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    // Sempre retorna sucesso (segurança: não revela se e-mail existe)
    $resposta = [
        'mensagem' => 'Se este e-mail estiver cadastrado, enviaremos um link de redefinição em breve.',
    ];

    if ($usuario) {
        $usuario_id = $usuario['id'];
        $nome       = $usuario['nome'];

        // Invalida tokens anteriores
        $pdo->prepare("DELETE FROM password_reset WHERE usuario_id = ?")
            ->execute([$usuario_id]);

        // Gera token seguro
        $token_raw  = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token_raw);
        $expira_em  = date('Y-m-d H:i:s', strtotime('+2 hours'));

        $ins = $pdo->prepare(
            "INSERT INTO password_reset (usuario_id, token, expira_em) VALUES (?, ?, ?)"
        );
        if (!$ins->execute([$usuario_id, $token_hash, $expira_em])) {
            responderErro('Erro ao gerar token. Tente novamente.', 500);
        }

        // Constrói link
        $link_reset = SITE_URL . '/resetar-senha.html?token=' . urlencode($token_raw);

        // ── Envia e-mail REAL ────────────────────────────────
        $enviado = Mailer::enviarRecuperacaoSenha($email, $nome, $link_reset);

        if (!$enviado) {
            error_log("[recuperar.php] Falha ao enviar e-mail para {$email}");
        }

        // Debug local — retorna o link diretamente para facilitar testes
        if (AMBIENTE === 'local') {
            $resposta['_debug_link']  = $link_reset;
            $resposta['_debug_email'] = $enviado ? 'E-mail enviado (Mailpit)' : 'Falha no envio — use o link acima';
        }
    }

    responderOk($resposta);

} catch (Exception $e) {
    error_log("[recuperar.php] Erro: " . $e->getMessage());
    responderErro('Erro interno. Tente novamente.', 500);
}
