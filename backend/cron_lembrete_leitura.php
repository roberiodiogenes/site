<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/cron_lembrete_leitura.php
   Cron job: e-mail de abandono de leitura

   Critério: assinante/comprador com progresso 5%–98% que não abre
   o leitor há 7 dias (sem repetição nos 14 dias seguintes).

   Execução no cPanel Hostgator (cron diário às 9h):
     0 9 * * *  /usr/bin/php /home/[user]/public_html/backend/cron_lembrete_leitura.php

   Ou testar via URL (com token):
     GET /backend/cron_lembrete_leitura.php?token=RD_CRON_2025_SEGURO
   ================================================================ */

/* ── Proteção de acesso ─────────────────────────────────────── */
define('CRON_TOKEN_LEITURA', 'RD_CRON_2025_SEGURO'); // ← troque em produção

$viaCLI  = (PHP_SAPI === 'cli');
$viaHTTP = !$viaCLI;

if ($viaHTTP) {
    $token = $_GET['token'] ?? '';
    if (!hash_equals(CRON_TOKEN_LEITURA, $token)) {
        http_response_code(403);
        die('Acesso negado.');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

$pdo = db();
$enviados = 0; $erros = 0;

/* ── Buscar leitores que pararam há 7 dias ──────────────────── */
// Tabela real: leitor_progresso (ultima_leitura)
$st = $pdo->prepare("
    SELECT
      u.id, u.nome, u.email,
      p.livro_slug, p.percentual, p.capitulo_atual,
      l.titulo AS livro_titulo,
      p.ultima_leitura
    FROM leitor_progresso p
    JOIN usuarios u ON u.id = p.usuario_id
    JOIN livros   l ON l.slug = p.livro_slug
    WHERE p.percentual BETWEEN 5 AND 98
      AND p.concluido = 0
      AND p.ultima_leitura < DATE_SUB(NOW(), INTERVAL 7 DAY)
      AND p.ultima_leitura > DATE_SUB(NOW(), INTERVAL 60 DAY)
      AND NOT EXISTS (
        SELECT 1 FROM leitor_lembretes_enviados le
        WHERE le.usuario_id = p.usuario_id
          AND le.livro_slug  = p.livro_slug
          AND le.enviado_em  > DATE_SUB(NOW(), INTERVAL 14 DAY)
      )
      AND u.email IS NOT NULL
      AND u.ativo = 1
    ORDER BY p.ultima_leitura ASC
    LIMIT 50
");
$st->execute();
$leitores = $st->fetchAll();

_log("Iniciando. Leitores elegíveis: " . count($leitores));

foreach ($leitores as $l) {
    $perc = round((float)$l['percentual']);
    $link = SITE_URL . "/leitor/livro.html?livro={$l['livro_slug']}";
    $primeiroNome = explode(' ', trim($l['nome']))[0];

    /* Texto varia conforme progresso */
    if ($perc >= 75) {
        $msg = "Você está a poucos capítulos do fim de <em>{$l['livro_titulo']}</em>. Vale a pena terminar!";
        $cta = "Terminar a leitura";
    } elseif ($perc >= 40) {
        $msg = "Você leu {$perc}% de <em>{$l['livro_titulo']}</em>. A história está esquentando — não pare agora.";
        $cta = "Continuar de onde parei";
    } else {
        $msg = "Parece que <em>{$l['livro_titulo']}</em> ainda está esperando por você. Que tal retomar?";
        $cta = "Voltar à leitura";
    }

    try {
        Mailer::enviar([
            'para_email' => $l['email'],
            'para_nome'  => $l['nome'],
            'assunto'    => "Você parou no capítulo {$l['capitulo_atual']} de \"{$l['livro_titulo']}\"",
            'html'       => "
<p>Olá, <strong>{$primeiroNome}</strong>.</p>
<p>{$msg}</p>
<div style='background:var(--borda,#E4DBC8);border-radius:8px;padding:1rem;margin:1.25rem 0'>
  <div style='background:#B8860B;height:8px;border-radius:4px;width:{$perc}%;max-width:100%'></div>
  <p style='font-size:.8rem;color:#5C4F3A;margin:.4rem 0 0;text-align:center'>{$perc}% concluído · Capítulo {$l['capitulo_atual']}</p>
</div>
<p style='text-align:center;margin:1.5rem 0'>
  <a href='{$link}' class='btn-email'>{$cta} →</a>
</p>
<p style='font-size:.82rem;color:#8C7D65'>
  Se quiser parar de receber estes lembretes, é só
  <a href='" . SITE_URL . "/contato.html' style='color:#8C7D65'>entrar em contato</a>.
</p>
",
            'texto' => "Olá {$primeiroNome}!\n\nVocê leu {$perc}% de \"{$l['livro_titulo']}\" e ainda não terminou.\nContinue: {$link}",
        ]);

        /* Registra envio para throttle de 14 dias */
        $pdo->prepare(
            "INSERT INTO leitor_lembretes_enviados (usuario_id, livro_slug)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE enviado_em = NOW()"
        )->execute([$l['id'], $l['livro_slug']]);

        $enviados++;
        _log("[OK] {$l['email']} — {$l['livro_titulo']} ({$perc}%)");

    } catch (Throwable $e) {
        $erros++;
        _log("[ERRO] {$l['email']}: " . $e->getMessage());
    }
}

_log("Concluído. Enviados: {$enviados} | Erros: {$erros}");

/* ── Helper ─────────────────────────────────────────────────── */
function _log(string $msg): void {
    $linha = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    error_log($linha);
    echo $linha . "\n";
}
