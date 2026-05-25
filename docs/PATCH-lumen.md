# Patch — lumen.html
# Integração dos botões de compra/leitura inteligentes
# Aplique as substituições abaixo manualmente no arquivo livros/lumen.html

## SUBSTITUIÇÃO 1 — Botões principais (área livro-ctas)
## Localizar:
      <div class="livro-ctas reveal reveal-delay-3">
        <a
          href="https://www.amazon.com.br"
          target="_blank"
          rel="noopener noreferrer"
          class="btn btn-primario"
          aria-label="Comprar Lúmen na Amazon"
        >
          <i class="fa-brands fa-amazon" aria-hidden="true"></i>
          Comprar na Amazon
        </a>
        <a href="../livros.html" class="btn btn-ghost">
          <i class="fa fa-arrow-left" aria-hidden="true"></i>
          Ver todos os livros
        </a>
      </div>

## Substituir por:
      <div class="livro-ctas reveal reveal-delay-3">
        <!-- Botão inteligente: injetado pelo compra-livro.js -->
        <div data-slot-compra aria-live="polite" aria-label="Opções de leitura e compra"></div>
        <a href="../livros.html" class="btn btn-ghost">
          <i class="fa fa-arrow-left" aria-hidden="true"></i>
          Ver todos os livros
        </a>
      </div>


## SUBSTITUIÇÃO 2 — Sidebar (card de compra)
## Localizar:
      <!-- Compra -->
      <div class="sidebar-card reveal reveal-delay-2">
        <p class="sidebar-titulo">
          <i class="fa fa-shopping-cart" aria-hidden="true"></i> Adquira o livro completo
        </p>
        <a
          href="https://www.amazon.com.br"
          target="_blank"
          rel="noopener noreferrer"
          class="btn btn-primario"
          style="width:100%;justify-content:center;"
          aria-label="Comprar Lúmen na Amazon"
        >
          <i class="fa-brands fa-amazon" aria-hidden="true"></i> Comprar na Amazon
        </a>
      </div>

## Substituir por:
      <!-- Compra -->
      <div class="sidebar-card reveal reveal-delay-2">
        <p class="sidebar-titulo">
          <i class="fa fa-book-open" aria-hidden="true"></i> Leia este livro
        </p>
        <!-- Botão inteligente: injetado pelo compra-livro.js -->
        <div data-slot-compra style="display:flex;flex-direction:column;gap:.6rem" aria-live="polite"></div>
      </div>


## ADIÇÃO — Script no final do <body> (antes de </body>)
## Adicionar após o último <script> existente:
<script src="../js/compra-livro.js"></script>


## NOTA PARA OUTROS LIVROS
## Para aplicar nos demais livros (jogo-das-mascaras.html, etc.):
## 1. Certifique-se de que o <body> tem data-livro="slug-do-livro"
## 2. Substitua os botões Amazon pelos <div data-slot-compra>
## 3. Adicione <script src="../js/compra-livro.js"></script> no final
## O script é genérico e funciona para qualquer livro automaticamente.
