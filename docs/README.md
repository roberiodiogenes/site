# Robério Diógenes — Site Oficial
**Documentação técnica completa · Versão 6.0 · Junho/2026**

**Domínio:** [roberiodiogenes.com](https://roberiodiogenes.com)  
**Hospedagem:** Hostgator (PHP 8.2, MySQL **5.7**, HTTPS via Let's Encrypt)  
**Dev local:** XAMPP · VS Code · Sincronização via SFTP  
**Stack:** HTML5 · CSS3 · JavaScript Vanilla · PHP 8.2 · MySQL 5.7  
**Sem framework** — PDO puro, sem Composer obrigatório

> ⚠ **MySQL 5.7 no HostGator** — `ALTER TABLE ADD COLUMN IF NOT EXISTS` não existe (só no MySQL 8.0+). Usar `ADD COLUMN` simples nas migrations.  

---

## Objetivos do site

- **Venda Direta** — livros e contos avulsos + assinatura, sem intermediários, via Mercado Pago
- **Tráfego Orgânico** — SEO técnico, Schema.org, blog com clusters Hub & Spoke
- **Autoridade Literária** — marca pessoal, clipping, estrutura E-E-A-T
- **E-mail Marketing** — captura segmentada, automações, campanhas manuais
- **Receita Recorrente** — clube de assinaturas (mensal/trimestral/semestral/anual)

---

## Estrutura de arquivos

```
roberiodiogenes.com/
│
├── ── PÁGINAS PÚBLICAS ─────────────────────────────────────────
├── index.html              # Home · exit-intent · push · cookies
├── livros.html             # Catálogo de obras
├── blog.html               # Diário do Escritor · clusters
├── autor.html              # Sobre o autor · Schema.org/Person
├── contato.html            # Formulário · Schema.org/FAQPage
├── bio.html                # Link-in-bio (Instagram e redes)
├── pre-lancamento.html     # Landing lista de espera (?slug=X)
├── presentear.html         # Presente digital com 20% de desconto
├── carrinho.html           # Carrinho de compras
├── login.html              # Login de usuário
│
├── ── PÁGINAS JURÍDICAS ────────────────────────────────────────
├── ajuda.html              # FAQ acordeão + busca · Schema FAQPage
├── termos.html             # Termos de Uso (leitor, pirataria, assinatura)
├── privacidade.html        # Política de Privacidade LGPD (9 direitos)
├── reembolso.html          # Política de Reembolso (CDC, 7 dias)
│
├── ── SUBPASTAS PRINCIPAIS ─────────────────────────────────────
├── livros/                 # 14 páginas individuais de livros
│   └── lumen.html, a-marca-da-besta.html … (+ 12 mais)
├── blog/
│   ├── post-template.html  # Template dinâmico (paywall, áudio, enquete)
│   └── cluster-template.html
├── pagamento/
│   ├── sucesso.html        # Retorno Mercado Pago — aprovado
│   ├── falha.html          # Retorno MP — falha
│   ├── pendente.html       # Retorno MP — pendente
│   └── assinatura.html     # Página de planos de assinatura
├── leitor/
│   └── index.html          # Biblioteca + leitor epub.js
│
├── ── BACKEND PHP ──────────────────────────────────────────────
├── backend/
│   ├── config.php          # ⚠ NÃO versionar — copiar de config.example.php
│   ├── config.example.php  # Modelo seguro para versionar
│   ├── mailer.php          # PHPMailer — todos os templates de e-mail
│   ├── pagamento.php       # Mercado Pago: compra, assinatura, presente, webhook
│   ├── acesso.php          # Verifica acesso do usuário a um livro
│   ├── compras.php         # Histórico de compras do usuário
│   ├── carrinho.php        # Persiste carrinho (coluna `itens`, NÃO `itens_json`)
│   ├── newsletter.php      # Double opt-in + rastreamento de origem
│   ├── blog_api.php        # REST blog: posts, curtidas, paywall, enquetes
│   ├── blog_upload.php     # Upload → WebP automático (max 100KB)
│   ├── bio.php             # API bio: links dinâmicos + analytics de cliques
│   ├── visitas.php         # Contador de visitas únicas
│   ├── pre-lancamento.php  # API lista de espera (campanhas + leads + disparo)
│   ├── push.php            # Wrapper OneSignal REST API
│   ├── presente.php        # Compra de presente
│   ├── resgatar-presente.php
│   ├── cron_carrinho_abandonado.php   # ⏰ Ativar no cPanel
│   ├── cron_lembrete_leitura.php      # ⏰ Ativar no cPanel
│   └── auth/
│       ├── login.php, register.php, logout.php, sessao.php
│       ├── perfil.php, mudar-senha.php, deletar-conta.php
│       ├── recuperar.php, resetar-senha.php
│       └── google-url.php, google-callback.php
│
├── ── ADMIN ────────────────────────────────────────────────────
├── admin/
│   ├── _admin.php          # Layout + sidebar (único include por página)
│   ├── .htaccess           # Allowlist de arquivos PHP permitidos
│   ├── index.php           # Dashboard
│   ├── blog.php            # CRUD posts + newsletter + push automático
│   ├── usuarios.php, assinaturas.php, compras.php
│   ├── livros.php, bio.php, enquetes.php, clusters.php
│   ├── comentarios.php     # Moderação com log de flags
│   ├── marketing.php       # Campanhas + segmentos por interesse
│   ├── pre-lancamento.php  # Gestão de listas de espera
│   ├── crons.php           # Painel de automações (stats + guia cPanel)
│   └── push.php            # Painel de push notifications
│
├── ── JAVASCRIPT ───────────────────────────────────────────────
├── js/
│   ├── global.js           # Temas, sons, acessibilidade, partículas
│   ├── api-client.js       # API.Newsletter.inscrever(email, origem)
│   ├── busca.js            # Busca em tempo real
│   ├── exit-intent.js      # Popup saída (3 triggers, cookie 365d)
│   ├── push-notifications.js  # OneSignal SDK + opt-in por contexto
│   ├── cookies-lgpd.js     # Banner LGPD: aceitar/essencial, 365d
│   ├── compra-livro.js     # Botão "Ler agora / Comprar" nas páginas de livros
│   ├── livros-shared.js    # Favoritar, avaliar, newsletter nos livros
│   ├── leitor.js           # Leitor online (epub.js wrapper)
│   ├── biblioteca.js       # Biblioteca do leitor
│   └── perfil.js           # Perfil do usuário
│
├── ── BANCO DE DADOS ───────────────────────────────────────────
├── database/
│   ├── banco_consolidado.sql # ← ARQUIVO PRINCIPAL (v6.0, junho/2026)
│   │                         #   42 tabelas, 5 views, 4 procedures, 2 eventos
│   │                         #   Inclui analytics_sessoes, analytics_eventos,
│   │                         #   configuracoes, temas_sazonais,
│   │                         #   cluster HG Wells + 6 posts
│   └── banco_unificado.sql   # Versão anterior (v5.0) — mantido como histórico
│   └── migration_*.sql       # Histórico de migrações (já incorporadas no v6.0)
│
├── ── SERVICE WORKERS ──────────────────────────────────────────
├── OneSignalSDKWorker.js   # Obrigatório na raiz para push notifications
├── OneSignalSDKUpdaterWorker.js
│
└── ── DOCUMENTAÇÃO ─────────────────────────────────────────────
    └── docs/
        ├── README.md              # Este arquivo
        ├── INSTALACAO_HOSTGATOR.md # Guia completo de deploy
        ├── proximos_passos.md     # Análise + roadmap atualizado
        └── Configurar-Mercado-Pago.md
```

---

## Banco de dados — tabelas principais

| Grupo | Tabelas |
|---|---|
| Autenticação | `usuarios`, `password_reset`, `magic_links`, `admin_users`, `auth_log` |
| Catálogo | `livros`, `favoritos`, `avaliacoes`, `downloads_log` |
| Blog | `posts`, `curtidas`, `posts_lidos`, `comentarios`, `clusters`, `enquetes*` |
| Newsletter | `newsletter` (com `origem`), `campanhas`, `newsletter_disparos` |
| Interesses | `usuario_interesses` (segmentação por categoria lida) |
| Bio | `bio_links`, `bio_config`, `bio_clicks` |
| Comércio | `planos`, `assinaturas`, `compras`, `carrinhos` (col: `itens`), `presentes`, `cupons` |
| Leitor | `leitor_progresso`, `leitor_anotacoes`, `leitor_marcacoes`, `leitor_preferencias`, `leitor_conquistas`, `leitor_notas_autor` |
| Automações | `leitor_lembretes_enviados` |
| Pré-lançamento | `pre_lancamentos`, `pre_lancamento_leads` |
| Analytics | `visitas`, `visitas_log`, `analytics_sessoes` (UTMs/dispositivo), `analytics_eventos` (comportamento/BI) |
| Configurações | `configuracoes` (painel chave-valor), `temas_sazonais` (17 temas sazonais com paletas) |

---

## Funcionalidades implementadas (junho/2026)

| Módulo | Funcionalidade | Status |
|---|---|---|
| **Auth** | Cadastro + e-mail de boas-vindas | ✅ |
| **Auth** | Login/Logout/Recuperação de senha | ✅ |
| **Auth** | Google OAuth (estrutura pronta) | ⚠ Credenciais obtidas — configurar no servidor |
| **Auth** | Exclusão de conta (LGPD) | ✅ |
| **Pagamentos** | Mercado Pago — compra avulsa | ✅ |
| **Pagamentos** | Mercado Pago — assinatura | ✅ |
| **Pagamentos** | Mercado Pago — carrinho múltiplo | ✅ |
| **Pagamentos** | Mercado Pago — presente (gift) | ✅ |
| **Pagamentos** | Webhook automático + e-mail confirmação | ✅ |
| **Leitor** | Epub.js + progresso + anotações | ✅ |
| **Leitor** | Controle de acesso (compra/assinatura) | ✅ |
| **Blog** | Posts dinâmicos + paywall parcial | ✅ |
| **Blog** | Clusters Hub & Spoke | ✅ |
| **Blog** | Enquetes vinculadas a posts | ✅ |
| **Blog** | Comentários aninhados + moderação | ✅ |
| **E-mail** | Newsletter double opt-in + origem | ✅ |
| **E-mail** | Exit-intent popup | ✅ |
| **E-mail** | Cron abandono de leitura | ⚠ Aguarda ativação cPanel |
| **E-mail** | Cron carrinho abandonado | ⚠ Aguarda ativação cPanel |
| **E-mail** | Segmentação por interesse literário | ✅ |
| **E-mail** | Campanhas manuais no admin | ✅ |
| **Push** | OneSignal SDK + opt-in inteligente | ⚠ App ID pendente |
| **Push** | Disparo automático ao publicar post | ⚠ App ID pendente |
| **Analytics** | GTM (GTM-PZXC4SK8) + GA4 via GTM | ✅ Container publicado (jun/2026) |
| **Analytics** | Microsoft Clarity (x52noc8f87) | ✅ |
| **Analytics** | BI interno: sessões + eventos + views bi_* | ✅ |
| **SEO** | Schema.org completo (Book, Person, FAQ…) | ✅ |
| **SEO** | Sitemap dinâmico + hreflang | ✅ |
| **SEO** | Landing pages transacionais | ✅ |
| **Jurídico** | Termos, Privacidade, Reembolso, Ajuda | ✅ |
| **Jurídico** | Banner LGPD (cookies) | ✅ |
| **Jurídico** | Rodapé jurídico em todas as páginas | ✅ |
| **Bio** | Link-in-bio + analytics de cliques | ✅ |
| **Lista de espera** | Campanhas de pré-lançamento | ✅ |
| **Admin** | Painel completo (todos os módulos) | ✅ |

---

## Convenções de desenvolvimento

```
PDO + prepared statements — nunca concatenar SQL
IPs armazenados como SHA256 (LGPD)
Imagens → WebP automático no upload (max 100KB, GD nativo)
Slugs: minúsculas, sem acentos, [a-z0-9-]
Sessão usuário: PHPSESSID | Admin: rd_admin_sess (session_name separado)
Respostas JSON: { ok: true, ... } ou { ok: false, erro: "msg" }
admin/: require_once '_admin.php' abre HTML completo; página fecha com $ADMIN_FOOTER_HTML
carrinhos: coluna `itens` (NÃO `itens_json` — o banco_unificado já usa o nome correto)
leitor_progresso: coluna `ultima_leitura` (NÃO `atualizado_em`)
```

---

## Credenciais e configurações pendentes

| Item | Arquivo | Status |
|---|---|---|
| DB Hostgator | `backend/config.php` | ✅ Configurado em produção |
| Google OAuth Client ID/Secret | `backend/config.php` do servidor | ⚠ Credenciais obtidas — inserir no servidor |
| SMTP | `backend/mailer.php` | ✅ PHP mail() nativo (funcional no HostGator) |
| Mercado Pago Public Key + Access Token | `backend/pagamento.php` | ⚠ Mover para config.php (segurança) |
| Webhook secret do MP | `backend/config.php` | ✅ Registrado |
| OneSignal App ID | `backend/push.php` + `js/push-notifications.js` | ⚠ Pendente (onesignal.com) |
| TOKEN_CRON | `backend/config.php` | ✅ Centralizado em config.php |
| JWT_SECRET | `backend/config.php` do servidor | ⚠ Substituir por string aleatória forte |

---

## Guia de instalação

Ver `docs/INSTALACAO_HOSTGATOR.md` para o passo a passo completo de deploy.

---

*© 2026 Robério Diógenes · Cascavel, Ceará, Brasil*  
*Documentação atualizada com análise completa do estado atual — junho/2026*
