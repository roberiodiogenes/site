-- ================================================================
-- migration_bio_v2.sql
-- Rastreamento de cliques na bio + novos links padrão
-- Execute no phpMyAdmin após migration_bio.sql.
-- ================================================================

-- ── Tabela de cliques ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `bio_clicks` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `link_id`    INT UNSIGNED          DEFAULT NULL COMMENT 'ID em bio_links (NULL = link dinâmico)',
  `link_slug`  VARCHAR(100)          DEFAULT NULL COMMENT 'Slug para links dinâmicos (ex: ultimo-post)',
  `origem`     VARCHAR(300)          DEFAULT NULL COMMENT 'utm_source, rede social ou referrer',
  `ip_hash`    VARCHAR(64)  NOT NULL,
  `clicado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bc_link_id`  (`link_id`),
  KEY `idx_bc_link_slug`(`link_slug`),
  KEY `idx_bc_clicado`  (`clicado_em`),
  KEY `idx_bc_origem`   (`origem`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Novos links padrão (newsletter e imprensa) ────────────────────
INSERT IGNORE INTO `bio_links` (`titulo`, `subtitulo`, `url`, `icone`, `tipo`, `ordem`) VALUES
  ('Newsletter Gratuita',   'Novos posts e capítulos exclusivos no e-mail', '/blog.html#newsletter', 'fa-envelope-open-text', 'link', 7),
  ('Contato para Imprensa', 'Entrevistas, resenhas e parcerias',            '/contato.html?assunto=imprensa', 'fa-newspaper', 'link', 8);
