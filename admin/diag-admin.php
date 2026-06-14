<?php
// Diagnóstico temporário — APAGUE após usar
if (($_GET['key'] ?? '') !== 'RD2025diag') { http_response_code(404); die('Not found.'); }

require_once __DIR__ . '/../backend/config.php';

$senha_teste = 'RD@Admin2025!';
$pdo = db();

$row = $pdo->query("SELECT id, username, password, created_at FROM admin_users WHERE username='admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$verify = $row ? password_verify($senha_teste, $row['password']) : false;

// Gera um hash novo para comparar
$novo_hash = password_hash($senha_teste, PASSWORD_BCRYPT, ['cost' => 12]);

echo '<pre style="font-family:monospace;background:#111;color:#0f0;padding:2rem;font-size:13px">';
echo "PHP versão:       " . phpversion() . "\n";
echo "Usuário no BD:    " . ($row ? $row['username'] : 'NÃO ENCONTRADO') . "\n";
echo "Hash no BD:       " . ($row ? $row['password'] : '---') . "\n";
echo "Criado em:        " . ($row ? $row['created_at'] : '---') . "\n";
echo "password_verify:  " . ($verify ? '✅ TRUE — senha correta' : '❌ FALSE — senha NÃO bate') . "\n";
echo "\n--- Novo hash gerado agora (para confirmar que bcrypt funciona) ---\n";
echo "Novo hash:        $novo_hash\n";
echo "Verificação novo: " . (password_verify($senha_teste, $novo_hash) ? '✅ TRUE' : '❌ FALSE') . "\n";
echo '</pre>';
