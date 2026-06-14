-- =====================================================================
-- ROBÉRIO DIÓGENES — leitor_tabelas_faltando.sql
-- Cria as tabelas que estão faltando em produção e causando erro 500
-- no leitor: conquistas e notas do autor.
-- Execute no phpMyAdmin (banco fra46117_roberio_site).
-- =====================================================================

-- ── Conquistas / Medalhas de leitura ─────────────────────────────
CREATE TABLE IF NOT EXISTS `leitura_conquistas` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`      INT UNSIGNED NOT NULL,
  `livro_slug`      VARCHAR(120) NOT NULL,
  `marco`           TINYINT UNSIGNED NOT NULL COMMENT '25, 50, 75, 90, 100',
  `medalha`         VARCHAR(10)  NOT NULL,
  `titulo`          VARCHAR(80)  NOT NULL,
  `conquistado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_conquista` (`usuario_id`, `livro_slug`, `marco`),
  KEY `idx_usuario`          (`usuario_id`),
  KEY `idx_livro`            (`livro_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Notas do autor (vinculadas a posição CFI no epub.js) ─────────
CREATE TABLE IF NOT EXISTS `leitor_notas_autor` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `livro_slug`  VARCHAR(100) NOT NULL,
  `cfi`         VARCHAR(500) NOT NULL COMMENT 'Posição CFI onde a nota aparece',
  `tipo`        ENUM('bastidor','personagem','cena','curiosidade','outro') NOT NULL DEFAULT 'outro',
  `titulo`      VARCHAR(200) DEFAULT NULL,
  `conteudo`    TEXT         NOT NULL,
  `ativo`       TINYINT(1)   NOT NULL DEFAULT 1,
  `criado_em`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_livro`    (`livro_slug`),
  KEY `idx_ativo`    (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
