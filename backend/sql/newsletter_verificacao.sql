-- ================================================================
-- MIGRAÇÃO: Verificação de e-mail para newsletter
-- Execute este SQL no phpMyAdmin após o deploy
-- ================================================================

-- Adicionar colunas de verificação na tabela newsletter
ALTER TABLE `newsletter`
  ADD COLUMN IF NOT EXISTS `nome`              VARCHAR(120) DEFAULT NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `status`            ENUM('pendente','ativo','descadastrado') NOT NULL DEFAULT 'pendente',
  ADD COLUMN IF NOT EXISTS `origem`            VARCHAR(80)  DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `token_verificacao` VARCHAR(64)  DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `token_expira`      DATETIME     DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `descad_em`         DATETIME     DEFAULT NULL,
  ADD INDEX IF NOT EXISTS  `idx_token` (`token_verificacao`);

-- Marcar inscritos existentes como já confirmados (ativo)
-- para não perder a base atual
UPDATE `newsletter` SET `status` = 'ativo' WHERE `status` = 'ativo' OR `token_verificacao` IS NULL;

