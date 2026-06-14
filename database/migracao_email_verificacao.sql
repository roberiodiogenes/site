-- ================================================================
-- ROBÉRIO DIÓGENES — Migração: e-mail de boas-vindas + rastreamento
-- Execute no phpMyAdmin do HostGator (aba SQL)
-- Compatível com MySQL 5.7
-- Se algum ALTER TABLE der erro "Duplicate column", ignore — já existe.
-- ================================================================

-- 1. Colunas de confirmação de e-mail na tabela de usuários
ALTER TABLE `usuarios`
  ADD COLUMN `token_confirmacao`  VARCHAR(64) DEFAULT NULL AFTER `verificado`;

ALTER TABLE `usuarios`
  ADD COLUMN `token_expira_em`    DATETIME    DEFAULT NULL AFTER `token_confirmacao`;

ALTER TABLE `usuarios`
  ADD COLUMN `email_confirmado_em` DATETIME   DEFAULT NULL AFTER `token_expira_em`;

ALTER TABLE `usuarios`
  ADD INDEX `idx_token_confirmacao` (`token_confirmacao`);

-- 2. Tabela de rastreamento de cliques de e-mail
CREATE TABLE IF NOT EXISTS `email_cliques` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `usuario_id`   INT UNSIGNED  NOT NULL,
  `acao`         VARCHAR(60)   NOT NULL COMMENT 'ex: baixar_conto, visitar_biblioteca',
  `ip`           VARCHAR(45)   DEFAULT NULL,
  `user_agent`   VARCHAR(300)  DEFAULT NULL,
  `clicado_em`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_acao`    (`acao`),
  KEY `idx_data`    (`clicado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
