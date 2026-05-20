<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/comentarios.php
   GET  ?livro=slug          → lista comentários aprovados
   POST {livro,nome,cidade,leu,texto} → envia comentário
   ================================================================ */
require_once __DIR__ . '/config.php';

$metodo = $_SERVER['REQUEST_METHOD'];

/* ── GET: listar comentários aprovados ───────────────────────── */
if ($metodo === 'GET') {
    $slug = trim($_GET['livro'] ?? '');
    if (!$slug) responderErro('Livro não informado.');

    $pdo  = db();
    $stmt = $pdo->prepare("
        SELECT nome, cidade, leu, texto, criado_em
        FROM comentarios
        WHERE livro_slug = ? AND aprovado = 1
        ORDER BY criado_em DESC
        LIMIT 50
    ");
    $stmt->execute([$slug]);
    $comentarios = $stmt->fetchAll();

    // Formatar datas
    foreach ($comentarios as &$c) {
        $c['data'] = (new DateTime($c['criado_em']))->format('d/m/Y');
        unset($c['criado_em']);
    }
    responderOk(['comentarios' => $comentarios]);
}

/* ── POST: enviar comentário ─────────────────────────────────── */
if ($metodo === 'POST') {
    verificarRateLimit('comentario', 5, 3600);

    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $slug  = trim($body['livro']  ?? '');
    $nome  = trim($body['nome']   ?? '');
    $cidade= trim($body['cidade'] ?? '');
    $leu   = trim($body['leu']    ?? '');
    $texto = trim($body['texto']  ?? '');

    if (!$slug)  responderErro('Livro não informado.');
    if (!$nome || mb_strlen($nome) < 2) responderErro('Informe seu nome.');
    if (!$texto || mb_strlen($texto) < 10) responderErro('Comentário muito curto.');
    if (mb_strlen($texto) > 2000) responderErro('Comentário muito longo (máx. 2000 caracteres).');
    if (!in_array($leu, ['sim','cap','nao',''])) $leu = '';

    $pdo = db();
    $pdo->prepare("
        INSERT INTO comentarios (livro_slug, nome, cidade, leu, texto, ip)
        VALUES (?, ?, ?, ?, ?, ?)
    ")->execute([$slug, $nome, $cidade ?: null, $leu, $texto, getIP()]);

    responderOk(['mensagem' => 'Comentário enviado! Ele aparecerá após moderação. Obrigado, ' . $nome . '.']);
}

responderErro('Método não permitido.', 405);
