-- ================================================================
-- ROBÉRIO DIÓGENES — banco_completo.sql  v5.0
-- ARQUIVO ÚNICO E DEFINITIVO — substitui todos os SQLs anteriores
--
-- COMO USAR (instalação do zero):
--   1. phpMyAdmin → criar banco "roberio_site" (utf8mb4, utf8mb4_unicode_ci)
--   2. Selecionar o banco → aba SQL → colar tudo → Executar
--
-- COMO USAR (atualização de banco existente):
--   Execute normalmente — todos os comandos usam IF NOT EXISTS e
--   ALTER TABLE ... ADD COLUMN IF NOT EXISTS, portanto são idempotentes.
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

CREATE TABLE IF NOT EXISTS `password_reset` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED NOT NULL,
  `token`       VARCHAR(255) NOT NULL,
  `expira_em`   DATETIME     NOT NULL,
  `criado_em`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usado_em`    DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token`    (`token`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_expira_em`  (`expira_em`),
  CONSTRAINT `fk_pr_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`     VARCHAR(80)  NOT NULL,
  `password`     VARCHAR(255) NOT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_login` DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin padrão: usuário=admin | senha=RD@2025admin  ← TROQUE APÓS O 1º LOGIN
INSERT IGNORE INTO `admin_users` (`username`, `password`)
VALUES ('admin', '$2y$12$eImiTXuWVxfM37uY4JANjO6RFV1bGVJNX6aGQ6MjaTN6tQqaHxcPC');

CREATE TABLE IF NOT EXISTS `auth_log` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`         VARCHAR(45)  NOT NULL,
  `action`     VARCHAR(30)  NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_action` (`ip`, `action`),
  KEY `idx_created`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- ================================================================

CREATE TABLE IF NOT EXISTS `livros` (
  `id`              INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `slug`            VARCHAR(100)   NOT NULL,
  `tipo`            ENUM('livro','conto') NOT NULL DEFAULT 'livro',
  `titulo`          VARCHAR(200)   NOT NULL,
  `subtitulo`       VARCHAR(200)   DEFAULT NULL,
  `genero`          VARCHAR(80)    DEFAULT NULL,
  `sinopse`         TEXT           DEFAULT NULL,
  `capa_img`        VARCHAR(200)   DEFAULT NULL,
  `arquivo_pdf`     VARCHAR(200)   DEFAULT NULL,
  `arquivo_epub`    VARCHAR(200)   DEFAULT NULL,
  `pasta_conteudo`  VARCHAR(200)   DEFAULT NULL,
  `formato`         ENUM('html','epub') NOT NULL DEFAULT 'epub',
  `total_capitulos` SMALLINT UNSIGNED DEFAULT 1,
  `preco`           DECIMAL(8,2)   DEFAULT NULL,
  `preco_promocao`  DECIMAL(8,2)   DEFAULT NULL,
  `ativo`           TINYINT(1)     NOT NULL DEFAULT 1,
  `gratuito`        TINYINT(1)     NOT NULL DEFAULT 0,
  `destaque`        TINYINT(1)     NOT NULL DEFAULT 0,
  `novo`            TINYINT(1)     NOT NULL DEFAULT 0,
  `badges`          VARCHAR(200)   DEFAULT NULL,
  `link_amazon`     VARCHAR(500)   DEFAULT NULL,
  `data_pub`        DATE           DEFAULT NULL,
  `ordem`           SMALLINT UNSIGNED NOT NULL DEFAULT 99,
  `criado_em`       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug`     (`slug`),
  KEY `idx_tipo`     (`tipo`),
  KEY `idx_genero`   (`genero`),
  KEY `idx_destaque` (`destaque`),
  KEY `idx_ordem`    (`ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catálogo completo (INSERT IGNORE = idempotente)
-- arquivo_epub só é preenchido quando o arquivo epub REAL existe em livros-conteudo/slug/
-- Livros sem epub válido usam formato='html' e cap01.html, cap02.html etc.
INSERT IGNORE INTO `livros`
  (slug, tipo, titulo, arquivo_pdf, arquivo_epub, formato, preco, total_capitulos, pasta_conteudo, capa_img)
VALUES
  /* Livros — todos com epub válido */
  ('lumen',               'livro','Lúmen – A Outra Metade do Céu', 'lumen-capitulo-1.pdf',              'lumen.epub',                    'epub',19.90,1,'livros-conteudo/lumen/',               'img/lumen.jpg'),
  ('jogo-das-mascaras',   'livro','O Jogo das Máscaras',           'O-jogo-das-mascas-capitulo-1.pdf',  'o-jogo-das-mascaras.epub',      'epub',19.90,1,'livros-conteudo/jogo-das-mascaras/',   'img/jogo-das-mascaras.jpg'),
  ('a-setima-lei',        'livro','A Sétima Lei',                  'A-setima-lei-capitulo-1.pdf',       'a-setima-lei.epub',             'epub',19.90,1,'livros-conteudo/a-setima-lei/',        'img/a-setima-lei.jpg'),
  ('a-marca-da-besta',    'livro','A Marca da Besta',              'a-marca-da-besta-capitulo-1.pdf',   'a-marca-da-besta.epub',         'epub',19.90,1,'livros-conteudo/a-marca-da-besta/',    'img/a-marca-da-besta.jpg'),
  ('caminhos-de-outono',  'livro','Caminhos de Outono',            'caminhos-de-outono-capitulo-1.pdf', 'caminhos-de-outono.epub',       'epub',19.90,1,'livros-conteudo/caminhos-de-outono/',  'img/caminhos-de-outono.jpg'),
  ('cartas-do-passado',   'livro','Cartas do Passado',             'cartas-do-passado-capitulo-1.pdf',  'cartas-do-passado.epub',        'epub',19.90,1,'livros-conteudo/cartas-do-passado/',   'img/cartas-do-passado.jpg'),
  ('das-coisas-que-o-amor-faz','livro','Das Coisas que o Amor Faz','das-coisas-que-o-amor-faz-capitulo-1.pdf','das-coisas-que-o-amor-faz.epub','epub',19.90,1,'livros-conteudo/das-coisas-que-o-amor-faz/','img/das-coisas-que-o-amor-faz.jpg'),
  ('genesis',             'livro','Gênesis',                       'genesis-capitulo-1.pdf',            'genesis.epub',                  'epub',19.90,1,'livros-conteudo/genesis/',             'img/genesis.jpg'),
  ('mares-secretas-do-amor','livro','As Marés Secretas do Amor',   'as-mares-secretas-do-amor-capitulo-1.pdf','as-mares-secretas-do-amor.epub','epub',19.90,1,'livros-conteudo/mares-secretas-do-amor/','img/mares-secretas.jpg'),
  ('o-abismo-das-almas',  'livro','O Abismo das Almas',            'o-abismo-das-almas-capitulo-1.pdf', 'o-abismo-das-almas.epub',       'epub',19.90,1,'livros-conteudo/o-abismo-das-almas/',  'img/o-abismo-das-almas.jpg'),
  ('rosas-e-espinhos',    'livro','Rosas e Espinhos',              'rosas-e-espinhos-capitulo-1.pdf',   'rosas-e-espinhos.epub',         'epub',19.90,1,'livros-conteudo/rosas-e-espinhos/',    'img/rosas-e-espinhos.jpg'),
  /* Contos gratuitos */
  ('o-farol-do-afogado',  'conto','O Farol do Afogado',  NULL,'o-farol-do-afogado.epub','epub',0.00,1,'livros-conteudo/o-farol-do-afogado/', 'img/o-farol-do-afogado-conto.jpg'),
  ('linhas-e-agulhas',    'conto','Linhas e Agulhas',    NULL,'linhas-e-agulhas.epub',  'epub',0.00,1,'livros-conteudo/linhas-e-agulhas/',   'img/linhas-e-agulhas.jpg'),
  ('o-quarto-das-moscas', 'conto','O Quarto das Moscas', NULL,NULL,                     'html',0.00,1,'livros-conteudo/o-quarto-das-moscas/','img/o-quarto-das-moscas.jpg');
-- Marcar contos como gratuitos
UPDATE `livros` SET `gratuito` = 1, `preco` = 0.00
WHERE `tipo` = 'conto';

-- ================================================================
-- PARTE 3 — INTERAÇÕES COM LIVROS
-- ================================================================

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

CREATE TABLE IF NOT EXISTS `comentarios` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED  DEFAULT NULL,
  `referencia` VARCHAR(100)  DEFAULT NULL,
  `tipo`       ENUM('livro','blog') DEFAULT 'livro',
  `livro_slug` VARCHAR(100)  NOT NULL DEFAULT '',
  `nome`       VARCHAR(120)  NOT NULL DEFAULT '',
  `cidade`     VARCHAR(100)  DEFAULT NULL,
  `leu`        ENUM('sim','cap','nao','') DEFAULT '',
  `texto`      TEXT          NOT NULL,
  `ip`         VARCHAR(45)   NOT NULL DEFAULT '',
  `aprovado`   TINYINT(1)    NOT NULL DEFAULT 1,
  `criado_em`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_livro_aprovado`  (`livro_slug`, `aprovado`),
  KEY `idx_referencia_tipo` (`referencia`, `tipo`, `aprovado`),
  KEY `idx_usuario_id`      (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- ================================================================
-- PARTE 4 — NEWSLETTER
-- ================================================================

CREATE TABLE IF NOT EXISTS `newsletter` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`              VARCHAR(255) NOT NULL,
  `nome`               VARCHAR(120) DEFAULT NULL,
  `whatsapp`           VARCHAR(25)  DEFAULT NULL,
  `ip`                 VARCHAR(45)  DEFAULT NULL,
  `status`             ENUM('pendente','ativo','descadastrado') NOT NULL DEFAULT 'pendente',
  `origem`             VARCHAR(80)  DEFAULT NULL,
  `tags`               VARCHAR(200) DEFAULT NULL,
  `token_verificacao`  VARCHAR(64)  DEFAULT NULL,
  `token_expira`       DATETIME     DEFAULT NULL,
  `descad_em`          DATETIME     DEFAULT NULL,
  `pref_bastidores`    TINYINT(1)   NOT NULL DEFAULT 1,
  `pref_reflexao`      TINYINT(1)   NOT NULL DEFAULT 1,
  `pref_escritor`      TINYINT(1)   NOT NULL DEFAULT 1,
  `pref_livros`        TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email`  (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_token`  (`token_verificacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `newsletter_log` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`        VARCHAR(45)  NOT NULL,
  `tentativa` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PARTE 5 — VISITAS
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
  KEY `idx_visit_date` (`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PARTE 6 — BLOG
-- ================================================================

CREATE TABLE IF NOT EXISTS `posts` (
  `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `slug`          VARCHAR(160)     NOT NULL,
  `titulo`        VARCHAR(300)     NOT NULL,
  `subtitulo`     VARCHAR(400)     DEFAULT NULL,
  `categoria`     ENUM('bastidores','reflexao','escritor','livros') NOT NULL DEFAULT 'reflexao',
  `resumo`        TEXT             DEFAULT NULL,
  `conteudo`      LONGTEXT         NOT NULL,
  `imagem_url`    VARCHAR(500)     DEFAULT NULL,
  `audio_url`     VARCHAR(500)     DEFAULT NULL,
  `tempo_leitura` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `livro_slug`    VARCHAR(160)     DEFAULT NULL,
  `status`        ENUM('rascunho','publicado','oculto') NOT NULL DEFAULT 'rascunho',
  `destaque`      TINYINT(1)       NOT NULL DEFAULT 0,
  `publicado_em`  DATETIME         DEFAULT NULL,
  `created_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug`              (`slug`),
  KEY `idx_status_publicado` (`status`, `publicado_em`),
  KEY `idx_categoria`        (`categoria`),
  KEY `idx_destaque`         (`destaque`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `posts` (slug,titulo,subtitulo,categoria,resumo,conteudo,imagem_url,tempo_leitura,status,destaque,publicado_em) VALUES
('post-01','Por trás da serpente e da maçã: os bastidores de A Sétima Lei','Como nasceu a capa, a ideia, e o silêncio que precedeu tudo isso','bastidores','Cada livro começa muito antes da primeira palavra. Aqui conto como nasceu a capa, a ideia, e o silêncio que precedeu tudo isso.','<p>Cada livro começa muito antes da primeira palavra. Antes da sinopse, antes do nome, antes mesmo da ideia clara. Começa numa inquietação — algo que incomoda sem ter forma.</p><p><em>A Sétima Lei</em> nasceu de uma pergunta que me perseguiu durante meses: o que acontece com a alma de quem descobre que as regras que sempre obedeceu foram escritas por alguém que não merecia escrever nada?</p>','img/post-bastidores-setima-lei.jpg',8,'publicado',1,'2025-04-01 10:00:00'),
('post-02','O peso do silêncio entre duas pessoas que se amaram',NULL,'reflexao','Existe um idioma que só existe entre pessoas que já se amaram.','<p>Existe um idioma que só existe entre pessoas que já se amaram. É feito de não-ditos, de gestos pela metade, de despedidas que ninguém sabe que foram as últimas.</p>','img/post-01.jpg',5,'publicado',0,'2025-03-01 10:00:00'),
('post-03','Por que escrevo sobre dor — e por que não me arrependo',NULL,'escritor','Há quem prefira histórias que consolam. Eu prefiro as que arrancam a venda dos olhos.','<p>Há quem prefira histórias que consolam. Eu prefiro as que arrancam a venda dos olhos. Não porque gosto de machucar — mas porque acredito que a dor vista de frente perde o poder de nos destruir.</p>',NULL,6,'publicado',0,'2025-02-01 10:00:00'),
('post-04','23h47 em Cascavel — onde as histórias nascem',NULL,'reflexao','Cada cidade tem uma hora em que se revela.','<p>Cada cidade tem uma hora em que se revela. Cascavel às 23h47 é um lugar que só existe quando o silêncio é profundo o suficiente para ter peso.</p>',NULL,4,'publicado',0,'2025-01-01 10:00:00'),
('post-05','Deus e o versículo incompleto — as origens de O Abismo das Almas',NULL,'livros','Todo livro de horror tem um medo real no centro.','<p>Todo livro de horror tem um medo real no centro. No Abismo das Almas, o medo é este: e se a humanidade descobrisse que sentir é o maior perigo de todos?</p>',NULL,7,'publicado',0,'2024-12-01 10:00:00'),
('post-06','O labirinto de Lúmen — como construí uma mente que não sabe se existe',NULL,'livros','Escrever Hannah foi o exercício mais perturbador que já fiz.','<p>Escrever Hannah foi o exercício mais perturbador que já fiz. Uma protagonista que não tem certeza se seus sonhos são memórias, e suas memórias são sonhos.</p>',NULL,9,'publicado',0,'2024-11-01 10:00:00'),
('post-07','Sobre a solidão que produz — e a que apenas consome',NULL,'escritor','Existe uma diferença entre a solidão que gera e a solidão que apenas esvazia.','<p>Existe uma diferença entre a solidão que gera e a solidão que apenas esvazia. Aprendi a distinguir as duas — e aprendi a escolher qual cultivar.</p>',NULL,5,'publicado',0,'2024-10-01 10:00:00');

CREATE TABLE IF NOT EXISTS `posts_lidos` (
  `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED     NOT NULL,
  `post_slug`  VARCHAR(160)     NOT NULL,
  `lido_em`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `progresso`  TINYINT UNSIGNED NOT NULL DEFAULT 100,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_post` (`usuario_id`, `post_slug`),
  KEY `idx_usuario` (`usuario_id`),
  CONSTRAINT `fk_lidos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `posts_curtidas` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_slug`  VARCHAR(160) NOT NULL,
  `usuario_id` INT UNSIGNED DEFAULT NULL,
  `ip`         VARCHAR(45)  NOT NULL,
  `criado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_curtida` (`post_slug`, `usuario_id`),
  KEY `idx_slug` (`post_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `newsletter_posts` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id`      INT UNSIGNED NOT NULL,
  `enviado_em`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_envios` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_post` (`post_id`),
  CONSTRAINT `fk_nlpost_post` FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PARTE 7 — SISTEMA DE PAGAMENTOS E ASSINATURAS
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

INSERT IGNORE INTO `planos` (slug, nome, descricao, preco, duracao_dias) VALUES
  ('mensal',    'Assinante Mensal',    'Acesso completo à biblioteca por 30 dias.',  19.90,  30),
  ('semestral', 'Assinante Semestral', 'Acesso completo à biblioteca por 6 meses.',  99.90, 180),
  ('anual',     'Assinante Anual',     'Acesso completo à biblioteca por 1 ano.',   179.90, 365);

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
  UNIQUE KEY `uq_compra`   (`usuario_id`, `livro_slug`),
  KEY `idx_usuario`        (`usuario_id`),
  KEY `idx_livro_slug`     (`livro_slug`),
  KEY `idx_status`         (`status`),
  CONSTRAINT `fk_compra_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PARTE 8 — LEITOR ONLINE (tabelas operacionais)
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
  `cfi_posicao`    VARCHAR(500)  DEFAULT NULL COMMENT 'Posição CFI para ePub',
  `concluido_em`   DATETIME      DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_progresso`    (`usuario_id`, `livro_slug`),
  KEY `idx_ultima_leitura`     (`ultima_leitura`),
  CONSTRAINT `fk_prog_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leitor_anotacoes` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`    INT UNSIGNED NOT NULL,
  `livro_slug`    VARCHAR(100) NOT NULL,
  `capitulo`      SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `texto`         TEXT         NOT NULL,
  `cor`           VARCHAR(20)  NOT NULL DEFAULT '#FFD700',
  `criado_em`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_livro` (`usuario_id`, `livro_slug`),
  CONSTRAINT `fk_anot_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leitor_marcacoes` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED NOT NULL,
  `livro_slug`  VARCHAR(100) NOT NULL,
  `capitulo`    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `trecho`      TEXT         NOT NULL,
  `cor`         ENUM('amarela','verde','rosa','azul') NOT NULL DEFAULT 'amarela',
  `nota`        TEXT         DEFAULT NULL,
  `criado_em`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_livro` (`usuario_id`, `livro_slug`),
  CONSTRAINT `fk_marc_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leitor_preferencias` (
  `usuario_id`     INT UNSIGNED NOT NULL,
  `fonte`          ENUM('serifada','classica','sans','manuscrito') NOT NULL DEFAULT 'serifada',
  `tamanho_fonte`  TINYINT UNSIGNED NOT NULL DEFAULT 18,
  `fundo_leitura`  ENUM('branco','bege','cinza','preto') NOT NULL DEFAULT 'bege',
  `largura_coluna` ENUM('estreita','media','larga') NOT NULL DEFAULT 'media',
  `altura_linha`   DECIMAL(3,1) NOT NULL DEFAULT 1.8,
  `ranking_opt_in` TINYINT(1)   NOT NULL DEFAULT 0,
  `atualizado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`usuario_id`),
  CONSTRAINT `fk_pref_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conquistas e medalhas por progresso de leitura
CREATE TABLE IF NOT EXISTS `leitura_conquistas` (
  `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `usuario_id`     INT UNSIGNED     NOT NULL,
  `livro_slug`     VARCHAR(120)     NOT NULL,
  `marco`          TINYINT UNSIGNED NOT NULL COMMENT '25, 50, 75, 90, 100',
  `medalha`        VARCHAR(10)      NOT NULL,
  `titulo`         VARCHAR(80)      NOT NULL,
  `conquistado_em` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_conquista` (`usuario_id`, `livro_slug`, `marco`),
  KEY `idx_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Erros ortográficos reportados pelos leitores
CREATE TABLE IF NOT EXISTS `leitura_erros_reportados` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED DEFAULT NULL,
  `livro_slug`  VARCHAR(120) NOT NULL,
  `capitulo`    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `trecho`      VARCHAR(500) NOT NULL,
  `tipo`        ENUM('ortografia','gramatica','pontuacao','digitacao','outro') NOT NULL DEFAULT 'ortografia',
  `observacao`  VARCHAR(500) DEFAULT NULL,
  `resolvido`   TINYINT(1)   NOT NULL DEFAULT 0,
  `criado_em`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_livro`     (`livro_slug`),
  KEY `idx_resolvido` (`resolvido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feedback de leitura ao concluir livro
CREATE TABLE IF NOT EXISTS `leitura_feedback` (
  `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED     NOT NULL,
  `livro_slug`  VARCHAR(120)     NOT NULL,
  `texto`       TEXT             DEFAULT NULL,
  `nota`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `criado_em`   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_feedback` (`usuario_id`, `livro_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Controle de lembretes de leitura (evita spam)
CREATE TABLE IF NOT EXISTS `leitura_lembretes_enviados` (
  `usuario_id` INT UNSIGNED NOT NULL,
  `livro_slug` VARCHAR(120) NOT NULL,
  `enviado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`usuario_id`, `livro_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PARTE 9 — MARKETING E CAMPANHAS
-- ================================================================

CREATE TABLE IF NOT EXISTS `campanhas` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nome`            VARCHAR(200)  NOT NULL,
  `tipo`            ENUM('lancamento','promocao','newsletter','reengajamento','boas_vindas','recompensa','destaque','outro') NOT NULL DEFAULT 'newsletter',
  `assunto_email`   VARCHAR(255)  DEFAULT NULL,
  `corpo_html`      LONGTEXT      DEFAULT NULL,
  `corpo_texto`     TEXT          DEFAULT NULL,
  `segmento`        ENUM('todos','newsletter','compradores','assinantes','inativos_30','inativos_90','sem_compra','personalizado') NOT NULL DEFAULT 'todos',
  `status`          ENUM('rascunho','agendada','enviando','enviada','cancelada') NOT NULL DEFAULT 'rascunho',
  `total_destinat`  INT UNSIGNED  NOT NULL DEFAULT 0,
  `total_enviados`  INT UNSIGNED  NOT NULL DEFAULT 0,
  `total_abertos`   INT UNSIGNED  NOT NULL DEFAULT 0,
  `total_cliques`   INT UNSIGNED  NOT NULL DEFAULT 0,
  `agendado_para`   DATETIME      DEFAULT NULL,
  `iniciado_em`     DATETIME      DEFAULT NULL,
  `concluido_em`    DATETIME      DEFAULT NULL,
  `criado_por`      INT UNSIGNED  DEFAULT NULL,
  `criado_em`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status`   (`status`),
  KEY `idx_tipo`     (`tipo`),
  KEY `idx_agendado` (`agendado_para`),
  CONSTRAINT `fk_camp_admin` FOREIGN KEY (`criado_por`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campanhas_envios` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `campanha_id`    INT UNSIGNED    NOT NULL,
  `email`          VARCHAR(255)    NOT NULL,
  `usuario_id`     INT UNSIGNED    DEFAULT NULL,
  `status`         ENUM('pendente','enviado','erro','descadastrou') NOT NULL DEFAULT 'pendente',
  `aberto_em`      DATETIME        DEFAULT NULL,
  `clicou_em`      DATETIME        DEFAULT NULL,
  `erro_msg`       VARCHAR(500)    DEFAULT NULL,
  `enviado_em`     DATETIME        DEFAULT NULL,
  `token_rastreio` CHAR(32)        DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_campanha` (`campanha_id`),
  KEY `idx_email`    (`email`),
  KEY `idx_status`   (`status`),
  KEY `idx_token`    (`token_rastreio`),
  CONSTRAINT `fk_env_campanha` FOREIGN KEY (`campanha_id`) REFERENCES `campanhas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PARTE 10 — VIEWS
-- ================================================================

CREATE OR REPLACE VIEW `vw_ultimas_leituras` AS
SELECT
  lp.usuario_id, lp.livro_slug, l.titulo, l.capa_img,
  lp.capitulo_atual, lp.percentual, lp.ultima_leitura, lp.concluido
FROM `leitor_progresso` lp
LEFT JOIN `livros` l ON l.slug = lp.livro_slug
ORDER BY lp.ultima_leitura DESC;

CREATE OR REPLACE VIEW `vw_acesso_leitura` AS
SELECT c.usuario_id, c.livro_slug, 'compra' AS tipo_acesso, NULL AS expira_em
FROM `compras` c WHERE c.status = 'aprovada'
UNION ALL
SELECT a.usuario_id, l.slug AS livro_slug, 'assinatura' AS tipo_acesso, a.expira_em
FROM `assinaturas` a CROSS JOIN `livros` l
WHERE a.status = 'ativa' AND a.expira_em > NOW() AND l.ativo = 1
UNION ALL
SELECT NULL AS usuario_id, l.slug AS livro_slug, 'gratuito' AS tipo_acesso, NULL AS expira_em
FROM `livros` l WHERE l.gratuito = 1 AND l.ativo = 1;

CREATE OR REPLACE VIEW `vw_dashboard` AS
SELECT
  (SELECT COUNT(*) FROM usuarios WHERE ativo=1)                                      AS total_usuarios,
  (SELECT COUNT(*) FROM assinaturas WHERE status='ativa' AND expira_em>NOW())        AS assinaturas_ativas,
  (SELECT COUNT(*) FROM compras WHERE status='aprovada')                             AS total_compras,
  (SELECT COALESCE(SUM(preco_pago),0) FROM compras WHERE status='aprovada'
   AND YEAR(comprado_em)=YEAR(NOW()) AND MONTH(comprado_em)=MONTH(NOW()))            AS receita_mes_compras,
  (SELECT COALESCE(SUM(p.preco),0) FROM assinaturas a JOIN planos p ON p.id=a.plano_id
   WHERE a.status='ativa'
   AND YEAR(a.inicio_em)=YEAR(NOW()) AND MONTH(a.inicio_em)=MONTH(NOW()))            AS receita_mes_assinaturas;

CREATE OR REPLACE VIEW `vw_leads` AS
SELECT 'usuario' AS origem_tipo, u.id AS ref_id, u.nome, u.email,
       u.created_at AS inscrito_em, u.ultimo_login AS ultima_atividade, u.ativo,
       (SELECT COUNT(*) FROM compras WHERE usuario_id=u.id AND status='aprovada') AS n_compras,
       (SELECT COUNT(*) FROM assinaturas WHERE usuario_id=u.id AND status='ativa' AND expira_em>NOW()) AS tem_assin,
       NULL AS tags
FROM `usuarios` u
UNION ALL
SELECT 'newsletter', n.id, COALESCE(n.nome,''), n.email,
       n.created_at, NULL, (n.status='ativo'), 0, 0, n.tags
FROM `newsletter` n WHERE n.email NOT IN (SELECT email FROM `usuarios`);

CREATE OR REPLACE VIEW `vw_segmentos` AS
SELECT 'todos'       AS segmento, COUNT(DISTINCT email) AS total FROM vw_leads WHERE ativo=1 UNION ALL
SELECT 'newsletter',   COUNT(*) FROM newsletter WHERE status='ativo' UNION ALL
SELECT 'compradores',  COUNT(DISTINCT usuario_id) FROM compras WHERE status='aprovada' UNION ALL
SELECT 'assinantes',   COUNT(*) FROM assinaturas WHERE status='ativa' AND expira_em>NOW() UNION ALL
SELECT 'inativos_30',  COUNT(*) FROM usuarios WHERE ativo=1 AND (ultimo_login IS NULL OR ultimo_login < DATE_SUB(NOW(),INTERVAL 30 DAY)) UNION ALL
SELECT 'inativos_90',  COUNT(*) FROM usuarios WHERE ativo=1 AND (ultimo_login IS NULL OR ultimo_login < DATE_SUB(NOW(),INTERVAL 90 DAY)) UNION ALL
SELECT 'sem_compra',   COUNT(*) FROM usuarios u WHERE ativo=1 AND NOT EXISTS (SELECT 1 FROM compras WHERE usuario_id=u.id AND status='aprovada');

-- ================================================================
-- PARTE 11 — STORED PROCEDURES E EVENTOS
-- ================================================================

DROP PROCEDURE IF EXISTS `limpar_tokens_expirados`;
DELIMITER //
CREATE PROCEDURE `limpar_tokens_expirados`()
BEGIN
  DELETE FROM `password_reset` WHERE `expira_em` < NOW() AND `usado_em` IS NULL;
  DELETE FROM `newsletter` WHERE `token_expira` < DATE_SUB(NOW(), INTERVAL 7 DAY) AND `status` = 'pendente';
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

CREATE EVENT IF NOT EXISTS `evt_limpar_tokens`
  ON SCHEDULE EVERY 1 DAY STARTS CURRENT_TIMESTAMP
  DO CALL `limpar_tokens_expirados`();

CREATE EVENT IF NOT EXISTS `evt_limpar_logs`
  ON SCHEDULE EVERY 1 WEEK STARTS CURRENT_TIMESTAMP
  DO CALL `limpar_logs_antigos`();


-- ================================================================
-- CORREÇÃO: atualizar livros existentes para formato e epub corretos
-- (seguro para bancos que já foram criados com os SQLs anteriores)
-- ================================================================

-- Livros com epub VÁLIDO
UPDATE `livros` SET `arquivo_epub`='lumen.epub',                     `formato`='epub' WHERE slug='lumen';
UPDATE `livros` SET `arquivo_epub`='O-jogo-das-mascas-capitulo-1.epub', `formato`='epub' WHERE slug='jogo-das-mascaras';
UPDATE `livros` SET `arquivo_epub`='cartas-do-passado.epub',         `formato`='epub' WHERE slug='cartas-do-passado';
UPDATE `livros` SET `arquivo_epub`='o-farol-do-afogado.epub',        `formato`='epub' WHERE slug='o-farol-do-afogado';
UPDATE `livros` SET `arquivo_epub`='linhas-e-agulhas.epub',          `formato`='epub' WHERE slug='linhas-e-agulhas';

-- Adicionar cfi_posicao a bancos existentes (idempotente)
ALTER TABLE `leitor_progresso`
  ADD COLUMN IF NOT EXISTS `cfi_posicao` VARCHAR(500) DEFAULT NULL AFTER `total_paginas`;

-- Livros sem epub válido — usar HTML
UPDATE `livros` SET `arquivo_epub`=NULL, `formato`='html'
WHERE slug IN ('a-setima-lei','a-marca-da-besta','caminhos-de-outono',
               'das-coisas-que-o-amor-faz','genesis','mares-secretas-do-amor',
               'o-abismo-das-almas','rosas-e-espinhos','o-quarto-das-moscas');

-- Garantir contos como gratuitos
UPDATE `livros` SET `gratuito`=1, `tipo`='conto'
WHERE slug IN ('o-farol-do-afogado','linhas-e-agulhas','o-quarto-das-moscas');


-- Corrigir nomes de arquivos epub em bancos existentes
UPDATE `livros` SET `arquivo_epub`='lumen.epub'                  WHERE slug='lumen';
UPDATE `livros` SET `arquivo_epub`='o-jogo-das-mascaras.epub'    WHERE slug='jogo-das-mascaras';
UPDATE `livros` SET `arquivo_epub`='a-setima-lei.epub'           WHERE slug='a-setima-lei';
UPDATE `livros` SET `arquivo_epub`='a-marca-da-besta.epub'       WHERE slug='a-marca-da-besta';
UPDATE `livros` SET `arquivo_epub`='caminhos-de-outono.epub'     WHERE slug='caminhos-de-outono';
UPDATE `livros` SET `arquivo_epub`='cartas-do-passado.epub'      WHERE slug='cartas-do-passado';
UPDATE `livros` SET `arquivo_epub`='das-coisas-que-o-amor-faz.epub' WHERE slug='das-coisas-que-o-amor-faz';
UPDATE `livros` SET `arquivo_epub`='genesis.epub'                WHERE slug='genesis';
UPDATE `livros` SET `arquivo_epub`='as-mares-secretas-do-amor.epub' WHERE slug='mares-secretas-do-amor';
UPDATE `livros` SET `arquivo_epub`='o-abismo-das-almas.epub'     WHERE slug='o-abismo-das-almas';
UPDATE `livros` SET `arquivo_epub`='rosas-e-espinhos.epub'       WHERE slug='rosas-e-espinhos';
UPDATE `livros` SET `arquivo_epub`='o-farol-do-afogado.epub'     WHERE slug='o-farol-do-afogado';
UPDATE `livros` SET `arquivo_epub`='linhas-e-agulhas.epub'       WHERE slug='linhas-e-agulhas';
UPDATE `livros` SET `arquivo_epub`=NULL, `formato`='html'        WHERE slug='o-quarto-das-moscas';
-- Garantir formato correto
UPDATE `livros` SET `formato`='epub' WHERE `arquivo_epub` IS NOT NULL AND `arquivo_epub` != '';

SET foreign_key_checks = 1;

-- ================================================================
-- ✓ RESUMO — v5.0 (arquivo único, idempotente)
-- ================================================================
-- TABELAS (29):
--   Auth: usuarios, password_reset, admin_users, auth_log, admin_log
--   Livros: livros, favoritos, avaliacoes, downloads_log, comentarios, contato
--   Newsletter: newsletter, newsletter_log, newsletter_posts
--   Blog: posts, posts_lidos, posts_curtidas
--   Visitas: visitas, visitas_log
--   Pagamentos: planos, assinaturas, compras
--   Leitor: leitor_progresso, leitor_anotacoes, leitor_marcacoes, leitor_preferencias
--           leitura_conquistas, leitura_erros_reportados, leitura_feedback,
--           leitura_lembretes_enviados
--   Marketing: campanhas, campanhas_envios
--
-- VIEWS (5): vw_ultimas_leituras, vw_acesso_leitura, vw_dashboard, vw_leads, vw_segmentos
-- PROCEDURES (2): limpar_tokens_expirados, limpar_logs_antigos
-- EVENTOS (2): evt_limpar_tokens, evt_limpar_logs
--
-- DADOS INICIAIS:
--   Admin: usuário=admin | senha=RD@2025admin  ← TROQUE APÓS O 1º LOGIN
--   14 livros/contos no catálogo (11 livros + 3 contos, contos gratuitos)
--   3 planos de assinatura
--   7 posts do blog
-- ================================================================
