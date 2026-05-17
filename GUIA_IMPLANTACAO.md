# Guia de Implantação — roberiodiogenes.com
## Backend MySQL + PHP no HostGator

---

## ESTRUTURA DE ARQUIVOS

```
roberiodiogenes.com/
├── index.html          ← Página principal (já criada)
├── subscribe.php       ← Recebe inscrições do formulário
├── .htaccess           ← Segurança e cache
├── config/
│   ├── db.php          ← Credenciais do banco (NUNCA tornar público)
│   └── setup.sql       ← Script para criar as tabelas
└── admin/
    └── index.php       ← Painel administrativo
```

---

## PASSO 1 — Criar o banco de dados no cPanel (HostGator)

1. Acesse **hpanel.hostgator.com.br** e faça login
2. Vá em **cPanel → Bancos de Dados MySQL**
3. Em "Criar novo banco de dados", digite: `rdsite`
   - O HostGator adicionará seu prefixo automaticamente → ex: `roberio_rdsite`
   - Anote o nome completo gerado
4. Em "Usuários MySQL", crie um novo usuário:
   - Nome: `rduser` → será criado como ex: `roberio_rduser`
   - Senha: crie uma senha forte (use o gerador do cPanel)
   - Anote o nome e a senha
5. Em "Adicionar usuário ao banco", selecione:
   - Usuário: `roberio_rduser`
   - Banco: `roberio_rdsite`
   - Privilégios: marque **TODAS** as permissões
   - Clique em "Adicionar"

---

## PASSO 2 — Criar as tabelas (phpMyAdmin)

1. No cPanel, vá em **phpMyAdmin**
2. No painel esquerdo, clique no seu banco (`roberio_rdsite`)
3. Clique na aba **SQL** (na barra superior)
4. Abra o arquivo `config/setup.sql` em um editor de texto
5. Copie todo o conteúdo e cole na caixa de texto do phpMyAdmin
6. Clique em **Executar**
7. Você deve ver as tabelas criadas: `newsletter`, `newsletter_log`, `admin_users`

---

## PASSO 3 — Configurar as credenciais

Abra o arquivo `config/db.php` em um editor de texto (ex: VS Code ou Bloco de Notas) e substitua os valores:

```php
define('DB_HOST', 'localhost');           // Não mude
define('DB_NAME', 'roberio_rdsite');      // ← Seu banco
define('DB_USER', 'roberio_rduser');      // ← Seu usuário
define('DB_PASS', 'SuaSenhaForte123!');   // ← Sua senha
```

Salve o arquivo.

---

## PASSO 4 — Enviar os arquivos via FileZilla

### Instalando o FileZilla (gratuito)
- Baixe em: https://filezilla-project.org/download.php

### Configurando a conexão FTP no HostGator
1. No cPanel → **Contas FTP** → anote ou crie as credenciais FTP
2. No FileZilla, clique em **Arquivo → Gerenciador de Sites → Novo Site**
3. Preencha:
   - Protocolo: **FTP - Protocolo de Transferência de Arquivos**
   - Servidor: `ftp.roberiodiogenes.com` (ou seu IP do HostGator)
   - Porta: `21`
   - Modo de logon: **Normal**
   - Usuário e Senha: suas credenciais FTP
4. Clique em **Conectar**

### Enviando os arquivos
No FileZilla, o painel direito mostra o servidor. Navegue até `public_html/`.

Envie os arquivos na seguinte ordem:

| Arquivo local              | Destino no servidor              |
|---------------------------|----------------------------------|
| `.htaccess`               | `public_html/.htaccess`         |
| `subscribe.php`           | `public_html/subscribe.php`     |
| `index.html`              | `public_html/index.html`        |
| `config/db.php`           | `public_html/config/db.php`     |
| `config/setup.sql`        | `public_html/config/setup.sql`  |
| `admin/index.php`         | `public_html/admin/index.php`   |

**Atenção:** O arquivo `config/setup.sql` pode ser apagado do servidor após executar no phpMyAdmin — ele não é necessário online.

---

## PASSO 5 — Testar

1. Acesse **roberiodiogenes.com** no navegador
2. Vá até a seção "Inscreva-se" e tente inserir um e-mail
3. Você deve ver a mensagem: *"Inscrição realizada com sucesso!"*
4. Verifique no phpMyAdmin → tabela `newsletter` se o e-mail foi salvo

### Testar o painel admin
1. Acesse **roberiodiogenes.com/admin/**
2. Login inicial:
   - Usuário: `admin`
   - Senha: `RD@2025admin`
3. **IMPORTANTE:** Troque a senha imediatamente após o primeiro login (veja abaixo)

---

## PASSO 6 — Trocar a senha do admin

No phpMyAdmin, acesse a aba SQL e execute:

```sql
UPDATE admin_users
SET password = '$2y$12$NOVA_HASH_AQUI'
WHERE username = 'admin';
```

Para gerar a nova hash, peça ao Claude:
> "Gere um hash bcrypt para a senha: MinhaNovaSenh@2025"

---

## SEGURANÇA — Checklist

- [ ] Credenciais do banco anotadas em local seguro
- [ ] Senha do admin trocada após primeiro login
- [ ] Arquivo `setup.sql` apagado do servidor após uso
- [ ] `.htaccess` enviado (protege a pasta `/config`)
- [ ] SSL ativo no HostGator (cPanel → SSL/TLS → ativar Let's Encrypt)

---

## SOLUÇÃO DE PROBLEMAS

| Sintoma | Causa provável | Solução |
|---------|---------------|---------|
| "Erro de conexão com o banco" | Credenciais erradas em `db.php` | Verifique `DB_NAME`, `DB_USER`, `DB_PASS` |
| Formulário não responde | `subscribe.php` não foi enviado | Verifique se o arquivo está em `public_html/` |
| Painel admin em branco | PHP desativado ou erro de sintaxe | Verifique a versão PHP no cPanel (use 8.1+) |
| 403 Forbidden no /admin | Permissão errada | No FileZilla, clique direito → Permissões → 644 para arquivos, 755 para pastas |

---

*Guia gerado para roberiodiogenes.com — HostGator Brasil*
