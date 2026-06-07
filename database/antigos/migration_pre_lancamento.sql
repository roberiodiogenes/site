-- ================================================================
--  ROBÉRIO DIÓGENES — migration: Lista de Espera (pré-lançamento)
--  Executar uma vez no banco roberio_site
--  Via phpMyAdmin ou: mysql -u root roberio_site < migration_pre_lancamento.sql
-- ================================================================

-- Tabela de campanhas de pré-lançamento
CREATE TABLE IF NOT EXISTS pre_lancamentos (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug            VARCHAR(100) NOT NULL,
    titulo          VARCHAR(200) NOT NULL,
    subtitulo       VARCHAR(300) NOT NULL DEFAULT '',
    descricao       TEXT,
    capa_img        VARCHAR(500) NOT NULL DEFAULT '',
    data_lancamento DATE         NULL DEFAULT NULL,
    brinde_titulo   VARCHAR(200) NOT NULL DEFAULT '',
    brinde_html     TEXT,          -- HTML do trecho/nota do autor enviado imediatamente
    ativo           TINYINT(1)   NOT NULL DEFAULT 1,
    lancado         TINYINT(1)   NOT NULL DEFAULT 0,  -- 1 após disparo do e-mail de lançamento
    criado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de leads por campanha
CREATE TABLE IF NOT EXISTS pre_lancamento_leads (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    lancamento_id       INT UNSIGNED NOT NULL,
    nome                VARCHAR(120) NOT NULL DEFAULT '',
    email               VARCHAR(255) NOT NULL,
    ip_hash             CHAR(64)     NOT NULL DEFAULT '',
    brinde_enviado      TINYINT(1)   NOT NULL DEFAULT 0,
    lancamento_enviado  TINYINT(1)   NOT NULL DEFAULT 0,
    inscrito_em         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_lead (lancamento_id, email),
    KEY idx_lancamento_id (lancamento_id),
    KEY idx_email         (email),
    CONSTRAINT fk_lead_lancamento
        FOREIGN KEY (lancamento_id) REFERENCES pre_lancamentos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
