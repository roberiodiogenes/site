<?php
/* Teste temporário — delete após usar
   Acesse: http://localhost/roberiodiogenes.com/backend/teste_api.php */

ob_start();
require_once __DIR__ . '/config.php';
$saida_obs = ob_get_clean();

header('Content-Type: text/plain; charset=utf-8');

echo "=== TESTE DA API DO BLOG ===\n\n";

// 1. Output antes de config.php
echo "1. Output capturado antes do config: " . json_encode($saida_obs) . "\n\n";

// 2. Conexão com banco
try {
    $pdo = db();
    echo "2. Conexão com banco: OK\n\n";
} catch (Exception $e) {
    echo "2. ERRO no banco: " . $e->getMessage() . "\n\n";
    exit;
}

// 3. Colunas da tabela posts
try {
    $st = $pdo->query("SHOW COLUMNS FROM posts");
    $cols = array_column($st->fetchAll(PDO::FETCH_ASSOC), 'Field');
    echo "3. Colunas em posts: " . implode(', ', $cols) . "\n\n";
    echo "   html_externo existe? " . (in_array('html_externo', $cols) ? "SIM" : "NAO - ESSE E O PROBLEMA!") . "\n\n";
} catch (Exception $e) {
    echo "3. ERRO: " . $e->getMessage() . "\n\n";
}

// 4. Testar o SELECT exato da API
try {
    $st = $pdo->query(
        "SELECT id, slug, titulo, categoria, status, publicado_em
         FROM posts WHERE status = 'publicado'
         ORDER BY destaque DESC, publicado_em DESC LIMIT 9"
    );
    $posts = $st->fetchAll(PDO::FETCH_ASSOC);
    echo "4. Posts publicados encontrados: " . count($posts) . "\n";
    foreach ($posts as $p) {
        echo "   id={$p['id']} slug={$p['slug']} titulo=" . substr($p['titulo'],0,30) . "...\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "4. ERRO no SELECT: " . $e->getMessage() . "\n\n";
}

// 5. Testar SELECT com html_externo (o que causa o erro)
try {
    $st = $pdo->query(
        "SELECT id, slug, titulo, html_externo FROM posts LIMIT 1"
    );
    echo "5. SELECT com html_externo: OK (coluna existe)\n\n";
} catch (Exception $e) {
    echo "5. SELECT com html_externo FALHOU: " . $e->getMessage() . "\n";
    echo "   SOLUCAO: Execute no phpMyAdmin:\n";
    echo "   ALTER TABLE posts ADD COLUMN html_externo VARCHAR(300) DEFAULT NULL;\n\n";
}

// 6. Simular retorno completo da API
echo "6. Simulando resposta da API (blog_api.php?acao=listar):\n";
ob_start();
$_GET['acao'] = 'listar';
$_GET['pagina'] = '1';
$_GET['por_pagina'] = '3';
$_GET['cat'] = 'todos';
$_GET['busca'] = '';
try {
    // incluir o arquivo e capturar output
    include __DIR__ . '/blog_api.php';
} catch (Exception $e) {
    $out = ob_get_clean();
    echo "   ERRO ao incluir blog_api.php: " . $e->getMessage() . "\n";
    echo "   Output capturado: " . substr($out, 0, 200) . "\n";
}
