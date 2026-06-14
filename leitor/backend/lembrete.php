<?php
/* ================================================================
   leitor/backend/lembrete.php
   Verifica livros abandonados e envia lembretes por e-mail.

   EXECUÇÃO:
     - Via cron (recomendado): 0 9 * * 1 php /caminho/lembrete.php
     - Via URL admin: GET ?token=SEU_TOKEN_SECRETO
     - Critério: sem leitura há 14 dias, livro não concluído,
       sem lembrete enviado nos últimos 30 dias.
   ================================================================ */

// Proteção: só via cron ou token correto
$viaHTTP = php_sapi_name() !== 'cli';
if ($viaHTTP) {
    $token = $_GET['token'] ?? '';
    // Defina LEMBRETE_TOKEN em config.php ou diretamente aqui
    define('LEMBRETE_TOKEN', getenv('LEMBRETE_TOKEN') ?: 'RD_LEMBRETE_2025_TOKEN');
    if (!hash_equals(LEMBRETE_TOKEN, $token)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'erro' => 'Token inválido.']);
        exit;
    }
}

require_once __DIR__ . '/../../backend/config.php';
require_once __DIR__ . '/../../backend/mailer.php';

$pdo = db();
$log = [];

// Livros abandonados: sem leitura há 14+ dias, não concluídos
$stAb = $pdo->query(
    "SELECT lp.usuario_id, lp.livro_slug, lp.percentual, lp.ultima_leitura,
            u.email, u.nome, l.titulo, l.capa_img
     FROM leitor_progresso lp
     JOIN usuarios u ON u.id = lp.usuario_id
     JOIN livros   l ON l.slug = lp.livro_slug
     WHERE lp.concluido = 0
       AND lp.percentual > 0
       AND lp.ultima_leitura < DATE_SUB(NOW(), INTERVAL 14 DAY)
       AND u.ativo = 1
       AND NOT EXISTS (
         SELECT 1 FROM leitor_lembretes_enviados ll
         WHERE ll.usuario_id = lp.usuario_id
           AND ll.livro_slug = lp.livro_slug
           AND ll.enviado_em > DATE_SUB(NOW(), INTERVAL 30 DAY)
       )"
);
$abandonados = $stAb->fetchAll(PDO::FETCH_ASSOC);

foreach ($abandonados as $item) {
    $primeiro   = explode(' ', trim($item['nome']))[0];
    $slug       = $item['livro_slug'];
    $pct        = round((float)$item['percentual']);
    $diasSem    = (int)((time() - strtotime($item['ultima_leitura'])) / 86400);
    $leitorUrl  = 'https://www.roberiodiogenes.com/leitor/?livro=' . urlencode($slug);
    $capaHtml   = $item['capa_img']
        ? "<img src='https://www.roberiodiogenes.com/{$item['capa_img']}' alt='' style='width:80px;border-radius:4px;float:left;margin-right:1rem'>"
        : '';

    $html = "
      <p>Olá, <strong>$primeiro</strong>.</p>
      <p>Você estava lendo <strong>{$item['titulo']}</strong> e parou nos <strong>$pct%</strong>.</p>
      <p>Já faz <strong>$diasSem dias</strong> sem abrir o livro. Que tal continuar hoje?</p>
      <div style='margin:1.5rem 0;padding:1rem;background:#FAF7F2;border-radius:8px;overflow:hidden'>
        $capaHtml
        <p style='margin:0;font-family:Georgia,serif;font-size:1.1rem'>{$item['titulo']}</p>
        <p style='font-size:.85rem;color:#7A6E5C;margin:.35rem 0'>$pct% lido — falta pouco!</p>
        <div style='clear:both'></div>
      </div>
      <p style='text-align:center'>
        <a href='$leitorUrl' style='display:inline-block;padding:.7rem 1.5rem;background:#B8860B;color:#fff;border-radius:8px;text-decoration:none;font-weight:600'>
          Continuar leitura →
        </a>
      </p>
      <p style='font-size:.78rem;color:#9A8E7C;margin-top:1.5rem'>
        Você recebe este e-mail pois tem uma leitura em andamento no site de Robério Diógenes.<br>
        <a href='https://www.roberiodiogenes.com/backend/newsletter.php?acao=cancelar&email=" . urlencode($item['email']) . "'>Cancelar lembretes de leitura</a>
      </p>";

    $ok = Mailer::enviar([
        'para_email' => $item['email'],
        'para_nome'  => $item['nome'],
        'assunto'    => "📖 Você parou nos {$pct}% de \"{$item['titulo']}\"",
        'html'       => $html,
        'texto'      => "Você parou nos {$pct}% de \"{$item['titulo']}\". Que tal continuar? $leitorUrl",
    ]);

    if ($ok) {
        $pdo->prepare(
            "INSERT INTO leitor_lembretes_enviados (usuario_id, livro_slug)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE enviado_em = NOW()"
        )->execute([$item['usuario_id'], $slug]);
        $log[] = "OK: {$item['email']} — {$item['titulo']} ({$pct}%)";
    } else {
        $log[] = "ERRO: {$item['email']} — {$item['titulo']}";
    }
}

$msg = count($abandonados) . ' leituras verificadas. ' . count(array_filter($log, fn($l) => str_starts_with($l, 'OK'))) . ' lembretes enviados.';

if ($viaHTTP) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'msg' => $msg, 'log' => $log]);
} else {
    echo $msg . "\n";
    foreach ($log as $l) echo "  $l\n";
}
