-- ================================================================
-- ROBÉRIO DIÓGENES — agendamento_migracao.sql
-- Execute no phpMyAdmin: banco roberio_site → SQL → Executar
-- ================================================================

-- 1. Adicionar 'agendado' ao ENUM de status
ALTER TABLE `posts`
  MODIFY COLUMN `status`
    ENUM('rascunho','publicado','oculto','agendado')
    NOT NULL DEFAULT 'rascunho';

-- 2. Índice para buscar posts agendados eficientemente
ALTER TABLE `posts`
  ADD KEY IF NOT EXISTS `idx_agendado` (`status`, `publicado_em`);

-- ================================================================
-- VERIFICAR: SELECT id, slug, titulo, status, publicado_em
--            FROM posts WHERE status = 'agendado';
-- ================================================================
