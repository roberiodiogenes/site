<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/leitor.php
   Endpoints para o leitor de livros online.

   GET  ?acao=progresso&livro=slug          → progresso atual
   GET  ?acao=anotacoes&livro=slug          → lista de anotações
   GET  ?acao=marcacoes&livro=slug          → lista de marcações
   GET  ?acao=preferencias                  → preferências tipográficas
   GET  ?acao=painel                        → todos os livros em leitura

   POST { acao:'salvar_progresso', livro, capitulo, scroll, percentual }
   POST { acao:'criar_anotacao',   livro, capitulo, texto, cor }
   POST { acao:'editar_anotacao',  id, texto, cor }
   POST { acao:'deletar_anotacao', id }
   POST { acao:'criar_marcacao',   livro, capitulo, trecho, cor, nota }
   POST { acao:'deletar_marcacao', id }
   POST { acao:'salvar_preferencias', fonte, tamanho_fonte, fundo_leitura, ... }
   ================================================================ */

require_once __DIR__ . '/config.php';
iniciarSessao();

/* ── Apenas usuários logados ─────────────────────────────────── */
if (empty($_SESSION['usuario_id'])) {
    responderErro('Você precisa estar logado.', 401);
}

$uid    = (int) $_SESSION['usuario_id'];
$metodo = $_SERVER['REQUEST_METHOD'];
$pdo    = db();

/* ──────────────────────────────────────────────────────────────
   GET
   ────────────────────────────────────────────────────────────── */
if ($metodo === 'GET') {
    $acao = trim($_GET['acao'] ?? '');
    $slug = trim($_GET['livro'] ?? '');

    /* ── Progresso de leitura ── */
    if ($acao === 'progresso') {
        if (!$slug) responderErro('Livro não informado.');
        $stmt = $pdo->prepare(
            "SELECT capitulo_atual, posicao_scroll, percentual,
                    total_paginas, ultima_leitura, concluido
             FROM leitor_progresso
             WHERE usuario_id = ? AND livro_slug = ?"
        );
        $stmt->execute([$uid, $slug]);
        $row = $stmt->fetch();

        if (!$row) {
            responderOk(['progresso' => null]); // nunca leu este livro
        }

        responderOk([
            'progresso' => [
                'capitulo_atual'  => (int)   $row['capitulo_atual'],
                'posicao_scroll'  => (int)   $row['posicao_scroll'],
                'percentual'      => (float) $row['percentual'],
                'total_paginas'   => (int)   $row['total_paginas'],
                'ultima_leitura'  => $row['ultima_leitura'],
                'concluido'       => (bool)  $row['concluido'],
            ],
        ]);
    }

    /* ── Anotações ── */
    if ($acao === 'anotacoes') {
        if (!$slug) responderErro('Livro não informado.');
        $stmt = $pdo->prepare(
            "SELECT id, capitulo, texto, cor, criado_em, atualizado_em
             FROM leitor_anotacoes
             WHERE usuario_id = ? AND livro_slug = ?
             ORDER BY capitulo ASC, criado_em ASC"
        );
        $stmt->execute([$uid, $slug]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) { $r['id'] = (int) $r['id']; $r['capitulo'] = (int) $r['capitulo']; }
        responderOk(['anotacoes' => $rows]);
    }

    /* ── Marcações ── */
    if ($acao === 'marcacoes') {
        if (!$slug) responderErro('Livro não informado.');
        $stmt = $pdo->prepare(
            "SELECT id, capitulo, trecho, cor, nota, criado_em
             FROM leitor_marcacoes
             WHERE usuario_id = ? AND livro_slug = ?
             ORDER BY capitulo ASC, criado_em ASC"
        );
        $stmt->execute([$uid, $slug]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) { $r['id'] = (int) $r['id']; $r['capitulo'] = (int) $r['capitulo']; }
        responderOk(['marcacoes' => $rows]);
    }

    /* ── Preferências ── */
    if ($acao === 'preferencias') {
        $stmt = $pdo->prepare(
            "SELECT fonte, tamanho_fonte, fundo_leitura, largura_coluna, altura_linha
             FROM leitor_preferencias WHERE usuario_id = ?"
        );
        $stmt->execute([$uid]);
        $prefs = $stmt->fetch();
        if (!$prefs) {
            // Retorna padrões se ainda não salvou
            $prefs = [
                'fonte'          => 'serifada',
                'tamanho_fonte'  => 18,
                'fundo_leitura'  => 'bege',
                'largura_coluna' => 'media',
                'altura_linha'   => 1.8,
            ];
        }
        $prefs['tamanho_fonte'] = (int)   $prefs['tamanho_fonte'];
        $prefs['altura_linha']  = (float) $prefs['altura_linha'];
        responderOk(['preferencias' => $prefs]);
    }

    /* ── Painel: livros em leitura ── */
    if ($acao === 'painel') {
        $stmt = $pdo->prepare(
            "SELECT lp.livro_slug AS slug,
                    l.titulo,
                    l.capa_img,
                    lp.capitulo_atual,
                    lp.percentual,
                    lp.ultima_leitura,
                    lp.concluido
             FROM leitor_progresso lp
             LEFT JOIN livros l ON l.slug = lp.livro_slug
             WHERE lp.usuario_id = ?
             ORDER BY lp.ultima_leitura DESC
             LIMIT 20"
        );
        $stmt->execute([$uid]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['percentual']    = (float) $r['percentual'];
            $r['capitulo_atual']= (int)   $r['capitulo_atual'];
            $r['concluido']     = (bool)  $r['concluido'];
        }
        responderOk(['leituras' => $rows]);
    }

    responderErro('Ação inválida.');
}

/* ──────────────────────────────────────────────────────────────
   POST
   ────────────────────────────────────────────────────────────── */
if ($metodo === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $acao = trim($body['acao'] ?? '');
    $slug = trim($body['livro'] ?? '');

    /* ── Salvar progresso ── */
    if ($acao === 'salvar_progresso') {
        if (!$slug) responderErro('Livro não informado.');

        $capitulo  = max(1, (int) ($body['capitulo']   ?? 1));
        $scroll    = max(0, (int) ($body['scroll']     ?? 0));
        $percentual= max(0, min(100, (float) ($body['percentual'] ?? 0)));
        $totalPag  = (int) ($body['total_paginas'] ?? 0) ?: null;
        $concluido = $percentual >= 100.0 ? 1 : 0;

        $pdo->prepare(
            "INSERT INTO leitor_progresso
                (usuario_id, livro_slug, capitulo_atual, posicao_scroll, percentual, total_paginas, concluido, concluido_em)
             VALUES (?, ?, ?, ?, ?, ?, ?, IF(? >= 100, NOW(), NULL))
             ON DUPLICATE KEY UPDATE
                capitulo_atual  = VALUES(capitulo_atual),
                posicao_scroll  = VALUES(posicao_scroll),
                percentual      = VALUES(percentual),
                total_paginas   = COALESCE(VALUES(total_paginas), total_paginas),
                concluido       = VALUES(concluido),
                concluido_em    = IF(VALUES(concluido) = 1 AND concluido_em IS NULL, NOW(), concluido_em)"
        )->execute([$uid, $slug, $capitulo, $scroll, $percentual, $totalPag, $concluido, $percentual]);

        responderOk(['percentual' => $percentual, 'concluido' => (bool) $concluido]);
    }

    /* ── Criar anotação ── */
    if ($acao === 'criar_anotacao') {
        if (!$slug) responderErro('Livro não informado.');
        $capitulo = max(1, (int) ($body['capitulo'] ?? 1));
        $texto    = trim($body['texto'] ?? '');
        $cor      = trim($body['cor']   ?? '#FFD700');

        if (!$texto) responderErro('Texto da anotação não pode ser vazio.');
        if (mb_strlen($texto) > 5000) responderErro('Anotação muito longa (máx. 5000 caracteres).');

        // Limitar a 100 anotações por livro por usuário
        $count = $pdo->prepare(
            "SELECT COUNT(*) FROM leitor_anotacoes WHERE usuario_id=? AND livro_slug=?"
        );
        $count->execute([$uid, $slug]);
        if ((int) $count->fetchColumn() >= 100) {
            responderErro('Limite de 100 anotações por livro atingido.');
        }

        $stmt = $pdo->prepare(
            "INSERT INTO leitor_anotacoes (usuario_id, livro_slug, capitulo, texto, cor)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$uid, $slug, $capitulo, $texto, $cor]);
        $id = (int) $pdo->lastInsertId();

        responderOk(['id' => $id, 'mensagem' => 'Anotação salva!']);
    }

    /* ── Editar anotação ── */
    if ($acao === 'editar_anotacao') {
        $id    = (int) ($body['id']    ?? 0);
        $texto = trim($body['texto']   ?? '');
        $cor   = trim($body['cor']     ?? '#FFD700');

        if (!$id || !$texto) responderErro('Dados inválidos.');

        $stmt = $pdo->prepare(
            "UPDATE leitor_anotacoes SET texto=?, cor=?
             WHERE id=? AND usuario_id=?"
        );
        $stmt->execute([$texto, $cor, $id, $uid]);

        if ($stmt->rowCount() === 0) responderErro('Anotação não encontrada.', 404);
        responderOk(['mensagem' => 'Anotação atualizada!']);
    }

    /* ── Deletar anotação ── */
    if ($acao === 'deletar_anotacao') {
        $id = (int) ($body['id'] ?? 0);
        if (!$id) responderErro('ID inválido.');

        $stmt = $pdo->prepare(
            "DELETE FROM leitor_anotacoes WHERE id=? AND usuario_id=?"
        );
        $stmt->execute([$id, $uid]);
        if ($stmt->rowCount() === 0) responderErro('Anotação não encontrada.', 404);
        responderOk(['mensagem' => 'Anotação removida.']);
    }

    /* ── Criar marcação (highlight) ── */
    if ($acao === 'criar_marcacao') {
        if (!$slug) responderErro('Livro não informado.');
        $capitulo = max(1, (int) ($body['capitulo'] ?? 1));
        $trecho   = trim($body['trecho'] ?? '');
        $cor      = trim($body['cor']    ?? '#FFD700');
        $nota     = trim($body['nota']   ?? '') ?: null;

        if (!$trecho) responderErro('Trecho não pode ser vazio.');
        if (mb_strlen($trecho) > 2000) responderErro('Trecho muito longo (máx. 2000 caracteres).');

        // Limitar a 200 marcações por livro por usuário
        $count = $pdo->prepare(
            "SELECT COUNT(*) FROM leitor_marcacoes WHERE usuario_id=? AND livro_slug=?"
        );
        $count->execute([$uid, $slug]);
        if ((int) $count->fetchColumn() >= 200) {
            responderErro('Limite de 200 marcações por livro atingido.');
        }

        $stmt = $pdo->prepare(
            "INSERT INTO leitor_marcacoes (usuario_id, livro_slug, capitulo, trecho, cor, nota)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$uid, $slug, $capitulo, $trecho, $cor, $nota]);
        $id = (int) $pdo->lastInsertId();

        responderOk(['id' => $id, 'mensagem' => 'Trecho marcado!']);
    }

    /* ── Deletar marcação ── */
    if ($acao === 'deletar_marcacao') {
        $id = (int) ($body['id'] ?? 0);
        if (!$id) responderErro('ID inválido.');

        $stmt = $pdo->prepare(
            "DELETE FROM leitor_marcacoes WHERE id=? AND usuario_id=?"
        );
        $stmt->execute([$id, $uid]);
        if ($stmt->rowCount() === 0) responderErro('Marcação não encontrada.', 404);
        responderOk(['mensagem' => 'Marcação removida.']);
    }

    /* ── Salvar preferências ── */
    if ($acao === 'salvar_preferencias') {
        $fonteOk     = ['serifada','sans','manuscrito','classica'];
        $fundoOk     = ['branco','bege','cinza','preto'];
        $larguraOk   = ['estreita','media','larga'];

        $fonte      = in_array($body['fonte']          ?? '', $fonteOk)   ? $body['fonte']          : 'serifada';
        $tamanho    = max(14, min(28, (int) ($body['tamanho_fonte']  ?? 18)));
        $fundo      = in_array($body['fundo_leitura']  ?? '', $fundoOk)   ? $body['fundo_leitura']  : 'bege';
        $largura    = in_array($body['largura_coluna'] ?? '', $larguraOk) ? $body['largura_coluna'] : 'media';
        $linha      = max(1.4, min(2.4, (float) ($body['altura_linha'] ?? 1.8)));

        $pdo->prepare(
            "INSERT INTO leitor_preferencias (usuario_id, fonte, tamanho_fonte, fundo_leitura, largura_coluna, altura_linha)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                fonte          = VALUES(fonte),
                tamanho_fonte  = VALUES(tamanho_fonte),
                fundo_leitura  = VALUES(fundo_leitura),
                largura_coluna = VALUES(largura_coluna),
                altura_linha   = VALUES(altura_linha)"
        )->execute([$uid, $fonte, $tamanho, $fundo, $largura, $linha]);

        responderOk(['mensagem' => 'Preferências salvas!']);
    }

    responderErro('Ação inválida.');
}

responderErro('Método não permitido.', 405);
