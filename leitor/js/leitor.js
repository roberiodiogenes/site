/* ================================================================
   ROBÉRIO DIÓGENES — leitor/js/leitor.js
   Leitor Online v3 — Lógica principal
   ================================================================ */
'use strict';

/* ══ ESTADO GLOBAL ═════════════════════════════════════════════ */
const L = {
  livroAtual:    null,    // objeto do catálogo
  book:          null,    // instância epub.js
  rendition:     null,    // rendition do epub.js
  progresso:     null,    // dados do BD
  prefs:         { fonte:'serifada', tamanho:18, espacamento:1.8, largura:'media', tema:'claro' },
  notas:         [],      // notas do autor carregadas
  anotacoes:     [],      // anotações do leitor
  marcacoes:     [],      // highlights do leitor
  conquistas:    [],      // conquistadas
  cfiAtual:      '',      // CFI da posição atual
  pctAtual:      0,       // percentual atual
  capituloAtual: '',      // href do capítulo atual
  usuario:       null,
  // Amostra grátis
  modoAmostra:   false,   // true = usuário não comprou o livro
  limiteAmostra: 10,      // % máximo na amostra
  paywallAtivo:  false,   // paywall já foi exibido
  _amostratimer: null,    // timer do monitor de posição em modo amostra
  timer: {
    tipo: 'relogio', ativo: false, segundos: 0,
    alvo: 0, intervalo: null, oculto: false
  },
  corAnotacao:   '#FFD700',
  selecaoAtual:  null,    // seleção de texto para anotação/marcação
  tempoInicioLeitura: Date.now(),
  autoSaveTimer: null,
};

/* ══ HELPERS ═══════════════════════════════════════════════════ */
function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function dataFmt(iso) {
  if (!iso) return '';
  try { return new Date(iso).toLocaleDateString('pt-BR', { day:'2-digit', month:'short', year:'numeric' }); }
  catch { return iso; }
}
function toast(msg, tipo = 'ok', dur = 3200) {
  const wrap = document.getElementById('toastWrap');
  if (!wrap) return;
  const t = document.createElement('div');
  t.className = 'toast' + (tipo === 'erro' ? ' erro' : '');
  t.textContent = msg;
  wrap.appendChild(t);
  requestAnimationFrame(() => t.classList.add('visivel'));
  setTimeout(() => { t.classList.remove('visivel'); setTimeout(() => t.remove(), 400); }, dur);
}
async function api(path, opts = {}) {
  try {
    const r = await fetch(path, { credentials: 'same-origin', ...opts });
    return await r.json();
  } catch (e) {
    console.error('[Leitor] API error:', path, e);
    return { ok: false, erro: e.message };
  }
}
function abrirPainel(nome) {
  fecharTodosPaineis();
  const p = document.getElementById('painel' + nome.charAt(0).toUpperCase() + nome.slice(1));
  if (p) { p.removeAttribute('hidden'); p.classList.add('aberto'); }
  const ov = document.getElementById('painelOverlay');
  if (ov) ov.removeAttribute('hidden');
}
function fecharPainel(nome) {
  const p = document.getElementById('painel' + nome.charAt(0).toUpperCase() + nome.slice(1));
  if (p) {
    p.classList.remove('aberto');
    const ref = p;
    setTimeout(() => {
      if (!ref.classList.contains('aberto')) ref.setAttribute('hidden', '');
    }, 300);
  }
  const abertos = document.querySelectorAll('.painel-lateral.aberto');
  if (!abertos.length) {
    const ov = document.getElementById('painelOverlay');
    if (ov) ov.setAttribute('hidden', '');
  }
}
function fecharTodosPaineis() {
  document.querySelectorAll('.painel-lateral').forEach(p => {
    p.classList.remove('aberto');
    // Só ocultar após a animação se o painel ainda não foi reaberto
    const ref = p;
    setTimeout(() => {
      if (!ref.classList.contains('aberto')) ref.setAttribute('hidden', '');
    }, 300);
  });
  const ov = document.getElementById('painelOverlay');
  if (ov) ov.setAttribute('hidden', '');
}

/* ══ SESSÃO ════════════════════════════════════════════════════ */
async function carregarSessao() {
  const d = await api('../backend/auth/sessao.php');
  if (d.ok && d.logado) {
    L.usuario = d.usuario;
    const el = document.getElementById('tsUsuarioNome');
    if (el) el.textContent = d.usuario.nome.split(' ')[0];
  } else {
    window.location.href = '../login.html?redir=leitor/';
  }
}

/* ══ BIBLIOTECA (tela de seleção) ══════════════════════════════ */
async function carregarBiblioteca() {
  const grade = document.getElementById('tsGrade');
  if (!grade) return;

  const d = await api('backend/acesso.php?acao=minha_biblioteca');
  if (!d.ok) {
    grade.innerHTML = `<div class="ts-vazio"><p>Nenhum livro disponível ainda.</p>
      <p><a href="../livros.html">Ver catálogo →</a></p></div>`;
    return;
  }

  if (!d.livros || d.livros.length === 0) {
    grade.innerHTML = `<div class="ts-vazio">
      <p>Sua biblioteca está vazia.</p>
      <p><a href="../livros.html">Conheça nosso catálogo →</a></p></div>`;
    return;
  }

  // Rótulos de acesso exibidos ao leitor
  const labelAcesso = {
    gratuito:    'Gratuito',
    compra:      'Adquirido',
    assinatura:  'Assinante',
    amostra:     'Amostra · 10%',
  };

  grade.innerHTML = d.livros.map(l => {
    const pct     = parseFloat(l.percentual || 0);
    const concl   = l.concluido === '1' || l.concluido === 1;
    const isAmostra = l.acesso === 'amostra';

    const badgeHtml = concl
      ? `<span class="ts-badge ts-badge-concl">✓ Lido</span>`
      : isAmostra
        ? `<span class="ts-badge ts-badge-amostra">Amostra</span>`
        : (l.novo == '1' ? `<span class="ts-badge ts-badge-novo">Novo</span>` : '');

    const capaHtml = l.capa_img
      ? `<img src="../${esc(l.capa_img)}" alt="${esc(l.titulo)}" class="ts-card-capa" loading="lazy"
              onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
      : '';
    const progHtml = pct > 0 ? `
      <div class="ts-prog-wrap">
        <div class="ts-prog-bar"><div class="ts-prog-fill" style="width:${Math.min(100,pct)}%"></div></div>
        <span class="ts-prog-txt">${Math.round(pct)}% lido</span>
      </div>` : '';

    const tipoLabel = l.tipo === 'conto' ? 'Conto' : 'Livro';
    const acessoLabel = labelAcesso[l.acesso] || l.acesso;
    const ctaLabel = pct > 0 ? 'Continuar leitura' : (isAmostra ? 'Ler amostra' : 'Começar a ler');

    return `<div class="ts-card${isAmostra ? ' ts-card-amostra' : ''}" role="button" tabindex="0"
                 data-slug="${esc(l.slug)}" data-titulo="${esc(l.titulo)}"
                 onclick="abrirLivro('${esc(l.slug)}')"
                 onkeydown="if(event.key==='Enter')abrirLivro('${esc(l.slug)}')">
      ${badgeHtml}
      <div class="ts-card-capa-wrap">
        ${capaHtml}
        <div class="ts-card-capa-ph" ${capaHtml?'style="display:none"':''}>
          <i class="fa fa-book"></i>${esc(l.titulo)}
        </div>
      </div>
      <div class="ts-card-corpo">
        <span class="ts-card-tipo">${tipoLabel} · ${esc(acessoLabel)}</span>
        <h2 class="ts-card-titulo">${esc(l.titulo)}</h2>
        ${progHtml}
      </div>
      <div class="ts-card-continuar">${ctaLabel}</div>
    </div>`;
  }).join('');
}

/* ══ ABRIR LIVRO ═══════════════════════════════════════════════ */
async function abrirLivro(slug) {
  // Verificar acesso
  const d = await api(`backend/acesso.php?acao=verificar&slug=${encodeURIComponent(slug)}`);
  if (!d.ok || !d.acesso) {
    toast('Acesso negado: ' + (d.motivo || 'sem permissão'), 'erro');
    if (d.motivo === 'login_necessario') window.location.href = '../login.html?redir=leitor/?livro=' + slug;
    return;
  }

  L.livroAtual = d.livro;
  // Detectar modo amostra
  L.modoAmostra   = (d.tipo === 'amostra');
  L.limiteAmostra = d.percentual_max || 10;
  L.paywallAtivo  = false;

  // Restaurar scroll (pode ter sido bloqueado por paywall em livro anterior)
  _desbloquearScroll();

  // Restaurar controles de navegação (podem ter sido desabilitados por paywall anterior)
  const btnAnt = document.getElementById('btnAnterior');
  const btnPrx = document.getElementById('btnProximo');
  const selCap = document.getElementById('seletorCapitulo');
  if (btnAnt) { btnAnt.disabled = false; btnAnt.title = 'Capítulo anterior'; }
  if (btnPrx) { btnPrx.disabled = false; btnPrx.title = 'Próximo capítulo'; }
  if (selCap) selCap.disabled = false;

  document.getElementById('lhTitulo').textContent = d.livro.titulo;
  document.getElementById('page-title').textContent = d.livro.titulo + ' | Leitor | Robério Diógenes';

  // Trocar telas — esconder paywall se estava ativo
  document.getElementById('telaSelecao').style.display = 'none';
  document.getElementById('telaLeitor').style.display  = 'flex';
  const pw = document.getElementById('paywallOverlay');
  if (pw) pw.style.display = 'none';

  // Carregar progresso e preferências
  const pd = await api(`backend/progresso.php?acao=carregar&slug=${encodeURIComponent(slug)}`);
  if (pd.ok) {
    L.progresso = pd.progresso;
    if (pd.preferencias) aplicarPreferencias(pd.preferencias);
  }

  // Iniciar epub.js
  await iniciarEpub(slug, L.progresso?.cfi);

  // Carregar dados paralelos
  carregarAnotacoes(slug);
  carregarMarcacoes(slug);
  carregarConquistas(slug);
  carregarNotasAutor(slug);

  // Auto-save a cada 30s
  clearInterval(L.autoSaveTimer);
  L.autoSaveTimer = setInterval(() => salvarProgresso(), 30000);
  L.tempoInicioLeitura = Date.now();
}

/* ══ EPUB.JS ═══════════════════════════════════════════════════ */
async function iniciarEpub(slug, cfiInicial) {
  const viewer = document.getElementById('epubViewer');
  viewer.innerHTML = '<div style="text-align:center;padding:3rem;color:var(--texto-3)"><i class="fa fa-spinner fa-spin fa-2x"></i></div>';

  // Destruir instância anterior
  if (L.rendition) { try { L.rendition.destroy(); } catch(e){} }
  if (L.book)      { try { L.book.destroy();      } catch(e){} }

  // URL segura via backend
  const epubUrl = `backend/acesso.php?acao=servir&slug=${encodeURIComponent(slug)}`;

  try {
    L.book = ePub(epubUrl, { openAs: 'epub' });

    // Calcular largura da coluna
    const larguras = { estreita: '560px', media: '680px', larga: '820px' };
    const largPx   = larguras[L.prefs.largura] || '680px';

    // 'scrolled' + 'continuous': carrega capítulos conforme rola (lazy loading).
    // O usuário não precisa clicar "Próximo" — basta descer a página.
    L.rendition = L.book.renderTo('epubViewer', {
      flow:    'scrolled',
      manager: 'continuous',
      width:   '100%',
      height:  '100%',
      spread:  'none',
      stylesheet: `
        body {
          font-size: ${L.prefs.tamanho}px !important;
          line-height: ${L.prefs.espacamento} !important;
          max-width: ${largPx} !important;
          margin: 0 auto !important;
          padding: 2rem ${L.prefs.largura === 'larga' ? '1.5rem' : '2.5rem'} !important;
        }
      `,
    });

    await L.book.ready;

    // Gera posições para calcular percentual correto no evento 'relocated'.
    // Async — não bloqueia a renderização; quando terminar, 'relocated' passa a
    // ter location.start.percentage preciso (0–1 do livro inteiro).
    L.book.locations.generate(1024).catch(() => {});

    // Preencher seletor de capítulos
    preencherSeletorCapitulos();

    // ── Registrar eventos ANTES do display() para não perder o 1º render ──

    // Injetar estilos em cada capítulo que carregar
    L.rendition.on('rendered', (section, view) => {
      const win = view?.window || view?.contents?.window;
      injetarEstilosIframe(win);
    });

    // Eventos do rendition
    L.rendition.on('relocated', location => {
      L.cfiAtual      = location.start.cfi;
      L.capituloAtual = location.start.href;

      // location.start.percentage só fica disponível após locations.generate().
      // Enquanto isso (ou se falhar), estima pelo índice do capítulo no spine.
      const rawPct = location.start.percentage;
      if (rawPct !== undefined && rawPct !== null && !isNaN(rawPct)) {
        L.pctAtual = Math.min(100, parseFloat((rawPct * 100).toFixed(1)));
      } else {
        const idx   = location.start.index ?? 0;
        const total = L.book?.spine?.length || 1;
        L.pctAtual  = Math.min(100, parseFloat(((idx / total) * 100).toFixed(1)));
      }

      atualizarBarraProgresso(L.pctAtual);
      atualizarCapituloHeader(location.start.href);
      atualizarBotoesNav();
      exibirNotasAutorParaPosicao(L.cfiAtual);

      // Verificar conquistas localmente
      verificarConquistasLocal(L.pctAtual);

      // Amostra: bloquear ao atingir 10%
      if (L.modoAmostra && !L.paywallAtivo && L.pctAtual >= L.limiteAmostra) {
        exibirPaywall();
      }
    });

    // Menu de contexto ao selecionar texto
    L.rendition.on('selected', (cfiRange, contents) => {
      L.selecaoAtual = { cfiRange, trecho: contents.window.getSelection().toString() };
      mostrarMenuContexto(contents);
    });

    // Teclado: setas para navegar capítulos
    L.rendition.on('keyup', e => {
      if (e.key === 'ArrowRight') proximoCapitulo();
      if (e.key === 'ArrowLeft')  anteriorCapitulo();
    });

    // ── Navegar para posição salva ou início ──
    if (cfiInicial) {
      await L.rendition.display(cfiInicial);
    } else {
      await L.rendition.display();
    }

    // Aplicar preferências após o 1º render (pequeno delay p/ iframe estar pronto)
    setTimeout(forceAtualizarEstilos, 200);

    // Rastrear progresso via scroll (essencial para conquistas com flow:scrolled)
    setupScrollProgressTracker();

    viewer.querySelector('div')?.remove(); // remover loading

  } catch (err) {
    console.error('[Leitor] Erro epub.js:', err);
    viewer.innerHTML = `<div style="text-align:center;padding:3rem;color:var(--texto-3)">
      <i class="fa fa-exclamation-triangle" style="color:var(--ferrugem);font-size:2rem;display:block;margin-bottom:1rem"></i>
      Não foi possível carregar o livro.<br><small>${esc(err.message)}</small>
    </div>`;
    toast('Erro ao carregar o livro.', 'erro');
  }
}

function preencherSeletorCapitulos() {
  const sel = document.getElementById('seletorCapitulo');
  if (!sel || !L.book?.spine) return;
  sel.innerHTML = '';
  L.book.navigation.toc.forEach((item, i) => {
    const opt = document.createElement('option');
    opt.value       = item.href;
    opt.textContent = item.label || `Capítulo ${i + 1}`;
    sel.appendChild(opt);
  });
}

function atualizarBarraProgresso(pct) {
  const fill = document.getElementById('barraFill');
  const txt  = document.getElementById('barraPct');
  if (fill) fill.style.width = Math.min(100, pct) + '%';
  if (txt)  txt.textContent  = Math.round(pct) + '%';
  document.getElementById('barraProgresso')?.setAttribute('aria-valuenow', Math.round(pct));
}

function atualizarCapituloHeader(href) {
  const cap = document.getElementById('lhCapitulo');
  if (!cap || !L.book) return;
  const item = L.book.navigation?.toc?.find(t => href?.includes(t.href));
  cap.textContent = item?.label || '';
  const sel = document.getElementById('seletorCapitulo');
  if (sel && href) {
    const opt = [...sel.options].find(o => href.includes(o.value));
    if (opt) sel.value = opt.value;
  }
}

function atualizarBotoesNav() {
  // Não reabilitar botões enquanto paywall estiver ativo
  if (L.modoAmostra && L.paywallAtivo) return;
  const btnAnt = document.getElementById('btnAnterior');
  const btnPrx = document.getElementById('btnProximo');
  if (!L.book || !btnAnt || !btnPrx) return;
  const spine   = L.book.spine;
  const atualIdx = spine?.get(L.capituloAtual)?.index ?? 0;
  btnAnt.disabled = atualIdx <= 0;
  btnPrx.disabled = atualIdx >= (spine?.length - 1 || 0);
}

/* ══ NAVEGAÇÃO ═════════════════════════════════════════════════ */
async function anteriorCapitulo() {
  if (!L.rendition) return;
  if (L.modoAmostra && L.paywallAtivo) return; // bloqueado pelo paywall
  try { await L.rendition.prev(); } catch(e){}
}
async function proximoCapitulo() {
  if (!L.rendition) return;
  if (L.modoAmostra && L.paywallAtivo) { exibirPaywall(); return; } // bloqueado pelo paywall
  // Se chegou ao fim → modal de conclusão
  const spine = L.book?.spine;
  const idx   = spine?.get(L.capituloAtual)?.index ?? 0;
  if (idx >= (spine?.length - 1 || 0)) {
    concluirLeitura(); return;
  }
  try { await L.rendition.next(); } catch(e){}
}

document.getElementById('btnAnterior')?.addEventListener('click', anteriorCapitulo);
document.getElementById('btnProximo')?.addEventListener('click', proximoCapitulo);
document.getElementById('seletorCapitulo')?.addEventListener('change', e => {
  if (L.modoAmostra && L.paywallAtivo) return; // bloqueado pelo paywall
  L.rendition?.display(e.target.value);
});

/* ══ PROGRESSO ═════════════════════════════════════════════════ */
async function salvarProgresso() {
  if (!L.livroAtual || !L.cfiAtual) return;
  const tempoMin = Math.round((Date.now() - L.tempoInicioLeitura) / 60000);
  await api('backend/progresso.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      acao: 'salvar', slug: L.livroAtual.slug,
      cfi: L.cfiAtual, percentual: L.pctAtual,
      capitulo: L.capituloAtual, tempo_min: tempoMin,
    }),
  });
  L.tempoInicioLeitura = Date.now();
}

async function concluirLeitura() {
  await api('backend/progresso.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ acao: 'concluir', slug: L.livroAtual.slug }),
  });
  atualizarBarraProgresso(100);
  mostrarModalFeedback();
}

/* ══ INJEÇÃO DE CSS NOS IFRAMES DO EPUB ═══════════════════════
   themes.override do epub.js não funciona de forma confiável com
   o manager 'continuous'. Injetamos um <style id="rd-prefs"> diretamente
   em cada iframe renderizado.
   ═══════════════════════════════════════════════════════════════ */

/** Gera o bloco CSS com todas as preferências atuais */
function gerarCSSLeitor() {
  const larguras = { estreita:'560px', media:'680px', larga:'820px' };
  const fontes   = {
    serifada:     '"Georgia","Times New Roman",serif',
    'sem-serifa': '"Segoe UI",system-ui,-apple-system,sans-serif',
    manuscrita:   '"Georgia",cursive,serif',
  };
  // Cores por tema — necessário porque os iframes são isolados e
  // não enxergam as variáveis CSS do documento pai
  const temasCores = {
    claro:  { cor:'#2C1810', fundo:'#FAF7F2' },
    sepia:  { cor:'#3B2D1F', fundo:'#F4ECD8' },
    escuro: { cor:'#D4C5A0', fundo:'#0D0A07' },
  };
  const tc  = temasCores[L.prefs.tema] || temasCores.claro;
  const pad = L.prefs.largura === 'larga' ? '1.5rem' : '2.5rem';

  return `
    html, body {
      font-size:   ${L.prefs.tamanho}px !important;
      line-height: ${L.prefs.espacamento} !important;
      font-family: ${fontes[L.prefs.fonte] || fontes.serifada} !important;
      color:       ${tc.cor}   !important;
      background:  ${tc.fundo} !important;
      max-width:   ${larguras[L.prefs.largura] || '680px'} !important;
      margin:      0 auto   !important;
      padding:     2rem ${pad} !important;
    }
    /* Garantir que todos os elementos de texto herdem a cor */
    p, span, em, strong, a, h1, h2, h3, h4, h5, h6, li,
    td, th, blockquote, cite, q, code, pre {
      color: inherit !important;
    }
  `;
}

/** Injeta (ou atualiza) o bloco de estilos num iframe individual */
function injetarEstilosIframe(win) {
  if (!win?.document) return;
  const doc = win.document;
  const id  = 'rd-prefs';
  let el    = doc.getElementById(id);
  if (!el) {
    el    = doc.createElement('style');
    el.id = id;
    (doc.head || doc.documentElement)?.appendChild(el);
  }
  el.textContent = gerarCSSLeitor();
}

/** Atualiza TODOS os iframes atualmente carregados pelo rendition.
 *  Abordagem 1: API interna do manager (mais eficiente)
 *  Abordagem 2: varrer iframes no DOM (fallback confiável) */
function forceAtualizarEstilos() {
  if (!L.rendition) return;
  let injetado = 0;

  // ── Abordagem 1: path interno do epub.js continuous manager ──
  try {
    const mgr   = L.rendition.manager;
    const viewArr = mgr?.views?._views      // ContinuousViewManager v0.3.x
                  || mgr?._views            // outros managers
                  || [];
    viewArr.forEach(v => {
      const win = v?.window || v?.contents?.window;
      if (win?.document) { injetarEstilosIframe(win); injetado++; }
    });
  } catch (_) {}

  // ── Abordagem 2: percorrer iframes no DOM (sempre funciona) ──
  if (!injetado) {
    try {
      document.querySelectorAll('#epubViewer iframe').forEach(f => {
        try {
          if (f.contentWindow?.document) {
            injetarEstilosIframe(f.contentWindow);
            injetado++;
          }
        } catch (_) {}
      });
    } catch (_) {}
  }
}

/* ══ PREFERÊNCIAS TIPOGRÁFICAS ════════════════════════════════ */
function aplicarPreferencias(p) {
  L.prefs = { ...L.prefs, ...p };
  const body = document.body;
  body.className = body.className
    .replace(/\bfonte-\S+/g,'')
    .replace(/\btema-\S+/g,'')
    .trim();
  body.classList.add('fonte-' + L.prefs.fonte, 'tema-' + L.prefs.tema);

  // Sincronizar controles visuais
  const sl = document.getElementById('sliderTamanho');
  const se = document.getElementById('sliderEspacamento');
  if (sl) { sl.value = L.prefs.tamanho; document.getElementById('valTamanho').textContent = L.prefs.tamanho + 'px'; }
  if (se) { se.value = Math.round(L.prefs.espacamento * 10); document.getElementById('valEspacamento').textContent = parseFloat(L.prefs.espacamento).toFixed(1); }

  document.querySelectorAll('.cfg-btn-fonte').forEach(b => b.classList.toggle('ativo', b.dataset.fonte === L.prefs.fonte));
  document.querySelectorAll('.cfg-btn-larg').forEach(b  => b.classList.toggle('ativo', b.dataset.larg  === L.prefs.largura));
  document.querySelectorAll('.cfg-btn-tema').forEach(b  => {
    b.style.borderColor = b.dataset.tema === L.prefs.tema ? 'var(--ouro)' : 'transparent';
  });

  // Aplicar ao(s) iframe(s) do epub
  forceAtualizarEstilos();
}

// ── Eventos dos controles de configuração ──────────────────────
document.getElementById('sliderTamanho')?.addEventListener('input', e => {
  L.prefs.tamanho = parseInt(e.target.value);
  document.getElementById('valTamanho').textContent = L.prefs.tamanho + 'px';
  forceAtualizarEstilos();
});
document.getElementById('sliderEspacamento')?.addEventListener('input', e => {
  L.prefs.espacamento = (parseInt(e.target.value) / 10).toFixed(1);
  document.getElementById('valEspacamento').textContent = L.prefs.espacamento;
  forceAtualizarEstilos();
});
document.querySelectorAll('.cfg-btn-fonte').forEach(b => b.addEventListener('click', () => {
  document.querySelectorAll('.cfg-btn-fonte').forEach(x => x.classList.remove('ativo'));
  b.classList.add('ativo'); L.prefs.fonte = b.dataset.fonte;
  aplicarPreferencias(L.prefs);
}));
document.querySelectorAll('.cfg-btn-larg').forEach(b => b.addEventListener('click', () => {
  document.querySelectorAll('.cfg-btn-larg').forEach(x => x.classList.remove('ativo'));
  b.classList.add('ativo'); L.prefs.largura = b.dataset.larg;
  aplicarPreferencias(L.prefs);
}));
document.querySelectorAll('.cfg-btn-tema').forEach(b => b.addEventListener('click', () => {
  L.prefs.tema = b.dataset.tema;
  aplicarPreferencias(L.prefs);
}));
document.getElementById('btnSalvarConfig')?.addEventListener('click', async () => {
  const d = await api('backend/progresso.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ acao: 'salvar_preferencias', ...L.prefs }),
  });
  toast(d.ok ? 'Preferências salvas!' : 'Erro ao salvar.', d.ok ? 'ok' : 'erro');
  fecharPainel('config');
});

/* ══ ANOTAÇÕES ═════════════════════════════════════════════════ */
async function carregarAnotacoes(slug) {
  const d = await api(`backend/anotacoes.php?acao=listar&slug=${encodeURIComponent(slug)}`);
  if (d.ok) { L.anotacoes = d.anotacoes; renderizarAnotacoes(); }
}

function renderizarAnotacoes() {
  const lista = document.getElementById('listaAnotacoes');
  if (!lista) return;
  if (!L.anotacoes.length) {
    lista.innerHTML = '<p class="anot-vazio">Nenhuma anotação ainda.<br>Selecione um trecho do texto para anotar.</p>';
    return;
  }
  lista.innerHTML = L.anotacoes.map(a => `
    <div class="anot-item" style="border-left-color:${esc(a.cor)}">
      ${a.trecho ? `<div class="anot-item-trecho">"${esc(a.trecho.substring(0,80))}${a.trecho.length>80?'…':''}"</div>` : ''}
      <div class="anot-item-texto">${esc(a.anotacao)}</div>
      <div class="anot-item-data">${dataFmt(a.criado_em)}</div>
      <button class="anot-item-del" onclick="excluirAnotacao(${a.id})" aria-label="Excluir anotação">
        <i class="fa fa-xmark"></i>
      </button>
    </div>`
  ).join('');
}

document.getElementById('btnSalvarAnot')?.addEventListener('click', async () => {
  const texto = document.getElementById('anotNovoTexto').value.trim();
  if (!texto) { toast('Escreva algo antes de salvar.', 'erro'); return; }
  const d = await api('backend/anotacoes.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      acao: 'criar', slug: L.livroAtual?.slug,
      cfi: L.cfiAtual, cfi_range: L.selecaoAtual?.cfiRange || '',
      trecho: L.selecaoAtual?.trecho || '', anotacao: texto, cor: L.corAnotacao,
    }),
  });
  if (d.ok) {
    document.getElementById('anotNovoTexto').value = '';
    toast('Anotação salva!');
    await carregarAnotacoes(L.livroAtual.slug);
  } else { toast('Erro ao salvar anotação.', 'erro'); }
});

async function excluirAnotacao(id) {
  if (!confirm('Excluir esta anotação?')) return;
  const d = await api('backend/anotacoes.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ acao: 'excluir', id }),
  });
  if (d.ok) { toast('Anotação excluída.'); await carregarAnotacoes(L.livroAtual.slug); }
}

// Cores de anotação
document.querySelectorAll('.anot-cor').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.anot-cor').forEach(b => b.classList.remove('ativo'));
    btn.classList.add('ativo');
    L.corAnotacao = btn.dataset.cor;
  });
});

/* ══ MARCAÇÕES (HIGHLIGHTS) ════════════════════════════════════ */
async function carregarMarcacoes(slug) {
  const d = await api(`backend/marcacoes.php?acao=listar&slug=${encodeURIComponent(slug)}`);
  if (d.ok) {
    L.marcacoes = d.marcacoes;
    L.marcacoes.forEach(m => {
      try { L.rendition?.annotations.highlight(m.cfi_range, {}, null, 'hl-' + m.id, { 'fill': corHex(m.cor), 'fill-opacity': '0.3' }); }
      catch(e){}
    });
  }
}

function corHex(nome) {
  const mapa = { amarelo:'#FFD700', verde:'#7EC8A4', azul:'#7EC8E0', rosa:'#E07EC8', laranja:'#FFB347' };
  return mapa[nome] || '#FFD700';
}

/* Menu de contexto ao selecionar texto */
function mostrarMenuContexto(contents) {
  if (!L.selecaoAtual?.trecho?.trim()) return;
  const win = contents.window;
  // Injetar mini-toolbar no iframe
  win.document.querySelectorAll('.rd-ctx-menu').forEach(el => el.remove());
  const sel   = win.getSelection();
  if (!sel?.rangeCount) return;
  const rect  = sel.getRangeAt(0).getBoundingClientRect();

  const menu  = win.document.createElement('div');
  menu.className = 'rd-ctx-menu';
  menu.style.cssText = `
    position:fixed;left:${rect.left + rect.width/2 - 90}px;
    top:${rect.top - 44}px;
    background:#1A1208;border:1px solid #B8860B;border-radius:8px;
    display:flex;gap:2px;padding:4px;z-index:9999;box-shadow:0 4px 16px rgba(0,0,0,.4);
  `;
  const btns = [
    { label:'✏ Anotar', fn: () => { abrirPainel('anotacoes'); menu.remove(); } },
    { label:'🖍 Destacar', fn: () => { destacarSelecao(); menu.remove(); } },
    { label:'⚠ Erro', fn: () => { abrirModalErro(); menu.remove(); } },
    { label:'📤 Indicar', fn: () => { abrirIndicar(); menu.remove(); } },
  ];
  btns.forEach(b => {
    const btn = win.document.createElement('button');
    btn.textContent = b.label;
    btn.style.cssText = 'background:transparent;border:none;color:#D4C5A0;padding:4px 8px;cursor:pointer;font-size:12px;border-radius:4px;';
    btn.onmouseenter = () => btn.style.background = 'rgba(184,134,11,.2)';
    btn.onmouseleave = () => btn.style.background = 'transparent';
    btn.onclick = b.fn;
    menu.appendChild(btn);
  });
  win.document.body.appendChild(menu);
  setTimeout(() => menu.remove(), 5000);
}

async function destacarSelecao() {
  if (!L.selecaoAtual) return;
  const d = await api('backend/marcacoes.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      acao: 'criar', slug: L.livroAtual?.slug,
      cfi_range: L.selecaoAtual.cfiRange, trecho: L.selecaoAtual.trecho, cor: 'amarelo',
    }),
  });
  if (d.ok) {
    toast('Trecho destacado!');
    try { L.rendition?.annotations.highlight(L.selecaoAtual.cfiRange, {}, null, 'hl-new-' + d.id, { 'fill':'#FFD700','fill-opacity':'0.3' }); } catch(e){}
  }
}

/* ══ NOTAS DO AUTOR ════════════════════════════════════════════ */
async function carregarNotasAutor(slug) {
  const d = await api(`backend/notas_autor.php?acao=listar&slug=${encodeURIComponent(slug)}`);
  if (d.ok) L.notas = d.notas || [];
}

function exibirNotasAutorParaPosicao(cfi) {
  // Verificar se alguma nota está próxima desta posição CFI
  // (comparação simplificada por capítulo)
  const wrap = document.getElementById('notaAutorWrap');
  const cont = document.getElementById('notaAutorConteudo');
  if (!wrap || !cont || !L.notas.length) return;

  // Mostrar nota do capítulo atual se existir
  const nota = L.notas.find(n => cfi && n.cfi && cfi.includes(n.cfi.split('!')[0].split('[')[0]));
  if (nota) {
    const tipos = { bastidor:'🎬 Bastidor', personagem:'👤 Personagem', cena:'🎭 Cena', curiosidade:'💡 Curiosidade', outro:'📝 Nota' };
    cont.innerHTML = `
      <button class="nota-autor-fechar" onclick="document.getElementById('notaAutorWrap').style.display='none'"
              aria-label="Fechar nota">✕</button>
      <span class="nota-autor-tipo">${tipos[nota.tipo] || '📝 Nota do Autor'}</span>
      ${nota.titulo ? `<div class="nota-autor-titulo">${esc(nota.titulo)}</div>` : ''}
      <div>${esc(nota.conteudo)}</div>`;
    wrap.style.display = 'block';
  } else {
    wrap.style.display = 'none';
  }
}

/* ══ CONQUISTAS ════════════════════════════════════════════════ */
async function carregarConquistas(slug) {
  const d = await api(`backend/conquistas.php?acao=listar&slug=${encodeURIComponent(slug)}`);
  if (d.ok) { L.conquistas = d.conquistas; renderizarConquistas(); }
}

function renderizarConquistas() {
  const lista = document.getElementById('listaConquistas');
  if (!lista) return;
  const todas = [
    { tipo:'inicio', emoji:'📖', titulo:'Primeira Página',   desc:'Você começou!' },
    { tipo:'25pct',  emoji:'⭐', titulo:'Um quarto lido',    desc:'25% da obra' },
    { tipo:'50pct',  emoji:'🌟', titulo:'Metade da Jornada', desc:'Você está na metade!' },
    { tipo:'75pct',  emoji:'🔥', titulo:'Quase lá!',         desc:'75% lidos' },
    { tipo:'100pct', emoji:'🏆', titulo:'Obra Concluída',    desc:'Parabéns!' },
    { tipo:'velocidade', emoji:'⚡', titulo:'Leitor Veloz',   desc:'Recorde de leitura' },
    { tipo:'maratona',   emoji:'🎖', titulo:'Maratonista',    desc:'+3h contínuas' },
  ];
  const conquistadas = L.conquistas.map(c => c.tipo);
  lista.innerHTML = todas.map(c => {
    const conq = L.conquistas.find(x => x.tipo === c.tipo);
    const ok   = conquistadas.includes(c.tipo);
    return `<div class="conquista-item ${ok ? '' : 'conquista-bloq'} ${conq?.new ? 'nova' : ''}">
      <span class="conquista-emoji">${c.emoji}</span>
      <div class="conquista-info">
        <div class="conquista-titulo">${c.titulo}</div>
        <div class="conquista-desc">${c.desc}</div>
        ${ok && conq ? `<div class="conquista-data">${dataFmt(conq.conquistado_em)}</div>` : ''}
      </div>
    </div>`;
  }).join('');
}

function verificarConquistasLocal(pct) {
  const slug = L.livroAtual?.slug;
  if (!slug) return;

  const emojisPorTipo = { inicio:'📖', '25pct':'⭐', '50pct':'🌟', '75pct':'🔥' };
  const textosPorTipo = { inicio:'Você começou a ler!', '25pct':'25% concluído!', '50pct':'Metade da jornada!', '75pct':'Quase lá — 75%!' };
  const marcos = [
    { tipo:'inicio', pct:1  },
    { tipo:'25pct',  pct:25 },
    { tipo:'50pct',  pct:50 },
    { tipo:'75pct',  pct:75 },
  ];

  marcos.forEach(m => {
    if (pct >= m.pct && !L.conquistas.find(c => c.tipo === m.tipo)) {
      // ① Atualizar estado local IMEDIATAMENTE (sem aguardar backend)
      L.conquistas.push({
        tipo: m.tipo,
        conquistado_em: new Date().toISOString(),
        new: true,
      });
      renderizarConquistas(); // atualizar painel na hora

      // ② Toast de parabéns
      toast(`${emojisPorTipo[m.tipo]} ${textosPorTipo[m.tipo]}`, 'ok', 4500);

      // ③ Tentar salvar/notificar no backend (assíncrono, falha silenciosa)
      api('backend/conquistas.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ acao:'registrar', slug, tipo: m.tipo }),
      }).catch(() => {}); // ignorar erros de backend
    }
  });
}

/* ══ RELÓGIO DE META ═══════════════════════════════════════════ */
function formatarTempo(seg) {
  const h = Math.floor(seg/3600), m = Math.floor((seg%3600)/60), s = seg%60;
  return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
}
function atualizarDisplayMeta() {
  const disp = document.getElementById('metaDisplayGrande');
  const mini = document.getElementById('metaDisplay');
  let txt = '';
  if (L.timer.tipo === 'relogio') {
    txt = new Date().toLocaleTimeString('pt-BR');
  } else if (L.timer.tipo === 'cronometro') {
    txt = formatarTempo(L.timer.segundos);
  } else {
    const restante = Math.max(0, L.timer.alvo - L.timer.segundos);
    txt = formatarTempo(restante);
    if (restante === 0 && L.timer.ativo) {
      pararMeta(); toast('⏰ Meta de leitura atingida! Parabéns!', 'ok', 5000);
    }
  }
  if (disp) disp.textContent = txt;
  if (mini && !L.timer.oculto) mini.textContent = txt;
}

function iniciarMeta() {
  if (L.timer.tipo === 'regressivo') {
    L.timer.alvo = parseInt(document.getElementById('metaMinutos')?.value || 30) * 60;
  }
  L.timer.ativo = true;
  L.timer.intervalo = setInterval(() => {
    if (L.timer.tipo !== 'relogio') L.timer.segundos++;
    atualizarDisplayMeta();
  }, 1000);
  atualizarDisplayMeta();
  document.getElementById('btnMetaIniciar').style.display = 'none';
  document.getElementById('btnMetaPausar').style.display  = 'inline-flex';
  const mini = document.getElementById('metaDisplay');
  if (mini && !L.timer.oculto) mini.style.display = 'inline';
}
function pausarMeta() {
  clearInterval(L.timer.intervalo);
  L.timer.ativo = false;
  document.getElementById('btnMetaIniciar').style.display = 'inline-flex';
  document.getElementById('btnMetaPausar').style.display  = 'none';
}
function pararMeta() { pausarMeta(); L.timer.segundos = 0; atualizarDisplayMeta(); }

document.querySelectorAll('[data-meta-tipo]').forEach(b => b.addEventListener('click', () => {
  document.querySelectorAll('[data-meta-tipo]').forEach(x => x.classList.remove('ativo'));
  b.classList.add('ativo'); L.timer.tipo = b.dataset.metaTipo;
  L.timer.segundos = 0;
  const rw = document.getElementById('metaRegressivoWrap');
  if (rw) rw.style.display = L.timer.tipo === 'regressivo' ? 'block' : 'none';
  atualizarDisplayMeta();
}));
document.getElementById('btnMetaIniciar')?.addEventListener('click', iniciarMeta);
document.getElementById('btnMetaPausar')?.addEventListener('click', pausarMeta);
document.getElementById('btnMetaReset')?.addEventListener('click',  pararMeta);
document.getElementById('metaOcultar')?.addEventListener('change', e => {
  L.timer.oculto = e.target.checked;
  const mini = document.getElementById('metaDisplay');
  if (mini) mini.style.display = L.timer.oculto ? 'none' : 'inline';
});
// Relógio sempre atualiza, mesmo sem iniciar
setInterval(() => { if (L.timer.tipo === 'relogio') atualizarDisplayMeta(); }, 1000);
atualizarDisplayMeta();

/* ══ MODO NÃO PERTURBE ════════════════════════════════════════ */
document.getElementById('btnTelaCheia')?.addEventListener('click', () => {
  const body = document.body;
  const ativo = body.classList.toggle('nao-perturbe');
  document.getElementById('btnTelaCheia')?.classList.toggle('ativo', ativo);
  if (ativo && document.documentElement.requestFullscreen) {
    document.documentElement.requestFullscreen().catch(() => {});
  } else if (!ativo && document.exitFullscreen) {
    document.exitFullscreen().catch(() => {});
  }
});

/* ══ MODO FOCO (TDA/DISLEXIA) ═════════════════════════════════ */
document.getElementById('toggleFoco')?.addEventListener('change', e => {
  const overlay = document.getElementById('focoOverlay');
  if (overlay) overlay.style.display = e.target.checked ? 'flex' : 'none';
});

/* ══ REPORTAR ERRO ═════════════════════════════════════════════ */
function abrirModalErro() {
  const modal  = document.getElementById('modalErro');
  const trecho = document.getElementById('erroTrechoSel');
  if (!modal) return;
  if (trecho) trecho.textContent = '"' + (L.selecaoAtual?.trecho || '').substring(0, 120) + '"';
  modal.removeAttribute('hidden');
}
document.getElementById('btnCancelarErro')?.addEventListener('click', () => {
  document.getElementById('modalErro')?.setAttribute('hidden','');
});
document.getElementById('btnEnviarErro')?.addEventListener('click', async () => {
  const desc = document.getElementById('erroDescricao').value.trim();
  const d = await api('backend/erros.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      slug: L.livroAtual?.slug, cfi: L.cfiAtual,
      trecho: L.selecaoAtual?.trecho || '', descricao: desc,
    }),
  });
  toast(d.ok ? d.mensagem : 'Erro ao reportar.', d.ok ? 'ok' : 'erro');
  document.getElementById('modalErro')?.setAttribute('hidden','');
  document.getElementById('erroDescricao').value = '';
});

/* ══ FEEDBACK AO CONCLUIR ══════════════════════════════════════ */
let fbEstrelas = 5;
function mostrarModalFeedback() {
  document.getElementById('modalFeedback')?.removeAttribute('hidden');
  toast('🏆 Parabéns! Você concluiu a leitura!', 'ok', 5000);
}
document.querySelectorAll('.fb-star').forEach(s => {
  s.addEventListener('click', () => {
    fbEstrelas = parseInt(s.dataset.n);
    document.querySelectorAll('.fb-star').forEach((x,i) => x.classList.toggle('ativa', i < fbEstrelas));
  });
  s.addEventListener('mouseenter', () => {
    document.querySelectorAll('.fb-star').forEach((x,i) => x.classList.toggle('ativa', i < parseInt(s.dataset.n)));
  });
});
document.getElementById('btnEnviarFeedback')?.addEventListener('click', async () => {
  const d = await api('backend/feedback.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      slug: L.livroAtual?.slug, estrelas: fbEstrelas,
      texto: document.getElementById('fbTexto').value.trim(),
      compartilhou: document.getElementById('fbCompartilhou').checked ? 1 : 0,
    }),
  });
  toast(d.ok ? d.mensagem : 'Obrigado!');
  document.getElementById('modalFeedback')?.setAttribute('hidden','');
  if (document.getElementById('fbCompartilhou').checked) abrirIndicar();
});
document.getElementById('btnPularFeedback')?.addEventListener('click', () => {
  document.getElementById('modalFeedback')?.setAttribute('hidden','');
});

/* ══ INDICAR PARA AMIGO ════════════════════════════════════════ */
function abrirIndicar() {
  const livro = L.livroAtual;
  if (!livro) return;
  const url   = `https://www.roberiodiogenes.com/livros.html#${livro.slug}`;
  const titulo= encodeURIComponent(livro.titulo);
  const msg   = encodeURIComponent(`Estou lendo "${livro.titulo}" de Robério Diógenes e recomendo! ${url}`);
  const btns  = document.getElementById('indicarBtns');
  if (btns) btns.innerHTML = `
    <a href="https://wa.me/?text=${msg}" target="_blank" rel="noopener" class="indicar-btn">
      <i class="fa-brands fa-whatsapp" style="color:#25D366"></i> Enviar via WhatsApp
    </a>
    <a href="mailto:?subject=Recomendo este livro&body=${msg}" class="indicar-btn">
      <i class="fa fa-envelope" style="color:var(--ouro)"></i> Enviar por e-mail
    </a>
    <button onclick="navigator.clipboard?.writeText('${url}');toast('Link copiado!')" class="indicar-btn">
      <i class="fa fa-link" style="color:var(--texto-3)"></i> Copiar link
    </button>`;
  abrirPainel('indicar');
}

/* ══ BOTÕES DO HEADER ══════════════════════════════════════════ */
document.getElementById('btnBiblioteca')?.addEventListener('click', async () => {
  await salvarProgresso();
  clearInterval(L.autoSaveTimer);
  if (L.rendition) { try { L.rendition.destroy(); } catch(e){} }
  if (L.book)      { try { L.book.destroy();      } catch(e){} }
  document.getElementById('telaLeitor').style.display  = 'none';
  document.getElementById('telaSelecao').style.display = 'flex';
  carregarBiblioteca();
});
document.getElementById('btnConfig')?.addEventListener('click',     () => abrirPainel('config'));
document.getElementById('btnAnotacoes')?.addEventListener('click',  () => abrirPainel('anotacoes'));
document.getElementById('btnConquistas')?.addEventListener('click', () => { carregarConquistas(L.livroAtual?.slug); abrirPainel('conquistas'); });
document.getElementById('btnMetaToggle')?.addEventListener('click', () => abrirPainel('meta'));

// Fechar painéis
document.querySelectorAll('.pl-fechar').forEach(b => b.addEventListener('click', () => fecharPainel(b.dataset.painel)));
document.getElementById('painelOverlay')?.addEventListener('click', fecharTodosPaineis);
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') fecharTodosPaineis();
});

/* ══ SALVAR AO SAIR DA PÁGINA ═════════════════════════════════ */
window.addEventListener('beforeunload', () => {
  if (L.livroAtual && L.cfiAtual) {
    // Usar sendBeacon para garantir envio mesmo ao fechar
    const dados = JSON.stringify({
      acao: 'salvar', slug: L.livroAtual.slug,
      cfi: L.cfiAtual, percentual: L.pctAtual, capitulo: L.capituloAtual,
    });
    navigator.sendBeacon('backend/progresso.php', new Blob([dados], { type:'application/json' }));
  }
});

/* ══ SCROLL PROGRESS TRACKER ══════════════════════════════════
   Com flow:'scrolled' + manager:'continuous', o evento 'relocated'
   do epub.js só dispara ao trocar de seção — não durante o scroll
   dentro de um capítulo. Para livros com 1 capítulo (a maioria), o
   progresso nunca atualizaria. Este tracker usa o scroll do
   container para calcular progresso e disparar conquistas.
   ═══════════════════════════════════════════════════════════════ */
function setupScrollProgressTracker() {
  // epub.js em modo continuous pode fazer o scroll acontecer em #epubViewer
  // (o container que ele gerencia) em vez de #leitorMain. Escutamos ambos —
  // o que realmente tiver scrollHeight > clientHeight será o container ativo.
  ['leitorMain', 'epubViewer'].forEach(id => {
    const container = document.getElementById(id);
    if (!container) return;

    let tid;
    container.addEventListener('scroll', () => {
      clearTimeout(tid);
      tid = setTimeout(() => {
        const scrolled = container.scrollTop;
        const total    = container.scrollHeight - container.clientHeight;
        if (total < 10) return; // sem conteúdo suficiente ainda

        const pct = Math.min(100, parseFloat(((scrolled / total) * 100).toFixed(1)));
        if (Math.abs(pct - L.pctAtual) < 0.5) return; // ignorar micro-variações

        L.pctAtual = pct;
        atualizarBarraProgresso(pct);
        verificarConquistasLocal(pct);

        if (L.modoAmostra && !L.paywallAtivo && pct >= L.limiteAmostra) {
          exibirPaywall();
        }
      }, 250);
    }, { passive: true });
  });
}

/* ══ PAYWALL DE AMOSTRA (10%) ══════════════════════════════════ */
function _bloquearScroll() {
  ['leitorMain', 'epubViewer'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.overflowY = 'hidden';
  });
  // Bloquear scroll interno dos iframes do epub.js
  document.querySelectorAll('#epubViewer iframe').forEach(f => {
    try {
      f.contentDocument.documentElement.style.overflow = 'hidden';
      f.contentDocument.body.style.overflow            = 'hidden';
    } catch(e) {}
  });
}
function _desbloquearScroll() {
  ['leitorMain', 'epubViewer'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.overflowY = '';
  });
}

function exibirPaywall() {
  L.paywallAtivo = true;

  // Bloquear todo o scroll (container e iframes internos)
  _bloquearScroll();

  // Adicionar blur/pointer-events ao conteúdo epub
  if (L.rendition) {
    try {
      L.rendition.themes.override('body', {
        'filter': 'blur(5px) !important',
        'user-select': 'none !important',
        'pointer-events': 'none !important',
      });
    } catch(e) {}
  }

  // Preencher card do paywall com dados do livro
  const livro = L.livroAtual;
  if (livro) {
    const tituloEl = document.getElementById('payTitulo');
    const precoEl  = document.getElementById('payPreco');
    const livroBox = document.getElementById('payLivroInfo');

    if (tituloEl) tituloEl.textContent = livro.titulo || '—';
    if (precoEl)  precoEl.textContent  = livro.preco ? 'R$ ' + Number(livro.preco).toFixed(2).replace('.',',') : 'R$ 19,90';

    // Inserir capa se disponível
    if (livroBox && livro.capa_img) {
      livroBox.innerHTML = `
        <img src="../${esc(livro.capa_img)}" alt="Capa" class="pay-livro-capa"
             onerror="this.style.display='none'">
        <div>
          <div class="pay-livro-titulo">${esc(livro.titulo)}</div>
          <div class="pay-livro-autor">Robério Diógenes</div>
          <div class="pay-livro-preco">${precoEl?.textContent || 'R$ 19,90'}</div>
        </div>`;
    }
  }

  const overlay = document.getElementById('paywallOverlay');
  if (overlay) overlay.style.display = 'flex';

  // Desabilitar controles de navegação (não podem avançar/voltar/pular capítulo)
  const btnAnt = document.getElementById('btnAnterior');
  const btnPrx = document.getElementById('btnProximo');
  const selCap = document.getElementById('seletorCapitulo');
  if (btnAnt) { btnAnt.disabled = true; btnAnt.title = 'Adquira o livro para continuar lendo'; }
  if (btnPrx) { btnPrx.disabled = true; btnPrx.title = 'Adquira o livro para continuar lendo'; }
  if (selCap) { selCap.disabled = true; }

  // Salvar progresso antes de bloquear
  salvarProgresso();
}

function irComprarLivro() {
  const slug = L.livroAtual?.slug;
  if (slug) {
    window.location.href = '../carrinho.html?add=' + encodeURIComponent(slug);
  } else {
    window.location.href = '../livros.html';
  }
}

function voltarBibliotecaPaywall() {
  clearInterval(L.autoSaveTimer);
  if (L.rendition) { try { L.rendition.destroy(); } catch(e){} }
  if (L.book)      { try { L.book.destroy();      } catch(e){} }
  // Restaurar scroll antes de destruir os iframes
  _desbloquearScroll();
  document.getElementById('paywallOverlay').style.display = 'none';
  document.getElementById('telaLeitor').style.display  = 'none';
  document.getElementById('telaSelecao').style.display = 'flex';
  // Restaurar controles de navegação para o próximo livro
  const btnAnt = document.getElementById('btnAnterior');
  const btnPrx = document.getElementById('btnProximo');
  const selCap = document.getElementById('seletorCapitulo');
  if (btnAnt) { btnAnt.disabled = false; btnAnt.title = 'Capítulo anterior'; }
  if (btnPrx) { btnPrx.disabled = false; btnPrx.title = 'Próximo capítulo'; }
  if (selCap) selCap.disabled = false;
  carregarBiblioteca();
}

/* ══ PARÂMETRO ?livro= NA URL ═════════════════════════════════ */
function livroFromURL() {
  return new URLSearchParams(window.location.search).get('livro');
}

/* ══ INIT ══════════════════════════════════════════════════════ */
(async () => {
  await carregarSessao();
  const slugURL = livroFromURL();
  if (slugURL) {
    await abrirLivro(slugURL);
  } else {
    await carregarBiblioteca();
  }
})();
