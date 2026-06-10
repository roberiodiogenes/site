-- =====================================================================
-- ROBÉRIO DIÓGENES — migration_blog_hgwells.sql
-- Cluster HG Wells: 1 post pilar + 5 posts satélite
-- Execute no phpMyAdmin antes de usar os posts estáticos.
-- =====================================================================

-- 1. Garantir que as colunas necessárias existem na tabela posts
--    (MySQL 8 suporta ADD COLUMN IF NOT EXISTS)
ALTER TABLE posts ADD COLUMN IF NOT EXISTS cluster_id    INT UNSIGNED     NULL DEFAULT NULL;
ALTER TABLE posts ADD COLUMN IF NOT EXISTS tipo_post     VARCHAR(20)      NOT NULL DEFAULT 'avulso'  COMMENT 'pilar | satelite | avulso';
ALTER TABLE posts ADD COLUMN IF NOT EXISTS estatico      TINYINT(1)       NOT NULL DEFAULT 0         COMMENT '1 = conteudo embutido no HTML';
ALTER TABLE posts ADD COLUMN IF NOT EXISTS tempo_leitura TINYINT UNSIGNED NOT NULL DEFAULT 5         COMMENT 'minutos estimados de leitura';
ALTER TABLE posts ADD COLUMN IF NOT EXISTS html_externo  VARCHAR(500)     DEFAULT NULL               COMMENT 'path do arquivo HTML estático (ex: blog/meu-post.html)';

-- 2. Garantir que a tabela clusters existe (pode já existir)
CREATE TABLE IF NOT EXISTS clusters (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug        VARCHAR(160) NOT NULL,
  titulo      VARCHAR(255) NOT NULL,
  descricao   TEXT,
  imagem_url  VARCHAR(255),
  cor         VARCHAR(20) DEFAULT '#B8860B',
  ativo       TINYINT(1) NOT NULL DEFAULT 1,
  criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cluster_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2b. Adicionar coluna cor se a tabela já existia sem ela
ALTER TABLE clusters ADD COLUMN IF NOT EXISTS cor VARCHAR(20) NOT NULL DEFAULT '#B8860B';

-- 3. Inserir o cluster HG Wells
INSERT INTO clusters (slug, titulo, descricao, cor) VALUES (
  'hgwells',
  'H. G. Wells: O Pai da Ficção Científica',
  'Uma série completa sobre a obra de Herbert George Wells — do guia definitivo ao autor às resenhas aprofundadas de cada um de seus romances fundadores.',
  '#4A3728'
) ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), cor = '#4A3728';

SET @cluster_id = (SELECT id FROM clusters WHERE slug = 'hgwells' LIMIT 1);

-- 4. Inserir os 6 posts (apenas registro mínimo — conteúdo está nos HTMLs estáticos)
INSERT INTO posts (slug, titulo, subtitulo, resumo, categoria, cluster_id, tipo_post, estatico, tempo_leitura, imagem_url, status, publicado_em)
VALUES
(
  'h-g-wells-guia-definitivo',
  'H. G. Wells: O Homem Que Imaginou o Futuro Antes que Ele Existisse',
  'Da máquina do tempo à guerra dos mundos — o guia completo sobre o pai da ficção científica',
  'Um guia abrangente sobre a vida, a obra e o legado de H. G. Wells: biólogo, jornalista, visionário. Como um filho de empregada doméstica se tornou o profeta do século XX.',
  'literatura',
  @cluster_id,
  'pilar',
  1,
  18,
  'img/posts/hgwells-pilar.webp',
  'publicado',
  '2025-06-01 09:00:00'
),
(
  'a-maquina-do-tempo-resenha',
  'A Máquina do Tempo: O Livro que Me Ensinou a Sonhar com o Futuro — e a Temer o que Encontraria Lá',
  'Resenha aprofundada: Eloi, Morlocks e a melancolia do fim do mundo',
  'Uma resenha pessoal e literária de A Máquina do Tempo de H. G. Wells — sobre distopia, desigualdade e a solidão de quem vê o fim de tudo.',
  'literatura',
  @cluster_id,
  'satelite',
  1,
  12,
  'img/posts/hgwells-maquina-tempo.webp',
  'publicado',
  '2025-06-03 09:00:00'
),
(
  'o-homem-invisivel-resenha',
  'O Homem Invisível: Quando o Maior Poder do Mundo se Torna a Mais Terrível das Prisões',
  'Resenha: Griffin, o anel de Giges e o que acontece quando ninguém pode te ver',
  'O que você faria se pudesse se tornar invisível? A resposta de H. G. Wells é perturbadora — e mais atual do que nunca.',
  'literatura',
  @cluster_id,
  'satelite',
  1,
  10,
  'img/posts/hgwells-homem-invisivel.webp',
  'publicado',
  '2025-06-06 09:00:00'
),
(
  'o-dorminhoco-resenha',
  'O Dorminhoco: Acordar em um Futuro que Nunca Pedimos Para Ver',
  'Resenha: a distopia urbana mais esquecida — e mais visionária — de Wells',
  'Wells adormece um homem no século XIX e o acorda duzentos anos depois num mundo controlado por corporações. Ficção ou descrição?',
  'literatura',
  @cluster_id,
  'satelite',
  1,
  10,
  'img/posts/hgwells-dorminhoco.webp',
  'publicado',
  '2025-06-09 09:00:00'
),
(
  'a-ilha-do-dr-moreau-resenha',
  'A Ilha do Dr. Moreau: O Horror que Mora Dentro da Pele Humana',
  'Resenha: ciência, soberba e a linha tênue entre homem e animal',
  'Um cientista que remodela animais à imagem humana — não por bondade, mas por soberba intelectual. A pergunta de Wells sobre os limites da ciência ressoa mais forte do que nunca.',
  'literatura',
  @cluster_id,
  'satelite',
  1,
  11,
  'img/posts/hgwells-ilha-moreau.webp',
  'publicado',
  '2025-06-12 09:00:00'
),
(
  'a-guerra-dos-mundos-resenha',
  'A Guerra dos Mundos: Quando o Universo Parou de Ser Amigável',
  'Resenha: o imperialismo invertido e a noite que Orson Welles parou a América',
  'Wells pedalava pelas ruas de Woking quando imaginou: e se fôssemos nós os colonizados? O livro que virou o imperialismo britânico de cabeça para baixo.',
  'literatura',
  @cluster_id,
  'satelite',
  1,
  13,
  'img/posts/hgwells-guerra-mundos.webp',
  'publicado',
  '2025-06-15 09:00:00'
)
ON DUPLICATE KEY UPDATE
  cluster_id   = VALUES(cluster_id),
  tipo_post    = VALUES(tipo_post),
  estatico     = 1,
  status       = VALUES(status);

-- 5. Adicionar FK do cluster_id (se não existir)
-- Execute só se quiser reforço de integridade referencial:
-- ALTER TABLE posts ADD CONSTRAINT fk_posts_cluster
--   FOREIGN KEY (cluster_id) REFERENCES clusters(id) ON DELETE SET NULL;

SELECT
  CONCAT('Cluster inserido: ', titulo) AS resultado FROM clusters WHERE slug='hgwells'
UNION ALL
SELECT CONCAT('Posts do cluster: ', COUNT(*)) FROM posts WHERE cluster_id = @cluster_id;
