<?php
/* ================================================================
   leitor/backend/progresso.php
   GET  ?acao=carregar&slug=lumen   → { cfi, percentual, capitulo, ... }
   POST { acao:salvar, slug, cfi, percentual, capitulo, tempo_min }
   POST { acao:concluir, slug }
   ================================================================ */
ob_start();
require_once __DIR__ . '/../../backend/config.php';
iniciarSessao();

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario) {
    ob_end_clean(); http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'erro'=>'Login necessário.']); exit;
}

$pdo  = db();
$uid  = (int)$usuario['id'];
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$acao = trim($_GET['acao'] ?? $body['acao'] ?? '');
$slug = preg_replace('/[^a-z0-9_-]/', '', trim($_GET['slug'] ?? $body['slug'] ?? ''));

function jR(array $d): void { ob_end_clean(); header('Content-Type: application/json; charset=utf-8'); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

/* ── carregar ────────────────────────────────────────────────── */
if ($acao === 'carregar' && $slug) {
    $st = $pdo->prepare("SELECT * FROM leitor_progresso WHERE usuario_id=? AND livro_slug=? LIMIT 1");
    $st->execute([$uid, $slug]);
    $prog = $st->fetch(PDO::FETCH_ASSOC) ?: null;

    // Carregar preferências
    $stPref = $pdo->prepare("SELECT * FROM leitor_preferencias WHERE usuario_id=? LIMIT 1");
    $stPref->execute([$uid]);
    $pref = $stPref->fetch(PDO::FETCH_ASSOC) ?: ['fonte'=>'serifada','tamanho'=>18,'espacamento'=>1.8,'largura'=>'media','tema'=>'claro'];

    jR(['ok' => true, 'progresso' => $prog, 'preferencias' => $pref]);
}

/* ── salvar ──────────────────────────────────────────────────── */
if ($acao === 'salvar') {
    $cfi        = trim($body['cfi']        ?? '');
    $percentual = min(100, max(0, (float)($body['percentual'] ?? 0)));
    $capitulo   = trim($body['capitulo']   ?? '');
    $tempo_min  = max(0, (int)($body['tempo_min'] ?? 0));

    $pdo->prepare(
        "INSERT INTO leitor_progresso (usuario_id, livro_slug, cfi, percentual, capitulo_atual, tempo_total_min)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           cfi           = VALUES(cfi),
           percentual    = GREATEST(percentual, VALUES(percentual)),
           capitulo_atual= VALUES(capitulo_atual),
           tempo_total_min = tempo_total_min + VALUES(tempo_total_min),
           ultima_leitura = NOW()"
    )->execute([$uid, $slug, $cfi, $percentual, $capitulo, $tempo_min]);

    // Verificar conquistas
    verificarConquistas($pdo, $uid, $slug, $percentual);

    jR(['ok' => true]);
}

/* ── concluir ────────────────────────────────────────────────── */
if ($acao === 'concluir') {
    $pdo->prepare(
        "UPDATE leitor_progresso SET concluido=1, concluido_em=NOW(), percentual=100
         WHERE usuario_id=? AND livro_slug=?"
    )->execute([$uid, $slug]);

    verificarConquistas($pdo, $uid, $slug, 100);

    // Atualizar ranking: +50 pontos por livro concluído
    $tipo = $pdo->prepare("SELECT tipo FROM livros WHERE slug=? LIMIT 1")->execute([$slug])
            ? $pdo->query("SELECT tipo FROM livros WHERE slug='$slug' LIMIT 1")->fetchColumn()
            : 'livro';
    atualizarRanking($pdo, $uid, 50, $tipo === 'conto' ? 'conto' : 'livro');

    jR(['ok' => true]);
}

/* ── salvar_preferencias ─────────────────────────────────────── */
if ($acao === 'salvar_preferencias') {
    $fonte      = in_array($body['fonte'] ?? '', ['serifada','sem-serifa','manuscrita']) ? $body['fonte'] : 'serifada';
    $tamanho    = min(28, max(12, (int)($body['tamanho']    ?? 18)));
    $espacamento= min(2.5, max(1.2, (float)($body['espacamento'] ?? 1.8)));
    $largura    = in_array($body['largura'] ?? '', ['estreita','media','larga']) ? $body['largura'] : 'media';
    $tema       = in_array($body['tema'] ?? '', ['claro','sepia','escuro']) ? $body['tema'] : 'claro';

    $pdo->prepare(
        "INSERT INTO leitor_preferencias (usuario_id, fonte, tamanho, espacamento, largura, tema)
         VALUES (?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE fonte=VALUES(fonte), tamanho=VALUES(tamanho),
           espacamento=VALUES(espacamento), largura=VALUES(largura), tema=VALUES(tema)"
    )->execute([$uid, $fonte, $tamanho, $espacamento, $largura, $tema]);

    jR(['ok' => true]);
}

/* ── helpers internos ────────────────────────────────────────── */
function verificarConquistas(PDO $pdo, int $uid, string $slug, float $pct): void {
    $marcos = [
        'inicio' => 1,   '25pct' => 25, '50pct' => 50,
        '75pct'  => 75,  '100pct'=> 100,
    ];
    foreach ($marcos as $tipo => $minPct) {
        if ($pct >= $minPct) {
            try {
                $pdo->prepare(
                    "INSERT IGNORE INTO leitor_conquistas (usuario_id, livro_slug, tipo)
                     VALUES (?, ?, ?)"
                )->execute([$uid, $slug, $tipo]);
            } catch (PDOException $e) {}
        }
    }
    // Streak de leitura
    atualizarRanking($pdo, $uid, max(1, (int)($pct / 10)), 'nenhum');
}

function atualizarRanking(PDO $pdo, int $uid, int $pontos, string $tipo): void {
    $col = $tipo === 'livro' ? ', livros_lidos = livros_lidos + 1' : ($tipo === 'conto' ? ', contos_lidos = contos_lidos + 1' : '');
    try {
        $pdo->prepare(
            "INSERT INTO leitor_ranking (usuario_id, pontos) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE pontos = pontos + VALUES(pontos) $col"
        )->execute([$uid, $pontos]);
    } catch (PDOException $e) {}
}

jR(['ok' => false, 'erro' => 'Ação desconhecida.']);
