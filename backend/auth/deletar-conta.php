<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/auth/deletar-conta.php
   POST {senha} → confirma com senha e deleta a conta
   Usuários Google (sem senha) enviam {confirmar: "DELETAR"}
   ================================================================ */

require_once __DIR__ . '/../config.php';
iniciarSessao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderErro('Método não permitido.', 405);
}

if (empty($_SESSION['usuario_id'])) {
    responderErro('Não autenticado.', 401);
}

verificarRateLimit('deletar_conta', 3, 3600);

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$senha    = $body['senha']     ?? '';
$confirmar= $body['confirmar'] ?? '';
$uid      = $_SESSION['usuario_id'];
$pdo      = db();

// Buscar usuário
$stmt = $pdo->prepare("SELECT senha, google_id, nome FROM usuarios WHERE id=?");
$stmt->execute([$uid]);
$usuario = $stmt->fetch();

if (!$usuario) {
    responderErro('Usuário não encontrado.', 404);
}

// Verificação de identidade
if ($usuario['senha']) {
    // Usuário com senha — verificar senha
    if (!$senha) {
        responderErro('Informe sua senha para confirmar a exclusão.');
    }
    if (!password_verify($senha, $usuario['senha'])) {
        responderErro('Senha incorreta. A conta não foi excluída.');
    }
} else {
    // Usuário Google — verificar confirmação textual
    if (strtoupper(trim($confirmar)) !== 'DELETAR') {
        responderErro('Digite DELETAR para confirmar a exclusão da conta.');
    }
}

// Deletar — cascade remove favoritos, downloads, etc.
$pdo->prepare("DELETE FROM usuarios WHERE id=?")->execute([$uid]);

// Destruir sessão
$_SESSION = [];
session_destroy();

responderOk(['mensagem' => 'Sua conta foi excluída permanentemente. Sentiremos sua falta, ' . $usuario['nome'] . '.']);
