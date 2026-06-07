-- ================================================================
-- migration_comentarios_v2.sql  (versão final — idempotente)
-- Pode ser executado mais de uma vez sem erros.
-- Testado em MariaDB 10.4+ (XAMPP) e MySQL 8.0+.
--
-- Execute no phpMyAdmin: selecione "roberio_site" → aba SQL → cole tudo → OK
-- ================================================================

-- ────────────────────────────────────────────────────────────────
-- 1. Novas colunas em `comentarios` (ADD COLUMN IF NOT EXISTS)
-- ────────────────────────────────────────────────────────────────

ALTER TABLE `comentarios`
  ADD COLUMN IF NOT EXISTS `parent_id`      INT UNSIGNED  NULL DEFAULT NULL   COMMENT 'ID do comentário pai (respostas)' AFTER `id`,
  ADD COLUMN IF NOT EXISTS `curtidas_count` INT UNSIGNED  NOT NULL DEFAULT 0  COMMENT 'Cache de curtidas' AFTER `texto`,
  ADD COLUMN IF NOT EXISTS `flagged`        TINYINT(1)    NOT NULL DEFAULT 0  COMMENT '1 = conteúdo suspeito' AFTER `aprovado`,
  ADD COLUMN IF NOT EXISTS `flag_motivo`    VARCHAR(200)  NULL DEFAULT NULL   COMMENT 'Categoria detectada' AFTER `flagged`,
  ADD COLUMN IF NOT EXISTS `ip_hash`        VARCHAR(64)   NULL DEFAULT NULL   COMMENT 'SHA256 do IP (LGPD)' AFTER `flag_motivo`,
  ADD COLUMN IF NOT EXISTS `user_agent`     VARCHAR(500)  NULL DEFAULT NULL   COMMENT 'User-Agent do navegador' AFTER `ip_hash`;

-- ────────────────────────────────────────────────────────────────
-- 2. Índice em parent_id (IF NOT EXISTS — MariaDB 10.1.4+)
-- ────────────────────────────────────────────────────────────────

ALTER TABLE `comentarios`
  ADD INDEX IF NOT EXISTS `idx_parent_id` (`parent_id`);

-- ────────────────────────────────────────────────────────────────
-- 3. Garante tipo correto de parent_id (INT UNSIGNED = mesmo que `id`)
--    Necessário caso uma execução anterior tenha criado como INT (sem UNSIGNED)
-- ────────────────────────────────────────────────────────────────

ALTER TABLE `comentarios`
  MODIFY COLUMN `parent_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'ID do comentário pai (respostas)';

-- ────────────────────────────────────────────────────────────────
-- 4. Chave estrangeira auto-referencial
--    FOREIGN_KEY_CHECKS=0 evita conflito durante criação self-referencial
-- ────────────────────────────────────────────────────────────────

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `comentarios`
  DROP FOREIGN KEY IF EXISTS `fk_com_parent`;

ALTER TABLE `comentarios`
  ADD CONSTRAINT `fk_com_parent`
    FOREIGN KEY (`parent_id`) REFERENCES `comentarios` (`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;

-- ────────────────────────────────────────────────────────────────
-- 5. Tabela de curtidas em comentários
-- ────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `comentario_curtidas` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `comentario_id` INT UNSIGNED NOT NULL,
  `usuario_id`    INT UNSIGNED NOT NULL,
  `criado_em`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY  `uq_curtida`     (`comentario_id`, `usuario_id`),
  KEY         `idx_cc_usuario` (`usuario_id`),
  CONSTRAINT `fk_cc_comentario` FOREIGN KEY (`comentario_id`) REFERENCES `comentarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cc_usuario`    FOREIGN KEY (`usuario_id`)    REFERENCES `usuarios`    (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────
-- 6. Tabela de log de eventos flagged (prova circunstancial)
-- ────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `comentarios_flags_log` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `comentario_id`       INT UNSIGNED NOT NULL,
  `usuario_id`          INT UNSIGNED NULL,
  `usuario_nome`        VARCHAR(150) NULL,
  `usuario_email`       VARCHAR(255) NULL,
  `ip_hash`             VARCHAR(64)  NOT NULL COMMENT 'SHA256 — nunca IP em texto cru (LGPD)',
  `user_agent`          VARCHAR(500) NULL,
  `pais`                VARCHAR(50)  NULL,
  `texto_original`      TEXT         NOT NULL,
  `motivo_flag`         VARCHAR(200) NOT NULL,
  `palavras_detectadas` VARCHAR(500) NULL,
  `referencia_slug`     VARCHAR(200) NULL,
  `acao_tomada`         ENUM('pendente','mantido','removido') NOT NULL DEFAULT 'pendente',
  `criado_em`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `revisado_em`         DATETIME     NULL,
  `revisado_por`        VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cfl_comentario` (`comentario_id`),
  KEY `idx_cfl_acao`       (`acao_tomada`),
  KEY `idx_cfl_data`       (`criado_em`),
  CONSTRAINT `fk_cfl_comentario`
    FOREIGN KEY (`comentario_id`) REFERENCES `comentarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
