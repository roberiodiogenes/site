-- ================================================================
-- ROBÉRIO DIÓGENES — atualizacao_contos_marketing.sql
-- Execute no phpMyAdmin APÓS o banco_completo.sql já existir.
-- ================================================================

-- ── 1. Adiciona coluna 'tipo' e 'genero' na tabela livros ────────
-- Permite distinguir livros de contos e filtrar por gênero
ALTER TABLE `livros`
  ADD COLUMN IF NOT EXISTS `tipo`         ENUM('livro','conto') NOT NULL DEFAULT 'livro'  AFTER `slug`,
  ADD COLUMN IF NOT EXISTS `genero`       VARCHAR(60)           DEFAULT NULL              AFTER `tipo`,
  ADD COLUMN IF NOT EXISTS `subtitulo`    VARCHAR(200)          DEFAULT NULL              AFTER `titulo`,
  ADD COLUMN IF NOT EXISTS `destaque`     TINYINT(1)            NOT NULL DEFAULT 0        AFTER `ativo`,
  ADD COLUMN IF NOT EXISTS `gratuito`     TINYINT(1)            NOT NULL DEFAULT 0        AFTER `destaque`,
  ADD COLUMN IF NOT EXISTS `novo`         TINYINT(1)            NOT NULL DEFAULT 0        AFTER `gratuito`,
  ADD COLUMN IF NOT EXISTS `badges`       VARCHAR(200)          DEFAULT NULL              AFTER `novo`,
  ADD COLUMN IF NOT EXISTS `link_amazon`  VARCHAR(500)          DEFAULT NULL              AFTER `badges`,
  ADD COLUMN IF NOT EXISTS `data_pub`     DATE                  DEFAULT NULL              AFTER `link_amazon`,
  ADD COLUMN IF NOT EXISTS `ordem`        SMALLINT UNSIGNED     NOT NULL DEFAULT 99       AFTER `data_pub`;

-- Índices para performance nas queries da biblioteca
ALTER TABLE `livros`
  ADD KEY IF NOT EXISTS `idx_tipo`     (`tipo`),
  ADD KEY IF NOT EXISTS `idx_genero`   (`genero`),
  ADD KEY IF NOT EXISTS `idx_destaque` (`destaque`),
  ADD KEY IF NOT EXISTS `idx_ordem`    (`ordem`);

-- Atualiza livros existentes com o tipo 'livro'
UPDATE `livros` SET `tipo` = 'livro' WHERE `tipo` IS NULL OR `tipo` = '';

-- ── 2. Tabela de campanhas de marketing ──────────────────────────
CREATE TABLE IF NOT EXISTS `campanhas` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nome`             VARCHAR(200)  NOT NULL,
  `tipo`             ENUM(
                       'lancamento',       -- novo livro/conto
                       'promocao',         -- desconto por tempo limitado
                       'newsletter',       -- boletim geral
                       'reengajamento',    -- leitores inativos
                       'boas_vindas',      -- novo cadastro
                       'recompensa',       -- fidelidade/aniversário
                       'destaque',         -- destacar obra no catálogo
                       'outro'
                     ) NOT NULL DEFAULT 'newsletter',
  `assunto_email`    VARCHAR(255)  DEFAULT NULL,
  `corpo_html`       LONGTEXT      DEFAULT NULL,
  `corpo_texto`      TEXT          DEFAULT NULL,
  `segmento`         ENUM(
                       'todos',            -- toda a base
                       'newsletter',       -- só inscritos na newsletter
                       'compradores',      -- quem comprou algum livro
                       'assinantes',       -- assinantes ativos
                       'inativos_30',      -- não acessam há 30 dias
                       'inativos_90',      -- não acessam há 90 dias
                       'sem_compra',       -- cadastrados mas nunca compraram
                       'personalizado'     -- IDs manuais
                     ) NOT NULL DEFAULT 'todos',
  `status`           ENUM('rascunho','agendada','enviando','enviada','cancelada')
                     NOT NULL DEFAULT 'rascunho',
  `total_destinat`   INT UNSIGNED  NOT NULL DEFAULT 0,
  `total_enviados`   INT UNSIGNED  NOT NULL DEFAULT 0,
  `total_abertos`    INT UNSIGNED  NOT NULL DEFAULT 0,
  `total_cliques`    INT UNSIGNED  NOT NULL DEFAULT 0,
  `agendado_para`    DATETIME      DEFAULT NULL,
  `iniciado_em`      DATETIME      DEFAULT NULL,
  `concluido_em`     DATETIME      DEFAULT NULL,
  `criado_por`       INT UNSIGNED  DEFAULT NULL,
  `criado_em`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_status`      (`status`),
  KEY `idx_tipo`        (`tipo`),
  KEY `idx_agendado`    (`agendado_para`),
  CONSTRAINT `fk_camp_admin` FOREIGN KEY (`criado_por`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Log de envio de e-mails por campanha ──────────────────────
CREATE TABLE IF NOT EXISTS `campanhas_envios` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `campanha_id`     INT UNSIGNED    NOT NULL,
  `email`           VARCHAR(255)    NOT NULL,
  `usuario_id`      INT UNSIGNED    DEFAULT NULL,   -- NULL = lead/newsletter sem conta
  `status`          ENUM('pendente','enviado','erro','descadastrou') NOT NULL DEFAULT 'pendente',
  `aberto_em`       DATETIME        DEFAULT NULL,
  `clicou_em`       DATETIME        DEFAULT NULL,
  `erro_msg`        VARCHAR(500)    DEFAULT NULL,
  `enviado_em`      DATETIME        DEFAULT NULL,
  `token_rastreio`  CHAR(32)        DEFAULT NULL,   -- para pixel de rastreio

  PRIMARY KEY (`id`),
  KEY `idx_campanha`  (`campanha_id`),
  KEY `idx_email`     (`email`),
  KEY `idx_status`    (`status`),
  KEY `idx_token`     (`token_rastreio`),
  CONSTRAINT `fk_env_campanha` FOREIGN KEY (`campanha_id`) REFERENCES `campanhas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. Enriquece a tabela newsletter com mais dados ──────────────
ALTER TABLE `newsletter`
  ADD COLUMN IF NOT EXISTS `nome`      VARCHAR(120) DEFAULT NULL  AFTER `email`,
  ADD COLUMN IF NOT EXISTS `origem`    VARCHAR(60)  DEFAULT NULL  AFTER `nome`,   -- 'biblioteca','blog','contato','manual'
  ADD COLUMN IF NOT EXISTS `tags`      VARCHAR(200) DEFAULT NULL  AFTER `origem`, -- CSV: 'thriller,romance'
  ADD COLUMN IF NOT EXISTS `descad_em` DATETIME     DEFAULT NULL  AFTER `status`;

-- ── 5. View de leads consolidados (newsletter + usuários) ────────
CREATE OR REPLACE VIEW `vw_leads` AS
SELECT
  'usuario'           AS origem_tipo,
  u.id                AS ref_id,
  u.nome,
  u.email,
  u.created_at        AS inscrito_em,
  u.ultimo_login      AS ultima_atividade,
  u.ativo             AS ativo,
  (SELECT COUNT(*) FROM compras WHERE usuario_id=u.id AND status='aprovada') AS n_compras,
  (SELECT COUNT(*) FROM assinaturas WHERE usuario_id=u.id AND status='ativa' AND expira_em>NOW()) AS tem_assin,
  NULL                AS tags
FROM `usuarios` u

UNION ALL

SELECT
  'newsletter'        AS origem_tipo,
  n.id                AS ref_id,
  COALESCE(n.nome,'') AS nome,
  n.email,
  n.created_at        AS inscrito_em,
  NULL                AS ultima_atividade,
  (n.status = 'ativo') AS ativo,
  0                   AS n_compras,
  0                   AS tem_assin,
  n.tags
FROM `newsletter` n
WHERE n.email NOT IN (SELECT email FROM `usuarios`); -- evita duplicatas

-- ── 6. View resumo de leads por segmento ────────────────────────
CREATE OR REPLACE VIEW `vw_segmentos` AS
SELECT
  'todos'           AS segmento,
  COUNT(DISTINCT email) AS total
FROM vw_leads WHERE ativo=1
UNION ALL
SELECT 'newsletter', COUNT(*) FROM newsletter WHERE status='ativo'
UNION ALL
SELECT 'compradores', COUNT(DISTINCT usuario_id) FROM compras WHERE status='aprovada'
UNION ALL
SELECT 'assinantes', COUNT(*) FROM assinaturas WHERE status='ativa' AND expira_em>NOW()
UNION ALL
SELECT 'inativos_30', COUNT(*) FROM usuarios WHERE ativo=1 AND (ultimo_login IS NULL OR ultimo_login < DATE_SUB(NOW(),INTERVAL 30 DAY))
UNION ALL
SELECT 'inativos_90', COUNT(*) FROM usuarios WHERE ativo=1 AND (ultimo_login IS NULL OR ultimo_login < DATE_SUB(NOW(),INTERVAL 90 DAY))
UNION ALL
SELECT 'sem_compra', COUNT(*) FROM usuarios u WHERE ativo=1 AND NOT EXISTS (SELECT 1 FROM compras WHERE usuario_id=u.id AND status='aprovada');

-- ================================================================
-- Resumo:
--   ✓ livros: +10 colunas (tipo, genero, subtitulo, destaque, gratuito,
--              novo, badges, link_amazon, data_pub, ordem)
--   ✓ campanhas: nova tabela com 8 tipos e 7 segmentos
--   ✓ campanhas_envios: log com rastreio de abertura/clique
--   ✓ newsletter: +4 colunas (nome, origem, tags, descad_em)
--   ✓ vw_leads: view unificada leads (usuários + newsletter)
--   ✓ vw_segmentos: contagem por segmento para campanhas
-- ================================================================
