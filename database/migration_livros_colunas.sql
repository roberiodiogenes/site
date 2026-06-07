-- ================================================================
-- migration_livros_colunas.sql
-- Adiciona TODAS as colunas que o painel admin usa mas que podem
-- estar faltando no banco dependendo de qual schema foi usado.
--
-- SEGURO para executar mesmo que algumas colunas já existam:
-- o IF NOT EXISTS evita erros de "Duplicate column name".
-- Execute no phpMyAdmin: selecione o banco → aba SQL → execute tudo.
-- ================================================================

-- ────────────────────────────────────────────────────────────────
-- Colunas que costumam faltar dependendo da versão do banco usada
-- ────────────────────────────────────────────────────────────────

ALTER TABLE `livros`
  ADD COLUMN IF NOT EXISTS `pasta_conteudo`  VARCHAR(200)          DEFAULT NULL              AFTER `capa_img`,
  ADD COLUMN IF NOT EXISTS `tipo`            ENUM('livro','conto') NOT NULL DEFAULT 'livro'  AFTER `slug`,
  ADD COLUMN IF NOT EXISTS `subtitulo`       VARCHAR(200)          DEFAULT NULL              AFTER `titulo`,
  ADD COLUMN IF NOT EXISTS `genero`          VARCHAR(80)           DEFAULT NULL              AFTER `subtitulo`,
  ADD COLUMN IF NOT EXISTS `sinopse`         TEXT                  DEFAULT NULL              AFTER `genero`,
  ADD COLUMN IF NOT EXISTS `capa_img`        VARCHAR(200)          DEFAULT NULL              AFTER `sinopse`,
  ADD COLUMN IF NOT EXISTS `preco`           DECIMAL(8,2)          DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `preco_promocao`  DECIMAL(8,2)          DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `total_capitulos` SMALLINT UNSIGNED     DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `ativo`           TINYINT(1)            NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `destaque`        TINYINT(1)            NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `gratuito`        TINYINT(1)            NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `novo`            TINYINT(1)            NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `badges`          VARCHAR(200)          DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `link_amazon`     VARCHAR(500)          DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `data_pub`        DATE                  DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `ordem`           SMALLINT UNSIGNED     NOT NULL DEFAULT 99;

-- Índices (ignoram erro se já existirem no MariaDB)
ALTER TABLE `livros`
  ADD KEY IF NOT EXISTS `idx_tipo`     (`tipo`),
  ADD KEY IF NOT EXISTS `idx_genero`   (`genero`),
  ADD KEY IF NOT EXISTS `idx_destaque` (`destaque`),
  ADD KEY IF NOT EXISTS `idx_ordem`    (`ordem`);

-- Garante que livros existentes tenham tipo definido
UPDATE `livros` SET `tipo` = 'livro' WHERE `tipo` IS NULL OR `tipo` = '';
