<?php
/* ================================================================
   leitor/backend/conquistas.php
   GET ?acao=listar&slug=lumen   → conquistas do usuário no livro
   GET ?acao=todas               → todas as conquistas do usuário
   POST { acao:enviar_email, slug, tipo } → envia e-mail da medalha
   ================================================================ */
ob_start();
require_once __DIR__ . '/../../backend/config.php';
iniciarSessao();

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario) { ob_end_clean(); http_response_code(401); header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok'=>false,'erro'=>'Login necessário.']); exit; }

$pdo  = db();
$uid  = (int)$usuario['id'];
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$acao = trim($_GET['acao'] ?? $body['acao'] ?? '');
$slug = preg_replace('/[^a-z0-9_-]/', '', trim($_GET['slug'] ?? $body['slug'] ?? ''));

function jC(array $d): void { ob_end_clean(); header('Content-Type: application/json; charset=utf-8'); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

const CONQUISTAS_META = [
    'inicio'      => ['emoji' => '📖', 'titulo' => 'Primeira Página',    'desc' => 'Você começou a leitura!',              'pontos' => 5],
    '25pct'       => ['emoji' => '⭐', 'titulo' => 'Um quarto lido',      'desc' => '25% da obra — já está dentro!',        'pontos' => 15],
    '50pct'       => ['emoji' => '🌟', 'titulo' => 'Metade da Jornada',   'desc' => 'Você está na metade. Continue!',       'pontos' => 25],
    '75pct'       => ['emoji' => '🔥', 'titulo' => 'Quase lá!',           'desc' => '75% lidos — o fim está próximo.',      'pontos' => 30],
    '100pct'      => ['emoji' => '🏆', 'titulo' => 'Obra Concluída',      'desc' => 'Parabéns! Você leu até a última linha.','pontos' => 50],
    'velocidade'  => ['emoji' => '⚡', 'titulo' => 'Leitor Veloz',        'desc' => 'Leitura concluída em tempo recorde!',  'pontos' => 20],
    'maratona'    => ['emoji' => '🎖', 'titulo' => 'Maratonista',         'desc' => 'Mais de 3h de leitura contínua!',      'pontos' => 35],
];

if ($acao === 'listar') {
    $st = $pdo->prepare(
        "SELECT c.*, l.titulo AS livro_titulo, l.capa_img
         FROM leitor_conquistas c JOIN livros l ON l.slug=c.livro_slug
         WHERE c.usuario_id=? AND c.livro_slug=? ORDER BY c.conquistado_em"
    );
    $st->execute([$uid, $slug]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $meta = CONQUISTAS_META[$r['tipo']] ?? [];
        $r    = array_merge($r, $meta);
    }
    jC(['ok' => true, 'conquistas' => $rows]);
}

if ($acao === 'todas') {
    $st = $pdo->prepare(
        "SELECT c.*, l.titulo AS livro_titulo, l.capa_img
         FROM leitor_conquistas c JOIN livros l ON l.slug=c.livro_slug
         WHERE c.usuario_id=? ORDER BY c.conquistado_em DESC"
    );
    $st->execute([$uid]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) { $r = array_merge($r, CONQUISTAS_META[$r['tipo']] ?? []); }
    jC(['ok' => true, 'conquistas' => $rows]);
}

/* ── registrar: salvar conquista no BD (chamado pelo JS em background) ── */
if ($acao === 'registrar') {
    $tipo = preg_replace('/[^a-z0-9_]/', '', trim($body['tipo'] ?? ''));
    $slugR = preg_replace('/[^a-z0-9_-]/', '', trim($body['slug'] ?? ''));
    if (!$tipo || !$slugR) jC(['ok' => false, 'erro' => 'Dados incompletos.']);
    if (!isset(CONQUISTAS_META[$tipo])) jC(['ok' => false, 'erro' => 'Tipo inválido.']);

    try {
        $pdo->prepare(
            "INSERT IGNORE INTO leitor_conquistas (usuario_id, livro_slug, tipo)
             VALUES (?, ?, ?)"
        )->execute([$uid, $slugR, $tipo]);
    } catch (\PDOException $e) {
        // Tabela pode não existir ainda — ignorar silenciosamente
    }
    jC(['ok' => true]);
}

if ($acao === 'enviar_email') {
    $tipo = trim($body['tipo'] ?? '');
    $slug = preg_replace('/[^a-z0-9_-]/', '', trim($body['slug'] ?? ''));
    if (!$tipo || !$slug) jC(['ok'=>false,'erro'=>'Dados incompletos.']);

    // Verificar se a conquista existe e ainda não foi enviada por e-mail
    $st = $pdo->prepare("SELECT id FROM leitor_conquistas WHERE usuario_id=? AND livro_slug=? AND tipo=? AND email_enviado=0 LIMIT 1");
    $st->execute([$uid, $slug, $tipo]);
    $cid = $st->fetchColumn();
    if (!$cid) jC(['ok'=>false,'erro'=>'Conquista não encontrada ou e-mail já enviado.']);

    $meta  = CONQUISTAS_META[$tipo] ?? [];
    $stL   = $pdo->prepare("SELECT titulo FROM livros WHERE slug=? LIMIT 1");
    $stL->execute([$slug]);
    $tituloLivro = $stL->fetchColumn() ?: $slug;

    require_once __DIR__ . '/../../backend/mailer.php';
    $nome     = $usuario['nome'] ?? 'Leitor';
    $primeiro = explode(' ', trim($nome))[0];
    $html = "
      <h2 style='font-family:Georgia,serif;color:#B8860B'>{$meta['emoji']} {$meta['titulo']}</h2>
      <p>Parabéns, <strong>$primeiro</strong>!</p>
      <p>{$meta['desc']}</p>
      <p><em>Livro:</em> <strong>$tituloLivro</strong></p>
      <p>Continue lendo em: <a href='https://www.roberiodiogenes.com/leitor/?livro=$slug'>Leitor Online</a></p>
    ";
    Mailer::enviar([
        'para_email' => $usuario['email'],
        'para_nome'  => $nome,
        'assunto'    => "{$meta['emoji']} Conquista desbloqueada: {$meta['titulo']} — $tituloLivro",
        'html'       => $html,
        'texto'      => "{$meta['titulo']} — $tituloLivro\n{$meta['desc']}",
    ]);

    $pdo->prepare("UPDATE leitor_conquistas SET email_enviado=1 WHERE id=?")->execute([$cid]);
    jC(['ok' => true]);
}

jC(['ok' => false, 'erro' => 'Ação desconhecida.']);
