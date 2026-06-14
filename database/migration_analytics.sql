-- =====================================================================
-- ROBÉRIO DIÓGENES — migration_analytics.sql
-- Tabelas de Business Intelligence para Marketing
-- Execute no phpMyAdmin antes de usar o tracking.js
-- =====================================================================

-- ── Tabela de sessões (UTMs, dispositivo, landing page) ───────────
CREATE TABLE IF NOT EXISTS `analytics_sessoes` (
  `id`           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `session_id`   VARCHAR(64)    NOT NULL             COMMENT 'ID gerado pelo JS (rd_timestamp_random)',
  `usuario_id`   INT UNSIGNED   DEFAULT NULL         COMMENT 'NULL = visitante anônimo',
  `utm_source`   VARCHAR(100)   DEFAULT NULL         COMMENT 'utm_source da URL',
  `utm_medium`   VARCHAR(100)   DEFAULT NULL,
  `utm_campaign` VARCHAR(200)   DEFAULT NULL,
  `utm_term`     VARCHAR(200)   DEFAULT NULL,
  `utm_content`  VARCHAR(200)   DEFAULT NULL,
  `dispositivo`  ENUM('desktop','mobile','tablet') NOT NULL DEFAULT 'desktop',
  `idioma`       VARCHAR(10)    DEFAULT NULL         COMMENT 'navigator.language',
  `referrer`     VARCHAR(500)   DEFAULT NULL,
  `landing_page` VARCHAR(500)   DEFAULT NULL         COMMENT 'Primeira URL da sessão',
  `pagina_tipo`  VARCHAR(50)    DEFAULT NULL         COMMENT 'livro | post | leitor | home | autor ...',
  `ip_hash`      VARCHAR(64)    DEFAULT NULL         COMMENT 'SHA-256 do IP (LGPD)',
  `iniciada_em`  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_session` (`session_id`),
  KEY `idx_utm_source`   (`utm_source`),
  KEY `idx_utm_campaign` (`utm_campaign`),
  KEY `idx_dispositivo`  (`dispositivo`),
  KEY `idx_usuario`      (`usuario_id`),
  KEY `idx_iniciada`     (`iniciada_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Sessões anônimas e autenticadas com dados de origem de tráfego';

-- ── Tabela de eventos personalizados ─────────────────────────────
CREATE TABLE IF NOT EXISTS `analytics_eventos` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id`      VARCHAR(64)  NOT NULL,
  `usuario_id`      INT UNSIGNED DEFAULT NULL,
  `tipo_evento`     VARCHAR(80)  NOT NULL  COMMENT 'ViewContent | Lead | Leitura_Progresso | Download_Amostra | Tempo_Pagina ...',
  `conteudo_slug`   VARCHAR(200) DEFAULT NULL,
  `conteudo_titulo` VARCHAR(300) DEFAULT NULL,
  `params`          JSON         DEFAULT NULL  COMMENT 'Parâmetros extras do evento (capitulo, percentual, formato, etc.)',
  `tempo_permanencia` INT UNSIGNED DEFAULT NULL COMMENT 'Segundos na página (preenchido pelo evento tempo_pagina)',
  `registrado_em`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_session`     (`session_id`),
  KEY `idx_tipo`        (`tipo_evento`),
  KEY `idx_slug`        (`conteudo_slug`),
  KEY `idx_usuario`     (`usuario_id`),
  KEY `idx_registrado`  (`registrado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Eventos de comportamento: leitura, downloads, leads, tempo de permanência';

-- ── View de BI: Canal vs. Retenção de Leitura ─────────────────────
-- "Qual canal traz o leitor que passa mais tempo lendo?"
CREATE OR REPLACE VIEW `bi_canal_retencao` AS
SELECT
  COALESCE(s.utm_source, 'direto')              AS canal,
  COALESCE(s.utm_medium, '(none)')              AS meio,
  COUNT(DISTINCT s.session_id)                  AS total_sessoes,
  COUNT(DISTINCT CASE WHEN e.tipo_evento = 'Leitura_Progresso' THEN e.session_id END) AS leitores_ativos,
  ROUND(AVG(CASE WHEN e.tipo_evento IN ('ViewContent','Tempo_Pagina')
                 THEN e.tempo_permanencia END))   AS media_segundos_pagina,
  ROUND(AVG(CASE WHEN e.tipo_evento = 'Leitura_Progresso'
                 THEN JSON_UNQUOTE(JSON_EXTRACT(e.params, '$.percentual')) + 0
                 END))                            AS media_percentual_leitura
FROM analytics_sessoes s
LEFT JOIN analytics_eventos e ON e.session_id = s.session_id
WHERE s.iniciada_em >= DATE_SUB(NOW(), INTERVAL 90 DAY)
GROUP BY canal, meio
ORDER BY leitores_ativos DESC;

-- ── View de BI: Livro vs. Engajamento ─────────────────────────────
-- "Qual livro/post retém mais o leitor?"
CREATE OR REPLACE VIEW `bi_conteudo_engajamento` AS
SELECT
  e.conteudo_slug,
  MAX(e.conteudo_titulo)                               AS titulo,
  COUNT(DISTINCT e.session_id)                         AS total_visualizacoes,
  ROUND(AVG(e.tempo_permanencia))                      AS media_segundos,
  MAX(CASE WHEN e.tipo_evento='Leitura_Progresso'
           THEN JSON_UNQUOTE(JSON_EXTRACT(e.params,'$.percentual'))+0
           END)                                        AS max_percentual_lido,
  COUNT(CASE WHEN e.tipo_evento='Download_Amostra' THEN 1 END) AS downloads_amostra,
  COUNT(CASE WHEN e.tipo_evento='Lead'             THEN 1 END) AS leads_gerados
FROM analytics_eventos e
WHERE e.registrado_em >= DATE_SUB(NOW(), INTERVAL 90 DAY)
  AND e.conteudo_slug IS NOT NULL
GROUP BY e.conteudo_slug
ORDER BY total_visualizacoes DESC;

-- ── Relatório rápido: últimos 30 dias ─────────────────────────────
-- Execute para ver um resumo imediato:
-- SELECT * FROM bi_canal_retencao;
-- SELECT * FROM bi_conteudo_engajamento LIMIT 20;
