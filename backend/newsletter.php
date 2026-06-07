<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/newsletter.php
   Endpoint: POST /backend/newsletter.php
   Inscreve um e-mail na newsletter com verificação de e-mail dupla
   ================================================================ */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

/* ── GET: confirmar inscrição via token ───────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = trim($_GET['token'] ?? '');
    if (!$token) { header('Location: ' . SITE_URL . '/index.html'); exit; }

    $pdo  = db();
    $stmt = $pdo->prepare(
        "SELECT id, email, status FROM newsletter
         WHERE token_verificacao = ? AND token_expira > NOW() LIMIT 1"
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if (!$row) {
        // Token inválido ou expirado — redireciona com mensagem
        header('Location: ' . SITE_URL . '/index.html?nl=expirado');
        exit;
    }

    // Confirmar inscrição
    $pdo->prepare(
        "UPDATE newsletter SET status='ativo', token_verificacao=NULL, token_expira=NULL
         WHERE id = ?"
    )->execute([$row['id']]);

    header('Location: ' . SITE_URL . '/index.html?nl=confirmado');
    exit;
}

/* ── POST: nova inscrição ─────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderErro('Método não permitido.', 405);
}

verificarRateLimit('newsletter', 3, 3600);

$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim(strtolower($body['email'] ?? ''));
$nome  = mb_substr(trim($body['nome'] ?? ''), 0, 120, 'UTF-8');
// Preferências de categoria (array de strings)
$prefs = is_array($body['prefs'] ?? null) ? $body['prefs'] : [];
$prefCats = ['bastidores', 'reflexao', 'escritor', 'livros'];
$prefVals = [];
foreach ($prefCats as $c) {
    $prefVals['pref_'.$c] = in_array($c, $prefs, true) ? 1 : (empty($prefs) ? 1 : 0);
}

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responderErro('E-mail inválido.');
}
if (strlen($email) > 255) {
    responderErro('E-mail muito longo.');
}

$pdo  = db();
$stmt = $pdo->prepare("SELECT id, status FROM newsletter WHERE email = ?");
$stmt->execute([$email]);
$existente = $stmt->fetch();

if ($existente) {
    if ($existente['status'] === 'ativo') {
        // Já inscrito e verificado — não revela dados, responde ok
        responderOk(['mensagem' => 'Inscrição confirmada!']);
    }
    // Estava descadastrado ou pendente — gera novo token e reenvia
    $token    = bin2hex(random_bytes(32));
    $expira   = date('Y-m-d H:i:s', strtotime('+24 hours'));
    try {
        $pdo->prepare(
            "UPDATE newsletter SET status='pendente', token_verificacao=?, token_expira=?,
             ip=?, descad_em=NULL, nome=?,
             pref_bastidores=?, pref_reflexao=?, pref_escritor=?, pref_livros=?
             WHERE id=?"
        )->execute([$token, $expira, getIP(), $nome ?: null,
            $prefVals['pref_bastidores'], $prefVals['pref_reflexao'],
            $prefVals['pref_escritor'],  $prefVals['pref_livros'],
            $existente['id']]);
    } catch (Throwable $e) {
        // Fallback sem colunas de preferência (migration não executada)
        $pdo->prepare(
            "UPDATE newsletter SET status='pendente', token_verificacao=?, token_expira=?,
             ip=?, descad_em=NULL WHERE id=?"
        )->execute([$token, $expira, getIP(), $existente['id']]);
    }

    _enviarEmailVerificacao($email, $token);
    responderOk(['mensagem' => 'Enviamos um e-mail de confirmação. Verifique sua caixa de entrada.']);
}

// Novo cadastro — status pendente até confirmar
$token  = bin2hex(random_bytes(32));
$expira = date('Y-m-d H:i:s', strtotime('+24 hours'));

try {
    $pdo->prepare(
        "INSERT INTO newsletter (email, nome, ip, status, token_verificacao, token_expira,
                                  pref_bastidores, pref_reflexao, pref_escritor, pref_livros)
         VALUES (?, ?, ?, 'pendente', ?, ?, ?, ?, ?, ?)"
    )->execute([$email, $nome ?: null, getIP(), $token, $expira,
        $prefVals['pref_bastidores'], $prefVals['pref_reflexao'],
        $prefVals['pref_escritor'],  $prefVals['pref_livros']]);
} catch (Throwable $e) {
    // Fallback sem colunas de preferência
    $pdo->prepare(
        "INSERT INTO newsletter (email, ip, status, token_verificacao, token_expira) VALUES (?, ?, 'pendente', ?, ?)"
    )->execute([$email, getIP(), $token, $expira]);
}

_enviarEmailVerificacao($email, $token);

responderOk(['mensagem' => 'Quase lá! Enviamos um e-mail de confirmação para <strong>' . htmlspecialchars($email) . '</strong>. Clique no link para ativar sua inscrição.']);

/* ── Helper: envio do e-mail de verificação ───────────────── */
function _enviarEmailVerificacao(string $email, string $token): void {
    $link = SITE_URL . '/backend/newsletter.php?token=' . urlencode($token);
    Mailer::enviar([
        'para_email' => $email,
        'para_nome'  => '',
        'assunto'    => 'Confirme sua inscrição — Robério Diógenes',
        'html'       => '
<div style="font-family:Georgia,serif;max-width:540px;margin:0 auto;padding:2rem;background:#FAF7F2;border-radius:8px">
  <h2 style="font-family:\'Cinzel\',serif;color:#B8860B;font-size:1.3rem;margin-bottom:1rem">Confirme sua inscrição</h2>
  <p style="color:#2C2418;line-height:1.7">Obrigado pelo interesse! Para confirmar sua inscrição na newsletter de <strong>Robério Diógenes</strong> e receber lançamentos e novidades, clique no botão abaixo.</p>
  <div style="text-align:center;margin:2rem 0">
    <a href="' . $link . '" style="background:#B8860B;color:#1A0F00;padding:.85rem 2rem;border-radius:6px;text-decoration:none;font-weight:700;font-family:Georgia,serif;display:inline-block">
      Confirmar inscrição →
    </a>
  </div>
  <p style="color:#8C7D65;font-size:.85rem;line-height:1.6">
    Se você não solicitou esta inscrição, basta ignorar este e-mail. O link expira em 24 horas.<br>
    Ou acesse diretamente: <a href="' . $link . '" style="color:#B8860B">' . $link . '</a>
  </p>
  <hr style="border-color:#E4DBC8;margin:1.5rem 0">
  <p style="color:#B8A888;font-size:.78rem">Robério Diógenes · Escritor Independente · <a href="' . SITE_URL . '/privacidade.html" style="color:#B8A888">Política de Privacidade</a></p>
</div>',
        'texto' => "Confirme sua inscrição na newsletter de Robério Diógenes:\n\n$link\n\nO link expira em 24 horas.",
    ]);
}
