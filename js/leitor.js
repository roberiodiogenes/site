/* ================================================================
   ROBÉRIO DIÓGENES — js/leitor.js  v3.0 — DEFINITIVO
   Leitor ePub (epub.js) + HTML fallback
   ================================================================ */
'use strict';

/* ── Estado global ─────────────────────────────────────────────── */
const L = {
  livro: {
    slug:'', titulo:'', pastaConteudo:'',
    formato:'html', arquivoEpub:'',
    totalCapitulos:1,
  },
  estado: {
    percentual:0, carregando:false,
    painelAberto:false, abaAtiva:'config',
    capHtml:1,                    // só para modo HTML
    cfiSalvo:null,                // posição salva no epub
    scrollSalvo:0,
    conquistasVistas: new Set(),
    naoPerturbe:false, focoAtivo:false,
    timer:{ modo:'cronometro', ativo:false, seg:0, meta:25 },
    som:{ ativo:false, tipo:'ondas', vol:0.4 },
    anotacaoCor:'#FFD700',
    salvando:false,
  },
  prefs: {
    fonte:'serifada', tamanhoFonte:18,
    fundoLeitura:'bege', larguraColuna:'media',
    alturaLinha:1.8, rankingOptIn:false,
  },
  dados: {
    anotacoes:[], marcacoes:[],
    avaliacaoAtual:0, notasAutor:{},
    biblioteca:[],
  },
  _epub:null, _rendition:null,
  _timerProgresso:null, _timerPrefs:null,
  BASE: '../backend',
};

/* ── Inicialização ─────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', async () => {
  const cfg = window.LEITOR_CONFIG || {};
  Object.assign(L.livro, {
    slug:          cfg.slug          || '',
    titulo:        cfg.titulo        || 'Livro',
    pastaConteudo: cfg.pastaConteudo || `../livros-conteudo/${cfg.slug}/`,
    formato:       cfg.formato       || 'html',
    arquivoEpub:   cfg.arquivoEpub   || '',
    totalCapitulos:cfg.totalCapitulos|| 1,
  });

  if (!L.livro.slug) { _erroFatal('Livro não identificado.'); return; }

  // 1. Verificar acesso (pode atualizar formato e arquivoEpub do DB)
  const ok = await _verificarAcesso();
  if (!ok) return;

  // 2. Carregar preferências e progresso
  await Promise.all([_carregarPrefs(), _carregarProgresso()]);
  _aplicarPrefs();
  await _carregarNotasAutor();

  // 3. Abrir o livro
  if (L.livro.formato === 'epub' && L.livro.arquivoEpub) {
    await _abrirEpub();
  } else {
    await _carregarCapitulo(L.estado.capHtml);
  }

  // 4. Dados paralelos (não bloqueiam)
  Promise.all([
    _carregarAnotacoes(),
    _carregarMarcacoes(),
    _carregarAvaliacao(),
    _carregarBiblioteca(),
    _carregarConquistas(),
  ]);

  _iniciarListeners();
  _iniciarScrollTracker();
  _iniciarRelogio();
  _injetarDropdownBiblioteca();
});

/* ════════════════════════════════════════════════════════════════
   ACESSO
   ════════════════════════════════════════════════════════════════ */
async function _verificarAcesso() {
  try {
    const r = await fetch(`${L.BASE}/acesso.php?livro=${L.livro.slug}`, {credentials:'include'});
    const d = await r.json();
    if (!d.ok) throw new Error(d.erro);
    if (!d.tem_acesso) {
      _mostrarAcessoNegado(d.motivo, d.mensagem);
      return false;
    }
    // Atualiza com dados definitivos do banco
    if (d.livro) {
      if (d.livro.arquivo_epub)  L.livro.arquivoEpub    = d.livro.arquivo_epub;
      if (d.livro.formato)       L.livro.formato         = d.livro.formato;
      if (d.livro.total_capitulos) L.livro.totalCapitulos = +d.livro.total_capitulos;
      // Se o banco tem arquivo_epub, força epub
      if (d.livro.arquivo_epub && d.livro.arquivo_epub !== '') L.livro.formato = 'epub';
    }
    return true;
  } catch(e) {
    _mostrarAcessoNegado('erro', 'Não foi possível verificar seu acesso.');
    return false;
  }
}

function _mostrarAcessoNegado(motivo, msg) {
  const m = {
    nao_logado:          { i:'🔐', t:'Login necessário',    href:'../login.html',              label:'Fazer login' },
    sem_acesso:          { i:'📖', t:'Livro não adquirido', href:`../livros/${L.livro.slug}.html`, label:'Ver o livro' },
    assinatura_expirada: { i:'⏳', t:'Assinatura expirada',  href:'../leitor/index.html',       label:'Renovar' },
    erro:                { i:'⚠️', t:'Erro',                 href:'javascript:location.reload()', label:'Tentar novamente' },
  };
  const info = m[motivo] || m.sem_acesso;
  const w = document.getElementById('leitor-wrapper');
  if (w) w.innerHTML = `
    <div class="leitor-acesso-negado" role="alert">
      <span class="acesso-icone" aria-hidden="true">${info.i}</span>
      <h2>${info.t}</h2>
      <p>${_esc(msg || 'Você não tem acesso a este livro.')}</p>
      <a href="${info.href}" class="btn-acesso">
        <i class="fa-solid fa-arrow-right"></i> ${info.label}
      </a>
    </div>`;
}

/* ════════════════════════════════════════════════════════════════
   EPUB — renderização e CSS
   ════════════════════════════════════════════════════════════════ */
/* ── Leitor ePub via servidor (sem epub.js) ───────────────────
   O backend extrai o HTML de dentro do .epub e serve limpo,
   sem os CSS problemáticos do Calibre.
   ─────────────────────────────────────────────────────────── */
async function _abrirEpub() {
  const area = document.getElementById('leitor-texto-area');
  area.innerHTML = `<div class="leitor-loading">
    <div class="leitor-loading-spinner"></div>
    <span>Abrindo livro…</span></div>`;

  try {
    // 1. Busca metadados (total de partes, índice)
    const rInfo = await fetch(`${L.BASE}/epub_reader.php?livro=${L.livro.slug}&info=1`, {credentials:'include'});
    const info  = await rInfo.json();
    if (!info.ok) throw new Error(info.erro || 'Erro ao abrir epub');

    L._epubTotal = info.total;   // total de spine items
    L._epubParte = 0;            // parte atual

    // Retoma posição salva (salva como número de parte)
    if (L.estado.cfiSalvo && /^\d+$/.test(L.estado.cfiSalvo)) {
      L._epubParte = Math.min(parseInt(L.estado.cfiSalvo), info.total - 1);
    }

    // 2. Carrega a parte atual
    await _carregarParteEpub(L._epubParte, false);

    // Mostra nav de partes para epub
    _atualizarNavEpub();
    const tNav = document.getElementById('leitor-titulo-nav');
    if (tNav) tNav.textContent = L.livro.titulo;

  } catch(e) {
    console.warn('[Leitor] epub_reader falhou:', e.message);
    // Se o erro é JSON inválido, provavelmente PHP retornou HTML com erro
    // Tenta mostrar mensagem útil
    area.innerHTML = `<div class="leitor-loading" role="alert">
      <p>Não foi possível abrir o livro.</p>
      <p style="font-size:.8em;opacity:.6">
        ${e.message.includes('JSON') 
          ? 'Erro no servidor. Verifique se você tem acesso ao livro e se está logado.'
          : e.message}
      </p>
      <p style="margin-top:1rem">
        <a href="../livros/${L.livro.slug}.html" style="color:var(--ouro)">← Voltar para a página do livro</a>
      </p>
    </div>`;
  }
}

async function _carregarParteEpub(parte, toTop=true) {
  const area = document.getElementById('leitor-texto-area');
  area.innerHTML = `<div class="leitor-loading">
    <div class="leitor-loading-spinner"></div>
    <span>Carregando…</span></div>`;
  if (toTop) window.scrollTo({top:0, behavior:'smooth'});

  try {
    const r = await fetch(
      `${L.BASE}/epub_reader.php?livro=${L.livro.slug}&parte=${parte}`,
      {credentials:'include'}
    );
    const d = await r.json();
    if (!d.ok) throw new Error(d.erro || 'Erro ao carregar parte');

    area.innerHTML = `<article class="leitor-capitulo leitor-epub-conteudo" id="conteudo-principal">
      ${d.html}
    </article>`;

    L._epubParte = parte;
    L._epubTotal = d.total;

    // CSS do leitor (fonte, tamanho, linha)
    _aplicarCssHtmlEpub();

    // Notas do autor
    _injetarNotasAutorHTML();

    // Seleção de texto (igual ao HTML normal — sem iframe)
    area.addEventListener('mouseup',  _onMouseUp);
    area.addEventListener('touchend', _onMouseUp);

    // Progresso baseado em parte atual / total
    const perc = Math.min(100, Math.round(((parte + 1) / d.total) * 100));
    L.estado.percentual = perc;
    _atualizarBarra(perc);
    _atualizarBadgePerc(perc);
    _verificarConquistas(perc);

    // Nav de partes
    _atualizarNavEpub();

    // Salva progresso (usa o número da parte como "cfi")
    clearTimeout(L._timerProgresso);
    L._timerProgresso = setTimeout(() => _salvarProgressoEpub(String(parte), perc), 3000);

  } catch(e) {
    area.innerHTML = `<div class="leitor-loading" role="alert">
      <p>Erro ao carregar.</p>
      <p style="font-size:.8em;opacity:.6">${e.message}</p></div>`;
  }
}

function _aplicarCssHtmlEpub() {
  // Aplica preferências tipográficas ao conteúdo do epub renderizado como HTML
  const art = document.querySelector('.leitor-epub-conteudo');
  if (!art) return;
  const fonte = _fonteNome(L.prefs.fonte);
  const tam   = (L.prefs.tamanhoFonte || 18) + 'px';
  const lh    = L.prefs.alturaLinha || 1.8;
  art.style.cssText = `
    font-family: ${fonte};
    font-size: ${tam};
    line-height: ${lh};
    max-width: var(--leitor-largura, 680px);
    margin: 0 auto;
  `;
  // Aplica em todos os parágrafos para garantir
  art.querySelectorAll('p, li, td').forEach(el => {
    el.style.fontFamily = fonte;
    el.style.fontSize   = tam;
    el.style.lineHeight = String(lh);
  });
}

function _atualizarNavEpub() {
  const total = L._epubTotal || 1;
  const parte = L._epubParte || 0;
  const nav   = document.querySelector('.leitor-nav-caps');
  if (nav) nav.style.display = ''; // mostra a nav

  const bA = document.getElementById('btn-cap-anterior');
  const bP = document.getElementById('btn-cap-proximo');
  const info = document.getElementById('cap-info-atual');

  if (bA) bA.disabled = parte <= 0;
  if (bP) bP.disabled = parte >= total - 1;
  if (info) info.textContent = `Parte ${parte + 1} de ${total}`;

  // Tela de conclusão na última parte
  if (parte >= total - 1) {
    const c = document.getElementById('leitor-conclusao');
    if (c) c.style.display = 'block';
  }
}

function _injetarCssEpub(contents) {
  // Remove injeção anterior
  const old = contents.document?.getElementById('rd-css');
  if (old) old.remove();

  const fonte = _fonteNome(L.prefs.fonte);
  const tam   = (L.prefs.tamanhoFonte || 18) + 'px';
  const lh    = L.prefs.alturaLinha || 1.8;

  const css = `
    body, p, div, span, li, td, h1, h2, h3, h4, h5, h6 {
      font-family: ${fonte} !important;
    }
    p, li, td, div.conteudo {
      font-size: ${tam} !important;
      line-height: ${lh} !important;
    }
    body {
      max-width: 700px !important;
      margin: 0 auto !important;
      padding: 1.5rem 2rem 3rem !important;
      background: transparent !important;
      color: inherit !important;
    }
    img { max-width: 100% !important; height: auto !important; }
  `;
  const el = contents.document.createElement('style');
  el.id = 'rd-css';
  el.textContent = css;
  contents.document.head.appendChild(el);
}

function _injetarSelecaoEpub(contents) {
  // O texto do epub está num iframe — eventos de mouse não sobem ao pai.
  // Injetamos o listener dentro do iframe e comunicamos via postMessage.
  try {
    const doc = contents.document;
    if (!doc) return;
    doc.addEventListener('mouseup', () => {
      const sel = doc.getSelection();
      const txt = sel ? sel.toString().trim() : '';
      // Envia ao documento pai
      window.parent.postMessage({ tipo: 'epub-selecao', texto: txt }, '*');
    });
    doc.addEventListener('touchend', () => {
      const sel = doc.getSelection();
      const txt = sel ? sel.toString().trim() : '';
      window.parent.postMessage({ tipo: 'epub-selecao', texto: txt }, '*');
    });
  } catch(e) {}
}

function _reaplicarCssEpub() {
  // Para epub renderizado como HTML (via epub_reader.php)
  _aplicarCssHtmlEpub();
}

function _fonteNome(nome) {
  return {
    serifada:   'Merriweather, Georgia, serif',
    classica:   "'EB Garamond', Georgia, serif",
    sans:       "'Source Sans 3', Arial, sans-serif",
    manuscrito: 'Caveat, cursive',
  }[nome] || 'Georgia, serif';
}

async function _salvarProgressoEpub(cfi, perc) {
  if (L.estado.salvando) return;
  L.estado.salvando = true;
  try {
    await fetch(`${L.BASE}/leitor.php`, {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({
        acao:'salvar_progresso', livro:L.livro.slug,
        capitulo:1, scroll:0,
        percentual: Math.round(perc * 100) / 100,
        total_paginas:1,
        cfi_posicao: cfi,
      }),
    });
  } catch(e) {} finally { L.estado.salvando = false; }
}

/* ════════════════════════════════════════════════════════════════
   HTML — carregamento de capítulos
   ════════════════════════════════════════════════════════════════ */
async function _carregarCapitulo(n, toTop=false) {
  if (L.estado.carregando) return;
  L.estado.carregando = true;
  const area = document.getElementById('leitor-texto-area');
  area.innerHTML = `<div class="leitor-loading" role="status">
    <div class="leitor-loading-spinner"></div>
    <span>Carregando…</span></div>`;
  if (toTop) window.scrollTo({top:0,behavior:'smooth'});

  const url = `${L.livro.pastaConteudo}cap${String(n).padStart(2,'0')}.html`;
  try {
    const r = await fetch(url, {credentials:'include'});
    if (!r.ok) throw new Error(`HTTP ${r.status}`);
    const html = await r.text();
    area.innerHTML = `<article class="leitor-capitulo" id="conteudo-principal">${html}</article>`;
    L.estado.capHtml = n;
    _aplicarTamanhoFonte(L.prefs.tamanhoFonte);
    _aplicarAlturaLinha(L.prefs.alturaLinha);
    _injetarNotasAutorHTML();
    _atualizarNavCaps();
    _atualizarTituloNav();
    if (!toTop && L.estado.scrollSalvo > 0) {
      window.scrollTo({top:L.estado.scrollSalvo, behavior:'instant'});
      L.estado.scrollSalvo = 0;
    }
    area.addEventListener('mouseup',  _onMouseUp);
    area.addEventListener('touchend', _onMouseUp);
  } catch(e) {
    if (n === 1) {
      area.innerHTML = `<div class="leitor-loading" role="alert">
        <p>Não foi possível carregar o livro.</p>
        <p style="font-size:.8em;opacity:.6">Esperado: ${url}</p></div>`;
    } else {
      _toast('Capítulo não encontrado.','erro');
      await _carregarCapitulo(L.estado.capHtml);
    }
  } finally { L.estado.carregando = false; }
}

/* ════════════════════════════════════════════════════════════════
   PROGRESSO
   ════════════════════════════════════════════════════════════════ */
async function _carregarProgresso() {
  try {
    const r = await fetch(`${L.BASE}/leitor.php?acao=progresso&livro=${L.livro.slug}`, {credentials:'include'});
    const d = await r.json();
    if (!d.ok || !d.progresso) return;
    const p = d.progresso;
    L.estado.capHtml    = +p.capitulo_atual || 1;
    L.estado.percentual = +p.percentual     || 0;
    L.estado.scrollSalvo= +p.posicao_scroll || 0;
    L.estado.cfiSalvo   = p.cfi_posicao     || null;
    _atualizarBarra(L.estado.percentual);
  } catch(e) {}
}

async function _salvarProgressoHtml(scroll, perc) {
  if (L.estado.salvando) return;
  L.estado.salvando = true;
  try {
    await fetch(`${L.BASE}/leitor.php`, {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({
        acao:'salvar_progresso', livro:L.livro.slug,
        capitulo:L.estado.capHtml, scroll:Math.round(scroll),
        percentual:Math.round(perc*100)/100,
        total_paginas:L.livro.totalCapitulos,
      }),
    });
  } catch(e) {} finally { L.estado.salvando = false; }
}

function _iniciarScrollTracker() {
  if (L.livro.formato === 'epub') {
    // Para epub em partes: rastreia scroll dentro da parte atual
    window.addEventListener('scroll', () => {
      const scroll = window.scrollY;
      const total  = document.documentElement.scrollHeight - window.innerHeight;
      if (total <= 0) return;
      const percNaParte = scroll / total;
      const parteTot   = Math.max(1, L._epubTotal || 1);
      const parteAtual = L._epubParte || 0;
      const perc = Math.min(100, ((parteAtual + percNaParte) / parteTot) * 100);
      L.estado.percentual = perc;
      _atualizarBarra(perc);
      _atualizarBadgePerc(perc);
    }, {passive:true});
    return;
  }
  window.addEventListener('scroll', () => {
    const scroll = window.scrollY;
    const total  = document.documentElement.scrollHeight - window.innerHeight;
    if (total <= 0) return;
    const percCap    = scroll / total;
    const percGlobal = ((L.estado.capHtml - 1 + percCap) / L.livro.totalCapitulos) * 100;
    const perc       = Math.min(100, Math.max(0, percGlobal));
    L.estado.percentual = perc;
    _atualizarBarra(perc);
    _atualizarBadgePerc(perc);
    _verificarConquistas(perc);
    clearTimeout(L._timerProgresso);
    L._timerProgresso = setTimeout(() => _salvarProgressoHtml(scroll, perc), 5000);
  }, {passive:true});
}

function _atualizarBarra(perc) {
  const f = document.getElementById('barra-progresso-fill');
  if (f) f.style.width = perc.toFixed(1) + '%';
}
function _atualizarBadgePerc(perc) {
  const b = document.getElementById('leitor-percentual');
  if (b) b.textContent = Math.round(perc) + '%';
}

/* ════════════════════════════════════════════════════════════════
   CONQUISTAS
   ════════════════════════════════════════════════════════════════ */
const MARCOS = [
  {m:25,  emoji:'🥉', titulo:'Um quarto da jornada',  msg:'Você leu 25% do livro! Continue.'},
  {m:50,  emoji:'🥈', titulo:'Na metade do caminho',  msg:'Metade da história já é sua.'},
  {m:75,  emoji:'🥇', titulo:'Quase lá!',              msg:'75% lidos! O final está chegando.'},
  {m:90,  emoji:'⭐', titulo:'A reta final',            msg:'A 10% do fim. Um fôlego a mais!'},
  {m:100, emoji:'🏆', titulo:'Livro concluído!',        msg:'Parabéns! Obrigado por ler.'},
];

async function _carregarConquistas() {
  try {
    const r = await fetch(`${L.BASE}/leitor.php?acao=conquistas&livro=${L.livro.slug}`, {credentials:'include'});
    const d = await r.json();
    if (d.ok) d.conquistas.forEach(c => L.estado.conquistasVistas.add(+c.marco));
  } catch(e) {}
}

function _verificarConquistas(perc) {
  const p = Math.round(perc);
  MARCOS.forEach(m => {
    if (p >= m.m && !L.estado.conquistasVistas.has(m.m)) {
      L.estado.conquistasVistas.add(m.m);
      _exibirConquista(m);
      _registrarConquista(m.m);
    }
  });
}

function _exibirConquista(m) {
  document.getElementById('conquista-overlay')?.remove();
  const el = document.createElement('div');
  el.id = 'conquista-overlay';
  el.className = 'conquista-overlay';
  el.setAttribute('role','status');
  el.innerHTML = `<div class="conquista-box">
    <span class="conquista-medalha">${m.emoji}</span>
    <div>
      <p class="conquista-titulo">${m.titulo}</p>
      <p class="conquista-msg">${m.msg}</p>
    </div>
    <button class="conquista-fechar" onclick="this.closest('.conquista-overlay').remove()">✕</button>
  </div>`;
  document.body.appendChild(el);
  setTimeout(() => el.classList.add('visivel'), 50);
  setTimeout(() => { el.classList.remove('visivel'); setTimeout(()=>el.remove(),600); }, 7000);
}

async function _registrarConquista(marco) {
  try {
    await fetch(`${L.BASE}/leitor.php`, {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({acao:'registrar_conquista', livro:L.livro.slug, marco}),
    });
  } catch(e) {}
}

/* ════════════════════════════════════════════════════════════════
   NOTAS DO AUTOR
   ════════════════════════════════════════════════════════════════ */
async function _carregarNotasAutor() {
  try {
    const r = await fetch(`${L.livro.pastaConteudo}notas-autor.json`);
    if (r.ok) L.dados.notasAutor = await r.json();
  } catch(e) {}
}

function _injetarNotasAutorEpub(contents) {
  const notas = L.dados.notasAutor;
  if (!notas || !Object.keys(notas).length) return;
  const js = `(function(){
    const notas = ${JSON.stringify(notas)};
    document.querySelectorAll('[data-nota-autor]').forEach(el => {
      const id = el.getAttribute('data-nota-autor');
      if (!notas[id]) return;
      el.style.borderBottom = '2px dashed #B8860B';
      el.style.cursor = 'pointer';
      el.title = 'Nota do autor';
      el.addEventListener('click', e => {
        e.stopPropagation();
        document.querySelectorAll('.nota-balao').forEach(b=>b.remove());
        const b = document.createElement('div');
        b.className = 'nota-balao';
        b.style.cssText='position:fixed;bottom:1rem;left:50%;transform:translateX(-50%);z-index:9999;background:#1A0F00;color:#E8DCC8;border:1px solid #B8860B;border-radius:8px;padding:1rem;font-size:14px;line-height:1.6;max-width:320px;box-shadow:0 4px 20px rgba(0,0,0,.5)';
        b.innerHTML='<strong style="color:#B8860B;display:block;margin-bottom:.4rem;font-size:11px;letter-spacing:.1em;text-transform:uppercase">✍ Nota do autor</strong>'+notas[id];
        document.body.appendChild(b);
        setTimeout(()=>document.addEventListener('click',()=>b.remove(),{once:true}),50);
      });
    });
  })();`;
  try { contents.window.eval(js); } catch(e) {}
}

function _injetarNotasAutorHTML() {
  const notas = L.dados.notasAutor;
  if (!notas || !Object.keys(notas).length) return;
  document.querySelectorAll('[data-nota-autor]').forEach(el => {
    const nota = notas[el.getAttribute('data-nota-autor')];
    if (!nota) return;
    el.style.borderBottom = '2px dashed var(--ouro, #B8860B)';
    el.style.cursor = 'pointer';
    el.title = '✍ Nota do autor';
    el.addEventListener('click', e => {
      e.stopPropagation();
      document.querySelectorAll('.nota-balao').forEach(b=>b.remove());
      const b = document.createElement('div');
      b.className = 'nota-balao nota-autor-balao';
      b.innerHTML = `<span class="nota-autor-label">✍ Nota do autor</span>${_esc(nota)}`;
      el.style.position = 'relative';
      el.appendChild(b);
      setTimeout(()=>document.addEventListener('click',()=>b.remove(),{once:true}),50);
    });
  });
}

/* ════════════════════════════════════════════════════════════════
   BIBLIOTECA — troca de livro
   ════════════════════════════════════════════════════════════════ */
async function _carregarBiblioteca() {
  try {
    const r = await fetch(`${L.BASE}/leitor.php?acao=painel`, {credentials:'include'});
    const d = await r.json();
    if (d.ok) { L.dados.biblioteca = d.leituras || []; _renderizarBiblioteca(); }
  } catch(e) {}
}

function _injetarDropdownBiblioteca() {
  const nav = document.querySelector('.leitor-nav-esq');
  if (!nav) return;

  const btn = document.createElement('button');
  btn.id = 'btn-biblioteca';
  btn.className = 'leitor-nav-btn';
  btn.title = 'Trocar de livro';
  btn.setAttribute('aria-label','Trocar de livro');
  btn.innerHTML = '<i class="fa-solid fa-book-open-reader" aria-hidden="true"></i>';
  btn.addEventListener('click', e => {
    e.stopPropagation();
    document.getElementById('dropdown-bib')?.classList.toggle('aberto');
  });
  nav.appendChild(btn);

  const drop = document.createElement('div');
  drop.id = 'dropdown-bib';
  drop.className = 'dropdown-biblioteca';
  drop.innerHTML = `<p class="dropdown-bib-titulo">
    <i class="fa-solid fa-layer-group"></i> Minha Biblioteca</p>
    <div id="dropdown-bib-lista"><p style="font-size:.8rem;opacity:.5;padding:.5rem">Carregando…</p></div>`;
  document.body.appendChild(drop);
  document.addEventListener('click', e => {
    if (!drop.contains(e.target) && e.target !== btn) drop.classList.remove('aberto');
  });
}

function _renderizarBiblioteca() {
  const lista = document.getElementById('dropdown-bib-lista');
  if (!lista) return;
  const livros = L.dados.biblioteca;
  if (!livros.length) {
    lista.innerHTML = '<p style="font-size:.8rem;opacity:.5;padding:.5rem">Nenhum livro disponível.</p>';
    return;
  }
  lista.innerHTML = livros.map(l => `
    <button class="dropdown-bib-item ${l.slug===L.livro.slug?'ativo':''}"
            onclick="trocarLivro('${_esc(l.slug)}')"
            title="${_esc(l.titulo)}">
      <span class="dropdown-bib-titulo-livro">${_esc(l.titulo)}</span>
      <span class="dropdown-bib-perc">${Math.round(l.percentual||0)}%</span>
    </button>`).join('');
}

function trocarLivro(slug) {
  document.getElementById('dropdown-bib')?.classList.remove('aberto');
  if (slug === L.livro.slug) return;
  window.location.href = 'livro.html?livro=' + encodeURIComponent(slug);
}

/* ════════════════════════════════════════════════════════════════
   PREFERÊNCIAS TIPOGRÁFICAS
   ════════════════════════════════════════════════════════════════ */
async function _carregarPrefs() {
  try {
    const r = await fetch(`${L.BASE}/leitor.php?acao=preferencias`, {credentials:'include'});
    const d = await r.json();
    if (d.ok && d.preferencias) Object.assign(L.prefs, d.preferencias);
  } catch(e) {}
}

function _aplicarPrefs() {
  document.body.dataset.fonte   = L.prefs.fonte;
  document.body.dataset.fundo   = L.prefs.fundoLeitura;
  document.body.dataset.largura = L.prefs.larguraColuna;
  _aplicarTamanhoFonte(L.prefs.tamanhoFonte);
  _aplicarAlturaLinha(L.prefs.alturaLinha);
  _sincronizarControles();
  const ro = document.getElementById('ranking-opt-in');
  if (ro) ro.checked = !!L.prefs.rankingOptIn;
}

function _aplicarTamanhoFonte(px) {
  document.body.style.setProperty('--leitor-tamanho', px+'px');
  let s = document.getElementById('leitor-dynamic-style');
  if (!s) { s=document.createElement('style'); s.id='leitor-dynamic-style'; document.head.appendChild(s); }
  const lh = L.prefs.alturaLinha;
  s.textContent = `#leitor-texto-area, #leitor-texto-area p, .leitor-capitulo, .leitor-capitulo p { font-size:${px}px !important; line-height:${lh} !important; }`;
  if (L._rendition) _reaplicarCssEpub();
}

function _aplicarAlturaLinha(val) {
  document.body.style.setProperty('--leitor-linha', val);
  let s = document.getElementById('leitor-dynamic-style');
  if (s) { const px=L.prefs.tamanhoFonte; s.textContent=`#leitor-texto-area, #leitor-texto-area p, .leitor-capitulo, .leitor-capitulo p { font-size:${px}px !important; line-height:${val} !important; }`; }
  if (L._rendition) _reaplicarCssEpub();
}

function _sincronizarControles() {
  document.querySelectorAll('[data-config-fonte]').forEach(b => b.classList.toggle('ativo', b.dataset.configFonte===L.prefs.fonte));
  document.querySelectorAll('.fundo-amostra').forEach(b => b.classList.toggle('ativo', b.dataset.fundo===L.prefs.fundoLeitura));
  document.querySelectorAll('[data-config-largura]').forEach(b => b.classList.toggle('ativo', b.dataset.configLargura===L.prefs.larguraColuna));
  const st = document.getElementById('slider-tamanho'); if (st) st.value = L.prefs.tamanhoFonte;
  const vt = document.getElementById('valor-tamanho'); if (vt) vt.textContent = L.prefs.tamanhoFonte+'px';
  const sl = document.getElementById('slider-linha');  if (sl) sl.value = L.prefs.alturaLinha;
  const vl = document.getElementById('valor-linha');   if (vl) vl.textContent = L.prefs.alturaLinha.toFixed(1);
}

async function _salvarPrefs() {
  clearTimeout(L._timerPrefs);
  L._timerPrefs = setTimeout(async () => {
    try {
      await fetch(`${L.BASE}/leitor.php`, {
        method:'POST', credentials:'include',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({
          acao:'salvar_preferencias',
          fonte:           L.prefs.fonte,
          tamanho_fonte:   L.prefs.tamanhoFonte,
          fundo_leitura:   L.prefs.fundoLeitura,
          largura_coluna:  L.prefs.larguraColuna,
          altura_linha:    L.prefs.alturaLinha,
          ranking_opt_in:  L.prefs.rankingOptIn,
        }),
      });
    } catch(e) {}
  }, 1200);
}

/* ════════════════════════════════════════════════════════════════
   MODO NÃO PERTURBE
   ════════════════════════════════════════════════════════════════ */
function alternarNaoPerturbe() {
  L.estado.naoPerturbe = !L.estado.naoPerturbe;
  document.body.classList.toggle('modo-nao-perturbe', L.estado.naoPerturbe);
  const btn = document.getElementById('btn-nao-perturbe');
  if (btn) {
    btn.title = L.estado.naoPerturbe ? 'Sair do modo imersivo' : 'Modo imersivo (F)';
    btn.querySelector('i')?.classList.toggle('fa-expand',   !L.estado.naoPerturbe);
    btn.querySelector('i')?.classList.toggle('fa-compress',  L.estado.naoPerturbe);
  }
  if (L.estado.naoPerturbe) {
    document.documentElement.requestFullscreen?.().catch(()=>{});
    _toast('Modo imersivo ativado — ESC para sair');
  } else {
    document.exitFullscreen?.().catch(()=>{});
  }
}
document.addEventListener('fullscreenchange', () => {
  if (!document.fullscreenElement && L.estado.naoPerturbe) {
    L.estado.naoPerturbe = false;
    document.body.classList.remove('modo-nao-perturbe');
  }
});

/* ════════════════════════════════════════════════════════════════
   MODO FOCO
   ════════════════════════════════════════════════════════════════ */
function alternarFoco() {
  L.estado.focoAtivo = !L.estado.focoAtivo;
  document.body.classList.toggle('modo-foco', L.estado.focoAtivo);
  document.getElementById('btn-foco')?.classList.toggle('ativo', L.estado.focoAtivo);
  if (L.estado.focoAtivo) {
    document.addEventListener('mousemove', _focoMove, {passive:true});
    _toast('Modo foco ativado');
  } else {
    document.removeEventListener('mousemove', _focoMove);
    document.body.style.removeProperty('--foco-y');
  }
}
function _focoMove(e) {
  if (L.estado.focoAtivo) document.body.style.setProperty('--foco-y', e.clientY+'px');
}

/* ════════════════════════════════════════════════════════════════
   RELÓGIO DE METAS
   ════════════════════════════════════════════════════════════════ */
function _iniciarRelogio() {
  _tickRelogio();
  setInterval(_tickRelogio, 1000);
}
function _tickRelogio() {
  const st = L.estado.timer;
  if (st.ativo && st.modo === 'cronometro') st.seg++;
  if (st.ativo && st.modo === 'regressivo') {
    if (st.seg > 0) st.seg--;
    else { st.ativo=false; _toast('⏰ Tempo de leitura atingido!'); }
  }
  const el = document.getElementById('relogio-display');
  if (!el) return;
  if (st.modo === 'relogio') {
    el.textContent = new Date().toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'});
    el.style.color = '';
  } else {
    const s=st.seg, h=Math.floor(s/3600), m=Math.floor((s%3600)/60), sec=s%60;
    el.textContent = h>0 ? `${h}:${String(m).padStart(2,'0')}:${String(sec).padStart(2,'0')}` : `${String(m).padStart(2,'0')}:${String(sec).padStart(2,'0')}`;
    el.style.color = (st.modo==='regressivo' && s<=60 && s>0) ? '#c0392b' : '';
  }
}
function alternarTimer() {
  if (L.estado.timer.modo==='relogio') return;
  L.estado.timer.ativo = !L.estado.timer.ativo;
  const btn = document.getElementById('btn-timer');
  if (btn) btn.innerHTML = L.estado.timer.ativo ? '<i class="fa-solid fa-pause"></i>' : '<i class="fa-solid fa-play"></i>';
}
function resetarTimer() {
  L.estado.timer.ativo = false;
  L.estado.timer.seg = L.estado.timer.modo==='regressivo' ? L.estado.timer.meta*60 : 0;
  const btn = document.getElementById('btn-timer');
  if (btn) btn.innerHTML = '<i class="fa-solid fa-play"></i>';
}
function mudarModoTimer(modo) {
  L.estado.timer.modo = modo;
  L.estado.timer.ativo = false;
  L.estado.timer.seg = modo==='regressivo' ? L.estado.timer.meta*60 : 0;
  document.querySelectorAll('[data-timer-modo]').forEach(b => b.classList.toggle('ativo', b.dataset.timerModo===modo));
  const mr = document.getElementById('timer-meta-row');
  if (mr) mr.style.display = modo==='regressivo' ? 'flex' : 'none';
  const bt = document.getElementById('btn-timer');
  if (bt) bt.style.display = modo==='relogio' ? 'none' : '';
}
function ocultarRelogio() { document.getElementById('relogio-box')?.style.setProperty('display','none'); }

/* ════════════════════════════════════════════════════════════════
   RUÍDO BRANCO
   ════════════════════════════════════════════════════════════════ */
const SOM = { ctx:null, src:null, gain:null,
  urls:{ ondas:'../audio/ondas.mp3', chuva:'../audio/chuva.mp3', vento:'../audio/vento.mp3', floresta:'../audio/floresta.mp3', fogo:'../audio/fogo.mp3' }
};
async function alternarSom(tipo) {
  SOM.src?.stop?.(); SOM.src = null;
  const st = L.estado.som;
  if (st.ativo && st.tipo===tipo) { st.ativo=false; _atualizarBtnsSom(); return; }
  st.tipo=tipo; st.ativo=true;
  try {
    SOM.ctx = SOM.ctx || new (window.AudioContext||window.webkitAudioContext)();
    if (SOM.ctx.state==='suspended') await SOM.ctx.resume();
    const buf = await (await fetch(SOM.urls[tipo])).arrayBuffer();
    const dec = await SOM.ctx.decodeAudioData(buf);
    SOM.gain = SOM.ctx.createGain(); SOM.gain.gain.value = st.vol; SOM.gain.connect(SOM.ctx.destination);
    SOM.src = SOM.ctx.createBufferSource(); SOM.src.buffer=dec; SOM.src.loop=true; SOM.src.connect(SOM.gain); SOM.src.start(0);
    _toast('🎵 ' + {ondas:'Ondas',chuva:'Chuva',vento:'Vento',floresta:'Floresta',fogo:'Fogo'}[tipo] + ' ativado');
  } catch(e) { st.ativo=false; _toast('Arquivo .mp3 não encontrado em /audio/','erro'); }
  _atualizarBtnsSom();
}
function ajustarVolumeSom(v) { L.estado.som.vol=+v; if(SOM.gain) SOM.gain.gain.value=+v; }
function _atualizarBtnsSom() {
  const st=L.estado.som;
  document.querySelectorAll('[data-som]').forEach(b => b.classList.toggle('ativo', b.dataset.som===st.tipo && st.ativo));
}

/* ════════════════════════════════════════════════════════════════
   INDICAR A UM AMIGO
   ════════════════════════════════════════════════════════════════ */
function indicarAmigo(canal) {
  const msg = `Estou lendo "${L.livro.titulo}" de Robério Diógenes e recomendo! https://www.roberiodiogenes.com/livros/${L.livro.slug}.html`;
  if (canal==='whatsapp') window.open('https://wa.me/?text='+encodeURIComponent(msg),'_blank','noopener');
  else window.location.href=`mailto:?subject=${encodeURIComponent('Recomendação: '+L.livro.titulo)}&body=${encodeURIComponent(msg)}`;
}

/* ════════════════════════════════════════════════════════════════
   REPORTAR ERRO
   ════════════════════════════════════════════════════════════════ */
let _trecho = '';
function abrirReporteErro(t) {
  _trecho = t || _selecao || '';
  const m = document.getElementById('modal-reporte');
  const p = document.getElementById('reporte-trecho-preview');
  if (p) p.textContent = _trecho ? `"${_trecho.slice(0,120)}"` : '(sem trecho selecionado)';
  m?.classList.add('aberto');
}
async function enviarReporte() {
  const tipo = document.getElementById('reporte-tipo')?.value || 'ortografia';
  const obs  = document.getElementById('reporte-obs')?.value?.trim() || '';
  if (!_trecho && !obs) { _toast('Selecione um trecho ou descreva o erro.','erro'); return; }
  try {
    const r = await fetch(`${L.BASE}/leitor.php`, {
      method:'POST', credentials:'include', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({acao:'reportar_erro', livro:L.livro.slug, capitulo:L.estado.capHtml, trecho:_trecho, tipo, obs}),
    });
    const d = await r.json();
    document.getElementById('modal-reporte')?.classList.remove('aberto');
    _toast(d.ok ? '✓ Reporte enviado. Obrigado!' : 'Erro ao enviar.', d.ok?'ok':'erro');
  } catch(e) { _toast('Erro de conexão.','erro'); }
}

/* ════════════════════════════════════════════════════════════════
   FEEDBACK DE CONCLUSÃO
   ════════════════════════════════════════════════════════════════ */
async function enviarFeedback() {
  const txt = document.getElementById('feedback-texto')?.value?.trim()||'';
  const nota = L.dados.avaliacaoAtual;
  try {
    const r = await fetch(`${L.BASE}/leitor.php`, {
      method:'POST', credentials:'include', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({acao:'feedback_conclusao', livro:L.livro.slug, texto:txt, nota}),
    });
    const d = await r.json();
    if (d.ok) {
      const f = document.getElementById('feedback-form');
      if (f) f.innerHTML = '<p style="color:var(--ouro);text-align:center;padding:1rem">Obrigado pelo seu feedback! ❤️</p>';
      _toast('Feedback enviado!');
    }
  } catch(e) { _toast('Erro ao enviar.','erro'); }
}

/* ════════════════════════════════════════════════════════════════
   RANKING
   ════════════════════════════════════════════════════════════════ */
async function _carregarRanking() {
  const lista = document.getElementById('ranking-lista');
  if (!lista) return;
  lista.innerHTML = '<p style="font-size:.8rem;opacity:.5">Carregando…</p>';
  try {
    const r = await fetch(`${L.BASE}/leitor.php?acao=ranking&livro=${L.livro.slug}`, {credentials:'include'});
    const d = await r.json();
    if (!d.ok || !d.ranking?.length) { lista.innerHTML='<p style="font-size:.8rem;opacity:.5;text-align:center">Nenhum leitor no ranking ainda.</p>'; return; }
    lista.innerHTML = d.ranking.map((r,i) => `<div class="ranking-item">
      <span class="ranking-pos">${i===0?'🥇':i===1?'🥈':i===2?'🥉':i+1}</span>
      <span class="ranking-nome">${_esc(r.nome_exibicao)}</span>
      <span class="ranking-perc">${Math.round(r.percentual)}%</span>
      ${r.conquistas?`<span class="ranking-conquistas">${r.conquistas}</span>`:''}
    </div>`).join('');
  } catch(e) { lista.innerHTML='<p style="font-size:.8rem;opacity:.5">Erro.</p>'; }
}

/* ════════════════════════════════════════════════════════════════
   ANOTAÇÕES
   ════════════════════════════════════════════════════════════════ */
async function _carregarAnotacoes() {
  try {
    const r = await fetch(`${L.BASE}/leitor.php?acao=anotacoes&livro=${L.livro.slug}`, {credentials:'include'});
    const d = await r.json();
    if (d.ok) { L.dados.anotacoes = d.anotacoes; _renderizarAnotacoes(); }
  } catch(e) {}
}
function _renderizarAnotacoes() {
  const lista = document.getElementById('lista-anotacoes');
  if (!lista) return;
  if (!L.dados.anotacoes.length) { lista.innerHTML='<p style="font-size:.85rem;opacity:.6;text-align:center;padding:1rem">Nenhuma anotação ainda.</p>'; return; }
  lista.innerHTML = L.dados.anotacoes.map(a=>`<div class="anotacao-card" style="border-left-color:${a.cor}">
    <div class="anotacao-cap">Cap. ${a.capitulo}</div>
    <div class="anotacao-texto">${_esc(a.texto)}</div>
    <button class="anotacao-btn deletar" onclick="deletarAnotacao(${a.id})"><i class="fa-solid fa-trash-can"></i> Deletar</button>
  </div>`).join('');
}
async function criarAnotacao(texto, cor) {
  if (!texto.trim()) return;
  try {
    const r = await fetch(`${L.BASE}/leitor.php`, {
      method:'POST', credentials:'include', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({acao:'criar_anotacao', livro:L.livro.slug, capitulo:L.estado.capHtml, texto, cor}),
    });
    const d = await r.json();
    if (d.ok) { _toast('Anotação salva! ✍️'); await _carregarAnotacoes(); document.getElementById('anot-textarea').value=''; }
    else _toast(d.erro||'Erro.','erro');
  } catch(e) { _toast('Erro de conexão.','erro'); }
}
async function deletarAnotacao(id) {
  if (!confirm('Remover esta anotação?')) return;
  try {
    await fetch(`${L.BASE}/leitor.php`, {method:'POST',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify({acao:'deletar_anotacao',id})});
    L.dados.anotacoes = L.dados.anotacoes.filter(a=>a.id!==id);
    _renderizarAnotacoes(); _toast('Anotação removida.');
  } catch(e) {}
}

/* ════════════════════════════════════════════════════════════════
   MARCAÇÕES
   ════════════════════════════════════════════════════════════════ */
async function _carregarMarcacoes() {
  try {
    const r = await fetch(`${L.BASE}/leitor.php?acao=marcacoes&livro=${L.livro.slug}`, {credentials:'include'});
    const d = await r.json();
    if (d.ok) { L.dados.marcacoes = d.marcacoes; _renderizarMarcacoes(); }
  } catch(e) {}
}
function _renderizarMarcacoes() {
  const lista = document.getElementById('lista-marcacoes');
  if (!lista) return;
  if (!L.dados.marcacoes.length) { lista.innerHTML='<p style="font-size:.85rem;opacity:.6;text-align:center;padding:1rem">Nenhum trecho marcado.</p>'; return; }
  lista.innerHTML = L.dados.marcacoes.map(m=>`<div class="marcacao-card">
    <button class="marcacao-btn-del" onclick="deletarMarcacao(${m.id})"><i class="fa-solid fa-xmark"></i></button>
    <div class="marcacao-trecho ${m.cor}">"${_esc(m.trecho.slice(0,200))}${m.trecho.length>200?'…':''}"</div>
    ${m.nota?`<div class="marcacao-nota">💬 ${_esc(m.nota)}</div>`:''}
    <div style="font-size:.7rem;opacity:.5;margin-top:.35rem">Cap. ${m.capitulo}</div>
  </div>`).join('');
}
async function criarMarcacao(trecho, cor) {
  try {
    const r = await fetch(`${L.BASE}/leitor.php`, {method:'POST',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify({acao:'criar_marcacao',livro:L.livro.slug,capitulo:L.estado.capHtml,trecho,cor})});
    const d = await r.json();
    if (d.ok) { _toast('Trecho marcado!'); await _carregarMarcacoes(); }
  } catch(e) {}
}
async function deletarMarcacao(id) {
  try {
    await fetch(`${L.BASE}/leitor.php`, {method:'POST',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify({acao:'deletar_marcacao',id})});
    L.dados.marcacoes = L.dados.marcacoes.filter(m=>m.id!==id);
    _renderizarMarcacoes(); _toast('Marcação removida.');
  } catch(e) {}
}

/* ════════════════════════════════════════════════════════════════
   MENU DE SELEÇÃO DE TEXTO
   ════════════════════════════════════════════════════════════════ */
let _selecao = '';
function _onMouseUp() {
  const sel = window.getSelection();
  _selecao = sel?.toString().trim() || '';
  const menu = document.getElementById('menu-selecao');
  if (!menu) return;
  if (!_selecao || _selecao.length < 3) { menu.classList.remove('visivel'); return; }
  const rect = sel.getRangeAt(0).getBoundingClientRect();
  menu.style.top  = (rect.top + window.scrollY - 50) + 'px';
  menu.style.left = Math.max(4, rect.left + rect.width/2 - 140) + 'px';
  menu.classList.add('visivel');
}
document.addEventListener('selectionchange', () => {
  if (!window.getSelection()?.toString().trim()) document.getElementById('menu-selecao')?.classList.remove('visivel');
});
function marcarSelecao(cor) {
  if (!_selecao) return;
  criarMarcacao(_selecao, cor);
  window.getSelection()?.removeAllRanges();
  document.getElementById('menu-selecao')?.classList.remove('visivel');
}
function anotarSelecao() {
  if (!_selecao) return;
  _abrirPainel('anotacoes');
  const ta = document.getElementById('anot-textarea');
  if (ta) { ta.value=`"${_selecao}"\n\n`; ta.focus(); }
  window.getSelection()?.removeAllRanges();
  document.getElementById('menu-selecao')?.classList.remove('visivel');
}

/* ════════════════════════════════════════════════════════════════
   AVALIAÇÃO
   ════════════════════════════════════════════════════════════════ */
async function _carregarAvaliacao() {
  try {
    const r = await fetch(`${L.BASE}/livros.php?acao=estado&livro=${L.livro.slug}`, {credentials:'include'});
    const d = await r.json();
    if (d.ok && d.estrelas) { L.dados.avaliacaoAtual=d.estrelas; _renderizarEstrelas(d.estrelas); }
  } catch(e) {}
}
function _renderizarEstrelas(n) {
  document.querySelectorAll('.estrela-leitor').forEach((el,i) => el.classList.toggle('iluminada',i<n));
}
async function avaliar(n) {
  try {
    const r = await fetch(`${L.BASE}/livros.php`, {method:'POST',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify({acao:'avaliar',livro:L.livro.slug,estrelas:n})});
    const d = await r.json();
    if (d.ok) { L.dados.avaliacaoAtual=n; _renderizarEstrelas(n); _toast(`Avaliação de ${n} ⭐ registrada!`); }
  } catch(e) {}
}

/* ════════════════════════════════════════════════════════════════
   NAVEGAÇÃO (capítulos HTML)
   ════════════════════════════════════════════════════════════════ */
function _atualizarNavCaps() {
  const cap=L.estado.capHtml, total=L.livro.totalCapitulos;
  const bA=document.getElementById('btn-cap-anterior');
  const bP=document.getElementById('btn-cap-proximo');
  const info=document.getElementById('cap-info-atual');
  if (bA) bA.disabled = cap<=1;
  if (bP) bP.disabled = cap>=total;
  if (info) info.textContent = `Capítulo ${cap} de ${total}`;
  if (cap>=total) {
    const c=document.getElementById('leitor-conclusao');
    if (c) c.style.display='block';
  }
}
function _atualizarTituloNav() {
  const t=document.getElementById('leitor-titulo-nav');
  if (t) t.textContent = L.livro.titulo;
}
async function irCapitulo(n) {
  if (L.livro.formato==='epub') {
    const total = L._epubTotal || 1;
    const parte = Math.max(0, Math.min(n - 1, total - 1));
    await _carregarParteEpub(parte, true);
  } else {
    if (n<1||n>L.livro.totalCapitulos) return;
    await _carregarCapitulo(n, true);
  }
}

/* ════════════════════════════════════════════════════════════════
   PAINEL LATERAL
   ════════════════════════════════════════════════════════════════ */
function _alternarPainel() {
  const p=document.getElementById('leitor-painel');
  L.estado.painelAberto = !L.estado.painelAberto;
  p?.classList.toggle('aberto', L.estado.painelAberto);
  document.getElementById('btn-painel')?.classList.toggle('ativo', L.estado.painelAberto);
}
function _abrirPainel(aba) {
  document.getElementById('leitor-painel')?.classList.add('aberto');
  L.estado.painelAberto=true;
  document.getElementById('btn-painel')?.classList.add('ativo');
  _mudarAba(aba);
}
function _mudarAba(aba) {
  L.estado.abaAtiva=aba;
  document.querySelectorAll('.painel-aba-btn').forEach(b => b.classList.toggle('ativa', b.dataset.aba===aba));
  document.querySelectorAll('.painel-secao').forEach(s => s.classList.toggle('ativa', s.dataset.secao===aba));
  if (aba==='ranking') _carregarRanking();
}

/* ════════════════════════════════════════════════════════════════
   LISTENERS
   ════════════════════════════════════════════════════════════════ */
function _iniciarListeners() {
  // Navegação de caps
  document.getElementById('btn-cap-anterior')?.addEventListener('click', () => irCapitulo(L.estado.capHtml-1));
  document.getElementById('btn-cap-proximo')?.addEventListener('click',  () => irCapitulo(L.estado.capHtml+1));

  // Painel e modos
  document.getElementById('btn-painel')?.addEventListener('click', _alternarPainel);
  document.getElementById('btn-nao-perturbe')?.addEventListener('click', alternarNaoPerturbe);
  document.getElementById('btn-foco')?.addEventListener('click', alternarFoco);

  // Timer
  document.getElementById('btn-timer')?.addEventListener('click', alternarTimer);
  document.getElementById('btn-timer-reset')?.addEventListener('click', resetarTimer);
  document.getElementById('btn-relogio-ocultar')?.addEventListener('click', ocultarRelogio);
  document.querySelectorAll('[data-timer-modo]').forEach(b => b.addEventListener('click', () => mudarModoTimer(b.dataset.timerModo)));
  document.getElementById('timer-meta-input')?.addEventListener('change', e => {
    L.estado.timer.meta = Math.max(1, +e.target.value||25);
    if (!L.estado.timer.ativo) L.estado.timer.seg = L.estado.timer.meta*60;
  });

  // Sons
  document.querySelectorAll('[data-som]').forEach(b => b.addEventListener('click', () => alternarSom(b.dataset.som)));
  document.getElementById('slider-volume')?.addEventListener('input', e => ajustarVolumeSom(e.target.value));

  // Compartilhar
  document.getElementById('btn-indicar-wpp')?.addEventListener('click', () => indicarAmigo('whatsapp'));
  document.getElementById('btn-indicar-email')?.addEventListener('click', () => indicarAmigo('email'));

  // Reporte
  document.getElementById('btn-reporte-enviar')?.addEventListener('click', enviarReporte);
  document.getElementById('btn-reporte-cancelar')?.addEventListener('click', () => document.getElementById('modal-reporte')?.classList.remove('aberto'));

  // Feedback
  document.getElementById('btn-feedback-enviar')?.addEventListener('click', enviarFeedback);

  // Abas
  document.querySelectorAll('.painel-aba-btn').forEach(b => b.addEventListener('click', () => _mudarAba(b.dataset.aba)));

  // Config fonte
  document.querySelectorAll('[data-config-fonte]').forEach(b => b.addEventListener('click', () => {
    L.prefs.fonte = b.dataset.configFonte;
    document.body.dataset.fonte = L.prefs.fonte;
    document.querySelectorAll('[data-config-fonte]').forEach(x => x.classList.toggle('ativo',x===b));
    if (L._rendition) _reaplicarCssEpub();
    _salvarPrefs();
  }));

  // Config fundo
  document.querySelectorAll('.fundo-amostra').forEach(el => el.addEventListener('click', () => {
    L.prefs.fundoLeitura = el.dataset.fundo;
    document.body.dataset.fundo = L.prefs.fundoLeitura;
    document.querySelectorAll('.fundo-amostra').forEach(e => e.classList.toggle('ativo',e===el));
    _salvarPrefs();
  }));

  // Config largura
  document.querySelectorAll('[data-config-largura]').forEach(b => b.addEventListener('click', () => {
    L.prefs.larguraColuna = b.dataset.configLargura;
    document.body.dataset.largura = L.prefs.larguraColuna;
    document.querySelectorAll('[data-config-largura]').forEach(x => x.classList.toggle('ativo',x===b));
    _salvarPrefs();
  }));

  // Slider tamanho
  const st = document.getElementById('slider-tamanho'), vt = document.getElementById('valor-tamanho');
  st?.addEventListener('input', () => {
    L.prefs.tamanhoFonte = +st.value;
    _aplicarTamanhoFonte(L.prefs.tamanhoFonte);
    if (vt) vt.textContent = L.prefs.tamanhoFonte+'px';
    _salvarPrefs();
  });

  // Slider linha
  const sl = document.getElementById('slider-linha'), vl = document.getElementById('valor-linha');
  sl?.addEventListener('input', () => {
    L.prefs.alturaLinha = +sl.value;
    _aplicarAlturaLinha(L.prefs.alturaLinha);
    if (vl) vl.textContent = L.prefs.alturaLinha.toFixed(1);
    _salvarPrefs();
  });

  // Cores de anotação
  document.querySelectorAll('.anot-cor-btn').forEach(b => b.addEventListener('click', () => {
    L.estado.anotacaoCor = b.dataset.cor;
    document.querySelectorAll('.anot-cor-btn').forEach(x => x.classList.toggle('ativo',x===b));
  }));
  document.getElementById('btn-salvar-anot')?.addEventListener('click', () => {
    const ta = document.getElementById('anot-textarea');
    if (ta) criarAnotacao(ta.value, L.estado.anotacaoCor);
  });

  // Seleção de texto
  document.querySelectorAll('[data-marca-cor]').forEach(b => b.addEventListener('click', () => marcarSelecao(b.dataset.marcaCor)));
  document.getElementById('btn-sel-anotar')?.addEventListener('click', anotarSelecao);
  document.getElementById('btn-sel-reportar')?.addEventListener('click', () => abrirReporteErro(_selecao));

  // Estrelas
  document.querySelectorAll('.estrela-leitor').forEach((el,i) => {
    el.addEventListener('click', () => avaliar(i+1));
    el.addEventListener('mouseenter', () => document.querySelectorAll('.estrela-leitor').forEach((e,j) => e.style.color=j<=i?'var(--ouro)':''));
    el.addEventListener('mouseleave', () => _renderizarEstrelas(L.dados.avaliacaoAtual));
  });

  // Ranking opt-in
  document.getElementById('ranking-opt-in')?.addEventListener('change', e => {
    L.prefs.rankingOptIn = e.target.checked;
    _salvarPrefs();
    _toast(e.target.checked ? '✓ Você entrou no ranking!' : 'Removido do ranking.');
  });

  // Recebe seleção de texto do iframe do epub via postMessage
  window.addEventListener('message', e => {
    if (!e.data || e.data.tipo !== 'epub-selecao') return;
    const txt = (e.data.texto || '').trim();
    _selecao = txt;
    const menu = document.getElementById('menu-selecao');
    if (!menu) return;
    if (!txt || txt.length < 3) { menu.classList.remove('visivel'); return; }
    // Posiciona o menu no centro da tela (iframe não tem coordenadas acessíveis)
    menu.style.top  = (window.scrollY + window.innerHeight * 0.4) + 'px';
    menu.style.left = Math.max(4, window.innerWidth / 2 - 140) + 'px';
    menu.classList.add('visivel');
  });

  // Teclado
  document.addEventListener('keydown', e => {
    if (e.target.tagName==='TEXTAREA'||e.target.tagName==='INPUT') return;
    if (e.key==='ArrowRight'||e.key==='PageDown') irCapitulo(L.estado.capHtml+1);
    if (e.key==='ArrowLeft' ||e.key==='PageUp')   irCapitulo(L.estado.capHtml-1);
    if (e.key==='f'||e.key==='F') alternarNaoPerturbe();
    if (e.key==='Escape') { if(L.estado.naoPerturbe) alternarNaoPerturbe(); else if(L.estado.painelAberto) _alternarPainel(); }
  });

  // Fechar painel ao clicar fora
  document.addEventListener('click', e => {
    const p=document.getElementById('leitor-painel'), b=document.getElementById('btn-painel');
    if (p && L.estado.painelAberto && !p.contains(e.target) && !b?.contains(e.target)) {
      L.estado.painelAberto=false; p.classList.remove('aberto'); b?.classList.remove('ativo');
    }
  });

  // Salva ao fechar
  window.addEventListener('beforeunload', () => {
    const isEpub = L.livro.formato === 'epub';
    navigator.sendBeacon(`${L.BASE}/leitor.php`, JSON.stringify({
      acao:         'salvar_progresso',
      livro:        L.livro.slug,
      capitulo:     isEpub ? (L._epubParte||0)+1 : L.estado.capHtml,
      scroll:       Math.round(window.scrollY),
      percentual:   Math.round(L.estado.percentual*100)/100,
      total_paginas:isEpub ? (L._epubTotal||1) : L.livro.totalCapitulos,
      cfi_posicao:  isEpub ? String(L._epubParte||0) : null,
    }));
  });
}

/* ════════════════════════════════════════════════════════════════
   UTILITÁRIOS
   ════════════════════════════════════════════════════════════════ */
function _toast(msg, tipo='ok') {
  const t = document.getElementById('leitor-toast');
  if (!t) return;
  t.textContent = msg;
  t.style.borderColor = tipo==='erro'?'#c0392b':'var(--ouro)';
  t.classList.add('visivel');
  clearTimeout(t._t);
  t._t = setTimeout(() => t.classList.remove('visivel'), 3500);
}
function _erroFatal(msg) {
  const w = document.getElementById('leitor-wrapper');
  if (w) w.innerHTML = `<div class="leitor-acesso-negado"><span class="acesso-icone">⚠️</span><h2>Erro</h2><p>${msg}</p><a href="../livros.html" class="btn-acesso">← Voltar</a></div>`;
}
function _esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// API pública (chamada do HTML inline e onclick)
Object.assign(window, {
  trocarLivro, indicarAmigo, abrirReporteErro, enviarReporte, enviarFeedback,
  alternarNaoPerturbe, alternarFoco, alternarSom, ajustarVolumeSom,
  mudarModoTimer, alternarTimer, resetarTimer, ocultarRelogio,
  irCapitulo, avaliar, criarAnotacao, deletarAnotacao,
  criarMarcacao, deletarMarcacao, marcarSelecao, anotarSelecao,
});
