-- ================================================================
-- migration_blog_avancado.sql
-- Blog: paywall, enquetes, clusters (Hub & Spoke), newsletter
-- Execute no phpMyAdmin: banco roberio_site → aba SQL.
-- Todos os comandos são idempotentes (IF NOT EXISTS).
-- ================================================================

-- ────────────────────────────────────────────────────────────────
-- 1. Novas colunas em `posts`
-- ────────────────────────────────────────────────────────────────

ALTER TABLE `posts`
  ADD COLUMN IF NOT EXISTS `exclusivo`           TINYINT(1)   NOT NULL DEFAULT 0
    COMMENT '1 = conteúdo exclusivo para assinantes' AFTER `destaque`,
  ADD COLUMN IF NOT EXISTS `percentual_livre`    TINYINT UNSIGNED NOT NULL DEFAULT 35
    COMMENT '% do conteúdo exibido sem assinatura (paywall parcial)' AFTER `exclusivo`,
  ADD COLUMN IF NOT EXISTS `enquete_id`          INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'Enquete vinculada ao final do post' AFTER `livro_slug`,
  ADD COLUMN IF NOT EXISTS `cluster_id`          INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'Cluster Hub & Spoke ao qual este post pertence' AFTER `enquete_id`,
  ADD COLUMN IF NOT EXISTS `newsletter_enviado`  TINYINT(1)   NOT NULL DEFAULT 0
    COMMENT '1 = newsletter já foi disparada para este post' AFTER `cluster_id`;

-- ────────────────────────────────────────────────────────────────
-- 2. Clusters (Hub & Spoke — páginas pilares + satélites)
-- ────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `clusters` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `slug`        VARCHAR(160)  NOT NULL,
  `titulo`      VARCHAR(300)  NOT NULL,
  `descricao`   TEXT          DEFAULT NULL,
  `imagem_url`  VARCHAR(500)  DEFAULT NULL,
  `pilar_slug`  VARCHAR(160)  DEFAULT NULL COMMENT 'Slug do post pilar deste cluster',
  `ativo`       TINYINT(1)    NOT NULL DEFAULT 1,
  `criado_em`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────
-- 3. Enquetes / Votações
-- ────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `enquetes` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titulo`     VARCHAR(300) NOT NULL,
  `descricao`  TEXT         DEFAULT NULL,
  `ativo`      TINYINT(1)   NOT NULL DEFAULT 1,
  `multipla`   TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = múltipla escolha',
  `criado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `enquetes_opcoes` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `enquete_id` INT UNSIGNED NOT NULL,
  `texto`      VARCHAR(300) NOT NULL,
  `icone`      VARCHAR(50)  DEFAULT NULL,
  `ordem`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_enquete` (`enquete_id`),
  CONSTRAINT `fk_eopc_enquete` FOREIGN KEY (`enquete_id`) REFERENCES `enquetes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `enquetes_respostas` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `enquete_id` INT UNSIGNED NOT NULL,
  `opcao_id`   INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = voto anônimo (por IP)',
  `ip_hash`    VARCHAR(64)  DEFAULT NULL COMMENT 'SHA256 do IP (LGPD)',
  `criado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_er_enquete` (`enquete_id`),
  KEY `idx_er_opcao`   (`opcao_id`),
  CONSTRAINT `fk_er_enquete` FOREIGN KEY (`enquete_id`) REFERENCES `enquetes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_er_opcao`   FOREIGN KEY (`opcao_id`)   REFERENCES `enquetes_opcoes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────
-- 4. Rastreamento de disparos de newsletter por post
-- ────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `newsletter_disparos` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_slug`    VARCHAR(160) NOT NULL,
  `total_envios` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_erros`  INT UNSIGNED NOT NULL DEFAULT 0,
  `disparado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `disparado_por` VARCHAR(100) DEFAULT NULL COMMENT 'Nome do admin',
  PRIMARY KEY (`id`),
  KEY `idx_nd_post` (`post_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
