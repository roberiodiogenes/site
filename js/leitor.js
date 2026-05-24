/* ================================================================
   ROBÉRIO DIÓGENES — js/leitor.js
   Lógica completa do leitor de livros online
   Integra com: api-client.js, backend/leitor.php, backend/acesso.php
   ================================================================ */

'use strict';

/* ──────────────────────────────────────────────────────────────
   CONFIGURAÇÃO E ESTADO GLOBAL
   ────────────────────────────────────────────────────────────── */
const Leitor = {
  // Dados do livro (preenchidos pela página)
  livro: {
    slug:            '',
    titulo:          '',
    totalCapitulos:  1,
    pastaConteudo:   '',
  },

  // Estado de leitura
  estado: {
    capituloAtual:   1,
    percentual:      0,
    carregando:      false,
    selecionandoTexto: false,
    painelAberto:    false,
    abaAtiva:        'config',  // 'config' | 'anotacoes' | 'marcacoes'
    anotacaoCor:     '#FFD700',
    marcacaoCor:     'amarela',
  },

  // Preferências tipográficas
  prefs: {
    fonte:          'serifada',
    tamanhoFonte:   18,
    fundoLeitura:   'bege',
    larguraColuna:  'media',
    alturaLinha:    1.8,
  },

  // Dados carregados do backend
  dados: {
    anotacoes:  [],
    marcacoes:  [],
    avaliacaoAtual: 0,
  },

  // Timers
  _timerProgresso:  null,
  _timerPrefs:      null,
  _timerSalvando:   false,

  // BASE_URL relativo ao arquivo leitor/livro.html
  BASE_URL: '../backend',
};

/* ──────────────────────────────────────────────────────────────
   INICIALIZAÇÃO
   ────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', async () => {
  // Lê configuração injetada pelo HTML
  const cfg = window.LEITOR_CONFIG || {};
  Leitor.livro.slug           = cfg.slug            || '';
  Leitor.livro.titulo         = cfg.titulo          || '';
  Leitor.livro.totalCapitulos = cfg.totalCapitulos  || 1;
  Leitor.livro.pastaConteudo  = cfg.pastaConteudo   || `../livros-conteudo/${Leitor.livro.slug}/`;

  if (!Leitor.livro.slug) {
    mostrarErroFatal('Configuração do leitor inválida. Livro não identificado.');
    return;
  }

  // 1. Verifica acesso
  const temAcesso = await verificarAcesso();
  if (!temAcesso) return;

  // 2. Carrega preferências salvas
  await carregarPreferencias();

  // 3. Aplica preferências na UI
  aplicarPreferencias();

  // 4. Carrega progresso anterior
  await carregarProgresso();

  // 5. Carrega capítulo (retomando de onde parou)
  await carregarCapitulo(Leitor.estado.capituloAtual);

  // 6. Carrega anotações e marcações em background
  Promise.all([carregarAnotacoes(), carregarMarcacoes(), carregarAvaliacao()]);

  // 7. Inicia listeners
  iniciarListeners();
  iniciarScrollTracker();
});

/* ──────────────────────────────────────────────────────────────
   VERIFICAÇÃO DE ACESSO
   ────────────────────────────────────────────────────────────── */
async function verificarAcesso() {
  try {
    const resp = await fetch(
      `${Leitor.BASE_URL}/acesso.php?livro=${Leitor.livro.slug}`,
      { credentials: 'include' }
    );
    const data = await resp.json();

    if (!data.ok) throw new Error(data.erro || 'Erro ao verificar acesso.');

    if (!data.tem_acesso) {
      mostrarTelaAcessoNegado(data.motivo, data.mensagem);
      return false;
    }

    // Atualiza total de capítulos se o backend souber
    if (data.livro?.total_capitulos) {
      Leitor.livro.totalCapitulos = data.livro.total_capitulos;
    }

    return true;
  } catch (e) {
    console.error('[Leitor] Erro ao verificar acesso:', e);
    mostrarTelaAcessoNegado('erro', 'Não foi possível verificar seu acesso. Tente novamente.');
    return false;
  }
}

function mostrarTelaAcessoNegado(motivo, mensagem) {
  const mapa = {
    nao_logado:           { icone: '🔐', titulo: 'Login necessário',      btn: { href: '../login.html', label: 'Fazer login' } },
    sem_acesso:           { icone: '📖', titulo: 'Livro não adquirido',   btn: { href: `../livros/${Leitor.livro.slug}.html`, label: 'Adquirir livro' } },
    assinatura_expirada:  { icone: '⏳', titulo: 'Assinatura expirada',   btn: { href: '../leitor/index.html#planos', label: 'Renovar assinatura' } },
    erro:                 { icone: '⚠️', titulo: 'Erro de verificação',   btn: { href: 'javascript:location.reload()', label: 'Tentar novamente' } },
  };
  const info = mapa[motivo] || mapa.sem_acesso;

  document.getElementById('leitor-wrapper').innerHTML = `
    <div class="leitor-acesso-negado" role="alert">
      <span class="acesso-icone" aria-hidden="true">${info.icone}</span>
      <h2>${info.titulo}</h2>
      <p>${mensagem || 'Você não tem acesso a este livro no momento.'}</p>
      <a href="${info.btn.href}" class="btn-acesso">
        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        ${info.btn.label}
      </a>
    </div>
  `;
}

/* ──────────────────────────────────────────────────────────────
   CARREGAMENTO DE CAPÍTULO (LAZY LOADING)
   ────────────────────────────────────────────────────────────── */
async function carregarCapitulo(numero, scrollTopo = false) {
  if (Leitor.estado.carregando) return;
  Leitor.estado.carregando = true;

  const area = document.getElementById('leitor-texto-area');
  area.innerHTML = `
    <div class="leitor-loading" role="status" aria-live="polite">
      <div class="leitor-loading-spinner" aria-hidden="true"></div>
      <span>Carregando capítulo ${numero}…</span>
    </div>
  `;

  // Scroll ao topo ao mudar de capítulo
  if (scrollTopo) window.scrollTo({ top: 0, behavior: 'smooth' });

  const url = `${Leitor.livro.pastaConteudo}cap${String(numero).padStart(2,'0')}.html`;

  try {
    const resp = await fetch(url, { credentials: 'include' });
    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
    const html = await resp.text();

    area.innerHTML = `<article class="leitor-capitulo" id="conteudo-principal">${html}</article>`;

    Leitor.estado.capituloAtual = numero;

    // Atualiza UI
    atualizarNavCapitulos();
    atualizarTituloNav();
    reaplicarMarcacoes();

    // Reaplica tamanho/linha diretamente no novo elemento (necessário pois o DOM foi substituído)
    aplicarTamanhoFonte(Leitor.prefs.tamanhoFonte);
    aplicarAlturaLinha(Leitor.prefs.alturaLinha);

    // Restaura posição de scroll (somente no primeiro carregamento)
    if (!scrollTopo && Leitor.estado._scrollInicial > 0) {
      window.scrollTo({ top: Leitor.estado._scrollInicial, behavior: 'instant' });
      Leitor.estado._scrollInicial = 0;
    }

    // Registra menu de seleção de texto
    area.addEventListener('mouseup',  onMouseUp);
    area.addEventListener('touchend', onMouseUp);

  } catch (e) {
    console.error('[Leitor] Erro ao carregar capítulo:', e);
    if (numero === 1) {
      area.innerHTML = `
        <div class="leitor-loading" role="alert">
          <p>Não foi possível carregar o conteúdo do livro.</p>
          <p style="font-size:0.8em;opacity:0.7">Arquivo esperado: ${url}</p>
        </div>
      `;
    } else {
      mostrarToast('Capítulo não encontrado.', 'erro');
      await carregarCapitulo(Leitor.estado.capituloAtual); // volta ao anterior
    }
  } finally {
    Leitor.estado.carregando = false;
  }
}

/* ──────────────────────────────────────────────────────────────
   PROGRESSO DE LEITURA
   ────────────────────────────────────────────────────────────── */
async function carregarProgresso() {
  try {
    const resp = await fetch(
      `${Leitor.BASE_URL}/leitor.php?acao=progresso&livro=${Leitor.livro.slug}`,
      { credentials: 'include' }
    );
    const data = await resp.json();
    if (!data.ok || !data.progresso) return;

    const p = data.progresso;
    Leitor.estado.capituloAtual   = p.capitulo_atual;
    Leitor.estado.percentual      = p.percentual;
    Leitor.estado._scrollInicial  = p.posicao_scroll;

    // Atualiza barra de progresso
    atualizarBarraProgresso(p.percentual);
  } catch (e) {
    console.warn('[Leitor] Progresso não carregado:', e);
  }
}

function iniciarScrollTracker() {
  let ultimoScroll = 0;

  window.addEventListener('scroll', () => {
    const scrollAtual = window.scrollY;
    const alturaDoc   = document.documentElement.scrollHeight - window.innerHeight;
    if (alturaDoc <= 0) return;

    // Percentual global considerando capítulo atual
    const percCap     = scrollAtual / alturaDoc;
    const percGlobal  = (
      (Leitor.estado.capituloAtual - 1 + percCap) / Leitor.livro.totalCapitulos
    ) * 100;

    const perc = Math.min(100, Math.max(0, percGlobal));
    Leitor.estado.percentual = perc;

    atualizarBarraProgresso(perc);
    atualizarBadgePercentual(perc);

    ultimoScroll = scrollAtual;

    // Salva progresso com debounce (a cada 5s de leitura estável)
    clearTimeout(Leitor._timerProgresso);
    Leitor._timerProgresso = setTimeout(() => {
      salvarProgresso(scrollAtual, perc);
    }, 5000);
  }, { passive: true });
}

async function salvarProgresso(scroll, perc) {
  if (Leitor._timerSalvando) return;
  Leitor._timerSalvando = true;

  try {
    await fetch(`${Leitor.BASE_URL}/leitor.php`, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        acao:           'salvar_progresso',
        livro:          Leitor.livro.slug,
        capitulo:       Leitor.estado.capituloAtual,
        scroll:         Math.round(scroll),
        percentual:     Math.round(perc * 100) / 100,
        total_paginas:  Leitor.livro.totalCapitulos,
      }),
    });
  } catch (e) {
    console.warn('[Leitor] Progresso não salvo:', e);
  } finally {
    Leitor._timerSalvando = false;
  }
}

function atualizarBarraProgresso(perc) {
  const fill = document.getElementById('barra-progresso-fill');
  if (fill) fill.style.width = `${perc.toFixed(1)}%`;
}

function atualizarBadgePercentual(perc) {
  const badge = document.getElementById('leitor-percentual');
  if (badge) badge.textContent = `${Math.round(perc)}%`;
}

/* ──────────────────────────────────────────────────────────────
   PREFERÊNCIAS TIPOGRÁFICAS
   ────────────────────────────────────────────────────────────── */
async function carregarPreferencias() {
  try {
    const resp = await fetch(
      `${Leitor.BASE_URL}/leitor.php?acao=preferencias`,
      { credentials: 'include' }
    );
    const data = await resp.json();
    if (data.ok && data.preferencias) {
      Object.assign(Leitor.prefs, data.preferencias);
    }
  } catch (e) {
    console.warn('[Leitor] Preferências não carregadas:', e);
  }
}

function aplicarPreferencias() {
  const body = document.body;
  body.dataset.fonte   = Leitor.prefs.fonte;
  body.dataset.fundo   = Leitor.prefs.fundoLeitura;
  body.dataset.largura = Leitor.prefs.larguraColuna;

  // Aplica tamanho e linha diretamente no elemento de texto
  // (evita conflito com variáveis CSS redefinidas por seletores de tema/fonte)
  aplicarTamanhoFonte(Leitor.prefs.tamanhoFonte);
  aplicarAlturaLinha(Leitor.prefs.alturaLinha);

  // Atualiza controles do painel
  sincronizarControles();
}

/**
 * Aplica font-size com estratégia tripla para vencer qualquer regra CSS:
 * 1. CSS custom property no body (para variáveis)
 * 2. inline style no #leitor-texto-area (container permanente, sempre no DOM)
 * 3. <style> dinâmica que força font-size em todos os p dentro do leitor
 *    (necessário pois variables.css define p { font-size: 1rem } diretamente)
 */
function aplicarTamanhoFonte(px) {
  const tamanho = `${px}px`;

  // 1. Variável CSS no body
  document.body.style.setProperty('--leitor-tamanho', tamanho);

  // 2. Inline style no container permanente
  const area = document.getElementById('leitor-texto-area');
  if (area) area.style.fontSize = tamanho;

  // 3. Style dinâmica para forçar nos <p> (vence p { font-size: 1rem } do variables.css)
  let styleEl = document.getElementById('leitor-dynamic-style');
  if (!styleEl) {
    styleEl = document.createElement('style');
    styleEl.id = 'leitor-dynamic-style';
    document.head.appendChild(styleEl);
  }
  styleEl.textContent = `
    #leitor-texto-area p,
    #leitor-texto-area p.dialogo,
    #leitor-texto-area p.pensamento,
    #leitor-texto-area p.drop-cap,
    .leitor-capitulo p { font-size: ${tamanho} !important; }
    .leitor-capitulo { font-size: ${tamanho} !important; }
  `;
}

/**
 * Aplica line-height no container permanente e via style dinâmica.
 */
function aplicarAlturaLinha(valor) {
  document.body.style.setProperty('--leitor-linha', valor);

  const area = document.getElementById('leitor-texto-area');
  if (area) area.style.lineHeight = valor;

  let styleEl = document.getElementById('leitor-dynamic-style');
  if (styleEl) {
    // Adiciona line-height preservando o font-size já injetado
    const px = Leitor.prefs.tamanhoFonte + 'px';
    styleEl.textContent = `
      #leitor-texto-area p,
      #leitor-texto-area p.dialogo,
      #leitor-texto-area p.pensamento,
      #leitor-texto-area p.drop-cap,
      .leitor-capitulo p { font-size: ${px} !important; line-height: ${valor} !important; }
      .leitor-capitulo { font-size: ${px} !important; line-height: ${valor} !important; }
    `;
  }
}

function sincronizarControles() {
  // Fonte
  document.querySelectorAll('[data-config-fonte]').forEach(btn => {
    btn.classList.toggle('ativo', btn.dataset.configFonte === Leitor.prefs.fonte);
  });
  // Fundo
  document.querySelectorAll('[data-fundo]').forEach(el => {
    el.classList.toggle('ativo', el.dataset.fundo === Leitor.prefs.fundoLeitura);
  });
  // Largura
  document.querySelectorAll('[data-config-largura]').forEach(btn => {
    btn.classList.toggle('ativo', btn.dataset.configLargura === Leitor.prefs.larguraColuna);
  });
  // Tamanho
  const slider = document.getElementById('slider-tamanho');
  const valor  = document.getElementById('valor-tamanho');
  if (slider) slider.value = Leitor.prefs.tamanhoFonte;
  if (valor)  valor.textContent = `${Leitor.prefs.tamanhoFonte}px`;
}

async function salvarPreferencias() {
  clearTimeout(Leitor._timerPrefs);
  Leitor._timerPrefs = setTimeout(async () => {
    try {
      await fetch(`${Leitor.BASE_URL}/leitor.php`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          acao:           'salvar_preferencias',
          fonte:          Leitor.prefs.fonte,
          tamanho_fonte:  Leitor.prefs.tamanhoFonte,
          fundo_leitura:  Leitor.prefs.fundoLeitura,
          largura_coluna: Leitor.prefs.larguraColuna,
          altura_linha:   Leitor.prefs.alturaLinha,
        }),
      });
    } catch (e) {
      console.warn('[Leitor] Preferências não salvas:', e);
    }
  }, 1200); // debounce de 1.2s
}

/* ──────────────────────────────────────────────────────────────
   ANOTAÇÕES
   ────────────────────────────────────────────────────────────── */
async function carregarAnotacoes() {
  try {
    const resp = await fetch(
      `${Leitor.BASE_URL}/leitor.php?acao=anotacoes&livro=${Leitor.livro.slug}`,
      { credentials: 'include' }
    );
    const data = await resp.json();
    if (data.ok) {
      Leitor.dados.anotacoes = data.anotacoes;
      renderizarAnotacoes();
    }
  } catch (e) { console.warn('[Leitor] Anotações não carregadas:', e); }
}

function renderizarAnotacoes() {
  const lista = document.getElementById('lista-anotacoes');
  if (!lista) return;

  if (!Leitor.dados.anotacoes.length) {
    lista.innerHTML = '<p style="font-size:0.85rem;opacity:0.6;text-align:center;padding:1rem">Nenhuma anotação ainda.</p>';
    return;
  }

  lista.innerHTML = Leitor.dados.anotacoes.map(a => `
    <div class="anotacao-card" data-id="${a.id}" style="border-left-color:${a.cor}">
      <div class="anotacao-cap">Cap. ${a.capitulo}</div>
      <div class="anotacao-texto">${escapeHtml(a.texto)}</div>
      <div class="anotacao-acoes">
        <button class="anotacao-btn deletar" onclick="deletarAnotacao(${a.id})" title="Deletar anotação">
          <i class="fa-solid fa-trash-can"></i> Deletar
        </button>
      </div>
    </div>
  `).join('');
}

async function criarAnotacao(texto, cor) {
  if (!texto.trim()) return;
  try {
    const resp = await fetch(`${Leitor.BASE_URL}/leitor.php`, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        acao:      'criar_anotacao',
        livro:     Leitor.livro.slug,
        capitulo:  Leitor.estado.capituloAtual,
        texto,
        cor,
      }),
    });
    const data = await resp.json();
    if (data.ok) {
      mostrarToast('Anotação salva! ✍️');
      await carregarAnotacoes();
      document.getElementById('anot-textarea').value = '';
    } else {
      mostrarToast(data.erro || 'Erro ao salvar anotação.', 'erro');
    }
  } catch (e) {
    mostrarToast('Erro de conexão.', 'erro');
  }
}

async function deletarAnotacao(id) {
  if (!confirm('Remover esta anotação?')) return;
  try {
    const resp = await fetch(`${Leitor.BASE_URL}/leitor.php`, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ acao: 'deletar_anotacao', id }),
    });
    const data = await resp.json();
    if (data.ok) {
      mostrarToast('Anotação removida.');
      Leitor.dados.anotacoes = Leitor.dados.anotacoes.filter(a => a.id !== id);
      renderizarAnotacoes();
    }
  } catch (e) { mostrarToast('Erro de conexão.', 'erro'); }
}

/* ──────────────────────────────────────────────────────────────
   MARCAÇÕES (HIGHLIGHTS)
   ────────────────────────────────────────────────────────────── */
async function carregarMarcacoes() {
  try {
    const resp = await fetch(
      `${Leitor.BASE_URL}/leitor.php?acao=marcacoes&livro=${Leitor.livro.slug}`,
      { credentials: 'include' }
    );
    const data = await resp.json();
    if (data.ok) {
      Leitor.dados.marcacoes = data.marcacoes;
      renderizarMarcacoes();
    }
  } catch (e) { console.warn('[Leitor] Marcações não carregadas:', e); }
}

function renderizarMarcacoes() {
  const lista = document.getElementById('lista-marcacoes');
  if (!lista) return;

  if (!Leitor.dados.marcacoes.length) {
    lista.innerHTML = '<p style="font-size:0.85rem;opacity:0.6;text-align:center;padding:1rem">Nenhum trecho marcado ainda.</p>';
    return;
  }

  lista.innerHTML = Leitor.dados.marcacoes.map(m => `
    <div class="marcacao-card" data-id="${m.id}">
      <button class="marcacao-btn-del" onclick="deletarMarcacao(${m.id})" title="Remover marcação" aria-label="Remover marcação">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <div class="marcacao-trecho ${m.cor}">"${escapeHtml(m.trecho.substring(0,200))}${m.trecho.length > 200 ? '…' : ''}"</div>
      ${m.nota ? `<div class="marcacao-nota">💬 ${escapeHtml(m.nota)}</div>` : ''}
      <div style="font-size:0.7rem;opacity:0.5;margin-top:0.35rem;font-family:var(--fonte-display);letter-spacing:0.08em">Cap. ${m.capitulo}</div>
    </div>
  `).join('');
}

function reaplicarMarcacoes() {
  // Futura implementação: re-aplica os highlights no DOM do capítulo carregado
  // Por ora, os highlights são visuais no painel lateral
}

async function criarMarcacao(trecho, cor, nota = null) {
  try {
    const resp = await fetch(`${Leitor.BASE_URL}/leitor.php`, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        acao:     'criar_marcacao',
        livro:    Leitor.livro.slug,
        capitulo: Leitor.estado.capituloAtual,
        trecho, cor, nota,
      }),
    });
    const data = await resp.json();
    if (data.ok) {
      mostrarToast('Trecho marcado! 🎨');
      await carregarMarcacoes();
    } else {
      mostrarToast(data.erro || 'Erro ao marcar trecho.', 'erro');
    }
  } catch (e) { mostrarToast('Erro de conexão.', 'erro'); }
}

async function deletarMarcacao(id) {
  try {
    const resp = await fetch(`${Leitor.BASE_URL}/leitor.php`, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ acao: 'deletar_marcacao', id }),
    });
    const data = await resp.json();
    if (data.ok) {
      mostrarToast('Marcação removida.');
      Leitor.dados.marcacoes = Leitor.dados.marcacoes.filter(m => m.id !== id);
      renderizarMarcacoes();
    }
  } catch (e) { mostrarToast('Erro de conexão.', 'erro'); }
}

/* ──────────────────────────────────────────────────────────────
   MENU DE SELEÇÃO DE TEXTO
   ────────────────────────────────────────────────────────────── */
let _textoSelecionado = '';

function onMouseUp(e) {
  const sel = window.getSelection();
  _textoSelecionado = sel ? sel.toString().trim() : '';

  const menu = document.getElementById('menu-selecao');
  if (!menu) return;

  if (!_textoSelecionado || _textoSelecionado.length < 3) {
    menu.classList.remove('visivel');
    return;
  }

  // Posiciona o menu perto da seleção
  const range = sel.getRangeAt(0);
  const rect  = range.getBoundingClientRect();
  menu.style.top  = `${rect.top + window.scrollY - 50}px`;
  menu.style.left = `${Math.max(4, rect.left + rect.width / 2 - 120)}px`;
  menu.classList.add('visivel');
}

document.addEventListener('selectionchange', () => {
  const sel = window.getSelection();
  if (!sel || !sel.toString().trim()) {
    const menu = document.getElementById('menu-selecao');
    if (menu) menu.classList.remove('visivel');
  }
});

function marcarSelecao(cor) {
  if (!_textoSelecionado) return;
  criarMarcacao(_textoSelecionado, cor);
  window.getSelection()?.removeAllRanges();
  document.getElementById('menu-selecao')?.classList.remove('visivel');
}

function anotarSelecao() {
  if (!_textoSelecionado) return;
  // Abre painel de anotações com o trecho pré-preenchido
  abrirPainel('anotacoes');
  const ta = document.getElementById('anot-textarea');
  if (ta) {
    ta.value = `"${_textoSelecionado}"\n\n`;
    ta.focus();
    ta.setSelectionRange(ta.value.length, ta.value.length);
  }
  window.getSelection()?.removeAllRanges();
  document.getElementById('menu-selecao')?.classList.remove('visivel');
}

/* ──────────────────────────────────────────────────────────────
   AVALIAÇÃO COM ESTRELAS
   ────────────────────────────────────────────────────────────── */
async function carregarAvaliacao() {
  try {
    const resp = await fetch(
      `${Leitor.BASE_URL}/livros.php?acao=estado&livro=${Leitor.livro.slug}`,
      { credentials: 'include' }
    );
    const data = await resp.json();
    if (data.ok && data.estrelas) {
      Leitor.dados.avaliacaoAtual = data.estrelas;
      renderizarEstrelas(data.estrelas);
    }
  } catch (e) { console.warn('[Leitor] Avaliação não carregada:', e); }
}

function renderizarEstrelas(nota) {
  document.querySelectorAll('.estrela-leitor').forEach((el, i) => {
    el.classList.toggle('iluminada', i < nota);
  });
}

async function avaliar(estrelas) {
  try {
    const resp = await fetch(`${Leitor.BASE_URL}/livros.php`, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        acao:    'avaliar',
        livro:   Leitor.livro.slug,
        estrelas,
      }),
    });
    const data = await resp.json();
    if (data.ok) {
      Leitor.dados.avaliacaoAtual = estrelas;
      renderizarEstrelas(estrelas);
      mostrarToast(`Avaliação de ${estrelas} ⭐ registrada!`);
    }
  } catch (e) { mostrarToast('Erro ao avaliar.', 'erro'); }
}

/* ──────────────────────────────────────────────────────────────
   NAVEGAÇÃO ENTRE CAPÍTULOS
   ────────────────────────────────────────────────────────────── */
function atualizarNavCapitulos() {
  const btnAnterior = document.getElementById('btn-cap-anterior');
  const btnProximo  = document.getElementById('btn-cap-proximo');
  const infoAtual   = document.getElementById('cap-info-atual');
  const cap         = Leitor.estado.capituloAtual;
  const total       = Leitor.livro.totalCapitulos;

  if (btnAnterior) btnAnterior.disabled = cap <= 1;
  if (btnProximo)  btnProximo.disabled  = cap >= total;
  if (infoAtual)   infoAtual.textContent = `Capítulo ${cap} de ${total}`;

  // Mostra tela de conclusão no último capítulo ao chegar ao fim
  if (cap === total) {
    const conclusao = document.getElementById('leitor-conclusao');
    if (conclusao) conclusao.style.display = 'block';
  }
}

function atualizarTituloNav() {
  const titulo = document.getElementById('leitor-titulo-nav');
  if (titulo) titulo.textContent = Leitor.livro.titulo;
}

async function irCapitulo(numero) {
  if (numero < 1 || numero > Leitor.livro.totalCapitulos) return;
  await carregarCapitulo(numero, true);
}

/* ──────────────────────────────────────────────────────────────
   PAINEL LATERAL
   ────────────────────────────────────────────────────────────── */
function alternarPainel() {
  const painel = document.getElementById('leitor-painel');
  const btnPainel = document.getElementById('btn-painel');
  Leitor.estado.painelAberto = !Leitor.estado.painelAberto;
  painel?.classList.toggle('aberto', Leitor.estado.painelAberto);
  btnPainel?.classList.toggle('ativo', Leitor.estado.painelAberto);
}

function abrirPainel(aba) {
  const painel = document.getElementById('leitor-painel');
  Leitor.estado.painelAberto = true;
  painel?.classList.add('aberto');
  document.getElementById('btn-painel')?.classList.add('ativo');
  mudarAba(aba);
}

function mudarAba(aba) {
  Leitor.estado.abaAtiva = aba;
  document.querySelectorAll('.painel-aba-btn').forEach(btn => {
    btn.classList.toggle('ativa', btn.dataset.aba === aba);
  });
  document.querySelectorAll('.painel-secao').forEach(sec => {
    sec.classList.toggle('ativa', sec.dataset.secao === aba);
  });
}

/* ──────────────────────────────────────────────────────────────
   LISTENERS DE EVENTOS
   ────────────────────────────────────────────────────────────── */
function iniciarListeners() {
  // Navegação de capítulos
  document.getElementById('btn-cap-anterior')?.addEventListener('click', () => {
    irCapitulo(Leitor.estado.capituloAtual - 1);
  });
  document.getElementById('btn-cap-proximo')?.addEventListener('click', () => {
    irCapitulo(Leitor.estado.capituloAtual + 1);
  });

  // Painel
  document.getElementById('btn-painel')?.addEventListener('click', alternarPainel);

  // Abas do painel
  document.querySelectorAll('.painel-aba-btn').forEach(btn => {
    btn.addEventListener('click', () => mudarAba(btn.dataset.aba));
  });

  // Configurações de fonte
  document.querySelectorAll('[data-config-fonte]').forEach(btn => {
    btn.addEventListener('click', () => {
      Leitor.prefs.fonte = btn.dataset.configFonte;
      document.body.dataset.fonte = Leitor.prefs.fonte;
      document.querySelectorAll('[data-config-fonte]').forEach(b =>
        b.classList.toggle('ativo', b === btn)
      );
      salvarPreferencias();
    });
  });

  // Configurações de fundo
  document.querySelectorAll('[data-fundo].fundo-amostra').forEach(el => {
    el.addEventListener('click', () => {
      Leitor.prefs.fundoLeitura = el.dataset.fundo;
      document.body.dataset.fundo = Leitor.prefs.fundoLeitura;
      document.querySelectorAll('.fundo-amostra').forEach(e =>
        e.classList.toggle('ativo', e === el)
      );
      salvarPreferencias();
    });
  });

  // Configurações de largura
  document.querySelectorAll('[data-config-largura]').forEach(btn => {
    btn.addEventListener('click', () => {
      Leitor.prefs.larguraColuna = btn.dataset.configLargura;
      document.body.dataset.largura = Leitor.prefs.larguraColuna;
      document.querySelectorAll('[data-config-largura]').forEach(b =>
        b.classList.toggle('ativo', b === btn)
      );
      salvarPreferencias();
    });
  });

  // Slider de tamanho
  const slider = document.getElementById('slider-tamanho');
  const valorEl = document.getElementById('valor-tamanho');
  if (slider) {
    slider.addEventListener('input', () => {
      Leitor.prefs.tamanhoFonte = parseInt(slider.value);
      aplicarTamanhoFonte(Leitor.prefs.tamanhoFonte);
      if (valorEl) valorEl.textContent = `${Leitor.prefs.tamanhoFonte}px`;
      salvarPreferencias();
    });
  }

  // Slider de altura de linha
  const sliderLinha = document.getElementById('slider-linha');
  const valorLinha  = document.getElementById('valor-linha');
  if (sliderLinha) {
    sliderLinha.addEventListener('input', () => {
      Leitor.prefs.alturaLinha = parseFloat(sliderLinha.value);
      aplicarAlturaLinha(Leitor.prefs.alturaLinha);
      if (valorLinha) valorLinha.textContent = Leitor.prefs.alturaLinha.toFixed(1);
      salvarPreferencias();
    });
  }

  // Cor de anotação
  document.querySelectorAll('.anot-cor-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      Leitor.estado.anotacaoCor = btn.dataset.cor;
      document.querySelectorAll('.anot-cor-btn').forEach(b =>
        b.classList.toggle('ativo', b === btn)
      );
    });
  });

  // Salvar anotação
  document.getElementById('btn-salvar-anot')?.addEventListener('click', () => {
    const ta = document.getElementById('anot-textarea');
    if (ta) criarAnotacao(ta.value, Leitor.estado.anotacaoCor);
  });

  // Marcações — botões do menu de seleção
  document.querySelectorAll('[data-marca-cor]').forEach(btn => {
    btn.addEventListener('click', () => marcarSelecao(btn.dataset.marcaCor));
  });
  document.getElementById('btn-sel-anotar')?.addEventListener('click', anotarSelecao);

  // Estrelas
  document.querySelectorAll('.estrela-leitor').forEach((el, i) => {
    el.addEventListener('click', () => avaliar(i + 1));
    el.addEventListener('mouseenter', () => {
      document.querySelectorAll('.estrela-leitor').forEach((e, j) => {
        e.style.color = j <= i ? 'var(--ouro)' : '';
      });
    });
    el.addEventListener('mouseleave', () => {
      renderizarEstrelas(Leitor.dados.avaliacaoAtual);
    });
  });

  // Atalhos de teclado
  document.addEventListener('keydown', e => {
    if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT') return;
    if (e.key === 'ArrowRight' || e.key === 'PageDown') irCapitulo(Leitor.estado.capituloAtual + 1);
    if (e.key === 'ArrowLeft'  || e.key === 'PageUp')   irCapitulo(Leitor.estado.capituloAtual - 1);
    if (e.key === 'Escape') { alternarPainel(); }
  });

  // Fechar painel clicando fora (mobile)
  document.addEventListener('click', e => {
    const painel   = document.getElementById('leitor-painel');
    const btnPain  = document.getElementById('btn-painel');
    if (painel && Leitor.estado.painelAberto &&
        !painel.contains(e.target) && !btnPain?.contains(e.target)) {
      Leitor.estado.painelAberto = false;
      painel.classList.remove('aberto');
      btnPain?.classList.remove('ativo');
    }
  });

  // Salvar progresso ao sair da página
  window.addEventListener('beforeunload', () => {
    const scroll = window.scrollY;
    const perc   = Leitor.estado.percentual;
    // Beacon API — não bloqueia o fechamento
    navigator.sendBeacon(
      `${Leitor.BASE_URL}/leitor.php`,
      JSON.stringify({
        acao:          'salvar_progresso',
        livro:         Leitor.livro.slug,
        capitulo:      Leitor.estado.capituloAtual,
        scroll:        Math.round(scroll),
        percentual:    Math.round(perc * 100) / 100,
        total_paginas: Leitor.livro.totalCapitulos,
      })
    );
  });
}

/* ──────────────────────────────────────────────────────────────
   UTILITÁRIOS
   ────────────────────────────────────────────────────────────── */
function mostrarToast(msg, tipo = 'ok') {
  const toast = document.getElementById('leitor-toast');
  if (!toast) return;
  toast.textContent = msg;
  toast.style.borderColor = tipo === 'erro' ? '#c0392b' : 'var(--ouro)';
  toast.classList.add('visivel');
  setTimeout(() => toast.classList.remove('visivel'), 3000);
}

function mostrarErroFatal(msg) {
  const wrapper = document.getElementById('leitor-wrapper');
  if (wrapper) {
    wrapper.innerHTML = `
      <div class="leitor-acesso-negado">
        <span class="acesso-icone">⚠️</span>
        <h2>Erro</h2>
        <p>${msg}</p>
        <a href="../livros.html" class="btn-acesso">← Voltar à Biblioteca</a>
      </div>
    `;
  }
}

function escapeHtml(str) {
  return String(str)
    .replace(/&/g,  '&amp;')
    .replace(/</g,  '&lt;')
    .replace(/>/g,  '&gt;')
    .replace(/"/g,  '&quot;')
    .replace(/'/g,  '&#39;');
}

// Expõe funções usadas no HTML inline
window.irCapitulo       = irCapitulo;
window.alternarPainel   = alternarPainel;
window.abrirPainel      = abrirPainel;
window.mudarAba         = mudarAba;
window.deletarAnotacao  = deletarAnotacao;
window.deletarMarcacao  = deletarMarcacao;
window.marcarSelecao    = marcarSelecao;
window.anotarSelecao    = anotarSelecao;
window.avaliar          = avaliar;
