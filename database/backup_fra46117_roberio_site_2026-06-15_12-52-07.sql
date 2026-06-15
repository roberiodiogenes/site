-- ============================================================
-- Banco: fra46117_roberio_site
-- Gerado em: 2026-06-15 12:52:07
-- Site: roberiodiogenes.com
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Tabela: `admin_log`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `admin_log`;
CREATE TABLE `admin_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned DEFAULT NULL,
  `acao` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_criado_em` (`criado_em`),
  CONSTRAINT `fk_al_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `admin_users`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ultimo_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admin_users` VALUES
('1', 'admin', '$2y$12$pY9b2umN38/6ej6DEJorh.V9LUuRbuyyaG1k/b/OhbGMmxt9DiyBC', '2026-06-15 12:51:03', '2026-06-11 21:46:13');

-- ------------------------------------------------------------
-- Tabela: `analytics_eventos`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `analytics_eventos`;
CREATE TABLE `analytics_eventos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_id` int(10) unsigned DEFAULT NULL,
  `tipo_evento` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ViewContent | Lead | Leitura_Progresso | Download_Amostra | Tempo_Pagina ...',
  `conteudo_slug` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conteudo_titulo` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `params` json DEFAULT NULL,
  `tempo_permanencia` int(10) unsigned DEFAULT NULL COMMENT 'Segundos na página',
  `registrado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_tipo` (`tipo_evento`),
  KEY `idx_slug` (`conteudo_slug`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_registrado` (`registrado_em`)
) ENGINE=InnoDB AUTO_INCREMENT=568 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Eventos de comportamento: leitura, downloads, leads, tempo de permanência';

INSERT INTO `analytics_eventos` VALUES
('1', 'rd_1781272687359_km5d7dg', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '32', '2026-06-12 10:58:38'),
('2', 'rd_1781272687359_km5d7dg', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '13', '2026-06-12 10:59:50'),
('3', 'rd_1781272687359_km5d7dg', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '52', '2026-06-12 11:00:43'),
('4', 'rd_1781272687359_km5d7dg', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '9', '2026-06-12 11:00:52'),
('5', 'rd_1781272687359_km5d7dg', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '22', '2026-06-12 11:01:14'),
('6', 'rd_1781272687359_km5d7dg', NULL, 'Tempo_Pagina', 'blog', NULL, NULL, '25', '2026-06-12 11:01:39'),
('7', 'rd_1781272687359_km5d7dg', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '8', '2026-06-12 11:01:47'),
('8', 'rd_1781272687359_km5d7dg', NULL, 'Tempo_Pagina', 'blog', NULL, NULL, '40', '2026-06-12 11:02:28'),
('9', 'rd_1781272687359_km5d7dg', NULL, 'Tempo_Pagina', 'blog', NULL, NULL, '3', '2026-06-12 11:02:40'),
('10', 'rd_1781272687359_km5d7dg', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '19', '2026-06-12 11:03:00'),
('11', 'rd_1781272687359_km5d7dg', NULL, 'Tempo_Pagina', 'blog', NULL, NULL, '11', '2026-06-12 11:03:11'),
('12', 'rd_1781272687359_km5d7dg', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '101', '2026-06-12 11:04:52'),
('13', 'rd_1781272687359_km5d7dg', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '18', '2026-06-12 11:05:11'),
('14', 'rd_1781272687359_km5d7dg', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '5', '2026-06-12 11:05:49'),
('15', 'rd_1781272687359_km5d7dg', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '45', '2026-06-12 11:06:34'),
('16', 'rd_1781272687359_km5d7dg', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '28', '2026-06-12 11:08:10'),
('17', 'rd_1781272687359_km5d7dg', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '12', '2026-06-12 11:08:22'),
('18', 'rd_1781272687359_km5d7dg', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '5', '2026-06-12 11:08:35'),
('19', 'rd_1781272687359_km5d7dg', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '2', '2026-06-12 11:08:38'),
('20', 'rd_1781276060013_enup9b4', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '20', '2026-06-12 11:54:38'),
('21', 'rd_1781271314305_lm0vpbi', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '7200', '2026-06-12 15:07:20'),
('22', 'rd_1781271314305_lm0vpbi', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '4', '2026-06-12 15:07:25'),
('23', 'rd_1781287828722_qmb0l65', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '70', '2026-06-12 15:11:39'),
('24', 'rd_1781288935251_8w35w3d', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '4', '2026-06-12 15:28:59'),
('25', 'rd_1781288946323_5wqeiqo', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '3', '2026-06-12 15:29:09'),
('26', 'rd_1781287828722_qmb0l65', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '6', '2026-06-12 15:30:19'),
('27', 'rd_1781289729791_zp1usw6', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '2', '2026-06-12 15:42:12'),
('28', 'rd_1781289729791_zp1usw6', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '2', '2026-06-12 15:43:06'),
('29', 'rd_1781289729791_zp1usw6', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '4', '2026-06-12 15:44:23'),
('30', 'rd_1781289729791_zp1usw6', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '15', '2026-06-12 15:44:56'),
('31', 'rd_1781289729791_zp1usw6', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '6', '2026-06-12 15:45:42'),
('32', 'rd_1781291054819_obri8w0', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '18', '2026-06-12 16:04:34'),
('33', 'rd_1781291317381_to0fgxd', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '50', '2026-06-12 16:09:28'),
('34', 'rd_1781291897478_o5w5hr8', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '3', '2026-06-12 16:18:21'),
('35', 'rd_1781291897478_o5w5hr8', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '17', '2026-06-12 16:19:05'),
('36', 'rd_1781291897478_o5w5hr8', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '5', '2026-06-12 16:19:17'),
('37', 'rd_1781292097214_g4ln5jl', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '72', '2026-06-12 16:22:50'),
('38', 'rd_1781292097214_g4ln5jl', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '13', '2026-06-12 16:23:13'),
('39', 'rd_1781291054819_obri8w0', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '1088', '2026-06-12 16:23:51'),
('40', 'rd_1781291054819_obri8w0', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '575', '2026-06-12 16:39:32'),
('41', 'rd_1781292097214_g4ln5jl', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '1037', '2026-06-12 16:59:29'),
('42', 'rd_1781291897478_o5w5hr8', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '20', '2026-06-12 17:08:56'),
('43', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:07'),
('44', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:09'),
('45', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:09'),
('46', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_001.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:10'),
('47', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_002.html\", \"percentual\": 1, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:11'),
('48', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_004.html\", \"percentual\": 1, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:11'),
('49', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:11'),
('50', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:12'),
('51', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 3, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:13'),
('52', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 3, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:13'),
('53', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 4, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:13'),
('54', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 4, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:14'),
('55', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 5, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:14'),
('56', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 6, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:15'),
('57', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 6, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:15'),
('58', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 7, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:15'),
('59', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 7, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:16'),
('60', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 8, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:16'),
('61', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 9, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:16'),
('62', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 9, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:17'),
('63', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 9, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:17'),
('64', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 10, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:17'),
('65', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 10, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:18'),
('66', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_008.html\", \"percentual\": 11, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:18'),
('67', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_008.html\", \"percentual\": 11, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:19'),
('68', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:34'),
('69', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:44'),
('70', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:45'),
('71', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:46'),
('72', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:48'),
('73', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:49'),
('74', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_000.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:49'),
('75', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_002.html\", \"percentual\": 1, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:50'),
('76', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_002.html\", \"percentual\": 1, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:50'),
('77', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_003.html\", \"percentual\": 1, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:51'),
('78', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_003.html\", \"percentual\": 1, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:52'),
('79', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:54'),
('80', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_003.html\", \"percentual\": 1, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:57'),
('81', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_003.html\", \"percentual\": 1, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:57'),
('82', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_003.html\", \"percentual\": 1, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:58'),
('83', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_014.html\", \"percentual\": 78, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-12 17:09:59'),
('84', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'Caminhos de Outono | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"Caminhos de Outono\"}', NULL, '2026-06-12 17:10:28'),
('85', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'Caminhos de Outono | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"Caminhos de Outono\"}', NULL, '2026-06-12 17:10:31'),
('86', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'Caminhos de Outono | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_013.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"Caminhos de Outono\"}', NULL, '2026-06-12 17:10:36'),
('87', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'Caminhos de Outono | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_043.html\", \"percentual\": 47, \"content_ids\": [\"home\"], \"livro_titulo\": \"Caminhos de Outono\"}', NULL, '2026-06-12 17:10:40'),
('88', 'rd_1781296157753_s1qtjct', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '8', '2026-06-12 17:29:25'),
('89', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Marca da Besta | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Marca da Besta\"}', NULL, '2026-06-12 17:31:57'),
('90', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Marca da Besta | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Marca da Besta\"}', NULL, '2026-06-12 17:31:58'),
('91', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Marca da Besta | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_000.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Marca da Besta\"}', NULL, '2026-06-12 17:31:59'),
('92', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Marca da Besta | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_000.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Marca da Besta\"}', NULL, '2026-06-12 17:31:59'),
('93', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Marca da Besta | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_000.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Marca da Besta\"}', NULL, '2026-06-12 17:32:00'),
('94', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Marca da Besta | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Marca da Besta\"}', NULL, '2026-06-12 17:32:05'),
('95', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Marca da Besta | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Marca da Besta\"}', NULL, '2026-06-12 17:32:05'),
('96', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Marca da Besta | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Marca da Besta\"}', NULL, '2026-06-12 17:32:05'),
('97', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Marca da Besta | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Marca da Besta\"}', NULL, '2026-06-12 17:32:06'),
('98', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Marca da Besta | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_000.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Marca da Besta\"}', NULL, '2026-06-12 17:32:07'),
('99', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Marca da Besta | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_001.html\", \"percentual\": 1, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Marca da Besta\"}', NULL, '2026-06-12 17:32:08'),
('100', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Marca da Besta | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_002.html\", \"percentual\": 1, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Marca da Besta\"}', NULL, '2026-06-12 17:32:09'),
('101', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Marca da Besta | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Marca da Besta\"}', NULL, '2026-06-12 17:32:10'),
('102', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Marca da Besta | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Marca da Besta\"}', NULL, '2026-06-12 17:32:10'),
('103', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Marca da Besta | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Marca da Besta\"}', NULL, '2026-06-12 17:32:11'),
('104', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Marca da Besta | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_008.html\", \"percentual\": 4, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Marca da Besta\"}', NULL, '2026-06-12 17:32:14'),
('105', 'rd_1781291897478_o5w5hr8', '1', 'Leitura_Progresso', 'home', 'A Marca da Besta | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_010.html\", \"percentual\": 14, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Marca da Besta\"}', NULL, '2026-06-12 17:32:17'),
('106', 'rd_1781291897478_o5w5hr8', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '3021', '2026-06-12 17:59:19'),
('107', 'rd_1781291897478_o5w5hr8', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '18', '2026-06-12 17:59:37'),
('108', 'rd_1781298062663_j0fxdvp', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '3', '2026-06-12 18:01:06'),
('109', 'rd_1781298062663_j0fxdvp', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '15', '2026-06-12 18:01:21'),
('110', 'rd_1781298062663_j0fxdvp', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '12', '2026-06-12 18:02:55'),
('111', 'rd_1781298062663_j0fxdvp', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '2', '2026-06-12 18:03:11'),
('112', 'rd_1781298062663_j0fxdvp', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '414', '2026-06-12 18:10:06'),
('113', 'rd_1781291897478_o5w5hr8', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '879', '2026-06-12 18:14:17'),
('114', 'rd_1781299193437_f2voth4', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '7200', '2026-06-12 20:37:16'),
('115', 'rd_1781314961432_0pu3ljf', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '6', '2026-06-12 22:44:39'),
('116', 'rd_1781314961432_0pu3ljf', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '4', '2026-06-12 22:45:00'),
('117', 'rd_1781314961432_0pu3ljf', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '2', '2026-06-12 22:45:37'),
('118', 'rd_1781352949565_2gq7dg0', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '3', '2026-06-13 09:15:52'),
('119', 'rd_1781361509744_s2si653', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '6', '2026-06-13 11:38:36'),
('120', 'rd_1781343575663_235665e', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '7200', '2026-06-13 12:58:38'),
('121', 'rd_1781343575663_235665e', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '372', '2026-06-13 13:05:39'),
('122', 'rd_1781343575663_235665e', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '2', '2026-06-13 13:07:22'),
('123', 'rd_1781352991373_zyaobfd', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '7200', '2026-06-13 13:18:18'),
('124', 'rd_1781367127661_ccwrahp', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '370', '2026-06-13 13:18:18'),
('125', 'rd_1781343575663_235665e', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '499', '2026-06-13 13:18:22'),
('126', 'rd_1781343575663_235665e', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '3', '2026-06-13 13:18:28'),
('127', 'rd_1781343575663_235665e', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '2', '2026-06-13 13:18:47'),
('128', 'rd_1781343575663_235665e', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '285', '2026-06-13 13:24:03'),
('129', 'rd_1781343575663_235665e', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '6', '2026-06-13 13:24:47'),
('130', 'rd_1781343575663_235665e', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '4', '2026-06-13 13:26:26'),
('131', 'rd_1781343575663_235665e', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '13', '2026-06-13 13:26:40'),
('132', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:26:53'),
('133', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:27:00'),
('134', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:27:01'),
('135', 'rd_1781343575663_235665e', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '709', '2026-06-13 13:38:35'),
('136', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:38:42'),
('137', 'rd_1781343575663_235665e', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '13', '2026-06-13 13:38:49'),
('138', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:38:55'),
('139', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:39:04'),
('140', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:39:04'),
('141', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_001.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:39:05'),
('142', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_002.html\", \"percentual\": 1, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:39:05'),
('143', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_003.html\", \"percentual\": 1, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:39:06'),
('144', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_004.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:39:06'),
('145', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:39:07'),
('146', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:39:08'),
('147', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:39:08'),
('148', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:39:09'),
('149', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:39:10'),
('150', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:39:10'),
('151', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:39:11'),
('152', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:39:11'),
('153', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:39:12'),
('154', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:39:12'),
('155', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_004.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:39:12'),
('156', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 3, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:44:03'),
('157', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:44:15'),
('158', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:44:16'),
('159', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:44:16'),
('160', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:46:38'),
('161', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:46:42'),
('162', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:46:43'),
('163', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:46:44'),
('164', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:50:43'),
('165', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:50:45'),
('166', 'rd_1781343575663_235665e', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '734', '2026-06-13 13:51:03'),
('167', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:54:50'),
('168', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:54:51'),
('169', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:54:51'),
('170', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:54:52'),
('171', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:54:52'),
('172', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 6, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:54:52'),
('173', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 6, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:54:53'),
('174', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 7, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:54:53'),
('175', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 8, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:54:53'),
('176', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 9, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:54:54'),
('177', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 9, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:54:54'),
('178', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 10, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:54:55'),
('179', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 10, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:54:56'),
('180', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 10, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:54:59'),
('181', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 10, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:55:00'),
('182', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:55:09'),
('183', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 9, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:56:05'),
('184', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 9, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:56:06'),
('185', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 8, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:56:07'),
('186', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 9, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:56:08'),
('187', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 9, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:56:08'),
('188', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 9, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:56:09'),
('189', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 9, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:56:32'),
('190', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 9, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:56:32'),
('191', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 10, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:56:34'),
('192', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 10, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:56:34'),
('193', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 11, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:56:35'),
('194', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:56:47'),
('195', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:56:50'),
('196', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 8, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:56:51'),
('197', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 7, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:56:52'),
('198', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 7, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:56:55'),
('199', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_008.html\", \"percentual\": 11, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:56:56'),
('200', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 13:57:08'),
('201', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 13:57:09'),
('202', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_000.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 13:57:09'),
('203', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_001.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 13:57:10'),
('204', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_003.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 13:57:11'),
('205', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_004.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 13:57:12'),
('206', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_004.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 13:57:13'),
('207', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_004.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 13:57:14'),
('208', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_004.html\", \"percentual\": 3, \"content_ids\": [\"home\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 13:57:16'),
('209', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_004.html\", \"percentual\": 3, \"content_ids\": [\"home\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 13:57:17'),
('210', 'rd_1781343575663_235665e', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '204', '2026-06-13 13:58:07'),
('211', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_004.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 13:58:33'),
('212', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_004.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 13:58:33'),
('213', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_004.html\", \"percentual\": 2, \"content_ids\": [\"home\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 13:58:34'),
('214', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_008.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 13:58:42'),
('215', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_008.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 14:04:26'),
('216', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_008.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 14:04:27'),
('217', 'rd_1781315965741_na65f2j', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '2', '2026-06-13 14:10:40'),
('218', 'rd_1781343575663_235665e', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '679', '2026-06-13 14:15:40'),
('219', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_008.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 14:15:46'),
('220', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_009.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 14:15:55'),
('221', 'rd_1781343575663_235665e', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '965', '2026-06-13 14:31:46'),
('222', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_009.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 14:31:51'),
('223', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_009.html\", \"percentual\": 14, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 14:31:59'),
('224', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_009.html\", \"percentual\": 14, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 14:32:00'),
('225', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_009.html\", \"percentual\": 14, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 14:32:02'),
('226', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_004.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 14:32:15'),
('227', 'rd_1781343575663_235665e', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '91', '2026-06-13 14:33:17'),
('228', 'rd_1781343575663_235665e', '10', 'Leitura_Progresso', 'home', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_009.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-13 14:33:23'),
('229', 'rd_1781343575663_235665e', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '45', '2026-06-13 14:34:02'),
('230', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '2', '2026-06-13 15:04:18'),
('231', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '87', '2026-06-13 15:06:16'),
('232', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '3', '2026-06-13 15:07:15'),
('233', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '2', '2026-06-13 15:07:27'),
('234', 'rd_1781373857325_nhdv4ok', '12', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:11:15'),
('235', 'rd_1781373857325_nhdv4ok', '12', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:11:16'),
('236', 'rd_1781373857325_nhdv4ok', '12', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_000.html\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:11:16'),
('237', 'rd_1781373857325_nhdv4ok', '12', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_001.html\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:11:17'),
('238', 'rd_1781373857325_nhdv4ok', '12', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:18:05'),
('239', 'rd_1781373857325_nhdv4ok', '12', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:18:05'),
('240', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '593', '2026-06-13 15:18:09'),
('241', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '336', '2026-06-13 15:24:18'),
('242', 'rd_1781373857325_nhdv4ok', '5', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:24:43'),
('243', 'rd_1781373857325_nhdv4ok', '5', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:24:44'),
('244', 'rd_1781373857325_nhdv4ok', '5', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_000.html\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:24:45'),
('245', 'rd_1781373857325_nhdv4ok', '5', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_001.html\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:24:45'),
('246', 'rd_1781373857325_nhdv4ok', '5', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_000.html\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:40:30'),
('247', 'rd_1781373857325_nhdv4ok', '5', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:40:31'),
('248', 'rd_1781373857325_nhdv4ok', '5', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:40:31'),
('249', 'rd_1781373857325_nhdv4ok', '5', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:40:32'),
('250', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '958', '2026-06-13 15:40:36'),
('251', 'rd_1781373857325_nhdv4ok', '13', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:57:51'),
('252', 'rd_1781373857325_nhdv4ok', '13', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:57:52'),
('253', 'rd_1781373857325_nhdv4ok', '13', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_000.html\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:57:52'),
('254', 'rd_1781373857325_nhdv4ok', '13', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_001.html\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:57:52'),
('255', 'rd_1781373857325_nhdv4ok', '13', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_002.html\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:57:53'),
('256', 'rd_1781373857325_nhdv4ok', '13', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_003.html\", \"percentual\": 1, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:57:53'),
('257', 'rd_1781373857325_nhdv4ok', '13', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_004.html\", \"percentual\": 2, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:57:54'),
('258', 'rd_1781373857325_nhdv4ok', '13', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_004.html\", \"percentual\": 2, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 15:57:55'),
('259', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '1475', '2026-06-13 16:05:40'),
('260', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '3', '2026-06-13 16:05:54'),
('261', 'rd_1781373857325_nhdv4ok', '14', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 16:13:02'),
('262', 'rd_1781373857325_nhdv4ok', '14', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 16:13:03'),
('263', 'rd_1781373857325_nhdv4ok', '14', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 16:13:04'),
('264', 'rd_1781373857325_nhdv4ok', '14', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_001.html\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 16:13:04'),
('265', 'rd_1781373857325_nhdv4ok', '14', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_002.html\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 16:13:04'),
('266', 'rd_1781373857325_nhdv4ok', '14', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_004.html\", \"percentual\": 2, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 16:13:05'),
('267', 'rd_1781373857325_nhdv4ok', '14', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_004.html\", \"percentual\": 2, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 16:13:05'),
('268', 'rd_1781373857325_nhdv4ok', '14', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_004.html\", \"percentual\": 2, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 16:13:30'),
('269', 'rd_1781379177550_8mg5uf7', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '7', '2026-06-13 16:33:04'),
('270', 'rd_1781313321534_034tpwv', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '8', '2026-06-13 17:31:08'),
('271', 'rd_1781373857325_nhdv4ok', '14', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_004.html\", \"percentual\": 2, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 20:07:32'),
('272', 'rd_1781373857325_nhdv4ok', '14', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_004.html\", \"percentual\": 2, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 20:07:38'),
('273', 'rd_1781373857325_nhdv4ok', '14', 'Leitura_Progresso', 'index', 'Cartas do Passado | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_004.html\", \"percentual\": 2, \"content_ids\": [\"index\"], \"livro_titulo\": \"Cartas do Passado\"}', NULL, '2026-06-13 20:07:40'),
('274', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '7200', '2026-06-13 20:09:09'),
('275', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '55', '2026-06-13 20:10:05'),
('276', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '12', '2026-06-13 20:10:17'),
('277', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '412', '2026-06-13 20:17:09'),
('278', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '31', '2026-06-13 20:18:47'),
('279', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '9', '2026-06-13 20:20:18'),
('280', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '35', '2026-06-13 20:20:53'),
('281', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '25', '2026-06-13 20:21:19'),
('282', 'rd_1781393209659_qfnpouu', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '3', '2026-06-13 20:26:52'),
('283', 'rd_1781393209562_h3l51y0', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '3', '2026-06-13 20:26:52'),
('284', 'rd_1781393209963_7eu3yhy', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '3', '2026-06-13 20:26:52'),
('285', 'rd_1781383266905_blordex', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '7200', '2026-06-13 20:27:12'),
('286', 'rd_1781383266905_blordex', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '16', '2026-06-13 20:27:27'),
('287', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '303', '2026-06-13 20:29:16'),
('288', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '10', '2026-06-13 20:29:27'),
('289', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '592', '2026-06-13 20:39:19'),
('290', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '3', '2026-06-13 20:39:49'),
('291', 'rd_1781394531856_qu64suo', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '5', '2026-06-13 20:49:05'),
('292', 'rd_1781394531856_qu64suo', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '19', '2026-06-13 20:49:25'),
('293', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '790', '2026-06-13 20:53:07'),
('294', 'rd_1781373857325_nhdv4ok', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '10', '2026-06-13 20:53:18'),
('295', 'rd_1781394973115_w1o25r9', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '2', '2026-06-13 20:56:14'),
('296', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '2', '2026-06-13 20:56:34'),
('297', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '133', '2026-06-13 20:58:47'),
('298', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '73', '2026-06-13 21:00:00'),
('299', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '13', '2026-06-13 21:00:13'),
('300', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '747', '2026-06-13 21:12:41'),
('301', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '4', '2026-06-13 21:12:45'),
('302', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '33', '2026-06-13 21:13:19'),
('303', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '4', '2026-06-13 21:13:27'),
('304', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '245', '2026-06-13 21:17:33'),
('305', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '75', '2026-06-13 21:18:50'),
('306', 'rd_1781383266905_blordex', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '3197', '2026-06-13 21:20:59'),
('307', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '141', '2026-06-13 21:21:14'),
('308', 'rd_1781437059973_mxzou5m', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '5', '2026-06-14 08:37:55'),
('309', 'rd_1781437059973_mxzou5m', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '403', '2026-06-14 08:44:39'),
('310', 'rd_1781437059973_mxzou5m', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '752', '2026-06-14 08:57:11'),
('311', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '82', '2026-06-14 08:58:57'),
('312', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '5', '2026-06-14 08:59:04'),
('313', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '456', '2026-06-14 09:06:41'),
('314', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '269', '2026-06-14 09:11:11'),
('315', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:11'),
('316', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:12'),
('317', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 4, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:13'),
('318', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 8, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:14'),
('319', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 12, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:15'),
('320', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 19, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:15'),
('321', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 23, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:15'),
('322', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 27, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:16'),
('323', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 31, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:16'),
('324', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 39, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:16'),
('325', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 42, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:16'),
('326', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 50, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:17'),
('327', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 58, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:17'),
('328', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 62, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:18'),
('329', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 65, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:18'),
('330', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 69, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:18'),
('331', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 77, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:24'),
('332', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 81, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:24'),
('333', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 85, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:24'),
('334', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 92, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:25'),
('335', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 96, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:25'),
('336', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 100, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:11:25'),
('337', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 96, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:37'),
('338', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 92, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:38'),
('339', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '209', '2026-06-14 09:14:40'),
('340', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:41'),
('341', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 54, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:41'),
('342', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 54, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:42'),
('343', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 46, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:42'),
('344', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 42, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:42'),
('345', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 39, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:43'),
('346', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 35, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:43'),
('347', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 31, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:43'),
('348', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 27, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:44'),
('349', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 23, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:44'),
('350', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 19, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:44'),
('351', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 15, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:44'),
('352', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 12, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:45'),
('353', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 4, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:45'),
('354', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 4, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:45'),
('355', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 12, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:45'),
('356', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 8, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:46'),
('357', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 4, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:46'),
('358', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:46'),
('359', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:46'),
('360', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:47'),
('361', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:48'),
('362', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 4, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:49'),
('363', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 8, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:50'),
('364', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 12, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:50'),
('365', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 15, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:51'),
('366', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 19, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:51'),
('367', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 27, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:52'),
('368', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 31, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:52'),
('369', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 35, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:53'),
('370', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 39, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:53'),
('371', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 46, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:53'),
('372', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 54, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:54'),
('373', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 58, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:54'),
('374', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 62, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:54'),
('375', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 69, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:55'),
('376', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 73, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:55'),
('377', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 77, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:55'),
('378', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 85, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:56'),
('379', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 89, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:56'),
('380', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 92, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:56'),
('381', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 96, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:57'),
('382', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 100, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:14:57'),
('383', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'home', 'O farol do afogado | Leitor | Robério Diógenes', '{\"capitulo\": \"index.html\", \"percentual\": 96, \"content_ids\": [\"home\"], \"livro_titulo\": \"O farol do afogado\"}', NULL, '2026-06-14 09:31:23'),
('384', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '1006', '2026-06-14 09:31:26'),
('385', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'blog', NULL, NULL, '96', '2026-06-14 09:33:03'),
('386', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'blog', NULL, NULL, '107', '2026-06-14 09:34:50'),
('387', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'blog', NULL, NULL, '12', '2026-06-14 09:35:02'),
('388', 'rd_1781394991977_pa494yw', '1', 'ViewContent', 'h-g-wells-guia-definitivo', 'H. G. Wells: O Homem Que Imaginou o Futuro | Diário | Robério Diógenes', '{\"content_ids\": [\"h-g-wells-guia-definitivo\"], \"content_name\": \"H. G. Wells: O Homem Que Imaginou o Futuro Antes que Ele Existisse\", \"content_type\": \"post\"}', '8', '2026-06-14 09:35:04'),
('389', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'blog', NULL, NULL, '57', '2026-06-14 09:36:07'),
('390', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'blog', NULL, NULL, '1639', '2026-06-14 10:03:27'),
('391', 'rd_1781394991977_pa494yw', NULL, 'ViewContent', 'h-g-wells-guia-definitivo', 'H. G. Wells: O Homem Que Imaginou o Futuro | Diário | Robério Diógenes', '{\"content_ids\": [\"h-g-wells-guia-definitivo\"], \"content_name\": \"H. G. Wells: O Homem Que Imaginou o Futuro Antes que Ele Existisse\", \"content_type\": \"post\"}', '93', '2026-06-14 10:03:28'),
('392', 'rd_1781394991977_pa494yw', NULL, 'ViewContent', 'h-g-wells-guia-definitivo', 'H. G. Wells: O Homem Que Imaginou o Futuro | Diário | Robério Diógenes', '{\"content_ids\": [\"h-g-wells-guia-definitivo\"], \"content_name\": \"H. G. Wells: O Homem Que Imaginou o Futuro Antes que Ele Existisse\", \"content_type\": \"post\"}', '6', '2026-06-14 10:05:01'),
('393', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'blog', NULL, NULL, '174', '2026-06-14 10:08:00'),
('394', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'blog', NULL, NULL, '3', '2026-06-14 10:08:04'),
('395', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'blog', NULL, NULL, '66', '2026-06-14 10:09:10'),
('396', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'blog', NULL, NULL, '53', '2026-06-14 10:10:03'),
('397', 'rd_1781394991977_pa494yw', NULL, 'ViewContent', 'a-maquina-do-tempo-resenha', 'A Máquina do Tempo — Resenha | Diário | Robério Diógenes', '{\"content_ids\": [\"a-maquina-do-tempo-resenha\"], \"content_name\": \"A Máquina do Tempo: O Livro que Me Ensinou a Sonhar com o Futuro — e a Temer o que Encontraria Lá\", \"content_type\": \"post\"}', '338', '2026-06-14 10:10:05'),
('398', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'blog', NULL, NULL, '5', '2026-06-14 10:15:47'),
('399', 'rd_1781394991977_pa494yw', NULL, 'ViewContent', 'h-g-wells-guia-definitivo', 'H. G. Wells: O Homem Que Imaginou o Futuro | Diário | Robério Diógenes', '{\"content_ids\": [\"h-g-wells-guia-definitivo\"], \"content_name\": \"H. G. Wells: O Homem Que Imaginou o Futuro Antes que Ele Existisse\", \"content_type\": \"post\"}', '6', '2026-06-14 10:15:48'),
('400', 'rd_1781394991977_pa494yw', NULL, 'ViewContent', 'h-g-wells-guia-definitivo', 'H. G. Wells: O Homem Que Imaginou o Futuro | Diário | Robério Diógenes', '{\"content_ids\": [\"h-g-wells-guia-definitivo\"], \"content_name\": \"H. G. Wells: O Homem Que Imaginou o Futuro Antes que Ele Existisse\", \"content_type\": \"post\"}', '80', '2026-06-14 10:15:55'),
('401', 'rd_1781394991977_pa494yw', NULL, 'ViewContent', 'a-maquina-do-tempo-resenha', 'A Máquina do Tempo — Resenha | Diário | Robério Diógenes', '{\"content_ids\": [\"a-maquina-do-tempo-resenha\"], \"content_name\": \"A Máquina do Tempo: O Livro que Me Ensinou a Sonhar com o Futuro — e a Temer o que Encontraria Lá\", \"content_type\": \"post\"}', '4', '2026-06-14 10:17:15'),
('402', 'rd_1781394991977_pa494yw', NULL, 'ViewContent', 'a-maquina-do-tempo-resenha', 'A Máquina do Tempo — Resenha | Diário | Robério Diógenes', '{\"content_ids\": [\"a-maquina-do-tempo-resenha\"], \"content_name\": \"A Máquina do Tempo: O Livro que Me Ensinou a Sonhar com o Futuro — e a Temer o que Encontraria Lá\", \"content_type\": \"post\"}', '118', '2026-06-14 10:17:19'),
('403', 'rd_1781443094945_14q2lp5', NULL, 'ViewContent', 'a-maquina-do-tempo-resenha', 'A Máquina do Tempo — Resenha | Diário | Robério Diógenes', '{\"content_ids\": [\"a-maquina-do-tempo-resenha\"], \"content_name\": \"A Máquina do Tempo: O Livro que Me Ensinou a Sonhar com o Futuro — e a Temer o que Encontraria Lá\", \"content_type\": \"post\"}', '25', '2026-06-14 10:18:17'),
('404', 'rd_1781394991977_pa494yw', NULL, 'ViewContent', 'o-homem-invisivel-resenha', 'O Homem Invisível — Resenha | Diário | Robério Diógenes', '{\"content_ids\": [\"o-homem-invisivel-resenha\"], \"content_name\": \"O Homem Invisível: Quando o Maior Poder do Mundo se Torna a Mais Terrível das Prisões\", \"content_type\": \"post\"}', '29', '2026-06-14 10:19:18'),
('405', 'rd_1781394991977_pa494yw', NULL, 'ViewContent', 'o-dorminhoco-resenha', 'O Dorminhoco — Resenha | Diário | Robério Diógenes', '{\"content_ids\": [\"o-dorminhoco-resenha\"], \"content_name\": \"O Dorminhoco: Acordar em um Futuro que Nunca Pedimos Para Ver\", \"content_type\": \"post\"}', '26', '2026-06-14 10:19:48'),
('406', 'rd_1781394991977_pa494yw', NULL, 'ViewContent', 'a-ilha-do-dr-moreau-resenha', 'A Ilha do Dr. Moreau — Resenha | Diário | Robério Diógenes', '{\"content_ids\": [\"a-ilha-do-dr-moreau-resenha\"], \"content_name\": \"A Ilha do Dr. Moreau: O Horror que Mora Dentro da Pele Humana\", \"content_type\": \"post\"}', '16', '2026-06-14 10:20:14'),
('407', 'rd_1781394991977_pa494yw', NULL, 'ViewContent', 'a-guerra-dos-mundos-resenha', 'A Guerra dos Mundos — Resenha | Diário | Robério Diógenes', '{\"content_ids\": [\"a-guerra-dos-mundos-resenha\"], \"content_name\": \"A Guerra dos Mundos: Quando o Universo Parou de Ser Amigável\", \"content_type\": \"post\"}', '203', '2026-06-14 10:20:30'),
('408', 'rd_1781437059973_mxzou5m', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '5011', '2026-06-14 10:20:42'),
('409', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'blog', NULL, NULL, '14', '2026-06-14 10:24:06'),
('410', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '3', '2026-06-14 12:12:46'),
('411', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'blog', NULL, NULL, '4', '2026-06-14 12:12:51'),
('412', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '4', '2026-06-14 12:17:32'),
('413', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:02'),
('414', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:04'),
('415', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:04'),
('416', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"titlepage.xhtml\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:04'),
('417', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_001.html\", \"percentual\": 0, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:05'),
('418', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_002.html\", \"percentual\": 1, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:05'),
('419', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_003.html\", \"percentual\": 1, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:05'),
('420', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:06'),
('421', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_005.html\", \"percentual\": 2, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:06'),
('422', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 3, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:06'),
('423', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 3, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:07'),
('424', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 4, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:07'),
('425', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 4, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:07'),
('426', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 5, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:08'),
('427', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 5, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:08'),
('428', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 6, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:08'),
('429', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 6, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:09'),
('430', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_006.html\", \"percentual\": 7, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:09'),
('431', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 7, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:09'),
('432', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 8, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:10'),
('433', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 9, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:10'),
('434', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 9, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:11'),
('435', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 10, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:11'),
('436', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 10, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:11'),
('437', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_007.html\", \"percentual\": 11, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:12'),
('438', 'rd_1781394991977_pa494yw', '1', 'Leitura_Progresso', 'index', 'A Sétima Lei | Leitor | Robério Diógenes', '{\"capitulo\": \"index_split_008.html\", \"percentual\": 11, \"content_ids\": [\"index\"], \"livro_titulo\": \"A Sétima Lei\"}', NULL, '2026-06-14 12:18:12'),
('439', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"cover.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:47'),
('440', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"title.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:48'),
('441', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"title.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:49'),
('442', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"title.xhtml\", \"percentual\": 13, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:49'),
('443', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 25, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:50'),
('444', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 38, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:50'),
('445', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 38, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:51'),
('446', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 50, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:52'),
('447', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 63, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:52'),
('448', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 75, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:53'),
('449', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 88, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:53'),
('450', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 100, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:53'),
('451', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 100, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:54'),
('452', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 88, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:56'),
('453', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 88, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:56'),
('454', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 75, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:57'),
('455', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 63, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:57'),
('456', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 50, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:58'),
('457', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 38, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:58'),
('458', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 38, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:58'),
('459', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 25, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:58'),
('460', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"title.xhtml\", \"percentual\": 13, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:59'),
('461', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"cover.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:59'),
('462', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"cover.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:57:59'),
('463', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"title.xhtml\", \"percentual\": 13, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:58:01'),
('464', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"cover.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:58:02'),
('465', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"title.xhtml\", \"percentual\": 13, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:58:38'),
('466', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 25, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:58:38'),
('467', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 38, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:58:39'),
('468', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 38, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 16:58:40'),
('469', 'rd_1781467065900_sx98q1n', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '322', '2026-06-14 17:03:08'),
('470', 'rd_1781467065900_sx98q1n', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '37', '2026-06-14 17:03:45'),
('471', 'rd_1781467065900_sx98q1n', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '29', '2026-06-14 17:04:14'),
('472', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'A Penúltima Página | Leitor | Robério Diógenes', '{\"capitulo\": \"cover.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Penúltima Página\"}', NULL, '2026-06-14 17:04:15'),
('473', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'A Penúltima Página | Leitor | Robério Diógenes', '{\"capitulo\": \"title.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Penúltima Página\"}', NULL, '2026-06-14 17:04:17'),
('474', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'A Penúltima Página | Leitor | Robério Diógenes', '{\"capitulo\": \"title.xhtml\", \"percentual\": 14, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Penúltima Página\"}', NULL, '2026-06-14 17:04:18'),
('475', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'A Penúltima Página | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 29, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Penúltima Página\"}', NULL, '2026-06-14 17:04:18'),
('476', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'A Penúltima Página | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 43, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Penúltima Página\"}', NULL, '2026-06-14 17:04:19'),
('477', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'A Penúltima Página | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 57, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Penúltima Página\"}', NULL, '2026-06-14 17:04:19'),
('478', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'A Penúltima Página | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 71, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Penúltima Página\"}', NULL, '2026-06-14 17:04:20'),
('479', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'A Penúltima Página | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 100, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Penúltima Página\"}', NULL, '2026-06-14 17:04:20'),
('480', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'A Penúltima Página | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 100, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Penúltima Página\"}', NULL, '2026-06-14 17:04:20'),
('481', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'A Penúltima Página | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 100, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Penúltima Página\"}', NULL, '2026-06-14 17:04:21'),
('482', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'A Penúltima Página | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 86, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Penúltima Página\"}', NULL, '2026-06-14 17:04:33'),
('483', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'A Penúltima Página | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 71, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Penúltima Página\"}', NULL, '2026-06-14 17:04:33'),
('484', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'A Penúltima Página | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 57, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Penúltima Página\"}', NULL, '2026-06-14 17:04:33'),
('485', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'A Penúltima Página | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 43, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Penúltima Página\"}', NULL, '2026-06-14 17:04:34'),
('486', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'A Penúltima Página | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 29, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Penúltima Página\"}', NULL, '2026-06-14 17:04:34'),
('487', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'A Penúltima Página | Leitor | Robério Diógenes', '{\"capitulo\": \"title.xhtml\", \"percentual\": 14, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Penúltima Página\"}', NULL, '2026-06-14 17:04:34'),
('488', 'rd_1781467065900_sx98q1n', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '317', '2026-06-14 17:09:31'),
('489', 'rd_1781467065900_sx98q1n', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '12', '2026-06-14 17:09:44'),
('490', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'A Penúltima Página | Leitor | Robério Diógenes', '{\"capitulo\": \"title.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Penúltima Página\"}', NULL, '2026-06-14 17:09:45'),
('491', 'rd_1781467065900_sx98q1n', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '8', '2026-06-14 17:09:52'),
('492', 'rd_1781467065900_sx98q1n', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '16', '2026-06-14 17:10:08'),
('493', 'rd_1781467065900_sx98q1n', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '32', '2026-06-14 17:10:40'),
('494', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"cover.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 17:10:41'),
('495', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"title.xhtml\", \"percentual\": 14, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 17:10:43'),
('496', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 29, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 17:10:43'),
('497', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 57, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 17:10:44'),
('498', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 71, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 17:10:44'),
('499', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 86, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 17:10:44'),
('500', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 100, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 17:10:45');
;
INSERT INTO `analytics_eventos` VALUES
('501', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 100, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 17:10:45'),
('502', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 100, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 17:14:43'),
('503', 'rd_1781467065900_sx98q1n', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '254', '2026-06-14 17:14:54'),
('504', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"cover.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 17:14:55'),
('505', 'rd_1781467065900_sx98q1n', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '26', '2026-06-14 17:15:20'),
('506', 'rd_1781467065900_sx98q1n', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 17:15:21'),
('507', 'rd_1781469212029_afzrt8m', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 17:33:33'),
('508', 'rd_1781469212029_afzrt8m', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 29, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 17:33:34'),
('509', 'rd_1781469212029_afzrt8m', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 29, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 17:33:34'),
('510', 'rd_1781469212029_afzrt8m', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"title.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 17:33:35'),
('511', 'rd_1781469212029_afzrt8m', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"cover.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 17:33:35'),
('512', 'rd_1781469212029_afzrt8m', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"cover.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 17:41:46'),
('513', 'rd_1781467065900_sx98q1n', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '1607', '2026-06-14 17:42:07'),
('514', 'rd_1781469734086_ffu9sb5', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 17:42:15'),
('515', 'rd_1781469752664_87m3a75', '1', 'Leitura_Progresso', 'home', 'A Penúltima Página | Leitor | Robério Diógenes', '{\"capitulo\": \"title.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"A Penúltima Página\"}', NULL, '2026-06-14 17:42:34'),
('516', 'rd_1781469768991_8jywdf8', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"cover.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 17:42:50'),
('517', 'rd_1781469787774_uk87hae', '1', 'Leitura_Progresso', 'home', 'O Labirinto dos Espelhos | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Labirinto dos Espelhos\"}', NULL, '2026-06-14 17:43:09'),
('518', 'rd_1781394991977_pa494yw', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '7200', '2026-06-14 19:15:27'),
('519', 'rd_1781449175076_8tidllz', NULL, 'Tempo_Pagina', 'blog', NULL, NULL, '7200', '2026-06-14 19:15:27'),
('520', 'rd_1781469787774_uk87hae', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '5553', '2026-06-14 19:15:41'),
('521', 'rd_1781469768991_8jywdf8', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '5572', '2026-06-14 19:15:41'),
('522', 'rd_1781469752664_87m3a75', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '5589', '2026-06-14 19:15:42'),
('523', 'rd_1781469734086_ffu9sb5', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '5608', '2026-06-14 19:15:42'),
('524', 'rd_1781469212029_afzrt8m', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"cover.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 20:29:23'),
('525', 'rd_1781469212029_afzrt8m', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"cover.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 20:29:23'),
('526', 'rd_1781469212029_afzrt8m', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"title.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 20:40:11'),
('527', 'rd_1781469212029_afzrt8m', '1', 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"cover.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-14 20:40:11'),
('528', 'rd_1781480656248_jjmq9op', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '14', '2026-06-14 20:45:05'),
('529', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"cover.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:16'),
('530', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"title.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:18'),
('531', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"title.xhtml\", \"percentual\": 14, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:19'),
('532', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 29, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:20'),
('533', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 29, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:21'),
('534', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 29, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:22'),
('535', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 29, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:22'),
('536', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 29, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:23'),
('537', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 29, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:24'),
('538', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 43, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:24'),
('539', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 43, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:25'),
('540', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 43, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:25'),
('541', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 43, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:27'),
('542', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 43, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:27'),
('543', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 43, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:27'),
('544', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 57, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:28'),
('545', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 43, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:28'),
('546', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 57, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:29'),
('547', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 57, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:42'),
('548', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 57, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:43'),
('549', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 29, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:45'),
('550', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"content.xhtml\", \"percentual\": 14, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:45'),
('551', 'rd_1781480716064_3y9jlx9', '1', 'Leitura_Progresso', 'home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '{\"capitulo\": \"cover.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Quarto Passageiro\"}', NULL, '2026-06-14 20:45:45'),
('552', 'rd_1781383206967_0j3g7oi', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '2', '2026-06-15 00:04:53'),
('553', 'rd_1781503928055_rkj6dyt', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '11', '2026-06-15 03:12:19'),
('554', 'rd_1781503933039_aeid1ab', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '9', '2026-06-15 03:12:22'),
('555', 'rd_1781503930332_gnw70mb', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '13', '2026-06-15 03:12:23'),
('556', 'rd_1781503931873_em2cwkx', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '15', '2026-06-15 03:12:27'),
('557', 'rd_1781505240456_hb7k5f4', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '19', '2026-06-15 03:34:18'),
('558', 'rd_1781505240456_hb7k5f4', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '6', '2026-06-15 03:34:48'),
('559', 'rd_1781512131184_8o2cmpp', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '15', '2026-06-15 05:29:06'),
('560', 'rd_1781519397006_0pqlwj2', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '22', '2026-06-15 07:30:18'),
('561', 'rd_1781519397006_0pqlwj2', NULL, 'Tempo_Pagina', 'index', NULL, NULL, '4', '2026-06-15 07:30:26'),
('562', 'rd_1781519397006_0pqlwj2', NULL, 'Tempo_Pagina', 'livros', NULL, NULL, '34', '2026-06-15 07:31:02'),
('563', 'rd_1781519397006_0pqlwj2', NULL, 'Tempo_Pagina', 'blog', NULL, NULL, '8', '2026-06-15 07:32:27'),
('564', 'rd_1781519397006_0pqlwj2', NULL, 'ViewContent', 'a-ilha-do-dr-moreau-resenha', 'A Ilha do Dr. Moreau — Resenha | Diário | Robério Diógenes', '{\"content_ids\": [\"a-ilha-do-dr-moreau-resenha\"], \"content_name\": \"A Ilha do Dr. Moreau: O Horror que Mora Dentro da Pele Humana\", \"content_type\": \"post\"}', NULL, '2026-06-15 07:32:29'),
('565', 'rd_1781519972761_jwxelh7', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '29', '2026-06-15 07:40:02'),
('566', 'rd_1781469212029_afzrt8m', NULL, 'Leitura_Progresso', 'home', 'O Peso do Horizonte | Leitor | Robério Diógenes', '{\"capitulo\": \"cover.xhtml\", \"percentual\": 0, \"content_ids\": [\"home\"], \"livro_titulo\": \"O Peso do Horizonte\"}', NULL, '2026-06-15 12:50:52'),
('567', 'rd_1781469212029_afzrt8m', NULL, 'Tempo_Pagina', 'home', NULL, NULL, '7200', '2026-06-15 12:50:59');

-- ------------------------------------------------------------
-- Tabela: `analytics_sessoes`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `analytics_sessoes`;
CREATE TABLE `analytics_sessoes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ID gerado pelo JS (rd_timestamp_random)',
  `usuario_id` int(10) unsigned DEFAULT NULL COMMENT 'NULL = visitante anônimo',
  `utm_source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_medium` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_campaign` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_term` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_content` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dispositivo` enum('desktop','mobile','tablet') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'desktop',
  `idioma` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'navigator.language',
  `referrer` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `landing_page` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Primeira URL da sessão',
  `pagina_tipo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'livro | post | leitor | home | autor ...',
  `ip_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SHA-256 do IP (LGPD)',
  `iniciada_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_session` (`session_id`),
  KEY `idx_utm_source` (`utm_source`),
  KEY `idx_utm_campaign` (`utm_campaign`),
  KEY `idx_dispositivo` (`dispositivo`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_iniciada` (`iniciada_em`)
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sessões anônimas e autenticadas com dados de origem de tráfego';

INSERT INTO `analytics_sessoes` VALUES
('1', 'rd_1781271314305_lm0vpbi', NULL, '', '', '', '', '', 'desktop', 'pt-BR', '', 'https://www.roberiodiogenes.com/', 'home', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-12 10:35:16'),
('2', 'rd_1781272687359_km5d7dg', NULL, '', '', '', '', '', 'mobile', 'pt-BR', '', 'https://roberiodiogenes.com/', 'home', '95077cc248646072557b1f89579e4faeb7a45d49080a05a9779ffe16d320afc4', '2026-06-12 10:58:06'),
('3', 'rd_1781276060013_enup9b4', NULL, '', '', '', '', '', 'mobile', 'pt-BR', '', 'https://roberiodiogenes.com/index.html', 'home', '95077cc248646072557b1f89579e4faeb7a45d49080a05a9779ffe16d320afc4', '2026-06-12 11:54:20'),
('4', 'rd_1781285850531_erwhq7k', NULL, '', '', '', '', '', 'mobile', 'pt-BR', '', 'https://roberiodiogenes.com/', 'home', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-12 14:37:29'),
('5', 'rd_1781287828722_qmb0l65', NULL, '', '', '', '', '', 'desktop', 'pt-BR', 'https://roberiodiogenes.com/login.html', 'https://roberiodiogenes.com/index.html', 'home', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-12 15:10:29'),
('6', 'rd_1781288935251_8w35w3d', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://roberiodiogenes.com/', 'home', 'fca509d7c4d4274102f8f203d4e3e0940b51aa3b5f9babba227908f6d4dbac78', '2026-06-12 15:28:56'),
('7', 'rd_1781288946323_5wqeiqo', NULL, '', '', '', '', '', 'mobile', 'en-US', '', 'https://roberiodiogenes.com/', 'home', 'fca509d7c4d4274102f8f203d4e3e0940b51aa3b5f9babba227908f6d4dbac78', '2026-06-12 15:29:06'),
('8', 'rd_1781289729791_zp1usw6', NULL, '', '', '', '', '', 'desktop', 'pt-BR', 'https://roberiodiogenes.com/login.html', 'https://roberiodiogenes.com/index.html', 'home', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-12 15:42:11'),
('9', 'rd_1781291054819_obri8w0', '1', '', '', '', '', '', 'desktop', 'pt-BR', 'https://accounts.google.com/', 'https://roberiodiogenes.com/leitor/index.html', 'home', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-12 16:04:15'),
('10', 'rd_1781291317381_to0fgxd', '1', '', '', '', '', '', 'desktop', 'pt-BR', 'https://roberiodiogenes.com/admin', 'https://roberiodiogenes.com/index.html', 'home', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-12 16:08:38'),
('11', 'rd_1781291897478_o5w5hr8', '1', '', '', '', '', '', 'desktop', 'pt-BR', 'https://roberiodiogenes.com/privacidade.html', 'https://roberiodiogenes.com/index.html', 'home', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-12 16:18:18'),
('12', 'rd_1781292097214_g4ln5jl', '1', '', '', '', '', '', 'desktop', 'pt-BR', '', 'https://roberiodiogenes.com/index.html?nl=confirmado', 'home', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-12 16:21:37'),
('13', 'rd_1781296157753_s1qtjct', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://roberiodiogenes.com/', 'home', '6a1ad5c1759369bd362132cbb842d42bb8830ce74809907c34d518862ba8cb3c', '2026-06-12 17:29:18'),
('14', 'rd_1781298062663_j0fxdvp', '1', '', '', '', '', '', 'desktop', 'pt-BR', '', 'https://roberiodiogenes.com/', 'home', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-12 18:01:03'),
('15', 'rd_1781299193437_f2voth4', '1', '', '', '', '', '', 'desktop', 'pt-BR', 'https://roberiodiogenes.com/livros.html', 'https://roberiodiogenes.com/leitor/?livro=o-farol-do-afogdo', 'leitor', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-12 18:19:54'),
('16', 'rd_1781313321534_034tpwv', NULL, '', '', '', '', '', 'desktop', 'pt-BR', '', 'https://www.roberiodiogenes.com/', 'home', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-12 22:15:21'),
('17', 'rd_1781314961432_0pu3ljf', NULL, '', '', '', '', '', 'desktop', 'pt-BR', '', 'https://roberiodiogenes.com/', 'home', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-12 22:42:41'),
('18', 'rd_1781315425950_40dbn62', NULL, '', '', '', '', '', 'desktop', 'pt-BR', '', 'https://roberiodiogenes.com/livros.html', 'biblioteca', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-12 22:50:25'),
('19', 'rd_1781315965741_na65f2j', NULL, '', '', '', '', '', 'desktop', 'pt-BR', '', 'https://roberiodiogenes.com/livros.html', 'biblioteca', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-12 22:59:25'),
('20', 'rd_1781343463752_mz6eqoq', '5', '', '', '', '', '', 'desktop', 'pt-BR', '', 'https://roberiodiogenes.com/livros.html', 'biblioteca', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-13 06:37:43'),
('21', 'rd_1781343575663_235665e', '5', '', '', '', '', '', 'desktop', 'pt-BR', '', 'https://roberiodiogenes.com/livros.html', 'biblioteca', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-13 06:39:35'),
('22', 'rd_1781352949565_2gq7dg0', '6', '', '', '', '', '', 'desktop', 'pt-BR', 'https://roberiodiogenes.com/backend/tracker.php?token=1ce92163a05222a5b8a1480628ae374d614757ab36fd1fed983a2b77c0f5bfeb&acao=baixar_conto', 'https://roberiodiogenes.com/livros.html', 'biblioteca', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-13 09:15:50'),
('23', 'rd_1781352991373_zyaobfd', '6', '', '', '', '', '', 'desktop', 'pt-BR', '', 'https://roberiodiogenes.com/livros.html', 'biblioteca', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-13 09:16:31'),
('24', 'rd_1781361509744_s2si653', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://roberiodiogenes.com/', 'home', '3c830e664f2dfb057ef0467ce16cae8cdd630b46ac34f272a21de9dd3d0f6fe8', '2026-06-13 11:38:30'),
('25', 'rd_1781367127661_ccwrahp', '8', '', '', '', '', '', 'desktop', 'pt-BR', '', 'https://roberiodiogenes.com/livros.html', 'biblioteca', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-13 13:12:07'),
('26', 'rd_1781373857325_nhdv4ok', NULL, '', '', '', '', '', 'desktop', 'pt-BR', '', 'https://roberiodiogenes.com/', 'home', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-13 15:04:17'),
('27', 'rd_1781379177550_8mg5uf7', '14', '', '', '', '', '', 'desktop', 'pt-BR', '', 'https://roberiodiogenes.com/', 'home', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-13 16:32:57'),
('28', 'rd_1781383206967_0j3g7oi', NULL, '', '', '', '', '', 'desktop', 'pt-BR', '', 'https://roberiodiogenes.com/livros.html', 'biblioteca', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-13 17:40:06'),
('29', 'rd_1781383266905_blordex', NULL, '', '', '', '', '', 'desktop', 'pt-BR', '', 'https://roberiodiogenes.com/livros.html', 'biblioteca', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-13 17:41:06'),
('30', 'rd_1781393209963_7eu3yhy', NULL, '', '', '', '', '', 'mobile', 'en-US', '', 'https://roberiodiogenes.com/', 'home', 'fb61823d1a328eee8729050ce891ffdfe341ad7e8a85bfe89f2a43202e2fe1a8', '2026-06-13 20:26:50'),
('31', 'rd_1781393209562_h3l51y0', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://roberiodiogenes.com/', 'home', '0179fdffd1c2dc9a551a4df7f7b668cfb3001e522260bb5009c2c720091c9d49', '2026-06-13 20:26:50'),
('32', 'rd_1781393209659_qfnpouu', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://roberiodiogenes.com/', 'home', 'acd5bb2122624aaa03bc374aafff11b6fecd0fb49554f8b0b25e85c43b8df992', '2026-06-13 20:26:50'),
('33', 'rd_1781394014773_ybfa0nw', NULL, '', '', '', '', '', 'desktop', 'en-US', 'https://bing.com/', 'https://roberiodiogenes.com/', 'home', '435cd5380c6adbdf0996ef12b4eabac55a4356d85a476998cce977e34f94a65d', '2026-06-13 20:40:15'),
('34', 'rd_1781394033928_pav1qpq', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://roberiodiogenes.com/', 'home', 'ce62e32f43cbed86a6b84553bf55ac0d91272b8ca94ecc65817b221005523865', '2026-06-13 20:40:36'),
('35', 'rd_1781394482003_r4nm6cg', NULL, '', '', '', '', '', 'desktop', 'pt-BR', '', 'https://www.roberiodiogenes.com/', 'home', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-13 20:48:02'),
('36', 'rd_1781394531856_qu64suo', NULL, '', '', '', '', '', 'desktop', 'pt-BR', 'https://roberiodiogenes.com/login.html', 'https://roberiodiogenes.com/index.html', 'home', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-13 20:48:51'),
('37', 'rd_1781394973115_w1o25r9', NULL, '', '', '', '', '', 'desktop', 'pt-BR', '', 'https://www.roberiodiogenes.com/', 'home', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-13 20:56:13'),
('38', 'rd_1781394991977_pa494yw', '1', '', '', '', '', '', 'desktop', 'pt-BR', 'https://accounts.google.com/', 'https://roberiodiogenes.com/leitor/index.html', 'home', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-13 20:56:31'),
('39', 'rd_1781435153216_tgtcs1y', NULL, '', '', '', '', '', 'desktop', 'pt-BR', '', 'https://roberiodiogenes.com/livros.html', 'biblioteca', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-14 08:05:53'),
('40', 'rd_1781437059973_mxzou5m', NULL, '', '', '', '', '', 'desktop', 'pt-BR', '', 'https://roberiodiogenes.com/leitor/?livro=o-farol-do-afogado', 'leitor', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-14 08:37:40'),
('41', 'rd_1781449175076_8tidllz', NULL, '', '', '', '', '', 'desktop', 'pt-BR', 'https://localhost/', 'https://roberiodiogenes.com/blog.html', 'blog', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-14 11:59:35'),
('42', 'rd_1781467065900_sx98q1n', '1', '', '', '', '', '', 'desktop', 'pt-BR', '', 'https://roberiodiogenes.com/leitor/?livro=o-labirinto-dos-espelhos', 'leitor', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-14 16:57:46'),
('43', 'rd_1781468210744_fxnldgt', '1', '', '', '', '', '', 'desktop', 'pt-BR', 'https://localhost/', 'https://roberiodiogenes.com/livros.html', 'biblioteca', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-14 17:16:51'),
('44', 'rd_1781469212029_afzrt8m', '1', '', '', '', '', '', 'desktop', 'pt-BR', 'https://localhost/', 'https://roberiodiogenes.com/leitor/?livro=o-peso-do-horizonte', 'leitor', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-14 17:33:32'),
('45', 'rd_1781469734086_ffu9sb5', '1', '', '', '', '', '', 'desktop', 'pt-BR', 'https://roberiodiogenes.com/bio.html', 'https://roberiodiogenes.com/leitor/?livro=o-peso-do-horizonte', 'leitor', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-14 17:42:14'),
('46', 'rd_1781469752664_87m3a75', '1', '', '', '', '', '', 'desktop', 'pt-BR', 'https://roberiodiogenes.com/bio.html', 'https://roberiodiogenes.com/leitor/?livro=a-penultima-pagina', 'leitor', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-14 17:42:33'),
('47', 'rd_1781469768991_8jywdf8', '1', '', '', '', '', '', 'desktop', 'pt-BR', 'https://roberiodiogenes.com/bio.html', 'https://roberiodiogenes.com/leitor/?livro=o-quarto-passageiro', 'leitor', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-14 17:42:49'),
('48', 'rd_1781469787774_uk87hae', '1', '', '', '', '', '', 'desktop', 'pt-BR', 'https://roberiodiogenes.com/bio.html', 'https://roberiodiogenes.com/leitor/?livro=o-labirinto-dos-espelhos', 'leitor', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-14 17:43:08'),
('49', 'rd_1781480656248_jjmq9op', NULL, '', '', '', '', '', 'mobile', 'pt-BR', '', 'https://roberiodiogenes.com/leitor/?livro=o-quarto-passageiro', 'leitor', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-14 20:44:14'),
('50', 'rd_1781480716064_3y9jlx9', '1', '', '', '', '', '', 'mobile', 'pt-BR', '', 'https://roberiodiogenes.com/leitor/?livro=o-quarto-passageiro', 'leitor', 'e356ec7d4991a23d84a908964f3656a70ca7cef7544cdee36f0eb4ae4c803ef4', '2026-06-14 20:45:14'),
('51', 'rd_1781503928055_rkj6dyt', NULL, '', '', '', '', '', 'mobile', 'en-US', '', 'https://www.roberiodiogenes.com/', 'home', '6e3661f60a28690a94d7b3d7f7040dc546fee35f4ec214c36a8596c61e9b4391', '2026-06-15 03:12:08'),
('52', 'rd_1781503930332_gnw70mb', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://www.roberiodiogenes.com/', 'home', '6e3661f60a28690a94d7b3d7f7040dc546fee35f4ec214c36a8596c61e9b4391', '2026-06-15 03:12:10'),
('53', 'rd_1781503931873_em2cwkx', NULL, '', '', '', '', '', 'mobile', 'en-US', '', 'https://roberiodiogenes.com/', 'home', '2460beef8c53c9fc1dab8772a1e3efb505fe0ac16c2f54be9f34bf32557c3aac', '2026-06-15 03:12:12'),
('54', 'rd_1781503933039_aeid1ab', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://roberiodiogenes.com/', 'home', '506c59d3aaa760fd66382e74ba8fa3f075b6e80cbb238c7b4d9e2d32f7bc2a40', '2026-06-15 03:12:13'),
('55', 'rd_1781504016011_9dufy7j', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://www.roberiodiogenes.com/', 'home', '3f01f438fb1c61d0034b18b54b1cb29a61c3c5d4ecd74f9521bda9cf8e6d4248', '2026-06-15 03:13:36'),
('56', 'rd_1781504019518_dgses5w', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://roberiodiogenes.com/', 'home', '3f01f438fb1c61d0034b18b54b1cb29a61c3c5d4ecd74f9521bda9cf8e6d4248', '2026-06-15 03:13:39'),
('57', 'rd_1781505240456_hb7k5f4', NULL, '', '', '', '', '', 'mobile', 'pt-BR', 'android-app://com.google.android.googlequicksearchbox/', 'https://roberiodiogenes.com/', 'home', 'a6f5adbab7fea6fb71a4f6c61a2a51bd2771e43cf47b2e91dcfc151a30f8dee0', '2026-06-15 03:33:59'),
('58', 'rd_1781507786160_dz914oi', NULL, '', '', '', '', '', 'desktop', 'en-US', 'https://bing.com/', 'https://roberiodiogenes.com/', 'home', 'c8988d663d3ee55a5f40088e9d1d3f3e71e59fc43b512efa318462891492331b', '2026-06-15 04:16:27'),
('59', 'rd_1781508018991_3oskoea', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://www.roberiodiogenes.com/', 'home', '9d1db89bd45e76f3b11425c855f889c46d67a3941c9b10f4a66044c81b96361b', '2026-06-15 04:20:19'),
('60', 'rd_1781508109718_rpw6afb', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://roberiodiogenes.com/', 'home', '542147d7f7eb8acbecac0d531e00c70638bb74a5498a32be26d1f7cc9244df17', '2026-06-15 04:21:50'),
('61', 'rd_1781508924239_a3t6427', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://www.roberiodiogenes.com/', 'home', '19185e8b12a1378730c551618a91a26c777c2a33356772bbe9ead00b25ea9eed', '2026-06-15 04:35:25'),
('62', 'rd_1781509457508_6dkcdfn', NULL, '', '', '', '', '', 'desktop', 'en-US', 'https://bing.com/', 'https://www.roberiodiogenes.com/', 'home', '8bb1b874444a5ea929e2bcc9a815fcfd65d08da8f3853c6ff6c2193c18ebe358', '2026-06-15 04:44:18'),
('63', 'rd_1781511299585_5gmz8pp', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://www.roberiodiogenes.com/', 'home', '1aadbf50024ea1c6f30577ca20266a7011d098f384a04213de2c8c52e9e610aa', '2026-06-15 05:14:59'),
('64', 'rd_1781511355779_399mkt1', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://www.roberiodiogenes.com/', 'home', 'c162971cfa63f222029849fe471d48c257ab4664a36e59df40d6132918aecee1', '2026-06-15 05:15:56'),
('65', 'rd_1781511361981_xz3rnip', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://roberiodiogenes.com/', 'home', '8543f3b4105f8c2f8c1f7bae0265aebb01bf6f208a356b19533f63eaa45c0b72', '2026-06-15 05:16:02'),
('66', 'rd_1781511382627_pykv92z', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://roberiodiogenes.com/', 'home', '1bdee6d42d1e7956df5c9d1999d14f9794471298994e9f530e75b1776bafb097', '2026-06-15 05:16:23'),
('67', 'rd_1781512131184_8o2cmpp', NULL, '', '', '', '', '', 'mobile', 'en-US', '', 'https://www.roberiodiogenes.com/', 'home', 'f2d774d74f1ebd1c8d1b159ab53206036190a3d5d71bdcb6033c36b378a7b066', '2026-06-15 05:28:51'),
('68', 'rd_1781512372401_r40njrb', NULL, '', '', '', '', '', 'mobile', 'en-US', '', 'https://roberiodiogenes.com/', 'home', '915a418716bf63c36a88b41c7afcb54189fd08c7cd114177a702620605a90d79', '2026-06-15 05:32:53'),
('69', 'rd_1781514209788_15kzkpx', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://www.roberiodiogenes.com/', 'home', 'eb756c4e470c8293c9ecf056344864875ad55f6154abae9061880d3054d133ac', '2026-06-15 06:03:30'),
('70', 'rd_1781514769249_z7obnzw', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://www.roberiodiogenes.com/', 'home', '2f7c78b34d8c7352fb35392585c5e200171fc7f44b1590daa5a19d6b89c7a50b', '2026-06-15 06:12:50'),
('71', 'rd_1781515971652_dwaf0ov', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://roberiodiogenes.com/', 'home', 'a2a424bc623a5beee7f7525cd26f641774ad407245386ecb418cba6f15ac953d', '2026-06-15 06:32:52'),
('72', 'rd_1781516207677_n8i7kyr', NULL, '', '', '', '', '', 'desktop', 'en-US', '', 'https://roberiodiogenes.com/', 'home', 'ac3f9d1ad89aaa21b91913892878b4d53691c3745e7077279bc9e4fbe74b74b5', '2026-06-15 06:36:48'),
('73', 'rd_1781519397006_0pqlwj2', NULL, '', '', '', '', '', 'mobile', 'pt-BR', 'android-app://com.google.android.googlequicksearchbox/', 'https://roberiodiogenes.com/', 'home', '5948c81a11fdaeef46d01cc72d339e61394bf14400f9d62bb907182039bb7bbd', '2026-06-15 07:29:56'),
('74', 'rd_1781519972761_jwxelh7', NULL, '', '', '', '', '', 'desktop', 'en-US', 'https://www.facebook.com/', 'https://roberiodiogenes.com/?fbclid=IwZXh0bgNhZW0CMTEAc3J0YwZhcHBfaWQMMjU2MjgxMDQwNTU4AAEeMP0Gcn2jBjqqO4cgWiXcLSMesmL1R7xmRkv1IA9dtIeowcnYQDxm2vVNMj4_aem_Ih_vB0FkwI_c4mjoTiuoSA', 'home', '78599ba64132b3d8cc93b88cc0fa757e078589d8185151de7a9801d94ece0e5f', '2026-06-15 07:39:33');

-- ------------------------------------------------------------
-- Tabela: `assinaturas`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `assinaturas`;
CREATE TABLE `assinaturas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `plano_id` int(10) unsigned NOT NULL,
  `status` enum('ativa','cancelada','expirada','pendente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendente',
  `inicio_em` datetime NOT NULL,
  `expira_em` datetime NOT NULL,
  `renovacao_auto` tinyint(1) NOT NULL DEFAULT '0',
  `gateway` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_externa` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_status` (`status`),
  KEY `idx_expira_em` (`expira_em`),
  KEY `fk_assin_plano` (`plano_id`),
  CONSTRAINT `fk_assin_plano` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`),
  CONSTRAINT `fk_assin_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `auth_log`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `auth_log`;
CREATE TABLE `auth_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_action` (`ip`,`action`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `auth_log` VALUES
('50', '179.249.8.8', 'login', '2026-06-15 03:34:40'),
('51', '179.249.8.8', 'atualizar_perfil', '2026-06-15 03:35:10');

-- ------------------------------------------------------------
-- Tabela: `avaliacoes`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `avaliacoes`;
CREATE TABLE `avaliacoes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `livro_slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estrelas` tinyint(3) unsigned NOT NULL,
  `avaliado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_aval` (`usuario_id`,`livro_slug`),
  CONSTRAINT `fk_aval_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `avaliacoes` VALUES
('1', '1', 'a-setima-lei', '5', '2026-06-12 11:06:41', '2026-06-12 11:06:41'),
('2', '14', 'a-marca-da-besta', '5', '2026-06-13 20:18:28', '2026-06-13 20:18:28');

-- ------------------------------------------------------------
-- Tabela: `bi_canal_retencao`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `bi_canal_retencao`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `bi_canal_retencao` AS select coalesce(`s`.`utm_source`,'direto') AS `canal`,coalesce(`s`.`utm_medium`,'(none)') AS `meio`,count(distinct `s`.`session_id`) AS `total_sessoes`,count(distinct (case when (`e`.`tipo_evento` = 'Leitura_Progresso') then `e`.`session_id` end)) AS `leitores_ativos`,round(avg((case when (`e`.`tipo_evento` in ('ViewContent','Tempo_Pagina')) then `e`.`tempo_permanencia` end)),0) AS `media_segundos_pagina`,round(avg((case when (`e`.`tipo_evento` = 'Leitura_Progresso') then (json_unquote(json_extract(`e`.`params`,'$.percentual')) + 0) end)),0) AS `media_percentual_leitura` from (`analytics_sessoes` `s` left join `analytics_eventos` `e` on((`e`.`session_id` = `s`.`session_id`))) where (`s`.`iniciada_em` >= (now() - interval 90 day)) group by `canal`,`meio` order by `leitores_ativos` desc;

INSERT INTO `bi_canal_retencao` VALUES
('', '', '74', '11', '653', '19');

-- ------------------------------------------------------------
-- Tabela: `bi_conteudo_engajamento`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `bi_conteudo_engajamento`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `bi_conteudo_engajamento` AS select `e`.`conteudo_slug` AS `conteudo_slug`,max(`e`.`conteudo_titulo`) AS `titulo`,count(distinct `e`.`session_id`) AS `total_visualizacoes`,round(avg(`e`.`tempo_permanencia`),0) AS `media_segundos`,max((case when (`e`.`tipo_evento` = 'Leitura_Progresso') then (json_unquote(json_extract(`e`.`params`,'$.percentual')) + 0) end)) AS `max_percentual_lido`,count((case when (`e`.`tipo_evento` = 'Download_Amostra') then 1 end)) AS `downloads_amostra`,count((case when (`e`.`tipo_evento` = 'Lead') then 1 end)) AS `leads_gerados` from `analytics_eventos` `e` where ((`e`.`registrado_em` >= (now() - interval 90 day)) and (`e`.`conteudo_slug` is not null)) group by `e`.`conteudo_slug` order by `total_visualizacoes` desc;

INSERT INTO `bi_conteudo_engajamento` VALUES
('home', 'O Quarto Passageiro | Leitor | Robério Diógenes', '36', '906', '100', '0', '0'),
('index', 'Cartas do Passado | Leitor | Robério Diógenes', '18', '599', '11', '0', '0'),
('livros', NULL, '15', '480', NULL, '0', '0'),
('blog', NULL, '4', '529', NULL, '0', '0'),
('a-ilha-do-dr-moreau-resenha', 'A Ilha do Dr. Moreau — Resenha | Diário | Robério Diógenes', '2', '16', NULL, '0', '0'),
('a-maquina-do-tempo-resenha', 'A Máquina do Tempo — Resenha | Diário | Robério Diógenes', '2', '121', NULL, '0', '0'),
('a-guerra-dos-mundos-resenha', 'A Guerra dos Mundos — Resenha | Diário | Robério Diógenes', '1', '203', NULL, '0', '0'),
('h-g-wells-guia-definitivo', 'H. G. Wells: O Homem Que Imaginou o Futuro | Diário | Robério Diógenes', '1', '39', NULL, '0', '0'),
('o-dorminhoco-resenha', 'O Dorminhoco — Resenha | Diário | Robério Diógenes', '1', '26', NULL, '0', '0'),
('o-homem-invisivel-resenha', 'O Homem Invisível — Resenha | Diário | Robério Diógenes', '1', '29', NULL, '0', '0');

-- ------------------------------------------------------------
-- Tabela: `bio_clicks`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `bio_clicks`;
CREATE TABLE `bio_clicks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `link_id` int(10) unsigned DEFAULT NULL COMMENT 'ID em bio_links (NULL = link dinâmico)',
  `link_slug` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Slug para links dinâmicos',
  `origem` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'utm_source ou referrer',
  `ip_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clicado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bc_link_id` (`link_id`),
  KEY `idx_bc_link_slug` (`link_slug`),
  KEY `idx_bc_clicado` (`clicado_em`),
  KEY `idx_bc_origem` (`origem`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `bio_config`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `bio_config`;
CREATE TABLE `bio_config` (
  `chave` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `bio_config` VALUES
('cor_acento', '#B8860B'),
('cor_fundo', '#0D0A07'),
('email', 'mailto:contato@roberiodiogenes.com'),
('foto', 'img/autor2.jpg'),
('instagram', 'https://instagram.com/diogenesroberio'),
('linkedin', 'https://linkedin.com/in/roberio-diogenes'),
('nome', 'Robério Diógenes'),
('subtitulo', 'Escritor · Literatura Brasileira'),
('telegram', 'https://t.me/5585996409818'),
('whatsapp', 'https://wa.me/5585996409818');

-- ------------------------------------------------------------
-- Tabela: `bio_eventos`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `bio_eventos`;
CREATE TABLE `bio_eventos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sessao_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `evento` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `genero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estrategia` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rede_social` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_id` int(10) unsigned DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referrer` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_evento` (`evento`),
  KEY `idx_sessao` (`sessao_id`),
  KEY `idx_data` (`criado_em`)
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `bio_eventos` VALUES
('1', 'mqdxay0mcvj4u2z2c2g', 'pagina_aberta', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 12:12:29'),
('2', 'mqdxay0mcvj4u2z2c2g', 'card_aberto', 'romance', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 12:12:32'),
('3', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_50pct', 'romance', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 12:12:36'),
('4', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_100pct', 'romance', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 12:12:36'),
('5', 'mqdxay0mcvj4u2z2c2g', 'link_biblioteca', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 12:12:43'),
('6', 'mqdxay0mcvj4u2z2c2g', 'link_diario', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 12:12:48'),
('7', 'mqdxay0mcvj4u2z2c2g', 'bau_aberto', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 12:12:53'),
('8', 'mqdxay0mcvj4u2z2c2g', 'bau_gaveta', NULL, 'contos-ineditos', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 12:13:02'),
('9', 'mqdxay0mcvj4u2z2c2g', 'bau_aberto', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 12:13:16'),
('10', 'mqdxay0mcvj4u2z2c2g', 'bau_gaveta', NULL, 'playlist', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 12:13:18'),
('11', 'mqdxay0mcvj4u2z2c2g', 'bau_aberto', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 12:13:28'),
('12', 'mqdxay0mcvj4u2z2c2g', 'bau_gaveta', NULL, 'carta-do-autor', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 12:13:33'),
('13', 'mqdxay0mcvj4u2z2c2g', 'card_aberto', 'drama', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 12:14:20'),
('14', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_50pct', 'drama', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 12:14:21'),
('15', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_100pct', 'drama', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 12:14:21'),
('16', 'mqdxay0mcvj4u2z2c2g', 'card_aberto', 'drama', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 12:16:58'),
('17', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_50pct', 'drama', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 12:16:58'),
('18', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_100pct', 'drama', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 12:16:58'),
('19', 'mqdxay0mcvj4u2z2c2g', 'bau_aberto', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 12:17:11'),
('20', 'mqdxay0mcvj4u2z2c2g', 'pagina_aberta', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:42:07'),
('21', 'mqdxay0mcvj4u2z2c2g', 'card_aberto', 'drama', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:42:10'),
('22', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_50pct', 'drama', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:42:11'),
('23', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_100pct', 'drama', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:42:11'),
('24', 'mqdxay0mcvj4u2z2c2g', 'click_ler_final_site', 'drama', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:42:14'),
('25', 'mqdxay0mcvj4u2z2c2g', 'card_aberto', 'romance', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:42:29'),
('26', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_50pct', 'romance', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:42:29'),
('27', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_100pct', 'romance', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:42:29'),
('28', 'mqdxay0mcvj4u2z2c2g', 'click_ler_final_site', 'romance', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:42:32'),
('29', 'mqdxay0mcvj4u2z2c2g', 'card_aberto', 'terror', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:42:44'),
('30', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_50pct', 'terror', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:42:45'),
('31', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_100pct', 'terror', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:42:45'),
('32', 'mqdxay0mcvj4u2z2c2g', 'click_ler_final_site', 'terror', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:42:49'),
('33', 'mqdxay0mcvj4u2z2c2g', 'card_aberto', 'autoajuda', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:43:05'),
('34', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_50pct', 'autoajuda', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:43:06'),
('35', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_100pct', 'autoajuda', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:43:06'),
('36', 'mqdxay0mcvj4u2z2c2g', 'click_ler_final_site', 'autoajuda', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:43:07'),
('37', 'mqdxay0mcvj4u2z2c2g', 'bau_aberto', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:43:17'),
('38', 'mqdxay0mcvj4u2z2c2g', 'bau_gaveta', NULL, 'contos-ineditos', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:43:18'),
('39', 'mqdxay0mcvj4u2z2c2g', 'email_capturado', NULL, 'contos-ineditos', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:43:21'),
('40', 'mqdxay0mcvj4u2z2c2g', 'bau_aberto', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:43:25'),
('41', 'mqdxay0mcvj4u2z2c2g', 'bau_gaveta', NULL, 'playlist', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:43:25'),
('42', 'mqdxay0mcvj4u2z2c2g', 'email_capturado', NULL, 'playlist', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:43:28'),
('43', 'mqdxay0mcvj4u2z2c2g', 'bau_aberto', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:43:31'),
('44', 'mqdxay0mcvj4u2z2c2g', 'bau_gaveta', NULL, 'carta-do-autor', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:43:32'),
('45', 'mqdxay0mcvj4u2z2c2g', 'email_capturado', NULL, 'carta-do-autor', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:43:34'),
('46', 'mqdxay0mcvj4u2z2c2g', 'pagina_aberta', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:44:07'),
('47', 'mqdxay0mcvj4u2z2c2g', 'pagina_aberta', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:44:32'),
('48', 'mqdxay0mcvj4u2z2c2g', 'pagina_aberta', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 17:44:42'),
('49', 'mqdxay0mcvj4u2z2c2g', 'pagina_aberta', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:29:29'),
('50', 'mqdxay0mcvj4u2z2c2g', 'bau_aberto', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:29:31'),
('51', 'mqdxay0mcvj4u2z2c2g', 'bau_gaveta', NULL, 'contos-ineditos', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:29:32'),
('52', 'mqdxay0mcvj4u2z2c2g', 'email_capturado', NULL, 'contos-ineditos', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:29:38'),
('53', 'mqdxay0mcvj4u2z2c2g', 'bau_aberto', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:29:42'),
('54', 'mqdxay0mcvj4u2z2c2g', 'bau_gaveta', NULL, 'playlist', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:29:43'),
('55', 'mqdxay0mcvj4u2z2c2g', 'email_capturado', NULL, 'playlist', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:29:45'),
('56', 'mqdxay0mcvj4u2z2c2g', 'bau_aberto', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:29:47'),
('57', 'mqdxay0mcvj4u2z2c2g', 'bau_gaveta', NULL, 'carta-do-autor', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:29:48'),
('58', 'mqdxay0mcvj4u2z2c2g', 'email_capturado', NULL, 'carta-do-autor', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:29:51'),
('59', 'mqdxay0mcvj4u2z2c2g', 'pagina_aberta', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:32:41'),
('60', 'mqefi45c9hitpjmw8zs', 'pagina_aberta', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:41:55'),
('61', 'mqefi45c9hitpjmw8zs', 'bau_aberto', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:43:03'),
('62', 'mqefi45c9hitpjmw8zs', 'bau_gaveta', NULL, 'contos-ineditos', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:43:30'),
('63', 'mqefi45c9hitpjmw8zs', 'email_capturado', NULL, 'contos-ineditos', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:43:56'),
('64', 'mqefi45c9hitpjmw8zs', 'card_aberto', 'terror', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:44:02'),
('65', 'mqefi45c9hitpjmw8zs', 'conto_lido_50pct', 'terror', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:44:08'),
('66', 'mqefi45c9hitpjmw8zs', 'conto_lido_100pct', 'terror', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:44:10'),
('67', 'mqefi45c9hitpjmw8zs', 'click_ler_final_site', 'terror', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:44:13'),
('68', 'mqefi45c9hitpjmw8zs', 'pagina_aberta', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:45:06'),
('69', 'mqefi45c9hitpjmw8zs', 'card_aberto', 'terror', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:45:09'),
('70', 'mqefi45c9hitpjmw8zs', 'conto_lido_50pct', 'terror', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:45:10'),
('71', 'mqefi45c9hitpjmw8zs', 'conto_lido_100pct', 'terror', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:45:10'),
('72', 'mqefi45c9hitpjmw8zs', 'click_ler_final_site', 'terror', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-14 20:45:13'),
('73', 'mqdxay0mcvj4u2z2c2g', 'pagina_aberta', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:00:04'),
('74', 'mqdxay0mcvj4u2z2c2g', 'card_aberto', 'drama', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:00:13'),
('75', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_50pct', 'drama', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:00:14'),
('76', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_100pct', 'drama', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:00:14'),
('77', 'mqdxay0mcvj4u2z2c2g', 'pagina_aberta', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:00:25'),
('78', 'mqdxay0mcvj4u2z2c2g', 'pagina_aberta', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:00:28'),
('79', 'mqdxay0mcvj4u2z2c2g', 'bau_aberto', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:00:31'),
('80', 'mqdxay0mcvj4u2z2c2g', 'bau_gaveta', NULL, 'contos-ineditos', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:00:32'),
('81', 'mqdxay0mcvj4u2z2c2g', 'email_capturado', NULL, 'contos-ineditos', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:00:36'),
('82', 'mqdxay0mcvj4u2z2c2g', 'bau_aberto', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:00:38'),
('83', 'mqdxay0mcvj4u2z2c2g', 'bau_gaveta', NULL, 'playlist', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:00:39'),
('84', 'mqdxay0mcvj4u2z2c2g', 'email_capturado', NULL, 'playlist', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:00:43'),
('85', 'mqdxay0mcvj4u2z2c2g', 'bau_aberto', NULL, NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:00:44'),
('86', 'mqdxay0mcvj4u2z2c2g', 'bau_gaveta', NULL, 'carta-do-autor', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:00:45'),
('87', 'mqdxay0mcvj4u2z2c2g', 'email_capturado', NULL, 'carta-do-autor', NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:00:47'),
('88', 'mqdxay0mcvj4u2z2c2g', 'card_aberto', 'drama', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:00:49'),
('89', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_50pct', 'drama', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:00:50'),
('90', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_100pct', 'drama', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:00:50'),
('91', 'mqdxay0mcvj4u2z2c2g', 'compartilhamento', 'drama', NULL, 'instagram', NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:01:06'),
('92', 'mqdxay0mcvj4u2z2c2g', 'embaixador_gerado', 'drama', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:01:07'),
('93', 'mqdxay0mcvj4u2z2c2g', 'card_aberto', 'romance', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:01:58'),
('94', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_50pct', 'romance', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:01:59'),
('95', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_100pct', 'romance', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:01:59'),
('96', 'mqdxay0mcvj4u2z2c2g', 'compartilhamento', 'romance', NULL, 'whatsapp', NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:02:04'),
('97', 'mqdxay0mcvj4u2z2c2g', 'embaixador_gerado', 'romance', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:02:05'),
('98', 'mqdxay0mcvj4u2z2c2g', 'card_aberto', 'terror', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:02:44'),
('99', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_50pct', 'terror', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:02:45'),
('100', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_100pct', 'terror', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:02:45'),
('101', 'mqdxay0mcvj4u2z2c2g', 'compartilhamento', 'terror', NULL, 'instagram', NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:02:52'),
('102', 'mqdxay0mcvj4u2z2c2g', 'embaixador_gerado', 'terror', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:02:52'),
('103', 'mqdxay0mcvj4u2z2c2g', 'card_aberto', 'autoajuda', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:03:08'),
('104', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_50pct', 'autoajuda', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:03:09'),
('105', 'mqdxay0mcvj4u2z2c2g', 'conto_lido_100pct', 'autoajuda', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:03:09'),
('106', 'mqdxay0mcvj4u2z2c2g', 'compartilhamento', 'autoajuda', NULL, 'instagram', NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:03:12'),
('107', 'mqdxay0mcvj4u2z2c2g', 'embaixador_gerado', 'autoajuda', NULL, NULL, NULL, '170.82.213.33', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 00:03:13'),
('108', 'mqemx9mtpz27sorwdgh', 'pagina_aberta', NULL, NULL, NULL, NULL, '31.13.115.9', 'https://roberiodiogenes.com/bio.html?fbclid=IwZXh0bgNhZW0CMTEAc3J0YwZhcHBfaWQMMjU2MjgxMDQwNTU4AAEekcQzACOofbDHaK3boyBaqjTOfTcU7jwmWdkIrs8cRMgtqIdxz--R-v7RgVE_aem_gEkAieX2qYgWEIeY5Hvu1A', NULL, '2026-06-15 00:09:42'),
('109', 'mqemx9lxtbkv3xqgloi', 'pagina_aberta', NULL, NULL, NULL, NULL, '31.13.127.79', 'https://roberiodiogenes.com/bio.html?fbclid=IwZXh0bgNhZW0CMTEAc3J0YwZhcHBfaWQMMjU2MjgxMDQwNTU4AAEemvT9s0T9XeBQXcQPOaF8RAzHnFdUyfi07UTBMf5uqy0qXN7gLD8SKLudrio_aem_gpmPSSV6G8Q_wYl-ERUo2Q', NULL, '2026-06-15 00:09:42'),
('110', 'mqemz7fc89zd865wyqa', 'pagina_aberta', NULL, NULL, NULL, NULL, '179.249.8.24', 'https://roberiodiogenes.com/bio.html?utm_source=ig&utm_medium=social&utm_content=link_in_bio&fbclid=PAZXh0bgNhZW0CMTEAc3J0YwZhcHBfaWQPNTY3MDY3MzQzMzUyNDI3AAGnSQjNkL928O-63lGdUhe_B-iUuX8FmWTGe-o2F9cXXVqesm4h9nW10Kh2MT0_aem_cqRC3WZunLK_QWAqZDllog', NULL, '2026-06-15 00:11:16'),
('111', 'mqemz7fc89zd865wyqa', 'pagina_aberta', NULL, NULL, NULL, NULL, '179.249.7.252', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 07:33:43'),
('112', 'mqemz7fc89zd865wyqa', 'card_aberto', 'terror', NULL, NULL, NULL, '179.249.7.252', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 07:34:11'),
('113', 'mqemz7fc89zd865wyqa', 'conto_lido_50pct', 'terror', NULL, NULL, NULL, '179.249.7.252', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 07:34:19'),
('114', 'mqemz7fc89zd865wyqa', 'conto_lido_100pct', 'terror', NULL, NULL, NULL, '179.249.7.252', 'https://roberiodiogenes.com/bio.html', NULL, '2026-06-15 07:34:20');

-- ------------------------------------------------------------
-- Tabela: `bio_links`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `bio_links`;
CREATE TABLE `bio_links` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitulo` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Classe Font Awesome, ex: fa-book',
  `tipo` enum('link','destaque') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'link',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `ordem` smallint(5) unsigned NOT NULL DEFAULT '99',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ativo_ordem` (`ativo`,`ordem`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `bio_links` VALUES
('1', 'Biblioteca de Obras', 'Romances, contos e ficção literária', '/livros.html', 'fa-book', 'destaque', '1', '1', '2026-06-11 21:46:13'),
('2', 'Diário do Escritor', 'Reflexões, bastidores e processo criativo', '/blog.html', 'fa-pen-nib', 'link', '1', '2', '2026-06-11 21:46:13'),
('3', 'Leitor Online', 'Leia no navegador, sem app', '/leitor/index.html', 'fa-book-open', 'link', '1', '3', '2026-06-11 21:46:13'),
('4', 'Planos de Assinatura', 'Acesso completo à biblioteca', '/pagamento/assinatura.html', 'fa-crown', 'link', '1', '4', '2026-06-11 21:46:13'),
('5', 'Sobre o Autor', 'A história por trás das histórias', '/autor.html', 'fa-user-pen', 'link', '1', '5', '2026-06-11 21:46:13'),
('6', 'Presentear alguém', 'Dê um livro de presente com 20% off', '/presentear.html', 'fa-gift', 'link', '1', '6', '2026-06-11 21:46:13'),
('7', 'Newsletter Gratuita', 'Novos posts e capítulos exclusivos no e-mail', '/blog.html#newsletter', 'fa-envelope-open-text', 'link', '1', '7', '2026-06-11 21:46:13'),
('8', 'Contato para Imprensa', 'Entrevistas, resenhas e parcerias', '/contato.html?assunto=imprensa', 'fa-newspaper', 'link', '1', '8', '2026-06-11 21:46:13'),
('9', 'Biblioteca de Obras', 'Romances, contos e ficção literária', '/livros.html', 'fa-book', 'destaque', '1', '1', '2026-06-11 21:46:49'),
('10', 'Diário do Escritor', 'Reflexões, bastidores e processo criativo', '/blog.html', 'fa-pen-nib', 'link', '1', '2', '2026-06-11 21:46:49'),
('11', 'Leitor Online', 'Leia no navegador, sem app', '/leitor/index.html', 'fa-book-open', 'link', '1', '3', '2026-06-11 21:46:49'),
('12', 'Planos de Assinatura', 'Acesso completo à biblioteca', '/pagamento/assinatura.html', 'fa-crown', 'link', '1', '4', '2026-06-11 21:46:49'),
('13', 'Sobre o Autor', 'A história por trás das histórias', '/autor.html', 'fa-user-pen', 'link', '1', '5', '2026-06-11 21:46:49'),
('14', 'Presentear alguém', 'Dê um livro de presente com 20% off', '/presentear.html', 'fa-gift', 'link', '1', '6', '2026-06-11 21:46:49'),
('15', 'Newsletter Gratuita', 'Novos posts e capítulos exclusivos no e-mail', '/blog.html#newsletter', 'fa-envelope-open-text', 'link', '1', '7', '2026-06-11 21:46:49'),
('16', 'Contato para Imprensa', 'Entrevistas, resenhas e parcerias', '/contato.html?assunto=imprensa', 'fa-newspaper', 'link', '1', '8', '2026-06-11 21:46:49'),
('17', 'Biblioteca de Obras', 'Romances, contos e ficção literária', '/livros.html', 'fa-book', 'destaque', '1', '1', '2026-06-11 21:50:35'),
('18', 'Diário do Escritor', 'Reflexões, bastidores e processo criativo', '/blog.html', 'fa-pen-nib', 'link', '1', '2', '2026-06-11 21:50:35'),
('19', 'Leitor Online', 'Leia no navegador, sem app', '/leitor/index.html', 'fa-book-open', 'link', '1', '3', '2026-06-11 21:50:35'),
('20', 'Planos de Assinatura', 'Acesso completo à biblioteca', '/pagamento/assinatura.html', 'fa-crown', 'link', '1', '4', '2026-06-11 21:50:35'),
('21', 'Sobre o Autor', 'A história por trás das histórias', '/autor.html', 'fa-user-pen', 'link', '1', '5', '2026-06-11 21:50:35'),
('22', 'Presentear alguém', 'Dê um livro de presente com 20% off', '/presentear.html', 'fa-gift', 'link', '1', '6', '2026-06-11 21:50:35'),
('23', 'Newsletter Gratuita', 'Novos posts e capítulos exclusivos no e-mail', '/blog.html#newsletter', 'fa-envelope-open-text', 'link', '1', '7', '2026-06-11 21:50:35'),
('24', 'Contato para Imprensa', 'Entrevistas, resenhas e parcerias', '/contato.html?assunto=imprensa', 'fa-newspaper', 'link', '1', '8', '2026-06-11 21:50:35');

-- ------------------------------------------------------------
-- Tabela: `campanhas`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `campanhas`;
CREATE TABLE `campanhas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('newsletter','lancamento','promocao','reengajamento','boas_vindas','recompensa','destaque','push','outro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'newsletter',
  `segmento` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'todos',
  `assunto_email` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `corpo_html` longtext COLLATE utf8mb4_unicode_ci,
  `corpo_texto` text COLLATE utf8mb4_unicode_ci,
  `agendado_para` datetime DEFAULT NULL,
  `status` enum('rascunho','agendada','enviando','enviada','cancelada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rascunho',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_criado_em` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `campanhas_envios`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `campanhas_envios`;
CREATE TABLE `campanhas_envios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `campanha_id` int(10) unsigned NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('enviado','falhou','descadastrado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'enviado',
  `enviado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_campanha_id` (`campanha_id`),
  KEY `idx_email` (`email`),
  CONSTRAINT `fk_cenv_campanha` FOREIGN KEY (`campanha_id`) REFERENCES `campanhas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `carrinhos`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `carrinhos`;
CREATE TABLE `carrinhos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `itens` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'JSON com os itens do carrinho',
  `em_checkout` tinyint(1) NOT NULL DEFAULT '0',
  `lembrete_env` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'E-mail de abandono já enviado',
  `lembrete_em` datetime DEFAULT NULL COMMENT 'Quando o lembrete foi enviado',
  `checkout_em` datetime DEFAULT NULL,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario` (`usuario_id`),
  KEY `idx_carr_lembrete` (`lembrete_env`,`atualizado_em`),
  CONSTRAINT `fk_carr_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `carrinhos` VALUES
('1', '14', '[{\"slug\":\"a-marca-da-besta\",\"titulo\":\"A Marca da Besta\",\"preco\":1.9899999999999999911182158029987476766109466552734375,\"capa\":\"img\\/a-marca-da-besta-capa.jpg\"}]', '0', '1', '2026-06-14 08:32:24', '2026-06-13 20:21:37', '2026-06-14 08:32:24'),
('2', '1', '[{\"slug\":\"a-marca-da-besta\",\"titulo\":\"A Marca da Besta\",\"preco\":5,\"capa\":\"img\\/a-marca-da-besta-capa.jpg\"}]', '0', '1', '2026-06-14 08:32:24', '2026-06-13 20:53:26', '2026-06-14 08:32:24');

-- ------------------------------------------------------------
-- Tabela: `clusters`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `clusters`;
CREATE TABLE `clusters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `imagem_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cor` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#B8860B' COMMENT 'Cor principal do cluster (hex)',
  `pilar_slug` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Slug do post pilar deste cluster',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `clusters` VALUES
('1', 'hgwells', 'H. G. Wells: O Pai da Ficção Científica', 'Uma série completa sobre a obra de Herbert George Wells — do guia definitivo ao autor às resenhas aprofundadas de cada um de seus romances fundadores.', 'img/posts/h-g-wells-guia-definitivo.webp', '#4a3728', NULL, '1', '2026-06-11 21:46:13');

-- ------------------------------------------------------------
-- Tabela: `comentario_curtidas`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `comentario_curtidas`;
CREATE TABLE `comentario_curtidas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comentario_id` int(10) unsigned NOT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_curtida` (`comentario_id`,`usuario_id`),
  KEY `idx_cc_usuario` (`usuario_id`),
  CONSTRAINT `fk_cc_comentario` FOREIGN KEY (`comentario_id`) REFERENCES `comentarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cc_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `comentarios`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `comentarios`;
CREATE TABLE `comentarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned DEFAULT NULL COMMENT 'ID do comentário pai (respostas)',
  `usuario_id` int(10) unsigned DEFAULT NULL,
  `referencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Slug do post ou livro',
  `tipo` enum('livro','blog') COLLATE utf8mb4_unicode_ci DEFAULT 'livro',
  `livro_slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Mantido por compatibilidade',
  `nome` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `leu` enum('sim','cap','nao','') COLLATE utf8mb4_unicode_ci DEFAULT '',
  `texto` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `curtidas_count` int(10) unsigned NOT NULL DEFAULT '0',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `aprovado` tinyint(1) NOT NULL DEFAULT '1',
  `flagged` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = conteúdo suspeito',
  `flag_motivo` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SHA256 do IP (LGPD)',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_livro_aprovado` (`livro_slug`,`aprovado`),
  KEY `idx_referencia_tipo` (`referencia`,`tipo`,`aprovado`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_flagged` (`flagged`),
  CONSTRAINT `fk_com_parent` FOREIGN KEY (`parent_id`) REFERENCES `comentarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `comentarios_flags_log`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `comentarios_flags_log`;
CREATE TABLE `comentarios_flags_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comentario_id` int(10) unsigned NOT NULL,
  `usuario_id` int(10) unsigned DEFAULT NULL,
  `usuario_nome` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SHA256 — LGPD',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pais` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `texto_original` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `motivo_flag` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `palavras_detectadas` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referencia_slug` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acao_tomada` enum('pendente','mantido','removido') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendente',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `revisado_em` datetime DEFAULT NULL,
  `revisado_por` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cfl_comentario` (`comentario_id`),
  KEY `idx_cfl_acao` (`acao_tomada`),
  KEY `idx_cfl_data` (`criado_em`),
  CONSTRAINT `fk_cfl_comentario` FOREIGN KEY (`comentario_id`) REFERENCES `comentarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `compras`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `compras`;
CREATE TABLE `compras` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `livro_slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `preco_pago` decimal(8,2) NOT NULL DEFAULT '0.00',
  `status` enum('aprovada','pendente','cancelada','reembolsada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendente',
  `gateway` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_externa` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comprado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_compra` (`usuario_id`,`livro_slug`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_livro_slug` (`livro_slug`),
  KEY `idx_status` (`status`),
  KEY `idx_comprado_em` (`comprado_em`),
  CONSTRAINT `fk_compra_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `compras` VALUES
('1', '14', 'a-marca-da-besta', '1.99', 'pendente', 'mercadopago', 'carrinho_14_1781392896', '2026-06-13 20:21:37', '2026-06-13 20:21:37'),
('2', '1', 'a-marca-da-besta', '1.99', 'pendente', 'mercadopago', 'carrinho_1_1781394805', '2026-06-13 20:49:40', '2026-06-13 20:53:26');

-- ------------------------------------------------------------
-- Tabela: `configuracoes`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `configuracoes`;
CREATE TABLE `configuracoes` (
  `chave` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci,
  `tipo` enum('string','boolean','integer','json') COLLATE utf8mb4_unicode_ci DEFAULT 'string',
  `grupo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'geral',
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `configuracoes` VALUES
('analytics_id', '', 'string', 'seo'),
('email_admin', '', 'string', 'email'),
('email_nome_remetente', 'Robério Diógenes', 'string', 'email'),
('email_remetente', '', 'string', 'email'),
('mensagem_manutencao', 'Site em manutenção. Voltamos em breve.', 'string', 'acesso'),
('modo_manutencao', '0', 'boolean', 'acesso'),
('og_image_padrao', '', 'string', 'seo'),
('permitir_cadastros', '1', 'boolean', 'acesso'),
('pixel_facebook', '', 'string', 'seo'),
('site_copyright', '', 'string', 'site'),
('site_nome', 'Robério Diógenes', 'string', 'site'),
('site_slogan', 'Escritor Independente', 'string', 'site'),
('smtp_criptografia', 'tls', 'string', 'email'),
('smtp_host', '', 'string', 'email'),
('smtp_porta', '587', 'integer', 'email'),
('smtp_senha', '', 'string', 'email'),
('smtp_usuario', '', 'string', 'email'),
('social_facebook', '', 'string', 'social'),
('social_instagram', '', 'string', 'social'),
('social_linkedin', '', 'string', 'social'),
('social_tiktok', '', 'string', 'social'),
('social_twitter', '', 'string', 'social'),
('social_youtube', '', 'string', 'social'),
('tag_manager_id', '', 'string', 'seo'),
('upload_formatos_doc', 'pdf,epub', 'string', 'uploads'),
('upload_formatos_imagem', 'jpg,jpeg,png,webp', 'string', 'uploads'),
('upload_tamanho_max_kb', '2048', 'integer', 'uploads');

-- ------------------------------------------------------------
-- Tabela: `contato`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `contato`;
CREATE TABLE `contato` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assunto` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mensagem` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lida` tinyint(1) NOT NULL DEFAULT '0',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `contato` VALUES
('1', 'Francisco Robério Diógenes da Costa', 'roberiodiogenes.frdc@gmail.com', 'leitor', 'Oi Robério \nEstou testando o envio de mensagens para você;', '170.82.213.33', '0', '2026-06-12 16:20:22');

-- ------------------------------------------------------------
-- Tabela: `cupons`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `cupons`;
CREATE TABLE `cupons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('percentual','fixo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percentual',
  `valor` decimal(8,2) NOT NULL,
  `usos_max` smallint(5) unsigned DEFAULT NULL COMMENT 'NULL = ilimitado',
  `usos_atuais` smallint(5) unsigned NOT NULL DEFAULT '0',
  `valido_ate` datetime DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_codigo` (`codigo`),
  KEY `idx_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `curtidas`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `curtidas`;
CREATE TABLE `curtidas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_slug` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_id` int(10) unsigned DEFAULT NULL,
  `ip_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SHA256 do IP (LGPD)',
  `curtido_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_curtida_usuario` (`post_slug`,`usuario_id`),
  KEY `idx_post_slug` (`post_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `downloads`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `downloads`;
CREATE TABLE `downloads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `livro_id` int(10) unsigned NOT NULL,
  `tipo_arquivo` enum('pdf','epub','mobi') COLLATE utf8mb4_unicode_ci DEFAULT 'pdf',
  `data_download` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_download` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_id` (`usuario_id`),
  CONSTRAINT `fk_dl2_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `downloads_log`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `downloads_log`;
CREATE TABLE `downloads_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `livro_slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `formato` enum('pdf','epub') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pdf',
  `arquivo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `baixado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_livro` (`livro_slug`),
  CONSTRAINT `fk_dl_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `downloads_log` VALUES
('1', '1', 'a-setima-lei', 'epub', 'A-setima-lei-capitulo-1.epub', '170.82.213.33', '2026-06-12 17:08:59'),
('2', '1', 'a-setima-lei', 'epub', 'A-setima-lei-capitulo-1.epub', '170.82.213.33', '2026-06-12 17:09:27'),
('3', '1', 'caminhos-de-outono', 'epub', 'caminhos-de-outono-capitulo-1.epub', '170.82.213.33', '2026-06-12 17:10:26'),
('4', '1', 'a-marca-da-besta', 'epub', 'a-marca-da-besta-capitulo-1.epub', '170.82.213.33', '2026-06-12 17:31:56'),
('5', '1', 'lumen', 'epub', 'lumen-capitulo-1.epub', '170.82.213.33', '2026-06-12 18:02:52'),
('6', '10', 'a-setima-lei', 'epub', 'A-setima-lei-capitulo-1.epub', '170.82.213.33', '2026-06-13 13:26:46'),
('7', '10', 'a-setima-lei', 'epub', 'A-setima-lei-capitulo-1.epub', '170.82.213.33', '2026-06-13 13:38:36'),
('8', '10', 'a-setima-lei', 'epub', 'A-setima-lei-capitulo-1.epub', '170.82.213.33', '2026-06-13 13:38:49'),
('9', '10', 'a-setima-lei', 'epub', 'A-setima-lei-capitulo-1.epub', '170.82.213.33', '2026-06-13 13:54:44'),
('10', '10', 'a-setima-lei', 'epub', 'A-setima-lei-capitulo-1.epub', '170.82.213.33', '2026-06-13 13:55:04'),
('11', '10', 'a-setima-lei', 'epub', 'A-setima-lei-capitulo-1.epub', '170.82.213.33', '2026-06-13 13:56:42'),
('12', '10', 'cartas-do-passado', 'epub', 'cartas-do-passado-capitulo-1.epub', '170.82.213.33', '2026-06-13 13:57:06'),
('13', '10', 'a-setima-lei', 'epub', 'A-setima-lei-capitulo-1.epub', '170.82.213.33', '2026-06-13 13:58:36'),
('14', '10', 'a-setima-lei', 'epub', 'A-setima-lei-capitulo-1.epub', '170.82.213.33', '2026-06-13 14:04:21'),
('15', '10', 'a-setima-lei', 'epub', 'A-setima-lei-capitulo-1.epub', '170.82.213.33', '2026-06-13 14:15:40'),
('16', '10', 'a-setima-lei', 'epub', 'A-setima-lei-capitulo-1.epub', '170.82.213.33', '2026-06-13 14:31:46'),
('17', '10', 'cartas-do-passado', 'epub', 'cartas-do-passado-capitulo-1.epub', '170.82.213.33', '2026-06-13 14:32:12'),
('18', '10', 'a-setima-lei', 'epub', 'A-setima-lei-capitulo-1.epub', '170.82.213.33', '2026-06-13 14:33:17'),
('21', '13', 'cartas-do-passado', 'epub', 'cartas-do-passado-capitulo-1.epub', '170.82.213.33', '2026-06-13 15:57:48'),
('22', '14', 'cartas-do-passado', 'epub', 'cartas-do-passado-capitulo-1.epub', '170.82.213.33', '2026-06-13 16:13:00'),
('23', '14', 'cartas-do-passado', 'epub', 'cartas-do-passado-capitulo-1.epub', '170.82.213.33', '2026-06-13 20:19:15'),
('24', '1', 'o-farol-do-afogado', 'epub', 'o-farol-do-afogado.epub', '170.82.213.33', '2026-06-14 09:11:11'),
('25', '1', 'o-farol-do-afogado', 'epub', 'o-farol-do-afogado.epub', '170.82.213.33', '2026-06-14 09:14:40'),
('26', '1', 'a-setima-lei', 'epub', 'A-setima-lei-capitulo-1.epub', '170.82.213.33', '2026-06-14 12:17:57'),
('27', '1', 'o-labirinto-dos-espelhos', 'epub', 'o-labirinto-dos-espelhos.epub', '170.82.213.33', '2026-06-14 16:57:46'),
('28', '1', 'a-penultima-pagina', 'epub', 'a-penultima-pagina.epub', '170.82.213.33', '2026-06-14 17:04:15'),
('29', '1', 'a-penultima-pagina', 'epub', 'a-penultima-pagina.epub', '170.82.213.33', '2026-06-14 17:09:44'),
('30', '1', 'o-peso-do-horizonte', 'epub', 'o-peso-do-horizonte.epub', '170.82.213.33', '2026-06-14 17:10:40'),
('31', '1', 'o-quarto-passageiro', 'epub', 'o-quarto-passageiro.epub', '170.82.213.33', '2026-06-14 17:14:54'),
('32', '1', 'o-peso-do-horizonte', 'epub', 'o-peso-do-horizonte.epub', '170.82.213.33', '2026-06-14 17:15:21'),
('33', '1', 'o-peso-do-horizonte', 'epub', 'o-peso-do-horizonte.epub', '170.82.213.33', '2026-06-14 17:33:32'),
('34', '1', 'o-peso-do-horizonte', 'epub', 'o-peso-do-horizonte.epub', '170.82.213.33', '2026-06-14 17:42:14'),
('36', '1', 'a-penultima-pagina', 'epub', 'a-penultima-pagina.epub', '170.82.213.33', '2026-06-14 17:42:33'),
('38', '1', 'o-quarto-passageiro', 'epub', 'o-quarto-passageiro.epub', '170.82.213.33', '2026-06-14 17:42:49'),
('40', '1', 'o-labirinto-dos-espelhos', 'epub', 'o-labirinto-dos-espelhos.epub', '170.82.213.33', '2026-06-14 17:43:08'),
('42', '1', 'o-quarto-passageiro', 'epub', 'o-quarto-passageiro.epub', '170.82.213.33', '2026-06-14 20:45:15');

-- ------------------------------------------------------------
-- Tabela: `email_cliques`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `email_cliques`;
CREATE TABLE `email_cliques` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `acao` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ex: baixar_conto, visitar_biblioteca',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clicado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_acao` (`acao`),
  KEY `idx_data` (`clicado_em`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `email_cliques` VALUES
('1', '4', 'visitar_biblioteca', '170.82.213.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-12 22:50:24'),
('2', '5', 'baixar_conto', '170.82.213.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-13 06:37:42'),
('3', '5', 'visitar_biblioteca', '170.82.213.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-13 06:39:34'),
('4', '6', 'baixar_conto', '170.82.213.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-13 09:15:38'),
('5', '6', 'visitar_biblioteca', '170.82.213.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-13 09:16:31'),
('6', '7', 'baixar_conto', '170.82.213.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-13 13:00:32'),
('7', '8', 'baixar_conto', '170.82.213.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-13 13:10:27'),
('8', '8', 'visitar_biblioteca', '170.82.213.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-13 13:12:06'),
('9', '9', 'baixar_conto', '170.82.213.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-13 13:20:29'),
('10', '9', 'baixar_conto', '170.82.213.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-13 13:21:05'),
('11', '14', 'baixar_conto', '170.82.213.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-13 16:07:39');

-- ------------------------------------------------------------
-- Tabela: `embaixadores`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `embaixadores`;
CREATE TABLE `embaixadores` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `genero` varchar(20) DEFAULT NULL,
  `sessao_id` varchar(64) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sessao` (`sessao_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

INSERT INTO `embaixadores` VALUES
('1', 'drama', 'ck9eg7o3n8mqemm84j', '170.82.213.33', '2026-06-15 00:01:06');

-- ------------------------------------------------------------
-- Tabela: `enquetes`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `enquetes`;
CREATE TABLE `enquetes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `multipla` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = múltipla escolha',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `enquetes_opcoes`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `enquetes_opcoes`;
CREATE TABLE `enquetes_opcoes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `enquete_id` int(10) unsigned NOT NULL,
  `texto` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ordem` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_enquete` (`enquete_id`),
  CONSTRAINT `fk_eopc_enquete` FOREIGN KEY (`enquete_id`) REFERENCES `enquetes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `enquetes_respostas`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `enquetes_respostas`;
CREATE TABLE `enquetes_respostas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `enquete_id` int(10) unsigned NOT NULL,
  `opcao_id` int(10) unsigned NOT NULL,
  `usuario_id` int(10) unsigned DEFAULT NULL,
  `ip_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SHA256 do IP (LGPD)',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_er_enquete` (`enquete_id`),
  KEY `idx_er_opcao` (`opcao_id`),
  CONSTRAINT `fk_er_enquete` FOREIGN KEY (`enquete_id`) REFERENCES `enquetes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_er_opcao` FOREIGN KEY (`opcao_id`) REFERENCES `enquetes_opcoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `favoritos`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `favoritos`;
CREATE TABLE `favoritos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `livro_slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `adicionado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fav` (`usuario_id`,`livro_slug`),
  CONSTRAINT `fk_fav_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `favoritos` VALUES
('1', '1', 'a-setima-lei', '2026-06-12 16:19:08');

-- ------------------------------------------------------------
-- Tabela: `leitor_anotacoes`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `leitor_anotacoes`;
CREATE TABLE `leitor_anotacoes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `livro_slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capitulo` smallint(5) unsigned NOT NULL DEFAULT '1',
  `texto` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `cor` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#FFD700',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_livro` (`usuario_id`,`livro_slug`),
  CONSTRAINT `fk_anot_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `leitor_anotacoes` VALUES
('1', '10', 'a-setima-lei', '0', 'Ler com cuidado', '#7EC8E0', '2026-06-13 13:55:50', '2026-06-13 13:55:50');

-- ------------------------------------------------------------
-- Tabela: `leitor_conquistas`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `leitor_conquistas`;
CREATE TABLE `leitor_conquistas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `livro_slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conquistado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `email_enviado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_conquista` (`usuario_id`,`livro_slug`,`tipo`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_livro` (`livro_slug`)
) ENGINE=InnoDB AUTO_INCREMENT=674 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `leitor_conquistas` VALUES
('1', '1', 'o-farol-do-afogado', 'inicio', '2026-06-14 09:11:13', '0'),
('2', '1', 'o-farol-do-afogado', '25pct', '2026-06-14 09:11:16', '0'),
('3', '1', 'o-farol-do-afogado', '50pct', '2026-06-14 09:11:17', '0'),
('4', '1', 'o-farol-do-afogado', '75pct', '2026-06-14 09:11:24', '0'),
('9', '1', 'o-farol-do-afogado', '100pct', '2026-06-14 09:11:41', '0'),
('167', '1', 'a-setima-lei', 'inicio', '2026-06-14 12:18:05', '0'),
('453', '1', 'o-labirinto-dos-espelhos', 'inicio', '2026-06-14 16:57:49', '0'),
('454', '1', 'o-labirinto-dos-espelhos', '25pct', '2026-06-14 16:57:50', '0'),
('455', '1', 'o-labirinto-dos-espelhos', '50pct', '2026-06-14 16:57:52', '0'),
('456', '1', 'o-labirinto-dos-espelhos', '75pct', '2026-06-14 16:57:53', '0'),
('480', '1', 'a-penultima-pagina', 'inicio', '2026-06-14 17:04:18', '0'),
('481', '1', 'a-penultima-pagina', '25pct', '2026-06-14 17:04:18', '0'),
('482', '1', 'a-penultima-pagina', '50pct', '2026-06-14 17:04:19', '0'),
('483', '1', 'a-penultima-pagina', '75pct', '2026-06-14 17:04:20', '0'),
('500', '1', 'o-peso-do-horizonte', 'inicio', '2026-06-14 17:10:43', '0'),
('501', '1', 'o-peso-do-horizonte', '25pct', '2026-06-14 17:10:43', '0'),
('502', '1', 'o-peso-do-horizonte', '50pct', '2026-06-14 17:10:44', '0'),
('503', '1', 'o-peso-do-horizonte', '75pct', '2026-06-14 17:10:44', '0'),
('509', '1', 'o-peso-do-horizonte', '100pct', '2026-06-14 17:11:11', '0'),
('671', '1', 'o-quarto-passageiro', 'inicio', '2026-06-14 20:45:19', '0'),
('672', '1', 'o-quarto-passageiro', '25pct', '2026-06-14 20:45:20', '0'),
('673', '1', 'o-quarto-passageiro', '50pct', '2026-06-14 20:45:28', '0');

-- ------------------------------------------------------------
-- Tabela: `leitor_erros`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `leitor_erros`;
CREATE TABLE `leitor_erros` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `livro_slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cfi` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trecho` text COLLATE utf8mb4_unicode_ci,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `resolvido` tinyint(1) NOT NULL DEFAULT '0',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_livro_resolvido` (`livro_slug`,`resolvido`),
  KEY `idx_usuario` (`usuario_id`),
  CONSTRAINT `fk_erro_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `leitor_erros` VALUES
('1', '10', 'a-setima-lei', 'epubcfi(/6/20!/4/1:0)', 'frustrações, agora tem outro destinatário.', 'Esse é um teste da opção reportar erros.\nData: 13/06/2026\nCódigo 002', '1', '2026-06-13 14:08:45'),
('2', '10', 'a-setima-lei', 'epubcfi(/6/20!/4/1:0)', 'é só um amigo', 'Esse é um teste de erro. Verificando o envio de e-mail com o erro.\nData: 13/06/2026\nCód. 003', '1', '2026-06-13 14:14:19'),
('3', '10', 'a-setima-lei', 'epubcfi(/6/22!/4/4/1:83)', 'E mesmo que nunca chegue ao ato sexual, o estrago está feito', 'Teste de erro e-mail\nData: 13/06/20264', '1', '2026-06-13 14:16:39'),
('4', '10', 'a-setima-lei', 'epubcfi(/6/22!/4/4/1:83)', 'E mesmo que nunca chegue ao ato sexual, o estrago está feito.', 'Teste 005\nReportando erros', '1', '2026-06-13 14:31:04'),
('5', '10', 'a-setima-lei', 'epubcfi(/6/22!/4/4/1:83)', 'E mesmo que nunca chegue ao ato sexual, o estrago está feito.', 'Teste 005\nReportando erros', '1', '2026-06-13 14:31:07'),
('10', '13', 'cartas-do-passado', 'epubcfi(/6/12!/4/28/1:344)', 'a tempestade de metal que os rodeava.', 'Teste 16', '1', '2026-06-13 15:58:09'),
('11', '13', 'cartas-do-passado', 'epubcfi(/6/12!/4/28/1:344)', 'a tempestade de metal que os rodeava.', 'Teste 017', '1', '2026-06-13 16:05:19'),
('13', '14', 'cartas-do-passado', 'epubcfi(/6/12!/4/36/1:138)', 'Lucas', 'Teste 019', '1', '2026-06-13 16:18:28');

-- ------------------------------------------------------------
-- Tabela: `leitor_lembretes_enviados`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `leitor_lembretes_enviados`;
CREATE TABLE `leitor_lembretes_enviados` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `livro_slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enviado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lembrete` (`usuario_id`,`livro_slug`),
  KEY `idx_enviado_em` (`enviado_em`),
  CONSTRAINT `fk_lem_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Throttle de 14 dias para lembretes de leitura';

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `leitor_marcacoes`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `leitor_marcacoes`;
CREATE TABLE `leitor_marcacoes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `livro_slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capitulo` smallint(5) unsigned NOT NULL DEFAULT '1',
  `cfi_range` text COLLATE utf8mb4_unicode_ci,
  `trecho` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `cor` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#FFD700',
  `nota` text COLLATE utf8mb4_unicode_ci,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_livro` (`usuario_id`,`livro_slug`),
  CONSTRAINT `fk_marc_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `leitor_marcacoes` VALUES
('1', '10', 'a-setima-lei', '1', 'epubcfi(/6/18!/4/70,/1:0,/1:26)', 'Finanças, poder e vergonha', 'amarelo', NULL, '2026-06-13 13:55:19');

-- ------------------------------------------------------------
-- Tabela: `leitor_notas_autor`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `leitor_notas_autor`;
CREATE TABLE `leitor_notas_autor` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `livro_slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cfi` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('bastidor','personagem','cena','curiosidade','outro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'outro',
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conteudo` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_livro` (`livro_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `leitor_preferencias`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `leitor_preferencias`;
CREATE TABLE `leitor_preferencias` (
  `usuario_id` int(10) unsigned NOT NULL,
  `fonte` enum('serifada','sans','manuscrito','classica') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'serifada',
  `tamanho_fonte` tinyint(3) unsigned NOT NULL DEFAULT '18',
  `fundo_leitura` enum('branco','bege','cinza','preto') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bege',
  `largura_coluna` enum('estreita','media','larga') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'media',
  `altura_linha` decimal(3,1) NOT NULL DEFAULT '1.8',
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`usuario_id`),
  CONSTRAINT `fk_pref_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `leitor_preferencias` VALUES
('1', 'serifada', '18', '', 'larga', '1.8', '2026-06-14 20:45:40'),
('10', 'serifada', '19', '', 'larga', '1.7', '2026-06-13 13:43:45');

-- ------------------------------------------------------------
-- Tabela: `leitor_progresso`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `leitor_progresso`;
CREATE TABLE `leitor_progresso` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `livro_slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cfi` text COLLATE utf8mb4_unicode_ci,
  `capitulo_atual` smallint(5) unsigned NOT NULL DEFAULT '1',
  `posicao_scroll` int(10) unsigned NOT NULL DEFAULT '0',
  `percentual` decimal(5,2) NOT NULL DEFAULT '0.00',
  `total_paginas` smallint(5) unsigned DEFAULT NULL,
  `iniciado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultima_leitura` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `concluido` tinyint(1) NOT NULL DEFAULT '0',
  `concluido_em` datetime DEFAULT NULL,
  `tempo_total_min` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_progresso` (`usuario_id`,`livro_slug`),
  KEY `idx_ultima_leitura` (`ultima_leitura`),
  KEY `idx_percentual` (`percentual`),
  CONSTRAINT `fk_prog_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1303 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `leitor_progresso` VALUES
('1', '10', 'a-setima-lei', 'epubcfi(/6/22!/4/60/1:349)', '0', '0', '13.90', NULL, '2026-06-13 13:53:56', '2026-06-13 14:34:02', '0', NULL, '19'),
('9', '10', 'cartas-do-passado', 'epubcfi(/6/12!/4/42/1:162)', '1', '0', '3.10', NULL, '2026-06-13 13:57:38', '2026-06-13 14:33:17', '0', NULL, '2'),
('123', '13', 'cartas-do-passado', 'epubcfi(/6/12!/4/28/1:344)', '1', '0', '1.90', NULL, '2026-06-13 15:58:21', '2026-06-13 16:05:38', '0', NULL, '6'),
('135', '14', 'cartas-do-passado', 'epubcfi(/6/12!/4/26/1:0)', '1', '0', '2.30', NULL, '2026-06-13 16:13:32', '2026-06-13 20:09:09', '0', NULL, '233'),
('136', '1', 'o-farol-do-afogado', 'epubcfi(/6/4!/4/506/1:0)', '1', '0', '100.00', NULL, '2026-06-14 09:11:41', '2026-06-14 09:31:26', '0', NULL, '16'),
('169', '1', 'a-setima-lei', 'epubcfi(/6/20!/4/1:0)', '1', '0', '10.60', NULL, '2026-06-14 12:18:11', '2026-06-14 19:15:27', '0', NULL, '414'),
('455', '1', 'o-labirinto-dos-espelhos', 'epubcfi(/6/6!/4/2/12/1:0)', '1', '0', '37.50', NULL, '2026-06-14 16:58:17', '2026-06-14 19:15:41', '0', NULL, '95'),
('470', '1', 'a-penultima-pagina', 'epubcfi(/6/4!/4/2/2/1:0)', '1', '0', '14.30', NULL, '2026-06-14 17:04:45', '2026-06-14 19:15:42', '0', NULL, '95'),
('488', '1', 'o-peso-do-horizonte', 'epubcfi(/6/2!/4/1:0)', '1', '0', '100.00', NULL, '2026-06-14 17:11:11', '2026-06-14 21:10:33', '0', NULL, '299'),
('500', '1', 'o-quarto-passageiro', 'epubcfi(/6/2!/4/1:0)', '1', '0', '0.00', NULL, '2026-06-14 17:15:20', '2026-06-14 20:45:46', '0', NULL, '92');

-- ------------------------------------------------------------
-- Tabela: `leitura_conquistas`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `leitura_conquistas`;
CREATE TABLE `leitura_conquistas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `livro_slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `marco` tinyint(3) unsigned NOT NULL,
  `medalha` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conquistado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_conquista` (`usuario_id`,`livro_slug`,`marco`),
  KEY `idx_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `livros`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `livros`;
CREATE TABLE `livros` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('livro','conto') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'livro',
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitulo` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `genero` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sinopse` text COLLATE utf8mb4_unicode_ci,
  `capa_img` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `arquivo_pdf` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `arquivo_epub` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pasta_conteudo` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_capitulos` smallint(5) unsigned DEFAULT NULL,
  `preco` decimal(8,2) DEFAULT NULL,
  `preco_promocao` decimal(8,2) DEFAULT NULL,
  `promo_ate` datetime DEFAULT NULL COMMENT 'Promoção ativa enquanto NOW() < promo_ate',
  `gratuito_ate` datetime DEFAULT NULL COMMENT 'Gratuito temporariamente até esta data',
  `link_amazon` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_pub` date DEFAULT NULL,
  `badges` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `destaque` tinyint(1) NOT NULL DEFAULT '0',
  `gratuito` tinyint(1) NOT NULL DEFAULT '0',
  `novo` tinyint(1) NOT NULL DEFAULT '0',
  `ordem` smallint(5) unsigned NOT NULL DEFAULT '99',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_genero` (`genero`),
  KEY `idx_destaque` (`destaque`),
  KEY `idx_ordem` (`ordem`),
  KEY `idx_ativo_ordem` (`ativo`,`ordem`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `livros` VALUES
('1', 'jogo-das-mascaras', 'livro', 'O Jogo das Máscaras', NULL, NULL, NULL, 'img/jogo-das-mascaras.jpg', 'O-jogo-das-mascas-capitulo-1.pdf', 'O-jogo-das-mascas-capitulo-1.epub', 'livros-conteudo/jogo-das-mascaras/', '10', '19.90', NULL, NULL, NULL, NULL, NULL, NULL, '1', '0', '0', '0', '99', '2026-06-11 21:46:13'),
('2', 'a-setima-lei', 'livro', 'A Sétima Lei', NULL, NULL, NULL, 'img/a-setima-lei.jpg', 'A-setima-lei-capitulo-1.pdf', 'A-setima-lei-capitulo-1.epub', 'livros-conteudo/a-setima-lei/', '8', '19.90', NULL, NULL, NULL, NULL, NULL, NULL, '1', '0', '0', '0', '99', '2026-06-11 21:46:13'),
('3', 'a-marca-da-besta', 'livro', 'A Marca da Besta', 'O decimo quinto algoritmo', 'Gospel', NULL, 'img/a-marca-da-besta-capa.jpg', 'a-marca-da-besta-capitulo-1.pdf', 'a-marca-da-besta-capitulo-1.epub', 'livros-conteudo/a-marca-da-besta/', '9', '19.99', NULL, NULL, NULL, NULL, NULL, 'epub', '1', '0', '0', '0', '99', '2026-06-11 21:46:13'),
('4', 'caminhos-de-outono', 'livro', 'Caminhos de Outono', NULL, NULL, NULL, 'img/caminhos-de-outono.jpg', 'caminhos-de-outono-capitulo-1.pdf', 'caminhos-de-outono-capitulo-1.epub', 'livros-conteudo/caminhos-de-outono/', '7', '19.90', NULL, NULL, NULL, NULL, NULL, NULL, '1', '0', '0', '0', '99', '2026-06-11 21:46:13'),
('5', 'cartas-do-passado', 'livro', 'Cartas do Passado', NULL, NULL, NULL, 'img/cartas-do-passado.jpg', 'cartas-do-passado-capitulo-1.pdf', 'cartas-do-passado-capitulo-1.epub', 'livros-conteudo/cartas-do-passado/', '11', '19.90', NULL, NULL, NULL, NULL, NULL, NULL, '1', '0', '0', '0', '99', '2026-06-11 21:46:13'),
('6', 'das-coisas-que-o-amor-faz', 'livro', 'Das Coisas que o Amor Faz', NULL, NULL, NULL, 'img/coisas-que-o-amor-faz.jpg', 'das-coisas-que-o-amor-faz-capitulo-1.pdf', 'das-coisas-que-o-amor-faz-capitulo-1.epub', 'livros-conteudo/das-coisas-que-o-amor-faz/', '8', '19.90', NULL, NULL, NULL, NULL, NULL, 'epub', '1', '0', '0', '0', '99', '2026-06-11 21:46:13'),
('7', 'genesis', 'livro', 'Gênesis', NULL, NULL, NULL, 'img/genesis.jpg', 'genesis-capitulo-1.pdf', 'genesis-capitulo-1.epub', 'livros-conteudo/genesis/', '10', '19.90', NULL, NULL, NULL, NULL, NULL, NULL, '1', '0', '0', '0', '99', '2026-06-11 21:46:13'),
('8', 'lumen', 'livro', 'Lúmen – A Outra Metade do Céu', NULL, NULL, NULL, 'img/lumen.jpg', 'lumen-capitulo-1.pdf', 'lumen-capitulo-1.epub', 'livros-conteudo/lumen/', '9', '19.90', NULL, NULL, NULL, NULL, NULL, NULL, '1', '0', '0', '0', '99', '2026-06-11 21:46:13'),
('9', 'mares-secretas-do-amor', 'livro', 'As Marés Secretas do Amor', NULL, NULL, NULL, 'img/mares-secretas.jpg', 'as-mares-secretas-do-amor-capitulo-1.pdf', 'as-mares-secretas-do-amor-capitulo-1.epub', 'livros-conteudo/mares-secretas-do-amor/', '9', '19.90', NULL, NULL, NULL, NULL, NULL, NULL, '1', '0', '0', '0', '99', '2026-06-11 21:46:13'),
('10', 'o-abismo-das-almas', 'livro', 'O Abismo das Almas', NULL, NULL, NULL, 'img/o-abismo-das-almas-vol-1.jpg', 'o-abismo-das-almas-capitulo-1.pdf', 'o-abismo-das-almas-capitulo-1.epub', 'livros-conteudo/o-abismo-das-almas/', '8', '19.90', NULL, NULL, NULL, NULL, NULL, 'epub', '1', '0', '0', '0', '99', '2026-06-11 21:46:13'),
('11', 'rosas-e-espinhos', 'livro', 'Rosas e Espinhos', NULL, NULL, NULL, 'img/rosas-e-espinhos.jpg', 'rosas-e-espinhos-capitulo-1.pdf', 'rosas-e-espinhos-capitulo-1.epub', 'livros-conteudo/rosas-e-espinhos/', '6', '19.90', NULL, NULL, NULL, NULL, NULL, NULL, '1', '0', '0', '0', '99', '2026-06-11 21:46:13'),
('34', 'o-farol-do-afogado', 'conto', 'O farol do afogado', NULL, 'Thriller', NULL, 'img/o-farol-do-afogado-conto.jpg', NULL, 'o-farol-do-afogado.epub', 'livros-conteudo/contos/', '10', NULL, NULL, NULL, NULL, NULL, '2026-04-28', 'epub', '1', '0', '1', '1', '12', '2026-06-12 17:59:11'),
('35', 'o-labirinto-dos-espelhos', 'conto', 'O Labirinto dos Espelhos', NULL, 'Autoajuda', 'Um homem que se sente estagnado na carreira e na vida pessoal acorda dentro de um labirinto feito inteiramente de espelhos. Cada espelho que ele olha não reflete sua imagem atual, mas sim uma versão diferente de quem ele poderia ter sido se tivesse mantido a disciplina ou enfrentado seus medos.', 'img/contos/o-labirinto-dos-espelhos.webp', NULL, NULL, 'livros-conteudo/contos/', '10', NULL, NULL, NULL, NULL, NULL, '2026-02-28', 'epub', '1', '0', '1', '0', '13', '2026-06-14 16:57:17'),
('36', 'a-penultima-pagina', 'livro', 'A Penúltima Página', NULL, 'Drama', 'Jaqueline tem o hábito de comprar livros usados em sebos e deixar bilhetes dobrados na penúltima página, contendo suas impressões e um bilhete anônimo. Ela faz isso há anos, sem nunca receber uma resposta.', 'img/contos/a-penultima-pagina.webp', NULL, NULL, 'livros-conteudo/contos', '10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', '0', '1', '0', '14', '2026-06-14 17:02:58'),
('37', 'o-peso-do-horizonte', 'conto', 'O Peso do Horizonte', NULL, 'Drama', 'Um pintor famoso, agora idoso e perdendo a visão, decide leiloar sua obra-prima secreta: um quadro que ele manteve trancado em seu estúdio por quarenta anos. O quadro retrata uma escolha que ele fez na juventude, onde abandonou tudo o que amava em busca do sucesso.', 'img/contos/o-peso-do-horizinte.webp', NULL, NULL, 'livros-conteudo/contos', '10', NULL, NULL, NULL, NULL, NULL, '2026-02-28', NULL, '1', '0', '1', '0', '15', '2026-06-14 17:09:03'),
('38', 'o-quarto-passageiro', 'conto', 'O Quarto Passageiro', NULL, 'Terror', 'Um motorista de aplicativo aceita uma corrida de madrugada em uma estrada isolada de Ceará. Três passageiros entram no carro em silêncio absoluto. O clima fica pesado instantaneamente, e o rádio do carro começa a chiar em uma frequência estranha.', 'img/contos/o-quarto-passageiro', NULL, NULL, 'livros-conteudo/contos', '10', NULL, NULL, NULL, NULL, NULL, '2026-02-28', 'epub', '1', '0', '1', '0', '99', '2026-06-14 17:14:28');

-- ------------------------------------------------------------
-- Tabela: `livros_favoritos`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `livros_favoritos`;
CREATE TABLE `livros_favoritos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `livro_id` int(10) unsigned NOT NULL,
  `adicionado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_livro` (`usuario_id`,`livro_id`),
  CONSTRAINT `fk_lf_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `magic_links`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `magic_links`;
CREATE TABLE `magic_links` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `token` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ml_token` (`token`),
  KEY `idx_ml_usuario` (`usuario_id`),
  CONSTRAINT `fk_ml_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `newsletter`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `newsletter`;
CREATE TABLE `newsletter` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origem` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'home, blog, livro_slug, popup_saida, etc.',
  `status` enum('pendente','ativo','descadastrado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendente',
  `token_verificacao` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL,
  `pref_bastidores` tinyint(1) NOT NULL DEFAULT '1',
  `pref_reflexao` tinyint(1) NOT NULL DEFAULT '1',
  `pref_escritor` tinyint(1) NOT NULL DEFAULT '1',
  `pref_livros` tinyint(1) NOT NULL DEFAULT '1',
  `descad_em` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_origem` (`origem`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `newsletter` VALUES
('1', 'roberiodiogenes.frdc@gmail.com', NULL, NULL, '170.82.213.33', 'popup_saida', 'ativo', NULL, NULL, '1', '1', '1', '1', NULL, '2026-06-12 10:35:45');

-- ------------------------------------------------------------
-- Tabela: `newsletter_disparos`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `newsletter_disparos`;
CREATE TABLE `newsletter_disparos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_slug` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_envios` int(10) unsigned NOT NULL DEFAULT '0',
  `total_erros` int(10) unsigned NOT NULL DEFAULT '0',
  `disparado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `disparado_por` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_nd_post` (`post_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `newsletter_log`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `newsletter_log`;
CREATE TABLE `newsletter_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tentativa` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `password_reset`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `password_reset`;
CREATE TABLE `password_reset` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expira_em` datetime NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token` (`token`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_expira_em` (`expira_em`),
  CONSTRAINT `fk_pr_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `password_reset` VALUES
('4', '1', '18bdea51208f59a79b29b56d2ae444da426bee7b8976e344f36d989d5bd737f2', '2026-06-12 17:34:20', '2026-06-12 15:34:20', NULL);

-- ------------------------------------------------------------
-- Tabela: `pdf_cliques`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `pdf_cliques`;
CREATE TABLE `pdf_cliques` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned DEFAULT NULL,
  `pdf_nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pdf` (`pdf_nome`),
  KEY `idx_data` (`criado_em`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pdf_cliques` VALUES
('1', NULL, 'o-colecionador-de-paginas', '170.82.213.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-13 17:41:06'),
('2', NULL, 'o-colecionador-de-paginas', '170.82.213.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-14 08:05:53');

-- ------------------------------------------------------------
-- Tabela: `planos`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `planos`;
CREATE TABLE `planos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `preco` decimal(8,2) NOT NULL,
  `duracao_dias` smallint(5) unsigned NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `planos` VALUES
('1', 'mensal', 'Plano Mensal', 'Acesso completo à biblioteca por 30 dias. R$29,90/mês.', '29.90', '30', '1', '2026-06-11 21:46:13'),
('2', 'trimestral', 'Plano Trimestral', 'Acesso completo à biblioteca por 3 meses. Equivale a R$23,30/mês.', '69.90', '90', '1', '2026-06-11 21:46:13'),
('3', 'semestral', 'Plano Semestral', 'Acesso completo à biblioteca por 6 meses. Equivale a R$19,98/mês.', '119.90', '180', '1', '2026-06-11 21:46:13'),
('4', 'anual', 'Plano Anual', 'Acesso completo à biblioteca por 1 ano. Equivale a R$12,90/mês.', '154.80', '365', '1', '2026-06-11 21:46:13');

-- ------------------------------------------------------------
-- Tabela: `posts`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitulo` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `categoria` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tags` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resumo` text COLLATE utf8mb4_unicode_ci,
  `corpo` longtext COLLATE utf8mb4_unicode_ci,
  `imagem_url` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `audio_url` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `livro_slug` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destaque` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('publicado','oculto','rascunho','agendado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rascunho',
  `publicado_em` datetime DEFAULT NULL,
  `exclusivo` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = conteúdo para assinantes',
  `percentual_livre` tinyint(3) unsigned NOT NULL DEFAULT '35' COMMENT '% exibido sem assinatura',
  `enquete_id` int(10) unsigned DEFAULT NULL,
  `cluster_id` int(10) unsigned DEFAULT NULL,
  `newsletter_enviado` tinyint(1) NOT NULL DEFAULT '0',
  `tipo_post` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'avulso' COMMENT 'pilar | satelite | avulso',
  `estatico` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = conteúdo embutido no HTML estático',
  `tempo_leitura` tinyint(3) unsigned NOT NULL DEFAULT '5' COMMENT 'minutos estimados de leitura',
  `html_externo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'path do arquivo HTML estático (ex: blog/meu-post.html)',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_status` (`status`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_publicado_em` (`publicado_em`),
  KEY `idx_destaque` (`destaque`),
  KEY `idx_cluster_id` (`cluster_id`),
  KEY `idx_exclusivo` (`exclusivo`),
  KEY `idx_tipo_post` (`tipo_post`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `posts` VALUES
('1', 'h-g-wells-guia-definitivo', 'H. G. Wells: O Homem Que Imaginou o Futuro Antes que Ele Existisse', 'Da máquina do tempo à guerra dos mundos — o guia completo sobre o pai da ficção científica', 'bastidores', NULL, 'Um guia abrangente sobre a vida, a obra e o legado de H. G. Wells: biólogo, jornalista, visionário. Como um filho de empregada doméstica se tornou o profeta do século XX.', '', 'img/posts/h-g-wells-guia-definitivo.webp', '', '', '1', 'publicado', '2025-06-01 09:00:00', '0', '35', NULL, '1', '0', 'pilar', '1', '18', '', '2026-06-11 21:46:13', '2026-06-14 09:36:02'),
('2', 'a-maquina-do-tempo-resenha', 'A Máquina do Tempo: O Livro que Me Ensinou a Sonhar com o Futuro — e a Temer o que Encontraria Lá', 'Resenha aprofundada: Eloi, Morlocks e a melancolia do fim do mundo', 'bastidores', NULL, 'Uma resenha pessoal e literária de A Máquina do Tempo de H. G. Wells — sobre distopia, desigualdade e a solidão de quem vê o fim de tudo.', '', 'img/posts/a-maquina-do-tempo-resenha.webp', '', '', '0', 'publicado', '2025-06-04 09:00:00', '0', '35', NULL, '1', '0', 'satelite', '1', '12', '', '2026-06-11 21:46:13', '2026-06-14 10:07:09'),
('3', 'o-homem-invisivel-resenha', 'O Homem Invisível: Quando o Maior Poder do Mundo se Torna a Mais Terrível das Prisões', 'Resenha: Griffin, o anel de Giges e o que acontece quando ninguém pode te ver', 'bastidores', NULL, 'O que você faria se pudesse se tornar invisível? A resposta de H. G. Wells é perturbadora — e mais atual do que nunca.', '', 'img/posts/o-homem-invisivel-resenha.webp', '', '', '0', 'publicado', '2025-06-06 09:00:00', '0', '35', NULL, '1', '0', 'satelite', '1', '10', '', '2026-06-11 21:46:13', '2026-06-14 10:07:48'),
('4', 'o-dorminhoco-resenha', 'O Dorminhoco: Acordar em um Futuro que Nunca Pedimos Para Ver', 'Resenha: a distopia urbana mais esquecida — e mais visionária — de Wells', 'bastidores', NULL, 'Wells adormece um homem no século XIX e o acorda duzentos anos depois num mundo controlado por corporações. Ficção ou descrição?', '', 'img/posts/o-dorminhoco-resenha.webp', '', '', '0', 'publicado', '2025-06-03 09:00:00', '0', '35', NULL, '1', '0', 'satelite', '1', '10', '', '2026-06-11 21:46:13', '2026-06-14 10:06:25'),
('5', 'a-ilha-do-dr-moreau-resenha', 'A Ilha do Dr. Moreau: O Horror que Mora Dentro da Pele Humana', 'Resenha: ciência, soberba e a linha tênue entre homem e animal', 'bastidores', NULL, 'Um cientista que remodela animais à imagem humana — não por bondade, mas por soberba intelectual. A pergunta de Wells sobre os limites da ciência ressoa mais forte do que nunca.', '', 'img/posts/a-ilha-do-dr-moreau-resenha.webp', '', '', '0', 'publicado', '2025-06-09 09:00:00', '0', '35', NULL, '1', '0', 'satelite', '1', '11', '', '2026-06-11 21:46:13', '2026-06-14 10:09:02'),
('6', 'a-guerra-dos-mundos-resenha', 'A Guerra dos Mundos: Quando o Universo Parou de Ser Amigável', 'Resenha: o imperialismo invertido e a noite que Orson Welles parou a América', 'bastidores', NULL, 'Wells pedalava pelas ruas de Woking quando imaginou: e se fôssemos nós os colonizados? O livro que virou o imperialismo britânico de cabeça para baixo.', '', 'img/posts/a-guerra-dos-mundos-resenha.webp', '', 'abismo-das-almas', '0', 'publicado', '2025-06-02 09:00:00', '0', '35', NULL, '1', '0', 'satelite', '1', '13', '', '2026-06-11 21:46:13', '2026-06-14 10:04:55');

-- ------------------------------------------------------------
-- Tabela: `posts_lidos`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `posts_lidos`;
CREATE TABLE `posts_lidos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `post_slug` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `progresso` tinyint(3) unsigned NOT NULL DEFAULT '100',
  `lido_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lido` (`usuario_id`,`post_slug`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_post_slug` (`post_slug`),
  CONSTRAINT `fk_pl_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `pre_lancamento_leads`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `pre_lancamento_leads`;
CREATE TABLE `pre_lancamento_leads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lancamento_id` int(10) unsigned NOT NULL,
  `nome` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `brinde_enviado` tinyint(1) NOT NULL DEFAULT '0',
  `lancamento_enviado` tinyint(1) NOT NULL DEFAULT '0',
  `inscrito_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lead` (`lancamento_id`,`email`),
  KEY `idx_ll_lancamento` (`lancamento_id`),
  KEY `idx_ll_email` (`email`),
  KEY `idx_ll_brinde` (`brinde_enviado`),
  CONSTRAINT `fk_lead_lancamento` FOREIGN KEY (`lancamento_id`) REFERENCES `pre_lancamentos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `pre_lancamentos`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `pre_lancamentos`;
CREATE TABLE `pre_lancamentos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitulo` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `capa_img` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_lancamento` date DEFAULT NULL,
  `brinde_titulo` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brinde_html` longtext COLLATE utf8mb4_unicode_ci,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `lancado` tinyint(1) NOT NULL DEFAULT '0',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `presentes`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `presentes`;
CREATE TABLE `presentes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comprador_id` int(10) unsigned NOT NULL,
  `livro_slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_presenteado` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_presenteado` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dedicatoria` text COLLATE utf8mb4_unicode_ci,
  `preco_pago` decimal(8,2) NOT NULL DEFAULT '0.00',
  `token_acesso` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pendente','aprovado','resgatado','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendente',
  `ref_externa` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resgatado_por` int(10) unsigned DEFAULT NULL,
  `resgatado_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token` (`token_acesso`),
  KEY `idx_comprador` (`comprador_id`),
  KEY `idx_email_presenteado` (`email_presenteado`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_pres_comprador` FOREIGN KEY (`comprador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `temas_sazonais`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `temas_sazonais`;
CREATE TABLE `temas_sazonais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `prioridade` smallint(6) NOT NULL DEFAULT '0',
  `dia_inicio` tinyint(3) unsigned NOT NULL COMMENT '1-31',
  `mes_inicio` tinyint(3) unsigned NOT NULL COMMENT '1-12',
  `dia_fim` tinyint(3) unsigned NOT NULL,
  `mes_fim` tinyint(3) unsigned NOT NULL,
  `cor_ouro` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#B8860B',
  `cor_ferrugem` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#8B3A2A',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `temas_sazonais` VALUES
('1', 'Ano Novo', 'ano-novo', '1', '1', '28', '12', '3', '1', '#A8B8D0', '#1A2C5A', '2026-06-11 21:50:35', '2026-06-11 21:50:35'),
('2', 'Carnaval', 'carnaval', '1', '2', '8', '2', '5', '3', '#9B59B6', '#16A085', '2026-06-11 21:50:35', '2026-06-11 21:50:35'),
('3', 'Dia Internacional da Mulher', 'dia-mulher', '1', '3', '6', '3', '8', '3', '#8E24AA', '#C62828', '2026-06-11 21:50:35', '2026-06-11 21:50:35'),
('4', 'Dia Internacional do Livro Infantil', 'dia-livro-infantil', '1', '4', '30', '3', '2', '4', '#D81B60', '#7B1FA2', '2026-06-11 21:50:35', '2026-06-11 21:50:35'),
('5', 'Dia do Livro', 'dia-livro', '1', '5', '18', '4', '23', '4', '#9A6B1A', '#6B3A28', '2026-06-11 21:50:35', '2026-06-11 21:50:35'),
('6', 'Dia das Mães', 'dia-maes', '1', '6', '5', '5', '11', '5', '#C2185B', '#7B1FA2', '2026-06-11 21:50:35', '2026-06-11 21:50:35'),
('7', 'Dia dos Namorados', 'dia-namorados', '1', '7', '10', '6', '12', '6', '#B71C1C', '#880E4F', '2026-06-11 21:50:35', '2026-06-11 21:50:35'),
('8', 'Dia da Literatura Brasileira', 'dia-literatura-brasileira', '1', '8', '23', '7', '25', '7', '#2E7D32', '#E65100', '2026-06-11 21:50:35', '2026-06-11 21:50:35'),
('9', 'Dia dos Pais', 'dia-pais', '1', '9', '7', '8', '11', '8', '#1565C0', '#0D47A1', '2026-06-11 21:50:35', '2026-06-11 21:50:35'),
('10', '7 de Setembro', 'sete-setembro', '1', '10', '5', '9', '8', '9', '#F9A825', '#1B5E20', '2026-06-11 21:50:35', '2026-06-11 21:50:35'),
('11', 'Dia Nacional do Escritor', 'dia-escritor', '1', '11', '17', '9', '19', '9', '#8D6E63', '#4E342E', '2026-06-11 21:50:35', '2026-06-11 21:50:35'),
('12', 'Nossa Senhora Aparecida', 'nossa-senhora-aparecida', '1', '12', '10', '10', '12', '10', '#1565C0', '#9C7B00', '2026-06-11 21:50:35', '2026-06-11 21:50:35'),
('13', 'Dia das Crianças', 'dia-criancas', '0', '13', '10', '10', '12', '10', '#FF6F00', '#00838F', '2026-06-11 21:50:35', '2026-06-11 21:50:35'),
('14', 'Halloween', 'halloween', '1', '14', '27', '10', '31', '10', '#E65100', '#4A148C', '2026-06-11 21:50:35', '2026-06-11 21:50:35'),
('15', 'Finados', 'finados', '1', '15', '31', '10', '2', '11', '#7B1FA2', '#E65100', '2026-06-11 21:50:35', '2026-06-11 21:50:35'),
('16', '15 de Novembro', 'quinze-novembro', '1', '16', '13', '11', '15', '11', '#2E7D32', '#F9A825', '2026-06-11 21:50:35', '2026-06-11 21:50:35'),
('17', 'Natal', 'natal', '1', '17', '1', '12', '25', '12', '#C62828', '#2E7D32', '2026-06-11 21:50:35', '2026-06-11 21:50:35');

-- ------------------------------------------------------------
-- Tabela: `usuario_interesses`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `usuario_interesses`;
CREATE TABLE `usuario_interesses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `categoria` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Valor do campo categoria nos posts',
  `contagem` int(10) unsigned NOT NULL DEFAULT '1' COMMENT 'Nº de posts lidos nesta categoria',
  `ultima_vista` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_cat` (`usuario_id`,`categoria`),
  KEY `idx_cat` (`categoria`),
  KEY `idx_ultima` (`ultima_vista`),
  CONSTRAINT `fk_int_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Rastreia categorias de posts mais lidas por usuário — segmentação de campanhas';

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `usuarios`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tipo` enum('leitor','assinante','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'leitor',
  `sexo` enum('masculino','feminino','outro','nao_informado') COLLATE utf8mb4_unicode_ci DEFAULT 'nao_informado',
  `data_nascimento` date DEFAULT NULL,
  `cidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pais` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Brasil',
  `whatsapp` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_cadastro` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verificado` tinyint(1) NOT NULL DEFAULT '0',
  `token_confirmacao` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_expira_em` datetime DEFAULT NULL,
  `email_confirmado_em` datetime DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `ultimo_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  UNIQUE KEY `uq_google_id` (`google_id`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_token_confirmacao` (`token_confirmacao`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usuarios` VALUES
('1', 'Francisco Robério Diógenes da Costa', 'roberiodiogenes.frdc@gmail.com', '$2y$12$U.mDpTbM.KrJtiMDbr1nrux6VQPXxoyrl3NQDLodlPrhzjvYgyLlS', 'leitor', 'nao_informado', '1974-02-28', 'Cascavel', NULL, 'Brasil', '+5585996409818', '113965967309702618041', 'https://lh3.googleusercontent.com/a/ACg8ocKhWFhbrCt4p0WVZZ5s767h_SXR8cFmMDzS74fOpBUkauZY3nE=s96-c', '170.82.213.4', '0', NULL, NULL, NULL, '1', '2026-06-15 03:34:40', '2026-06-12 10:59:35', '2026-06-15 03:35:10'),
('3', 'Francisco', 'roberdioha@gmail.com', '$2y$12$7AU/lMafDTVnl6xPNpkLpui0Kbk0Sajww4elVW9QmKD3popuPW4Hu', 'leitor', 'nao_informado', NULL, NULL, NULL, 'Brasil', NULL, NULL, NULL, '170.82.213.33', '0', NULL, NULL, NULL, '1', '2026-06-12 15:45:35', '2026-06-12 15:45:35', '2026-06-12 15:45:35'),
('4', 'Rober Dioh', 'roberdioh@gmail.com', '$2y$12$39FZXykwXGpYDK5xswSbcue.WBjX7dBOY.zYl9t.Z8RcwTjO/Fbva', 'leitor', 'nao_informado', NULL, NULL, NULL, 'Brasil', NULL, NULL, NULL, '170.82.213.33', '1', NULL, NULL, NULL, '1', '2026-06-12 22:49:07', '2026-06-12 22:49:07', '2026-06-12 22:50:24'),
('6', 'Diógenes Costa', 'roberdioh2018b@gmail.com', '$2y$12$dUPC1GEfO5gicR6AFTf5petrh0eR9kKVV7PveWHlhnf6I4ZrR22Ta', 'leitor', 'nao_informado', NULL, NULL, NULL, 'Brasil', NULL, NULL, NULL, '170.82.213.33', '0', '1ce92163a05222a5b8a1480628ae374d614757ab36fd1fed983a2b77c0f5bfeb', '2026-06-20 09:14:10', NULL, '1', '2026-06-13 09:14:10', '2026-06-13 09:14:10', '2026-06-13 09:14:10'),
('10', 'Rober Dioh', 'roberdioh2018d@gmail.com', '', 'leitor', 'nao_informado', NULL, NULL, NULL, 'Brasil', NULL, '107411770598417310354', 'https://lh3.googleusercontent.com/a/ACg8ocJVVzU5GkwSrdzlvLhfgGDAAs-n7nqXOp-Ez1zAKNRNRZ5EKg=s96-c', '170.82.213.33', '1', NULL, NULL, NULL, '1', '2026-06-13 13:24:41', '2026-06-13 13:24:41', '2026-06-13 13:24:41'),
('13', 'Rober', 'roberdioh2018c@gmail.com', '$2y$12$3OwNQ6xPB9jblOAVR7fH/ew5Q1pErC0yXxEfhhixt6yMRvbbHmB8i', 'leitor', 'nao_informado', NULL, NULL, NULL, 'Brasil', NULL, NULL, NULL, '170.82.213.33', '0', 'bdsqsodi4ESL66ZI6lasWWXO-XTnTzI-VixnJgrgoKI', '2026-06-20 15:41:01', NULL, '1', '2026-06-13 15:41:01', '2026-06-13 15:41:01', '2026-06-13 15:41:01'),
('14', 'Rober Dioh', 'roberdioh2018a@gmail.com', '$2y$12$Sqdrl9.XqFwLuxLaFA5R..vw1Zi9xCRYPaueQQlXXxiN9JonG7YD.', 'leitor', 'nao_informado', NULL, NULL, NULL, 'Brasil', NULL, NULL, NULL, '170.82.213.33', '1', NULL, NULL, '2026-06-13 16:07:39', '1', '2026-06-13 16:07:03', '2026-06-13 16:07:03', '2026-06-13 16:07:39');

-- ------------------------------------------------------------
-- Tabela: `visitas`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `visitas`;
CREATE TABLE `visitas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `total` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `visitas` VALUES
('1', '38');

-- ------------------------------------------------------------
-- Tabela: `visitas_log`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `visitas_log`;
CREATE TABLE `visitas_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `visitor_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visit_date` date NOT NULL,
  `visited_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_visitor_dia` (`visitor_hash`,`visit_date`),
  KEY `idx_visit_date` (`visit_date`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `visitas_log` VALUES
('1', 'e3e89e9e78e64b8ce7631f227afdcdbaea29702eaa7f8a5b2831969d734b7daa', '2026-06-12', '2026-06-12 10:35:15'),
('2', '64cfed0b614dc986eb5032fd76df273dc9d9d429a1d27399f705042ede20a436', '2026-06-12', '2026-06-12 10:58:06'),
('10', 'a6e3fe3475002d085c137f067bb6ff1c00c7461204e28c5bc55a16ee1774f80e', '2026-06-12', '2026-06-12 14:37:29'),
('17', 'd3baf1db163c8ad8606f094d52591b9cdef054e60fcfe2d5c56406716075aea1', '2026-06-12', '2026-06-12 15:28:55'),
('18', 'bae8e0171a6b0b1611de3f817c22693134022ea2990f3e9a57f551bc29d87949', '2026-06-12', '2026-06-12 15:29:06'),
('24', '6efc810d769f4081ac94a466fd71611e2862eaec108f31655951cd0d5474f45f', '2026-06-12', '2026-06-12 17:29:18'),
('27', 'f101c322f6de0f18ab63bf4cad4de87b2cd190e66ab9754aa78d241a124fd09e', '2026-06-12', '2026-06-12 22:15:22'),
('30', '80e85c09d0fa591b3890bd84460cd47b41a6f69ba92e9576d5a56a27db5441f1', '2026-06-13', '2026-06-13 11:38:30'),
('31', 'e3e89e9e78e64b8ce7631f227afdcdbaea29702eaa7f8a5b2831969d734b7daa', '2026-06-13', '2026-06-13 13:07:21'),
('40', 'f5ff53328e24232d78b44d08bfef002af495298d7871bc19056cfdc414aed418', '2026-06-13', '2026-06-13 20:26:50'),
('41', '69edb5c5040b1fe6e54b233c6f9d829591712d72be7e9ece207c399ca3586dfe', '2026-06-13', '2026-06-13 20:26:50'),
('42', '0dde0bc3ddebebd6dde8dbf767eaf14c44e08bde5132ad3abcc42be31f06894f', '2026-06-13', '2026-06-13 20:26:50'),
('43', '55e356f819daab0dc0ae75b1e515a07b5163bcfb6fceac455243fee31feade27', '2026-06-13', '2026-06-13 20:40:15'),
('44', 'a9cd66cd0000019433b0641530af27576838153969cb431f2a6e14000ef9ecae', '2026-06-13', '2026-06-13 20:40:36'),
('45', 'e3e89e9e78e64b8ce7631f227afdcdbaea29702eaa7f8a5b2831969d734b7daa', '2026-06-14', '2026-06-14 12:17:28'),
('46', '8c5f34112bdd1bb5112d2572ded706b37b16973401fad9fdeab6887f91350e87', '2026-06-15', '2026-06-15 03:12:08'),
('47', 'd0060c9b67fc9deba1979b388e4c6e29dfe78fc17bb4628346a023ffcb0b3714', '2026-06-15', '2026-06-15 03:12:10'),
('48', '6e9f2fbb917aa285b73f5fe64aa62ab16659b8f11fafcb8988379459d3a0e7ce', '2026-06-15', '2026-06-15 03:12:12'),
('49', '53185ecb23aee3b2029345b956728ae50f44c8a51fcd0047fb4483801e0a4caf', '2026-06-15', '2026-06-15 03:12:13'),
('50', 'dd75f67ac639b16bde889637edd5431954c86a1f497b4050b0393c130cf75a9b', '2026-06-15', '2026-06-15 03:13:37'),
('52', '2625230e44113b20c9cf991b5cd38a5b034b3fb8dd3eba684856cb0be39a543e', '2026-06-15', '2026-06-15 03:33:59'),
('53', '244a2c8b6e309a142e8a7c7fc8dd5baee16ad8385044bca0295d64f5f321a8bd', '2026-06-15', '2026-06-15 04:16:26'),
('54', 'd8f54ae31d5a971e2bc92b5be1c47aeea17e77b8ffa5cc6c7cf13e87a2165c90', '2026-06-15', '2026-06-15 04:20:19'),
('55', '73dafe70922067875631304ffec14092a4026c036594012d6715f57299be852f', '2026-06-15', '2026-06-15 04:21:50'),
('56', '5c4b064ce799ba36bb85966160662162512ddec7ea88aef9edb5053ddf116860', '2026-06-15', '2026-06-15 04:35:24'),
('57', 'b5776eb8731c9016824b468bbfe1cff774bc073743945755fd271a9de5d53646', '2026-06-15', '2026-06-15 04:44:17'),
('58', '0239f7128f1275179cc12e3cf0c5cece97dd33c3601118f74a66a6e54d452748', '2026-06-15', '2026-06-15 05:15:00'),
('59', '315238607d2b29caf51dbc8a527d65e0df84d67ec744ee608048d75bf4b643c9', '2026-06-15', '2026-06-15 05:15:56'),
('60', 'd58bf83ab77c6411e7e17967bcb859d1b458660d2cacdd97c5b3de070a3f6ead', '2026-06-15', '2026-06-15 05:16:02'),
('61', 'e502b0382c21d02a8eecd79deae0d20bce824760e578eb8f5cece739a0b79950', '2026-06-15', '2026-06-15 05:16:23'),
('62', '23040be018ef484414b29fa3ccdcf99b2fed49236a728afaf3ad0bbc10332f37', '2026-06-15', '2026-06-15 05:28:51'),
('63', '26102bcb34724016d0776321ab7e3414d9f2822181631a5c6bfec622e298305d', '2026-06-15', '2026-06-15 05:32:52'),
('64', '56a12f677582e67746abcf19cf3c0d9c201efda72dc569aef1debbc367474968', '2026-06-15', '2026-06-15 06:03:30'),
('65', '3253b48e2f7407bd0ec63ea4ff90d33ef88f7bc7d33c35d76c10204272eb611e', '2026-06-15', '2026-06-15 06:12:49'),
('66', '4e09271cdba07dc64084e8c560a48fdf816b37cec3d34f8b9db2c0b2a4988551', '2026-06-15', '2026-06-15 06:32:52'),
('67', '1c06613b2a326f69e3a405b37f256880d912a9c7a2f600aac1a60b75f7140344', '2026-06-15', '2026-06-15 06:36:48'),
('68', '6a9df8b5fce55b4f99390c7b9fcd537818b9100de80b55b70944d03cb47f52c8', '2026-06-15', '2026-06-15 07:29:56'),
('70', '9d9644b5fe4cbe3b2e4cc2df184725b6c9a4026166ab8a5a21a6912c1c684861', '2026-06-15', '2026-06-15 07:39:35');

-- ------------------------------------------------------------
-- Tabela: `vw_acesso_leitura`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `vw_acesso_leitura`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_acesso_leitura` AS select `c`.`usuario_id` AS `usuario_id`,`c`.`livro_slug` AS `livro_slug`,'compra' AS `tipo_acesso`,NULL AS `expira_em` from `compras` `c` where (`c`.`status` = 'aprovada') union all select `a`.`usuario_id` AS `usuario_id`,`l`.`slug` AS `livro_slug`,'assinatura' AS `tipo_acesso`,`a`.`expira_em` AS `expira_em` from (`assinaturas` `a` join `livros` `l`) where ((`a`.`status` = 'ativa') and (`a`.`expira_em` > now()) and (`l`.`ativo` = 1));

-- (sem registros)

-- ------------------------------------------------------------
-- Tabela: `vw_dashboard`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `vw_dashboard`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_dashboard` AS select (select count(0) from `usuarios` where (`usuarios`.`ativo` = 1)) AS `total_usuarios`,(select count(0) from `assinaturas` where ((`assinaturas`.`status` = 'ativa') and (`assinaturas`.`expira_em` > now()))) AS `assinaturas_ativas`,(select count(0) from `compras` where (`compras`.`status` = 'aprovada')) AS `total_compras`,(select coalesce(sum(`compras`.`preco_pago`),0) from `compras` where ((`compras`.`status` = 'aprovada') and (year(`compras`.`comprado_em`) = year(now())) and (month(`compras`.`comprado_em`) = month(now())))) AS `receita_mes_compras`,(select coalesce(sum(`p`.`preco`),0) from (`assinaturas` `a` join `planos` `p` on((`p`.`id` = `a`.`plano_id`))) where ((`a`.`status` = 'ativa') and (year(`a`.`inicio_em`) = year(now())) and (month(`a`.`inicio_em`) = month(now())))) AS `receita_mes_assinaturas`,(select count(0) from `newsletter` where (`newsletter`.`status` = 'ativo')) AS `leads_newsletter`,(select count(0) from `pre_lancamento_leads`) AS `leads_pre_lancamento`;

INSERT INTO `vw_dashboard` VALUES
('7', '0', '0', '0.00', '0.00', '1', '0');

-- ------------------------------------------------------------
-- Tabela: `vw_ultimas_leituras`
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `vw_ultimas_leituras`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_ultimas_leituras` AS select `lp`.`usuario_id` AS `usuario_id`,`lp`.`livro_slug` AS `livro_slug`,`l`.`titulo` AS `titulo`,`l`.`capa_img` AS `capa_img`,`lp`.`capitulo_atual` AS `capitulo_atual`,`lp`.`percentual` AS `percentual`,`lp`.`ultima_leitura` AS `ultima_leitura`,`lp`.`concluido` AS `concluido` from (`leitor_progresso` `lp` left join `livros` `l` on((`l`.`slug` = `lp`.`livro_slug`))) order by `lp`.`ultima_leitura` desc;

INSERT INTO `vw_ultimas_leituras` VALUES
('1', 'o-peso-do-horizonte', 'O Peso do Horizonte', 'img/contos/o-peso-do-horizinte.webp', '1', '100.00', '2026-06-14 21:10:33', '0'),
('1', 'o-quarto-passageiro', 'O Quarto Passageiro', 'img/contos/o-quarto-passageiro', '1', '0.00', '2026-06-14 20:45:46', '0'),
('1', 'a-penultima-pagina', 'A Penúltima Página', 'img/contos/a-penultima-pagina.webp', '1', '14.30', '2026-06-14 19:15:42', '0'),
('1', 'o-labirinto-dos-espelhos', 'O Labirinto dos Espelhos', 'img/contos/o-labirinto-dos-espelhos.webp', '1', '37.50', '2026-06-14 19:15:41', '0'),
('1', 'a-setima-lei', 'A Sétima Lei', 'img/a-setima-lei.jpg', '1', '10.60', '2026-06-14 19:15:27', '0'),
('1', 'o-farol-do-afogado', 'O farol do afogado', 'img/o-farol-do-afogado-conto.jpg', '1', '100.00', '2026-06-14 09:31:26', '0'),
('14', 'cartas-do-passado', 'Cartas do Passado', 'img/cartas-do-passado.jpg', '1', '2.30', '2026-06-13 20:09:09', '0'),
('13', 'cartas-do-passado', 'Cartas do Passado', 'img/cartas-do-passado.jpg', '1', '1.90', '2026-06-13 16:05:38', '0'),
('10', 'a-setima-lei', 'A Sétima Lei', 'img/a-setima-lei.jpg', '0', '13.90', '2026-06-13 14:34:02', '0'),
('10', 'cartas-do-passado', 'Cartas do Passado', 'img/cartas-do-passado.jpg', '1', '3.10', '2026-06-13 14:33:17', '0');

SET FOREIGN_KEY_CHECKS = 1;
-- Fim do backup
