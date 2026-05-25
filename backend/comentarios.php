<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/comentarios.php
   Comentários para posts do blog e (futuramente) páginas de livros.

   GET  ?acao=listar&slug=post-01            → lista comentários aprovados
   POST { acao:'criar', slug, texto }        → cria comentário (requer login)
   POST { acao:'deletar', id }               → remove (admin ou dono)
   ================================================================ */

require_once __DIR__ . '/config.php';
iniciarSessao();

$metodo = $_SERVER['REQUEST_METHOD'];

/* ──────────────────────────────────────────────────────────────
   GET — listar comentários de um post
   ────────────────────────────────────────────────────────────── */
if ($metodo === 'GET') {
    $slug = trim($_GET['slug'] ?? '');
    if (!$slug) responderErro('Slug não informado.');

    $pdo  = db();
    $stmt = $pdo->prepare(
        "SELECT c.id, c.texto, c.criado_em,
                u.nome, u.foto_url
         FROM comentarios c
         JOIN usuarios u ON u.id = c.usuario_id
         WHERE c.referencia = ? AND c.tipo = 'blog' AND c.aprovado = 1
         ORDER BY c.criado_em ASC
         LIMIT 100"
    );
    $stmt->execute([$slug]);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
        $r['id'] = (int) $r['id'];
        $r['foto_url'] = $r['foto_url'] ?: null;
    }

    responderOk(['comentarios' => $rows]);
}

/* ──────────────────────────────────────────────────────────────
   POST
   ────────────────────────────────────────────────────────────── */
if ($metodo === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $acao = trim($body['acao'] ?? '');

    /* ── Criar comentário ── */
    if ($acao === 'criar') {
        if (empty($_SESSION['usuario_id'])) {
            responderErro('Faça login para comentar.', 401);
        }
        $uid  = (int) $_SESSION['usuario_id'];
        $slug = trim($body['slug']  ?? '');
        $texto = trim($body['texto'] ?? '');

        if (!$slug)  responderErro('Post não identificado.');
        if (!$texto) responderErro('Comentário não pode ser vazio.');
        if (mb_strlen($texto) < 5)    responderErro('Comentário muito curto (mín. 5 caracteres).');
        if (mb_strlen($texto) > 1500) responderErro('Comentário muito longo (máx. 1500 caracteres).');

        // Rate limiting: máx 3 comentários por hora por usuário
        verificarRateLimit('comentario_' . $uid, 3, 3600);

        $pdo = db();

        // Verifica se o usuário já comentou o mesmo post nos últimos 30 segundos
        $recente = $pdo->prepare(
            "SELECT id FROM comentarios
             WHERE usuario_id = ? AND referencia = ? AND criado_em > DATE_SUB(NOW(), INTERVAL 30 SECOND)
             LIMIT 1"
        );
        $recente->execute([$uid, $slug]);
        if ($recente->fetch()) {
            responderErro('Aguarde alguns segundos antes de comentar novamente.');
        }

        // Aprovação automática (mude para 0 se quiser moderação manual)
        $aprovado = 1;

        $pdo->prepare(
            "INSERT INTO comentarios (usuario_id, referencia, tipo, texto, aprovado)
             VALUES (?, ?, 'blog', ?, ?)"
        )->execute([$uid, $slug, $texto, $aprovado]);

        $id = (int) $pdo->lastInsertId();

        responderOk([
            'id'       => $id,
            'mensagem' => $aprovado
                ? 'Comentário publicado!'
                : 'Comentário enviado e aguardando aprovação.',
        ]);
    }

    /* ── Deletar comentário (dono ou admin) ── */
    if ($acao === 'deletar') {
        if (empty($_SESSION['usuario_id'])) responderErro('Não autenticado.', 401);
        $uid = (int) $_SESSION['usuario_id'];
        $id  = (int) ($body['id'] ?? 0);
        if (!$id) responderErro('ID inválido.');

        $pdo  = db();
        $stmt = $pdo->prepare("SELECT usuario_id FROM comentarios WHERE id = ?");
        $stmt->execute([$id]);
        $com = $stmt->fetch();

        if (!$com) responderErro('Comentário não encontrado.', 404);

        // Permite deletar se for o dono ou se for admin
        $ehAdmin = !empty($_SESSION['admin_id']);
        if ($com['usuario_id'] !== $uid && !$ehAdmin) {
            responderErro('Sem permissão.', 403);
        }

        $pdo->prepare("DELETE FROM comentarios WHERE id = ?")->execute([$id]);
        responderOk(['mensagem' => 'Comentário removido.']);
    }

    responderErro('Ação inválida.');
}

responderErro('Método não permitido.', 405);
