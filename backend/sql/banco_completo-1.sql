-- ================================================================
-- ROBÉRIO DIÓGENES — banco_completo.sql
-- Banco de dados COMPLETO em um único arquivo.
-- Versão 4.0 — Setup + Leitor + Blog integrados
--
-- COMO USAR:
--   1. Abra o phpMyAdmin → http://localhost/phpmyadmin
--   2. Crie um banco novo: roberio_site   (utf8mb4, utf8mb4_unicode_ci)
--   3. Selecione o banco criado
--   4. Aba SQL → cole TUDO deste arquivo → Executar
--
-- Compatível com MySQL 5.7+ e MariaDB 10.4+
-- ================================================================

SET NAMES utf8mb4;
SET time_zone = '-03:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

-- ================================================================
-- PARTE 1 — USUÁRIOS E AUTENTICAÇÃO
-- ================================================================

-- ── Usuários ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nome`             VARCHAR(120)  NOT NULL,
  `email`            VARCHAR(255)  NOT NULL,
  `senha`            VARCHAR(255)  NOT NULL DEFAULT '',
  `sexo`             ENUM('masculino','feminino','outro','nao_informado') DEFAULT 'nao_informado',
  `data_nascimento`  DATE          DEFAULT NULL,
  `cidade`           VARCHAR(100)  DEFAULT NULL,
  `estado`           CHAR(2)       DEFAULT NULL,
  `pais`             VARCHAR(100)  DEFAULT 'Brasil',
  `whatsapp`         VARCHAR(25)   DEFAULT NULL,
  `google_id`        VARCHAR(100)  DEFAULT NULL,
  `foto_url`         VARCHAR(500)  DEFAULT NULL,
  `ip_cadastro`      VARCHAR(45)   DEFAULT NULL,
  `verificado`       TINYINT(1)    NOT NULL DEFAULT 0,
  `ativo`            TINYINT(1)    NOT NULL DEFAULT 1,
  `ultimo_login`     DATETIME      DEFAULT NULL,
  `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email`     (`email`),
  UNIQUE KEY `uq_google_id` (`google_id`),
  KEY `idx_ativo`      (`ativo`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Recuperação de senha ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `password_reset` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED NOT NULL,
  `token`       VARCHAR(255) NOT NULL,
  `expira_em`   DATETIME     NOT NULL,
  `criado_em`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usado_em`    DATETIME     DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token`     (`token`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_expira_em`  (`expira_em`),
  CONSTRAINT `fk_pr_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Admin ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`     VARCHAR(80)  NOT NULL,
  `password`     VARCHAR(255) NOT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_login` DATETIME     DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin padrão: usuário=admin | senha=RD@2025admin (TROQUE APÓS O 1º LOGIN)
INSERT IGNORE INTO `admin_users` (`username`, `password`)
VALUES ('admin', '$2y$12$eImiTXuWVxfM37uY4JANjO6RFV1bGVJNX6aGQ6MjaTN6tQqaHxcPC');

-- ── Log de autenticação (rate limiting) ──────────────────────────
CREATE TABLE IF NOT EXISTS `auth_log` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`         VARCHAR(45)  NOT NULL,
  `action`     VARCHAR(30)  NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_ip_action` (`ip`, `action`),
  KEY `idx_created`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Log de ações do admin ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admin_log` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id`    INT UNSIGNED DEFAULT NULL,
  `acao`        VARCHAR(100) NOT NULL,
  `descricao`   TEXT         DEFAULT NULL,
  `ip`          VARCHAR(45)  DEFAULT NULL,
  `criado_em`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_criado_em` (`criado_em`),
  CONSTRAINT `fk_al_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PARTE 2 — CATÁLOGO DE LIVROS
-- ================================================================

-- ── Livros ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `livros` (
  `id`               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `slug`             VARCHAR(100)   NOT NULL,
  `titulo`           VARCHAR(200)   NOT NULL,
  `arquivo_pdf`      VARCHAR(200)   DEFAULT NULL,
  `arquivo_epub`     VARCHAR(200)   DEFAULT NULL,
  `ativo`            TINYINT(1)     NOT NULL DEFAULT 1,
  -- Campos do leitor (adicionados direto na criação)
  `preco`            DECIMAL(8,2)   DEFAULT NULL,
  `preco_promocao`   DECIMAL(8,2)   DEFAULT NULL,
  `pasta_conteudo`   VARCHAR(200)   DEFAULT NULL,
  `total_capitulos`  SMALLINT UNSIGNED DEFAULT NULL,
  `sinopse`          TEXT           DEFAULT NULL,
  `capa_img`         VARCHAR(200)   DEFAULT NULL,
  `criado_em`        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catálogo inicial de livros
INSERT IGNORE INTO `livros`
  (slug, titulo, arquivo_pdf, arquivo_epub, preco, total_capitulos, pasta_conteudo, capa_img)
VALUES
  ('jogo-das-mascaras',        'O Jogo das Máscaras',         'O-jogo-das-mascas-capitulo-1.pdf',           'O-jogo-das-mascas-capitulo-1.epub',           19.90, 10, 'livros-conteudo/jogo-das-mascaras/',        'img/jogo-das-mascaras.jpg'),
  ('a-setima-lei',             'A Sétima Lei',                'A-setima-lei-capitulo-1.pdf',                'A-setima-lei-capitulo-1.epub',                19.90,  8, 'livros-conteudo/a-setima-lei/',             'img/a-setima-lei.jpg'),
  ('a-marca-da-besta',         'A Marca da Besta',            'a-marca-da-besta-capitulo-1.pdf',            'a-marca-da-besta-capitulo-1.epub',            19.90,  9, 'livros-conteudo/a-marca-da-besta/',         'img/a-marca-da-besta.jpg'),
  ('caminhos-de-outono',       'Caminhos de Outono',          'caminhos-de-outono-capitulo-1.pdf',          'caminhos-de-outono-capitulo-1.epub',          19.90,  7, 'livros-conteudo/caminhos-de-outono/',       'img/caminhos-de-outono.jpg'),
  ('cartas-do-passado',        'Cartas do Passado',           'cartas-do-passado-capitulo-1.pdf',           'cartas-do-passado-capitulo-1.epub',           19.90, 11, 'livros-conteudo/cartas-do-passado/',        'img/cartas-do-passado.jpg'),
  ('das-coisas-que-o-amor-faz','Das Coisas que o Amor Faz',  'das-coisas-que-o-amor-faz-capitulo-1.pdf',   'das-coisas-que-o-amor-faz-capitulo-1.epub',   19.90,  8, 'livros-conteudo/das-coisas-que-o-amor-faz/','img/das-coisas-que-o-amor-faz.jpg'),
  ('genesis',                  'Gênesis',                     'genesis-capitulo-1.pdf',                     'genesis-capitulo-1.epub',                     19.90, 10, 'livros-conteudo/genesis/',                  'img/genesis.jpg'),
  ('lumen',                    'Lúmen – A Outra Metade do Céu','lumen-capitulo-1.pdf',                      'lumen-capitulo-1.epub',                       19.90,  9, 'livros-conteudo/lumen/',                    'img/lumen.jpg'),
  ('mares-secretas-do-amor',   'As Marés Secretas do Amor',   'as-mares-secretas-do-amor-capitulo-1.pdf',   'as-mares-secretas-do-amor-capitulo-1.epub',   19.90,  9, 'livros-conteudo/mares-secretas-do-amor/',   'img/mares-secretas.jpg'),
  ('o-abismo-das-almas',       'O Abismo das Almas',          'o-abismo-das-almas-capitulo-1.pdf',          'o-abismo-das-almas-capitulo-1.epub',          19.90,  8, 'livros-conteudo/o-abismo-das-almas/',       'img/o-abismo-das-almas.jpg'),
  ('rosas-e-espinhos',         'Rosas e Espinhos',            'rosas-e-espinhos-capitulo-1.pdf',            'rosas-e-espinhos-capitulo-1.epub',            19.90,  6, 'livros-conteudo/rosas-e-espinhos/',         'img/rosas-e-espinhos.jpg');

-- ================================================================
-- PARTE 3 — INTERAÇÕES COM LIVROS
-- ================================================================

-- ── Favoritos ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `favoritos` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`    INT UNSIGNED NOT NULL,
  `livro_slug`    VARCHAR(100) NOT NULL,
  `adicionado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fav` (`usuario_id`, `livro_slug`),
  CONSTRAINT `fk_fav_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Avaliações com estrelas ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `avaliacoes` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `usuario_id`    INT UNSIGNED    NOT NULL,
  `livro_slug`    VARCHAR(100)    NOT NULL,
  `estrelas`      TINYINT UNSIGNED NOT NULL,
  `avaliado_em`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_aval` (`usuario_id`, `livro_slug`),
  CONSTRAINT `fk_aval_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Downloads de amostras ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `downloads_log` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED NOT NULL,
  `livro_slug`  VARCHAR(100) NOT NULL,
  `formato`     ENUM('pdf','epub') NOT NULL DEFAULT 'pdf',
  `arquivo`     VARCHAR(200) NOT NULL,
  `ip`          VARCHAR(45)  DEFAULT NULL,
  `baixado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_usuario`  (`usuario_id`),
  KEY `idx_livro`    (`livro_slug`),
  CONSTRAINT `fk_dl_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Comentários (livros e blog) ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `comentarios` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED  DEFAULT NULL,      -- NULL = comentário anônimo (legado)
  `referencia`  VARCHAR(100)  DEFAULT NULL,      -- slug do post ou livro
  `tipo`        ENUM('livro','blog') DEFAULT 'livro',
  `livro_slug`  VARCHAR(100)  NOT NULL DEFAULT '',  -- mantido por compatibilidade
  `nome`        VARCHAR(120)  NOT NULL DEFAULT '',
  `cidade`      VARCHAR(100)  DEFAULT NULL,
  `leu`         ENUM('sim','cap','nao','') DEFAULT '',
  `texto`       TEXT          NOT NULL,
  `ip`          VARCHAR(45)   NOT NULL DEFAULT '',
  `aprovado`    TINYINT(1)    NOT NULL DEFAULT 1,   -- 1=aprovado automaticamente
  `criado_em`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_livro_aprovado`   (`livro_slug`, `aprovado`),
  KEY `idx_referencia_tipo`  (`referencia`, `tipo`, `aprovado`),
  KEY `idx_usuario_id`       (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Mensagens de contato ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `contato` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome`       VARCHAR(120) NOT NULL,
  `email`      VARCHAR(255) NOT NULL,
  `assunto`    VARCHAR(200) DEFAULT NULL,
  `mensagem`   TEXT         NOT NULL,
  `ip`         VARCHAR(45)  NOT NULL DEFAULT '',
  `lida`       TINYINT(1)   NOT NULL DEFAULT 0,
  `criado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Newsletter ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `newsletter` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(255) NOT NULL,
  `whatsapp`   VARCHAR(25)  DEFAULT NULL,
  `ip`         VARCHAR(45)  DEFAULT NULL,
  `status`     ENUM('ativo','descadastrado') NOT NULL DEFAULT 'ativo',
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `newsletter_log` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`        VARCHAR(45)  NOT NULL,
  `tentativa` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Visitas ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `visitas` (
  `id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `total` BIGINT       NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `visitas` (`id`, `total`) VALUES (1, 0);

CREATE TABLE IF NOT EXISTS `visitas_log` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `visitor_hash` CHAR(64)     NOT NULL,
  `visit_date`   DATE         NOT NULL,
  `visited_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_visitor_dia` (`visitor_hash`, `visit_date`),
  KEY `idx_visit_date` (`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Livros favoritos (tabela legado) ─────────────────────────────
CREATE TABLE IF NOT EXISTS `livros_favoritos` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`    INT UNSIGNED NOT NULL,
  `livro_id`      INT UNSIGNED NOT NULL,
  `adicionado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_livro` (`usuario_id`, `livro_id`),
  CONSTRAINT `fk_lf_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Downloads (tabela legado) ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `downloads` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`     INT UNSIGNED NOT NULL,
  `livro_id`       INT UNSIGNED NOT NULL,
  `tipo_arquivo`   ENUM('pdf','epub','mobi') DEFAULT 'pdf',
  `data_download`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_download`    VARCHAR(45)  DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_id` (`usuario_id`),
  CONSTRAINT `fk_dl2_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PARTE 4 — SISTEMA DE LEITURA ONLINE
-- ================================================================

-- ── Planos de assinatura ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `planos` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `slug`          VARCHAR(50)   NOT NULL,
  `nome`          VARCHAR(100)  NOT NULL,
  `descricao`     TEXT          DEFAULT NULL,
  `preco`         DECIMAL(8,2)  NOT NULL,
  `duracao_dias`  SMALLINT UNSIGNED NOT NULL,
  `ativo`         TINYINT(1)    NOT NULL DEFAULT 1,
  `criado_em`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `planos` (slug, nome, descricao, preco, duracao_dias) VALUES
  ('mensal',    'Assinante Mensal',    'Acesso completo à biblioteca por 30 dias.',  19.90,  30),
  ('semestral', 'Assinante Semestral', 'Acesso completo à biblioteca por 6 meses.',  99.90, 180),
  ('anual',     'Assinante Anual',     'Acesso completo à biblioteca por 1 ano.',   179.90, 365);

-- ── Assinaturas ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `assinaturas` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`     INT UNSIGNED NOT NULL,
  `plano_id`       INT UNSIGNED NOT NULL,
  `status`         ENUM('ativa','cancelada','expirada','pendente') NOT NULL DEFAULT 'pendente',
  `inicio_em`      DATETIME     NOT NULL,
  `expira_em`      DATETIME     NOT NULL,
  `renovacao_auto` TINYINT(1)   NOT NULL DEFAULT 0,
  `gateway`        VARCHAR(50)  DEFAULT NULL,
  `ref_externa`    VARCHAR(200) DEFAULT NULL,
  `criado_em`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_usuario`   (`usuario_id`),
  KEY `idx_status`    (`status`),
  KEY `idx_expira_em` (`expira_em`),
  KEY `idx_gateway`   (`gateway`),
  CONSTRAINT `fk_assin_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assin_plano`   FOREIGN KEY (`plano_id`)   REFERENCES `planos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Compras avulsas ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `compras` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `usuario_id`    INT UNSIGNED  NOT NULL,
  `livro_slug`    VARCHAR(100)  NOT NULL,
  `preco_pago`    DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
  `status`        ENUM('aprovada','pendente','cancelada','reembolsada') NOT NULL DEFAULT 'pendente',
  `gateway`       VARCHAR(50)   DEFAULT NULL,
  `ref_externa`   VARCHAR(200)  DEFAULT NULL,
  `comprado_em`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_compra`    (`usuario_id`, `livro_slug`),
  KEY `idx_usuario`         (`usuario_id`),
  KEY `idx_livro_slug`      (`livro_slug`),
  KEY `idx_status`          (`status`),
  KEY `idx_comprado_em`     (`comprado_em`),
  CONSTRAINT `fk_compra_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Progresso de leitura ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `leitor_progresso` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `usuario_id`     INT UNSIGNED  NOT NULL,
  `livro_slug`     VARCHAR(100)  NOT NULL,
  `capitulo_atual` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `posicao_scroll` INT UNSIGNED  NOT NULL DEFAULT 0,
  `percentual`     DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  `total_paginas`  SMALLINT UNSIGNED DEFAULT NULL,
  `iniciado_em`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultima_leitura` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `concluido`      TINYINT(1)    NOT NULL DEFAULT 0,
  `concluido_em`   DATETIME      DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_progresso`    (`usuario_id`, `livro_slug`),
  KEY `idx_ultima_leitura`     (`ultima_leitura`),
  KEY `idx_percentual`         (`percentual`),
  CONSTRAINT `fk_prog_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Anotações do leitor ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `leitor_anotacoes` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`    INT UNSIGNED NOT NULL,
  `livro_slug`    VARCHAR(100) NOT NULL,
  `capitulo`      SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `texto`         TEXT         NOT NULL,
  `cor`           VARCHAR(10)  NOT NULL DEFAULT '#FFD700',
  `criado_em`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_usuario_livro` (`usuario_id`, `livro_slug`),
  KEY `idx_criado`        (`criado_em`),
  CONSTRAINT `fk_anot_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Marcações (highlights) ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS `leitor_marcacoes` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED NOT NULL,
  `livro_slug`  VARCHAR(100) NOT NULL,
  `capitulo`    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `trecho`      TEXT         NOT NULL,
  `cor`         VARCHAR(10)  NOT NULL DEFAULT '#FFD700',
  `nota`        TEXT         DEFAULT NULL,
  `criado_em`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_usuario_livro` (`usuario_id`, `livro_slug`),
  KEY `idx_criado`        (`criado_em`),
  CONSTRAINT `fk_marc_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Preferências tipográficas ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `leitor_preferencias` (
  `usuario_id`     INT UNSIGNED NOT NULL,
  `fonte`          ENUM('serifada','sans','manuscrito','classica') NOT NULL DEFAULT 'serifada',
  `tamanho_fonte`  TINYINT UNSIGNED NOT NULL DEFAULT 18,
  `fundo_leitura`  ENUM('branco','bege','cinza','preto') NOT NULL DEFAULT 'bege',
  `largura_coluna` ENUM('estreita','media','larga') NOT NULL DEFAULT 'media',
  `altura_linha`   DECIMAL(3,1) NOT NULL DEFAULT 1.8,
  `atualizado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`usuario_id`),
  CONSTRAINT `fk_pref_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PARTE 5 — VIEWS
-- ================================================================

-- Últimas leituras do usuário (para a biblioteca do leitor)
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

-- Acesso unificado (compra avulsa + assinatura ativa)
CREATE OR REPLACE VIEW `vw_acesso_leitura` AS
SELECT c.usuario_id, c.livro_slug, 'compra' AS tipo_acesso, NULL AS expira_em
FROM `compras` c
WHERE c.status = 'aprovada'
UNION ALL
SELECT a.usuario_id, l.slug AS livro_slug, 'assinatura' AS tipo_acesso, a.expira_em
FROM `assinaturas` a
CROSS JOIN `livros` l
WHERE a.status = 'ativa' AND a.expira_em > NOW() AND l.ativo = 1;

-- Dashboard admin — estatísticas principais
CREATE OR REPLACE VIEW `vw_dashboard` AS
SELECT
  (SELECT COUNT(*) FROM usuarios  WHERE ativo = 1)                          AS total_usuarios,
  (SELECT COUNT(*) FROM assinaturas WHERE status='ativa' AND expira_em>NOW()) AS assinaturas_ativas,
  (SELECT COUNT(*) FROM compras   WHERE status = 'aprovada')                 AS total_compras,
  (SELECT COALESCE(SUM(preco_pago),0) FROM compras
   WHERE status='aprovada'
   AND YEAR(comprado_em)=YEAR(NOW()) AND MONTH(comprado_em)=MONTH(NOW()))    AS receita_mes_compras,
  (SELECT COALESCE(SUM(p.preco),0) FROM assinaturas a JOIN planos p ON p.id=a.plano_id
   WHERE a.status='ativa'
   AND YEAR(a.inicio_em)=YEAR(NOW()) AND MONTH(a.inicio_em)=MONTH(NOW()))    AS receita_mes_assinaturas;

-- ================================================================
-- PARTE 6 — STORED PROCEDURES E EVENTOS
-- ================================================================

DROP PROCEDURE IF EXISTS `verificar_acesso`;
DELIMITER //
CREATE PROCEDURE `verificar_acesso`(
  IN  p_usuario_id INT UNSIGNED,
  IN  p_livro_slug VARCHAR(100),
  OUT p_tem_acesso TINYINT
)
BEGIN
  SELECT COUNT(*) INTO p_tem_acesso
  FROM `vw_acesso_leitura`
  WHERE usuario_id = p_usuario_id AND livro_slug = p_livro_slug;
  IF p_tem_acesso > 0 THEN SET p_tem_acesso = 1; END IF;
END //
DELIMITER ;

DROP PROCEDURE IF EXISTS `limpar_dados_leitor`;
DELIMITER //
CREATE PROCEDURE `limpar_dados_leitor`()
BEGIN
  DELETE FROM `leitor_anotacoes` WHERE usuario_id IN (
    SELECT id FROM `usuarios` WHERE ativo=0 AND updated_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
  );
  DELETE FROM `leitor_marcacoes` WHERE usuario_id IN (
    SELECT id FROM `usuarios` WHERE ativo=0 AND updated_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
  );
END //
DELIMITER ;

DROP PROCEDURE IF EXISTS `limpar_tokens_expirados`;
DELIMITER //
CREATE PROCEDURE `limpar_tokens_expirados`()
BEGIN
  DELETE FROM `password_reset` WHERE `expira_em` < NOW() AND `usado_em` IS NULL;
END //
DELIMITER ;

DROP PROCEDURE IF EXISTS `limpar_logs_antigos`;
DELIMITER //
CREATE PROCEDURE `limpar_logs_antigos`()
BEGIN
  DELETE FROM `auth_log`       WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 30 DAY);
  DELETE FROM `newsletter_log` WHERE `tentativa`   < DATE_SUB(NOW(), INTERVAL 7  DAY);
  DELETE FROM `admin_log`      WHERE `criado_em`   < DATE_SUB(NOW(), INTERVAL 90 DAY);
END //
DELIMITER ;

-- Eventos agendados (requerem EVENT SCHEDULER ativo no MySQL)
CREATE EVENT IF NOT EXISTS `evt_limpar_tokens`
  ON SCHEDULE EVERY 1 DAY STARTS CURRENT_TIMESTAMP
  DO CALL `limpar_tokens_expirados`();

CREATE EVENT IF NOT EXISTS `evt_limpar_logs`
  ON SCHEDULE EVERY 1 WEEK STARTS CURRENT_TIMESTAMP
  DO CALL `limpar_logs_antigos`();

SET foreign_key_checks = 1;

-- ================================================================
-- ✓ RESUMO DO QUE ESTE SCRIPT CRIA (37 objetos)
-- ================================================================
-- TABELAS (22):
--   usuarios, password_reset, admin_users, auth_log, admin_log
--   livros, favoritos, avaliacoes, downloads_log, comentarios
--   contato, newsletter, newsletter_log, visitas, visitas_log
--   livros_favoritos, downloads
--   planos, assinaturas, compras
--   leitor_progresso, leitor_anotacoes, leitor_marcacoes, leitor_preferencias
--
-- VIEWS (3):
--   vw_ultimas_leituras, vw_acesso_leitura, vw_dashboard
--
-- PROCEDURES (4):
--   verificar_acesso, limpar_dados_leitor,
--   limpar_tokens_expirados, limpar_logs_antigos
--
-- EVENTOS (2):
--   evt_limpar_tokens, evt_limpar_logs
--
-- DADOS INICIAIS:
--   Admin: usuário=admin | senha=RD@2025admin (TROQUE APÓS O 1º LOGIN)
--   11 livros no catálogo com slugs, preços e pastas configurados
--   3 planos de assinatura (mensal R$19,90 | semestral R$99,90 | anual R$179,90)
-- ================================================================
