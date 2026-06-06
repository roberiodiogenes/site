    <?php
    /* ================================================================
    ROBÉRIO DIÓGENES — backend/auth/login.php
    Endpoint: POST /backend/auth/login.php
    Autentica usuário com e-mail e senha
    ================================================================ */

    require_once __DIR__ . '/../config.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderErro('Método não permitido.', 405);
    }

    verificarRateLimit('login', 10, 3600);
    iniciarSessao();

    // ── Receber dados ─────────────────────────────────────────────
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $email = trim(strtolower($body['email'] ?? ''));
    $senha = $body['senha'] ?? '';

    if (!$email || !$senha) {
        responderErro('Preencha e-mail e senha.');
    }

    // ── Buscar usuário ────────────────────────────────────────────
    $pdo  = db();
    $stmt = $pdo->prepare("SELECT id, nome, email, senha, ativo FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    // Verificação constante para evitar timing attack
    $senhaHash = $usuario['senha'] ?? '$2y$12$invalido000000000000000000000000000000000000000000000000';
    $valido    = password_verify($senha, $senhaHash);

    if (!$usuario || !$valido) {
        responderErro('E-mail ou senha incorretos.');
    }

    if (!$usuario['ativo']) {
        responderErro('Esta conta foi desativada. Entre em contato pelo site.');
    }

    // ── Iniciar sessão ────────────────────────────────────────────
    $_SESSION['usuario_id']    = $usuario['id'];
    $_SESSION['usuario_nome']  = $usuario['nome'];
    $_SESSION['usuario_email'] = $usuario['email'];
    // Formato unificado para o leitor
    $_SESSION['usuario'] = [
        'id'    => $usuario['id'],
        'nome'  => $usuario['nome'],
        'email' => $usuario['email'],
    ];

    $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?")
        ->execute([$usuario['id']]);

    responderOk([
        'mensagem' => 'Bem-vindo de volta, ' . $usuario['nome'] . '!',
        'usuario'  => [
            'id'    => $usuario['id'],
            'nome'  => $usuario['nome'],
            'email' => $usuario['email'],
        ],
    ]);
