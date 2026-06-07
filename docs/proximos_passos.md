# Análise Completa e Próximos Passos
**Robério Diógenes — roberiodiogenes.com**  
**Atualizado: junho/2026 · Baseado em análise completa do código**

---

## O QUE ESTÁ FUNCIONANDO

### Pagamentos — totalmente operacional

O `backend/pagamento.php` tem credenciais reais do Mercado Pago e implementa quatro fluxos completos:

- **Compra avulsa** — checkout individual por livro
- **Carrinho múltiplo** — até 20 livros em uma compra
- **Assinatura** — quatro planos (mensal R$29,90 / trimestral R$69,90 / semestral R$119,90 / anual R$154,80)
- **Presente (gift)** — 20% de desconto, e-mail com link de resgate para o presenteado

O webhook recebe confirmações do Mercado Pago automaticamente, atualiza o status no banco e dispara e-mail de confirmação. O `acesso.php` verifica compra avulsa + assinatura ativa corretamente antes de liberar o leitor.

### Autenticação

Login, logout, cadastro, recuperação de senha e exclusão de conta funcionam. O `register.php` já dispara o e-mail de boas-vindas automaticamente no cadastro. A estrutura de Google OAuth existe completa; as credenciais é que estão em placeholder.

### Leitor Online

Epub.js integrado, progresso salvo no banco, anotações e marcações de texto, preferências tipográficas (fonte, tamanho, fundo, largura), controle de acesso por compra/assinatura.

### Blog / Diário

Posts dinâmicos com paywall parcial, áudio player, enquetes vinculadas, curtidas, comentários aninhados com moderação e log de flags. Clusters Hub & Spoke (admin + pillar pages). Ao publicar: newsletter dispatch + push automático + ping Google/Bing.

### E-mail Marketing

Newsletter com double opt-in, preferências e origem rastreada (de qual página o lead veio, inclusive para páginas de livros individuais via HTTP Referer). Exit-intent popup com 3 triggers (mouse no topo, 40 segundos, scroll rápido). Segmentação por interesse literário no painel de marketing. Os dois cron jobs estão escritos, corretos e prontos — só precisam ser ativados no cPanel.

### SEO

Sitemap dinâmico, Schema.org completo (Book, BlogPosting, Person, FAQPage, CollectionPage), hreflang pt-BR + x-default, Open Graph + Twitter Card em todas as páginas, landing pages transacionais para categorias literárias.

### Jurídico

Termos de Uso (leitor, pirataria, assinatura), Política de Privacidade LGPD completa (9 direitos do titular), Política de Reembolso (7 dias CDC, avulsa vs assinatura), Central de Ajuda (FAQ acordeão com busca em tempo real, Schema.org FAQPage). Cookie banner LGPD em todas as páginas. Rodapé jurídico em todas as páginas de livros e no leitor.

### Admin

Painel completo com 13 módulos: Dashboard, Usuários, Assinaturas, Compras, Livros, Blog, Enquetes, Clusters, Comentários, Marketing, Bio, Lista de Espera, Automações, Push.

---

## O QUE NÃO FUNCIONA AGORA

### 🔴 Críticos — impedem uso em produção

**1. SMTP não configurado para produção**
Em desenvolvimento usa Mailpit (porta 1025). Em produção, está definido como SMTP local do Hostgator sem autenticação — entregabilidade péssima, e-mails irão para spam ou não chegarão. Sem isso: boas-vindas, confirmações de compra, recuperação de senha e newsletter não funcionam de verdade.
> Solução: configurar SendGrid (gratuito até 100/dia) ou Zoho Mail em `backend/mailer.php`.

**2. Google OAuth quebrado**
`GOOGLE_CLIENT_ID` e `GOOGLE_CLIENT_SECRET` em `config.php` têm valor literal `'SEU_GOOGLE_CLIENT_ID'`. O botão "Entrar com Google" aparece mas falha.
> Solução: criar credenciais em console.cloud.google.com.

**3. OneSignal não configurado**
`RD_ONESIGNAL_APP_ID = 'SEU_ONESIGNAL_APP_ID'` em dois arquivos. O SDK carrega, o prompt aparece, mas nenhuma notificação é enviada.
> Solução: criar conta em onesignal.com e preencher App ID.

**4. Crons não ativados**
Os dois scripts estão prontos e corretos, mas nenhum job foi cadastrado no cPanel. Nem lembrete de leitura nem recuperação de carrinho está rodando.
> Solução: Admin → Automações → seguir o guia de configuração no cPanel.

**5. Credenciais MP hardcoded**
`MP_PUBLIC_KEY` e `MP_ACCESS_TOKEN` estão dentro de `backend/pagamento.php` em vez de `config.php`. Se o arquivo vazar (erro de configuração de servidor), as credenciais ficam expostas.
> Solução: mover para `config.php` e referenciar como constante em pagamento.php.

### 🟡 Funcionais mas incompletos

**6. Migrations não executadas em produção**
As tabelas `usuario_interesses`, `leitor_lembretes_enviados` e a coluna `origem` na newsletter não existem em produção até que `banco_unificado.sql` seja executado. Os crons e o rastreamento de interesse falham silenciosamente por isso.

**7. `presente.html` possivelmente ausente**
O webhook envia e-mail com link `SITE_URL/presente.html?token=...` para o presenteado resgatar o livro, mas essa página não aparece no inventário de arquivos. O fluxo de presente pode resultar em erro 404 no resgate.
> Verificar se existe. Se não, criar a página.

**8. HTTPS não redireciona**
O `.htaccess` tem o redirecionamento HTTP → HTTPS comentado. Após configurar o SSL no Hostgator, é necessário descomentar para forçar HTTPS em todos os acessos.

**9. `push-notifications.js` ausente nas páginas de livros individuais**
As 14 páginas de livros receberam o banner de cookies e links jurídicos, mas não têm o script de push. Usuários que chegam diretamente por uma página de livro não recebem o convite de notificação.

**10. Página 404 personalizada ausente**
Não existe arquivo de erro customizado. O Apache exibe uma página genérica em URLs inválidas.

---

## O QUE FALTA DO ROADMAP ORIGINAL

| # | Funcionalidade | Observação |
|---|---|---|
| 1 | Micro-enquetes no leitor (95%) | Infra de enquetes pronta. Falta o trigger no leitor.js |
| 2 | Up-sell no Checkout (bump inline) | Oferta de assinatura trimestral antes do redirect ao MP |
| 3 | Cupom de Indicação | Aguarda 200+ usuários cadastrados |
| 4 | Funil de Onboarding por e-mail | Sequência 1/3/7 dias — cron dedicado |
| 5 | Página de "Obrigado" com upsell | `pagamento/sucesso.html` existe mas sem upsell |
| 6 | Avaliação na Amazon | Modal após 100% de leitura |

---

## PRÓXIMOS PASSOS — PRIORIDADE

### Fase 0 · Antes de ir a ar (bloqueadores de produção)

- [ ] Executar `database/banco_unificado.sql` no phpMyAdmin do Hostgator
- [ ] Configurar SMTP real em `backend/mailer.php` (SendGrid ou Zoho)
- [ ] Preencher credenciais do banco em `backend/config.php`
- [ ] Mover `MP_PUBLIC_KEY` e `MP_ACCESS_TOKEN` para `config.php`
- [ ] Verificar existência de `presente.html` — criar se não existir
- [ ] Descomentar redirecionamento HTTPS no `.htaccess` raiz
- [ ] Testar fluxo completo de compra em produção (Pix R$1,00 de teste)

### Fase 1 · Primeiros dias em produção

- [ ] Criar conta OneSignal → preencher App ID em `backend/push.php` e `js/push-notifications.js`
- [ ] Ativar 2 crons no cPanel (guia em Admin → Automações)
- [ ] Configurar Google OAuth no Google Cloud Console
- [ ] Criar página 404 personalizada
- [ ] Adicionar `push-notifications.js` nas páginas de livros individuais

### Fase 2 · Curto prazo (próximas sessões de desenvolvimento)

- [ ] **Up-sell no Checkout** — bump inline de assinatura trimestral antes do redirect ao MP
- [ ] **Página de "Obrigado"** — capa do livro comprado + sugestão de próximo + link para o Diário
- [ ] **Micro-enquetes no leitor** — modal ao 95% de progresso ("O que achou do final?")

### Fase 3 · Médio prazo

- [ ] **Funil de Onboarding** — cron: D+1 trecho gratuito · D+3 post do Diário · D+7 cupom 20%
- [ ] **Avaliação na Amazon** — modal após 100% de leitura com link direto
- [ ] **Cupom de Indicação** — após 200+ usuários cadastrados

---

## Histórico de versões e sprints

### Sprints 1–7 (maio de 2025)
Base do projeto: autenticação, layout responsivo, sistema de temas, Newsletter, área do leitor, perfil, recuperação de senha, Google OAuth (estrutura), favoritos, avaliações, downloads.

### Sprint 8 (jan–abr/2026)
Integração Mercado Pago (compra avulsa, carrinho, assinatura, presente), Leitor epub.js, Blog dinâmico com paywall, Clusters Hub & Spoke, SEO avançado, Bio link-in-bio.

### Sprint 9 (junho/2026)
- Lista de espera / pré-lançamento (admin + landing + disparo)
- Cron de abandono de leitura (corrigido e pronto para ativar)
- Cron de carrinho abandonado (pronto para ativar)
- Painel de automações no admin
- Rastreamento de origem nas inscrições de newsletter
- Exit-intent popup
- Push notifications OneSignal (Fase 1)
- Segmentação por interesse literário + marketing panel expandido
- Páginas jurídicas completas (Termos, Privacidade, Reembolso, Ajuda/FAQ)
- Banner LGPD
- Rodapé jurídico em todas as páginas
- banco_unificado.sql — schema definitivo único

---

*Documento gerado com base em análise completa do código-fonte — junho/2026*
