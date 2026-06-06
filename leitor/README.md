# Leitor Online — Robério Diógenes
**Versão:** 3.0.0  
**Data:** 2026-05-31  
**Status:** Em desenvolvimento (fase de testes locais — XAMPP)

---

## Visão Geral

O Leitor Online é a interface de leitura de EPUBs do site roberiodiogenes.com.  
Permite que usuários cadastrados leiam livros e contos diretamente no navegador,  
com anotações, marcações, conquistas, progresso sincronizado e muito mais.

---

## Estrutura de Pastas

```
leitor/
├── index.html                  ← Interface principal do leitor
├── .htaccess                   ← Proteção de pastas sensíveis
├── README.md                   ← Este arquivo
│
├── css/
│   └── leitor.css              ← Estilos: temas, tipografia, painéis
│
├── js/
│   └── leitor.js               ← Lógica principal (epub.js, API calls, UI)
│
├── backend/
│   ├── acesso.php              ← Verifica permissão + serve EPUB via stream
│   ├── progresso.php           ← Salva/carrega progresso de leitura e prefs
│   ├── anotacoes.php           ← CRUD de anotações do leitor
│   ├── marcacoes.php           ← CRUD de highlights/marcações
│   ├── conquistas.php          ← Sistema de medalhas com envio de e-mail
│   ├── notas_autor.php         ← Notas do autor vinculadas a posição CFI
│   ├── ranking.php             ← Ranking de leitores (top + posição própria)
│   ├── erros.php               ← Reportar erros ortográficos
│   ├── feedback.php            ← Feedback ao concluir leitura
│   └── lembrete.php            ← (futuro) Lembretes para leituras abandonadas
│
├── epub/
│   ├── .htaccess               ← Deny from all (EPUBs nunca acessíveis diretamente)
│   ├── lumen.epub
│   ├── a-setima-lei.epub
│   ├── o-abismo-das-almas.epub
│   └── ... (demais EPUBs)
│
└── docs/
    └── uso.html                ← Documentação pública para o usuário
```

---

## Dependências Externas (CDN)

| Biblioteca | Versão | Uso |
|---|---|---|
| epub.js | 0.3.93 | Renderização de EPUBs no navegador |
| JSZip | 3.10.1 | Dependência do epub.js |
| Font Awesome | 6.5.0 | Ícones da interface |

Todas carregadas via CDN. Sem instalação de pacotes necessária.

---

## Banco de Dados — Tabelas do Leitor

| Tabela | Descrição |
|---|---|
| `leitor_progresso` | Posição CFI, percentual, tempo lido, status de conclusão |
| `leitor_preferencias` | Fonte, tamanho, espaçamento, largura, tema por usuário |
| `leitor_anotacoes` | Anotações vinculadas a posição CFI com cor personalizada |
| `leitor_marcacoes` | Highlights de trechos selecionados |
| `leitor_conquistas` | Medalhas por marco de leitura (25%, 50%, 75%, 100%) |
| `leitor_notas_autor` | Notas do autor inseridas pelo admin por posição CFI |
| `leitor_erros` | Erros ortográficos reportados pelos leitores |
| `leitor_feedback` | Avaliação e comentário ao concluir leitura |
| `leitor_lembretes` | Controle de lembretes para livros abandonados |
| `leitor_ranking` | Pontuação, livros lidos, contos lidos, streak diário |
| `leitor_metas` | Configurações do relógio de meta por usuário |

---

## Sistema de Acesso

```
Livro gratuito  → usuário logado
Livro comprado  → usuário logado + compra aprovada (sem expiração)
Assinatura ativa → acesso a toda a biblioteca enquanto vigente
```

O arquivo EPUB **nunca é exposto diretamente**. Todo acesso passa por  
`backend/acesso.php` que verifica permissão antes de fazer `readfile()`.

---

## Sistema de Conquistas

| Tipo | Marco | Pontos | E-mail |
|---|---|---|---|
| `inicio` | 1% lido | 5 pts | ✓ |
| `25pct` | 25% lido | 15 pts | ✓ |
| `50pct` | 50% lido | 25 pts | ✓ |
| `75pct` | 75% lido | 30 pts | ✓ |
| `100pct` | Concluído | 50 pts | ✓ |
| `velocidade` | (futuro) | 20 pts | ✓ |
| `maratona` | +3h contínuas | 35 pts | ✓ |

---

## Funcionalidades Implementadas

- [x] Renderização de EPUB via epub.js
- [x] Tela de seleção com biblioteca personalizada
- [x] Barra de progresso de leitura (topo)
- [x] Salvamento automático de progresso (30s + ao sair)
- [x] Preferências tipográficas (fonte, tamanho, espaçamento, largura, tema)
- [x] 3 temas: Claro, Sépia, Escuro
- [x] 3 fontes: Serifada, Sem Serifa, Manuscrita
- [x] Anotações com cor personalizada
- [x] Marcações/Highlights de texto
- [x] Menu de contexto ao selecionar texto
- [x] Notas do autor por posição CFI
- [x] Sistema de conquistas com envio de e-mail
- [x] Relógio de metas (relógio, cronômetro, regressivo)
- [x] Modo não perturbe (tela cheia, UI oculta)
- [x] Modo foco — área de foco para TDA/Dislexia
- [x] Reportar erros ortográficos
- [x] Feedback ao concluir a leitura
- [x] Indicar para amigo (WhatsApp, e-mail, link)
- [x] Navegação entre capítulos com seletor
- [x] Ranking de leitores
- [x] Service Worker (cache offline — a implementar)
- [ ] Lembrete para livros abandonados (backend pronto, cron pendente)
- [ ] Notas do autor — interface admin para inserção por CFI

---

## Como Adicionar um Novo Livro/Conto

1. Converter o arquivo para `.epub` (Calibre recomendado)
2. Nomear o arquivo conforme o slug: `meu-livro.epub`
3. Copiar para `leitor/epub/`
4. No phpMyAdmin ou `admin/livros.php`, inserir/atualizar o registro em `livros`
   com o campo `arquivo_epub = 'meu-livro.epub'`
5. O livro já aparece automaticamente para quem tiver acesso

---

## Configuração Inicial (XAMPP)

1. Importar `banco_completo_v2.sql` no phpMyAdmin
2. Copiar os EPUBs para `leitor/epub/`
3. Garantir que `leitor/epub/.htaccess` contém `Require all denied`
4. Acessar: `http://localhost/roberiodiogenes.com/leitor/`

---

## Produção (Hostgator)

- Ativar HTTPS antes de fazer deploy
- Descomentar redirect HTTPS no `.htaccess` raiz
- Verificar que `AllowOverride All` está ativo no VHost
- Testar o serve de EPUB via `backend/acesso.php?acao=servir&slug=lumen`
- Configurar cron para lembretes de leitura abandonada

---

## Changelog

### v3.0.0 (2026-05-31)
- Reescrita completa do zero
- Adotado epub.js como motor de renderização
- Implementado sistema de conquistas com e-mail
- Adicionado modo foco (TDA/Dislexia)
- Adicionado relógio de metas (3 modos)
- Notas do autor por posição CFI
- Menu de contexto ao selecionar texto
- Service Worker (estrutura base)
- Banco de dados unificado (v2)

### v2.x (legado — removido)
- Leitor HTML simples sem epub.js
- Sem anotações sincronizadas
- Sem sistema de conquistas
