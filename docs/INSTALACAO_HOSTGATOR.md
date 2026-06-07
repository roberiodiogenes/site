# Guia de Instalação — Hostgator
**roberiodiogenes.com · Versão 5.0 · Junho/2026**

Siga este guia na ordem exata. Cada etapa tem uma verificação de confirmação ao final.

---

## Pré-requisitos

Antes de começar, confirme que você tem acesso a:

- [ ] cPanel do Hostgator (login em hostgator.com.br → Minha Conta)
- [ ] Gerenciador de arquivos do cPanel **ou** cliente SFTP (recomendado: FileZilla ou VS Code + extensão SFTP)
- [ ] phpMyAdmin (disponível no cPanel)
- [ ] Domínio apontado para o Hostgator (propagação pode levar até 24h)
- [ ] SSL ativo no domínio (cPanel → SSL/TLS → Let's Encrypt → instalar)

---

## Etapa 1 — Banco de Dados

### 1.1 Criar o banco no cPanel

1. cPanel → **Banco de Dados MySQL** → "Criar novo banco de dados"
2. Nome: `roberio_site` (o cPanel adicionará seu prefixo automaticamente, ex: `usuario_roberio_site`)
3. **Criar novo usuário** com senha forte (anote — você vai precisar)
4. **Adicionar usuário ao banco** → marque "Todos os privilégios"

### 1.2 Executar o schema

1. cPanel → **phpMyAdmin** → clique no banco criado
2. Aba **SQL**
3. Clique em "Escolher arquivo" e selecione `database/banco_unificado.sql`  
   **OU** copie e cole o conteúdo do arquivo e clique em **Executar**
4. Aguarde — o script cria 38 tabelas, 3 views, 4 procedures e 2 eventos agendados

✅ **Verificar:** a aba "Estrutura" deve listar as tabelas começando por `admin_users`, `assinaturas`, `auth_log`…

> ⚠ **NÃO execute os arquivos `migration_*.sql` separados** — o `banco_unificado.sql` já inclui tudo.

### 1.3 Configurar senha do admin

O script já cria o usuário admin padrão:
- **Usuário:** `admin`
- **Senha:** `RD@2025admin`

**Troque a senha logo após o primeiro login** em Admin → perfil.

---

## Etapa 2 — Upload dos Arquivos

### 2.1 Via SFTP (recomendado)

Dados de acesso SFTP:
- **Host:** ftp.roberiodiogenes.com (ou o IP do servidor Hostgator)
- **Porta:** 21 (FTP) ou 22 (SFTP — preferível)
- **Usuário e senha:** seus dados de cPanel

Destino no servidor: `public_html/` (raiz do domínio principal)

Envie **todos os arquivos e pastas** do projeto, exceto:
- `.git/` (controle de versão — não enviar)
- `backend/config.php` (será criado manualmente na etapa 3)
- `database/` (não precisa estar no servidor)
- `docs/` (documentação — opcional)

### 2.2 Permissões de pastas (verificar após upload)

Via cPanel → Gerenciador de Arquivos → clique com botão direito → "Alterar permissões":

| Pasta | Permissão |
|---|---|
| `img/` (se houver upload) | 755 |
| `download/` | 755 |
| `backend/` | 755 |
| Arquivos `.php` | 644 |
| Arquivos `.html`, `.css`, `.js` | 644 |

✅ **Verificar:** acesse `https://roberiodiogenes.com` — deve exibir a home.

---

## Etapa 3 — Configuração do Backend

### 3.1 Configurar `backend/config.php`

No servidor, crie o arquivo `backend/config.php` copiando `backend/config.example.php` e preenchendo:

```php
// Banco de dados (use os dados do passo 1.1)
define('DB_HOST', 'localhost');
define('DB_USER', 'usuario_seuusuario');  // ← prefixo do cPanel + nome
define('DB_PASS', 'sua_senha_do_banco');
define('DB_NAME', 'usuario_roberio_site');

// Google OAuth (Etapa 6)
define('GOOGLE_CLIENT_ID',     'SEU_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'SEU_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI',  'https://roberiodiogenes.com/backend/auth/google-callback.php');

// URL base
define('SITE_URL', 'https://roberiodiogenes.com');

// Segurança — gere uma frase aleatória longa
define('JWT_SECRET', 'troque-por-uma-frase-secreta-longa-aleatoria-aqui-2026');
```

✅ **Verificar:** acesse `https://roberiodiogenes.com/backend/diagnostico.php` (se existir) ou tente fazer login no site.

### 3.2 Mover credenciais do Mercado Pago para config.php

No `backend/config.php`, adicione também:

```php
// Mercado Pago
define('MP_PUBLIC_KEY',   'APP_USR-fbf67b6a-...');  // copie de pagamento.php
define('MP_ACCESS_TOKEN', 'APP_USR-184457053...');   // copie de pagamento.php
```

Depois edite `backend/pagamento.php` e substitua as duas linhas `define('MP_...')` por:
```php
// Credenciais agora em config.php — não redefinir aqui
```

> ⚠ **Segurança:** nunca deixe credenciais hardcoded em arquivos que podem ser lidos se o PHP parar de funcionar.

### 3.3 Configurar SMTP para e-mail real

Edite `backend/mailer.php` — bloco de produção (dentro do `else`):

**Opção A — SendGrid (recomendado, gratuito até 100 e-mails/dia):**
1. Crie conta em [sendgrid.com](https://sendgrid.com) → Settings → API Keys → Create API Key (Full Access)
2. Em `mailer.php`, bloco produção:
```php
define('SMTP_HOST',   'smtp.sendgrid.net');
define('SMTP_PORT',   587);
define('SMTP_USER',   'apikey');          // literal "apikey"
define('SMTP_PASS',   'SUA_API_KEY');     // cole a chave gerada
define('SMTP_SEGURO', 'tls');
```

**Opção B — Zoho Mail:**
1. Crie conta em [zoho.com/mail](https://zoho.com/mail) → adicione o domínio roberiodiogenes.com
2. Configure os registros MX no cPanel → Zone Editor
3. Em `mailer.php`:
```php
define('SMTP_HOST',   'smtp.zoho.com');
define('SMTP_PORT',   587);
define('SMTP_USER',   'contato@roberiodiogenes.com');
define('SMTP_PASS',   'SUA_SENHA_ZOHO');
define('SMTP_SEGURO', 'tls');
```

✅ **Verificar:** use o formulário de contato do site e verifique se o e-mail chega.

---

## Etapa 4 — Forçar HTTPS

Edite o arquivo `.htaccess` na raiz do site. Localize o bloco comentado de HTTPS e descomente:

```apache
# Redirecionar HTTP → HTTPS (descomentar após SSL ativo)
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

✅ **Verificar:** acesse `http://roberiodiogenes.com` — deve redirecionar para `https://`.

---

## Etapa 5 — Cron Jobs (Automações)

Configure no cPanel → **Cron Jobs** → Add New Cron Job.

Descubra o usuário do cPanel em: cPanel → Informações da conta → Nome de usuário.

### 5.1 Cron — Abandono de Leitura (diário às 9h)

| Campo | Valor |
|---|---|
| Minute | 0 |
| Hour | 9 |
| Day | * |
| Month | * |
| Weekday | * |

**Comando:**
```
/usr/bin/php /home/USUARIO/public_html/backend/cron_lembrete_leitura.php
```

### 5.2 Cron — Carrinho Abandonado (a cada hora)

| Campo | Valor |
|---|---|
| Minute | 0 |
| Hour | * |
| Day | * |
| Month | * |
| Weekday | * |

**Comando:**
```
/usr/bin/php /home/USUARIO/public_html/backend/cron_carrinho_abandonado.php
```

### 5.3 Trocar o token dos crons (segurança)

Nos dois arquivos de cron, substitua o token padrão por uma string aleatória:
- `backend/cron_lembrete_leitura.php` → `define('CRON_TOKEN_LEITURA', 'SEU_TOKEN_ALEATORIO')`
- `backend/cron_carrinho_abandonado.php` → `define('TOKEN_CRON', 'SEU_TOKEN_ALEATORIO')`

Use o painel Admin → Automações para testar manualmente se os crons executam sem erros.

✅ **Verificar:** Admin → Automações → botão "Testar agora" deve mostrar output sem erros.

---

## Etapa 6 — Google OAuth

1. Acesse [console.cloud.google.com](https://console.cloud.google.com)
2. Crie um projeto (ex: "Roberio Diogenes Site")
3. APIs e Serviços → Credenciais → Criar credencial → **ID do cliente OAuth 2.0**
4. Tipo de aplicativo: **Aplicativo da Web**
5. URIs de redirecionamento autorizados:
   ```
   https://roberiodiogenes.com/backend/auth/google-callback.php
   ```
6. Copie o **Client ID** e o **Client Secret**
7. Cole em `backend/config.php` (veja etapa 3.1)

✅ **Verificar:** tente fazer login com Google no site.

---

## Etapa 7 — Push Notifications (OneSignal)

1. Acesse [onesignal.com](https://onesignal.com) → Create account (gratuito)
2. New App → nome: "Robério Diógenes" → **Web**
3. Site URL: `https://roberiodiogenes.com`
4. Default Icon URL: `https://roberiodiogenes.com/img/favicon.png`
5. Copie o **App ID** e a **REST API Key** (Settings → Keys & IDs)

**Preencher em dois arquivos:**

`backend/push.php` — linhas iniciais:
```php
define('ONESIGNAL_APP_ID',       'cole-o-app-id-aqui');
define('ONESIGNAL_REST_API_KEY', 'cole-a-rest-api-key-aqui');
```

`js/push-notifications.js` — linha inicial:
```js
const RD_ONESIGNAL_APP_ID = 'cole-o-app-id-aqui';
```

✅ **Verificar:** Admin → Push → o painel deve mostrar "Ativo" e exibir o número de subscribers.

---

## Etapa 8 — Verificações Finais

### Checklist de funcionamento

- [ ] Site carrega em `https://roberiodiogenes.com`
- [ ] HTTP redireciona para HTTPS
- [ ] Login e cadastro funcionam
- [ ] E-mail de boas-vindas chega após cadastro
- [ ] Formulário de contato envia e-mail
- [ ] Recuperação de senha funciona (e-mail chega)
- [ ] Página de livro exibe botão "Comprar" ou "Ler agora"
- [ ] Fluxo de compra redireciona para Mercado Pago
- [ ] Página de sucesso confirma compra após pagamento
- [ ] Leitor abre o epub corretamente
- [ ] Newsletter: inscrição + e-mail de confirmação chegam
- [ ] Admin acessível em `/admin/` com login correto
- [ ] Painel de automações mostra números (não erro de migration)
- [ ] Blog: posts dinâmicos carregam

### Teste de compra

Faça uma compra real de R$1,00 (se o Mercado Pago permitir) ou use o **ambiente sandbox** do MP:
- cPanel → MP Dashboard → Ativar modo teste
- Cartão de teste: `5031 4332 1540 6351` · CVV `123` · Validade `11/25`

---

## Referências úteis

| Recurso | Link |
|---|---|
| cPanel Hostgator | https://suporte.hostgator.com.br |
| Documentação Mercado Pago | https://www.mercadopago.com.br/developers |
| Google Cloud Console | https://console.cloud.google.com |
| OneSignal Dashboard | https://dashboard.onesignal.com |
| SendGrid | https://app.sendgrid.com |
| phpMyAdmin (local) | http://localhost/phpmyadmin |
| Admin do site | https://roberiodiogenes.com/admin/ |

---

## Estrutura de contatos e suporte

**Banco de dados:** use o phpMyAdmin do Hostgator para consultas e backups manuais.  
**Backups:** cPanel → Backup Wizard → Full Backup (semanal).  
**Logs de erro PHP:** cPanel → Métricas → Erros.  
**Log de e-mails:** verifique o `error_log` do PHP para rastrear falhas de SMTP.  

---

*Guia atualizado — junho/2026 · roberiodiogenes.com*
