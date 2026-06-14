# Análise Completa e Próximos Passos
**Robério Diógenes — roberiodiogenes.com**  
**Atualizado: junho/2026 · Site LIVE em produção**

---

## STATUS ATUAL — SITE EM PRODUÇÃO

O site está no ar em `https://roberiodiogenes.com` com todas as funcionalidades principais operacionais.

---

## O QUE ESTÁ FUNCIONANDO EM PRODUÇÃO

### Pagamentos — Mercado Pago ✅
- Compra avulsa por livro
- Carrinho múltiplo (até 20 itens)
- Assinaturas (mensal R$29,90 / trimestral R$69,90 / semestral R$119,90 / anual R$154,80)
- Presente (gift) com 20% de desconto
- Webhook automático + e-mail de confirmação de compra
- Promo price com validade (`promo_ate`) — bug corrigido jun/2026
- Idempotência do webhook de assinatura — corrigido jun/2026

### Autenticação ✅
- Cadastro + e-mail de boas-vindas com PDF do conto gratuito
- Login / Logout / Recuperação de senha
- Exclusão de conta (LGPD)
- Google OAuth — **credenciais obtidas, aguarda configuração no config.php do servidor**

### Leitor Online ✅
- epub.js v0.3.93 — flow scrolled, manager continuous
- Progresso salvo no banco (percentual + CFI)
- Anotações e marcações de texto
- Preferências tipográficas (fonte, tamanho, fundo, largura)
- Controle de acesso (compra avulsa / assinatura / amostra 10%)
- Conquistas/medalhas por percentual de leitura
- Notas do autor vinculadas a posições CFI
- Todos os livros/contos carregam corretamente (incluindo "O Farol do Afogado" — corrigido jun/2026)

### Blog / Diário ✅
- Posts dinâmicos com paywall parcial
- Clusters Hub & Spoke
- Enquetes vinculadas a posts
- Comentários aninhados + moderação
- Push automático + newsletter dispatch ao publicar

### E-mail Marketing ✅
- E-mail de boas-vindas com download do conto (PHP mail() nativo — funcional)
- Newsletter com double opt-in e rastreamento de origem
- Exit-intent popup (3 triggers)
- Segmentação por interesse literário
- Campanhas manuais no painel admin
- Cron de carrinho abandonado — **script corrigido; aguarda ativação no cPanel**
- Cron de lembrete de leitura — **pronto; aguarda ativação no cPanel**

### Analytics ✅
- GTM (GTM-PZXC4SK8) — **publicado** em jun/2026
- GA4 (G-D4846SQWW1) — ativo via GTM
- Microsoft Clarity (x52noc8f87) — ativo
- BI interno: sessões, eventos, views bi_* no admin

### SEO ✅
- Schema.org completo (Book, Person, FAQ, BlogPosting)
- Sitemap dinâmico + hreflang pt-BR
- Landing pages transacionais
- Open Graph + Twitter Card em todas as páginas

### Jurídico ✅
- Termos, Privacidade, Reembolso, Central de Ajuda (FAQ)
- Banner LGPD (cookies)
- Rodapé jurídico em todas as páginas de livros e leitor

### Admin ✅
- Dashboard Analytics (4 abas: Visão Geral, Tráfego, Conteúdo, Usuários)
- 13 módulos: Usuários, Assinaturas, Compras, Livros, Blog, Enquetes, Clusters, Comentários, Marketing, Bio, Lista de Espera, Automações, Push

---

## PENDÊNCIAS ATUAIS

### 🔴 Segurança — fazer logo

**JWT_SECRET fraco no servidor**  
O `config.php` de produção tem frase literal como JWT_SECRET.  
→ Gerar novo: `php -r "echo bin2hex(random_bytes(32));"`  
→ Substituir manualmente no `backend/config.php` do servidor HostGator (nunca subir o config.php local)

### 🟡 Funcionais mas incompletos

**Crons não ativados no cPanel**  
Os scripts estão corretos e o TOKEN_CRON está centralizado em `config.php`.  
→ Configurar em cPanel → Cron Jobs:
```
# Carrinho abandonado — a cada hora
0 * * * *   /usr/bin/php /home/fra46117/public_html/backend/cron_carrinho_abandonado.php

# Lembrete de leitura — diário às 9h
0 9 * * *   /usr/bin/php /home/fra46117/public_html/backend/cron_lembrete_leitura.php
```

**Google OAuth — credenciais obtidas, falta configurar no servidor**  
Client ID e Secret já existem (obtidos jun/2026).  
→ Adicionar no `backend/config.php` do servidor (via cPanel File Manager):
```php
define('GOOGLE_CLIENT_ID',     '493743123645-odvk6ne...');
define('GOOGLE_CLIENT_SECRET', 'GOCSPX-...');
```

**OneSignal — sem App ID**  
SDK carrega e o prompt aparece, mas nenhuma notificação é enviada.  
→ Criar conta em onesignal.com → preencher App ID em `backend/push.php` e `js/push-notifications.js`

**Credenciais MP em pagamento.php**  
`MP_PUBLIC_KEY` e `MP_ACCESS_TOKEN` ainda estão hardcoded em `backend/pagamento.php` em vez de `config.php`.  
→ Mover para `config.php` e remover de `pagamento.php` (segurança)

---

## ROADMAP — PRÓXIMAS FUNCIONALIDADES

*Prioridade confirmada pelo Robério (junho/2026)*

| # | Funcionalidade | Observação |
|---|---|---|
| 1 | Micro-enquetes no leitor (95%) | Infra de enquetes pronta. Falta o trigger no leitor.js |
| 2 | Up-sell no Checkout (bump inline) | Oferta de assinatura trimestral antes do redirect ao MP |
| 3 | Página de "Obrigado" com upsell | `pagamento/sucesso.html` existe mas sem upsell |
| 4 | Avaliação na Amazon | Modal após 100% de leitura com link direto |
| 5 | Funil de Onboarding por e-mail | Sequência 1/3/7 dias — cron dedicado |
| 6 | Cupom de Indicação | Após 200+ usuários cadastrados |

---

## Histórico de sprints

### Sprints 1–7 (maio/2025)
Base: autenticação, layout responsivo, newsletter, leitor, perfil, recuperação de senha, Google OAuth (estrutura), favoritos, avaliações, downloads.

### Sprint 8 (jan–abr/2026)
Mercado Pago (compra avulsa, carrinho, assinatura, presente), Leitor epub.js, Blog dinâmico + paywall, Clusters Hub & Spoke, SEO avançado, Bio link-in-bio.

### Sprint 9 (junho/2026)
- Lista de espera / pré-lançamento
- Cron de abandono de leitura (corrigido)
- Cron de carrinho abandonado (corrigido — captura `em_checkout=1`)
- Painel de automações no admin
- Dashboard Analytics reescrito (4 abas + Chart.js)
- Exit-intent popup
- Push notifications OneSignal (estrutura)
- Segmentação por interesse literário + marketing panel
- Páginas jurídicas completas
- Banner LGPD
- Auditoria de pagamento (promo price, webhook idempotência, TOKEN_CRON)
- Leitor: conquistas, notas do autor, correção do spinner, correção do "O Farol do Afogado"
- banco_consolidado.sql v6.0

---

*Atualizado — junho/2026 · roberiodiogenes.com*
