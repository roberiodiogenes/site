-- ============================================================
-- ROBÉRIO DIÓGENES — setup.sql
-- Esquema completo do banco de dados
-- roberiodiogenes.com | versão 3.0
-- ============================================================
-- Compatível com MySQL 5.7+ e MariaDB 10.4+
-- Execute no phpMyAdmin → aba SQL
-- ============================================================

-- ── Tabela de usuários cadastrados (expandida) ────────────
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(120) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `senha` VARCHAR(255) NOT NULL DEFAULT '', -- vazio para contas Google
  
  -- Dados pessoais expandidos
  `sexo` ENUM('masculino', 'feminino', 'outro', 'nao_informado') DEFAULT 'nao_informado',
  `data_nascimento` DATE DEFAULT NULL,
  `cidade` VARCHAR(100) DEFAULT NULL,
  `estado` CHAR(2) DEFAULT NULL, -- UF: CE, SP, RJ, etc
  `pais` VARCHAR(100) DEFAULT 'Brasil',
  `whatsapp` VARCHAR(25) DEFAULT NULL, -- formato: +5585999999999
  
  -- Autenticação Google
  `google_id` VARCHAR(100) DEFAULT NULL, -- sub do token Google
  `foto_url` VARCHAR(500) DEFAULT NULL, -- avatar Google ou upload
  
  -- Segurança e auditoria
  `ip_cadastro` VARCHAR(45) DEFAULT NULL,
  `verificado` TINYINT(1) NOT NULL DEFAULT 0, -- 1 = email verificado
  `ativo` TINYINT(1) NOT NULL DEFAULT 1, -- 0 = conta desativada
  `ultimo_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  UNIQUE KEY `uq_google_id` (`google_id`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_data_nascimento` (`data_nascimento`),
  KEY `idx_whatsapp` (`whatsapp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabela de recuperação de senha ───────────────────────
CREATE TABLE IF NOT EXISTS `password_reset` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(255) NOT NULL, -- hash do token enviado por e-mail
  `expira_em` DATETIME NOT NULL, -- quando o token expira (ex: +1 hora)
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usado_em` DATETIME DEFAULT NULL, -- timestamp do reset bem-sucedido
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token` (`token`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_expira_em` (`expira_em`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabela de livros favoritos ───────────────────────────
CREATE TABLE IF NOT EXISTS `livros_favoritos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `livro_id` INT UNSIGNED NOT NULL, -- ID do livro (será definido em futura tabela livros)
  `adicionado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_livro` (`usuario_id`, `livro_id`),
  KEY `idx_livro_id` (`livro_id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabela de downloads ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `downloads` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `livro_id` INT UNSIGNED NOT NULL,
  `tipo_arquivo` ENUM('pdf', 'epub', 'mobi') DEFAULT 'pdf',
  `data_download` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_download` VARCHAR(45) DEFAULT NULL,
  
  PRIMARY KEY (`id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_livro_id` (`livro_id`),
  KEY `idx_data_download` (`data_download`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabela de log de autenticação (rate limiting) ────────
CREATE TABLE IF NOT EXISTS `auth_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(45) NOT NULL,
  `action` VARCHAR(30) NOT NULL, -- 'login', 'register', 'google_auth', 'recuperar_senha'
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  KEY `idx_ip_action` (`ip`, `action`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabela de newsletter ────────────────────────────────
CREATE TABLE IF NOT EXISTS `newsletter` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `whatsapp` VARCHAR(25) DEFAULT NULL,
  `ip` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('ativo', 'descadastrado') NOT NULL DEFAULT 'ativo',
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabela de log de tentativas de newsletter (anti-spam) ──
CREATE TABLE IF NOT EXISTS `newsletter_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(45) NOT NULL,
  `tentativa` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip`),
  KEY `idx_tentativa` (`tentativa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabela de contador de visitas ───────────────────────
CREATE TABLE IF NOT EXISTS `visitas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `total` BIGINT NOT NULL DEFAULT 0,
  
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `visitas` (`id`, `total`) VALUES (1, 0);

-- ── Tabela de controle de visitas únicas ────────────────
-- visitor_hash = SHA-256(IP + User-Agent) → anônimo e irreversível
-- visit_date = dia da visita (uma entrada por visitante por dia)
-- UNIQUE em (visitor_hash, visit_date) permite recontar no dia seguinte
CREATE TABLE IF NOT EXISTS `visitas_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `visitor_hash` CHAR(64) NOT NULL,
  `visit_date` DATE NOT NULL DEFAULT (CURRENT_DATE),
  `visited_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_visitor_dia` (`visitor_hash`, `visit_date`),
  KEY `idx_visit_date` (`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabela de admin ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(80) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_login` DATETIME DEFAULT NULL,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin padrão: usuário=admin | senha=RD@2025admin (TROQUE APÓS O 1º LOGIN)
-- Hash bcrypt cost 12: $2y$12$eImiTXuWVxfM37uY4JANjO6RFV1bGVJNX6aGQ6MjaTN6tQqaHxcPC
INSERT IGNORE INTO `admin_users` (`username`, `password`) 
VALUES ('admin', '$2y$12$eImiTXuWVxfM37uY4JANjO6RFV1bGVJNX6aGQ6MjaTN6tQqaHxcPC');

-- ── Tabela de log de admin (auditoria) ────────────────────
CREATE TABLE IF NOT EXISTS `admin_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED DEFAULT NULL,
  `acao` VARCHAR(100) NOT NULL, -- 'login', 'criar_usuario', 'deletar_usuario', etc
  `descricao` TEXT DEFAULT NULL,
  `ip` VARCHAR(45) DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_acao` (`acao`),
  KEY `idx_criado_em` (`criado_em`),
  FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ÍNDICES ADICIONAIS PARA OTIMIZAÇÃO
-- ============================================================

-- Índices para queries de perfil
ALTER TABLE `usuarios` ADD KEY `idx_created_at` (`created_at`);
ALTER TABLE `livros_favoritos` ADD KEY `idx_adicionado_em` (`adicionado_em`);

-- ============================================================
-- VIEWS (opcional — para relatórios)
-- ============================================================

-- View: usuários com contagem de livros favoritos
CREATE OR REPLACE VIEW `vw_usuarios_com_favoritos` AS
SELECT 
  u.id,
  u.nome,
  u.email,
  COUNT(lf.id) AS total_favoritos,
  u.ultimo_login,
  u.ativo
FROM `usuarios` u
LEFT JOIN `livros_favoritos` lf ON u.id = lf.usuario_id
GROUP BY u.id;

-- View: usuários ativos com contagem de downloads
CREATE OR REPLACE VIEW `vw_usuarios_com_downloads` AS
SELECT 
  u.id,
  u.nome,
  u.email,
  COUNT(DISTINCT d.id) AS total_downloads,
  MAX(d.data_download) AS ultimo_download
FROM `usuarios` u
LEFT JOIN `downloads` d ON u.id = d.usuario_id
WHERE u.ativo = 1
GROUP BY u.id;

-- ============================================================
-- STORED PROCEDURES (opcional — para limpeza automática)
-- ============================================================

-- Procedure: limpar tokens de reset expirados
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `limpar_tokens_expirados`()
BEGIN
  DELETE FROM `password_reset`
  WHERE `expira_em` < NOW() AND `usado_em` IS NULL;
END //
DELIMITER ;

-- Procedure: limpar logs antigos (> 30 dias)
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `limpar_logs_antigos`()
BEGIN
  DELETE FROM `auth_log` WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 30 DAY);
  DELETE FROM `newsletter_log` WHERE `tentativa` < DATE_SUB(NOW(), INTERVAL 7 DAY);
  DELETE FROM `admin_log` WHERE `criado_em` < DATE_SUB(NOW(), INTERVAL 90 DAY);
END //
DELIMITER ;

-- ============================================================
-- EVENTOS AGENDADOS (opcional — execução automática)
-- ============================================================

-- Event: executar limpeza de tokens expirados diariamente
CREATE EVENT IF NOT EXISTS `evt_limpar_tokens`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO CALL `limpar_tokens_expirados`();

-- Event: executar limpeza de logs antigos semanalmente
CREATE EVENT IF NOT EXISTS `evt_limpar_logs`
ON SCHEDULE EVERY 1 WEEK
STARTS CURRENT_TIMESTAMP
DO CALL `limpar_logs_antigos`();

-- ============================================================
-- COMENTÁRIOS FINAIS
-- ============================================================
-- 
-- Este script cria um banco de dados completo com:
-- ✓ Tabelas de usuários expandidas (sexo, data_nascimento, país, cidade, estado)
-- ✓ Autenticação segura (senhas bcrypt, Google OAuth)
-- ✓ Recuperação de senha com tokens com expiração
-- ✓ Livros favoritos e downloads
-- ✓ Newsletter com controle de spam
-- ✓ Contador de visitas anônimas
-- ✓ Auditoria de admin com log de ações
-- ✓ Índices otimizados para performance
-- ✓ Foreign keys para integridade referencial
-- ✓ Views para relatórios
-- ✓ Stored procedures para limpeza automática
-- ✓ Eventos agendados para manutenção
--
-- Para executar no phpMyAdmin:
-- 1. Acesse: http://localhost/phpmyadmin
-- 2. Crie um novo banco de dados: roberio_site
-- 3. Selecione o banco criado
-- 4. Vá na aba SQL
-- 5. Cole TODO o conteúdo deste arquivo
-- 6. Clique em "Executar"
--
-- ============================================================
-- ── Comentários de livros ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS `comentarios` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `livro_slug`  VARCHAR(100) NOT NULL,
    `nome`        VARCHAR(120) NOT NULL,
    `cidade`      VARCHAR(100) DEFAULT NULL,
    `leu`         ENUM('sim','cap','nao','') DEFAULT '',
    `texto`       TEXT NOT NULL,
    `ip`          VARCHAR(45) NOT NULL,
    `aprovado`    TINYINT(1) NOT NULL DEFAULT 0,
    `criado_em`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_livro_aprovado` (`livro_slug`, `aprovado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Mensagens de contato ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `contato` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nome`       VARCHAR(120) NOT NULL,
    `email`      VARCHAR(255) NOT NULL,
    `assunto`    VARCHAR(200) DEFAULT NULL,
    `mensagem`   TEXT NOT NULL,
    `ip`         VARCHAR(45) NOT NULL,
    `lida`       TINYINT(1) NOT NULL DEFAULT 0,
    `criado_em`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
