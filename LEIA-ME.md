# LEIA-ME — Testando o site localmente com XAMPP

Guia completo para rodar o site **roberiodiogenes.com** no seu computador
antes de publicar no HostGator.

---

## O que é o XAMPP?

XAMPP é um pacote gratuito que instala no seu PC um servidor local com:
- **Apache** — o servidor web (equivalente ao HostGator)
- **MySQL/MariaDB** — o banco de dados
- **PHP** — a linguagem dos arquivos `.php`
- **phpMyAdmin** — interface visual para o banco de dados

Com ele, o site roda no seu computador exatamente como rodará no servidor.

---

## PASSO 1 — Instalar o XAMPP

1. Acesse: **https://www.apachefriends.org/pt_br/index.html**
2. Clique em **"Baixar XAMPP para Windows"**
3. Execute o instalador baixado (`xampp-windows-x64-*.exe`)
4. Na tela de seleção de componentes, mantenha marcados:
   - ✅ Apache
   - ✅ MySQL
   - ✅ PHP
   - ✅ phpMyAdmin
5. Escolha a pasta de instalação (padrão: `C:\xampp`) — **não mude**
6. Clique em **Next** até concluir e depois em **Finish**

> **Windows 10/11:** Se aparecer aviso do Firewall ao iniciar, clique em
> "Permitir acesso" para Apache e MySQL.

---

## PASSO 2 — Iniciar o XAMPP

1. Abra o **XAMPP Control Panel** (procure no Menu Iniciar)
2. Clique em **Start** ao lado de **Apache**
3. Clique em **Start** ao lado de **MySQL**
4. Ambos devem ficar com o fundo **verde** e o status **Running**

Se a porta 80 estiver ocupada (outro programa usando), o Apache não inicia.
Veja a seção **Solução de Problemas** no final deste guia.

---

## PASSO 3 — Organizar os arquivos do projeto

### 3.1 — Localizar a pasta do servidor local

A pasta onde os sites ficam é:
```
C:\xampp\htdocs\
```
Tudo que você colocar aqui fica acessível em `http://localhost/`

### 3.2 — Criar a pasta do projeto

1. Abra o **Explorador de Arquivos** do Windows
2. Navegue até `C:\xampp\htdocs\`
3. Crie uma nova pasta chamada: **`roberiodiogenes`**

### 3.3 — Copiar os arquivos do projeto

Copie os arquivos do repositório para `C:\xampp\htdocs\roberiodiogenes\`:

```
C:\xampp\htdocs\roberiodiogenes\
│
├── index.html
├── subscribe.php
├── visit.php
├── .htaccess
│
├── config\
│   ├── db.php          ← você vai editar este
│   └── setup.sql
│
├── admin\
│   └── index.php
│
└── img\
    ├── autor2.jpg
    └── jogo-das-mascaras.jpg
```

> **Atenção:** O arquivo `.htaccess` pode estar oculto no Windows.
> Para exibi-lo: Explorador de Arquivos → Exibir → marcar "Itens ocultos".

---

## PASSO 4 — Criar o banco de dados local

### 4.1 — Abrir o phpMyAdmin

Com o XAMPP rodando, abra o navegador e acesse:
```
http://localhost/phpmyadmin
```
Usuário: `root` | Senha: *(deixe em branco)*

### 4.2 — Criar o banco de dados

1. No menu lateral esquerdo, clique em **Novo** (ou "New")
2. No campo "Nome do banco de dados", digite: `roberiodiogenes`
3. No menu suspenso ao lado, selecione: `utf8mb4_unicode_ci`
4. Clique em **Criar**

### 4.3 — Criar as tabelas (executar o SQL)

1. Com o banco `roberiodiogenes` selecionado no painel esquerdo,
   clique na aba **SQL** (na barra superior central)
2. Abra o arquivo `config\setup.sql` com o Bloco de Notas ou VS Code
3. Selecione todo o conteúdo (`Ctrl+A`) e copie (`Ctrl+C`)
4. Cole (`Ctrl+V`) na caixa de texto do phpMyAdmin
5. Clique em **Executar**
6. Você verá as tabelas criadas:
   - `newsletter`
   - `newsletter_log`
   - `admin_users`
   - `visitas`
   - `visitas_log`

---

## PASSO 5 — Configurar as credenciais locais

Abra o arquivo `config\db.php` com o VS Code ou Bloco de Notas e altere:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'roberiodiogenes');   // nome do banco que você criou
define('DB_USER', 'root');              // usuário padrão do XAMPP
define('DB_PASS', '');                  // senha em branco no XAMPP local
define('DB_CHARSET', 'utf8mb4');
```

Salve o arquivo.

> ⚠️ **Importante:** Quando for enviar para o HostGator, você deverá
> restaurar os valores reais do servidor (nome do banco, usuário e senha
> criados no cPanel). Não envie o `db.php` com as credenciais do HostGator
> para o GitHub — o `.gitignore` já protege este arquivo.

---

## PASSO 6 — Testar o site

Abra o navegador e acesse:
```
http://localhost/roberiodiogenes/
```

### O que testar:

| Funcionalidade | Como testar |
|---|---|
| **Visual geral** | A página carrega com layout, fontes e imagens corretos |
| **Temas** | Clique em ☀ ☾ ◑ — o tema muda e é salvo ao recarregar |
| **Fonte** | Clique em A- e A+ — o texto aumenta/diminui em todas as seções |
| **Acessibilidade mobile** | Reduza a janela do navegador até ~400px ou use DevTools (F12 → ícone de celular) — o hambúrguer aparece e, ao clicar, os botões de acessibilidade aparecem no menu |
| **Formulário de inscrição** | Digite um e-mail e clique em Inscrever — deve aparecer mensagem de sucesso |
| **Verificar inscrição no banco** | phpMyAdmin → banco `roberiodiogenes` → tabela `newsletter` — o e-mail deve aparecer |
| **Contador de visitas** | O número no rodapé deve aparecer; ao recarregar, não deve incrementar |
| **Botão voltar ao topo** | Role a página para baixo — o botão ↑ aparece no canto inferior esquerdo |
| **Modal Entrar** | Clique em "Entrar" — o modal abre com as abas Login e Cadastrar |
| **Painel admin** | Acesse `http://localhost/roberiodiogenes/admin/` |

### Testando o painel admin:

1. Acesse `http://localhost/roberiodiogenes/admin/`
2. Login: `admin` | Senha: `RD@2025admin`
3. Você verá a lista de e-mails inscritos, estatísticas e botão de exportar CSV

---

## PASSO 7 — Testar em modo mobile (sem celular físico)

1. Com o site aberto no navegador, pressione **F12** para abrir o DevTools
2. Clique no ícone de **celular/tablet** na barra superior do DevTools
   (ou pressione `Ctrl + Shift + M`)
3. Selecione um dispositivo no menu suspenso (ex: "iPhone 12", "Galaxy S20")
4. Recarregue a página

Verifique:
- O menu hambúrguer aparece no lugar dos links de navegação
- Os botões de acessibilidade aparecem dentro do menu mobile
- O layout das seções empilha verticalmente
- As imagens se ajustam à tela
- O formulário de inscrição funciona em coluna única

---

## PASSO 8 — Testar em outros navegadores

Antes de publicar, teste em pelo menos:

| Navegador | Download |
|---|---|
| Google Chrome | chrome.google.com |
| Mozilla Firefox | firefox.com |
| Microsoft Edge | já instalado no Windows 10/11 |

---

## SOLUÇÃO DE PROBLEMAS

### Apache não inicia (porta 80 ocupada)

O Skype, IIS ou outro programa pode estar usando a porta 80.

**Opção A — Mudar a porta do XAMPP:**
1. No XAMPP Control Panel, clique em **Config** ao lado de Apache
2. Escolha `httpd.conf`
3. Procure por `Listen 80` e mude para `Listen 8080`
4. Salve e reinicie o Apache
5. Acesse: `http://localhost:8080/roberiodiogenes/`

**Opção B — Encerrar o processo:**
1. Abra o Prompt de Comando como administrador
2. Digite: `netstat -ano | findstr :80`
3. Anote o PID (último número)
4. Digite: `taskkill /PID [número] /F`
5. Tente iniciar o Apache novamente

---

### MySQL não inicia (porta 3306 ocupada)

Se você tiver o MySQL instalado separadamente no Windows:
1. Abra **Serviços** (procure no Menu Iniciar)
2. Encontre "MySQL" na lista
3. Clique com botão direito → **Parar**
4. Tente iniciar o MySQL do XAMPP novamente

---

### Página abre mas imagens não aparecem

Verifique se a pasta `img\` existe dentro de `C:\xampp\htdocs\roberiodiogenes\`
e se os arquivos `autor2.jpg` e `jogo-das-mascaras.jpg` estão dentro dela.

---

### Formulário mostra "Erro de conexão"

Verifique:
1. O MySQL está rodando no XAMPP (fundo verde)
2. O arquivo `config\db.php` tem as credenciais corretas para o ambiente local
3. O banco `roberiodiogenes` foi criado no phpMyAdmin
4. O SQL do `setup.sql` foi executado com sucesso

---

### `.htaccess` causa erro 500

No XAMPP local, o `.htaccess` pode gerar erro se o módulo `rewrite`
não estiver ativo. Para ativar:

1. No XAMPP Control Panel, clique em **Config** → `httpd.conf`
2. Procure por: `#LoadModule rewrite_module`
3. Remova o `#` do início da linha
4. Salve e **reinicie o Apache**

---

## FLUXO RESUMIDO

```
Instalar XAMPP
    ↓
Iniciar Apache + MySQL
    ↓
Copiar arquivos → C:\xampp\htdocs\roberiodiogenes\
    ↓
Criar pasta img\ e copiar as imagens
    ↓
phpMyAdmin → Criar banco "roberiodiogenes" → Executar setup.sql
    ↓
Editar config\db.php com credenciais locais (root / sem senha)
    ↓
Abrir http://localhost/roberiodiogenes/
    ↓
Testar todas as funcionalidades
    ↓
✅ Tudo OK? → Publicar no HostGator
```

---

*Guia preparado para roberiodiogenes.com — XAMPP no Windows*
