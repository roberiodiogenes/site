-- ================================================================
-- ROBÉRIO DIÓGENES — migration_configuracoes.sql
-- Painel de Configurações + Temas Sazonais
-- Executar no phpMyAdmin ou via mysql CLI
-- ================================================================

-- ── Tabela de configurações (chave-valor agrupadas) ────────────
CREATE TABLE IF NOT EXISTS configuracoes (
    chave       VARCHAR(100)  NOT NULL,
    valor       TEXT          DEFAULT NULL,
    tipo        ENUM('string','boolean','integer','json') DEFAULT 'string',
    grupo       VARCHAR(50)   DEFAULT 'geral',
    PRIMARY KEY (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabela de temas sazonais ───────────────────────────────────
-- Apenas 2 cores principais (ouro + ferrugem); as demais são
-- derivadas automaticamente pelo backend na rota tema_ativo.
CREATE TABLE IF NOT EXISTS temas_sazonais (
    id           INT           AUTO_INCREMENT PRIMARY KEY,
    nome         VARCHAR(100)  NOT NULL,
    slug         VARCHAR(100)  NOT NULL,
    ativo        TINYINT(1)    NOT NULL DEFAULT 1,
    prioridade   SMALLINT      NOT NULL DEFAULT 0,
    dia_inicio   TINYINT UNSIGNED NOT NULL,   -- 1-31
    mes_inicio   TINYINT UNSIGNED NOT NULL,   -- 1-12
    dia_fim      TINYINT UNSIGNED NOT NULL,
    mes_fim      TINYINT UNSIGNED NOT NULL,
    cor_ouro     VARCHAR(7)    NOT NULL DEFAULT '#B8860B',
    cor_ferrugem VARCHAR(7)    NOT NULL DEFAULT '#8B3A2A',
    criado_em    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Seed: configurações padrão ─────────────────────────────────
INSERT IGNORE INTO configuracoes (chave, valor, tipo, grupo) VALUES
-- Informações do site
('site_nome',              'Robério Diógenes',         'string',  'site'),
('site_slogan',            'Escritor Independente',    'string',  'site'),
('site_copyright',         '',                         'string',  'site'),

-- Acesso e segurança
('permitir_cadastros',     '1',                        'boolean', 'acesso'),
('modo_manutencao',        '0',                        'boolean', 'acesso'),
('mensagem_manutencao',    'Site em manutenção. Voltamos em breve.', 'string', 'acesso'),

-- Uploads
('upload_tamanho_max_kb',  '2048',                     'integer', 'uploads'),
('upload_formatos_imagem', 'jpg,jpeg,png,webp',        'string',  'uploads'),
('upload_formatos_doc',    'pdf,epub',                 'string',  'uploads'),

-- E-mail e notificações
('smtp_host',              '',                         'string',  'email'),
('smtp_porta',             '587',                      'integer', 'email'),
('smtp_usuario',           '',                         'string',  'email'),
('smtp_senha',             '',                         'string',  'email'),
('smtp_criptografia',      'tls',                      'string',  'email'),
('email_remetente',        '',                         'string',  'email'),
('email_nome_remetente',   'Robério Diógenes',         'string',  'email'),
('email_admin',            '',                         'string',  'email'),

-- SEO & Analytics
('analytics_id',           '',                         'string',  'seo'),
('tag_manager_id',         '',                         'string',  'seo'),
('pixel_facebook',         '',                         'string',  'seo'),
('og_image_padrao',        '',                         'string',  'seo'),

-- Redes Sociais
('social_instagram',       '',                         'string',  'social'),
('social_twitter',         '',                         'string',  'social'),
('social_facebook',        '',                         'string',  'social'),
('social_tiktok',          '',                         'string',  'social'),
('social_youtube',         '',                         'string',  'social'),
('social_linkedin',        '',                         'string',  'social');

-- ── Seed: 17 temas sazonais ────────────────────────────────────
-- Nota: datas fixas anuais (dia/mês); ajuste pelo painel se necessário.
-- Temas com mesma data: ajuste prioridade para escolher qual aparece.
-- Ano Novo cruza o ano (dez→jan); o backend trata esse caso.

INSERT IGNORE INTO temas_sazonais
    (nome, slug, ativo, prioridade, dia_inicio, mes_inicio, dia_fim, mes_fim, cor_ouro, cor_ferrugem)
VALUES
-- 1. Ano Novo (28 dez → 3 jan — cruza o ano)
('Ano Novo',                        'ano-novo',                1,  1, 28, 12,  3,  1, '#A8B8D0', '#1A2C5A'),
-- 2. Carnaval (8 fev → 5 mar — datas aproximadas, ajuste se necessário)
('Carnaval',                        'carnaval',                1,  2,  8,  2,  5,  3, '#9B59B6', '#16A085'),
-- 3. Dia Internacional da Mulher (6 → 8 mar)
('Dia Internacional da Mulher',     'dia-mulher',              1,  3,  6,  3,  8,  3, '#8E24AA', '#C62828'),
-- 4. Dia Internacional do Livro Infantil (30 mar → 2 abr)
('Dia Internacional do Livro Infantil', 'dia-livro-infantil',  1,  4, 30,  3,  2,  4, '#D81B60', '#7B1FA2'),
-- 5. Dia do Livro (18 → 23 abr)
('Dia do Livro',                    'dia-livro',               1,  5, 18,  4, 23,  4, '#9A6B1A', '#6B3A28'),
-- 6. Dia das Mães (5 → 11 mai — 2º domingo; ajuste o dia pelo painel)
('Dia das Mães',                    'dia-maes',                1,  6,  5,  5, 11,  5, '#C2185B', '#7B1FA2'),
-- 7. Dia dos Namorados (10 → 12 jun)
('Dia dos Namorados',               'dia-namorados',           1,  7, 10,  6, 12,  6, '#B71C1C', '#880E4F'),
-- 8. Dia da Literatura Brasileira (23 → 25 jul)
('Dia da Literatura Brasileira',    'dia-literatura-brasileira', 1, 8, 23,  7, 25,  7, '#2E7D32', '#E65100'),
-- 9. Dia dos Pais (7 → 11 ago — 2º domingo; ajuste o dia pelo painel)
('Dia dos Pais',                    'dia-pais',                1,  9,  7,  8, 11,  8, '#1565C0', '#0D47A1'),
-- 10. 7 de Setembro (5 → 8 set)
('7 de Setembro',                   'sete-setembro',           1, 10,  5,  9,  8,  9, '#F9A825', '#1B5E20'),
-- 11. Dia Nacional do Escritor (17 → 19 set)
('Dia Nacional do Escritor',        'dia-escritor',            1, 11, 17,  9, 19,  9, '#8D6E63', '#4E342E'),
-- 12. Nossa Senhora Aparecida (10 → 12 out)
('Nossa Senhora Aparecida',         'nossa-senhora-aparecida', 1, 12, 10, 10, 12, 10, '#1565C0', '#9C7B00'),
-- 13. Dia das Crianças (10 → 12 out — mesma data que N.Sra.; prioridade define qual ativa)
('Dia das Crianças',                'dia-criancas',            0, 13, 10, 10, 12, 10, '#FF6F00', '#00838F'),
-- 14. Halloween (27 → 31 out)
('Halloween',                       'halloween',               1, 14, 27, 10, 31, 10, '#E65100', '#4A148C'),
-- 15. Finados (31 out → 2 nov)
('Finados',                         'finados',                 1, 15, 31, 10,  2, 11, '#7B1FA2', '#E65100'),
-- 16. 15 de Novembro (13 → 15 nov)
('15 de Novembro',                  'quinze-novembro',         1, 16, 13, 11, 15, 11, '#2E7D32', '#F9A825'),
-- 17. Natal (1 → 25 dez)
('Natal',                           'natal',                   1, 17,  1, 12, 25, 12, '#C62828', '#2E7D32');
