-- ================================================================
-- migration_bio.sql
-- Página bio (link-in-bio para Instagram e redes sociais)
-- ================================================================

-- Links principais (os botões numerados)
CREATE TABLE IF NOT EXISTS `bio_links` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titulo`    VARCHAR(120) NOT NULL,
  `subtitulo` VARCHAR(200) DEFAULT NULL,
  `url`       VARCHAR(500) NOT NULL,
  `icone`     VARCHAR(50)  DEFAULT NULL  COMMENT 'Classe Font Awesome, ex: fa-book',
  `tipo`      ENUM('link','destaque')    NOT NULL DEFAULT 'link',
  `ativo`     TINYINT(1)  NOT NULL DEFAULT 1,
  `ordem`     SMALLINT UNSIGNED NOT NULL DEFAULT 99,
  `criado_em` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ativo_ordem` (`ativo`,`ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configurações da bio (foto, nome, subtítulo, redes sociais)
CREATE TABLE IF NOT EXISTS `bio_config` (
  `chave` VARCHAR(80)  NOT NULL,
  `valor` TEXT         DEFAULT NULL,
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valores padrão
INSERT IGNORE INTO `bio_config` (`chave`, `valor`) VALUES
  ('nome',        'Robério Diógenes'),
  ('subtitulo',   'Escritor · Literatura Brasileira'),
  ('foto',        'img/autor2.jpg'),
  ('instagram',   'https://instagram.com/diogenesroberio'),
  ('whatsapp',    'https://wa.me/5585996409818'),
  ('telegram',    'https://t.me/5585996409818'),
  ('linkedin',    'https://linkedin.com/in/roberio-diogenes'),
  ('email',       'mailto:contato@roberiodiogenes.com'),
  ('cor_fundo',   '#0D0A07'),
  ('cor_acento',  '#B8860B');

-- Links padrão
INSERT IGNORE INTO `bio_links` (`titulo`, `subtitulo`, `url`, `icone`, `tipo`, `ordem`) VALUES
  ('Biblioteca de Obras',  'Romances, contos e ficção literária', '/livros.html',               'fa-book',      'destaque', 1),
  ('Diário do Escritor',   'Reflexões, bastidores e processo criativo', '/blog.html',            'fa-pen-nib',   'link',     2),
  ('Leitor Online',        'Leia no navegador, sem app', '/leitor/index.html',                   'fa-book-open', 'link',     3),
  ('Planos de Assinatura', 'Acesso completo à biblioteca', '/pagamento/assinatura.html',         'fa-crown',     'link',     4),
  ('Sobre o Autor',        'A história por trás das histórias', '/autor.html',                   'fa-user-pen',  'link',     5),
  ('Presentear alguém',    'Dê um livro de presente com 20% off', '/presentear.html',            'fa-gift',      'link',     6);
