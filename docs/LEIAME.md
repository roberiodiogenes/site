# LEIAME — Guia de Instalação e Deploy

Instruções para rodar o site localmente no **XAMPP** e depois fazer o upload para o **Hostgator**.

---

## 1. Pré-requisitos

| Ferramenta | Versão mínima | Download |
|---|---|---|
| XAMPP | 8.0+ | https://www.apachefriends.org |
| Git | qualquer | https://git-scm.com |
| Navegador moderno | — | — |

---

## 2. ⚠ Regra de ouro — sempre use o Apache

**Nunca abra os arquivos diretamente pelo Windows Explorer** (`C:/xampp/htdocs/...`).  
O PHP só funciona quando servido pelo Apache. Use sempre o endereço HTTP:

```
✅ CORRETO:  http://localhost/site/
❌ ERRADO:   C:/xampp/htdocs/site/index.html
```

---

## 3. Instalação local com XAMPP

### 3.1 Clonar o repositório

```bash
cd C:/xampp/htdocs          # Windows
# cd /Applications/XAMPP/htdocs   # macOS

git clone https://github.com/SEU_USUARIO/SEU_REPOSITORIO.git site
cd site
```

### 3.2 Criar o banco de dados

1. Inicie o **XAMPP Control Panel** e ligue **Apache** e **MySQL**
2. Acesse: http://localhost/phpmyadmin
3. Clique em **Novo** → Nome: `roberio_site` → Collation: `utf8mb4_unicode_ci` → **Criar**
4. Selecione `roberio_site` → aba **SQL** → cole o conteúdo de `backend/setup.sql` → **Executar**

### 3.3 Configurar o backend

```bash
cp backend/config.example.php backend/config.php
```

Abra `backend/config.php` e verifique (padrão XAMPP):
```php
define('DB_USER', 'root');
define('DB_PASS', '');        // sem senha
define('DB_NAME', 'roberio_site');
```

> ⚠ `config.php` está no `.gitignore` — nunca é enviado ao GitHub.

### 3.4 Verificar instalação

Acesse o diagnóstico:
```
http://localhost/site/backend/diagnostico.php
```
Todos os itens devem aparecer em **verde**. **Apague ou renomeie o arquivo após os testes.**

### 3.5 Testar funcionalidades

```
http://localhost/site/                         → Página inicial
http://localhost/site/autor.html               → Página do autor
http://localhost/site/cadastro.html            → Cadastro
http://localhost/site/login.html               → Login
http://localhost/site/recuperar-senha.html     → Recuperação de senha*
http://localhost/site/leitor/index.html        → Área do leitor (requer login)
http://localhost/site/leitor/perfil.html       → Perfil do leitor
```

*Em modo local, após solicitar recuperação de senha, o link aparece diretamente na tela (não é enviado por e-mail).

---

## 4. Configurar Login com Google

1. Acesse https://console.cloud.google.com/
2. Crie um projeto → **APIs e Serviços → Credenciais → Criar credencial → OAuth 2.0**
3. Tipo: **Aplicativo da Web**
4. Origens JS autorizadas: `http://localhost`
5. URIs de redirecionamento: `http://localhost/site/backend/auth/google-callback.php`
6. Copie Client ID e Client Secret para `backend/config.php`:
```php
define('GOOGLE_CLIENT_ID',     'COLE_AQUI');
define('GOOGLE_CLIENT_SECRET', 'COLE_AQUI');
```

---

## 5. Deploy no Hostgator

### 5.1 Preparar config.php para produção

```php
define('AMBIENTE', 'producao');
define('DB_USER', 'usuario_cpanel');
define('DB_PASS', 'senha_cpanel');
define('DB_NAME', 'nome_banco_cpanel');
define('GOOGLE_CLIENT_ID',     'SEU_ID');
define('GOOGLE_CLIENT_SECRET', 'SEU_SECRET');
define('GOOGLE_REDIRECT_URI',  'https://roberiodiogenes.com/backend/auth/google-callback.php');
define('SITE_URL', 'https://roberiodiogenes.com');
define('JWT_SECRET', 'FRASE_SECRETA_LONGA_DIFERENTE_DA_LOCAL');
```

### 5.2 Banco de dados no Hostgator

1. cPanel → **Bancos de Dados MySQL** → criar banco + usuário + atribuir todos os privilégios
2. cPanel → **phpMyAdmin** → selecionar banco → importar `backend/setup.sql`

### 5.3 Upload dos arquivos

**Via Gerenciador de Arquivos do cPanel:**
1. Acesse cPanel → Gerenciador de Arquivos → `public_html/`
2. Faça upload de todos os arquivos (exceto pasta `.git/`)
3. Use o `config.php` já preenchido com dados de produção

**Via FTP (FileZilla):**
- Host, usuário, senha e porta 21 estão no cPanel → **Contas FTP**

### 5.4 SSL/HTTPS

No cPanel → **SSL/TLS → Let's Encrypt** → ativar para `roberiodiogenes.com`.

Atualize também as URIs no Google Console para usar `https://`.

### 5.5 Checklist pós-deploy

- [ ] Apagar `backend/diagnostico.php` do servidor
- [ ] Confirmar que `backend/config.php` não está acessível pelo navegador
- [ ] Testar cadastro, login e newsletter em produção
- [ ] Verificar HTTPS ativo

---

## 6. Estrutura de arquivos sensíveis

| Arquivo | Git? | Motivo |
|---|---|---|
| `backend/config.php` | ❌ NÃO | Contém credenciais |
| `backend/config.example.php` | ✅ SIM | Modelo sem dados reais |
| `backend/diagnostico.php` | ⚠ Remover | Ferramenta de diagnóstico |
| `.gitignore` | ✅ SIM | Controla o que vai ao Git |

---

*Dúvidas: diogenes.escritor@gmail.com*
