-- ================================================================
-- migration_lista_espera.sql
-- Sistema de pré-lançamento: coleta leads aquecidos antes do lançamento
-- e entrega um brinde imediato (trecho, nota do autor, etc.)
-- Execute no phpMyAdmin após banco_completo.sql.
-- ================================================================

-- ── Campanhas de pré-lançamento ───────────────────────────────────
CREATE TABLE IF NOT EXISTS `pre_lancamentos` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `slug`             VARCHAR(100)  NOT NULL,
  `titulo`           VARCHAR(200)  NOT NULL,
  `subtitulo`        VARCHAR(300)  DEFAULT NULL,
  `descricao`        TEXT          DEFAULT NULL   COMMENT 'Apresentação do livro para a página de espera',
  `capa_img`         VARCHAR(300)  DEFAULT NULL   COMMENT 'Caminho relativo da imagem de capa',
  `data_lancamento`  DATE          DEFAULT NULL   COMMENT 'Data prevista de lançamento (exibe countdown)',
  `brinde_titulo`    VARCHAR(200)  DEFAULT NULL   COMMENT 'Ex: "Primeiro capítulo gratuito"',
  `brinde_html`      LONGTEXT      DEFAULT NULL   COMMENT 'Conteúdo HTML do brinde (trecho, nota do autor, etc.)',
  `ativo`            TINYINT(1)    NOT NULL DEFAULT 1,
  `lancado`          TINYINT(1)    NOT NULL DEFAULT 0  COMMENT '1 após disparar o email de lançamento',
  `criado_em`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Leads da lista de espera ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pre_lancamento_leads` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lancamento_id`   INT UNSIGNED NOT NULL,
  `nome`            VARCHAR(120) DEFAULT NULL,
  `email`           VARCHAR(255) NOT NULL,
  `ip_hash`         VARCHAR(64)  NOT NULL,
  `brinde_enviado`  TINYINT(1)   NOT NULL DEFAULT 0,
  `lancamento_enviado` TINYINT(1) NOT NULL DEFAULT 0,
  `inscrito_em`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lanc_email` (`lancamento_id`, `email`),
  KEY `idx_ll_lancamento` (`lancamento_id`),
  KEY `idx_ll_brinde` (`brinde_enviado`),
  CONSTRAINT `fk_ll_lancamento`
    FOREIGN KEY (`lancamento_id`) REFERENCES `pre_lancamentos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
