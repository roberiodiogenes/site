-- ============================================================
-- ROBÉRIO DIÓGENES — leitor_setup.sql
-- Tabelas do sistema de leitura online
-- Versão 1.0 — Integra ao setup.sql existente
-- ============================================================
-- Execute APÓS o setup.sql já existente no phpMyAdmin
-- ============================================================

-- ── Planos de assinatura disponíveis ─────────────────────────
-- Tabela de referência com os planos oferecidos
CREATE TABLE IF NOT EXISTS `planos` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`          VARCHAR(50)  NOT NULL,                          -- 'mensal', 'semestral', 'anual'
    `nome`          VARCHAR(100) NOT NULL,                          -- 'Plano Mensal', etc.
    `descricao`     TEXT         DEFAULT NULL,
    `preco`         DECIMAL(8,2) NOT NULL,                          -- preço em BRL
    `duracao_dias`  SMALLINT UNSIGNED NOT NULL,                     -- 30, 180, 365
    `ativo`         TINYINT(1)   NOT NULL DEFAULT 1,
    `criado_em`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Planos iniciais
INSERT IGNORE INTO `planos` (slug, nome, descricao, preco, duracao_dias) VALUES
('mensal',    'Assinante Mensal',    'Acesso completo à biblioteca por 30 dias.',  19.90,  30),
('semestral', 'Assinante Semestral', 'Acesso completo à biblioteca por 6 meses.',  99.90, 180),
('anual',     'Assinante Anual',     'Acesso completo à biblioteca por 1 ano.',   179.90, 365);

-- ── Assinaturas dos usuários ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `assinaturas` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`    INT UNSIGNED NOT NULL,
    `plano_id`      INT UNSIGNED NOT NULL,
    `status`        ENUM('ativa','cancelada','expirada','pendente') NOT NULL DEFAULT 'pendente',
    `inicio_em`     DATETIME     NOT NULL,
    `expira_em`     DATETIME     NOT NULL,
    `renovacao_auto`TINYINT(1)   NOT NULL DEFAULT 0,
    `gateway`       VARCHAR(50)  DEFAULT NULL,                      -- 'mercadopago', 'stripe', 'manual'
    `ref_externa`   VARCHAR(200) DEFAULT NULL,                      -- ID da transação no gateway
    `criado_em`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_usuario`   (`usuario_id`),
    KEY `idx_status`    (`status`),
    KEY `idx_expira_em` (`expira_em`),
    CONSTRAINT `fk_assin_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_assin_plano`   FOREIGN KEY (`plano_id`)   REFERENCES `planos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Compras avulsas de livros ─────────────────────────────────
-- Um usuário pode comprar um livro específico (sem assinatura)
CREATE TABLE IF NOT EXISTS `compras` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`    INT UNSIGNED NOT NULL,
    `livro_slug`    VARCHAR(100) NOT NULL,
    `preco_pago`    DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    `status`        ENUM('aprovada','pendente','cancelada','reembolsada') NOT NULL DEFAULT 'pendente',
    `gateway`       VARCHAR(50)  DEFAULT NULL,                      -- 'mercadopago', 'stripe', 'manual'
    `ref_externa`   VARCHAR(200) DEFAULT NULL,                      -- ID da transação
    `comprado_em`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_compra` (`usuario_id`, `livro_slug`),            -- 1 compra por livro por usuário
    KEY `idx_usuario`    (`usuario_id`),
    KEY `idx_livro_slug` (`livro_slug`),
    KEY `idx_status`     (`status`),
    CONSTRAINT `fk_compra_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Preços dos livros (catálogo de vendas) ────────────────────
-- Adiciona colunas de preço e conteúdo à tabela livros existente
ALTER TABLE `livros`
    ADD COLUMN IF NOT EXISTS `preco`          DECIMAL(8,2)  DEFAULT NULL          AFTER `ativo`,
    ADD COLUMN IF NOT EXISTS `preco_promocao` DECIMAL(8,2)  DEFAULT NULL          AFTER `preco`,
    ADD COLUMN IF NOT EXISTS `pasta_conteudo` VARCHAR(200)  DEFAULT NULL          AFTER `preco_promocao`,
    ADD COLUMN IF NOT EXISTS `total_capitulos`SMALLINT UNSIGNED DEFAULT NULL      AFTER `pasta_conteudo`,
    ADD COLUMN IF NOT EXISTS `sinopse`        TEXT          DEFAULT NULL          AFTER `total_capitulos`,
    ADD COLUMN IF NOT EXISTS `capa_img`       VARCHAR(200)  DEFAULT NULL          AFTER `sinopse`;

-- Atualizar livros existentes com pasta de conteúdo padrão
UPDATE `livros` SET
    `pasta_conteudo` = CONCAT('livros-conteudo/', slug, '/'),
    `capa_img`       = CONCAT('img/', slug, '.jpg')
WHERE `pasta_conteudo` IS NULL;

-- Preço de exemplo para o Lúmen (ajuste conforme necessário)
UPDATE `livros` SET `preco` = 19.90 WHERE `slug` = 'lumen';

-- ── Progresso de leitura ──────────────────────────────────────
-- Salva exatamente onde o leitor parou para retomar depois
CREATE TABLE IF NOT EXISTS `leitor_progresso` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`      INT UNSIGNED NOT NULL,
    `livro_slug`      VARCHAR(100) NOT NULL,
    `capitulo_atual`  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `posicao_scroll`  INT UNSIGNED NOT NULL DEFAULT 0,              -- scrollTop em pixels
    `percentual`      DECIMAL(5,2) NOT NULL DEFAULT 0.00,           -- 0.00 a 100.00
    `total_paginas`   SMALLINT UNSIGNED DEFAULT NULL,               -- total de capítulos do livro
    `iniciado_em`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ultima_leitura`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `concluido`       TINYINT(1)   NOT NULL DEFAULT 0,
    `concluido_em`    DATETIME     DEFAULT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_progresso` (`usuario_id`, `livro_slug`),
    KEY `idx_ultima_leitura` (`ultima_leitura`),
    CONSTRAINT `fk_prog_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Anotações do leitor ───────────────────────────────────────
-- Notas livres que o leitor escreve em qualquer ponto do livro
CREATE TABLE IF NOT EXISTS `leitor_anotacoes` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`     INT UNSIGNED NOT NULL,
    `livro_slug`     VARCHAR(100) NOT NULL,
    `capitulo`       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `texto`          TEXT         NOT NULL,                         -- conteúdo da anotação
    `cor`            VARCHAR(10)  NOT NULL DEFAULT '#FFD700',       -- cor da etiqueta visual
    `criado_em`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_usuario_livro` (`usuario_id`, `livro_slug`),
    KEY `idx_capitulo`      (`capitulo`),
    CONSTRAINT `fk_anot_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Marcações de trechos (highlights) ───────────────────────
-- Trechos de texto que o leitor selecionou e marcou
CREATE TABLE IF NOT EXISTS `leitor_marcacoes` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`     INT UNSIGNED NOT NULL,
    `livro_slug`     VARCHAR(100) NOT NULL,
    `capitulo`       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `trecho`         TEXT         NOT NULL,                         -- texto selecionado
    `cor`            VARCHAR(10)  NOT NULL DEFAULT '#FFD700',       -- amarelo, verde, rosa, azul
    `nota`           TEXT         DEFAULT NULL,                     -- nota opcional sobre o trecho
    `criado_em`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_usuario_livro` (`usuario_id`, `livro_slug`),
    KEY `idx_capitulo`      (`capitulo`),
    CONSTRAINT `fk_marc_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Preferências tipográficas do leitor ──────────────────────
-- Salva as configurações de leitura de cada usuário
CREATE TABLE IF NOT EXISTS `leitor_preferencias` (
    `usuario_id`     INT UNSIGNED NOT NULL,
    `fonte`          ENUM('serifada','sans','manuscrito','classica') NOT NULL DEFAULT 'serifada',
    `tamanho_fonte`  TINYINT UNSIGNED NOT NULL DEFAULT 18,          -- 14 a 28 (px)
    `fundo_leitura`  ENUM('branco','bege','cinza','preto') NOT NULL DEFAULT 'bege',
    `largura_coluna` ENUM('estreita','media','larga') NOT NULL DEFAULT 'media',
    `altura_linha`   DECIMAL(3,1) NOT NULL DEFAULT 1.8,             -- 1.4 a 2.4
    `atualizado_em`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`usuario_id`),
    CONSTRAINT `fk_pref_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- VIEWS ÚTEIS
-- ============================================================

-- View: últimas leituras do usuário (para o painel do leitor)
CREATE OR REPLACE VIEW `vw_ultimas_leituras` AS
SELECT
    lp.usuario_id,
    lp.livro_slug,
    l.titulo,
    l.capa_img,
    lp.capitulo_atual,
    lp.percentual,
    lp.ultima_leitura,
    lp.concluido
FROM `leitor_progresso` lp
LEFT JOIN `livros` l ON l.slug = lp.livro_slug
ORDER BY lp.ultima_leitura DESC;

-- View: biblioteca do usuário (compras + assinaturas ativas)
-- Usada pelo backend/acesso.php para verificar se pode ler
CREATE OR REPLACE VIEW `vw_acesso_leitura` AS
-- Acesso por compra avulsa
SELECT
    c.usuario_id,
    c.livro_slug,
    'compra' AS tipo_acesso,
    NULL      AS expira_em
FROM `compras` c
WHERE c.status = 'aprovada'

UNION ALL

-- Acesso por assinatura ativa (todos os livros)
SELECT
    a.usuario_id,
    l.slug     AS livro_slug,
    'assinatura' AS tipo_acesso,
    a.expira_em
FROM `assinaturas` a
CROSS JOIN `livros` l
WHERE a.status = 'ativa'
  AND a.expira_em > NOW()
  AND l.ativo = 1;

-- ============================================================
-- STORED PROCEDURE: verificar acesso de um usuário a um livro
-- ============================================================
DROP PROCEDURE IF EXISTS `verificar_acesso`;
DELIMITER //
CREATE PROCEDURE `verificar_acesso`(
    IN p_usuario_id INT UNSIGNED,
    IN p_livro_slug VARCHAR(100),
    OUT p_tem_acesso TINYINT
)
BEGIN
    SELECT COUNT(*) INTO p_tem_acesso
    FROM `vw_acesso_leitura`
    WHERE usuario_id = p_usuario_id
      AND livro_slug  = p_livro_slug;

    -- Normaliza para 0 ou 1
    IF p_tem_acesso > 0 THEN SET p_tem_acesso = 1; END IF;
END //
DELIMITER ;

-- ============================================================
-- STORED PROCEDURE: limpeza de progresso de contas deletadas
-- ============================================================
DROP PROCEDURE IF EXISTS `limpar_dados_leitor`;
DELIMITER //
CREATE PROCEDURE `limpar_dados_leitor`()
BEGIN
    -- Remove anotações e marcações de usuários inativos há > 1 ano
    DELETE FROM `leitor_anotacoes`
    WHERE usuario_id IN (
        SELECT id FROM `usuarios`
        WHERE ativo = 0 AND updated_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
    );
    DELETE FROM `leitor_marcacoes`
    WHERE usuario_id IN (
        SELECT id FROM `usuarios`
        WHERE ativo = 0 AND updated_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
    );
END //
DELIMITER ;

-- ============================================================
-- ÍNDICES EXTRAS PARA PERFORMANCE
-- ============================================================
-- (só execute se as tabelas acima foram criadas com sucesso)

ALTER TABLE `leitor_progresso`  ADD KEY `idx_percentual` (`percentual`);
ALTER TABLE `leitor_anotacoes`  ADD KEY `idx_criado`     (`criado_em`);
ALTER TABLE `leitor_marcacoes`  ADD KEY `idx_criado`     (`criado_em`);
ALTER TABLE `assinaturas`       ADD KEY `idx_gateway`    (`gateway`);
ALTER TABLE `compras`           ADD KEY `idx_comprado_em`(`comprado_em`);

-- ============================================================
-- RESUMO DO QUE ESTE SCRIPT CRIA:
-- ============================================================
--  ✓ planos            — planos de assinatura (mensal/semestral/anual)
--  ✓ assinaturas       — vínculo usuário ↔ plano com datas e status
--  ✓ compras           — compra avulsa de livro por usuário
--  ✓ leitor_progresso  — posição exata de leitura por usuário/livro
--  ✓ leitor_anotacoes  — notas livres por capítulo
--  ✓ leitor_marcacoes  — trechos destacados (highlights) com cor
--  ✓ leitor_preferencias — fonte, tamanho, fundo, espaçamento
--  ✓ vw_ultimas_leituras — view para o painel do leitor
--  ✓ vw_acesso_leitura   — view unificada (compra + assinatura)
--  ✓ verificar_acesso    — procedure para checar acesso a um livro
--  ✓ limpar_dados_leitor — procedure de manutenção
--
-- COMO EXECUTAR:
--  1. Abra o phpMyAdmin → banco roberio_site
--  2. Aba SQL → cole este arquivo inteiro → Executar
--  3. Verifique se todas as tabelas apareceram no painel esquerdo
-- ============================================================
