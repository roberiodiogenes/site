# Robério Diógenes — Site Oficial

Site pessoal do escritor Robério Diógenes, desenvolvido em HTML, CSS, JavaScript e PHP com MySQL.

**Domínio:** [roberiodiogenes.com](https://roberiodiogenes.com)  
**Hospedagem:** Hostgator  
**Stack:** HTML5 · CSS3 (variáveis CSS) · JavaScript Vanilla · PHP 8+ · MySQL 5.7+

---

## Estrutura do projeto

```
site/
├── index.html                  # Página inicial
├── autor.html                  # Página do autor
├── login.html                  # Login de usuário
├── cadastro.html               # Cadastro de usuário
├── recuperar-senha.html        # Recuperação de senha
├── resetar-senha.html          # Redefinição de senha (via link)
├── privacidade.html            # Política de privacidade (LGPD)
├── termos.html                 # Termos de uso
├── 404.html                    # Página de erro
│
├── css/
│   ├── variables.css           # Sistema de design: temas, fontes, cores, componentes
│   ├── index.css               # Estilos exclusivos da home
│   ├── autor.css               # Estilos exclusivos da página do autor
│   └── auth.css                # Menu dropdown de usuário logado (nav)
│
├── js/
│   ├── global.js               # Temas, sons, acessibilidade, partículas (todas as páginas)
│   ├── api-client.js           # Comunicação JS ↔ PHP + menu de usuário logado
│   ├── busca.js                # Busca em tempo real (index.html e autor.html)
│   ├── index.js                # Scripts exclusivos da home (contador de visitas)
│   └── script-autor.js         # Scripts exclusivos da página do autor
│
├── img/                        # Imagens, favicon, capas de livros
│
├── leitor/
│   ├── index.html              # Área restrita — dashboard do leitor
│   └── perfil.html             # Perfil do leitor (dados, senha, segurança)
│
├── backend/
│   ├── config.php              # ⚠ NÃO está no Git — copie de config.example.php
│   ├── config.example.php      # Modelo de configuração (seguro para versionar)
│   ├── setup.sql               # Esquema completo do banco de dados
│   ├── newsletter.php          # POST: inscrição na newsletter
│   ├── visitas.php             # GET: contador de visitas únicas
│   ├── diagnostico.php         # Ferramenta de diagnóstico local (apagar após uso)
│   └── auth/
│       ├── register.php        # POST: cadastro com e-mail e senha
│       ├── login.php           # POST: login
│       ├── logout.php          # POST: logout
│       ├── sessao.php          # GET: verificar sessão ativa
│       ├── perfil.php          # GET/POST: dados do perfil
│       ├── mudar-senha.php     # POST: alterar senha (usuário logado)
│       ├── recuperar.php       # POST: solicitar link de recuperação
│       ├── resetar-senha.php   # POST: redefinir senha com token
│       ├── google-url.php      # GET: gerar URL OAuth do Google
│       └── google-callback.php # Callback OAuth Google
│
└── docs/
    ├── README.md               # Esta documentação (também na raiz do repositório)
    ├── LEIAME.md               # Guia de instalação XAMPP + deploy Hostgator
    └── proximos_passos.md      # Histórico e tarefas pendentes
```

---

## Funcionalidades implementadas

| Funcionalidade | Status |
|---|---|
| Design responsivo com 3 temas (claro, noturno, alto contraste) | ✅ |
| Acessibilidade (aria-labels, pular navegação, controle de fonte) | ✅ |
| Sistema de partículas e sons | ✅ |
| Cadastro e login com e-mail/senha | ✅ |
| Login com Google (OAuth 2.0) | ✅ |
| Newsletter integrada ao banco de dados | ✅ |
| Recuperação e redefinição de senha | ✅ |
| Área do leitor (rota protegida) | ✅ |
| Dashboard de perfil do leitor | ✅ |
| Contador de visitas únicas por dia | ✅ |
| Busca em tempo real (index + autor) | ✅ |
| Rate limiting anti-spam | ✅ |
| Página do autor com carrossel e timeline | ✅ |
| Botão "Entrar" adaptativo por tema | ✅ |

---

## Configuração inicial

Veja `docs/LEIAME.md` para instruções detalhadas de instalação local (XAMPP) e deploy (Hostgator).

---

© 2025 Robério Diógenes · Cascavel, Ceará, Brasil
