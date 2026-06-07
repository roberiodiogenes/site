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
   Aplicação única "roberiodiogenes" — cobre todos os produtos:
   livros avulsos, contos, assinaturas, presentes e promoções.

   Painel: https://www.mercadopago.com.br/developers/panel/app
   ─────────────────────────────────────────────────────────── */
define('MP_PUBLIC_KEY',   'APP_USR-fbf67b6a-e0c1-49e9-a52e-d6ad8972382a');
define('MP_ACCESS_TOKEN', 'APP_USR-184457053197001-060614-f0bef50c0b779f99134bbf42cc24e77f-3452806373');

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

    /* ── Adquirir livro gratuito (sem pagamento) ── */
    if ($acao === 'adquirir_gratis') {
        $slug = trim($body['livro_slug'] ?? '');
        if (!$slug) responderErro('Livro não informado.');

        $lStmt = $pdo->prepare(
            "SELECT titulo, gratuito, promo_ate, gratuito_ate
             FROM livros WHERE slug = ? AND ativo = 1"
        );
        $lStmt->execute([$slug]);
        $livro = $lStmt->fetch();
        if (!$livro) responderErro('Livro não encontrado.', 404);

        $ehGratuito = $livro['gratuito']
            || ($livro['gratuito_ate'] && strtotime($livro['gratuito_ate']) > time());

        if (!$ehGratuito) responderErro('Este livro não está disponível gratuitamente.', 403);

        // Verifica se já tem na biblioteca
        $jaC = $pdo->prepare("SELECT id FROM compras WHERE usuario_id=? AND livro_slug=? AND status='aprovada'");
        $jaC->execute([$uid, $slug]);
        if ($jaC->fetch()) responderErro('Você já possui este livro.', 409);

        // Adiciona direto à biblioteca
        $pdo->prepare(
            "INSERT INTO compras (usuario_id, livro_slug, preco_pago, status, gateway, ref_externa)
             VALUES (?, ?, 0.00, 'aprovada', 'gratuito', ?)
             ON DUPLICATE KEY UPDATE status='aprovada', gateway='gratuito'"
        )->execute([$uid, $slug, 'gratis_' . $uid . '_' . time()]);

        responderOk([
            'mensagem'   => 'Livro adicionado à sua biblioteca!',
            'leitor_url' => SITE_URL . '/leitor/index.html',
        ]);
    }

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

        // Busca dados do livro (com suporte a promoções temporárias)
        try {
            $lStmt = $pdo->prepare(
                "SELECT titulo, preco, preco_promocao, gratuito, promo_ate, gratuito_ate
                 FROM livros WHERE slug = ? AND ativo = 1"
            );
        } catch (Throwable $e) {
            // Colunas promo ainda não existem (migration pendente)
            $lStmt = $pdo->prepare(
                "SELECT titulo, preco, preco_promocao, gratuito,
                        NULL AS promo_ate, NULL AS gratuito_ate
                 FROM livros WHERE slug = ? AND ativo = 1"
            );
        }
        $lStmt->execute([$slug]);
        $livro = $lStmt->fetch();
        if (!$livro) responderErro('Livro não encontrado.', 404);

        // Checar se está gratuito temporariamente
        $ehGratuito = $livro['gratuito']
            || ($livro['gratuito_ate'] && strtotime($livro['gratuito_ate']) > time());
        if ($ehGratuito) {
            // Redirecionar para adquirir_gratis
            $pdo->prepare(
                "INSERT INTO compras (usuario_id, livro_slug, preco_pago, status, gateway, ref_externa)
                 VALUES (?, ?, 0.00, 'aprovada', 'gratuito', ?)
                 ON DUPLICATE KEY UPDATE status='aprovada', gateway='gratuito'"
            )->execute([$uid, $slug, 'gratis_' . $uid . '_' . time()]);
            responderOk(['gratis' => true, 'leitor_url' => SITE_URL . '/leitor/index.html']);
        }

        // Calcular preço: promo ativa > preco_promocao > preco
        $promoAtiva = $livro['promo_ate'] && strtotime($livro['promo_ate']) > time()
                      && $livro['preco_promocao'] > 0;
        $preco = $promoAtiva ? (float)$livro['preco_promocao'] : (float)$livro['preco'];
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

    /* ── Iniciar carrinho (múltiplos livros) ── */
    if ($acao === 'iniciar_carrinho') {
        $slugs = array_filter(array_map(
            fn($s) => preg_replace('/[^a-z0-9_-]/', '', trim($s)),
            (array)($body['slugs'] ?? [])
        ));
        if (empty($slugs)) responderErro('Carrinho vazio.');
        if (count($slugs) > 20) responderErro('Máximo de 20 itens por compra.');

        // Verificar quais o usuário já comprou
        $ph   = implode(',', array_fill(0, count($slugs), '?'));
        $jaC  = $pdo->prepare(
            "SELECT livro_slug FROM compras
             WHERE usuario_id = ? AND status = 'aprovada' AND livro_slug IN ($ph)"
        );
        $jaC->execute(array_merge([$uid], array_values($slugs)));
        $jaComprados = $jaC->fetchAll(PDO::FETCH_COLUMN, 0);
        $novos = array_diff($slugs, $jaComprados);
        if (empty($novos)) responderErro('Você já possui todos os livros do carrinho.', 409);

        // Buscar dados dos livros
        $ph2  = implode(',', array_fill(0, count($novos), '?'));
        $lStmt= $pdo->prepare(
            "SELECT slug, titulo, preco, preco_promocao FROM livros
             WHERE slug IN ($ph2) AND ativo = 1"
        );
        $lStmt->execute(array_values($novos));
        $livros = $lStmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($livros)) responderErro('Nenhum livro válido encontrado.', 404);

        $refExterna = 'carrinho_' . $uid . '_' . time();
        $mpItens    = [];
        $totalPago  = 0;

        foreach ($livros as $lv) {
            $preco = (float)($lv['preco_promocao'] ?: $lv['preco']);
            if ($preco <= 0) continue;
            $mpItens[] = [
                'id'          => $lv['slug'],
                'title'       => $lv['titulo'],
                'description' => 'Livro digital — acesso no leitor online',
                'quantity'    => 1,
                'currency_id' => 'BRL',
                'unit_price'  => $preco,
            ];
            $totalPago += $preco;
        }
        if (empty($mpItens)) responderErro('Nenhum item com preço válido.');

        // Criar preferência MP com todos os itens
        $preference = criarPreferenciaMP([
            'items' => $mpItens,
            'payer' => [
                'name'  => $usuario['nome'],
                'email' => $usuario['email'],
            ],
            'back_urls' => [
                'success' => SITE_URL . '/pagamento/sucesso.html?ref=' . $refExterna,
                'failure' => SITE_URL . '/pagamento/falha.html?ref='   . $refExterna,
                'pending' => SITE_URL . '/pagamento/pendente.html?ref='. $refExterna,
            ],
            'auto_return'        => 'approved',
            'external_reference' => $refExterna,
            'notification_url'   => SITE_URL . '/backend/pagamento.php?acao=webhook',
            'expires'            => true,
            'expiration_date_to' => date('c', strtotime('+24 hours')),
            'metadata'           => [
                'usuario_id' => $uid,
                'tipo'       => 'carrinho',
                'slugs'      => implode(',', array_column($livros, 'slug')),
            ],
        ]);

        // Registrar cada item como compra pendente
        $insStmt = $pdo->prepare(
            "INSERT INTO compras (usuario_id, livro_slug, preco_pago, status, gateway, ref_externa)
             VALUES (?, ?, ?, 'pendente', 'mercadopago', ?)
             ON DUPLICATE KEY UPDATE status='pendente', ref_externa=VALUES(ref_externa)"
        );
        foreach ($livros as $lv) {
            $preco = (float)($lv['preco_promocao'] ?: $lv['preco']);
            $insStmt->execute([$uid, $lv['slug'], $preco, $refExterna]);
        }

        // Marcar carrinho como em checkout
        $pdo->prepare(
            "UPDATE carrinhos SET em_checkout=1, checkout_em=NOW() WHERE usuario_id=?"
        )->execute([$uid]);

        responderOk([
            'checkout_url' => $preference['init_point'],
            'ref'          => $refExterna,
            'ja_possuia'   => array_values($jaComprados),
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

    /* ── Iniciar presente (gift purchase) ── */
    if ($acao === 'iniciar_presente') {
        $slug             = trim($body['livro_slug']       ?? '');
        $emailPresenteado = trim($body['email_presenteado'] ?? '');
        $nomePresenteado  = trim($body['nome_presenteado']  ?? '');
        $dedicatoria      = trim($body['dedicatoria']       ?? '');

        if (!$slug)                       responderErro('Livro não informado.');
        if (!filter_var($emailPresenteado, FILTER_VALIDATE_EMAIL)) {
            responderErro('E-mail do presenteado inválido.');
        }

        // Busca dados do livro
        try {
            $lStmt = $pdo->prepare(
                "SELECT titulo, preco, preco_promocao, promo_ate, gratuito, gratuito_ate, capa_img
                 FROM livros WHERE slug = ? AND ativo = 1"
            );
        } catch (Throwable $e) {
            $lStmt = $pdo->prepare(
                "SELECT titulo, preco, preco_promocao, NULL AS promo_ate, gratuito,
                        NULL AS gratuito_ate, capa_img
                 FROM livros WHERE slug = ? AND ativo = 1"
            );
        }
        $lStmt->execute([$slug]);
        $livro = $lStmt->fetch();
        if (!$livro) responderErro('Livro não encontrado.', 404);

        // Preço base: usa promoção ativa se houver, senão preço normal
        $promoAtiva = $livro['promo_ate'] && strtotime($livro['promo_ate']) > time()
                      && $livro['preco_promocao'] > 0;
        $precoBase = $promoAtiva ? (float)$livro['preco_promocao'] : (float)$livro['preco'];
        if (!$precoBase || $precoBase <= 0) responderErro('Este livro não está disponível para presente.');

        // Desconto de 20% no presente (estratégia de venda)
        $preco = round($precoBase * 0.80, 2);

        // Gera token único para o presenteado resgatar
        $token      = bin2hex(random_bytes(20)); // 40 chars
        $refExterna = 'pres_' . $uid . '_' . $slug . '_' . time();

        // Salva o presente como pendente
        $pdo->prepare(
            "INSERT INTO presentes
               (comprador_id, livro_slug, email_presenteado, nome_presenteado, dedicatoria,
                preco_pago, token_acesso, status, ref_externa, gateway)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente', ?, 'mercadopago')"
        )->execute([
            $uid, $slug, $emailPresenteado, $nomePresenteado ?: null,
            $dedicatoria ?: null, $preco, $token, $refExterna,
        ]);

        // Cria preferência MP
        $nomePresent = $nomePresenteado ? " para {$nomePresenteado}" : '';
        $descontoFmt = 'R$ ' . number_format($preco, 2, ',', '.');
        $preference = criarPreferenciaMP([
            'items' => [[
                'id'          => $slug,
                'title'       => "Presente{$nomePresent}: {$livro['titulo']} (20% off)",
                'description' => "Livro digital com 20% de desconto — presenteado recebe por e-mail.",
                'quantity'    => 1,
                'currency_id' => 'BRL',
                'unit_price'  => $preco, // já com 20% de desconto
            ]],
            'payer' => [
                'name'  => $usuario['nome'],
                'email' => $usuario['email'],
            ],
            'back_urls' => [
                'success' => SITE_URL . '/pagamento/sucesso.html?ref=' . $refExterna . '&tipo=presente',
                'failure' => SITE_URL . '/pagamento/falha.html?ref=' . $refExterna,
                'pending' => SITE_URL . '/pagamento/pendente.html?ref=' . $refExterna,
            ],
            'auto_return'        => 'approved',
            'external_reference' => $refExterna,
            'notification_url'   => SITE_URL . '/backend/pagamento.php?acao=webhook',
            'expires'            => true,
            'expiration_date_to' => date('c', strtotime('+24 hours')),
            'metadata'           => [
                'usuario_id'       => $uid,
                'tipo'             => 'presente',
                'livro_slug'       => $slug,
                'email_presenteado' => $emailPresenteado,
                'token'            => $token,
            ],
        ]);

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
    $url = 'https://api.mercadopago.com/checkout/preferences';

    // ── Ajustes para ambiente local ──────────────────────────────
    // 1. notification_url com localhost é rejeitada pelo MP (não acessível externamente)
    // 2. SSL_VERIFYPEER false porque o XAMPP frequentemente não tem bundle CA atualizado
    if (AMBIENTE === 'local') {
        unset($dados['notification_url']);
        unset($dados['expires']);
        unset($dados['expiration_date_to']);
    }

    $json = json_encode($dados, JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $json,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . MP_ACCESS_TOKEN,
        ],
        CURLOPT_TIMEOUT        => 20,
        // Em produção sempre verificar SSL. Em local, XAMPP muitas vezes
        // não tem o bundle CA correto e a requisição falha por isso.
        CURLOPT_SSL_VERIFYPEER => AMBIENTE === 'producao',
        CURLOPT_SSL_VERIFYHOST => AMBIENTE === 'producao' ? 2 : 0,
    ]);

    $resposta = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erro     = curl_error($ch);
    curl_close($ch);

    if ($erro) {
        error_log('[MP] Erro cURL: ' . $erro);
        $msg = AMBIENTE === 'local'
            ? "Erro de conexão com MP: {$erro}"
            : 'Não foi possível conectar ao gateway de pagamento. Tente novamente.';
        responderErro($msg, 503);
    }

    $obj     = json_decode($resposta, true) ?? [];
    $mpErro  = $obj['message'] ?? ($obj['error'] ?? '');
    $mpCausa = isset($obj['cause']) ? ' — ' . json_encode($obj['cause'], JSON_UNESCAPED_UNICODE) : '';

    if ($status !== 201 || empty($obj['init_point'])) {
        error_log("[MP] HTTP {$status}: {$mpErro}{$mpCausa} | Payload: " . substr($json, 0, 500));

        // Em local: mostrar o erro real do MP para diagnóstico mais fácil
        $msg = AMBIENTE === 'local'
            ? "MP retornou HTTP {$status}: {$mpErro}{$mpCausa}"
            : 'Erro ao criar sessão de pagamento. Tente novamente.';
        responderErro($msg, 502);
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

    /* ── Atualizar presente ── */
    if ($tipo_tx === 'presente' && $ref) {
        if ($nossoStatus === 'aprovada') {
            // Marca presente como aprovado
            $pdo->prepare("UPDATE presentes SET status='aprovado' WHERE ref_externa=?")
                ->execute([$ref]);

            // Busca dados para o e-mail
            $stPres = $pdo->prepare(
                "SELECT p.email_presenteado, p.nome_presenteado, p.dedicatoria,
                        p.token_acesso, l.titulo, l.capa_img,
                        u.nome AS comprador_nome
                 FROM presentes p
                 JOIN livros   l ON l.slug = p.livro_slug
                 JOIN usuarios u ON u.id   = p.comprador_id
                 WHERE p.ref_externa = ? LIMIT 1"
            );
            $stPres->execute([$ref]);
            $pres = $stPres->fetch();

            if ($pres && class_exists('Mailer')) {
                $nomeDestinatario = $pres['nome_presenteado'] ?: 'você';
                $linkResgate      = SITE_URL . '/presente.html?token=' . $pres['token_acesso'];
                $dedicatoriaHtml  = $pres['dedicatoria']
                    ? '<blockquote style="border-left:3px solid #B8860B;padding:.5rem 1rem;margin:1rem 0;
                                         color:#666;font-style:italic">' . nl2br(htmlspecialchars($pres['dedicatoria'])) . '</blockquote>'
                    : '';

                Mailer::enviar([
                    'para_email' => $pres['email_presenteado'],
                    'para_nome'  => $pres['nome_presenteado'] ?: 'Leitor',
                    'assunto'    => "{$pres['comprador_nome']} te enviou um presente: {$pres['titulo']}",
                    'html'       => "
                        <p>Olá, <strong>{$nomeDestinatario}</strong>! 🎁</p>
                        <p><strong>{$pres['comprador_nome']}</strong> te enviou o livro
                           <em>{$pres['titulo']}</em> como presente.</p>
                        {$dedicatoriaHtml}
                        <p>Clique no botão abaixo para acessar o seu presente:</p>
                        <p style='text-align:center;margin:2rem 0'>
                          <a href='{$linkResgate}' class='btn-email'>Resgatar meu presente</a>
                        </p>
                        <p style='font-size:.8em;color:#888'>O link é pessoal e intransferível.
                           Crie uma conta gratuita ou faça login para ativar o livro na sua biblioteca.</p>
                    ",
                    'texto' => "Olá {$nomeDestinatario}!\n\n{$pres['comprador_nome']} te enviou {$pres['titulo']} como presente.\n\nResgate em: {$linkResgate}",
                ]);
            }
        } else {
            $pdo->prepare("UPDATE presentes SET status=? WHERE ref_externa=?")
                ->execute([$nossoStatus, $ref]);
        }
        return;
    }

    /* ── Atualizar compra avulsa OU carrinho ── */
    if (($tipo_tx === 'compra' || $tipo_tx === 'carrinho') && $ref) {
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
