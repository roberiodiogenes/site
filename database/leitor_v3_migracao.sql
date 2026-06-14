-- ================================================================
-- MIGRAÇÃO LEITOR v3 — Execute no phpMyAdmin (aba SQL)
-- ================================================================

-- Adiciona coluna CFI (posição exata do epub.js) em leitor_progresso
-- MySQL 5.x: ADD COLUMN sem IF NOT EXISTS. Se der erro "Duplicate column", a coluna já existe — ignore.
ALTER TABLE `leitor_progresso`
  ADD COLUMN `cfi` TEXT DEFAULT NULL AFTER `livro_slug`;

ALTER TABLE `leitor_progresso`
  ADD COLUMN `tempo_total_min` INT UNSIGNED NOT NULL DEFAULT 0;

-- Adiciona coluna CFI Range (range do highlight epub.js) em leitor_marcacoes
ALTER TABLE `leitor_marcacoes`
  ADD COLUMN `cfi_range` TEXT DEFAULT NULL AFTER `capitulo`;

-- Cria tabela de erros ortográficos reportados (ausente no banco de produção)
CREATE TABLE IF NOT EXISTS `leitor_erros` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED NOT NULL,
  `livro_slug`  VARCHAR(100) NOT NULL,
  `cfi`         VARCHAR(500) DEFAULT NULL,
  `trecho`      TEXT         DEFAULT NULL,
  `descricao`   TEXT         DEFAULT NULL,
  `resolvido`   TINYINT(1)   NOT NULL DEFAULT 0,
  `criado_em`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_livro_resolvido` (`livro_slug`, `resolvido`),
  KEY `idx_usuario`         (`usuario_id`),
  CONSTRAINT `fk_erro_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
