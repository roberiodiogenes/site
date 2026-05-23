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

### Sprint 7 — Favoritos, Avaliações, Downloads protegidos
- [x] Tabela `livros` (catálogo com slugs e nomes de arquivos)
- [x] Tabela `favoritos` (por slug, com FK CASCADE)
- [x] Tabela `avaliacoes` (estrelas 1-5, upsert)
- [x] Tabela `downloads_log` (log de downloads por usuário)
- [x] backend/livros.php — estado, favoritar, avaliar, contadores, meus_favoritos
- [x] backend/downloads.php — servir arquivo protegido (só logado), meus_downloads
- [x] backend/auth/deletar-conta.php — exclusão com confirmação de senha
- [x] download/.htaccess — bloquear acesso direto aos arquivos
- [x] js/livros-shared.js v2 — favoritar, estrelas, baixarCapitulo(pdf/epub)
- [x] js/perfil.js — abas Favoritos e Downloads populadas do backend
- [x] perfil.html — botão deletar conta funcional
- [x] Botões PDF + ePub em todas as 11 páginas de livros
- [x] Contador de downloads em livros.html (ao vivo do banco)
- [x] auth.css adicionado em blog.html, contato.html, livros.html
- [x] ⚠ Executar novo setup.sql no phpMyAdmin (novas tabelas)


# 21/05/2026
1. [x] Botão entrar da página blog.html e contato.html sem formatação;
2. [x] Ajustes na página jogo-das-mascaras.html:
    - [x] Após fazer o comentário, esse comentário não aparece em comentários;
    - [x] Criar uma formatação melhor para os favoritar. avaliação e downloads (pdf e epub);
    - [x] Avaliar se estes botões (favoritar. avaliação e downloads) gar. Ou se estão no melhor lugar ou se precisão de um lugar mais estratégico;
    - [x] Otimizar o SEO da página para estar em conformidade com as boas práticas e com o Google, garantindo um excelente posicionamento da página nas pesquisas do google;

3. SEO das páginas dos livros
    1. [x] A marca da besta;
    2. [?] A sétima lei - Algumas imagens precisão do alt="";

# 22/05/2026 A sétima lei
    1. O campo de email, na sessão de download gratuito não funciona. O botão de download só deve funcionar se o visitante etiver logado. Refazer essa parte da página para funcione corretamente;

# 23/05/2026
    1. As marés secretas do amor - livros/mares-secretas.html - Botões favoritar sem formatação;
    2. Comentários em Lúmen não funcionam;
    3. Comentário em Rosas & Espnhos não funciona;
    4. Cartas do passado - livros/cartas-do-passado.html - Desconfigurada, comentáriod não funcionam;
    5. Caminhos de outono - livros/caminhos-de-outono.html - Comentários não funcionam;
    6. Das coisas que o amor faz - livros/das-coisas-que-o-amor-faz.html - Comentários não funionam;
    7. A marca da besta - livros/a-marca-da-besta.html - Atualizarpágina. Comentários não funcionam;
    