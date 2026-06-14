<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/auth/google-callback.php
   Recebe o code do Google via POST (response_mode=form_post),
   troca por token, cria/autentica usuário e redireciona.

   NOTA: usamos response_mode=form_post para evitar que o ModSecurity
   bloqueie o parâmetro `code` quando chegava via GET na URL.
   O Google agora envia um POST com os campos code e state.
   ================================================================ */

require_once __DIR__ . '/../config.php';

iniciarSessao();

// Aceita tanto POST (form_post) quanto GET (fallback / testes locais)
$code  = $_POST['code']  ?? $_GET['code']  ?? '';
$state = $_POST['state'] ?? $_GET['state'] ?? '';
$error = $_POST['error'] ?? $_GET['error'] ?? '';

// ── Verificar CSRF (state) ────────────────────────────────────
// Com form_post o PHPSESSID tem SameSite=Lax e não chega no POST cross-site.
// Usamos o cookie oauth_state (SameSite=None) como fallback.
$savedState = $_SESSION['oauth_state'] ?? $_COOKIE['oauth_state'] ?? '';

if ($error || !$code || !$state || $state !== $savedState) {
    header('Location: ' . SITE_URL . '/login.html?erro=google_falhou');
    exit;
}

// Limpar state
unset($_SESSION['oauth_state']);
setcookie('oauth_state', '', [
    'expires'  => time() - 3600,
    'path'     => '/backend/auth/',
    'secure'   => AMBIENTE === 'producao',
    'httponly' => true,
    'samesite' => 'None',
]);

// ── Trocar code por access_token ──────────────────────────────
$tokenResp = http_post_google('https://oauth2.googleapis.com/token', [
    'code'          => $code,
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
]);

if (empty($tokenResp['access_token'])) {
    header('Location: ' . SITE_URL . '/login.html?erro=google_token');
    exit;
}

// ── Buscar perfil Google ──────────────────────────────────────
$perfil = http_get_google(
    'https://www.googleapis.com/oauth2/v3/userinfo',
    $tokenResp['access_token']
);

if (empty($perfil['sub']) || empty($perfil['email'])) {
    header('Location: ' . SITE_URL . '/login.html?erro=google_perfil');
    exit;
}

$googleId = $perfil['sub'];
$email    = strtolower(trim($perfil['email']));
$nome     = trim($perfil['name'] ?? explode('@', $email)[0]);
$foto     = $perfil['picture'] ?? null;

// ── Upsert usuário ────────────────────────────────────────────
$pdo  = db();
$stmt = $pdo->prepare("SELECT id, nome, ativo FROM usuarios WHERE google_id = ? OR email = ? LIMIT 1");
$stmt->execute([$googleId, $email]);
$usuario = $stmt->fetch();

if ($usuario) {
    if (!$usuario['ativo']) {
        header('Location: ' . SITE_URL . '/login.html?erro=conta_inativa');
        exit;
    }
    // Atualizar google_id e foto (caso tenha se cadastrado antes por e-mail)
    $pdo->prepare("UPDATE usuarios SET google_id = ?, foto_url = ?, ultimo_login = NOW() WHERE id = ?")
        ->execute([$googleId, $foto, $usuario['id']]);
    $userId = $usuario['id'];
    $nome   = $usuario['nome'];
} else {
    // Criar usuário novo via Google
    $pdo->prepare("
        INSERT INTO usuarios (nome, email, google_id, foto_url, verificado, ip_cadastro, ultimo_login)
        VALUES (?, ?, ?, ?, 1, ?, NOW())
    ")->execute([$nome, $email, $googleId, $foto, getIP()]);
    $userId = (int) $pdo->lastInsertId();
}

// ── Sessão ────────────────────────────────────────────────────
$_SESSION['usuario_id']    = $userId;
$_SESSION['usuario_nome']  = $nome;
$_SESSION['usuario_email'] = $email;
$_SESSION['usuario_foto']  = $foto;
$_SESSION['usuario'] = [
    'id'    => $userId,
    'nome'  => $nome,
    'email' => $email,
];

header('Location: ' . SITE_URL . '/leitor/index.html');
exit;

// ── Helpers HTTP ──────────────────────────────────────────────
function http_post_google(string $url, array $dados): array {
    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($dados),
        'timeout' => 10,
    ]]);
    $r = @file_get_contents($url, false, $ctx);
    return $r ? (json_decode($r, true) ?? []) : [];
}

function http_get_google(string $url, string $token): array {
    $ctx = stream_context_create(['http' => [
        'header'  => 'Authorization: Bearer ' . $token,
        'timeout' => 10,
    ]]);
    $r = @file_get_contents($url, false, $ctx);
    return $r ? (json_decode($r, true) ?? []) : [];
}
