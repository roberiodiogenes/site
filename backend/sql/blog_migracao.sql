-- ================================================================
-- ROBÉRIO DIÓGENES — blog_migracao.sql
-- Migração para o sistema de Blog dinâmico
-- Execute após banco_completo.sql já estar aplicado.
-- ================================================================

-- ── Tabela principal de posts ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `posts` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `slug`          VARCHAR(160)    NOT NULL,
  `titulo`        VARCHAR(300)    NOT NULL,
  `subtitulo`     VARCHAR(400)    DEFAULT NULL,
  `categoria`     ENUM('bastidores','reflexao','escritor','livros') NOT NULL DEFAULT 'reflexao',
  `resumo`        TEXT            DEFAULT NULL,
  `conteudo`      LONGTEXT        NOT NULL,
  `imagem_url`    VARCHAR(500)    DEFAULT NULL,  -- caminho relativo em img/ ou URL
  `audio_url`     VARCHAR(500)    DEFAULT NULL,  -- arquivo de áudio do post (mp3/ogg)
  `tempo_leitura` TINYINT UNSIGNED NOT NULL DEFAULT 5,  -- minutos estimados
  `livro_slug`    VARCHAR(160)    DEFAULT NULL,  -- para CTA: qual livro promover
  `status`        ENUM('rascunho','publicado','oculto') NOT NULL DEFAULT 'rascunho',
  `destaque`      TINYINT(1)      NOT NULL DEFAULT 0,
  `publicado_em`  DATETIME        DEFAULT NULL,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_status_publicado` (`status`, `publicado_em`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_destaque` (`destaque`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Posts já existentes (migração dos HTMLs estáticos) ────────
INSERT IGNORE INTO `posts`
  (slug, titulo, subtitulo, categoria, resumo, conteudo, imagem_url, tempo_leitura, status, destaque, publicado_em)
VALUES
(
  'post-01',
  'Por trás da serpente e da maçã: os bastidores de A Sétima Lei',
  'Como nasceu a capa, a ideia, e o silêncio que precedeu tudo isso',
  'bastidores',
  'Cada livro começa muito antes da primeira palavra. Aqui conto como nasceu a capa, a ideia, e o silêncio que precedeu tudo isso.',
  '<p>Cada livro começa muito antes da primeira palavra. Antes da sinopse, antes do nome, antes mesmo da ideia clara. Começa numa inquietação — algo que incomoda sem ter forma, que aparece nos sonhos e nos intervalos de silêncio.</p>\n<p><em>A Sétima Lei</em> nasceu de uma pergunta que me perseguiu durante meses: o que acontece com a alma de quem descobre que as regras que sempre obedeceu foram escritas por alguém que não merecia escrever nada?</p>\n<blockquote>\"A serpente não mentiu. A maçã era boa para comer, era agradável aos olhos, e daria conhecimento. O problema nunca foi a maçã.\"</blockquote>\n<h2>A capa que nasceu antes do livro</h2>\n<p>Aconteceu algo incomum neste livro: a capa existiu antes do texto. Eu tinha uma imagem na cabeça — uma mão segurando uma serpente enrolada em torno dos dedos, não com medo, mas com familiaridade.</p>\n<h2>O silêncio que precedeu tudo</h2>\n<p>Escrevi os primeiros trinta páginas em três dias. Depois parei por seis semanas. Não por bloqueio — por necessidade. Havia algo que eu ainda não sabia sobre os personagens.</p>\n<p>Quando voltei, sabia o que faltava: o protagonista não queria ser salvo. Ele queria entender. E esses são desejos completamente diferentes.</p>\n<blockquote>\"Se o bem e o mal fossem fáceis de distinguir, não precisaríamos de histórias.\"</blockquote>\n<p>Foi o livro mais difícil que escrevi até hoje. Espero que seja o mais difícil de esquecer que você já leu.</p>',
  'img/post-bastidores-setima-lei.jpg',
  8, 'publicado', 1,
  '2025-04-01 10:00:00'
),
(
  'post-02',
  'O peso do silêncio entre duas pessoas que se amaram',
  NULL,
  'reflexao',
  'Existe um idioma que só existe entre pessoas que já se amaram. É feito de não-ditos, de gestos pela metade.',
  '<p>Existe um idioma que só existe entre pessoas que já se amaram. É feito de não-ditos, de gestos pela metade, de despedidas que ninguém sabe que foram as últimas.</p>\n<p>Esse idioma não tem nome. Não tem gramática. Mas quem já o falou reconhece qualquer frase que pertença a ele — mesmo décadas depois, mesmo depois de tudo ter mudado.</p>\n<h2>O que fica</h2>\n<p>O que surpreende não é a dor do fim. É o quanto fica. Não as memórias grandes — as pequenas. O jeito que a outra pessoa segurava o copo. O silêncio específico de uma tarde de domingo.</p>\n<p>Esses resíduos são os mais honestos de todos os sentimentos. Eles existem sem pedir licença, sem considerar se ainda fazem sentido.</p>',
  'img/bastidores-setima-lei.jpg',
  5, 'publicado', 0,
  '2025-03-01 10:00:00'
),
(
  'post-03',
  'Por que escrevo sobre dor — e por que não me arrependo',
  NULL,
  'escritor',
  'Há quem prefira histórias que consolam. Eu prefiro as que arrancam a venda dos olhos.',
  '<p>Há quem prefira histórias que consolam. Eu prefiro as que arrancam a venda dos olhos. Não porque gosto de machucar — mas porque acredito que a dor vista de frente perde o poder de nos destruir.</p>\n<h2>O propósito da escrita difícil</h2>\n<p>Escrever sobre dor não é comprazer-se com o sofrimento. É fazer o que a arte sempre fez melhor: nomear o inominável, para que ele deixe de assombrar nas sombras.</p>\n<p>Quando você lê sobre um personagem que sente o que você sempre teve medo de admitir que sente, acontece algo silencioso mas decisivo: você se sente menos sozinho no mundo.</p>',
  NULL,
  6, 'publicado', 0,
  '2025-02-01 10:00:00'
),
(
  'post-04',
  '23h47 em Cascavel — onde as histórias nascem',
  NULL,
  'reflexao',
  'Cada cidade tem uma hora em que se revela. Cascavel às 23h47 é um lugar que só existe quando o silêncio é profundo o suficiente para ter peso.',
  '<p>Cada cidade tem uma hora em que se revela. Cascavel às 23h47 é um lugar que só existe quando o silêncio é profundo o suficiente para ter peso.</p>\n<p>É a hora em que os cachorros param de latir. Em que o trânsito da BR some. Em que a única coisa que se ouve é o vento passando pelos telhados de cimento e a respiração de uma cidade que nunca quis ser grande.</p>\n<h2>O que o interior guarda</h2>\n<p>Nasci no interior e carrego isso como se fosse um órgão extra. As histórias que escrevo têm o cheiro da terra úmida, o barulho do gerador quando a luz vai embora, a lentidão de quem sabe esperar porque não tem alternativa.</p>',
  NULL,
  4, 'publicado', 0,
  '2025-01-01 10:00:00'
),
(
  'post-05',
  'Deus e o versículo incompleto — as origens de O Abismo das Almas',
  NULL,
  'livros',
  'Todo livro de horror tem um medo real no centro. No Abismo das Almas, o medo é este: e se a humanidade descobrisse que sentir é o maior perigo de todos?',
  '<p>Todo livro de horror tem um medo real no centro. No Abismo das Almas, o medo é este: e se a humanidade descobrisse que sentir é o maior perigo de todos?</p>\n<h2>A inspiração</h2>\n<p>Comecei com uma cena que não saía da minha cabeça: uma multidão caminhando em silêncio absoluto por uma cidade inteira, sem expressão, sem pressa, sem destino visível. E ninguém achava isso estranho.</p>\n<p>De onde vinha o horror? Não dos mortos. Dos vivos que tinham aprendido a não sentir.</p>',
  NULL,
  7, 'publicado', 0,
  '2024-12-01 10:00:00'
),
(
  'post-06',
  'O labirinto de Lúmen — como construí uma mente que não sabe se existe',
  NULL,
  'livros',
  'Escrever Hannah foi o exercício mais perturbador que já fiz.',
  '<p>Escrever Hannah foi o exercício mais perturbador que já fiz. Uma protagonista que não tem certeza se seus sonhos são memórias, e suas memórias são sonhos.</p>\n<h2>O desafio da narradora não-confiável</h2>\n<p>A narrativa não-confiável é uma das ferramentas mais poderosas da ficção. Mas tem um custo: o escritor também se perde. Você começa a não saber mais o que é real na história que está contando.</p>',
  NULL,
  9, 'publicado', 0,
  '2024-11-01 10:00:00'
),
(
  'post-07',
  'Sobre a solidão que produz — e a que apenas consome',
  NULL,
  'escritor',
  'Existe uma diferença entre a solidão que gera e a solidão que apenas esvazia.',
  '<p>Existe uma diferença entre a solidão que gera e a solidão que apenas esvazia. Aprendi a distinguir as duas — e aprendi a escolher qual cultivar.</p>\n<h2>Dois tipos de silêncio</h2>\n<p>A solidão produtiva tem textura. Tem cheiro de café que esfriou sem que você percebesse, de caderno aberto, de tela em branco que você não teme mais. É a solidão de quem escolheu estar só para fazer algo com o silêncio.</p>\n<p>A outra é diferente. É a que aparece quando você não chamou, quando ninguém chamou. Essa não gera nada — ela apenas subtrai.</p>',
  NULL,
  5, 'publicado', 0,
  '2024-10-01 10:00:00'
);

-- ── Registro de posts lidos por usuário ──────────────────────
CREATE TABLE IF NOT EXISTS `posts_lidos` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `post_slug`  VARCHAR(160) NOT NULL,
  `lido_em`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `progresso`  TINYINT UNSIGNED NOT NULL DEFAULT 100,  -- % de leitura (0–100)
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_post` (`usuario_id`, `post_slug`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_post` (`post_slug`),
  CONSTRAINT `fk_lidos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Curtidas de posts (backend) ───────────────────────────────
CREATE TABLE IF NOT EXISTS `posts_curtidas` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_slug`  VARCHAR(160) NOT NULL,
  `usuario_id` INT UNSIGNED DEFAULT NULL,  -- NULL = anônimo (por IP)
  `ip`         VARCHAR(45)  NOT NULL,
  `criado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_curtida` (`post_slug`, `usuario_id`),
  KEY `idx_slug` (`post_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Preferências de newsletter por categoria ──────────────────
-- Adicionar coluna de preferências à tabela newsletter existente
ALTER TABLE `newsletter`
  ADD COLUMN IF NOT EXISTS `pref_bastidores` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `pref_reflexao`   TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `pref_escritor`   TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `pref_livros`     TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `nome`            VARCHAR(120) DEFAULT NULL;

-- ── Log de envios de newsletter de blog ───────────────────────
CREATE TABLE IF NOT EXISTS `newsletter_posts` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id`      INT UNSIGNED NOT NULL,
  `enviado_em`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_envios` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_post` (`post_id`),
  CONSTRAINT `fk_nlpost_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Associar posts a livro da newsletter (add coluna livros) ──
-- A tabela posts já tem livro_slug, usado no CTA.

-- ================================================================
-- ✓ RESUMO DA MIGRAÇÃO
-- Novas tabelas: posts, posts_lidos, posts_curtidas, newsletter_posts
-- Colunas adicionadas: newsletter.pref_*, newsletter.nome
-- Dados migrados: 7 posts estáticos → tabela posts
-- ================================================================
