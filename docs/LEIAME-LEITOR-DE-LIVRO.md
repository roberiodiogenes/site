# 📖 Leitor de Livros Online — Guia de Integração
**Robério Diógenes — roberiodiogenes.com**

---

## Estrutura dos arquivos criados

```
roberiodiogenes.com/
│
├── leitor/
│   └── livro.html                  ← PÁGINA DO LEITOR (nova)
│
├── livros-conteudo/
│   └── lumen/
│       ├── cap01.html              ← Capítulo 1 — A Vigília (teste)
│       └── cap02.html              ← Capítulo 2 — O Inventário (teste)
│
├── css/
│   └── leitor-livro.css            ← Estilos do leitor (novo)
│
├── js/
│   └── leitor.js                   ← Lógica do leitor (novo)
│
└── backend/
    ├── acesso.php                  ← Verificação de acesso (novo)
    └── leitor.php                  ← Endpoints do leitor (novo)
```

---

## Passo 1 — Execute o SQL no phpMyAdmin

1. Abra `http://localhost/phpmyadmin`
2. Selecione o banco `roberio_site`
3. Aba **SQL** → Cole o conteúdo de `leitor_setup.sql` → **Executar**

Serão criadas as tabelas:
- `planos` (planos de assinatura com preços já inseridos)
- `assinaturas`
- `compras`
- `leitor_progresso`
- `leitor_anotacoes`
- `leitor_marcacoes`
- `leitor_preferencias`
- Views e procedures auxiliares

---

## Passo 2 — Copie os arquivos para o htdocs

Cole os novos arquivos dentro da pasta `roberiodiogenes.com/`:

```
htdocs/roberiodiogenes.com/leitor/livro.html
htdocs/roberiodiogenes.com/livros-conteudo/lumen/cap01.html
htdocs/roberiodiogenes.com/livros-conteudo/lumen/cap02.html
htdocs/roberiodiogenes.com/css/leitor-livro.css
htdocs/roberiodiogenes.com/js/leitor.js
htdocs/roberiodiogenes.com/backend/acesso.php
htdocs/roberiodiogenes.com/backend/leitor.php
```

---

## Passo 3 — Teste com o Lúmen

### Opção A — Usuário logado com acesso liberado manualmente

Para testar sem sistema de pagamento, insira um registro manual de compra:

```sql
-- Substitua 1 pelo ID do seu usuário (veja na tabela `usuarios`)
INSERT INTO compras (usuario_id, livro_slug, preco_pago, status)
VALUES (1, 'lumen', 0.00, 'aprovada');
```

### Opção B — Acesso por assinatura manual

```sql
-- Assinatura mensal de teste (30 dias a partir de hoje)
INSERT INTO assinaturas (usuario_id, plano_id, status, inicio_em, expira_em)
VALUES (1, 1, 'ativa', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY));
```

### URL de acesso ao leitor

Com o XAMPP rodando, acesse:
```
http://localhost/roberiodiogenes.com/leitor/livro.html?livro=lumen
```

---

## Passo 4 — Adicionar botão "Ler agora" nas páginas dos livros

Na página `livros/lumen.html`, adicione o botão de acesso ao leitor:

```html
<!-- Botão principal de leitura -->
<a href="../leitor/livro.html?livro=lumen"
   class="btn btn-primario"
   id="btn-ler-livro">
  <i class="fa-solid fa-book-open"></i>
  Ler agora
</a>
```

---

## Como adicionar capítulos reais do Lúmen

Cada capítulo é um arquivo HTML puro (sem `<html>`, `<head>` ou `<body>`).

Crie os arquivos seguindo o padrão:
```
livros-conteudo/lumen/cap01.html
livros-conteudo/lumen/cap02.html
livros-conteudo/lumen/cap03.html
...
livros-conteudo/lumen/cap12.html   ← conforme total_capitulos
```

### Estrutura HTML dos capítulos

```html
<span class="capitulo-numero">Capítulo I</span>
<h2>Nome do Capítulo</h2>

<!-- Primeiro parágrafo (do capítulo) com drop cap (letra capitular) -->
<p class="drop-cap">
  Texto do primeiro parágrafo…
</p>

<!-- Parágrafos normais -->
<p>Parágrafo normal…</p>

<!-- Separador ornamental -->
<span class="ornamento" aria-hidden="true">✦</span>

<!-- Diálogos -->
<p class="dialogo">— Fala de um personagem.</p>

<!-- Pensamentos ou narrativa interna -->
<p class="pensamento">Pensamento em itálico…</p>

<!-- Ênfase -->
<p>Palavra <em>enfatizada</em> no texto.</p>
```

### Atualize o total de capítulos

No arquivo `leitor/livro.html`, localize o objeto `LIVROS` dentro da tag `<script>`
e ajuste o `totalCapitulos` do livro conforme for criando os capítulos:

```javascript
const LIVROS = {
  'lumen': { titulo: 'Lúmen – A Outra Metade do Céu', totalCapitulos: 12 },
  // ...
};
```

---

## Como funciona o acesso (resumo)

```
Usuário acessa leitor/livro.html?livro=lumen
    ↓
JS chama backend/acesso.php?livro=lumen
    ↓
PHP verifica:
    1. Está logado? (sessão PHP) → Não: mostra tela de login
    2. Comprou o livro?          → Sim: libera leitura
    3. Tem assinatura ativa?     → Sim: libera leitura
    4. Nenhum dos dois?          → Mostra tela de compra/assinatura
    ↓
JS carrega cap01.html via fetch() do servidor
```

---

## Próximos passos sugeridos

| Etapa | O que fazer |
|-------|-------------|
| **Conteúdo** | Converter os capítulos reais do Lúmen para HTML fragmentado |
| **Compras** | Criar página de compra/pagamento (integrar Mercado Pago ou Stripe) |
| **Assinaturas** | Criar página de planos com os 3 planos já no banco |
| **Painel do leitor** | Criar `leitor/index.html` com lista de livros em leitura |
| **PHP dinâmico** | Converter `leitor/livro.html` de estático para PHP (`livro.php`) |

---

## Dúvidas frequentes

**P: O leitor não carrega o capítulo — dá erro 404.**
R: Verifique se os arquivos `livros-conteudo/lumen/cap01.html` existem no htdocs.

**P: O backend retorna "Você precisa estar logado".**
R: Faça login no site normalmente. A sessão PHP precisa estar ativa.

**P: As preferências não são salvas.**
R: Certifique-se de que a tabela `leitor_preferencias` foi criada (passo 1).

**P: Como testar sem sistema de pagamento?**
R: Use o INSERT manual descrito no Passo 3, Opção A ou B acima.
