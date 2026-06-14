-- ================================================================
-- MIGRAÇÃO LEITOR v2.0 — Execute no phpMyAdmin após o deploy
-- ================================================================

-- ── Progresso de leitura (inclui lazy loading + ePub) ──────────
CREATE TABLE IF NOT EXISTS `leitura_progresso` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`       INT UNSIGNED NOT NULL,
  `livro_slug`       VARCHAR(120) NOT NULL,
  `capitulo_atual`   SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `posicao_scroll`   INT UNSIGNED NOT NULL DEFAULT 0,
  `percentual`       DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  `total_capitulos`  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `atualizado_em`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_usuario_livro` (`usuario_id`, `livro_slug`),
  KEY `idx_slug` (`livro_slug`),
  KEY `idx_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Conquistas / Medalhas ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `leitura_conquistas` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`      INT UNSIGNED NOT NULL,
  `livro_slug`      VARCHAR(120) NOT NULL,
  `marco`           TINYINT UNSIGNED NOT NULL COMMENT '25, 50, 75, 90, 100',
  `medalha`         VARCHAR(10)  NOT NULL,
  `titulo`          VARCHAR(80)  NOT NULL,
  `conquistado_em`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_conquista` (`usuario_id`, `livro_slug`, `marco`),
  KEY `idx_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Anotações do leitor ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `leitura_anotacoes` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`  INT UNSIGNED NOT NULL,
  `livro_slug`  VARCHAR(120) NOT NULL,
  `capitulo`    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `texto`       TEXT NOT NULL,
  `cor`         VARCHAR(20) NOT NULL DEFAULT '#FFD700',
  `criado_em`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_usuario_livro` (`usuario_id`, `livro_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Marcações (highlights) ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS `leitura_marcacoes` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`  INT UNSIGNED NOT NULL,
  `livro_slug`  VARCHAR(120) NOT NULL,
  `capitulo`    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `trecho`      VARCHAR(1000) NOT NULL,
  `cor`         ENUM('amarela','verde','rosa','azul') NOT NULL DEFAULT 'amarela',
  `nota`        VARCHAR(500) DEFAULT NULL,
  `criado_em`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_usuario_livro` (`usuario_id`, `livro_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Preferências tipográficas ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `leitura_preferencias` (
  `usuario_id`       INT UNSIGNED PRIMARY KEY,
  `fonte`            ENUM('serifada','classica','sans','manuscrito') NOT NULL DEFAULT 'serifada',
  `tamanho_fonte`    TINYINT UNSIGNED NOT NULL DEFAULT 18,
  `fundo_leitura`    ENUM('branco','bege','cinza','preto') NOT NULL DEFAULT 'bege',
  `largura_coluna`   ENUM('estreita','media','larga') NOT NULL DEFAULT 'media',
  `altura_linha`     DECIMAL(3,1) NOT NULL DEFAULT 1.8,
  `ranking_opt_in`   TINYINT(1) NOT NULL DEFAULT 0,
  `atualizado_em`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Erros ortográficos reportados ─────────────────────────────
CREATE TABLE IF NOT EXISTS `leitura_erros_reportados` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`   INT UNSIGNED DEFAULT NULL,
  `livro_slug`   VARCHAR(120) NOT NULL,
  `capitulo`     SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `trecho`       VARCHAR(500) NOT NULL,
  `tipo`         ENUM('ortografia','gramatica','pontuacao','digitacao','outro') NOT NULL DEFAULT 'ortografia',
  `observacao`   VARCHAR(500) DEFAULT NULL,
  `resolvido`    TINYINT(1) NOT NULL DEFAULT 0,
  `criado_em`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_livro` (`livro_slug`),
  KEY `idx_resolvido` (`resolvido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Feedback de conclusão ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `leitura_feedback` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`  INT UNSIGNED NOT NULL,
  `livro_slug`  VARCHAR(120) NOT NULL,
  `texto`       TEXT DEFAULT NULL,
  `nota`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `criado_em`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_feedback` (`usuario_id`, `livro_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Coluna formato na tabela livros (ePub ou HTML) ─────────────
ALTER TABLE `livros`
  ADD COLUMN IF NOT EXISTS `formato` ENUM('html','epub') NOT NULL DEFAULT 'html' AFTER `pasta_conteudo`;

-- ── Controle de lembretes enviados (evita spam) ───────────────
CREATE TABLE IF NOT EXISTS `leitura_lembretes_enviados` (
  `usuario_id`  INT UNSIGNED NOT NULL,
  `livro_slug`  VARCHAR(120) NOT NULL,
  `enviado_em`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`usuario_id`, `livro_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Notas SQL para o admin ver erros reportados ────────────────
-- SELECT e.*, u.nome, u.email
-- FROM leitura_erros_reportados e
-- LEFT JOIN usuarios u ON u.id = e.usuario_id
-- WHERE e.resolvido = 0
-- ORDER BY e.criado_em DESC;
