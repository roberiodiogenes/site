# Contas e Credenciais de Serviços Externos
**Robério Diógenes — roberiodiogenes.com**

> ⚠ Este arquivo contém referências a credenciais sensíveis.  
> Está protegido por `docs/.htaccess` (Deny from all) — nunca versionar no GitHub.

---

## Analytics

### Microsoft Clarity
- **ID:** `x52noc8f87`
- **Dashboard:** https://clarity.microsoft.com/projects/view/x52noc8f87/gettingstarted

### Google Tag Manager
- **Container ID:** `GTM-PZXC4SK8`
- **Dashboard:** https://tagmanager.google.com/#/container/accounts/6360255900/containers/255152647
- **Status:** Publicado em 11/06/2026

### Google Analytics 4
- **Measurement ID:** `G-D4846SQWW1`
- **Stream:** Meu Site — https://www.roberiodiogenes.com
- **Dashboard:** https://analytics.google.com

---

## Google OAuth (Login Social)

- **Conta Google usada:** roberdioh@gmail.com
- **Client ID:** `493743123645-odvk6nemqp40357o2to5lajnj0lb411m.apps.googleusercontent.com`
- **Client Secret:** ver arquivo `backend/config.php` no servidor (nunca registrar aqui)
- **Data de criação:** 12/06/2026
- **URI de redirecionamento:** `https://roberiodiogenes.com/backend/auth/google-callback.php`

**Pendente:** inserir Client ID e Secret no `backend/config.php` do servidor HostGator.

---

## Mercado Pago

- **Painel:** https://www.mercadopago.com.br/developers/panel/app
- **Aplicação:** produção ativa
- **Public Key:** ver `backend/config.php` (ou `backend/pagamento.php` — pendente migração)
- **Access Token:** ver `backend/config.php` (ou `backend/pagamento.php` — pendente migração)
- **Webhook:** `https://roberiodiogenes.com/backend/pagamento.php?acao=webhook`
- **Mínimo de transação:** R$ 5,00 (abaixo disso o botão fica inativo)
- **Auto-pagamento:** bloqueado pelo MP (não é possível comprar com a própria conta)

**Cartão de teste (sandbox):**
```
Número:   5031 4332 1540 6351
Validade: 11/25
CVV:      123
CPF:      12345678909
```

---

## Painel Administrativo

- **URL:** https://roberiodiogenes.com/admin/
- **Usuário:** `admin`
- **Senha:** ver `admin_users` no banco (nunca registrar aqui)

---

## HostGator — Banco de Dados

- **Host:** localhost
- **DB Name:** `fra46117_roberio_site`
- **User/Pass:** ver `backend/config.php` do servidor
- **phpMyAdmin:** disponível no cPanel do HostGator
- **MySQL versão:** 5.7 (não suporta `ADD COLUMN IF NOT EXISTS`)

---

## Pendências de configuração

| Item | Ação |
|---|---|
| Google OAuth no servidor | Inserir Client ID/Secret no config.php do servidor |
| JWT_SECRET | Substituir por `bin2hex(random_bytes(32))` no config.php do servidor |
| Credenciais MP | Mover de `pagamento.php` para `config.php` |
| OneSignal | Criar conta e preencher App ID em `backend/push.php` e `js/push-notifications.js` |
| Crons | Ativar no cPanel (ver `docs/INSTALACAO_HOSTGATOR.md` seção 5) |
