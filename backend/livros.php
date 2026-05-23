<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/livros.php
   Endpoints para interações do leitor com os livros:

   GET  ?acao=estado&livro=slug        → estado do usuário (fav, estrelas)
   GET  ?acao=contadores&livro=slug    → total downloads, média estrelas
   GET  ?acao=todos_contadores         → contadores de todos os livros
   POST {acao:'favoritar', livro}      → toggle favorito
   POST {acao:'avaliar', livro, estrelas} → salvar avaliação
   ================================================================ */

require_once __DIR__ . '/config.php';
iniciarSessao();

$metodo = $_SERVER['REQUEST_METHOD'];
$acao   = $_GET['acao'] ?? ($metodo === 'POST' ? (json_decode(file_get_contents('php://input'), true)['acao'] ?? '') : '');

/* ── GET: estado do usuário para um livro ──────────────────── */
if ($metodo === 'GET' && $acao === 'estado') {
    $slug = trim($_GET['livro'] ?? '');
    if (!$slug) responderErro('Livro não informado.');

    if (empty($_SESSION['usuario_id'])) {
        responderOk(['logado' => false]);
    }

    $uid = $_SESSION['usuario_id'];
    $pdo = db();

    $fav  = $pdo->prepare("SELECT id FROM favoritos WHERE usuario_id=? AND livro_slug=?");
    $fav->execute([$uid, $slug]);
    $isFav = (bool) $fav->fetch();

    $aval = $pdo->prepare("SELECT estrelas FROM avaliacoes WHERE usuario_id=? AND livro_slug=?");
    $aval->execute([$uid, $slug]);
    $estrelas = (int) ($aval->fetchColumn() ?: 0);

    responderOk(['logado' => true, 'favorito' => $isFav, 'estrelas' => $estrelas]);
}

/* ── GET: contadores públicos de um livro ──────────────────── */
if ($metodo === 'GET' && $acao === 'contadores') {
    $slug = trim($_GET['livro'] ?? '');
    if (!$slug) responderErro('Livro não informado.');
    $pdo = db();

    $dl = $pdo->prepare("SELECT COUNT(*) FROM downloads_log WHERE livro_slug=?");
    $dl->execute([$slug]);
    $totalDownloads = (int) $dl->fetchColumn();

    $av = $pdo->prepare("SELECT AVG(estrelas), COUNT(*) FROM avaliacoes WHERE livro_slug=?");
    $av->execute([$slug]);
    [$media, $totalAval] = $av->fetch(PDO::FETCH_NUM);

    responderOk([
        'downloads'     => $totalDownloads,
        'media_estrelas'=> $media ? round((float)$media, 1) : null,
        'total_aval'    => (int)$totalAval,
    ]);
}

/* ── GET: contadores de TODOS os livros (para livros.html) ─── */
/* ── GET: meus favoritos com avaliação (para o perfil) ──────── */
if ($metodo === 'GET' && $acao === 'meus_favoritos') {
    if (empty($_SESSION['usuario_id'])) {
        responderErro('Não autenticado.', 401);
    }
    $uid  = $_SESSION['usuario_id'];
    $pdo  = db();
    $stmt = $pdo->prepare("
        SELECT f.livro_slug AS slug,
               l.titulo,
               a.estrelas,
               f.adicionado_em
        FROM favoritos f
        LEFT JOIN livros l ON l.slug = f.livro_slug
        LEFT JOIN avaliacoes a ON a.usuario_id = f.usuario_id AND a.livro_slug = f.livro_slug
        WHERE f.usuario_id = ?
        ORDER BY f.adicionado_em DESC
    ");
    $stmt->execute([$uid]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['estrelas']     = (int)($r['estrelas'] ?? 0);
        $r['adicionado_em']= (new DateTime($r['adicionado_em']))->format('d/m/Y');
    }
    responderOk(['favoritos' => $rows]);
}

if ($metodo === 'GET' && $acao === 'todos_contadores') {
    $pdo = db();
    $stmt = $pdo->query("
        SELECT livro_slug, COUNT(*) as total
        FROM downloads_log
        GROUP BY livro_slug
    ");
    $contadores = [];
    foreach ($stmt->fetchAll() as $r) {
        $contadores[$r['livro_slug']] = (int)$r['total'];
    }
    responderOk(['contadores' => $contadores]);
}

/* ── POST: requer login ────────────────────────────────────── */
if ($metodo === 'POST') {
    if (empty($_SESSION['usuario_id'])) {
        responderErro('Você precisa estar logado.', 401);
    }

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $acao = $body['acao'] ?? '';
    $slug = trim($body['livro'] ?? '');
    $uid  = $_SESSION['usuario_id'];
    $pdo  = db();

    if (!$slug) responderErro('Livro não informado.');

    /* ── Toggle favorito ─────────────────────────────────── */
    if ($acao === 'favoritar') {
        $check = $pdo->prepare("SELECT id FROM favoritos WHERE usuario_id=? AND livro_slug=?");
        $check->execute([$uid, $slug]);

        if ($check->fetch()) {
            // Já é favorito → remover
            $pdo->prepare("DELETE FROM favoritos WHERE usuario_id=? AND livro_slug=?")
                ->execute([$uid, $slug]);
            responderOk(['favorito' => false, 'mensagem' => 'Removido dos favoritos.']);
        } else {
            // Adicionar favorito
            $pdo->prepare("INSERT INTO favoritos (usuario_id, livro_slug) VALUES (?,?)")
                ->execute([$uid, $slug]);
            responderOk(['favorito' => true, 'mensagem' => 'Adicionado aos favoritos! ❤️']);
        }
    }

    /* ── Salvar avaliação ────────────────────────────────── */
    if ($acao === 'avaliar') {
        $estrelas = (int)($body['estrelas'] ?? 0);
        if ($estrelas < 1 || $estrelas > 5) responderErro('Avaliação inválida (1 a 5 estrelas).');

        $pdo->prepare("
            INSERT INTO avaliacoes (usuario_id, livro_slug, estrelas)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE estrelas=VALUES(estrelas), atualizado_em=NOW()
        ")->execute([$uid, $slug, $estrelas]);

        // Retornar nova média
        $av = $pdo->prepare("SELECT AVG(estrelas), COUNT(*) FROM avaliacoes WHERE livro_slug=?");
        $av->execute([$slug]);
        [$media, $total] = $av->fetch(PDO::FETCH_NUM);

        responderOk([
            'estrelas'      => $estrelas,
            'media_estrelas'=> round((float)$media, 1),
            'total_aval'    => (int)$total,
            'mensagem'      => "Avaliação de $estrelas estrela(s) registrada!",
        ]);
    }

    responderErro('Ação inválida.');
}

responderErro('Método não permitido.', 405);
