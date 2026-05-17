-- ============================================================
-- SETUP DO BANCO DE DADOS — roberiodiogenes.com
-- ============================================================
-- Execute este script no cPanel → phpMyAdmin → aba SQL
-- ============================================================

-- Tabela de inscritos na newsletter
CREATE TABLE IF NOT EXISTS `newsletter` (
    `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `email`      VARCHAR(255)    NOT NULL,
    `ip`         VARCHAR(45)     DEFAULT NULL,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status`     ENUM('ativo','descadastrado') NOT NULL DEFAULT 'ativo',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de logs de tentativas (proteção anti-spam)
CREATE TABLE IF NOT EXISTS `newsletter_log` (
    `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `ip`         VARCHAR(45)     NOT NULL,
    `tentativa`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de admin (painel administrativo)
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `username`   VARCHAR(80)     NOT NULL,
    `password`   VARCHAR(255)    NOT NULL,  -- bcrypt hash
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- INSERIR USUÁRIO ADMIN INICIAL
-- Usuário: admin
-- Senha:   RD@2025admin  (TROQUE APÓS O PRIMEIRO LOGIN)
-- ============================================================
INSERT IGNORE INTO `admin_users` (`username`, `password`)
VALUES (
    'admin',
    '$2y$12$eImiTXuWVxfM37uY4JANjO6RFV1bGVJNX6aGQ6MjaTN6tQqaHxcPC'
);
-- Senha hash acima corresponde a: RD@2025admin
-- Para gerar nova hash: php -r "echo password_hash('SuaNovaSenha', PASSWORD_BCRYPT, ['cost'=>12]);"

-- ============================================================
-- TABELA DE CONTADOR DE VISITAS (adicionada na v2)
-- ============================================================

-- Tabela principal: total de visitas únicas
CREATE TABLE IF NOT EXISTS `visitas` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `total`      BIGINT       NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inserir linha inicial
INSERT IGNORE INTO `visitas` (`id`, `total`) VALUES (1, 0);

-- Tabela de controle: evita contar o mesmo visitante várias vezes
-- Usa hash do IP + user-agent (sem armazenar dados pessoais)
CREATE TABLE IF NOT EXISTS `visitas_log` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `visitor_hash` CHAR(64)   NOT NULL,          -- SHA-256 de IP+UA
    `visited_at`   DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_visitor` (`visitor_hash`)     -- impede duplicatas
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
