<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/auth/google-callback.php
   Recebe o code do Google, troca por token, cria/autentica usuário
   ================================================================ */

require_once __DIR__ . '/../config.php';

iniciarSessao();

$code  = $_GET['code']  ?? '';
$state = $_GET['state'] ?? '';
$error = $_GET['error'] ?? '';

// ── Verificar CSRF (state) ────────────────────────────────────
if ($error || !$code || !$state || $state !== ($_SESSION['oauth_state'] ?? '')) {
    header('Location: ' . SITE_URL . '/login.html?erro=google_falhou');
    exit;
}
unset($_SESSION['oauth_state']);

// ── Trocar code por access_token ──────────────────────────────
$tokenResp = http_post('https://oauth2.googleapis.com/token', [
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
$perfil = http_get(
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
    $nome   = $usuario['nome']; // manter o nome que o usuário definiu
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

header('Location: ' . SITE_URL . '/leitor/index.html');
exit;

// ── Helpers HTTP ──────────────────────────────────────────────
function http_post(string $url, array $dados): array {
    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($dados),
        'timeout' => 10,
    ]]);
    $r = @file_get_contents($url, false, $ctx);
    return $r ? (json_decode($r, true) ?? []) : [];
}

function http_get(string $url, string $token): array {
    $ctx = stream_context_create(['http' => [
        'header'  => 'Authorization: Bearer ' . $token,
        'timeout' => 10,
    ]]);
    $r = @file_get_contents($url, false, $ctx);
    return $r ? (json_decode($r, true) ?? []) : [];
}
