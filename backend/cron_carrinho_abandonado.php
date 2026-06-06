<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/cron_carrinho_abandonado.php
   Detecta carrinhos abandonados e envia e-mail de lembrete.

   Critério:
     · Carrinho não-vazio, não em checkout ativo
     · Última atualização há mais de 1h (fase 1) ou 24h (fase 2)
     · E-mail de lembrete ainda não enviado nesta fase

   Execução recomendada (cPanel / cron):
     0 * * * *  php /caminho/backend/cron_carrinho_abandonado.php

   Pode também ser invocado via URL com token (para testes):
     GET /backend/cron_carrinho_abandonado.php?token=SEU_TOKEN_CRON
   ================================================================ */

/* ── Proteção de acesso ──────────────────────────────────────── */
define('TOKEN_CRON', 'RD_CRON_2025_SEGURO'); // ← troque em produção
$viaCLI  = (php_sapi_name() === 'cli');
$viaHTTP = !$viaCLI;

if ($viaHTTP) {
    $token = $_GET['token'] ?? '';
    if (!hash_equals(TOKEN_CRON, $token)) {
        http_response_code(403);
        die('Acesso negado.');
    }
}

require_once __DIR__ . '/config.php';
@include_once __DIR__ . '/mailer.php';

$pdo = db();
$enviados = 0;
$erros    = 0;

/* ── Buscar carrinhos abandonados (não-vazio, >1h sem atualização) */
$stmt = $pdo->prepare("
    SELECT c.usuario_id,
           c.itens,
           c.atualizado_em,
           c.lembrete_env,
           u.nome,
           u.email
    FROM   carrinhos c
    JOIN   usuarios  u ON u.id = c.usuario_id
    WHERE  c.em_checkout = 0
      AND  c.lembrete_env = 0
      AND  JSON_LENGTH(c.itens) > 0
      AND  c.atualizado_em < DATE_SUB(NOW(), INTERVAL 1 HOUR)
      AND  u.ativo = 1
    ORDER BY c.atualizado_em ASC
    LIMIT 100
");
$stmt->execute();
$carrinhos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($carrinhos as $row) {
    $itens = json_decode($row['itens'], true) ?? [];
    if (empty($itens)) continue;

    $total = array_sum(array_column($itens, 'preco'));
    $totalFmt = 'R$ ' . number_format($total, 2, ',', '.');

    $listaTitulos = array_map(fn($i) => $i['titulo'] ?? '—', $itens);

    $enviado = enviarLembreteCarrinho(
        $row['email'],
        $row['nome'],
        $listaTitulos,
        $totalFmt
    );

    if ($enviado) {
        $pdo->prepare(
            "UPDATE carrinhos
             SET lembrete_env=1, lembrete_em=NOW()
             WHERE usuario_id=?"
        )->execute([$row['usuario_id']]);
        $enviados++;
        log_cron("Lembrete enviado → {$row['email']} ({$row['nome']}) — {$totalFmt}");
    } else {
        $erros++;
        log_cron("FALHA ao enviar → {$row['email']}");
    }
}

log_cron("Concluído. Enviados: {$enviados} | Erros: {$erros}");

/* ================================================================
   FUNÇÕES AUXILIARES
   ================================================================ */

function enviarLembreteCarrinho(
    string $email,
    string $nome,
    array  $titulos,
    string $totalFmt
): bool {
    if (!class_exists('Mailer')) {
        // Fallback: mail() nativo
        $assunto   = 'Você deixou algo para trás — Robério Diógenes';
        $primeiroNome = explode(' ', $nome)[0];
        $listaHtml = implode('', array_map(
            fn($t) => "<li style='margin-bottom:6px'>📖 {$t}</li>",
            $titulos
        ));

        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<body style="font-family:Georgia,serif;background:#F5F0E8;margin:0;padding:0">
<div style="max-width:560px;margin:0 auto;background:#FAF7F2;border:1px solid #d4c89a;border-radius:12px;overflow:hidden;margin-top:24px">
  <div style="background:#1A0F00;padding:28px 32px;text-align:center">
    <p style="font-family:'Cinzel',serif;color:#B8860B;letter-spacing:3px;font-size:11px;text-transform:uppercase;margin:0 0 8px">Robério Diógenes</p>
    <p style="color:#D4C5A0;font-size:13px;margin:0">Escritor Independente</p>
  </div>
  <div style="padding:32px">
    <p style="font-family:Georgia,serif;font-size:22px;color:#2C2418;margin-bottom:8px">Olá, {$primeiroNome}.</p>
    <p style="color:#5C4F3A;font-size:15px;line-height:1.7;margin-bottom:16px">
      Você adicionou ao carrinho e ainda não finalizou a compra. Suas histórias estão te esperando:
    </p>
    <ul style="background:#EDE6D6;border-radius:8px;padding:16px 20px 16px 36px;color:#2C2418;font-size:14px;line-height:1.8;margin-bottom:20px">
      {$listaHtml}
    </ul>
    <p style="color:#8C7D65;font-size:14px;margin-bottom:24px">
      Total do pedido: <strong style="color:#B8860B">{$totalFmt}</strong>
    </p>
    <div style="text-align:center">
      <a href="https://www.roberiodiogenes.com/carrinho.html"
         style="display:inline-block;background:#B8860B;color:#1A0F00;padding:14px 32px;
                border-radius:8px;font-family:Georgia,serif;font-weight:bold;
                font-size:14px;text-decoration:none;letter-spacing:1px">
        Finalizar minha compra →
      </a>
    </div>
    <p style="color:#8C7D65;font-size:12px;margin-top:28px;text-align:center;line-height:1.6">
      Não quer mais receber esses lembretes?<br>
      <a href="https://www.roberiodiogenes.com/contato.html" style="color:#B8860B">Entre em contato</a>.
    </p>
  </div>
</div>
</body>
</html>
HTML;
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Robério Diógenes <contato@roberiodiogenes.com>\r\n";
        return @mail($email, $assunto, $html, $headers);
    }

    // Se PHPMailer disponível
    try {
        return Mailer::enviarLembreteCarrinho($email, $nome, $titulos, $totalFmt);
    } catch (\Throwable $e) {
        error_log('[Cron Carrinho] ' . $e->getMessage());
        return false;
    }
}

function log_cron(string $msg): void {
    $linha = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    error_log($linha);
    if (php_sapi_name() === 'cli') {
        echo $linha;
    }
}
