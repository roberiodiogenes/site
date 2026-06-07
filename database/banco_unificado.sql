-- ================================================================
--  ROBÉRIO DIÓGENES — banco_unificado.sql
--  Versão 5.0 — Schema completo e definitivo (junho/2026)
--
--  Este arquivo substitui banco_completo.sql + todos os arquivos
--  migration_*.sql. Basta executar este único arquivo para ter
--  o banco 100% atualizado do zero.
--
--  COMO USAR:
--    1. phpMyAdmin → crie o banco: roberio_site (utf8mb4 / utf8mb4_unicode_ci)
--    2. Selecione o banco → aba SQL → cole tudo → Executar
--    ─── OU via linha de comando ───
--    mysql -u root -p roberio_site < banco_unificado.sql
--
--  Compatível com MySQL 8.0+ e MariaDB 10.4+
--  100% idempotente: pode ser executado em banco existente sem erros.
-- ================================================================

SET NAMES utf8mb4;
SET time_zone = '-03:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';


-- ================================================================
-- PARTE 1 — USUÁRIOS E AUTENTICAÇÃO
-- ================================================================

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nome`             VARCHAR(120)  NOT NULL,
  `email`            VARCHAR(255)  NOT NULL,
  `senha`            VARCHAR(255)  NOT NULL DEFAULT '',
  `tipo`             ENUM('leitor','assinante','admin') NOT NULL DEFAULT 'leitor',
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
  KEY `idx_tipo`       (`tipo`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Recuperação de senha ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `password_reset` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `token`      VARCHAR(255) NOT NULL,
  `expira_em`  DATETIME     NOT NULL,
  `criado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usado_em`   DATETIME     DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token`     (`token`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_expira_em`  (`expira_em`),
  CONSTRAINT `fk_pr_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Magic links (login sem senha) ────────────────────────────────
CREATE TABLE IF NOT EXISTS `magic_links` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `token`      VARCHAR(100) NOT NULL,
  `expira_em`  DATETIME     NOT NULL,
  `usado_em`   DATETIME     DEFAULT NULL,
  `criado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ml_token`  (`token`),
  KEY `idx_ml_usuario`      (`usuario_id`),
  CONSTRAINT `fk_ml_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Administradores ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`     VARCHAR(80)  NOT NULL,
  `password`     VARCHAR(255) NOT NULL,
  `ultimo_login` DATETIME     DEFAULT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

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
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id`  INT UNSIGNED DEFAULT NULL,
  `acao`      VARCHAR(100) NOT NULL,
  `descricao` TEXT         DEFAULT NULL,
  `ip`        VARCHAR(45)  DEFAULT NULL,
  `criado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_admin_id`  (`admin_id`),
  KEY `idx_criado_em` (`criado_em`),
  CONSTRAINT `fk_al_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ================================================================
-- PARTE 2 — CATÁLOGO DE LIVROS
-- (inclui todas as colunas de migration_livros_colunas.sql
--  e migration_vendas.sql)
-- ================================================================

CREATE TABLE IF NOT EXISTS `livros` (
  `id`               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `slug`             VARCHAR(100)   NOT NULL,
  `tipo`             ENUM('livro','conto') NOT NULL DEFAULT 'livro',
  `titulo`           VARCHAR(200)   NOT NULL,
  `subtitulo`        VARCHAR(200)   DEFAULT NULL,
  `genero`           VARCHAR(80)    DEFAULT NULL,
  `sinopse`          TEXT           DEFAULT NULL,
  `capa_img`         VARCHAR(200)   DEFAULT NULL,
  `arquivo_pdf`      VARCHAR(200)   DEFAULT NULL,
  `arquivo_epub`     VARCHAR(200)   DEFAULT NULL,
  `pasta_conteudo`   VARCHAR(200)   DEFAULT NULL,
  `total_capitulos`  SMALLINT UNSIGNED DEFAULT NULL,
  `preco`            DECIMAL(8,2)   DEFAULT NULL,
  `preco_promocao`   DECIMAL(8,2)   DEFAULT NULL,
  `promo_ate`        DATETIME       DEFAULT NULL  COMMENT 'Promoção ativa enquanto NOW() < promo_ate',
  `gratuito_ate`     DATETIME       DEFAULT NULL  COMMENT 'Gratuito temporariamente até esta data',
  `link_amazon`      VARCHAR(500)   DEFAULT NULL,
  `data_pub`         DATE           DEFAULT NULL,
  `badges`           VARCHAR(200)   DEFAULT NULL,
  `ativo`            TINYINT(1)     NOT NULL DEFAULT 1,
  `destaque`         TINYINT(1)     NOT NULL DEFAULT 0,
  `gratuito`         TINYINT(1)     NOT NULL DEFAULT 0,
  `novo`             TINYINT(1)     NOT NULL DEFAULT 0,
  `ordem`            SMALLINT UNSIGNED NOT NULL DEFAULT 99,
  `criado_em`        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug`    (`slug`),
  KEY `idx_tipo`          (`tipo`),
  KEY `idx_genero`        (`genero`),
  KEY `idx_destaque`      (`destaque`),
  KEY `idx_ordem`         (`ordem`),
  KEY `idx_ativo_ordem`   (`ativo`, `ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `livros`
  (slug, tipo, titulo, arquivo_pdf, arquivo_epub, preco, total_capitulos, pasta_conteudo, capa_img)
VALUES
  ('jogo-das-mascaras',        'livro','O Jogo das Máscaras',          'O-jogo-das-mascas-capitulo-1.pdf',           'O-jogo-das-mascas-capitulo-1.epub',           19.90, 10, 'livros-conteudo/jogo-das-mascaras/',        'img/jogo-das-mascaras.jpg'),
  ('a-setima-lei',             'livro','A Sétima Lei',                 'A-setima-lei-capitulo-1.pdf',                'A-setima-lei-capitulo-1.epub',                19.90,  8, 'livros-conteudo/a-setima-lei/',             'img/a-setima-lei.jpg'),
  ('a-marca-da-besta',         'livro','A Marca da Besta',             'a-marca-da-besta-capitulo-1.pdf',            'a-marca-da-besta-capitulo-1.epub',            19.90,  9, 'livros-conteudo/a-marca-da-besta/',         'img/a-marca-da-besta.jpg'),
  ('caminhos-de-outono',       'livro','Caminhos de Outono',           'caminhos-de-outono-capitulo-1.pdf',          'caminhos-de-outono-capitulo-1.epub',          19.90,  7, 'livros-conteudo/caminhos-de-outono/',       'img/caminhos-de-outono.jpg'),
  ('cartas-do-passado',        'livro','Cartas do Passado',            'cartas-do-passado-capitulo-1.pdf',           'cartas-do-passado-capitulo-1.epub',           19.90, 11, 'livros-conteudo/cartas-do-passado/',        'img/cartas-do-passado.jpg'),
  ('das-coisas-que-o-amor-faz','livro','Das Coisas que o Amor Faz',   'das-coisas-que-o-amor-faz-capitulo-1.pdf',   'das-coisas-que-o-amor-faz-capitulo-1.epub',   19.90,  8, 'livros-conteudo/das-coisas-que-o-amor-faz/','img/das-coisas-que-o-amor-faz.jpg'),
  ('genesis',                  'livro','Gênesis',                      'genesis-capitulo-1.pdf',                     'genesis-capitulo-1.epub',                     19.90, 10, 'livros-conteudo/genesis/',                  'img/genesis.jpg'),
  ('lumen',                    'livro','Lúmen – A Outra Metade do Céu','lumen-capitulo-1.pdf',                      'lumen-capitulo-1.epub',                       19.90,  9, 'livros-conteudo/lumen/',                    'img/lumen.jpg'),
  ('mares-secretas-do-amor',   'livro','As Marés Secretas do Amor',    'as-mares-secretas-do-amor-capitulo-1.pdf',   'as-mares-secretas-do-amor-capitulo-1.epub',   19.90,  9, 'livros-conteudo/mares-secretas-do-amor/',   'img/mares-secretas.jpg'),
  ('o-abismo-das-almas',       'livro','O Abismo das Almas',           'o-abismo-das-almas-capitulo-1.pdf',          'o-abismo-das-almas-capitulo-1.epub',          19.90,  8, 'livros-conteudo/o-abismo-das-almas/',       'img/o-abismo-das-almas.jpg'),
  ('rosas-e-espinhos',         'livro','Rosas e Espinhos',             'rosas-e-espinhos-capitulo-1.pdf',            'rosas-e-espinhos-capitulo-1.epub',            19.90,  6, 'livros-conteudo/rosas-e-espinhos/',         'img/rosas-e-espinhos.jpg');

-- ── Interações com livros ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `favoritos` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`    INT UNSIGNED NOT NULL,
  `livro_slug`    VARCHAR(100) NOT NULL,
  `adicionado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fav` (`usuario_id`, `livro_slug`),
  CONSTRAINT `fk_fav_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `avaliacoes` (
  `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `usuario_id`    INT UNSIGNED     NOT NULL,
  `livro_slug`    VARCHAR(100)     NOT NULL,
  `estrelas`      TINYINT UNSIGNED NOT NULL,
  `avaliado_em`   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_aval` (`usuario_id`, `livro_slug`),
  CONSTRAINT `fk_aval_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `downloads_log` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `livro_slug` VARCHAR(100) NOT NULL,
  `formato`    ENUM('pdf','epub') NOT NULL DEFAULT 'pdf',
  `arquivo`    VARCHAR(200) NOT NULL,
  `ip`         VARCHAR(45)  DEFAULT NULL,
  `baixado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_livro`   (`livro_slug`),
  CONSTRAINT `fk_dl_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (tabelas legado mantidas por compatibilidade)
CREATE TABLE IF NOT EXISTS `livros_favoritos` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`    INT UNSIGNED NOT NULL,
  `livro_id`      INT UNSIGNED NOT NULL,
  `adicionado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_livro` (`usuario_id`, `livro_id`),
  CONSTRAINT `fk_lf_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `downloads` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`    INT UNSIGNED NOT NULL,
  `livro_id`      INT UNSIGNED NOT NULL,
  `tipo_arquivo`  ENUM('pdf','epub','mobi') DEFAULT 'pdf',
  `data_download` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_download`   VARCHAR(45)  DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_usuario_id` (`usuario_id`),
  CONSTRAINT `fk_dl2_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ================================================================
-- PARTE 3 — BLOG / DIÁRIO
-- (posts + migration_blog_avancado.sql integrado)
-- ================================================================

-- ── Hub & Spoke (Clusters) ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `clusters` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `slug`       VARCHAR(160)  NOT NULL,
  `titulo`     VARCHAR(300)  NOT NULL,
  `descricao`  TEXT          DEFAULT NULL,
  `imagem_url` VARCHAR(500)  DEFAULT NULL,
  `pilar_slug` VARCHAR(160)  DEFAULT NULL COMMENT 'Slug do post pilar deste cluster',
  `ativo`      TINYINT(1)    NOT NULL DEFAULT 1,
  `criado_em`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Enquetes ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `enquetes` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titulo`    VARCHAR(300) NOT NULL,
  `descricao` TEXT         DEFAULT NULL,
  `ativo`     TINYINT(1)   NOT NULL DEFAULT 1,
  `multipla`  TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = múltipla escolha',
  `criado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `enquetes_opcoes` (
  `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `enquete_id` INT UNSIGNED     NOT NULL,
  `texto`      VARCHAR(300)     NOT NULL,
  `icone`      VARCHAR(50)      DEFAULT NULL,
  `ordem`      TINYINT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  KEY `idx_enquete` (`enquete_id`),
  CONSTRAINT `fk_eopc_enquete` FOREIGN KEY (`enquete_id`) REFERENCES `enquetes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `enquetes_respostas` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `enquete_id` INT UNSIGNED NOT NULL,
  `opcao_id`   INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED DEFAULT NULL,
  `ip_hash`    VARCHAR(64)  DEFAULT NULL COMMENT 'SHA256 do IP (LGPD)',
  `criado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_er_enquete` (`enquete_id`),
  KEY `idx_er_opcao`   (`opcao_id`),
  CONSTRAINT `fk_er_enquete` FOREIGN KEY (`enquete_id`) REFERENCES `enquetes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_er_opcao`   FOREIGN KEY (`opcao_id`)   REFERENCES `enquetes_opcoes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Posts do Diário ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `posts` (
  `id`                 INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `slug`               VARCHAR(160)     NOT NULL,
  `titulo`             VARCHAR(300)     NOT NULL,
  `subtitulo`          VARCHAR(300)     DEFAULT NULL,
  `categoria`          VARCHAR(80)      DEFAULT NULL,
  `tags`               VARCHAR(300)     DEFAULT NULL,
  `resumo`             TEXT             DEFAULT NULL,
  `corpo`              LONGTEXT         DEFAULT NULL,
  `imagem_url`         VARCHAR(300)     DEFAULT NULL,
  `audio_url`          VARCHAR(300)     DEFAULT NULL,
  `livro_slug`         VARCHAR(100)     DEFAULT NULL,
  `destaque`           TINYINT(1)       NOT NULL DEFAULT 0,
  `status`             ENUM('publicado','oculto','rascunho','agendado') NOT NULL DEFAULT 'rascunho',
  `publicado_em`       DATETIME         DEFAULT NULL,
  -- Campos de paywall e blog avançado (migration_blog_avancado)
  `exclusivo`          TINYINT(1)       NOT NULL DEFAULT 0 COMMENT '1 = conteúdo para assinantes',
  `percentual_livre`   TINYINT UNSIGNED NOT NULL DEFAULT 35 COMMENT '% exibido sem assinatura',
  `enquete_id`         INT UNSIGNED     DEFAULT NULL,
  `cluster_id`         INT UNSIGNED     DEFAULT NULL,
  `newsletter_enviado` TINYINT(1)       NOT NULL DEFAULT 0,
  `criado_em`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug`       (`slug`),
  KEY `idx_status`           (`status`),
  KEY `idx_categoria`        (`categoria`),
  KEY `idx_publicado_em`     (`publicado_em`),
  KEY `idx_destaque`         (`destaque`),
  KEY `idx_cluster_id`       (`cluster_id`),
  KEY `idx_exclusivo`        (`exclusivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Curtidas em posts ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `curtidas` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_slug`  VARCHAR(160) NOT NULL,
  `usuario_id` INT UNSIGNED DEFAULT NULL,
  `ip_hash`    VARCHAR(64)  DEFAULT NULL COMMENT 'SHA256 do IP (LGPD)',
  `curtido_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_curtida_usuario` (`post_slug`, `usuario_id`),
  KEY `idx_post_slug` (`post_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Posts lidos (paywall + progresso do blog) ─────────────────────
CREATE TABLE IF NOT EXISTS `posts_lidos` (
  `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED     NOT NULL,
  `post_slug`  VARCHAR(160)     NOT NULL,
  `progresso`  TINYINT UNSIGNED NOT NULL DEFAULT 100,
  `lido_em`    DATETIME         DEFAULT NULL,
  `criado_em`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lido`    (`usuario_id`, `post_slug`),
  KEY `idx_usuario`       (`usuario_id`),
  KEY `idx_post_slug`     (`post_slug`),
  CONSTRAINT `fk_pl_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Comentários (livros e blog)
--    inclui todos os campos de migration_comentarios_v2.sql
-- ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `comentarios` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id`      INT UNSIGNED DEFAULT NULL   COMMENT 'ID do comentário pai (respostas)',
  `usuario_id`     INT UNSIGNED DEFAULT NULL,
  `referencia`     VARCHAR(100) DEFAULT NULL   COMMENT 'Slug do post ou livro',
  `tipo`           ENUM('livro','blog')         DEFAULT 'livro',
  `livro_slug`     VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Mantido por compatibilidade',
  `nome`           VARCHAR(120) NOT NULL DEFAULT '',
  `cidade`         VARCHAR(100) DEFAULT NULL,
  `leu`            ENUM('sim','cap','nao','')   DEFAULT '',
  `texto`          TEXT         NOT NULL,
  `curtidas_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `ip`             VARCHAR(45)  NOT NULL DEFAULT '',
  `aprovado`       TINYINT(1)   NOT NULL DEFAULT 1,
  `flagged`        TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = conteúdo suspeito',
  `flag_motivo`    VARCHAR(200) DEFAULT NULL,
  `ip_hash`        VARCHAR(64)  DEFAULT NULL    COMMENT 'SHA256 do IP (LGPD)',
  `user_agent`     VARCHAR(500) DEFAULT NULL,
  `criado_em`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_livro_aprovado`   (`livro_slug`, `aprovado`),
  KEY `idx_referencia_tipo`  (`referencia`, `tipo`, `aprovado`),
  KEY `idx_usuario_id`       (`usuario_id`),
  KEY `idx_parent_id`        (`parent_id`),
  KEY `idx_flagged`          (`flagged`),
  CONSTRAINT `fk_com_parent` FOREIGN KEY (`parent_id`) REFERENCES `comentarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Curtidas em comentários ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS `comentario_curtidas` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `comentario_id` INT UNSIGNED NOT NULL,
  `usuario_id`    INT UNSIGNED NOT NULL,
  `criado_em`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_curtida`      (`comentario_id`, `usuario_id`),
  KEY `idx_cc_usuario`         (`usuario_id`),
  CONSTRAINT `fk_cc_comentario` FOREIGN KEY (`comentario_id`) REFERENCES `comentarios`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cc_usuario`    FOREIGN KEY (`usuario_id`)    REFERENCES `usuarios`(`id`)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Log de comentários flagged ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `comentarios_flags_log` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `comentario_id`       INT UNSIGNED NOT NULL,
  `usuario_id`          INT UNSIGNED DEFAULT NULL,
  `usuario_nome`        VARCHAR(150) DEFAULT NULL,
  `usuario_email`       VARCHAR(255) DEFAULT NULL,
  `ip_hash`             VARCHAR(64)  NOT NULL COMMENT 'SHA256 — LGPD',
  `user_agent`          VARCHAR(500) DEFAULT NULL,
  `pais`                VARCHAR(50)  DEFAULT NULL,
  `texto_original`      TEXT         NOT NULL,
  `motivo_flag`         VARCHAR(200) NOT NULL,
  `palavras_detectadas` VARCHAR(500) DEFAULT NULL,
  `referencia_slug`     VARCHAR(200) DEFAULT NULL,
  `acao_tomada`         ENUM('pendente','mantido','removido') NOT NULL DEFAULT 'pendente',
  `criado_em`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `revisado_em`         DATETIME     DEFAULT NULL,
  `revisado_por`        VARCHAR(100) DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_cfl_comentario` (`comentario_id`),
  KEY `idx_cfl_acao`       (`acao_tomada`),
  KEY `idx_cfl_data`       (`criado_em`),
  CONSTRAINT `fk_cfl_comentario` FOREIGN KEY (`comentario_id`) REFERENCES `comentarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Formulário de contato ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `contato` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome`      VARCHAR(120) NOT NULL,
  `email`     VARCHAR(255) NOT NULL,
  `assunto`   VARCHAR(200) DEFAULT NULL,
  `mensagem`  TEXT         NOT NULL,
  `ip`        VARCHAR(45)  NOT NULL DEFAULT '',
  `lida`      TINYINT(1)   NOT NULL DEFAULT 0,
  `criado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Rastreamento de disparos de newsletter por post ───────────────
CREATE TABLE IF NOT EXISTS `newsletter_disparos` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_slug`     VARCHAR(160) NOT NULL,
  `total_envios`  INT UNSIGNED NOT NULL DEFAULT 0,
  `total_erros`   INT UNSIGNED NOT NULL DEFAULT 0,
  `disparado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `disparado_por` VARCHAR(100) DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_nd_post` (`post_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ================================================================
-- PARTE 4 — NEWSLETTER E MARKETING
-- (inclui migration_email_marketing.sql: coluna origem +
--  migration_blog_avancado.sql: newsletter_disparos já acima)
-- ================================================================

-- ── Newsletter com double opt-in, preferências e origem ───────────
CREATE TABLE IF NOT EXISTS `newsletter` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`               VARCHAR(255) NOT NULL,
  `nome`                VARCHAR(120) DEFAULT NULL,
  `whatsapp`            VARCHAR(25)  DEFAULT NULL,
  `ip`                  VARCHAR(45)  DEFAULT NULL,
  `origem`              VARCHAR(80)  DEFAULT NULL  COMMENT 'home, blog, livro_slug, popup_saida, etc.',
  `status`              ENUM('pendente','ativo','descadastrado') NOT NULL DEFAULT 'pendente',
  `token_verificacao`   VARCHAR(64)  DEFAULT NULL,
  `token_expira`        DATETIME     DEFAULT NULL,
  `pref_bastidores`     TINYINT(1)   NOT NULL DEFAULT 1,
  `pref_reflexao`       TINYINT(1)   NOT NULL DEFAULT 1,
  `pref_escritor`       TINYINT(1)   NOT NULL DEFAULT 1,
  `pref_livros`         TINYINT(1)   NOT NULL DEFAULT 1,
  `descad_em`           DATETIME     DEFAULT NULL,
  `created_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email`    (`email`),
  KEY `idx_status`         (`status`),
  KEY `idx_origem`         (`origem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `newsletter_log` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`        VARCHAR(45)  NOT NULL,
  `tentativa` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Campanhas de e-mail marketing ────────────────────────────────
CREATE TABLE IF NOT EXISTS `campanhas` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome`          VARCHAR(200) NOT NULL,
  `tipo`          ENUM('newsletter','lancamento','promocao','reengajamento','boas_vindas','recompensa','destaque','push','outro') NOT NULL DEFAULT 'newsletter',
  `segmento`      VARCHAR(80)  NOT NULL DEFAULT 'todos',
  `assunto_email` VARCHAR(300) DEFAULT NULL,
  `corpo_html`    LONGTEXT     DEFAULT NULL,
  `corpo_texto`   TEXT         DEFAULT NULL,
  `agendado_para` DATETIME     DEFAULT NULL,
  `status`        ENUM('rascunho','agendada','enviando','enviada','cancelada') NOT NULL DEFAULT 'rascunho',
  `criado_em`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_status`    (`status`),
  KEY `idx_criado_em` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campanhas_envios` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `campanha_id` INT UNSIGNED NOT NULL,
  `email`       VARCHAR(255) NOT NULL,
  `status`      ENUM('enviado','falhou','descadastrado') NOT NULL DEFAULT 'enviado',
  `enviado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_campanha_id` (`campanha_id`),
  KEY `idx_email`       (`email`),
  CONSTRAINT `fk_cenv_campanha` FOREIGN KEY (`campanha_id`) REFERENCES `campanhas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Interesses literários por usuário (segmentação dinâmica) ──────
CREATE TABLE IF NOT EXISTS `usuario_interesses` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`   INT UNSIGNED NOT NULL,
  `categoria`    VARCHAR(80)  NOT NULL COMMENT 'Valor do campo categoria nos posts',
  `contagem`     INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Nº de posts lidos nesta categoria',
  `ultima_vista` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_cat` (`usuario_id`, `categoria`),
  KEY `idx_cat`               (`categoria`),
  KEY `idx_ultima`            (`ultima_vista`),
  CONSTRAINT `fk_int_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Rastreia categorias de posts mais lidas por usuário — segmentação de campanhas';


-- ================================================================
-- PARTE 5 — BIO / LINK-IN-BIO
-- (migration_bio.sql + migration_bio_v2.sql)
-- ================================================================

CREATE TABLE IF NOT EXISTS `bio_links` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titulo`    VARCHAR(120) NOT NULL,
  `subtitulo` VARCHAR(200) DEFAULT NULL,
  `url`       VARCHAR(500) NOT NULL,
  `icone`     VARCHAR(50)  DEFAULT NULL COMMENT 'Classe Font Awesome, ex: fa-book',
  `tipo`      ENUM('link','destaque') NOT NULL DEFAULT 'link',
  `ativo`     TINYINT(1)   NOT NULL DEFAULT 1,
  `ordem`     SMALLINT UNSIGNED NOT NULL DEFAULT 99,
  `criado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_ativo_ordem` (`ativo`, `ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bio_config` (
  `chave` VARCHAR(80) NOT NULL,
  `valor` TEXT        DEFAULT NULL,
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bio_clicks` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `link_id`    INT UNSIGNED DEFAULT NULL COMMENT 'ID em bio_links (NULL = link dinâmico)',
  `link_slug`  VARCHAR(100) DEFAULT NULL COMMENT 'Slug para links dinâmicos',
  `origem`     VARCHAR(300) DEFAULT NULL COMMENT 'utm_source ou referrer',
  `ip_hash`    VARCHAR(64)  NOT NULL,
  `clicado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_bc_link_id`  (`link_id`),
  KEY `idx_bc_link_slug`(`link_slug`),
  KEY `idx_bc_clicado`  (`clicado_em`),
  KEY `idx_bc_origem`   (`origem`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados padrão da bio
INSERT IGNORE INTO `bio_config` (`chave`, `valor`) VALUES
  ('nome',       'Robério Diógenes'),
  ('subtitulo',  'Escritor · Literatura Brasileira'),
  ('foto',       'img/autor2.jpg'),
  ('instagram',  'https://instagram.com/diogenesroberio'),
  ('whatsapp',   'https://wa.me/5585996409818'),
  ('telegram',   'https://t.me/5585996409818'),
  ('linkedin',   'https://linkedin.com/in/roberio-diogenes'),
  ('email',      'mailto:contato@roberiodiogenes.com'),
  ('cor_fundo',  '#0D0A07'),
  ('cor_acento', '#B8860B');

INSERT IGNORE INTO `bio_links` (`titulo`, `subtitulo`, `url`, `icone`, `tipo`, `ordem`) VALUES
  ('Biblioteca de Obras',    'Romances, contos e ficção literária',           '/livros.html',                          'fa-book',              'destaque', 1),
  ('Diário do Escritor',     'Reflexões, bastidores e processo criativo',     '/blog.html',                            'fa-pen-nib',           'link',     2),
  ('Leitor Online',          'Leia no navegador, sem app',                    '/leitor/index.html',                    'fa-book-open',         'link',     3),
  ('Planos de Assinatura',   'Acesso completo à biblioteca',                  '/pagamento/assinatura.html',            'fa-crown',             'link',     4),
  ('Sobre o Autor',          'A história por trás das histórias',             '/autor.html',                           'fa-user-pen',          'link',     5),
  ('Presentear alguém',      'Dê um livro de presente com 20% off',          '/presentear.html',                      'fa-gift',              'link',     6),
  ('Newsletter Gratuita',    'Novos posts e capítulos exclusivos no e-mail', '/blog.html#newsletter',                  'fa-envelope-open-text','link',     7),
  ('Contato para Imprensa',  'Entrevistas, resenhas e parcerias',             '/contato.html?assunto=imprensa',        'fa-newspaper',         'link',     8);


-- ================================================================
-- PARTE 6 — COMÉRCIO E PAGAMENTOS
-- (planos, assinaturas, compras, carrinhos, presentes, cupons)
-- (inclui migration_vendas.sql com preços atualizados)
-- ================================================================

CREATE TABLE IF NOT EXISTS `planos` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `slug`         VARCHAR(50)   NOT NULL,
  `nome`         VARCHAR(100)  NOT NULL,
  `descricao`    TEXT          DEFAULT NULL,
  `preco`        DECIMAL(8,2)  NOT NULL,
  `duracao_dias` SMALLINT UNSIGNED NOT NULL,
  `ativo`        TINYINT(1)    NOT NULL DEFAULT 1,
  `criado_em`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Preços atualizados (migration_vendas)
INSERT IGNORE INTO `planos` (slug, nome, descricao, preco, duracao_dias) VALUES
  ('mensal',     'Plano Mensal',     'Acesso completo à biblioteca por 30 dias. R$29,90/mês.',            29.90,  30),
  ('trimestral', 'Plano Trimestral', 'Acesso completo à biblioteca por 3 meses. Equivale a R$23,30/mês.', 69.90,  90),
  ('semestral',  'Plano Semestral',  'Acesso completo à biblioteca por 6 meses. Equivale a R$19,98/mês.', 119.90, 180),
  ('anual',      'Plano Anual',      'Acesso completo à biblioteca por 1 ano. Equivale a R$12,90/mês.',   154.80, 365);

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
  CONSTRAINT `fk_assin_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assin_plano`   FOREIGN KEY (`plano_id`)   REFERENCES `planos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `compras` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`    INT UNSIGNED NOT NULL,
  `livro_slug`    VARCHAR(100) NOT NULL,
  `preco_pago`    DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `status`        ENUM('aprovada','pendente','cancelada','reembolsada') NOT NULL DEFAULT 'pendente',
  `gateway`       VARCHAR(50)  DEFAULT NULL,
  `ref_externa`   VARCHAR(200) DEFAULT NULL,
  `comprado_em`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_compra`    (`usuario_id`, `livro_slug`),
  KEY `idx_usuario`         (`usuario_id`),
  KEY `idx_livro_slug`      (`livro_slug`),
  KEY `idx_status`          (`status`),
  KEY `idx_comprado_em`     (`comprado_em`),
  CONSTRAINT `fk_compra_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Carrinhos de compra
--    Coluna correta é `itens` (não `itens_json` — confirmado pelo código)
--    Inclui colunas de migration_crons.sql (lembrete_env, lembrete_em)
-- ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `carrinhos` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`   INT UNSIGNED NOT NULL,
  `itens`        TEXT         NOT NULL DEFAULT '[]' COMMENT 'JSON com os itens do carrinho',
  `em_checkout`  TINYINT(1)   NOT NULL DEFAULT 0,
  `lembrete_env` TINYINT(1)   NOT NULL DEFAULT 0   COMMENT 'E-mail de abandono já enviado',
  `lembrete_em`  DATETIME     DEFAULT NULL          COMMENT 'Quando o lembrete foi enviado',
  `checkout_em`  DATETIME     DEFAULT NULL,
  `atualizado_em` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario`         (`usuario_id`),
  KEY `idx_carr_lembrete`         (`lembrete_env`, `atualizado_em`),
  CONSTRAINT `fk_carr_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Presentes digitais ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `presentes` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `comprador_id`      INT UNSIGNED NOT NULL,
  `livro_slug`        VARCHAR(100) NOT NULL,
  `email_presenteado` VARCHAR(255) NOT NULL,
  `nome_presenteado`  VARCHAR(150) DEFAULT NULL,
  `dedicatoria`       TEXT         DEFAULT NULL,
  `preco_pago`        DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `token_acesso`      VARCHAR(100) NOT NULL,
  `status`            ENUM('pendente','aprovado','resgatado','cancelado') NOT NULL DEFAULT 'pendente',
  `ref_externa`       VARCHAR(200) DEFAULT NULL,
  `gateway`           VARCHAR(50)  DEFAULT NULL,
  `resgatado_por`     INT UNSIGNED DEFAULT NULL,
  `resgatado_em`      DATETIME     DEFAULT NULL,
  `criado_em`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token`           (`token_acesso`),
  KEY `idx_comprador`             (`comprador_id`),
  KEY `idx_email_presenteado`     (`email_presenteado`),
  KEY `idx_status`                (`status`),
  CONSTRAINT `fk_pres_comprador` FOREIGN KEY (`comprador_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Cupons de desconto ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `cupons` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo`          VARCHAR(50)  NOT NULL,
  `tipo`            ENUM('percentual','fixo') NOT NULL DEFAULT 'percentual',
  `valor`           DECIMAL(8,2) NOT NULL,
  `usos_max`        SMALLINT UNSIGNED DEFAULT NULL COMMENT 'NULL = ilimitado',
  `usos_atuais`     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `valido_ate`      DATETIME     DEFAULT NULL,
  `ativo`           TINYINT(1)   NOT NULL DEFAULT 1,
  `criado_em`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_codigo` (`codigo`),
  KEY `idx_ativo`        (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ================================================================
-- PARTE 7 — LEITOR ONLINE
-- (banco_completo + migration_crons: leitor_lembretes_enviados)
-- ================================================================

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
  CONSTRAINT `fk_anot_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leitor_marcacoes` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `livro_slug` VARCHAR(100) NOT NULL,
  `capitulo`   SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `trecho`     TEXT         NOT NULL,
  `cor`        VARCHAR(10)  NOT NULL DEFAULT '#FFD700',
  `nota`       TEXT         DEFAULT NULL,
  `criado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_usuario_livro` (`usuario_id`, `livro_slug`),
  CONSTRAINT `fk_marc_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leitor_preferencias` (
  `usuario_id`     INT UNSIGNED NOT NULL,
  `fonte`          ENUM('serifada','sans','manuscrito','classica') NOT NULL DEFAULT 'serifada',
  `tamanho_fonte`  TINYINT UNSIGNED NOT NULL DEFAULT 18,
  `fundo_leitura`  ENUM('branco','bege','cinza','preto')          NOT NULL DEFAULT 'bege',
  `largura_coluna` ENUM('estreita','media','larga')                NOT NULL DEFAULT 'media',
  `altura_linha`   DECIMAL(3,1) NOT NULL DEFAULT 1.8,
  `atualizado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`usuario_id`),
  CONSTRAINT `fk_pref_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Throttle de lembretes de leitura (migration_crons) ────────────
CREATE TABLE IF NOT EXISTS `leitor_lembretes_enviados` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `livro_slug` VARCHAR(100) NOT NULL,
  `enviado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lembrete`  (`usuario_id`, `livro_slug`),
  KEY `idx_enviado_em`      (`enviado_em`),
  CONSTRAINT `fk_lem_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Throttle de 14 dias para lembretes de leitura';


-- ================================================================
-- PARTE 8 — PRÉ-LANÇAMENTO / LISTA DE ESPERA
-- (migration_lista_espera.sql / migration_pre_lancamento.sql)
-- ================================================================

CREATE TABLE IF NOT EXISTS `pre_lancamentos` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`            VARCHAR(100) NOT NULL,
  `titulo`          VARCHAR(200) NOT NULL,
  `subtitulo`       VARCHAR(300) DEFAULT NULL,
  `descricao`       TEXT         DEFAULT NULL COMMENT 'Apresentação do livro para a landing page',
  `capa_img`        VARCHAR(500) DEFAULT NULL,
  `data_lancamento` DATE         DEFAULT NULL COMMENT 'Data prevista (exibe countdown)',
  `brinde_titulo`   VARCHAR(200) DEFAULT NULL COMMENT 'Ex: "Primeiro capítulo gratuito"',
  `brinde_html`     LONGTEXT     DEFAULT NULL COMMENT 'HTML do brinde enviado imediatamente ao lead',
  `ativo`           TINYINT(1)   NOT NULL DEFAULT 1,
  `lancado`         TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 após disparo do e-mail de lançamento',
  `criado_em`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pre_lancamento_leads` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lancamento_id`     INT UNSIGNED NOT NULL,
  `nome`              VARCHAR(120) DEFAULT NULL,
  `email`             VARCHAR(255) NOT NULL,
  `ip_hash`           CHAR(64)     NOT NULL DEFAULT '',
  `brinde_enviado`    TINYINT(1)   NOT NULL DEFAULT 0,
  `lancamento_enviado` TINYINT(1)  NOT NULL DEFAULT 0,
  `inscrito_em`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lead`          (`lancamento_id`, `email`),
  KEY `idx_ll_lancamento`       (`lancamento_id`),
  KEY `idx_ll_email`            (`email`),
  KEY `idx_ll_brinde`           (`brinde_enviado`),
  CONSTRAINT `fk_lead_lancamento`
    FOREIGN KEY (`lancamento_id`) REFERENCES `pre_lancamentos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ================================================================
-- PARTE 9 — ANALYTICS
-- ================================================================

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
  KEY `idx_visit_date`        (`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ================================================================
-- PARTE 10 — VIEWS
-- ================================================================

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

CREATE OR REPLACE VIEW `vw_acesso_leitura` AS
SELECT c.usuario_id, c.livro_slug, 'compra' AS tipo_acesso, NULL AS expira_em
FROM `compras` c WHERE c.status = 'aprovada'
UNION ALL
SELECT a.usuario_id, l.slug AS livro_slug, 'assinatura' AS tipo_acesso, a.expira_em
FROM `assinaturas` a
CROSS JOIN `livros` l
WHERE a.status = 'ativa' AND a.expira_em > NOW() AND l.ativo = 1;

CREATE OR REPLACE VIEW `vw_dashboard` AS
SELECT
  (SELECT COUNT(*) FROM usuarios WHERE ativo = 1)                                  AS total_usuarios,
  (SELECT COUNT(*) FROM assinaturas WHERE status='ativa' AND expira_em > NOW())    AS assinaturas_ativas,
  (SELECT COUNT(*) FROM compras WHERE status = 'aprovada')                         AS total_compras,
  (SELECT COALESCE(SUM(preco_pago),0) FROM compras WHERE status='aprovada'
   AND YEAR(comprado_em)=YEAR(NOW()) AND MONTH(comprado_em)=MONTH(NOW()))          AS receita_mes_compras,
  (SELECT COALESCE(SUM(p.preco),0) FROM assinaturas a JOIN planos p ON p.id=a.plano_id
   WHERE a.status='ativa'
   AND YEAR(a.inicio_em)=YEAR(NOW()) AND MONTH(a.inicio_em)=MONTH(NOW()))          AS receita_mes_assinaturas,
  (SELECT COUNT(*) FROM newsletter WHERE status='ativo')                            AS leads_newsletter,
  (SELECT COUNT(*) FROM pre_lancamento_leads)                                       AS leads_pre_lancamento;


-- ================================================================
-- PARTE 11 — STORED PROCEDURES E EVENTOS
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
  DELETE FROM `magic_links`    WHERE `expira_em` < NOW() AND `usado_em` IS NULL;
END //
DELIMITER ;

DROP PROCEDURE IF EXISTS `limpar_logs_antigos`;
DELIMITER //
CREATE PROCEDURE `limpar_logs_antigos`()
BEGIN
  DELETE FROM `auth_log`       WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 30 DAY);
  DELETE FROM `newsletter_log` WHERE `tentativa`   < DATE_SUB(NOW(), INTERVAL  7 DAY);
  DELETE FROM `admin_log`      WHERE `criado_em`   < DATE_SUB(NOW(), INTERVAL 90 DAY);
  DELETE FROM `leitor_lembretes_enviados` WHERE `enviado_em` < DATE_SUB(NOW(), INTERVAL 90 DAY);
END //
DELIMITER ;

CREATE EVENT IF NOT EXISTS `evt_limpar_tokens`
  ON SCHEDULE EVERY 1 DAY STARTS CURRENT_TIMESTAMP
  DO CALL `limpar_tokens_expirados`();

CREATE EVENT IF NOT EXISTS `evt_limpar_logs`
  ON SCHEDULE EVERY 1 WEEK STARTS CURRENT_TIMESTAMP
  DO CALL `limpar_logs_antigos`();

SET foreign_key_checks = 1;

-- ================================================================
-- ✓ RESUMO — banco_unificado.sql v5.0 (junho/2026)
-- ================================================================
-- TABELAS (38):
--   Auth:         usuarios, password_reset, magic_links, admin_users,
--                 auth_log, admin_log
--   Catálogo:     livros, favoritos, avaliacoes, downloads_log,
--                 livros_favoritos, downloads
--   Blog:         clusters, enquetes, enquetes_opcoes, enquetes_respostas,
--                 posts, curtidas, posts_lidos, comentarios,
--                 comentario_curtidas, comentarios_flags_log,
--                 contato, newsletter_disparos
--   Newsletter:   newsletter, newsletter_log, campanhas, campanhas_envios,
--                 usuario_interesses
--   Bio:          bio_links, bio_config, bio_clicks
--   Comércio:     planos, assinaturas, compras, carrinhos,
--                 presentes, cupons
--   Leitor:       leitor_progresso, leitor_anotacoes, leitor_marcacoes,
--                 leitor_preferencias, leitor_lembretes_enviados
--   Pré-lançamento: pre_lancamentos, pre_lancamento_leads
--   Analytics:    visitas, visitas_log
--
-- VIEWS (3):      vw_ultimas_leituras, vw_acesso_leitura, vw_dashboard
-- PROCEDURES (4): verificar_acesso, limpar_dados_leitor,
--                 limpar_tokens_expirados, limpar_logs_antigos
-- EVENTOS (2):    evt_limpar_tokens, evt_limpar_logs
--
-- DADOS INICIAIS:
--   Admin: usuário=admin | senha=RD@2025admin (TROQUE APÓS O 1º LOGIN)
--   11 livros no catálogo
--   4 planos de assinatura (mensal/trimestral/semestral/anual)
--   Configurações e links padrão da bio
--   Contador de visitas inicializado
-- ================================================================
