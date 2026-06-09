/* ================================================================
   ROBÉRIO DIÓGENES — js/busca.js
   Busca em tempo real nas seções da página inicial.
   Indexa o conteúdo do DOM na primeira execução e filtra
   localmente a cada tecla — sem requisições ao servidor.
   ================================================================ */

(function Busca() {
  'use strict';

  // ── Índice de conteúdo pesquisável ─────────────────────────
  // Cada entrada: { titulo, desc, ancora, icone, palavras }
  const INDICE = [
    {
      titulo: 'Início',
      desc:   'Apresentação do autor e destaque da obra.',
      ancora: '#inicio',
      icone:  '🏠',
    },
    {
      titulo: 'Livros',
      desc:   'Conheça os thrillers psicológicos, dramas e terror de Robério Diógenes.',
      ancora: '#livros',
      icone:  '📚',
    },
    {
      titulo: 'Sobre o Autor',
      desc:   'A história, a inspiração e o universo literário de Robério Diógenes.',
      ancora: '#autor-sec',
      icone:  '✒️',
    },
    {
      titulo: 'Diário do Escritor',
      desc:   'Posts, reflexões e bastidores direto do autor.',
      ancora: '#blog-sec',
      icone:  '📝',
    },
    {
      titulo: 'Newsletter',
      desc:   'Inscreva-se e receba novidades, capítulos exclusivos e lançamentos.',
      ancora: '#newsletter',
      icone:  '✉️',
    },
    {
      titulo: 'Login — Área do Leitor',
      desc:   'Entre na sua conta ou cadastre-se gratuitamente.',
      ancora: 'login.html',
      icone:  '🔑',
      externo: true,
    },
    {
      titulo: 'Cadastro',
      desc:   'Crie sua conta para acessar conteúdos exclusivos.',
      ancora: 'cadastro.html',
      icone:  '👤',
      externo: true,
    },
    {
      titulo: 'Política de Privacidade',
      desc:   'Como seus dados são coletados e protegidos.',
      ancora: 'privacidade.html',
      icone:  '🔒',
      externo: true,
    },
    {
      titulo: 'Termos de Uso',
      desc:   'Regras e condições de uso do site.',
      ancora: 'termos.html',
      icone:  '📄',
      externo: true,
    },
    // ── Entradas da página do Autor ─────────────────────────
    {
      titulo: 'Sobre o Autor — Biografia',
      desc:   'Nascido em Cascavel, Ceará. Trajetória, formação e inspirações de Robério Diógenes.',
      ancora: 'autor.html',
      icone:  '✒️',
      externo: true,
    },
    {
      titulo: 'Thriller Psicológico',
      desc:   'O gênero literário central de Robério Diógenes. Personagens que enganam e são enganados.',
      ancora: 'autor.html',
      icone:  '🎭',
      externo: true,
    },
    {
      titulo: 'Biblioteca — Todos os livros',
      desc:   'Explore todos os livros publicados por Robério Diógenes.',
      ancora: 'livros.html',
      icone:  '📚',
      externo: true,
    },
    {
      titulo: 'Leitor Online',
      desc:   'Leia os livros de Robério Diógenes no navegador, com anotações e progresso.',
      ancora: 'leitor/index.html',
      icone:  '📖',
      externo: true,
    },
    {
      titulo: 'Contato',
      desc:   'Entre em contato com o autor.',
      ancora: 'contato.html',
      icone:  '✉️',
      externo: true,
    },
  ];

  // Pré-computar campo de busca normalizado
  INDICE.forEach(item => {
    item._busca = normalizar(item.titulo + ' ' + item.desc);
  });

  // ── Elementos ──────────────────────────────────────────────
  let input, resultados;

  document.addEventListener('DOMContentLoaded', () => {
    input      = document.getElementById('buscaNav');
    resultados = document.getElementById('buscaResultados');
    if (!input || !resultados) return;

    // Também indexa títulos e textos das seções em tempo real
    indexarPagina();

    // Eventos
    input.addEventListener('input',   debounce(pesquisar, 180));
    input.addEventListener('keydown', navegarTeclado);
    input.addEventListener('focus',   () => { if (input.value.trim().length >= 2) mostrar(); });

    // Fechar ao clicar fora
    document.addEventListener('click', e => {
      if (!input.closest('.busca-nav').contains(e.target)) fechar();
    });

    // Fechar com Escape
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') { fechar(); input.blur(); }
    });
  });

  // ── Indexar seções da própria página ──────────────────────
  function indexarPagina() {
    // Livros individuais
    document.querySelectorAll('.livro-card, .card-livro, [class*="livro"]').forEach(el => {
      const titulo = el.querySelector('h2, h3, .livro-titulo, .card-titulo')?.textContent?.trim();
      const desc   = el.querySelector('p, .livro-desc, .card-desc')?.textContent?.trim();
      const id     = el.id || el.closest('section')?.id;
      if (titulo && titulo.length > 2) {
        INDICE.push({
          titulo,
          desc:    desc?.slice(0, 120) || '',
          ancora:  id ? '#' + id : '#livros',
          icone:   '📖',
          _busca:  normalizar(titulo + ' ' + (desc || '')),
        });
      }
    });
  }

  // ── Pesquisar ──────────────────────────────────────────────
  function pesquisar() {
    const termo = input.value.trim();
    if (termo.length < 2) { fechar(); return; }

    const normalizado = normalizar(termo);
    const encontrados = INDICE.filter(item =>
      item._busca.includes(normalizado)
    ).slice(0, 6);

    renderizar(encontrados, termo);
    mostrar();
  }

  // ── Renderizar resultados ──────────────────────────────────
  function renderizar(itens, termo) {
    resultados.innerHTML = '';

    if (itens.length === 0) {
      resultados.innerHTML = `<p class="busca-vazio">Nenhum resultado para "<em>${escapar(termo)}</em>"</p>`;
      return;
    }

    itens.forEach((item, idx) => {
      const btn = document.createElement('button');
      btn.className = 'busca-item';
      btn.setAttribute('role', 'option');
      btn.setAttribute('tabindex', '-1');
      btn.dataset.idx = idx;

      const tituloDestacado = destacar(item.titulo, termo);

      btn.innerHTML = `
        <span class="busca-item-icone" aria-hidden="true">${item.icone}</span>
        <span class="busca-item-texto">
          <span class="busca-item-titulo">${tituloDestacado}</span>
          <span class="busca-item-desc">${escapar(item.desc)}</span>
        </span>`;

      btn.addEventListener('click', () => navegar(item));
      btn.addEventListener('mouseenter', () => ativarFoco(idx));
      resultados.appendChild(btn);
    });
  }

  // ── Navegar para o resultado ───────────────────────────────
  function navegar(item) {
    fechar();
    input.value = '';

    if (item.externo) {
      window.location.href = item.ancora;
      return;
    }

    const alvo = document.querySelector(item.ancora);
    if (alvo) {
      const topo = alvo.getBoundingClientRect().top + window.scrollY;
      const navH = document.querySelector('nav, header')?.offsetHeight || 80;
      window.scrollTo({ top: topo - navH - 16, behavior: 'smooth' });
      // Foco acessível
      alvo.setAttribute('tabindex', '-1');
      alvo.focus({ preventScroll: true });
    }
  }

  // ── Navegação por teclado ──────────────────────────────────
  function navegarTeclado(e) {
    if (resultados.hidden) return;

    const itens = resultados.querySelectorAll('.busca-item');
    const atual = resultados.querySelector('.busca-item.ativo');
    let idx     = atual ? parseInt(atual.dataset.idx) : -1;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      idx = Math.min(idx + 1, itens.length - 1);
      ativarFoco(idx);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      idx = Math.max(idx - 1, 0);
      ativarFoco(idx);
    } else if (e.key === 'Enter' && atual) {
      e.preventDefault();
      atual.click();
    }
  }

  function ativarFoco(idx) {
    resultados.querySelectorAll('.busca-item').forEach((el, i) => {
      el.classList.toggle('ativo', i === idx);
      if (i === idx) el.focus();
    });
  }

  // ── Mostrar / fechar dropdown ──────────────────────────────
  function mostrar() { resultados.hidden = false; }
  function fechar()  { resultados.hidden = true; }

  // ── Helpers ────────────────────────────────────────────────
  function normalizar(str) {
    return str.toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '') // remove acentos
      .replace(/[^a-z0-9\s]/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function destacar(texto, termo) {
    const re = new RegExp('(' + escaparRegex(termo) + ')', 'gi');
    return escapar(texto).replace(re, '<mark>$1</mark>');
  }

  function escapar(str) {
    return (str || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  function escaparRegex(str) {
    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  function debounce(fn, ms) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
  }

})();
