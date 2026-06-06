<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/cron_lembrete_leitura.php
   Cron job: envia lembretes para livros abandonados
   Configurar no cPanel Hostgator:
     Comando: /usr/bin/php /home/[user]/public_html/backend/cron_lembrete_leitura.php
     Frequência: uma vez por dia (ex: 0 9 * * *)
   ================================================================ */

/* Proteção: só executa via CLI ou IP local */
if (PHP_SAPI !== 'cli' && ($_SERVER['REMOTE_ADDR']??'') !== '127.0.0.1') {
    http_response_code(403); exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

$pdo = db();
$hoje = date('Y-m-d H:i:s');

/* Leitores que pararam há 7 dias, progresso entre 5% e 98%, sem conclusão */
$st = $pdo->prepare(
    "SELECT
       u.id, u.nome, u.email,
       p.livro_slug, p.percentual, p.capitulo_atual,
       l.titulo AS livro_titulo,
       p.atualizado_em
     FROM leitura_progresso p
     JOIN usuarios u ON u.id = p.usuario_id
     JOIN livros   l ON l.slug = p.livro_slug
     WHERE p.percentual BETWEEN 5 AND 98
       AND p.atualizado_em < DATE_SUB(NOW(), INTERVAL 7 DAY)
       AND p.atualizado_em > DATE_SUB(NOW(), INTERVAL 60 DAY)
       AND NOT EXISTS (
         SELECT 1 FROM leitura_lembretes_enviados le
         WHERE le.usuario_id=p.usuario_id AND le.livro_slug=p.livro_slug
           AND le.enviado_em > DATE_SUB(NOW(), INTERVAL 14 DAY)
       )
       AND u.email IS NOT NULL
     ORDER BY p.atualizado_em ASC
     LIMIT 50"
);
$st->execute();
$leitores = $st->fetchAll();

$enviados = 0; $erros = 0;

foreach ($leitores as $l) {
    $perc = round($l['percentual']);
    $link = SITE_URL . "/leitor/livro.html?livro={$l['livro_slug']}";

    /* Mensagem varia conforme o progresso */
    if ($perc >= 75) {
        $msg  = "Você está a poucos capítulos do fim de <em>{$l['livro_titulo']}</em>. Vale a pena terminar!";
        $cta  = "Terminar a leitura";
    } elseif ($perc >= 40) {
        $msg  = "Você leu {$perc}% de <em>{$l['livro_titulo']}</em>. A história está esquentando — não pare agora.";
        $cta  = "Continuar de onde parei";
    } else {
        $msg  = "Parece que <em>{$l['livro_titulo']}</em> ainda está esperando por você. Que tal retomar?";
        $cta  = "Voltar à leitura";
    }

    try {
        Mailer::enviar([
            'para_email' => $l['email'],
            'para_nome'  => $l['nome'],
            'assunto'    => "📖 Você esqueceu de terminar: {$l['livro_titulo']}",
            'html'       => "
<div style=\"font-family:Georgia,serif;max-width:520px;margin:0 auto;padding:2rem;background:#FAF7F2;border-radius:8px\">
  <p style=\"font-family:'Cinzel',serif;font-size:.7rem;letter-spacing:.15em;text-transform:uppercase;color:#B8860B\">Robério Diógenes · Leitor Online</p>
  <h2 style=\"font-family:'Cinzel',serif;color:#2C2418;font-size:1.25rem;margin:.75rem 0\">{$l['livro_titulo']}</h2>
  <p style=\"color:#2C2418;line-height:1.7\">Olá, <strong>{$l['nome']}</strong>!</p>
  <p style=\"color:#2C2418;line-height:1.7\">{$msg}</p>
  <div style=\"background:#E4DBC8;border-radius:8px;padding:1rem;margin:1.25rem 0\">
    <div style=\"background:#B8860B;height:8px;border-radius:4px;width:{$perc}%\"></div>
    <p style=\"font-size:.8rem;color:#5C4F3A;margin-top:.4rem;text-align:center\">{$perc}% lido — capítulo {$l['capitulo_atual']}</p>
  </div>
  <div style=\"text-align:center;margin:1.5rem 0\">
    <a href=\"{$link}\" style=\"background:#B8860B;color:#1A0F00;padding:.85rem 2rem;border-radius:6px;text-decoration:none;font-weight:700;font-family:Georgia,serif;display:inline-block\">{$cta} →</a>
  </div>
  <hr style=\"border-color:#E4DBC8;margin:1.5rem 0\">
  <p style=\"color:#B8A888;font-size:.75rem\">Para cancelar estes lembretes, <a href=\"".SITE_URL."/backend/newsletter.php?descadastrar=".urlencode($l['email'])."\" style=\"color:#B8A888\">clique aqui</a>.</p>
</div>",
            'texto' => "Olá {$l['nome']}! Você leu {$perc}% de \"{$l['livro_titulo']}\". Continue: {$link}",
        ]);

        /* Registra envio para não repetir em 14 dias */
        $pdo->prepare(
            "INSERT INTO leitura_lembretes_enviados (usuario_id,livro_slug,enviado_em) VALUES (?,?,NOW())
             ON DUPLICATE KEY UPDATE enviado_em=NOW()"
        )->execute([$l['id'], $l['livro_slug']]);

        $enviados++;
        echo "[OK] {$l['email']} — {$l['livro_titulo']} ({$perc}%)\n";

    } catch (\Throwable $e) {
        $erros++;
        echo "[ERRO] {$l['email']}: " . $e->getMessage() . "\n";
    }
}

echo "\nResumo: {$enviados} enviados, {$erros} erros.\n";
