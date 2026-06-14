# Leitor de EPUB — Guia de Integração
**Robério Diógenes — roberiodiogenes.com · Atualizado: junho/2026**

---

## Arquitetura atual

```
roberiodiogenes.com/
│
├── leitor/
│   ├── index.html              ← Biblioteca + leitor unificado (epub.js v0.3.93)
│   └── backend/
│       ├── acesso.php          ← Serve o EPUB e verifica acesso (compra/assinatura/gratuito)
│       ├── progresso.php       ← Salva/carrega posição de leitura e percentual
│       ├── anotacoes.php       ← Anotações do leitor por livro
│       ├── marcacoes.php       ← Marcações de texto (highlights)
│       ├── conquistas.php      ← Medalhas de leitura (25%, 50%, 75%, 100%)
│       └── notas_autor.php     ← Notas do autor vinculadas a posições CFI
│
├── livros-conteudo/
│   └── {slug}/                 ← Pasta individual por livro/conto
│       └── {slug}.epub         ← Arquivo EPUB
│
├── js/
│   ├── leitor.js               ← Lógica do leitor (epub.js wrapper + paywall + conquistas)
│   └── biblioteca.js           ← Biblioteca do leitor (lista de livros acessíveis)
│
└── backend/
    └── cron_lembrete_leitura.php  ← Cron diário: lembra usuários com leitura parada
```

---

## Como adicionar um livro ou conto novo

### 1. Upload do arquivo EPUB

Crie a pasta e suba o EPUB via SFTP ou cPanel File Manager:

```
livros-conteudo/{slug}/{slug}.epub
```

Exemplos:
```
livros-conteudo/lumen/lumen.epub
livros-conteudo/o-farol-do-afogado/o-farol-do-afogado.epub
livros-conteudo/a-marca-da-besta/a-marca-da-besta.epub
```

### 2. Cadastrar no banco de dados

No admin (`/admin/livros.php`) ou via phpMyAdmin, certifique-se de que a tabela `livros` tem:

| Campo | Valor exemplo |
|---|---|
| `slug` | `o-farol-do-afogado` |
| `titulo` | `O Farol do Afogado` |
| `pasta_conteudo` | `livros-conteudo/o-farol-do-afogado` |
| `arquivo_epub` | `o-farol-do-afogado.epub` |
| `gratuito` | `1` (gratuito) ou `0` (pago) |
| `ativo` | `1` |

> ⚠ O campo `pasta_conteudo` **não** deve ter barra no final.  
> ✅ `livros-conteudo/o-farol-do-afogado`  
> ❌ `livros-conteudo/o-farol-do-afogado/`

### 3. Testar o acesso

Acesse o leitor com o slug do livro:
```
https://roberiodiogenes.com/leitor/?livro={slug}
```

---

## Como o acesso funciona

```
Usuário abre leitor/?livro=lumen
    ↓
JS chama leitor/backend/acesso.php?acao=verificar&slug=lumen
    ↓
PHP verifica (em ordem):
    1. Livro ativo no banco?          → Não: erro
    2. Livro gratuito?                → Sim + logado: acesso concedido
    3. Comprou o livro?               → Sim: acesso concedido
    4. Tem assinatura ativa?          → Sim: acesso concedido
    5. Nenhum dos anteriores?         → modo amostra (10% gratuito)
    ↓
JS chama acesso.php?acao=servir&slug=lumen
    ↓
PHP localiza o EPUB em livros-conteudo/{slug}/{slug}.epub e faz stream
    ↓
epub.js recebe o binário e renderiza
```

---

## Onde o EPUB é buscado (ordem de prioridade)

O `leitor/backend/acesso.php` busca o arquivo em 5 locais:

1. `livros-conteudo/{pasta_conteudo}/{arquivo_epub}` — **via banco de dados** (prioritário)
2. `leitor/epub/{arquivo_epub}`
3. `leitor/epub/{slug}.epub`
4. `livros-conteudo/livros/{arquivo_epub}`
5. `livros-conteudo/{slug}/{arquivo_epub}`

Se o `pasta_conteudo` estiver preenchido corretamente no banco, o caminho 1 resolve sempre.

---

## Tabelas do banco usadas pelo leitor

| Tabela | Função |
|---|---|
| `livros` | Catálogo de obras (slug, titulo, pasta_conteudo, arquivo_epub, gratuito) |
| `compras` | Compras avulsas aprovadas |
| `assinaturas` | Assinaturas ativas com data de expiração |
| `leitor_progresso` | Posição de leitura (CFI), percentual, concluído |
| `leitor_anotacoes` | Anotações do leitor por livro |
| `leitor_marcacoes` | Highlights/marcações de texto |
| `leitor_preferencias` | Fonte, tamanho, fundo, largura por usuário |
| `leitor_conquistas` | Medalhas desbloqueadas (início, 25%, 50%, 75%, 100%) |
| `leitor_notas_autor` | Notas do autor vinculadas a posições CFI no epub |
| `leitor_lembretes_enviados` | Controle de throttle do cron de lembrete |

---

## Testar sem sistema de pagamento

Para liberar acesso manualmente (ambiente de desenvolvimento):

```sql
-- Compra avulsa manual
INSERT INTO compras (usuario_id, livro_slug, preco_pago, status)
VALUES (1, 'lumen', 0.00, 'aprovada');

-- OU assinatura manual (30 dias)
INSERT INTO assinaturas (usuario_id, plano_id, status, inicio_em, expira_em)
VALUES (1, 1, 'ativa', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY));
```

---

## Cron de lembrete de leitura

Detecta usuários que pararam de ler há mais de 3 dias e envia e-mail.

**Configurar no cPanel → Cron Jobs:**
```
0 9 * * *   /usr/bin/php /home/fra46117/public_html/backend/cron_lembrete_leitura.php
```

---

## Dúvidas frequentes

**P: O leitor abre mas fica no spinner infinito.**  
R: Verifique se o arquivo EPUB existe em `livros-conteudo/{slug}/{slug}.epub` no servidor. Confira também se `pasta_conteudo` e `arquivo_epub` estão corretos no banco.

**P: `acesso.php?acao=servir` retorna 404.**  
R: O EPUB não foi encontrado em nenhum dos 5 caminhos. Verifique se a pasta e o arquivo foram criados corretamente no servidor.

**P: O backend retorna "login necessário" mesmo logado.**  
R: A sessão do leitor usa `PHPSESSID`. Confirme que o usuário está logado no domínio principal (não localhost).

**P: Conquistas/medalhas não aparecem.**  
R: Confirme que a tabela `leitor_conquistas` existe no banco. Se necessário, execute o SQL de criação.

**P: Como testar sem pagar?**  
R: Use o INSERT manual de compra ou assinatura descrito acima.
