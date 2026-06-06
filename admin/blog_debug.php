<?php
/* ================================================================
   DIAGNÓSTICO TEMPORÁRIO — blog_debug.php
   Acesse: http://localhost/roberiodiogenes.com/admin/blog_debug.php
   Deletar após usar.
   ================================================================ */

// Capturar QUALQUER output gerado antes de chegarmos aqui
ob_start();

session_name('rd_admin_sess');
session_start();

$saida_capturada = ob_get_clean();

$checks = [];

// 1. Sessão admin
$checks['sessao_admin'] = !empty($_SESSION['admin_id'])
    ? ['ok' => true,  'detalhe' => 'admin_id = ' . $_SESSION['admin_id']]
    : ['ok' => false, 'detalhe' => 'Não logado como admin'];

// 2. Output antes do script
$checks['output_antes'] = strlen($saida_capturada) === 0
    ? ['ok' => true,  'detalhe' => 'Nenhum output vazado']
    : ['ok' => false, 'detalhe' => 'Output capturado: ' . json_encode(substr($saida_capturada, 0, 200))];

// 3. config.php
ob_start();
try {
    require_once __DIR__ . '/../backend/config.php';
    $output_config = ob_get_clean();
    $checks['config_php'] = strlen($output_config) === 0
        ? ['ok' => true,  'detalhe' => 'Sem output']
        : ['ok' => false, 'detalhe' => 'config.php emitiu: ' . json_encode(substr($output_config, 0, 200))];
} catch (Throwable $e) {
    ob_get_clean();
    $checks['config_php'] = ['ok' => false, 'detalhe' => 'Exceção: ' . $e->getMessage()];
}

// 4. Função db()
try {
    $pdo = db();
    $checks['banco'] = ['ok' => true, 'detalhe' => 'Conexão PDO OK'];
} catch (Throwable $e) {
    $checks['banco'] = ['ok' => false, 'detalhe' => 'Erro PDO: ' . $e->getMessage()];
}

// 5. Tabela posts existe?
if (!empty($pdo)) {
    try {
        $st = $pdo->query("SELECT COUNT(*) FROM posts");
        $checks['tabela_posts'] = ['ok' => true, 'detalhe' => $st->fetchColumn() . ' posts no banco'];
    } catch (Throwable $e) {
        $checks['tabela_posts'] = ['ok' => false, 'detalhe' => $e->getMessage()];
    }
}

// 6. _admin.php emite output?
ob_start();
try {
    // NÃO incluir _admin.php de verdade pois emite HTML completo —
    // apenas verificar se o arquivo existe e tem BOM
    $adminPath  = __DIR__ . '/_admin.php';
    $conteudo   = file_get_contents($adminPath);
    $temBOM     = substr($conteudo, 0, 3) === "\xEF\xBB\xBF";
    $primeiroChar = substr(ltrim($conteudo), 0, 5);
    ob_get_clean();
    $checks['_admin_php'] = [
        'ok'     => !$temBOM,
        'detalhe'=> 'BOM UTF-8: ' . ($temBOM ? 'SIM (problema!)' : 'Não') .
                    ' | Começa com: ' . json_encode($primeiroChar)
    ];
} catch (Throwable $e) {
    ob_get_clean();
    $checks['_admin_php'] = ['ok' => false, 'detalhe' => $e->getMessage()];
}

// 7. Simular POST de criar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['testar'] ?? '') === '1') {
    ob_start();
    $_POST['acao']          = 'criar';
    $_POST['titulo']        = 'Teste diagnóstico';
    $_POST['conteudo']      = '<p>Teste</p>';
    $_POST['categoria']     = 'reflexao';
    $_POST['tempo_leitura'] = '3';
    $_POST['status']        = 'rascunho';

    // Incluir blog.php e capturar saída
    try {
        // Não incluir — apenas simular o fetch manualmente
        $ch = curl_init('http://localhost/roberiodiogenes.com/admin/blog.php');
        if ($ch) {
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query($_POST),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_COOKIE         => 'rd_admin_sess=' . session_id(),
                CURLOPT_TIMEOUT        => 5,
            ]);
            $resposta = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            ob_get_clean();
            $checks['simulacao_post'] = [
                'ok'     => ($httpCode === 200 && json_decode($resposta) !== null),
                'detalhe'=> "HTTP $httpCode | Resposta: " . substr($resposta, 0, 400),
            ];
        } else {
            ob_get_clean();
            $checks['simulacao_post'] = ['ok' => false, 'detalhe' => 'cURL não disponível'];
        }
    } catch (Throwable $e) {
        ob_get_clean();
        $checks['simulacao_post'] = ['ok' => false, 'detalhe' => $e->getMessage()];
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Blog Debug</title>
<style>
  body { font-family: monospace; background: #0d0a07; color: #ccc; padding: 2rem; }
  h1   { color: #B8860B; font-size: 1.1rem; margin-bottom: 1.5rem; }
  .item { padding: .6rem 1rem; margin: .4rem 0; border-radius: 6px; border-left: 3px solid; }
  .ok   { background: rgba(39,174,96,.1);  border-color: #27ae60; }
  .erro { background: rgba(231,76,60,.1);  border-color: #c0392b; }
  .lbl  { font-weight: bold; margin-right: .75rem; }
  .ok  .lbl { color: #2ecc71; }
  .erro .lbl { color: #e74c3c; }
  .det  { color: #aaa; font-size: .88rem; }
  form  { margin-top: 2rem; }
  button { padding: .5rem 1.25rem; background: #B8860B; color: #1A0F00; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
  .aviso { background: rgba(241,196,15,.1); border: 1px solid #d4ac0d; color: #f1c40f; padding: .75rem 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: .85rem; }
</style>
</head>
<body>
<h1>🔍 Diagnóstico — admin/blog.php</h1>
<div class="aviso">⚠ Apague este arquivo (<code>admin/blog_debug.php</code>) após o diagnóstico.</div>

<?php foreach ($checks as $nome => $r): ?>
  <div class="item <?= $r['ok'] ? 'ok' : 'erro' ?>">
    <span class="lbl"><?= $r['ok'] ? '✓' : '✗' ?> <?= htmlspecialchars($nome) ?></span>
    <span class="det"><?= htmlspecialchars($r['detalhe']) ?></span>
  </div>
<?php endforeach; ?>

<?php if (!isset($checks['simulacao_post'])): ?>
<form method="POST">
  <input type="hidden" name="testar" value="1">
  <br><button type="submit">▶ Simular POST de criar post</button>
</form>
<?php endif; ?>

</body>
</html>
