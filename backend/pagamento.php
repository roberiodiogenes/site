<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/pagamento.php
   Integração Mercado Pago — Checkout Pro (redirect)

   GET  ?acao=planos                    → lista os planos de assinatura
   GET  ?acao=livro&slug=lumen          → preço e info do livro
   GET  ?acao=status&ref=ID             → status de um pagamento

   POST { acao:'iniciar_compra',  livro_slug }  → URL checkout (compra avulsa)
   POST { acao:'iniciar_assinatura', plano_id } → URL checkout (assinatura)
   POST { acao:'webhook' }                      → receber notificação do MP

   Documentação MP: https://www.mercadopago.com.br/developers/pt/docs
   ================================================================ */

require_once __DIR__ . '/config.php';
iniciarSessao();

/* ── Credenciais Mercado Pago ─────────────────────────────────
   Crie suas credenciais em: https://www.mercadopago.com.br/developers
   → Suas integrações → Credenciais de produção / teste               */
define('MP_ACCESS_TOKEN', AMBIENTE === 'local'
    ? 'TEST-COLE_SEU_ACCESS_TOKEN_DE_TESTE_AQUI'   // ← token de teste (sandbox)
    : 'APP_USR-COLE_SEU_ACCESS_TOKEN_PRODUCAO_AQUI' // ← token de produção
);
define('MP_PUBLIC_KEY', AMBIENTE === 'local'
    ? 'TEST-COLE_SUA_PUBLIC_KEY_DE_TESTE_AQUI'
    : 'APP_USR-COLE_SUA_PUBLIC_KEY_PRODUCAO_AQUI'
);

/* ── Roteamento ──────────────────────────────────────────────── */
$metodo = $_SERVER['REQUEST_METHOD'];

/* ──────────────────────────────────────────────────────────────
   GET
   ────────────────────────────────────────────────────────────── */
if ($metodo === 'GET') {
    $acao = trim($_GET['acao'] ?? '');

    /* ── Listar planos ── */
    if ($acao === 'planos') {
        $pdo  = db();
        $rows = $pdo->query(
            "SELECT id, slug, nome, descricao, preco, duracao_dias
             FROM planos WHERE ativo = 1 ORDER BY preco ASC"
        )->fetchAll();

        foreach ($rows as &$r) {
            $r['id']           = (int) $r['id'];
            $r['preco']        = (float) $r['preco'];
            $r['duracao_dias'] = (int)   $r['duracao_dias'];
        }
        responderOk(['planos' => $rows]);
    }

    /* ── Info de livro ── */
    if ($acao === 'livro') {
        $slug = trim($_GET['slug'] ?? '');
        if (!$slug) responderErro('Slug não informado.');

        $pdo  = db();
        $stmt = $pdo->prepare(
            "SELECT slug, titulo, preco, preco_promocao, sinopse, capa_img
             FROM livros WHERE slug = ? AND ativo = 1"
        );
        $stmt->execute([$slug]);
        $livro = $stmt->fetch();

        if (!$livro) responderErro('Livro não encontrado.', 404);

        $livro['preco']          = $livro['preco']          ? (float) $livro['preco']          : null;
        $livro['preco_promocao'] = $livro['preco_promocao'] ? (float) $livro['preco_promocao'] : null;
        responderOk(['livro' => $livro]);
    }

    /* ── Status de pagamento ── */
    if ($acao === 'status') {
        if (empty($_SESSION['usuario_id'])) responderErro('Não autenticado.', 401);
        $ref = trim($_GET['ref'] ?? '');
        if (!$ref) responderErro('Referência não informada.');

        $pdo  = db();
        $uid  = (int) $_SESSION['usuario_id'];

        // Verifica compras
        $stmt = $pdo->prepare(
            "SELECT status, livro_slug AS item, 'compra' AS tipo
             FROM compras WHERE ref_externa = ? AND usuario_id = ?
             UNION ALL
             SELECT a.status, p.slug AS item, 'assinatura' AS tipo
             FROM assinaturas a JOIN planos p ON p.id = a.plano_id
             WHERE a.ref_externa = ? AND a.usuario_id = ?
             LIMIT 1"
        );
        $stmt->execute([$ref, $uid, $ref, $uid]);
        $row = $stmt->fetch();

        if (!$row) responderErro('Referência não encontrada.', 404);
        responderOk(['status' => $row['status'], 'tipo' => $row['tipo'], 'item' => $row['item']]);
    }

    responderErro('Ação inválida.');
}

/* ──────────────────────────────────────────────────────────────
   POST
   ────────────────────────────────────────────────────────────── */
if ($metodo === 'POST') {

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $acao = trim($body['acao'] ?? $_POST['acao'] ?? '');

    /* ── Webhook do Mercado Pago (não requer sessão) ── */
    if ($acao === 'webhook') {
        processarWebhook();
        http_response_code(200);
        exit;
    }

    /* ── Requer autenticação para as demais ações ── */
    if (empty($_SESSION['usuario_id'])) {
        responderErro('Você precisa estar logado.', 401);
    }
    $uid = (int) $_SESSION['usuario_id'];
    $pdo = db();

    /* ── Busca nome e email do usuário para o MP ── */
    $uStmt = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = ?");
    $uStmt->execute([$uid]);
    $usuario = $uStmt->fetch();
    if (!$usuario) responderErro('Usuário não encontrado.', 404);

    /* ── Iniciar compra avulsa ── */
    if ($acao === 'iniciar_compra') {
        $slug = trim($body['livro_slug'] ?? '');
        if (!$slug) responderErro('Livro não informado.');

        // Verifica se já comprou
        $jaComprou = $pdo->prepare(
            "SELECT id FROM compras WHERE usuario_id = ? AND livro_slug = ? AND status = 'aprovada'"
        );
        $jaComprou->execute([$uid, $slug]);
        if ($jaComprou->fetch()) {
            responderErro('Você já possui este livro.', 409);
        }

        // Busca dados do livro
        $lStmt = $pdo->prepare(
            "SELECT titulo, preco, preco_promocao FROM livros WHERE slug = ? AND ativo = 1"
        );
        $lStmt->execute([$slug]);
        $livro = $lStmt->fetch();
        if (!$livro) responderErro('Livro não encontrado.', 404);

        $preco = $livro['preco_promocao'] ?? $livro['preco'];
        if (!$preco || $preco <= 0) responderErro('Preço não configurado para este livro.');

        // Gera ID externo único
        $refExterna = 'compra_' . $uid . '_' . $slug . '_' . time();

        // Cria preferência no Mercado Pago
        $preference = criarPreferenciaMP([
            'items' => [[
                'id'          => $slug,
                'title'       => $livro['titulo'],
                'description' => 'Livro digital — acesso completo no leitor online',
                'quantity'    => 1,
                'currency_id' => 'BRL',
                'unit_price'  => (float) $preco,
            ]],
            'payer' => [
                'name'  => $usuario['nome'],
                'email' => $usuario['email'],
            ],
            'back_urls' => [
                'success' => SITE_URL . '/pagamento/sucesso.html?ref=' . $refExterna,
                'failure' => SITE_URL . '/pagamento/falha.html?ref=' . $refExterna,
                'pending' => SITE_URL . '/pagamento/pendente.html?ref=' . $refExterna,
            ],
            'auto_return'        => 'approved',
            'external_reference' => $refExterna,
            'notification_url'   => SITE_URL . '/backend/pagamento.php?acao=webhook',
            'expires'            => true,
            'expiration_date_to' => date('c', strtotime('+24 hours')),
            'metadata'           => [
                'usuario_id' => $uid,
                'tipo'       => 'compra',
                'livro_slug' => $slug,
            ],
        ]);

        // Registra compra como pendente
        $pdo->prepare(
            "INSERT INTO compras (usuario_id, livro_slug, preco_pago, status, gateway, ref_externa)
             VALUES (?, ?, ?, 'pendente', 'mercadopago', ?)
             ON DUPLICATE KEY UPDATE status='pendente', ref_externa=VALUES(ref_externa)"
        )->execute([$uid, $slug, $preco, $refExterna]);

        responderOk([
            'checkout_url' => $preference['init_point'],
            'ref'          => $refExterna,
        ]);
    }

    /* ── Iniciar assinatura ── */
    if ($acao === 'iniciar_assinatura') {
        $planoId = (int) ($body['plano_id'] ?? 0);
        if (!$planoId) responderErro('Plano não informado.');

        // Busca dados do plano
        $pStmt = $pdo->prepare(
            "SELECT id, nome, preco, duracao_dias FROM planos WHERE id = ? AND ativo = 1"
        );
        $pStmt->execute([$planoId]);
        $plano = $pStmt->fetch();
        if (!$plano) responderErro('Plano não encontrado.', 404);

        // Gera referência
        $refExterna = 'assin_' . $uid . '_p' . $planoId . '_' . time();

        // Cria preferência no Mercado Pago
        $preference = criarPreferenciaMP([
            'items' => [[
                'id'          => 'plano_' . $plano['id'],
                'title'       => $plano['nome'] . ' — Robério Diógenes',
                'description' => 'Acesso completo à biblioteca por ' . $plano['duracao_dias'] . ' dias',
                'quantity'    => 1,
                'currency_id' => 'BRL',
                'unit_price'  => (float) $plano['preco'],
            ]],
            'payer' => [
                'name'  => $usuario['nome'],
                'email' => $usuario['email'],
            ],
            'back_urls' => [
                'success' => SITE_URL . '/pagamento/sucesso.html?ref=' . $refExterna,
                'failure' => SITE_URL . '/pagamento/falha.html?ref=' . $refExterna,
                'pending' => SITE_URL . '/pagamento/pendente.html?ref=' . $refExterna,
            ],
            'auto_return'        => 'approved',
            'external_reference' => $refExterna,
            'notification_url'   => SITE_URL . '/backend/pagamento.php?acao=webhook',
            'metadata'           => [
                'usuario_id' => $uid,
                'tipo'       => 'assinatura',
                'plano_id'   => $planoId,
            ],
        ]);

        // Registra assinatura como pendente
        $pdo->prepare(
            "INSERT INTO assinaturas
                (usuario_id, plano_id, status, inicio_em, expira_em, gateway, ref_externa)
             VALUES (?, ?, 'pendente', NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), 'mercadopago', ?)"
        )->execute([$uid, $planoId, $plano['duracao_dias'], $refExterna]);

        responderOk([
            'checkout_url' => $preference['init_point'],
            'ref'          => $refExterna,
        ]);
    }

    responderErro('Ação inválida.');
}

/* ──────────────────────────────────────────────────────────────
   HELPERS
   ────────────────────────────────────────────────────────────── */

/**
 * Cria uma preferência de pagamento no Mercado Pago via API REST.
 * Retorna o objeto de preferência com init_point (URL do checkout).
 */
function criarPreferenciaMP(array $dados): array {
    $url  = 'https://api.mercadopago.com/checkout/preferences';
    $json = json_encode($dados);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $json,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . MP_ACCESS_TOKEN,
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $resposta = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erro     = curl_error($ch);
    curl_close($ch);

    if ($erro) {
        error_log('[MP] Erro cURL: ' . $erro);
        responderErro('Não foi possível conectar ao gateway de pagamento. Tente novamente.', 503);
    }

    $obj = json_decode($resposta, true);

    if ($status !== 201 || empty($obj['init_point'])) {
        error_log('[MP] Resposta inesperada (' . $status . '): ' . $resposta);
        responderErro('Erro ao criar sessão de pagamento. Tente novamente.', 502);
    }

    return $obj;
}

/**
 * Processa notificações (webhooks) do Mercado Pago.
 * Chamado automaticamente pelo MP após aprovação/recusa do pagamento.
 */
function processarWebhook(): void {
    $pdo  = db();
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $tipo   = $body['type']        ?? ($_GET['type']       ?? '');
    $dataId = $body['data']['id']  ?? ($_GET['data_id']    ?? '');

    if ($tipo !== 'payment' || !$dataId) return;

    // Busca detalhes do pagamento na API do MP
    $ch = curl_init('https://api.mercadopago.com/v1/payments/' . $dataId);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . MP_ACCESS_TOKEN],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $resp   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200) return;

    $pagamento = json_decode($resp, true);
    $statusPag = $pagamento['status'] ?? '';
    $ref       = $pagamento['external_reference'] ?? '';
    $meta      = $pagamento['metadata'] ?? [];
    $tipo_tx   = $meta['tipo'] ?? '';

    // Mapeia status do MP para o nosso vocabulário
    $mapa = [
        'approved'    => 'aprovada',
        'pending'     => 'pendente',
        'in_process'  => 'pendente',
        'rejected'    => 'cancelada',
        'cancelled'   => 'cancelada',
        'refunded'    => 'reembolsada',
        'charged_back'=> 'reembolsada',
    ];
    $nossoStatus = $mapa[$statusPag] ?? 'pendente';

    /* ── Carregar mailer (silencioso se não instalado) ── */
    @include_once __DIR__ . '/mailer.php';

    /* ── Atualizar compra avulsa ── */
    if ($tipo_tx === 'compra' && $ref) {
        $pdo->prepare(
            "UPDATE compras SET status = ? WHERE ref_externa = ?"
        )->execute([$nossoStatus, $ref]);

        // E-mail de confirmação de compra aprovada
        if ($nossoStatus === 'aprovada') {
            $dadosComp = $pdo->prepare(
                "SELECT u.nome, u.email, c.livro_slug, c.preco_pago, l.titulo
                 FROM compras c
                 JOIN usuarios u ON u.id = c.usuario_id
                 LEFT JOIN livros l ON l.slug = c.livro_slug
                 WHERE c.ref_externa = ? LIMIT 1"
            );
            $dadosComp->execute([$ref]);
            $comp = $dadosComp->fetch();
            if ($comp && class_exists('Mailer')) {
                Mailer::enviarConfirmacaoCompra(
                    $comp['email'],
                    $comp['nome'],
                    $comp['titulo'] ?? $comp['livro_slug'],
                    (float) $comp['preco_pago']
                );
            }
        }
    }

    /* ── Atualizar assinatura ── */
    if ($tipo_tx === 'assinatura' && $ref) {
        if ($nossoStatus === 'aprovada') {
            $planoId = (int) ($meta['plano_id'] ?? 0);
            if ($planoId) {
                $durStmt = $pdo->prepare("SELECT duracao_dias FROM planos WHERE id = ?");
                $durStmt->execute([$planoId]);
                $dur = (int) ($durStmt->fetchColumn() ?: 30);

                $pdo->prepare(
                    "UPDATE assinaturas
                     SET status = 'ativa',
                         inicio_em  = NOW(),
                         expira_em  = DATE_ADD(NOW(), INTERVAL ? DAY)
                     WHERE ref_externa = ?"
                )->execute([$dur, $ref]);

                // E-mail de confirmação de assinatura
                $dadosAssin = $pdo->prepare(
                    "SELECT u.nome, u.email, p.nome AS plano_nome,
                            DATE_FORMAT(DATE_ADD(NOW(), INTERVAL ? DAY), '%d/%m/%Y') AS expira
                     FROM assinaturas a
                     JOIN usuarios u ON u.id = a.usuario_id
                     JOIN planos   p ON p.id = a.plano_id
                     WHERE a.ref_externa = ? LIMIT 1"
                );
                $dadosAssin->execute([$dur, $ref]);
                $assin = $dadosAssin->fetch();
                if ($assin && class_exists('Mailer')) {
                    Mailer::enviarConfirmacaoAssinatura(
                        $assin['email'],
                        $assin['nome'],
                        $assin['plano_nome'],
                        $assin['expira']
                    );
                }
            }
        } else {
            $pdo->prepare(
                "UPDATE assinaturas SET status = ? WHERE ref_externa = ?"
            )->execute([$nossoStatus, $ref]);
        }
    }
}
