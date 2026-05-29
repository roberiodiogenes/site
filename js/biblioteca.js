/* ================================================================
   ROBÉRIO DIÓGENES — js/biblioteca.js
   Carrega o catálogo de livros e contos dinamicamente via API,
   com filtro por tipo (livro/conto), gênero e busca.
   Paginação automática a cada 12 itens.
   ================================================================ */
'use strict';

(function () {

  /* ── Estado ── */
  const estado = {
    tipo:    'todos',
    genero:  'todos',
    busca:   '',
    pagina:  1,
    total:   0,
    totalPags: 0,
  };

  /* ── Elementos ── */
  const grade    = document.getElementById('gradeLivros');
  const loading  = document.getElementById('biblioteca-loading');
  const vazio    = document.getElementById('vazioMsg');
  const paginNav = document.getElementById('paginacao');
  const badgeContos = document.getElementById('badge-contos');

  /* ── Inicialização ── */
  document.addEventListener('DOMContentLoaded', () => {
    // Lê parâmetros da URL (permite link direto para filtro)
    const params = new URLSearchParams(window.location.search);
    if (params.get('tipo'))   estado.tipo   = params.get('tipo');
    if (params.get('genero')) estado.genero = params.get('genero');
    if (params.get('p'))      estado.pagina = parseInt(params.get('p')) || 1;
    if (params.get('q'))      estado.busca  = params.get('q');

    // Tabs de tipo
    document.querySelectorAll('.tipo-tab').forEach(btn => {
      if (btn.dataset.tipo === estado.tipo) {
        btn.classList.add('ativo');
        btn.setAttribute('aria-selected', 'true');
      }
      btn.addEventListener('click', () => {
        document.querySelectorAll('.tipo-tab').forEach(b => {
          b.classList.remove('ativo');
          b.setAttribute('aria-selected', 'false');
        });
        btn.classList.add('ativo');
        btn.setAttribute('aria-selected', 'true');
        estado.tipo   = btn.dataset.tipo;
        estado.pagina = 1;
        carregar();
      });
    });

    // Botões de gênero (já existentes no HTML)
    document.querySelectorAll('.filtro-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('ativo'));
        btn.classList.add('ativo');
        estado.genero = btn.dataset.filtro;
        estado.pagina = 1;
        carregar();
      });
    });

    // Busca (se existir campo no HTML)
    const inputBusca = document.querySelector('.busca-nav input');
    if (inputBusca) {
      let timer;
      inputBusca.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
          estado.busca  = inputBusca.value.trim();
          estado.pagina = 1;
          carregar();
        }, 400);
      });
    }

    // Conta contos para badge
    contarContos();

    // Carga inicial
    carregar();
  });

  /* ── Conta contos para o badge na tab ── */
  async function contarContos() {
    try {
      const r = await fetch('backend/biblioteca.php?acao=listar&tipo=conto&p=1');
      const d = await r.json();
      if (d.ok && badgeContos) {
        badgeContos.textContent = d.total || '';
        badgeContos.style.display = d.total > 0 ? 'inline-block' : 'none';
      }
    } catch {}
  }

  /* ── Carrega itens da API ── */
  async function carregar() {
    // Mostra loading
    if (grade)    grade.style.display   = 'none';
    if (paginNav) paginNav.style.display = 'none';
    if (vazio)    vazio.style.display   = 'none';
    if (loading)  loading.style.display = 'block';

    const qs = new URLSearchParams({
      acao:   'listar',
      tipo:   estado.tipo,
      genero: estado.genero,
      q:      estado.busca,
      p:      estado.pagina,
    });

    try {
      const r = await fetch(`backend/biblioteca.php?${qs}`);
      if (!r.ok) throw new Error('HTTP ' + r.status);
      const d = await r.json();

      if (loading) loading.style.display = 'none';

      if (!d.ok || !d.itens?.length) {
        if (vazio) vazio.style.display = 'flex';
        return;
      }

      estado.total     = d.total;
      estado.totalPags = d.total_pags;

      // Renderiza cards
      if (grade) {
        grade.innerHTML = d.itens.map(renderCard).join('');
        grade.style.display = '';
        // Dispara animação de reveal
        grade.querySelectorAll('.livro-item').forEach((el, i) => {
          setTimeout(() => el.classList.add('visivel'), i * 60);
        });
      }

      // Paginação
      renderPaginacao(d.pagina, d.total_pags, d.total);

      // Atualiza URL sem recarregar a página
      const url = new URL(window.location);
      url.searchParams.set('tipo',   estado.tipo);
      url.searchParams.set('genero', estado.genero);
      url.searchParams.set('p',      estado.pagina);
      if (estado.busca) url.searchParams.set('q', estado.busca);
      else url.searchParams.delete('q');
      window.history.replaceState({}, '', url);

      // Scroll suave ao topo da grade ao mudar de página
      if (estado.pagina > 1) {
        document.getElementById('catalogo')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }

    } catch (e) {
      if (loading) loading.style.display = 'none';
      if (vazio)   vazio.style.display   = 'flex';
      console.error('[Biblioteca]', e);
    }
  }

  /* ── Renderiza um card de livro/conto ── */
  function renderCard(item) {
    const eConto    = item.tipo === 'conto';
    const eGratuito = item.gratuito;
    const temPromo  = item.preco_promocao && item.preco_promocao < item.preco;
    const precoExib = temPromo ? item.preco_promocao : item.preco;

    // Badges dinâmicos
    const badges = [];
    if (item.destaque) badges.push('<span class="badge badge-ferrugem"><i class="fa fa-star" aria-hidden="true"></i> Destaque</span>');
    if (item.novo)     badges.push('<span class="badge badge-ouro"><i class="fa fa-sparkles" aria-hidden="true"></i> Novo</span>');
    if (eConto)        badges.push('<span class="badge badge-azul-escuro"><i class="fa fa-scroll" aria-hidden="true"></i> Conto</span>');
    if (eGratuito)     badges.push('<span class="badge badge-verde"><i class="fa fa-gift" aria-hidden="true"></i> Gratuito</span>');
    if (item.badges?.length) {
      item.badges.forEach(b => {
        if (b.trim()) badges.push(`<span class="badge badge-ouro">${esc(b.trim())}</span>`);
      });
    }

    // Avaliação
    const avalHtml = item.media_aval
      ? `<div class="livro-item-aval" aria-label="${item.media_aval} de 5 estrelas">
           <span class="aval-estrelas">${'★'.repeat(Math.round(item.media_aval))}${'☆'.repeat(5 - Math.round(item.media_aval))}</span>
           <span class="aval-num">${item.media_aval} (${item.n_aval})</span>
         </div>`
      : '';

    // Preço
    let precoHtml = '';
    if (eGratuito) {
      precoHtml = '<span class="livro-preco gratuito">Gratuito</span>';
    } else if (precoExib) {
      precoHtml = `<span class="livro-preco${temPromo ? ' promo' : ''}">
        ${temPromo ? `<span class="preco-riscado">R$ ${fmt(item.preco)}</span>` : ''}
        R$ ${fmt(precoExib)}
      </span>`;
    }

    // Botões de ação
    const slug     = esc(item.slug);
    const paginaLivro = `livros/${slug}.html`;

    let acoesHtml = '';
    if (eGratuito) {
      acoesHtml = `
        <a href="${paginaLivro}" class="btn btn-primario">
          <i class="fa fa-book-open" aria-hidden="true"></i> Ler grátis
        </a>
        <a href="${paginaLivro}" class="btn btn-ghost">Ver mais</a>`;
    } else if (item.link_amazon) {
      acoesHtml = `
        <a href="${esc(item.link_amazon)}" target="_blank" rel="noopener" class="btn btn-primario">
          <i class="fa-brands fa-amazon" aria-hidden="true"></i> Comprar
        </a>
        <a href="${paginaLivro}" class="btn btn-ghost">Ver mais</a>`;
    } else {
      acoesHtml = `
        <a href="${paginaLivro}" class="btn btn-secundario">Ver detalhes</a>`;
    }

    // Leitura estimada para contos
    const leitHtml = eConto && item.total_capitulos
      ? `<p class="livro-item-leitura"><i class="fa fa-clock" aria-hidden="true"></i> ~${item.total_capitulos * 5} min de leitura</p>`
      : '';

    return `
      <article class="livro-item reveal" data-genero="${esc(item.genero || '')}" data-tipo="${esc(item.tipo)}" role="listitem">
        <a href="${paginaLivro}" class="livro-item-capa-link" tabindex="-1" aria-hidden="true">
          <div class="livro-item-capa-wrap">
            <img src="${esc(item.capa_img || 'img/capa-placeholder.jpg')}"
                 alt="Capa de ${esc(item.titulo)}"
                 class="livro-item-capa" loading="lazy"
                 onerror="this.src='img/capa-placeholder.jpg'"/>
            <div class="livro-item-spine" aria-hidden="true"></div>
          </div>
        </a>
        <div class="livro-item-info">
          <span class="livro-item-genero">${esc(item.genero || (eConto ? 'Conto' : 'Ficção'))}</span>
          <h2 class="livro-item-titulo"><a href="${paginaLivro}">${esc(item.titulo)}</a></h2>
          ${item.subtitulo ? `<p class="livro-item-subtitulo">${esc(item.subtitulo)}</p>` : ''}
          ${item.sinopse   ? `<p class="livro-item-sinopse">${esc(item.sinopse)}</p>` : ''}
          ${leitHtml}
          ${avalHtml}
          ${precoHtml}
          ${badges.length ? `<div class="livro-item-badges">${badges.join('')}</div>` : ''}
          <div class="livro-item-acoes">${acoesHtml}</div>
        </div>
      </article>`;
  }

  /* ── Renderiza paginação ── */
  function renderPaginacao(pagAtual, totalPags, total) {
    if (!paginNav || totalPags <= 1) return;

    const btnStyle = 'display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;padding:0 .6rem;border-radius:6px;border:1px solid var(--borda-media);background:transparent;color:var(--texto-3);font-size:.82rem;cursor:pointer;text-decoration:none;transition:all .15s';
    const btnAtivo = 'background:var(--ouro);color:#1A0F00;border-color:var(--ouro);font-weight:700';

    let html = `<span style="font-size:.78rem;color:var(--texto-3);margin-right:.5rem">${total} itens</span>`;

    // Anterior
    if (pagAtual > 1) {
      html += `<button style="${btnStyle}" onclick="BibliotecaIr(${pagAtual - 1})" aria-label="Página anterior">
                 <i class="fa fa-chevron-left"></i>
               </button>`;
    }

    // Páginas
    const inicio = Math.max(1, pagAtual - 2);
    const fim    = Math.min(totalPags, pagAtual + 2);

    if (inicio > 1) html += `<button style="${btnStyle}" onclick="BibliotecaIr(1)">1</button>`;
    if (inicio > 2) html += `<span style="color:var(--texto-3);padding:0 .25rem">…</span>`;

    for (let i = inicio; i <= fim; i++) {
      const s = i === pagAtual ? `${btnStyle};${btnAtivo}` : btnStyle;
      html += `<button style="${s}" onclick="BibliotecaIr(${i})" aria-current="${i === pagAtual ? 'page' : 'false'}">${i}</button>`;
    }

    if (fim < totalPags - 1) html += `<span style="color:var(--texto-3);padding:0 .25rem">…</span>`;
    if (fim < totalPags)     html += `<button style="${btnStyle}" onclick="BibliotecaIr(${totalPags})">${totalPags}</button>`;

    // Próxima
    if (pagAtual < totalPags) {
      html += `<button style="${btnStyle}" onclick="BibliotecaIr(${pagAtual + 1})" aria-label="Próxima página">
                 <i class="fa fa-chevron-right"></i>
               </button>`;
    }

    paginNav.innerHTML = html;
    paginNav.style.display = 'flex';
  }

  /* ── API pública para os botões de paginação (chamados inline) ── */
  window.BibliotecaIr = function(pagina) {
    estado.pagina = pagina;
    carregar();
  };

  /* ── Helpers ── */
  function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function fmt(n) {
    return Number(n).toFixed(2).replace('.', ',');
  }

})();
