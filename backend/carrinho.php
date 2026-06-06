<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/carrinho.php
   Persiste o carrinho do usuário no BD para:
     · Sincronizar entre dispositivos
     · Detectar carrinhos abandonados e enviar lembrete

   GET                              → carregar carrinho salvo
   POST { acao:'salvar', itens:[] } → persistir carrinho
   POST { acao:'limpar' }           → esvaziar carrinho no BD
   POST { acao:'checkout' }         → registrar início de checkout
   ================================================================ */

require_once __DIR__ . '/config.php';
iniciarSessao();

/* Usuários não logados: apenas retornar vazio/ok (carrinho fica só no localStorage) */
$uid = (int) ($_SESSION['usuario_id'] ?? 0);
$pdo = db();

function jCart(array $d): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];

/* ── GET: carregar carrinho do BD ─────────────────────────────── */
if ($metodo === 'GET') {
    if (!$uid) { jCart(['ok' => true, 'itens' => [], 'logado' => false]); }

    $st = $pdo->prepare(
        "SELECT itens, atualizado_em FROM carrinhos WHERE usuario_id = ? LIMIT 1"
    );
    $st->execute([$uid]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    $itens = $row ? (json_decode($row['itens'], true) ?? []) : [];
    jCart(['ok' => true, 'logado' => true, 'itens' => $itens,
           'atualizado_em' => $row['atualizado_em'] ?? null]);
}

/* ── POST ─────────────────────────────────────────────────────── */
if ($metodo === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $acao = trim($body['acao'] ?? '');

    if (!$uid) { jCart(['ok' => true]); } // não logado: silencioso

    /* ── salvar ──────────────────────────────────────────── */
    if ($acao === 'salvar') {
        $itens = $body['itens'] ?? [];
        if (!is_array($itens)) { jCart(['ok' => false, 'erro' => 'Itens inválidos.']); }

        // Sanitizar: apenas os campos necessários
        $itensSanitizados = array_map(fn($i) => [
            'slug'   => preg_replace('/[^a-z0-9_-]/', '', $i['slug'] ?? ''),
            'titulo' => substr(strip_tags($i['titulo'] ?? ''), 0, 200),
            'preco'  => round(max(0, (float)($i['preco'] ?? 0)), 2),
            'capa'   => substr(strip_tags($i['capa'] ?? ''), 0, 300),
        ], $itens);

        $pdo->prepare(
            "INSERT INTO carrinhos (usuario_id, itens, em_checkout)
             VALUES (?, ?, 0)
             ON DUPLICATE KEY UPDATE
               itens         = VALUES(itens),
               em_checkout   = 0,
               atualizado_em = NOW()"
        )->execute([$uid, json_encode($itensSanitizados, JSON_UNESCAPED_UNICODE)]);

        jCart(['ok' => true]);
    }

    /* ── limpar ──────────────────────────────────────────── */
    if ($acao === 'limpar') {
        $pdo->prepare("DELETE FROM carrinhos WHERE usuario_id = ?")
            ->execute([$uid]);
        jCart(['ok' => true]);
    }

    /* ── checkout: marcar que iniciou pagamento ──────────── */
    if ($acao === 'checkout') {
        $pdo->prepare(
            "UPDATE carrinhos SET em_checkout = 1, checkout_em = NOW()
             WHERE usuario_id = ?"
        )->execute([$uid]);
        jCart(['ok' => true]);
    }

    jCart(['ok' => false, 'erro' => 'Ação inválida.']);
}

jCart(['ok' => false, 'erro' => 'Método não permitido.']);
