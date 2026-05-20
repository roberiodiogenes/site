# Próximos Passos — Robério Diógenes Site

---

## ✅ Concluído

### Sprint 1 — Base e autenticação
- [x] index.html com design, layout, temas e acessibilidade
- [x] Sistema de temas (claro / noturno / alto contraste)
- [x] Cadastro e login com e-mail e senha
- [x] Login com Google (OAuth 2.0)
- [x] Newsletter integrada ao banco de dados
- [x] Área do leitor protegida (leitor/index.html)
- [x] Rate limiting anti-spam

### Sprint 2 — Home e busca
- [x] Contador de visitas únicas por dia (backend/visitas.php)
- [x] Busca em tempo real nas seções (js/busca.js)
- [x] Botão Entrar com class btn-entrar em todas as páginas

### Sprint 3 — Perfil e recuperação de senha
- [x] leitor/perfil.html responsivo — dashboard completo
- [x] Atualização de dados pessoais (backend/auth/perfil.php)
- [x] Alteração de senha (backend/auth/mudar-senha.php)
- [x] Recuperação de senha por link (backend/auth/recuperar.php + resetar-senha.php)
- [x] api-client.js com BASE_URL dinâmico (funciona em subpastas)

### Sprint 4 — Página do Autor
- [x] autor.html com CSS e JS próprios
- [x] Busca ativada na página do autor
- [x] Newsletter real na página do autor

### Sprint 5 — Botão de usuário logado
- [x] Correção definitiva: appearance: none no .nav-usuario-btn (remove fundo branco do navegador)
- [x] Estilos explícitos por tema (noturno e alto contraste)
- [x] auth.css carregado em todas as páginas incluindo leitor/perfil.html
- [x] Links do dropdown corrigidos por subpasta (prefixo automático)

### Sprint 6 — Páginas de livros, blog e contato
- [x] 11 páginas de livros individuais (livros/*.html)
- [x] global.js + api-client.js + livros-shared.js em todas as páginas de livros
- [x] data-livro="slug" no body de cada página para identificar o livro
- [x] Comentários reais — backend/comentarios.php (POST + GET com moderação)
- [x] Formulário de contato real — backend/contato.php
- [x] Newsletter real em blog.html, contato.html e todas as páginas de livros
- [x] js/livros-shared.js — enviarComentario, submeterNewsletter, carregarComentarios
- [x] Tabelas `comentarios` e `contato` adicionadas ao setup.sql
- [x] livros/.htaccess criado
- [x] blog.html e livros.html com api-client.js

---

## 🔲 Pendentes

### Backend e funcionalidades
- [ ] Executar o novo setup.sql no phpMyAdmin para criar tabelas `comentarios` e `contato`
- [ ] Envio real de e-mail no formulário de contato (PHPMailer / SendGrid)
- [ ] Envio real de e-mail na recuperação de senha
- [ ] Sistema de download de capítulos (backend/downloads.php)
- [ ] backend/auth/deletar-conta.php — exclusão de conta (LGPD)
- [ ] .htaccess raiz: ativar redirecionamento HTTP→HTTPS após SSL no Hostgator

### Páginas a criar
- [ ] leitor/perfil.html — upload de foto de avatar
- [ ] Painel simples de administração (ver comentários, aprovar, ver contatos)

### Páginas a completar
- [ ] blog.html — posts reais (atualmente mostra cards estáticos)
- [ ] livros/a-marca-da-besta.html — aguardando lançamento (countdown funcional)

---

*Atualizado: maio de 2025*

# 20/05/2026
1. Botão de deletar conta no perfil ainda não funcionando;
2. Aplicar a solução do botão entrar (que fica na or branca) às páginas (que foram acrescentadas) livros.thml, blog.thml, contato.html;
3. Às páginas dos livros (dentro da pasta livros), devem conter agora um botão para marcar como favoritos, um botão para o leitor classificar (marcar de 0 a 5 estrelas), esses botões devem estar disponíveis apenas se o usuário estiver logado em sua conta. Essas preferencias (quantidade de estrelas, favoritar, devem aparecer no leitor/perfil.html na aba "Favoritos"). O botão para download do capitulo deve ser dois, dando opção baixar o formato PDF ou o formato ePub. Além disso, o visitante só poderá baixar se estiver cadastrado e logado no site em seu perfil. No perfil do usuário (leitor/perfil.html), deve constar o número de downloads que ele realizou e quais foram (a mostra gratuita para download), isso deve estar na aba "Downloads". Na página livors.html, cada livro deve conter agora o número geral de todos os downloads (capítulos gratuitos). Para facilitar, estou enviando o arquivo download.zip em anexo (não precisa ler os conteúdos, no momento eles são apenas exemplos dos arquivos que eu substituirei em breve, pelos verdadeiros.), note que dentro da pasta, que ficará localizada na raiz do site, existe as pastas pdf e a pasta epub, com seus respectivos arquivos. Crie todos os arquivos necessários para que estás páginas funcionem perfeitamente e se precisar, reestruture o backend/setup.sql para inserir novas tabelas;

4. Para trabalhar com o Gemini
    1. SEO das principais páginas do site;
        [*] index.html;
        [*] livros.html
        [*] contato.html;
        [] blog.html;
        [*] autor.html;

