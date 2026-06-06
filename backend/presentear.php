<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/presentear.php
   Gerencia o fluxo de "presentear" um livro.

   POST { acao:'iniciar', slug, nome_presenteado, email_presenteado,
          whatsapp_presenteado, dedicatoria }
        → Cria preferência MP e salva o presente no BD

   GET  ?acao=status&ref=REF
        → Verifica status do pagamento e, se aprovado, gera o PDF

   O webhook em pagamento.php detecta tipo='presente' e chama
   Presentear::processarPagamento($ref, $pdo)
   ================================================================ */

require_once __DIR__ . '/config.php';
iniciarSessao();

if (empty($_SESSION['usuario_id'])) {
    responderErro('Você precisa estar logado para presentear.', 401);
}

$uid    = (int) $_SESSION['usuario_id'];
$pdo    = db();
$metodo = $_SERVER['REQUEST_METHOD'];

/* ── POST: iniciar presente ───────────────────────────────────── */
if ($metodo === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $acao = trim($body['acao'] ?? '');

    /* ── Enviar presente gratuito (sem pagamento) ────────────────── */
    if ($acao === 'enviar_gratis') {
        $slug       = preg_replace('/[^a-z0-9_-]/', '', trim($body['slug'] ?? ''));
        $nomePresPh = substr(strip_tags(trim($body['nome_presenteado']   ?? '')), 0, 120);
        $emailPres  = strtolower(trim($body['email_presenteado']         ?? ''));
        $wpPres     = preg_replace('/[^0-9+()\- ]/', '', trim($body['whatsapp_presenteado'] ?? ''));
        $dedicatoria= substr(strip_tags(trim($body['dedicatoria']        ?? '')), 0, 600);

        if (!$slug)      responderErro('Livro não informado.');
        if (!$nomePresPh) responderErro('Nome do presenteado obrigatório.');
        if (!$emailPres || !filter_var($emailPres, FILTER_VALIDATE_EMAIL)) {
            responderErro('E-mail do presenteado inválido.');
        }

        // Verificar se o livro é realmente gratuito
        $stL = $pdo->prepare(
            "SELECT titulo, capa_img, COALESCE(gratuito,0) AS gratuito
             FROM livros WHERE slug=? AND ativo=1 LIMIT 1"
        );
        $stL->execute([$slug]);
        $livro = $stL->fetch(PDO::FETCH_ASSOC);

        if (!$livro) responderErro('Livro não encontrado.', 404);
        if (!$livro['gratuito']) responderErro('Este item não é gratuito.', 403);

        $token = bin2hex(random_bytes(24));
        $ref   = 'gratis_' . $uid . '_' . $slug . '_' . time();

        // Salvar presente já aprovado
        $pdo->prepare(
            "INSERT INTO presentes
                (comprador_id, livro_slug, nome_presenteado, email_presenteado,
                 whatsapp_presenteado, dedicatoria, preco_pago, status, ref_externa, token_acesso, aprovado_em)
             VALUES (?,?,?,?,?,?,0,'aprovado',?,?,NOW())"
        )->execute([$uid, $slug, $nomePresPh, $emailPres, $wpPres ?: null, $dedicatoria, $ref, $token]);

        $presenteId = (int)$pdo->lastInsertId();

        // Buscar comprador
        $stU = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id=? LIMIT 1");
        $stU->execute([$uid]);
        $comprador = $stU->fetch(PDO::FETCH_ASSOC);

        // Montar dados para envio
        $dadosPresente = [
            'ref_externa'        => $ref,
            'livro_slug'         => $slug,
            'titulo'             => $livro['titulo'],
            'capa_img'           => $livro['capa_img'],
            'nome_presenteado'   => $nomePresPh,
            'email_presenteado'  => $emailPres,
            'whatsapp_presenteado'=> $wpPres,
            'dedicatoria'        => $dedicatoria,
            'comprador_nome'     => $comprador['nome'],
            'comprador_email'    => $comprador['email'],
            'token_acesso'       => $token,
        ];

        // Enviar voucher
        Presentear::enviarVoucherPublico($dadosPresente, $pdo);

        responderOk(['mensagem' => 'Presente enviado com sucesso!']);
    }

    if ($acao !== 'iniciar') responderErro('Ação inválida.');

    $slug       = preg_replace('/[^a-z0-9_-]/', '', trim($body['slug'] ?? ''));
    $nomePresPh = substr(strip_tags(trim($body['nome_presenteado']   ?? '')), 0, 120);
    $emailPres  = strtolower(trim($body['email_presenteado']         ?? ''));
    $wpPres     = preg_replace('/[^0-9+()\- ]/', '', trim($body['whatsapp_presenteado'] ?? ''));
    $dedicatoria= substr(strip_tags(trim($body['dedicatoria']         ?? '')), 0, 600);

    // Validações
    if (!$slug)      responderErro('Livro não informado.');
    if (!$nomePresPh) responderErro('Nome do presenteado obrigatório.');
    if (!$emailPres || !filter_var($emailPres, FILTER_VALIDATE_EMAIL)) {
        responderErro('E-mail do presenteado inválido.');
    }

    // Buscar livro
    $stL = $pdo->prepare("SELECT titulo, preco, preco_promocao, capa_img FROM livros WHERE slug=? AND ativo=1 LIMIT 1");
    $stL->execute([$slug]);
    $livro = $stL->fetch(PDO::FETCH_ASSOC);
    if (!$livro) responderErro('Livro não encontrado.', 404);

    $preco = (float)($livro['preco_promocao'] ?: $livro['preco']);
    if ($preco <= 0) responderErro('Este livro não pode ser presenteado (sem preço).');

    // Buscar dados do comprador
    $stU = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id=? LIMIT 1");
    $stU->execute([$uid]);
    $comprador = $stU->fetch(PDO::FETCH_ASSOC);

    $refExterna = 'presente_' . $uid . '_' . $slug . '_' . time();

    // Criar preferência Mercado Pago
    require_once __DIR__ . '/pagamento.php'; // reutilizar criarPreferenciaMP
    $preference = criarPreferenciaMP([
        'items' => [[
            'id'          => 'presente_' . $slug,
            'title'       => 'Presente: ' . $livro['titulo'],
            'description' => 'Voucher digital de presente — inclui dedicatória e acesso ao leitor online',
            'quantity'    => 1,
            'currency_id' => 'BRL',
            'unit_price'  => $preco,
        ]],
        'payer' => [
            'name'  => $comprador['nome'],
            'email' => $comprador['email'],
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
            'usuario_id'  => $uid,
            'tipo'        => 'presente',
            'livro_slug'  => $slug,
        ],
    ]);

    // Salvar presente no BD
    $pdo->prepare(
        "INSERT INTO presentes
            (comprador_id, livro_slug, nome_presenteado, email_presenteado,
             whatsapp_presenteado, dedicatoria, preco_pago, status, ref_externa)
         VALUES (?,?,?,?,?,?,?,'pendente',?)"
    )->execute([$uid, $slug, $nomePresPh, $emailPres, $wpPres ?: null, $dedicatoria, $preco, $refExterna]);

    responderOk([
        'checkout_url' => $preference['init_point'],
        'ref'          => $refExterna,
    ]);
}

responderErro('Método não permitido.', 405);

/* ================================================================
   Classe estática para processar pagamento aprovado
   (chamada pelo webhook em pagamento.php)
   ================================================================ */
class Presentear {

    /** Wrapper público para enviar voucher (usado pelo fluxo gratuito) */
    public static function enviarVoucherPublico(array $dadosPresente, PDO $pdo): void {
        self::enviarVoucher($dadosPresente, $pdo);
    }

    public static function processarPagamento(string $ref, PDO $pdo): void {
        $st = $pdo->prepare(
            "SELECT p.*, l.titulo, l.capa_img, u.nome AS comprador_nome, u.email AS comprador_email
             FROM presentes p
             JOIN livros l ON l.slug = p.livro_slug
             JOIN usuarios u ON u.id = p.comprador_id
             WHERE p.ref_externa = ? AND p.status = 'pendente'
             LIMIT 1"
        );
        $st->execute([$ref]);
        $presente = $st->fetch(PDO::FETCH_ASSOC);

        if (!$presente) return;

        // Marcar como aprovado
        $pdo->prepare(
            "UPDATE presentes SET status='aprovado', aprovado_em=NOW() WHERE ref_externa=?"
        )->execute([$ref]);

        // Gerar token de acesso único para o presenteado
        $token = bin2hex(random_bytes(24));
        $pdo->prepare(
            "UPDATE presentes SET token_acesso=? WHERE ref_externa=?"
        )->execute([$token, $ref]);
        $presente['token_acesso'] = $token;

        // Registrar compra para o presenteado (pelo e-mail)
        // Verificar se o presenteado tem conta
        $stPres = $pdo->prepare("SELECT id FROM usuarios WHERE email=? LIMIT 1");
        $stPres->execute([$presente['email_presenteado']]);
        $presUid = $stPres->fetchColumn();

        if ($presUid) {
            // Já tem conta → liberar acesso ao livro
            $pdo->prepare(
                "INSERT IGNORE INTO compras (usuario_id, livro_slug, preco_pago, status, gateway, ref_externa)
                 VALUES (?,?,?,'aprovada','presente',?)"
            )->execute([$presUid, $presente['livro_slug'], 0.00, $ref . '_gift']);
        }

        // Gerar e enviar voucher PDF por e-mail
        self::enviarVoucher($presente, $pdo);
    }

    private static function enviarVoucher(array $p, PDO $pdo): void {
        @include_once __DIR__ . '/mailer.php';

        // Gerar HTML do voucher
        $htmlVoucher = self::gerarHtmlVoucher($p);
        $pdfPath     = self::gerarPdf($p, $htmlVoucher);

        $assunto = '🎁 Você ganhou um livro de ' . $p['comprador_nome'] . '!';
        $corpo   = self::gerarEmailPresenteado($p);

        if (class_exists('Mailer')) {
            try {
                Mailer::enviarComAnexo(
                    $p['email_presenteado'],
                    $p['nome_presenteado'],
                    $assunto,
                    $corpo,
                    $pdfPath,
                    'Seu-Presente-' . date('Y-m-d') . '.pdf'
                );
            } catch (\Throwable $e) {
                error_log('[Presentear] Erro PHPMailer: ' . $e->getMessage());
                self::enviarMailNativo($p['email_presenteado'], $p['nome_presenteado'], $assunto, $corpo, $pdfPath);
            }
        } else {
            self::enviarMailNativo($p['email_presenteado'], $p['nome_presenteado'], $assunto, $corpo, $pdfPath);
        }

        // Enviar cópia ao comprador
        $corpoComprador = self::gerarEmailComprador($p);
        if (class_exists('Mailer')) {
            try {
                Mailer::enviar($p['comprador_email'], $p['comprador_nome'],
                    'Seu presente para ' . $p['nome_presenteado'] . ' foi enviado! 🎁',
                    $corpoComprador);
            } catch (\Throwable $e) {}
        }

        // Enviar via WhatsApp (link do voucher) se tiver número
        if (!empty($p['whatsapp_presenteado'])) {
            $num = preg_replace('/[^0-9]/', '', $p['whatsapp_presenteado']);
            $linkAcesso = SITE_URL . '/presente.html?token=' . $p['token_acesso'];
            $msg = urlencode(
                "🎁 Você recebeu um presente de " . $p['comprador_nome'] . "!\n" .
                "Livro: " . $p['titulo'] . "\n" .
                "Acesse seu presente aqui: " . $linkAcesso
            );
            error_log('[Presentear] WhatsApp link: https://wa.me/' . $num . '?text=' . $msg);
        }

        // Marcar como voucher enviado
        $pdo->prepare("UPDATE presentes SET voucher_enviado=1, voucher_enviado_em=NOW() WHERE ref_externa=?")
            ->execute([$p['ref_externa']]);
    }

    /** Gera o HTML lindo do voucher (usado como corpo de e-mail e base para PDF) */
    public static function gerarHtmlVoucher(array $p): string {
        $primeiroNome = explode(' ', $p['nome_presenteado'])[0];
        $compradorPrime = explode(' ', $p['comprador_nome'])[0];
        $linkLeitor  = SITE_URL . '/leitor/index.html?livro=' . $p['livro_slug'];
        $linkVoucher = SITE_URL . '/presente.html?token=' . ($p['token_acesso'] ?? '');
        $data        = date('d/m/Y');
        $dedicatoria = $p['dedicatoria'] ? htmlspecialchars($p['dedicatoria']) : '';
        $capaUrl     = $p['capa_img'] ? SITE_URL . '/' . $p['capa_img'] : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
  @import url('https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,400;0,600;1,400&family=Cinzel:wght@400;600&display=swap');
  body { margin:0; padding:0; background:#F5F0E8; font-family:Georgia,serif; }
  .voucher {
    max-width:600px; margin:0 auto;
    background:linear-gradient(145deg,#FAF7F2 0%,#F2E8D4 100%);
    border:1px solid #c9b87a;
    border-radius:16px; overflow:hidden;
    box-shadow:0 8px 40px rgba(44,36,24,.18);
  }
  .vou-topo {
    background:#1A0F00; padding:28px 32px;
    text-align:center; position:relative;
  }
  .vou-topo::after {
    content:'';
    position:absolute; bottom:0; left:0; right:0; height:3px;
    background:linear-gradient(to right,#8B6508,#D4A843,#B8860B);
  }
  .vou-logo {
    font-family:'Cinzel',serif; font-size:18px; color:#D4A843;
    letter-spacing:3px; margin-bottom:4px;
  }
  .vou-sub { font-size:11px; color:#786858; letter-spacing:2px; text-transform:uppercase; }
  .vou-corpo { padding:36px 40px; }
  .vou-orn { font-family:'Cormorant',serif; font-size:48px; color:#B8860B; opacity:.2; text-align:center; line-height:1; margin-bottom:-8px; }
  .vou-titulo { font-family:'Cinzel',serif; font-size:13px; letter-spacing:3px; text-transform:uppercase; color:#B8860B; text-align:center; margin-bottom:28px; }
  .vou-livro { display:flex; gap:24px; align-items:flex-start; background:rgba(184,134,11,.05); border:1px solid rgba(184,134,11,.18); border-radius:12px; padding:20px; margin-bottom:28px; }
  .vou-capa { width:80px; flex-shrink:0; border-radius:6px; box-shadow:0 4px 16px rgba(44,36,24,.25); }
  .vou-livro-info h2 { font-family:'Cormorant',serif; font-size:22px; font-weight:400; color:#2C2418; margin-bottom:6px; }
  .vou-livro-info p { font-size:13px; color:#8C7D65; margin:0; }
  .vou-ded {
    background:#FAF7F2; border-left:3px solid #B8860B;
    padding:16px 20px; border-radius:0 8px 8px 0;
    margin-bottom:28px;
  }
  .vou-ded-label { font-family:'Cinzel',serif; font-size:10px; letter-spacing:2px; text-transform:uppercase; color:#B8860B; margin-bottom:8px; }
  .vou-ded-texto { font-family:'Cormorant',serif; font-style:italic; font-size:17px; color:#3B2D1F; line-height:1.75; }
  .vou-ded-de { font-size:13px; color:#8C7D65; margin-top:10px; font-family:'Cinzel',serif; letter-spacing:1px; }
  .vou-links { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:28px; }
  .vou-btn {
    flex:1; min-width:180px; padding:14px 20px;
    background:#B8860B; color:#1A0F00; text-decoration:none;
    border-radius:8px; font-family:'Cinzel',serif;
    font-size:12px; font-weight:600; letter-spacing:1.5px;
    text-transform:uppercase; text-align:center;
    display:block;
  }
  .vou-btn-sec { background:#2C2418; color:#D4A843; }
  .vou-rodape { border-top:1px solid rgba(184,134,11,.2); padding-top:20px; text-align:center; }
  .vou-rodape p { font-size:12px; color:#8C7D65; line-height:1.6; }
  .vou-data { font-family:'Cinzel',serif; font-size:10px; letter-spacing:2px; text-transform:uppercase; color:#B8860B; margin-bottom:8px; }
</style>
</head>
<body>
<div class="voucher">
  <div class="vou-topo">
    <div class="vou-logo">Robério Diógenes</div>
    <div class="vou-sub">Escritor Independente</div>
  </div>
  <div class="vou-corpo">
    <div class="vou-orn">❧</div>
    <div class="vou-titulo">Voucher de Presente</div>
    <div class="vou-livro">
      {$capaUrl ? '<img src="'.$capaUrl.'" alt="Capa" class="vou-capa">' : ''}
      <div class="vou-livro-info">
        <h2>{$p['titulo']}</h2>
        <p>Robério Diógenes</p>
        <p style="margin-top:8px;color:#B8860B;font-size:12px">Acesso no leitor online · Formato ePub</p>
      </div>
    </div>
    {$dedicatoria ? '
    <div class="vou-ded">
      <div class="vou-ded-label">Dedicatória</div>
      <div class="vou-ded-texto">'.$dedicatoria.'</div>
      <div class="vou-ded-de">— '.$compradorPrime.', com carinho</div>
    </div>' : ''}
    <div class="vou-links">
      <a href="{$linkLeitor}" class="vou-btn">
        📖 Ler agora online
      </a>
      <a href="{$linkVoucher}" class="vou-btn vou-btn-sec">
        📥 Acessar meu presente
      </a>
    </div>
    <div class="vou-rodape">
      <div class="vou-data">{$data}</div>
      <p>Este presente foi enviado por <strong>{$p['comprador_nome']}</strong>.<br>
      Dúvidas? Acesse <a href="https://roberiodiogenes.com/contato.html" style="color:#B8860B">roberiodiogenes.com</a></p>
    </div>
  </div>
</div>
</body>
</html>
HTML;
    }

    /** Gera PDF do voucher usando FPDF se disponível, ou salva o HTML */
    private static function gerarPdf(array $p, string $html): string {
        $dir  = sys_get_temp_dir();
        $file = $dir . '/voucher_' . $p['ref_externa'] . '.html';
        file_put_contents($file, $html);

        // Se FPDF estiver disponível, converter para PDF real
        $fpdfPath = __DIR__ . '/lib/fpdf/fpdf.php';
        if (file_exists($fpdfPath)) {
            require_once $fpdfPath;
            $pdfFile = $dir . '/voucher_' . $p['ref_externa'] . '.pdf';
            // FPDF: layout simples elegante
            $pdf = new FPDF('P', 'mm', 'A5');
            $pdf->AddPage();
            $pdf->SetFillColor(26, 15, 0);       // fundo topo
            $pdf->Rect(0, 0, 148, 30, 'F');       // header
            $pdf->SetFont('Helvetica', 'B', 14);
            $pdf->SetTextColor(212, 168, 67);
            $pdf->SetXY(0, 10);
            $pdf->Cell(148, 8, 'Roberio Diogenes', 0, 1, 'C');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetTextColor(120, 104, 88);
            $pdf->Cell(148, 5, 'Escritor Independente', 0, 1, 'C');
            // Linha dourada
            $pdf->SetDrawColor(184, 134, 11);
            $pdf->SetLineWidth(0.8);
            $pdf->Line(0, 30, 148, 30);
            // Título
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetTextColor(184, 134, 11);
            $pdf->SetXY(10, 36);
            $pdf->Cell(128, 8, 'VOUCHER DE PRESENTE', 0, 1, 'C');
            // Livro
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->SetTextColor(44, 36, 24);
            $pdf->SetXY(10, 48);
            $pdf->MultiCell(128, 7, iconv('UTF-8','windows-1252',$p['titulo']), 0, 'C');
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(140, 125, 101);
            $pdf->Cell(128, 5, 'Roberio Diogenes - ePub + Leitor Online', 0, 1, 'C');
            // Dedicatória
            if ($p['dedicatoria']) {
                $pdf->SetXY(10, 72);
                $pdf->SetFont('Helvetica', 'I', 9);
                $pdf->SetTextColor(59, 45, 31);
                $pdf->MultiCell(128, 6, '"' . iconv('UTF-8','windows-1252',$p['dedicatoria']) . '"', 0, 'C');
            }
            // Link
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetTextColor(184, 134, 11);
            $pdf->SetXY(10, 160);
            $linkVoucher = SITE_URL . '/presente.html?token=' . ($p['token_acesso'] ?? '');
            $pdf->Cell(128, 6, 'Acesse: ' . $linkVoucher, 0, 1, 'C');
            // Data
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetTextColor(140, 125, 101);
            $pdf->Cell(128, 5, date('d/m/Y'), 0, 1, 'C');
            $pdf->Output('F', $pdfFile);
            return $pdfFile;
        }

        // Sem FPDF: retornar o arquivo HTML (será enviado como .html)
        return $file;
    }

    private static function gerarEmailPresenteado(array $p): string {
        $linkVoucher = SITE_URL . '/presente.html?token=' . ($p['token_acesso'] ?? '');
        $linkLeitor  = SITE_URL . '/leitor/index.html?livro=' . $p['livro_slug'];
        $primeiro    = explode(' ', $p['nome_presenteado'])[0];
        $ded = $p['dedicatoria'] ? '<div style="font-family:Georgia,serif;font-style:italic;font-size:16px;color:#3B2D1F;border-left:3px solid #B8860B;padding:12px 20px;margin:20px 0;background:#FAF7F2">"'.htmlspecialchars($p['dedicatoria']).'"<br><small style="color:#8C7D65">— '.htmlspecialchars(explode(' ',$p['comprador_nome'])[0]).', com carinho</small></div>' : '';
        return <<<HTML
<!DOCTYPE html><html lang="pt-BR"><body style="font-family:Georgia,serif;background:#F5F0E8;margin:0;padding:16px">
<div style="max-width:560px;margin:0 auto;background:#FAF7F2;border:1px solid #c9b87a;border-radius:12px;overflow:hidden">
  <div style="background:#1A0F00;padding:24px 32px;text-align:center">
    <div style="font-size:16px;color:#D4A843;letter-spacing:3px;font-family:Georgia,serif">Robério Diógenes</div>
  </div>
  <div style="padding:32px">
    <p style="font-size:22px;color:#2C2418;margin-bottom:8px">Olá, {$primeiro}! 🎁</p>
    <p style="color:#5C4F3A;font-size:15px;line-height:1.7"><strong>{$p['comprador_nome']}</strong> te presenteou com um livro especial:</p>
    <div style="background:rgba(184,134,11,.08);border:1px solid rgba(184,134,11,.2);border-radius:10px;padding:20px;text-align:center;margin:20px 0">
      <div style="font-size:20px;color:#2C2418;font-weight:bold">{$p['titulo']}</div>
      <div style="font-size:13px;color:#8C7D65;margin-top:4px">Robério Diógenes · ePub</div>
    </div>
    {$ded}
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin:24px 0">
      <a href="{$linkLeitor}" style="flex:1;min-width:150px;background:#B8860B;color:#1A0F00;padding:14px;border-radius:8px;font-weight:bold;font-size:14px;text-decoration:none;text-align:center;display:block">📖 Ler agora</a>
      <a href="{$linkVoucher}" style="flex:1;min-width:150px;background:#2C2418;color:#D4A843;padding:14px;border-radius:8px;font-weight:bold;font-size:14px;text-decoration:none;text-align:center;display:block">🎁 Ver meu presente</a>
    </div>
    <p style="font-size:13px;color:#8C7D65;line-height:1.6">O voucher em PDF está em anexo. Você pode acessar o livro a qualquer momento usando o link acima.<br><br>Boa leitura! ✨</p>
  </div>
</div>
</body></html>
HTML;
    }

    private static function gerarEmailComprador(array $p): string {
        return <<<HTML
<!DOCTYPE html><html><body style="font-family:Georgia,serif;background:#F5F0E8;padding:16px">
<div style="max-width:520px;margin:0 auto;background:#FAF7F2;border:1px solid #c9b87a;border-radius:12px;overflow:hidden">
  <div style="background:#1A0F00;padding:20px;text-align:center">
    <div style="color:#D4A843;font-size:15px;letter-spacing:2px">Robério Diógenes</div>
  </div>
  <div style="padding:28px">
    <p style="font-size:20px;color:#2C2418">Presente enviado com sucesso! 🎉</p>
    <p style="color:#5C4F3A;font-size:15px;line-height:1.7">
      <strong>{$p['nome_presenteado']}</strong> ({$p['email_presenteado']}) recebeu o livro <strong>{$p['titulo']}</strong> por e-mail.
    </p>
    <p style="font-size:13px;color:#8C7D65;margin-top:16px">Obrigado por presentear com literatúra! ❤️</p>
  </div>
</div>
</body></html>
HTML;
    }

    private static function enviarMailNativo(string $para, string $nome, string $assunto, string $html, string $anexo = ''): void {
        $boundary = md5(time());
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "From: Robério Diógenes <contato@roberiodiogenes.com>\r\n";

        if ($anexo && file_exists($anexo)) {
            $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
            $body = "--{$boundary}\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n{$html}\r\n";
            $body .= "--{$boundary}\r\n";
            $ext  = pathinfo($anexo, PATHINFO_EXTENSION);
            $mime = ($ext === 'pdf') ? 'application/pdf' : 'text/html';
            $body .= "Content-Type: {$mime}; name=\"Voucher.{$ext}\"\r\n";
            $body .= "Content-Disposition: attachment; filename=\"Voucher.{$ext}\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode(file_get_contents($anexo))) . "\r\n";
            $body .= "--{$boundary}--";
        } else {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body = $html;
        }

        @mail($para, $assunto, $body, $headers);
    }
}
