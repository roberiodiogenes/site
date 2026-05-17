<?php
/**
 * CONFIGURAÇÃO DO BANCO DE DADOS
 * ─────────────────────────────────────────────────────────────
 * Substitua os valores abaixo com os dados do seu banco no HostGator.
 * Você encontra essas informações no cPanel → Bancos de Dados MySQL.
 *
 * ATENÇÃO: Nunca compartilhe este arquivo publicamente.
 */

define('DB_HOST', 'localhost');           // Sempre "localhost" no HostGator
define('DB_NAME', 'roberiodiogenes');  // Ex: roberio_rdsite
define('DB_USER', 'root');  // Ex: roberio_rduser
define('DB_PASS', '');        // A senha que você definiu no cPanel
define('DB_CHARSET', 'utf8mb4');

// ─── Conexão PDO (usada por todos os arquivos PHP) ───────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados.']);
            exit;
        }
    }
    return $pdo;
}
