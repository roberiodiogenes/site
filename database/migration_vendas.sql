-- ================================================================
-- migration_vendas.sql
-- Promoções com data, gratuito temporário, plano trimestral
-- e atualização dos preços dos planos.
--
-- Execute no phpMyAdmin: banco roberio_site → aba SQL → executar tudo.
-- Todos os comandos são idempotentes (IF NOT EXISTS / INSERT IGNORE).
-- ================================================================

-- ────────────────────────────────────────────────────────────────
-- 1. Colunas de promoção e gratuito temporário em livros
-- ────────────────────────────────────────────────────────────────

ALTER TABLE `livros`
  ADD COLUMN IF NOT EXISTS `promo_ate`    DATETIME NULL DEFAULT NULL
    COMMENT 'Fim da promoção — preco_promocao ativo enquanto NOW() < promo_ate' AFTER `preco_promocao`,
  ADD COLUMN IF NOT EXISTS `gratuito_ate` DATETIME NULL DEFAULT NULL
    COMMENT 'Livro gratuito temporariamente até esta data' AFTER `promo_ate`;

-- ────────────────────────────────────────────────────────────────
-- 2. Planos de assinatura — preços corretos + trimestral
-- ────────────────────────────────────────────────────────────────

-- Atualiza preços dos planos existentes
UPDATE `planos` SET preco = 29.90,  duracao_dias = 30  WHERE slug = 'mensal';
UPDATE `planos` SET preco = 119.90, duracao_dias = 180 WHERE slug = 'semestral';
UPDATE `planos` SET preco = 154.80, duracao_dias = 365 WHERE slug = 'anual';

-- Garante descrições corretas
UPDATE `planos` SET
  nome      = 'Plano Mensal',
  descricao = 'Acesso completo à biblioteca por 30 dias. R$29,90/mês.'
WHERE slug = 'mensal';

UPDATE `planos` SET
  nome      = 'Plano Semestral',
  descricao = 'Acesso completo à biblioteca por 6 meses. Equivale a R$19,98/mês.'
WHERE slug = 'semestral';

UPDATE `planos` SET
  nome      = 'Plano Anual',
  descricao = 'Acesso completo à biblioteca por 1 ano. Equivale a R$12,90/mês.'
WHERE slug = 'anual';

-- Insere plano trimestral (se ainda não existir)
INSERT IGNORE INTO `planos` (slug, nome, descricao, preco, duracao_dias, ativo) VALUES
  ('trimestral', 'Plano Trimestral',
   'Acesso completo à biblioteca por 3 meses. Equivale a R$23,30/mês.',
   69.90, 90, 1);

-- ────────────────────────────────────────────────────────────────
-- 3. Garantir que a tabela presentes existe
--    (caso o banco seja anterior à versão que a incluiu)
-- ────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `presentes` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `comprador_id`     INT UNSIGNED  NOT NULL,
  `livro_slug`       VARCHAR(100)  NOT NULL,
  `email_presenteado` VARCHAR(255) NOT NULL,
  `nome_presenteado` VARCHAR(150)  DEFAULT NULL,
  `dedicatoria`      TEXT          DEFAULT NULL,
  `preco_pago`       DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
  `token_acesso`     VARCHAR(100)  NOT NULL,
  `status`           ENUM('pendente','aprovado','resgatado','cancelado') NOT NULL DEFAULT 'pendente',
  `ref_externa`      VARCHAR(200)  DEFAULT NULL,
  `gateway`          VARCHAR(50)   DEFAULT NULL,
  `resgatado_por`    INT UNSIGNED  DEFAULT NULL,
  `resgatado_em`     DATETIME      DEFAULT NULL,
  `criado_em`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token`        (`token_acesso`),
  KEY `idx_comprador`          (`comprador_id`),
  KEY `idx_email_presenteado`  (`email_presenteado`),
  KEY `idx_status`             (`status`),
  CONSTRAINT `fk_pres_comprador` FOREIGN KEY (`comprador_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────
-- 4. Garantir que a tabela carrinhos existe
-- ────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `carrinhos` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`   INT UNSIGNED NOT NULL,
  `itens_json`   TEXT         NOT NULL DEFAULT '[]',
  `em_checkout`  TINYINT(1)   NOT NULL DEFAULT 0,
  `checkout_em`  DATETIME     DEFAULT NULL,
  `atualizado_em` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario` (`usuario_id`),
  CONSTRAINT `fk_carr_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
