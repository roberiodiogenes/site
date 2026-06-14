-- ================================================================
-- PATCH: Atualizar formato e arquivo_epub na tabela livros
-- Execute no phpMyAdmin após ter rodado leitor_acesso_migracao.sql
-- ================================================================

-- 1. Garantir que a coluna arquivo_epub existe (pode já existir)
ALTER TABLE `livros`
  ADD COLUMN IF NOT EXISTS `arquivo_epub` VARCHAR(200) DEFAULT NULL;

-- 2. Atualizar formato para 'epub' nos livros que têm arquivo epub
UPDATE `livros` SET `formato` = 'epub' WHERE `arquivo_epub` IS NOT NULL AND `arquivo_epub` != '';

-- 3. Verificar o estado atual — rode este SELECT para confirmar
SELECT slug, titulo, formato, arquivo_epub, pasta_conteudo
FROM livros
ORDER BY titulo;

-- ================================================================
-- NOTA IMPORTANTE sobre o caminho do epub:
-- O campo arquivo_epub guarda apenas o NOME DO ARQUIVO (ex: lumen-capitulo-1.epub)
-- O arquivo deve estar dentro da pasta_conteudo do livro.
-- Exemplo: pasta_conteudo = 'livros-conteudo/lumen/'
--          arquivo_epub   = 'lumen-capitulo-1.epub'
-- Caminho completo no servidor: livros-conteudo/lumen/lumen-capitulo-1.epub
-- ================================================================
