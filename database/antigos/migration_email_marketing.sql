-- ================================================================
--  ROBÉRIO DIÓGENES — migration_email_marketing.sql
--  1. Rastreamento de origem nas inscrições da newsletter
--  2. Rastreamento de interesse literário por categoria
--  Execute no phpMyAdmin: banco roberio_site → aba SQL
-- ================================================================

-- ── 1. Coluna origem na tabela newsletter ─────────────────────────
--  Registra de onde veio cada lead:
--  'home', 'blog', 'post', 'livro_lumen', 'cluster', 'popup_saida', etc.

ALTER TABLE `newsletter`
  ADD COLUMN IF NOT EXISTS `origem` VARCHAR(80) NULL DEFAULT NULL
    COMMENT 'Origem da inscrição: home, blog, livro_slug, popup_saida, etc.'
    AFTER `ip`;

CREATE INDEX IF NOT EXISTS `idx_nl_origem` ON `newsletter` (`origem`);

-- ── 2. Tabela de interesses literários por usuário ────────────────
--  Incrementada cada vez que um usuário logado termina/avança em
--  um post — permite segmentação de campanhas por categoria.

CREATE TABLE IF NOT EXISTS `usuario_interesses` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `usuario_id`   INT UNSIGNED  NOT NULL,
  `categoria`    VARCHAR(80)   NOT NULL  COMMENT 'Valor do campo categoria nos posts',
  `contagem`     INT UNSIGNED  NOT NULL DEFAULT 1
                   COMMENT 'Nº de posts lidos nesta categoria',
  `ultima_vista` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                   ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY  `uq_usuario_cat` (`usuario_id`, `categoria`),
  KEY         `idx_cat`        (`categoria`),
  KEY         `idx_ultima`     (`ultima_vista`),

  CONSTRAINT `fk_int_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Rastreia categorias de posts mais lidas por usuário';
