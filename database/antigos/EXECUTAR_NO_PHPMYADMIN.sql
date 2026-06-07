-- ================================================================
-- Execute este arquivo no phpMyAdmin do banco roberio_site
-- Menu: SQL → cole o conteúdo → Executar
-- ================================================================

-- 1. Coluna html_externo na tabela posts
ALTER TABLE `posts`
  ADD COLUMN IF NOT EXISTS `html_externo` VARCHAR(300) DEFAULT NULL
  COMMENT 'Caminho para arquivo HTML externo (ex: blog/post-11.html)';

-- 2. Garantir que newsletter tem colunas de preferência
ALTER TABLE `newsletter`
  ADD COLUMN IF NOT EXISTS `pref_bastidores` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `pref_reflexao`   TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `pref_escritor`   TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `pref_livros`     TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `nome`            VARCHAR(120) DEFAULT NULL;

-- 3. Tabela newsletter_posts (se não existir)
CREATE TABLE IF NOT EXISTS `newsletter_posts` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id`      INT UNSIGNED NOT NULL,
  `enviado_em`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_envios` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_post` (`post_id`),
  CONSTRAINT `fk_nlpost_post` FOREIGN KEY (`post_id`)
    REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificar resultado:
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_NAME = 'posts' AND TABLE_SCHEMA = DATABASE();
