-- ================================================================
-- MIGRAÇÃO DE ACESSO AO LEITOR
-- Execute no phpMyAdmin antes de testar o leitor
-- ================================================================

-- 1. Adicionar coluna gratuito na tabela livros
ALTER TABLE `livros`
  ADD COLUMN IF NOT EXISTS `gratuito`  TINYINT(1) NOT NULL DEFAULT 0 AFTER `ativo`,
  ADD COLUMN IF NOT EXISTS `formato`   ENUM('html','epub') NOT NULL DEFAULT 'html' AFTER `pasta_conteudo`,
  ADD COLUMN IF NOT EXISTS `destaque`  TINYINT(1) NOT NULL DEFAULT 0 AFTER `gratuito`,
  ADD COLUMN IF NOT EXISTS `novo`      TINYINT(1) NOT NULL DEFAULT 0 AFTER `destaque`,
  ADD COLUMN IF NOT EXISTS `badges`    VARCHAR(200) DEFAULT NULL AFTER `novo`,
  ADD COLUMN IF NOT EXISTS `tipo`      ENUM('livro','conto') NOT NULL DEFAULT 'livro' AFTER `slug`,
  ADD COLUMN IF NOT EXISTS `genero`    VARCHAR(80) DEFAULT NULL AFTER `tipo`,
  ADD COLUMN IF NOT EXISTS `subtitulo` VARCHAR(200) DEFAULT NULL AFTER `titulo`,
  ADD COLUMN IF NOT EXISTS `data_pub`  DATE DEFAULT NULL AFTER `sinopse`,
  ADD COLUMN IF NOT EXISTS `ordem`     TINYINT UNSIGNED NOT NULL DEFAULT 99 AFTER `data_pub`,
  ADD COLUMN IF NOT EXISTS `link_amazon` VARCHAR(300) DEFAULT NULL AFTER `ordem`;

-- 2. Marcar livros de teste/demonstração como gratuitos
--    (ajuste os slugs conforme necessário)
-- UPDATE livros SET gratuito=1 WHERE slug IN ('lumen', 'o-farol-do-afogado');

-- 3. Verificar se o slug usado no leitor bate com o da tabela
--    (execute para ver o estado atual)
SELECT slug, titulo, gratuito, formato, total_capitulos, pasta_conteudo 
FROM livros 
ORDER BY titulo;

-- ================================================================
-- CORREÇÕES ADICIONAIS v2 — execute após a migração anterior
-- ================================================================

-- Garantir que os contos/livros gratuitos existem no banco com os slugs corretos
-- (ajuste os slugs conforme o que você quer marcar como gratuito)

-- Marcar contos como tipo='conto' e livros como tipo='livro'
UPDATE `livros` SET `tipo` = 'conto'
WHERE slug IN ('o-farol-do-afogado', 'o-quarto-das-moscas', 'linhas-e-agulhas');

-- Tornar contos gratuitos (padrão — ajuste conforme queira)
-- Remova o comentário das linhas abaixo para ativar:
-- UPDATE `livros` SET `gratuito` = 1 WHERE slug IN ('o-farol-do-afogado','o-quarto-das-moscas','linhas-e-agulhas');

-- Inserir o-farol-do-afogado se não existir ainda
INSERT IGNORE INTO `livros` (slug, tipo, titulo, total_capitulos, pasta_conteudo, gratuito, ativo, preco)
VALUES ('o-farol-do-afogado', 'conto', 'O Farol do Afogado', 1, 'livros-conteudo/o-farol-do-afogado/', 1, 1, 0.00);

INSERT IGNORE INTO `livros` (slug, tipo, titulo, total_capitulos, pasta_conteudo, gratuito, ativo, preco)
VALUES ('o-quarto-das-moscas', 'conto', 'O Quarto das Moscas', 1, 'livros-conteudo/o-quarto-das-moscas/', 1, 1, 0.00);

INSERT IGNORE INTO `livros` (slug, tipo, titulo, total_capitulos, pasta_conteudo, gratuito, ativo, preco)
VALUES ('linhas-e-agulhas', 'conto', 'Linhas e Agulhas', 1, 'livros-conteudo/linhas-e-agulhas/', 1, 1, 0.00);

-- Verificar resultado final
SELECT slug, tipo, titulo, gratuito, formato, total_capitulos, ativo FROM livros ORDER BY tipo, titulo;
