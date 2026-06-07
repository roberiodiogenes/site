-- ================================================================
--  ROBÉRIO DIÓGENES — migration_crons.sql
--  Suporte aos cron jobs: abandono de carrinho + lembrete de leitura
--  Execute no phpMyAdmin: banco roberio_site → aba SQL
-- ================================================================

-- ── 1. Colunas de lembrete na tabela carrinhos ────────────────────
--  Rastreia se o e-mail de recuperação já foi enviado (evita re-envio)

ALTER TABLE `carrinhos`
  ADD COLUMN IF NOT EXISTS `lembrete_env` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'E-mail de carrinho abandonado já enviado'
    AFTER `em_checkout`,
  ADD COLUMN IF NOT EXISTS `lembrete_em`  DATETIME   NULL DEFAULT NULL
    COMMENT 'Quando o lembrete foi enviado'
    AFTER `lembrete_env`;

-- Índice para o cron (filtra lembrete_env=0 rapidamente)
CREATE INDEX IF NOT EXISTS `idx_carr_lembrete`
  ON `carrinhos` (`lembrete_env`, `atualizado_em`);

-- ── 2. Tabela de lembretes de leitura enviados ────────────────────
--  Garante que cada usuário receba no máximo 1 lembrete por livro
--  a cada 14 dias (o cron verifica antes de enviar)

CREATE TABLE IF NOT EXISTS `leitor_lembretes_enviados` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED NOT NULL,
  `livro_slug`  VARCHAR(100) NOT NULL,
  `enviado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lembrete` (`usuario_id`, `livro_slug`),
  KEY `idx_enviado_em`     (`enviado_em`),
  CONSTRAINT `fk_lem_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Throttle de 14 dias para lembretes de leitura por usuário/livro';
