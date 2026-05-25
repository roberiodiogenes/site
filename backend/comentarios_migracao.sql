-- ================================================================
-- ROBÉRIO DIÓGENES — comentarios_migracao.sql
-- Adapta a tabela comentarios existente para suportar:
--   1. Comentários de usuários logados (blog e livros)
--   2. Campo referencia genérico (substitui livro_slug)
--   3. Campo tipo: 'livro' | 'blog'
--
-- Execute no phpMyAdmin → banco roberio_site → aba SQL
-- ================================================================

-- 1. Adiciona colunas novas (IF NOT EXISTS via procedure segura)
ALTER TABLE `comentarios`
  ADD COLUMN IF NOT EXISTS `usuario_id`  INT UNSIGNED DEFAULT NULL          AFTER `id`,
  ADD COLUMN IF NOT EXISTS `referencia`  VARCHAR(100) DEFAULT NULL          AFTER `usuario_id`,
  ADD COLUMN IF NOT EXISTS `tipo`        ENUM('livro','blog') DEFAULT 'livro' AFTER `referencia`;

-- 2. Migra dados existentes: livro_slug → referencia com tipo='livro'
UPDATE `comentarios`
SET `referencia` = `livro_slug`, `tipo` = 'livro'
WHERE `referencia` IS NULL AND `livro_slug` != '';

-- 3. Adiciona índices para performance
ALTER TABLE `comentarios`
  ADD KEY IF NOT EXISTS `idx_referencia_tipo` (`referencia`, `tipo`, `aprovado`),
  ADD KEY IF NOT EXISTS `idx_usuario_id`      (`usuario_id`);

-- 4. Adiciona FK opcional (só se quiser — comentários anônimos continuam funcionando)
-- ALTER TABLE `comentarios`
--   ADD CONSTRAINT `fk_coment_usuario`
--   FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL;

-- ================================================================
-- Resumo das mudanças:
--   ✓ usuario_id — NULL = comentário anônimo (legado), preenchido = usuário logado
--   ✓ referencia — slug do post ou livro (ex: 'post-01', 'lumen')
--   ✓ tipo       — 'livro' (padrão) ou 'blog'
--   ✓ Dados existentes preservados — livro_slug antigo continua intacto
-- ================================================================
