-- ================================================================
-- ROBÉRIO DIÓGENES — banco_completo_v2.sql
-- Banco unificado e limpo — Leitor v3
-- Versão: 2.0  |  Data: 2026-05-31
--
-- INSTRUÇÕES:
--   1. No phpMyAdmin, selecione o banco roberio_site
--   2. Clique em SQL → cole este arquivo → Executar
--   OU: phpMyAdmin → Importar → selecione este arquivo
--
-- Este arquivo é idempotente (pode rodar mais de uma vez
-- sem duplicar dados, graças ao IF NOT EXISTS e INSERT IGNORE).
-- ================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ================================================================
-- PARTE 1 — AUTENTICAÇÃO E USUÁRIOS
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
  UNIQUE KEY `uq_token`      (`token`),
  KEY `idx_usuario_id`   (`usuario_id`),
  KEY `idx_expira_em`    (`expira_em`),
  CONSTRAINT `fk_pr_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE
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

-- Admin padrão: usuário=admin | senha=RD@2025admin ← TROQUE APÓS O 1º LOGIN
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
  CONSTRAINT `fk_al_admin` FOREIGN KEY (`admin_id`)
    REFERENCES `admin_users`(`id`) ON DELETE SET NULL
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
  `arquivo_epub`    VARCHAR(200)   DEFAULT NULL,
  `total_capitulos` SMALLINT UNSIGNED DEFAULT 1,
  `tempo_leitura`   SMALLINT UNSIGNED DEFAULT NULL COMMENT 'minutos estimados',
  `preco`           DECIMAL(8,2)   DEFAULT NULL,
  `preco_promocao`  DECIMAL(8,2)   DEFAULT NULL,
  `ativo`           TINYINT(1)     NOT NULL DEFAULT 1,
  `gratuito`        TINYINT(1)     NOT NULL DEFAULT 0,
  `destaque`        TINYINT(1)     NOT NULL DEFAULT 0,
  `novo`            TINYINT(1)     NOT NULL DEFAULT 0,
  `link_amazon`     VARCHAR(500)   DEFAULT NULL,
  `data_pub`        DATE           DEFAULT NULL,
  `ordem`           SMALLINT UNSIGNED NOT NULL DEFAULT 99,
  `criado_em`       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em`   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug`     (`slug`),
  KEY `idx_tipo`     (`tipo`),
  KEY `idx_ativo`    (`ativo`),
  KEY `idx_destaque` (`destaque`),
  KEY `idx_ordem`    (`ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catálogo completo (INSERT IGNORE = idempotente)
INSERT IGNORE INTO `livros`
  (slug, tipo, titulo, subtitulo, genero, sinopse, arquivo_epub,
   preco, total_capitulos, tempo_leitura, capa_img, gratuito, destaque, novo, ordem)
VALUES
  ('lumen', 'livro',
   'Lúmen — A Outra Metade do Céu',
   'A mente de Hannah oscila entre memórias e sonhos.',
   'Ficção psicológica',
   'Hannah não tem certeza se seus sonhos são memórias ou suas memórias são sonhos. Uma narrativa não-confiável que dissolve as fronteiras entre o real e o imaginado.',
   'lumen.epub', 19.90, 1, 90, 'img/lumen.jpg', 0, 1, 1, 1),

  ('jogo-das-mascaras', 'livro',
   'O Jogo das Máscaras', NULL, 'Suspense',
   'Num jogo onde identidade é moeda, ninguém sai sem perder alguma coisa.',
   'o-jogo-das-mascaras.epub', 19.90, 1, 110, 'img/jogo-das-mascaras.jpg', 0, 0, 0, 2),

  ('cartas-do-passado', 'livro',
   'Cartas do Passado', NULL, 'Drama',
   'Cartas que chegaram tarde demais — ou na hora exata.',
   'cartas-do-passado.epub', 19.90, 1, 95, 'img/cartas-do-passado.jpg', 0, 0, 0, 3),

  ('a-setima-lei', 'livro',
   'A Sétima Lei',
   'O que acontece quando as regras são escritas por quem não merecia escrever nada?',
   'Ficção literária',
   'Uma história sobre poder, culpa e a inquietação de quem descobre que obedeceu regras escritas por quem não merecia escrevê-las.',
   'a-setima-lei.epub', 19.90, 8, 420, 'img/a-setima-lei.jpg', 0, 1, 0, 4),

  ('a-marca-da-besta', 'livro',
   'A Marca da Besta', NULL, 'Horror',
   'Alguns medos habitam a carne. Outros habitam a memória. Os mais perigosos habitam ambos.',
   'a-marca-da-besta.epub', 19.90, 9, 380, 'img/a-marca-da-besta.jpg', 0, 0, 0, 5),

  ('caminhos-de-outono', 'livro',
   'Caminhos de Outono', NULL, 'Lírico',
   'As estradas que só existem quando estamos dispostos a nos perder nelas.',
   'caminhos-de-outono.epub', 19.90, 7, 300, 'img/caminhos-de-outono.jpg', 0, 0, 0, 6),

  ('das-coisas-que-o-amor-faz', 'livro',
   'Das Coisas que o Amor Faz', NULL, 'Romance literário',
   'Um inventário honesto das coisas que o amor faz — inclusive as que preferíamos que não fizesse.',
   'das-coisas-que-o-amor-faz.epub', 19.90, 8, 360, 'img/das-coisas-que-o-amor-faz.jpg', 0, 0, 0, 7),

  ('genesis', 'livro',
   'Gênesis', NULL, 'Ficção especulativa',
   'O que veio antes do começo? Uma ficção sobre origens e o peso de ser o primeiro.',
   'genesis.epub', 19.90, 10, 480, 'img/genesis.jpg', 0, 0, 0, 8),

  ('mares-secretas-do-amor', 'livro',
   'As Marés Secretas do Amor', NULL, 'Romance',
   'Há amores que sobem e descem como maré — sem perguntar se você quer que voltem.',
   'as-mares-secretas-do-amor.epub', 19.90, 9, 390, 'img/mares-secretas.jpg', 0, 0, 0, 9),

  ('o-abismo-das-almas', 'livro',
   'O Abismo das Almas',
   'E se sentir fosse o maior perigo?',
   'Horror literário',
   'Uma humanidade que aprendeu a não sentir — não por escolha, mas por necessidade.',
   'o-abismo-das-almas.epub', 19.90, 8, 400, 'img/o-abismo-das-almas.jpg', 0, 1, 0, 10),

  ('rosas-e-espinhos', 'livro',
   'Rosas e Espinhos', NULL, 'Drama',
   'A beleza e a dor sempre vieram juntas. Esta história não tenta separar as duas.',
   'rosas-e-espinhos.epub', 19.90, 6, 260, 'img/rosas-e-espinhos.jpg', 0, 0, 0, 11),

  ('o-farol-do-afogado', 'conto',
   'O Farol do Afogado', NULL, 'Conto',
   'Um farol que só acende quando ninguém está olhando.',
   'o-farol-do-afogado.epub', 0.00, 1, 25, 'img/o-farol-do-afogado-conto.jpg', 1, 0, 0, 12),

  ('linhas-e-agulhas', 'conto',
   'Linhas e Agulhas', NULL, 'Conto',
   'Há padrões que só ficam visíveis quando você se afasta o suficiente para ver o todo.',
   'linhas-e-agulhas.epub', 0.00, 1, 20, 'img/linhas-e-agulhas.jpg', 1, 0, 0, 13),

  ('o-quarto-das-moscas', 'conto',
   'O Quarto das Moscas', NULL, 'Conto de horror',
   'O quarto estava vazio antes de ela entrar. Depois que ela saiu, já não estava mais.',
   'o-quarto-das-moscas.epub', 0.00, 1, 18, 'img/o-quarto-das-moscas.jpg', 1, 0, 0, 14);

CREATE TABLE IF NOT EXISTS `favoritos` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`    INT UNSIGNED NOT NULL,
  `livro_slug`    VARCHAR(100) NOT NULL,
  `adicionado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fav` (`usuario_id`, `livro_slug`),
  CONSTRAINT `fk_fav_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE
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
  CONSTRAINT `fk_aval_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `downloads_log` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `livro_slug` VARCHAR(100) NOT NULL,
  `formato`    ENUM('epub') NOT NULL DEFAULT 'epub',
  `arquivo`    VARCHAR(200) NOT NULL,
  `ip`         VARCHAR(45)  DEFAULT NULL,
  `baixado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_livro`   (`livro_slug`),
  CONSTRAINT `fk_dl_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PARTE 3 — COMENTÁRIOS E CONTATO
-- ================================================================

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
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`             VARCHAR(255) NOT NULL,
  `nome`              VARCHAR(120) DEFAULT NULL,
  `whatsapp`          VARCHAR(25)  DEFAULT NULL,
  `ip`                VARCHAR(45)  DEFAULT NULL,
  `status`            ENUM('pendente','ativo','descadastrado') NOT NULL DEFAULT 'pendente',
  `origem`            VARCHAR(80)  DEFAULT NULL,
  `tags`              VARCHAR(200) DEFAULT NULL,
  `token_verificacao` VARCHAR(64)  DEFAULT NULL,
  `token_expira`      DATETIME     DEFAULT NULL,
  `descad_em`         DATETIME     DEFAULT NULL,
  `pref_bastidores`   TINYINT(1)   NOT NULL DEFAULT 1,
  `pref_reflexao`     TINYINT(1)   NOT NULL DEFAULT 1,
  `pref_escritor`     TINYINT(1)   NOT NULL DEFAULT 1,
  `pref_livros`       TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
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
  `html_externo`  VARCHAR(300)     DEFAULT NULL,
  `status`        ENUM('rascunho','publicado','oculto','agendado') NOT NULL DEFAULT 'rascunho',
  `destaque`      TINYINT(1)       NOT NULL DEFAULT 0,
  `publicado_em`  DATETIME         DEFAULT NULL,
  `created_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug`           (`slug`),
  KEY `idx_status_publicado` (`status`, `publicado_em`),
  KEY `idx_categoria`        (`categoria`),
  KEY `idx_destaque`         (`destaque`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `posts`
  (slug, titulo, subtitulo, categoria, resumo, conteudo,
   imagem_url, tempo_leitura, status, destaque, publicado_em)
VALUES
('post-01',
 'Por trás da serpente e da maçã: os bastidores de A Sétima Lei',
 'Como nasceu a capa, a ideia, e o silêncio que precedeu tudo isso',
 'bastidores',
 'Cada livro começa muito antes da primeira palavra. Aqui conto como nasceu a capa, a ideia, e o silêncio que precedeu tudo isso.',
 '<p>Cada livro começa muito antes da primeira palavra. Antes da sinopse, antes do nome, antes mesmo da ideia clara. Começa numa inquietação — algo que incomoda sem ter forma.</p><p><em>A Sétima Lei</em> nasceu de uma pergunta que me perseguiu durante meses: o que acontece com a alma de quem descobre que as regras que sempre obedeceu foram escritas por alguém que não merecia escrever nada?</p><blockquote>"A serpente não mentiu. A maçã era boa para comer, era agradável aos olhos, e daria conhecimento. O problema nunca foi a maçã."</blockquote><h2>A capa que nasceu antes do livro</h2><p>Aconteceu algo incomum neste livro: a capa existiu antes do texto. Eu tinha uma imagem na cabeça — uma mão segurando uma serpente enrolada em torno dos dedos, não com medo, mas com familiaridade.</p><h2>O silêncio que precedeu tudo</h2><p>Escrevi os primeiros trinta páginas em três dias. Depois parei por seis semanas. Quando voltei, sabia o que faltava: o protagonista não queria ser salvo. Ele queria entender.</p>',
 'img/post-bastidores-setima-lei.jpg', 8, 'publicado', 1, '2025-04-01 10:00:00'),

('post-02', 'O peso do silêncio entre duas pessoas que se amaram',
 NULL, 'reflexao',
 'Existe um idioma que só existe entre pessoas que já se amaram. É feito de não-ditos, de gestos pela metade.',
 '<p>Existe um idioma que só existe entre pessoas que já se amaram. É feito de não-ditos, de gestos pela metade, de despedidas que ninguém sabe que foram as últimas.</p><h2>O que fica</h2><p>O que surpreende não é a dor do fim. É o quanto fica. Não as memórias grandes — as pequenas. O jeito que a outra pessoa segurava o copo.</p>',
 'img/bastidores-setima-lei.jpg', 5, 'publicado', 0, '2025-03-01 10:00:00'),

('post-03', 'Por que escrevo sobre dor — e por que não me arrependo',
 NULL, 'escritor',
 'Há quem prefira histórias que consolam. Eu prefiro as que arrancam a venda dos olhos.',
 '<p>Há quem prefira histórias que consolam. Eu prefiro as que arrancam a venda dos olhos. Não porque gosto de machucar — mas porque acredito que a dor vista de frente perde o poder de nos destruir.</p>',
 NULL, 6, 'publicado', 0, '2025-02-01 10:00:00'),

('post-04', '23h47 em Cascavel — onde as histórias nascem',
 NULL, 'reflexao',
 'Cada cidade tem uma hora em que se revela.',
 '<p>Cada cidade tem uma hora em que se revela. Cascavel às 23h47 é um lugar que só existe quando o silêncio é profundo o suficiente para ter peso.</p>',
 NULL, 4, 'publicado', 0, '2025-01-01 10:00:00'),

('post-05', 'Deus e o versículo incompleto — as origens de O Abismo das Almas',
 NULL, 'livros',
 'Todo livro de horror tem um medo real no centro.',
 '<p>Todo livro de horror tem um medo real no centro. No Abismo das Almas, o medo é este: e se a humanidade descobrisse que sentir é o maior perigo de todos?</p>',
 NULL, 7, 'publicado', 0, '2024-12-01 10:00:00'),

('post-06', 'O labirinto de Lúmen — como construí uma mente que não sabe se existe',
 NULL, 'livros',
 'Escrever Hannah foi o exercício mais perturbador que já fiz.',
 '<p>Escrever Hannah foi o exercício mais perturbador que já fiz. Uma protagonista que não tem certeza se seus sonhos são memórias, e suas memórias são sonhos.</p>',
 NULL, 9, 'publicado', 0, '2024-11-01 10:00:00'),

('post-07', 'Sobre a solidão que produz — e a que apenas consome',
 NULL, 'escritor',
 'Existe uma diferença entre a solidão que gera e a solidão que apenas esvazia.',
 '<p>Existe uma diferença entre a solidão que gera e a solidão que apenas esvazia. Aprendi a distinguir as duas — e aprendi a escolher qual cultivar.</p>',
 NULL, 5, 'publicado', 0, '2024-10-01 10:00:00');

CREATE TABLE IF NOT EXISTS `posts_lidos` (
  `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED     NOT NULL,
  `post_slug`  VARCHAR(160)     NOT NULL,
  `lido_em`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `progresso`  TINYINT UNSIGNED NOT NULL DEFAULT 100,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_post` (`usuario_id`, `post_slug`),
  KEY `idx_usuario`            (`usuario_id`),
  CONSTRAINT `fk_lidos_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE
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
  CONSTRAINT `fk_nlpost_post` FOREIGN KEY (`post_id`)
    REFERENCES `posts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PARTE 7 — PAGAMENTOS E ASSINATURAS
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
  CONSTRAINT `fk_assin_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assin_plano` FOREIGN KEY (`plano_id`)
    REFERENCES `planos`(`id`)
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
  UNIQUE KEY `uq_compra`       (`usuario_id`, `livro_slug`),
  KEY `idx_usuario`            (`usuario_id`),
  KEY `idx_livro_slug`         (`livro_slug`),
  KEY `idx_status`             (`status`),
  CONSTRAINT `fk_compra_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PARTE 7A — PRESENTES (GIFTING)
-- ================================================================

CREATE TABLE IF NOT EXISTS `presentes` (
  `id`                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `comprador_id`         INT UNSIGNED  NOT NULL,
  `livro_slug`           VARCHAR(100)  NOT NULL,
  `nome_presenteado`     VARCHAR(120)  NOT NULL,
  `email_presenteado`    VARCHAR(255)  NOT NULL,
  `whatsapp_presenteado` VARCHAR(25)   DEFAULT NULL,
  `dedicatoria`          TEXT          DEFAULT NULL,
  `preco_pago`           DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
  `status`               ENUM('pendente','aprovado','cancelado','reembolsado') NOT NULL DEFAULT 'pendente',
  `ref_externa`          VARCHAR(200)  DEFAULT NULL,
  `token_acesso`         VARCHAR(80)   DEFAULT NULL COMMENT 'Token único para o presenteado resgatar',
  `voucher_enviado`      TINYINT(1)    NOT NULL DEFAULT 0,
  `voucher_enviado_em`   DATETIME      DEFAULT NULL,
  `aprovado_em`          DATETIME      DEFAULT NULL,
  `resgatado_por`        INT UNSIGNED  DEFAULT NULL COMMENT 'usuario_id que resgatou o presente',
  `resgatado_em`         DATETIME      DEFAULT NULL,
  `criado_em`            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comprador`    (`comprador_id`),
  KEY `idx_livro_slug`   (`livro_slug`),
  KEY `idx_email_pres`   (`email_presenteado`),
  KEY `idx_ref`          (`ref_externa`),
  KEY `idx_token`        (`token_acesso`),
  CONSTRAINT `fk_pres_comprador` FOREIGN KEY (`comprador_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PARTE 7B — CARRINHO DE COMPRAS
-- ================================================================

CREATE TABLE IF NOT EXISTS `carrinhos` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `usuario_id`    INT UNSIGNED  NOT NULL,
  `itens`         JSON          NOT NULL  COMMENT 'Array de {slug,titulo,preco,capa}',
  `em_checkout`   TINYINT(1)    NOT NULL DEFAULT 0,
  `checkout_em`   DATETIME      DEFAULT NULL,
  `lembrete_env`  TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '1 = e-mail de lembrete já enviado',
  `lembrete_em`   DATETIME      DEFAULT NULL,
  `atualizado_em` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `criado_em`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_carrinho_usuario` (`usuario_id`),
  KEY `idx_atualizado`   (`atualizado_em`),
  KEY `idx_lembrete`     (`lembrete_env`, `atualizado_em`),
  CONSTRAINT `fk_car_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PARTE 8 — LEITOR ONLINE v3
-- ================================================================

-- ── Progresso de leitura por EPUB ────────────────────────────
CREATE TABLE IF NOT EXISTS `leitor_progresso` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `usuario_id`      INT UNSIGNED  NOT NULL,
  `livro_slug`      VARCHAR(100)  NOT NULL,
  `cfi`             VARCHAR(500)  DEFAULT NULL  COMMENT 'Posição CFI do epub.js',
  `percentual`      DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  `capitulo_atual`  VARCHAR(200)  DEFAULT NULL  COMMENT 'href do spine item atual',
  `iniciado_em`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultima_leitura`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `concluido`       TINYINT(1)    NOT NULL DEFAULT 0,
  `concluido_em`    DATETIME      DEFAULT NULL,
  `tempo_total_min` INT UNSIGNED  NOT NULL DEFAULT 0 COMMENT 'minutos lidos acumulados',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_progresso`    (`usuario_id`, `livro_slug`),
  KEY `idx_ultima_leitura`     (`ultima_leitura`),
  KEY `idx_usuario`            (`usuario_id`),
  CONSTRAINT `fk_prog_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Preferências tipográficas do leitor ───────────────────────
CREATE TABLE IF NOT EXISTS `leitor_preferencias` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`   INT UNSIGNED NOT NULL,
  `fonte`        ENUM('serifada','sem-serifa','manuscrita') NOT NULL DEFAULT 'serifada',
  `tamanho`      TINYINT UNSIGNED NOT NULL DEFAULT 18  COMMENT 'px, range 12-28',
  `espacamento`  DECIMAL(3,1) NOT NULL DEFAULT 1.8     COMMENT 'line-height, 1.2 a 2.5',
  `largura`      ENUM('estreita','media','larga') NOT NULL DEFAULT 'media',
  `tema`         ENUM('claro','sepia','escuro') NOT NULL DEFAULT 'claro',
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pref_usuario` (`usuario_id`),
  CONSTRAINT `fk_pref_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Anotações do leitor ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `leitor_anotacoes` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED NOT NULL,
  `livro_slug`  VARCHAR(100) NOT NULL,
  `cfi`         VARCHAR(500) NOT NULL  COMMENT 'Posição CFI do trecho anotado',
  `cfi_range`   VARCHAR(1000) DEFAULT NULL COMMENT 'CFI range do trecho selecionado',
  `trecho`      TEXT          DEFAULT NULL COMMENT 'Texto original selecionado',
  `anotacao`    TEXT          NOT NULL,
  `cor`         VARCHAR(7)    DEFAULT '#FFD700' COMMENT 'Cor do marcador hex',
  `criado_em`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_livro` (`usuario_id`, `livro_slug`),
  CONSTRAINT `fk_anot_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Marcações/Highlights ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `leitor_marcacoes` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED NOT NULL,
  `livro_slug`  VARCHAR(100) NOT NULL,
  `cfi_range`   VARCHAR(1000) NOT NULL COMMENT 'CFI range do texto marcado',
  `trecho`      TEXT          NOT NULL COMMENT 'Texto marcado',
  `cor`         ENUM('amarelo','verde','azul','rosa','laranja') NOT NULL DEFAULT 'amarelo',
  `criado_em`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_livro` (`usuario_id`, `livro_slug`),
  CONSTRAINT `fk_marc_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Conquistas / Medalhas de leitura ──────────────────────────
CREATE TABLE IF NOT EXISTS `leitor_conquistas` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`   INT UNSIGNED NOT NULL,
  `livro_slug`   VARCHAR(100) NOT NULL,
  `tipo`         ENUM('inicio','25pct','50pct','75pct','100pct','velocidade','maratona') NOT NULL,
  `conquistado_em` DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `email_enviado`  TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_conquista` (`usuario_id`, `livro_slug`, `tipo`),
  KEY `idx_usuario`         (`usuario_id`),
  CONSTRAINT `fk_conq_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Notas do autor (vinculadas a posição CFI) ─────────────────
CREATE TABLE IF NOT EXISTS `leitor_notas_autor` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `livro_slug`  VARCHAR(100) NOT NULL,
  `cfi`         VARCHAR(500) NOT NULL COMMENT 'Posição CFI onde a nota aparece',
  `tipo`        ENUM('bastidor','personagem','cena','curiosidade','outro') NOT NULL DEFAULT 'outro',
  `titulo`      VARCHAR(200) DEFAULT NULL,
  `conteudo`    TEXT         NOT NULL,
  `ativo`       TINYINT(1)   NOT NULL DEFAULT 1,
  `criado_em`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_livro` (`livro_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Erros ortográficos reportados ────────────────────────────
CREATE TABLE IF NOT EXISTS `leitor_erros` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED NOT NULL,
  `livro_slug`  VARCHAR(100) NOT NULL,
  `cfi`         VARCHAR(500) DEFAULT NULL,
  `trecho`      TEXT         DEFAULT NULL COMMENT 'Trecho com erro',
  `descricao`   TEXT         DEFAULT NULL COMMENT 'Descrição do erro',
  `resolvido`   TINYINT(1)   NOT NULL DEFAULT 0,
  `criado_em`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_livro_resolvido` (`livro_slug`, `resolvido`),
  KEY `idx_usuario`         (`usuario_id`),
  CONSTRAINT `fk_erro_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Feedback ao concluir leitura ──────────────────────────────
CREATE TABLE IF NOT EXISTS `leitor_feedback` (
  `id`           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `usuario_id`   INT UNSIGNED     NOT NULL,
  `livro_slug`   VARCHAR(100)     NOT NULL,
  `estrelas`     TINYINT UNSIGNED NOT NULL COMMENT '1-5',
  `texto`        TEXT             DEFAULT NULL,
  `compartilhou` TINYINT(1)       NOT NULL DEFAULT 0,
  `criado_em`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_feedback` (`usuario_id`, `livro_slug`),
  CONSTRAINT `fk_feed_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Lembretes de livros abandonados ──────────────────────────
CREATE TABLE IF NOT EXISTS `leitor_lembretes` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED NOT NULL,
  `livro_slug`  VARCHAR(100) NOT NULL,
  `tipo`        ENUM('email','site') NOT NULL DEFAULT 'email',
  `enviado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_livro` (`usuario_id`, `livro_slug`),
  CONSTRAINT `fk_lemb_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Ranking de leitores ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS `leitor_ranking` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`   INT UNSIGNED NOT NULL,
  `pontos`       INT UNSIGNED NOT NULL DEFAULT 0,
  `livros_lidos` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `contos_lidos` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `streak_dias`  SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'dias consecutivos lendo',
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ranking_usuario` (`usuario_id`),
  KEY `idx_pontos`  (`pontos`),
  CONSTRAINT `fk_rank_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Metas de leitura ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `leitor_metas` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`   INT UNSIGNED NOT NULL,
  `tipo`         ENUM('cronometro','regressivo','relogio') NOT NULL DEFAULT 'relogio',
  `duracao_min`  SMALLINT UNSIGNED DEFAULT NULL COMMENT 'para tipo regressivo, minutos',
  `ativo`        TINYINT(1)   NOT NULL DEFAULT 1,
  `criado_em`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_meta_usuario` (`usuario_id`),
  CONSTRAINT `fk_meta_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PARTE 9 — CAMPANHAS DE EMAIL
-- ================================================================

CREATE TABLE IF NOT EXISTS `campanhas` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nome`            VARCHAR(200)  NOT NULL,
  `tipo`            ENUM('lancamento','promocao','newsletter','reengajamento',
                         'boas_vindas','recompensa','destaque','outro')
                    NOT NULL DEFAULT 'newsletter',
  `assunto_email`   VARCHAR(255)  DEFAULT NULL,
  `corpo_html`      LONGTEXT      DEFAULT NULL,
  `corpo_texto`     TEXT          DEFAULT NULL,
  `segmento`        ENUM('todos','newsletter','compradores','assinantes',
                         'inativos_30','inativos_90','sem_compra','personalizado')
                    NOT NULL DEFAULT 'todos',
  `status`          ENUM('rascunho','agendada','enviando','enviada','cancelada')
                    NOT NULL DEFAULT 'rascunho',
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
  CONSTRAINT `fk_camp_admin` FOREIGN KEY (`criado_por`)
    REFERENCES `admin_users`(`id`) ON DELETE SET NULL
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
  CONSTRAINT `fk_env_campanha` FOREIGN KEY (`campanha_id`)
    REFERENCES `campanhas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PARTE 10 — VIEWS ÚTEIS
-- ================================================================

CREATE OR REPLACE VIEW `vw_acesso_leitura` AS
  -- Usuários com acesso por compra aprovada
  SELECT c.usuario_id, c.livro_slug,
         'compra'     AS tipo_acesso,
         NULL         AS expira_em
  FROM   `compras` c
  WHERE  c.status = 'aprovada'
  UNION ALL
  -- Usuários com acesso por assinatura ativa
  SELECT a.usuario_id, l.slug AS livro_slug,
         'assinatura' AS tipo_acesso,
         a.expira_em
  FROM   `assinaturas` a
  CROSS JOIN `livros` l
  WHERE  a.status = 'ativa'
    AND  a.expira_em > NOW()
    AND  l.ativo = 1
  UNION ALL
  -- Livros/contos gratuitos (qualquer usuário logado)
  SELECT NULL AS usuario_id, l.slug AS livro_slug,
         'gratuito'   AS tipo_acesso,
         NULL         AS expira_em
  FROM   `livros` l
  WHERE  l.gratuito = 1 AND l.ativo = 1;

CREATE OR REPLACE VIEW `vw_ranking_leitores` AS
  SELECT
    r.usuario_id,
    u.nome,
    r.pontos,
    r.livros_lidos,
    r.contos_lidos,
    r.streak_dias,
    r.atualizado_em
  FROM `leitor_ranking` r
  JOIN `usuarios` u ON u.id = r.usuario_id
  WHERE u.ativo = 1
  ORDER BY r.pontos DESC;

CREATE OR REPLACE VIEW `vw_leituras_em_andamento` AS
  SELECT
    lp.usuario_id,
    lp.livro_slug,
    l.titulo,
    l.capa_img,
    lp.percentual,
    lp.ultima_leitura,
    lp.concluido,
    DATEDIFF(NOW(), lp.ultima_leitura) AS dias_sem_ler
  FROM `leitor_progresso` lp
  JOIN `livros` l ON l.slug = lp.livro_slug
  WHERE lp.concluido = 0
  ORDER BY lp.ultima_leitura DESC;

-- ================================================================
-- FIM  —  banco_completo_v2.sql
-- Tabelas criadas: 32  |  Views: 3  |  Dados iniciais: inseridos
-- ================================================================

SET FOREIGN_KEY_CHECKS = 1;
