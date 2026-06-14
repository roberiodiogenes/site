-- =====================================================================
-- ROBÉRIO DIÓGENES — pdf_cliques_migracao.sql
-- Tabela de rastreamento de cliques vindos de dentro de PDFs enviados por e-mail.
-- Execute no phpMyAdmin do HostGator (banco fra46117_roberio_site).
-- =====================================================================

CREATE TABLE IF NOT EXISTS `pdf_cliques` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED  DEFAULT NULL  COMMENT 'NULL = clique anônimo (não autenticado)',
  `pdf_nome`   VARCHAR(100)  NOT NULL      COMMENT 'Identificador do PDF / origem (ex: o-colecionador-de-paginas)',
  `ip`         VARCHAR(45)   DEFAULT NULL  COMMENT 'IP do clicante',
  `user_agent` VARCHAR(300)  DEFAULT NULL,
  `criado_em`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_pdf_nome`  (`pdf_nome`),
  KEY `idx_usuario`   (`usuario_id`),
  KEY `idx_criado`    (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cliques em links de PDFs enviados por e-mail (rastreamento de engajamento)';
