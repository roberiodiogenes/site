<?php
/**
 * config/db.example.php
 * ─────────────────────────────────────────────────────────────
 * ARQUIVO DE EXEMPLO — seguro para versionar no GitHub.
 *
 * Copie este arquivo para config/db.php e preencha com suas
 * credenciais reais. O db.php está no .gitignore e NUNCA
 * deve ser enviado ao repositório.
 *
 * Para ambiente local (XAMPP):
 *   DB_NAME → roberiodiogenes
 *   DB_USER → root
 *   DB_PASS → (vazio)
 *
 * Para produção (HostGator):
 *   DB_NAME → roberio_rdsite   (prefixo gerado pelo cPanel)
 *   DB_USER → roberio_rduser
 *   DB_PASS → SuaSenhaReal
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'SEU_BANCO_AQUI');
define('DB_USER', 'SEU_USUARIO_AQUI');
define('DB_PASS', 'SUA_SENHA_AQUI');
define('DB_CHARSET', 'utf8mb4');

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
