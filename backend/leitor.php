<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/leitor.php  v3.0
   Usa as tabelas reais do banco: leitor_progresso, leitor_anotacoes,
   leitor_marcacoes, leitor_preferencias
   + tabelas v2: leitura_conquistas, leitura_erros_reportados,
                 leitura_feedback, leitura_lembretes_enviados
   ================================================================ */

require_once __DIR__ . '/config.php';
iniciarSessao();   // usa a função central do config.php — sem conflito

$uid    = (int) ($_SESSION['usuario_id'] ?? 0);
$metodo = $_SERVER['REQUEST_METHOD'];
$input  = $metodo === 'POST' ? (json_decode(file_get_contents('php://input'), true) ?? []) : [];
$acao   = $metodo === 'GET'  ? ($_GET['acao'] ?? '') : ($input['acao'] ?? '');
$pdo    = db();

function jr(array $d, int $s = 200): void {
    http_response_code($s);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($d);
    exit;
}
function auth(): void {
    global $uid;
    if (!$uid) jr(['ok' => false, 'erro' => 'Não autenticado.'], 401);
}

/* ================================================================
   GET
   ================================================================ */
if ($metodo === 'GET') {

    /* ── Progresso ── */
    if ($acao === 'progresso') {
        auth();
        $slug = trim($_GET['livro'] ?? '');
        $st = $pdo->prepare(
            "SELECT capitulo_atual, percentual,
                    posicao_scroll, COALESCE(total_paginas,1) AS total_capitulos
             FROM leitor_progresso
             WHERE usuario_id = ? AND livro_slug = ? LIMIT 1"
        );
        $st->execute([$uid, $slug]);
        $p = $st->fetch();
        jr(['ok' => true, 'progresso' => $p ?: ['capitulo_atual' => 1, 'percentual' => 0, 'posicao_scroll' => 0]]);
    }

    /* ── Conquistas ── */
    if ($acao === 'conquistas') {
        auth();
        $slug = trim($_GET['livro'] ?? '');
        $st = $pdo->prepare(
            "SELECT marco, medalha, titulo, conquistado_em
             FROM leitura_conquistas
             WHERE usuario_id = ? AND livro_slug = ?
             ORDER BY marco"
        );
        $st->execute([$uid, $slug]);
        jr(['ok' => true, 'conquistas' => $st->fetchAll()]);
    }

    /* ── Anotações ── */
    if ($acao === 'anotacoes') {
        auth();
        $slug = trim($_GET['livro'] ?? '');
        $st = $pdo->prepare(
            "SELECT id, capitulo, texto, cor, criado_em
             FROM leitor_anotacoes
             WHERE usuario_id = ? AND livro_slug = ?
             ORDER BY capitulo, criado_em"
        );
        $st->execute([$uid, $slug]);
        jr(['ok' => true, 'anotacoes' => $st->fetchAll()]);
    }

    /* ── Marcações ── */
    if ($acao === 'marcacoes') {
        auth();
        $slug = trim($_GET['livro'] ?? '');
        $st = $pdo->prepare(
            "SELECT id, capitulo, trecho, cor, nota
             FROM leitor_marcacoes
             WHERE usuario_id = ? AND livro_slug = ?
             ORDER BY capitulo"
        );
        $st->execute([$uid, $slug]);
        jr(['ok' => true, 'marcacoes' => $st->fetchAll()]);
    }

    /* ── Preferências ── */
    if ($acao === 'preferencias') {
        auth();
        $st = $pdo->prepare(
            "SELECT fonte, tamanho_fonte, fundo_leitura, largura_coluna, altura_linha,
                    COALESCE(ranking_opt_in, 0) AS ranking_opt_in
             FROM leitor_preferencias WHERE usuario_id = ? LIMIT 1"
        );
        $st->execute([$uid]);
        $p = $st->fetch();
        if ($p) $p['rankingOptIn'] = (bool) $p['ranking_opt_in'];
        jr(['ok' => true, 'preferencias' => $p ?: null]);
    }

    /* ── Painel — biblioteca do usuário ── */
    if ($acao === 'painel') {
        auth();
        $st = $pdo->prepare(
            "SELECT l.slug,
                    l.titulo,
                    l.capa_img,
                    COALESCE(l.tipo, 'livro')    AS tipo,
                    COALESCE(l.gratuito, 0)       AS gratuito,
                    COALESCE(l.formato, 'html')   AS formato,
                    COALESCE(p.percentual, 0)     AS percentual,
                    COALESCE(p.capitulo_atual, 1) AS capitulo_atual,
                    p.ultima_leitura
             FROM livros l
             LEFT JOIN leitor_progresso p
                    ON p.usuario_id = ? AND p.livro_slug = l.slug
             WHERE l.ativo = 1
               AND (
                     l.gratuito = 1
                     OR EXISTS (
                         SELECT 1 FROM compras c
                         WHERE c.usuario_id = ? AND c.livro_slug = l.slug
                           AND c.status = 'aprovada'
                     )
                     OR EXISTS (
                         SELECT 1 FROM assinaturas a
                         WHERE a.usuario_id = ? AND a.status = 'ativa'
                           AND a.expira_em > NOW()
                     )
               )
             ORDER BY p.ultima_leitura DESC, l.titulo"
        );
        $st->execute([$uid, $uid, $uid]);
        jr(['ok' => true, 'leituras' => $st->fetchAll()]);
    }

    /* ── Ranking ── */
    if ($acao === 'ranking') {
        $slug = trim($_GET['livro'] ?? '');
        $st = $pdo->prepare(
            "SELECT
               CONCAT(
                 SUBSTRING_INDEX(u.nome,' ',1), ' ',
                 UPPER(LEFT(SUBSTRING_INDEX(u.nome,' ',-1), 1)), '.'
               ) AS nome_exibicao,
               p.percentual,
               (SELECT COUNT(*)
                FROM leitura_conquistas
                WHERE usuario_id = p.usuario_id AND livro_slug = p.livro_slug
               ) AS conquistas
             FROM leitor_progresso p
             JOIN leitor_preferencias pr
                  ON pr.usuario_id = p.usuario_id
                 AND COALESCE(pr.ranking_opt_in, 0) = 1
             JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.livro_slug = ?
             ORDER BY p.percentual DESC, conquistas DESC
             LIMIT 20"
        );
        $st->execute([$slug]);
        jr(['ok' => true, 'ranking' => $st->fetchAll()]);
    }
}

/* ================================================================
   POST
   ================================================================ */
if ($metodo === 'POST') {
    auth();

    /* ── Salvar progresso ── */
    if ($acao === 'salvar_progresso') {
        $slug   = trim($input['livro'] ?? '');
        $cap    = max(1, (int) ($input['capitulo'] ?? 1));
        $scroll = max(0, (int) ($input['scroll'] ?? 0));
        $perc   = min(100, max(0, (float) ($input['percentual'] ?? 0)));
        $total  = max(1, (int) ($input['total_paginas'] ?? 1));
        $concl  = $perc >= 99.9 ? 1 : 0;
        if (!$slug) jr(['ok' => false, 'erro' => 'Livro não informado.']);

        $pdo->prepare(
            "INSERT INTO leitor_progresso
               (usuario_id, livro_slug, capitulo_atual, posicao_scroll,
                percentual, total_paginas, ultima_leitura, concluido, concluido_em)
             VALUES (?, ?, ?, ?, ?, ?, NOW(),
                     ?, IF(? = 1 AND concluido = 0, NOW(), concluido_em))
             ON DUPLICATE KEY UPDATE
               capitulo_atual  = VALUES(capitulo_atual),
               posicao_scroll  = VALUES(posicao_scroll),
               percentual      = VALUES(percentual),
               total_paginas   = VALUES(total_paginas),
               ultima_leitura  = NOW(),
               concluido       = IF(VALUES(percentual) >= 99.9, 1, concluido),
               concluido_em    = IF(VALUES(percentual) >= 99.9 AND concluido = 0, NOW(), concluido_em)"
        )->execute([$uid, $slug, $cap, $scroll, $perc, $total, $concl, $concl]);
        jr(['ok' => true]);
    }

    /* ── Registrar conquista + e-mail ── */
    if ($acao === 'registrar_conquista') {
        $slug  = trim($input['livro'] ?? '');
        $marco = (int) ($input['marco'] ?? 0);
        if (!$slug || !$marco) jr(['ok' => false]);

        $st = $pdo->prepare(
            "SELECT id FROM leitura_conquistas
             WHERE usuario_id = ? AND livro_slug = ? AND marco = ?"
        );
        $st->execute([$uid, $slug, $marco]);
        if ($st->fetch()) jr(['ok' => true, 'ja_existia' => true]);

        $medalhas = [25=>'🥉',50=>'🥈',75=>'🥇',90=>'⭐',100=>'🏆'];
        $titulos  = [25=>'Um quarto da jornada',50=>'Na metade do caminho',
                     75=>'Quase lá!',90=>'A reta final',100=>'Livro concluído!'];
        $medalha  = $medalhas[$marco] ?? '🏅';
        $titulo   = $titulos[$marco]  ?? "{$marco}% lidos";

        $pdo->prepare(
            "INSERT IGNORE INTO leitura_conquistas
               (usuario_id, livro_slug, marco, medalha, titulo, conquistado_em)
             VALUES (?, ?, ?, ?, ?, NOW())"
        )->execute([$uid, $slug, $marco, $medalha, $titulo]);

        $stU = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = ?");
        $stU->execute([$uid]);
        $user = $stU->fetch();
        $stL = $pdo->prepare("SELECT titulo FROM livros WHERE slug = ?");
        $stL->execute([$slug]);
        $livroTitulo = $stL->fetchColumn() ?: $slug;

        if ($user && $user['email']) {
            try {
                require_once __DIR__ . '/mailer.php';
                $link = SITE_URL . "/leitor/livro.html?livro={$slug}";
                $msgExtra = $marco === 100
                    ? 'Você terminou o livro! Que tal deixar uma avaliação?'
                    : 'Continue a leitura — o melhor ainda está por vir.';
                Mailer::enviar([
                    'para_email' => $user['email'],
                    'para_nome'  => $user['nome'],
                    'assunto'    => "{$medalha} Conquista desbloqueada: {$titulo}",
                    'html'       => "<div style=\"font-family:Georgia,serif;max-width:520px;margin:0 auto;padding:2rem;background:#FAF7F2;border-radius:8px\">
  <div style=\"text-align:center;font-size:3.5rem;margin-bottom:1rem\">{$medalha}</div>
  <h2 style=\"font-family:'Cinzel',serif;color:#B8860B;text-align:center;font-size:1.2rem\">{$titulo}</h2>
  <p style=\"color:#2C2418;line-height:1.7;text-align:center\">Parabéns, <strong>{$user['nome']}</strong>! Você leu <strong>{$marco}%</strong> de <em>{$livroTitulo}</em>.</p>
  <p style=\"color:#2C2418;line-height:1.7;text-align:center\">{$msgExtra}</p>
  <div style=\"text-align:center;margin:1.5rem 0\">
    <a href=\"{$link}\" style=\"background:#B8860B;color:#1A0F00;padding:.8rem 2rem;border-radius:6px;text-decoration:none;font-weight:700\">Continuar lendo →</a>
  </div>
</div>",
                    'texto' => "{$medalha} {$titulo} — {$marco}% de {$livroTitulo}. Continue: {$link}",
                ]);
            } catch (\Throwable $e) { /* best-effort */ }
        }
        jr(['ok' => true, 'conquista' => ['marco' => $marco, 'medalha' => $medalha, 'titulo' => $titulo]]);
    }

    /* ── Salvar preferências ── */
    if ($acao === 'salvar_preferencias') {
        $fontes   = ['serifada','classica','sans','manuscrito'];
        $fundos   = ['branco','bege','cinza','preto'];
        $larguras = ['estreita','media','larga'];
        $fonte   = in_array($input['fonte'] ?? '', $fontes) ? $input['fonte'] : 'serifada';
        $tam     = max(14, min(28, (int) ($input['tamanho_fonte'] ?? 18)));
        $fundo   = in_array($input['fundo_leitura'] ?? '', $fundos) ? $input['fundo_leitura'] : 'bege';
        $larg    = in_array($input['largura_coluna'] ?? '', $larguras) ? $input['largura_coluna'] : 'media';
        $linha   = max(1.4, min(2.4, (float) ($input['altura_linha'] ?? 1.8)));
        $ranking = (int) !empty($input['ranking_opt_in']);

        $pdo->prepare(
            "INSERT INTO leitor_preferencias
               (usuario_id, fonte, tamanho_fonte, fundo_leitura, largura_coluna, altura_linha, ranking_opt_in)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               fonte          = VALUES(fonte),
               tamanho_fonte  = VALUES(tamanho_fonte),
               fundo_leitura  = VALUES(fundo_leitura),
               largura_coluna = VALUES(largura_coluna),
               altura_linha   = VALUES(altura_linha),
               ranking_opt_in = VALUES(ranking_opt_in)"
        )->execute([$uid, $fonte, $tam, $fundo, $larg, $linha, $ranking]);
        jr(['ok' => true]);
    }

    /* ── Criar anotação ── */
    if ($acao === 'criar_anotacao') {
        $slug  = trim($input['livro'] ?? '');
        $cap   = max(1, (int) ($input['capitulo'] ?? 1));
        $texto = trim($input['texto'] ?? '');
        $cor   = trim($input['cor'] ?? '#FFD700');
        if (!$texto) jr(['ok' => false, 'erro' => 'Texto vazio.']);
        if (mb_strlen($texto) > 5000) jr(['ok' => false, 'erro' => 'Anotação muito longa.']);
        $pdo->prepare(
            "INSERT INTO leitor_anotacoes (usuario_id, livro_slug, capitulo, texto, cor)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([$uid, $slug, $cap, $texto, $cor]);
        jr(['ok' => true, 'id' => (int) $pdo->lastInsertId()]);
    }

    /* ── Deletar anotação ── */
    if ($acao === 'deletar_anotacao') {
        $pdo->prepare("DELETE FROM leitor_anotacoes WHERE id = ? AND usuario_id = ?")
            ->execute([(int) ($input['id'] ?? 0), $uid]);
        jr(['ok' => true]);
    }

    /* ── Criar marcação ── */
    if ($acao === 'criar_marcacao') {
        $slug   = trim($input['livro'] ?? '');
        $cap    = max(1, (int) ($input['capitulo'] ?? 1));
        $trecho = trim($input['trecho'] ?? '');
        $cores  = ['amarela','verde','rosa','azul'];
        $cor    = in_array($input['cor'] ?? '', $cores) ? $input['cor'] : 'amarela';
        $nota   = trim($input['nota'] ?? '') ?: null;
        if (!$trecho) jr(['ok' => false, 'erro' => 'Trecho vazio.']);
        $pdo->prepare(
            "INSERT INTO leitor_marcacoes (usuario_id, livro_slug, capitulo, trecho, cor, nota)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([$uid, $slug, $cap, $trecho, $cor, $nota]);
        jr(['ok' => true, 'id' => (int) $pdo->lastInsertId()]);
    }

    /* ── Deletar marcação ── */
    if ($acao === 'deletar_marcacao') {
        $pdo->prepare("DELETE FROM leitor_marcacoes WHERE id = ? AND usuario_id = ?")
            ->execute([(int) ($input['id'] ?? 0), $uid]);
        jr(['ok' => true]);
    }

    /* ── Reportar erro ortográfico ── */
    if ($acao === 'reportar_erro') {
        $slug   = trim($input['livro'] ?? '');
        $cap    = (int) ($input['capitulo'] ?? 1);
        $trecho = mb_substr(trim($input['trecho'] ?? ''), 0, 500);
        $tipos  = ['ortografia','gramatica','pontuacao','digitacao','outro'];
        $tipo   = in_array($input['tipo'] ?? '', $tipos) ? $input['tipo'] : 'ortografia';
        $obs    = mb_substr(trim($input['obs'] ?? ''), 0, 500);

        $pdo->prepare(
            "INSERT INTO leitura_erros_reportados
               (usuario_id, livro_slug, capitulo, trecho, tipo, observacao, criado_em)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        )->execute([$uid, $slug, $cap, $trecho, $tipo, $obs]);

        try {
            require_once __DIR__ . '/mailer.php';
            $stU = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
            $stU->execute([$uid]);
            $nome = $stU->fetchColumn() ?: 'Leitor';
            Mailer::enviar([
                'para_email' => EMAIL_DESTINATARIO,
                'para_nome'  => 'Robério Diógenes',
                'assunto'    => "⚠️ Erro reportado: {$slug} (Cap.{$cap})",
                'html'       => "<p><b>Livro:</b> {$slug}<br><b>Capítulo:</b> {$cap}<br><b>Tipo:</b> {$tipo}<br><b>Leitor:</b> {$nome}</p><blockquote style=\"border-left:3px solid #B8860B;padding-left:1rem\">{$trecho}</blockquote><p><b>Obs:</b> {$obs}</p>",
                'texto'      => "Livro:{$slug} Cap:{$cap} Tipo:{$tipo}\nTrecho:{$trecho}\nObs:{$obs}",
            ]);
        } catch (\Throwable $e) {}
        jr(['ok' => true]);
    }

    /* ── Feedback de conclusão ── */
    if ($acao === 'feedback_conclusao') {
        $slug  = trim($input['livro'] ?? '');
        $texto = mb_substr(trim($input['texto'] ?? ''), 0, 2000);
        $nota  = max(0, min(5, (int) ($input['nota'] ?? 0)));

        $pdo->prepare(
            "INSERT INTO leitura_feedback (usuario_id, livro_slug, texto, nota, criado_em)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE texto = VALUES(texto), nota = VALUES(nota), criado_em = NOW()"
        )->execute([$uid, $slug, $texto, $nota]);

        if ($texto || $nota) {
            try {
                require_once __DIR__ . '/mailer.php';
                $stU = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
                $stU->execute([$uid]);
                $nome = $stU->fetchColumn() ?: 'Leitor';
                $estrelas = str_repeat('★', $nota) . str_repeat('☆', 5 - $nota);
                Mailer::enviar([
                    'para_email' => EMAIL_DESTINATARIO,
                    'para_nome'  => 'Robério Diógenes',
                    'assunto'    => "📖 Feedback: {$slug} — {$nome}",
                    'html'       => "<p><b>Livro:</b> {$slug}<br><b>Leitor:</b> {$nome}<br><b>Nota:</b> {$estrelas}</p><blockquote style=\"border-left:3px solid #B8860B;padding-left:1rem\">{$texto}</blockquote>",
                    'texto'      => "Livro:{$slug}\nLeitor:{$nome}\nNota:{$nota}/5\n\n{$texto}",
                ]);
            } catch (\Throwable $e) {}
        }
        jr(['ok' => true]);
    }
}

jr(['ok' => false, 'erro' => 'Ação desconhecida.'], 400);
